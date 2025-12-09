<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: calendarioCitas.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Endpoint API que proporciona citas de un psicólogo en formato JSON
 *   - Se comunica con calendarioCitas.js mediante AJAX para cargar eventos
 *   - Utiliza prepared statements para prevenir inyección SQL
 *   - Retorna información formateada para FullCalendar
 * 
 * Funcionalidad:
 *   - Recibe ID del psicólogo como parámetro GET (idDoctor)
 *   - Consulta citas asociadas a ese psicólogo con datos del paciente
 *   - Construye array de eventos con formato compatible con FullCalendar
 *   - Calcula hora de finalización basada en duración
 *   - Retorna JSON con información para mostrar en el calendario
 * 
 * Parámetros GET:
 *   - idDoctor (requerido): ID del psicólogo que solicita sus citas
 * 
 * Respuesta JSON:
 *   [
 *     {
 *       id: 1,
 *       title: "Nombre Paciente",
 *       start: "2024-12-10T14:00:00",
 *       end: "2024-12-10T15:00:00",
 *       estado: "Confirmada",
 *       paciente: "Juan Pérez",
 *       motivo: "Consulta inicial",
 *       fecha: "10/12/2024",
 *       hora: "14:00"
 *     },
 *     ...más citas
 *   ]
 * 
 * Dependencias:
 *   - db.php (conexión MySQL)
 *   - Tabla: citas, pacientes
 *   - Campos requeridos: ID_Cita, Fecha_Cita, Estado, Duracion, Motivo,
 *                        ID_Paciente, ID_Psicologo, Nombre_Paciente, Apellido_Paciente
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * SECCIÓN 1: INICIALIZACIÓN Y VALIDACIÓN
 * ────────────────────────────────────────────────────────────────────────────
 */

/**
 * Iniciar sesión para obtener credenciales del usuario autenticado
 * Aunque no se valida explícitamente aquí, se asume que el cliente es autorizado
 */
session_start();

/**
 * Incluir archivo de configuración de base de datos
 * Carga la conexión MySQLi en variable global $mysqli
 */
require_once 'db.php';

/**
 * VALIDACIÓN: Verificar que el parámetro requerido idDoctor esté presente
 * 
 * Si falta:
 *   - Establecer código de respuesta HTTP 400 (Bad Request)
 *   - Retornar mensaje de error en JSON
 *   - Terminar ejecución
 */
if (!isset($_GET['idDoctor'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro idDoctor faltante']);
    exit;
}

/**
 * CONVERSIÓN DE TIPO: Convertir idDoctor a entero para validación de tipo
 * 
 * Razones:
 *   - Prevenir inyección SQL (aunque prepared statements ya protegen)
 *   - Garantizar que sea un número válido
 *   - Mejorar seguridad del tipo de dato
 */
$idDoctor = (int)$_GET['idDoctor'];

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: CONSULTA SQL
// ────────────────────────────────────────────────────────────────────────────

/**
 * CONSULTA SQL: Obtener citas del psicólogo con datos de pacientes
 * 
 * Estructura:
 *   - SELECT: Campos de la tabla citas y nombre del paciente
 *   - FROM citas: Tabla base de citas
 *   - JOIN pacientes: Acceso a información del paciente
 *   - WHERE ID_Psicologo = ?: Filtrar citas solo de este psicólogo
 * 
 * Campos seleccionados:
 *   - ID_Cita: Identificador único de la cita
 *   - Fecha_Cita: Timestamp con fecha y hora de inicio
 *   - Estado: Estado actual (Pendiente, Confirmada, Completada, Cancelada)
 *   - Duracion: Duración en minutos
 *   - Motivo: Razón de la consulta
 *   - Nombre_Paciente, Apellido_Paciente: Datos del paciente
 */
$sql = "SELECT 
            c.ID_Cita, 
            c.Fecha_Cita, 
            c.Estado, 
            c.Duracion, 
            c.Motivo,
            p.Nombre_Paciente, 
            p.Apellido_Paciente
        FROM citas c
        JOIN pacientes p ON c.ID_Paciente = p.ID_Paciente
        WHERE c.ID_Psicologo = ?";

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: PREPARED STATEMENT Y EJECUCIÓN
// ────────────────────────────────────────────────────────────────────────────

/**
 * PREPARED STATEMENT: Preparar consulta paramétrica
 * 
 * Ventajas:
 *   - Inyección SQL: Imposible inyectar código SQL en el parámetro
 *   - Reutilización: Se puede usar la misma consulta con diferentes parámetros
 *   - Rendimiento: El servidor puede cachear el plan de ejecución
 */
$stmt = $mysqli->prepare($sql);

/**
 * VINCULACIÓN DE PARÁMETROS: Asociar variables a placeholders
 * 
 * bind_param("i", $idDoctor):
 *   - "i" = integer (tipo de dato)
 *   - $idDoctor = variable a vincular
 *   - El "?" en la consulta será reemplazado por este valor de forma segura
 */
$stmt->bind_param("i", $idDoctor);

/**
 * EJECUTAR: Ejecutar consulta preparada con parámetros vinculados
 */
$stmt->execute();

/**
 * OBTENER RESULTADOS: Recuperar todas las filas como array asociativo
 */
$result = $stmt->get_result();

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: PROCESAR RESULTADOS Y CONSTRUIR EVENTOS
// ────────────────────────────────────────────────────────────────────────────

/**
 * INICIALIZAR ARRAY DE EVENTOS
 * Este array contendrá objetos formateados para FullCalendar
 */
$events = [];

/**
 * ITERAR sobre cada cita obtenida
 */
while ($cita = $result->fetch_assoc()) {
    
    /**
     * NOMBRE COMPLETO DEL PACIENTE
     * Concatenar nombre y apellido, eliminando espacios extra
     */
    $pacienteNombreCompleto = trim($cita['Nombre_Paciente'] . ' ' . $cita['Apellido_Paciente']);
    
    /**
     * HORA DE INICIO FORMATEADA
     * Extraer solo la hora y minutos de Fecha_Cita
     * Formato: "HH:MM" (ejemplo: "14:30")
     */
    $hora = date('H:i', strtotime($cita['Fecha_Cita']));
    
    /**
     * TÍTULO DEL EVENTO EN EL CALENDARIO
     * FullCalendar mostrará el nombre del paciente en el bloque de la cita
     */
    $titulo = $pacienteNombreCompleto;

    /**
     * CÁLCULO DE HORA DE FIN
     * 
     * Lógica:
     *   1. Obtener Fecha_Cita (fecha y hora de inicio)
     *   2. Sumar la duración en minutos
     *   3. Convertir a formato DateTime
     * 
     * Ejemplo:
     *   - Inicio: "2024-12-10 14:00:00"
     *   - Duración: 60 minutos
     *   - Fin: "2024-12-10 15:00:00"
     */
    $horaFin = date('Y-m-d H:i:s', strtotime($cita['Fecha_Cita'] . ' +' . $cita['Duracion'] . ' minutes'));

    /**
     * CONSTRUIR OBJETO EVENTO PARA FULLCALENDAR
     * 
     * Propiedades estándar de FullCalendar:
     *   - id: Identificador único (ID_Cita)
     *   - title: Texto visible en el calendario
     *   - start: Fecha/hora de inicio (ISO 8601)
     *   - end: Fecha/hora de finalización (ISO 8601)
     * 
     * Propiedades personalizadas (extendedProps implícito):
     *   - estado: Estado de la cita (para coloración)
     *   - paciente: Nombre completo (para modal)
     *   - motivo: Razón de la consulta (para modal)
     *   - fecha: Formateado DD/MM/YYYY (para mostrar)
     *   - hora: Formateado HH:MM (para mostrar)
     * 
     * Las propiedades personalizadas se acceden via info.event.extendedProps en calendarioCitas.js
     */
    $events[] = [
        'id' => $cita['ID_Cita'],
        'title' => $titulo,
        'start' => $cita['Fecha_Cita'],
        'end' => $horaFin,
        'estado' => $cita['Estado'],
        'paciente' => $pacienteNombreCompleto,
        'motivo' => $cita['Motivo'],
        'fecha' => date('d/m/Y', strtotime($cita['Fecha_Cita'])),
        'hora' => $hora
    ];
}

/**
 * CERRAR PREPARED STATEMENT
 * Liberar recursos del servidor de base de datos
 */
$stmt->close();

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: ENVIAR RESPUESTA JSON
// ────────────────────────────────────────────────────────────────────────────

/**
 * HEADER HTTP: Indicar que la respuesta es JSON
 * 
 * Content-Type: application/json
 *   - Tipo de contenido: JSON
 *   - charset=utf-8: Codificación de caracteres (importante para acentos)
 * 
 * Esto permite que JavaScript interprete automáticamente la respuesta como JSON
 */
header('Content-Type: application/json; charset=utf-8');

/**
 * CODIFICAR Y ENVIAR ARRAY DE EVENTOS COMO JSON
 * 
 * json_encode():
 *   - Convierte array PHP a string JSON válido
 *   - Maneja caracteres especiales automáticamente
 * 
 * Cliente (calendarioCitas.js) lo decodifica con:
 *   - dataType: 'json' en la configuración de $.ajax
 *   - Acceso directo a propiedades: response[0].id, response[0].title, etc.
 */
echo json_encode($events);


