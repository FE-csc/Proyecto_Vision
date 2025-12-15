<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * HORAS_OCUPADAS.PHP - API PARA OBTENER HORARIOS NO DISPONIBLES
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Endpoint que retorna las horas ocupadas de un psicólogo en una fecha específica.
 * Utilizado para deshabilitar horarios ya reservados en calendarios de citas.
 * 
 * MÉTODO: GET
 * FORMATO: application/json
 * 
 * PARÁMETROS ESPERADOS:
 * - id_psicologo (int): ID del psicólogo
 * - fecha (string): Fecha en formato YYYY-MM-DD
 * 
 * RESPUESTAS JSON:
 * - success: true/false
 * - horas: Array de strings con horas ocupadas en formato HH:MM (si exitoso)
 * - message: Descripción del error (si falla)
 * 
 * CÓDIGOS HTTP:
 * - 200: Éxito
 * - 401: No autorizado (sesión inválida)
 * - 500: Error en base de datos
 * 
 * SEGURIDAD:
 * - Validación obligatoria de sesión activa
 * - Validación de formato de fecha con regex
 * - Prepared statement para prevenir SQL injection
 * - Sanitización de parámetros con intval y trim
 * 
 * DEPENDENCIAS:
 * - db.php: Conexión a base de datos
 * 
 * EJEMPLO DE USO:
 * GET /Horas_Ocupadas.php?id_psicologo=5&fecha=2025-12-09
 * Respuesta: {"success":true,"horas":["09:00","10:30","14:00"]}
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: CONFIGURACIÓN INICIAL Y VALIDACIÓN DE SESIÓN
// ────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

/**
 * Validar autenticación del usuario
 * Solo usuarios logueados pueden consultar disponibilidad
 */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN Y SANITIZACIÓN DE PARÁMETROS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Obtener parámetro id_psicologo
 * intval(): Convierte a entero, previene SQL injection
 * Valor por defecto: 0 (inválido)
 */
$idPsicologo = isset($_GET['id_psicologo']) ? intval($_GET['id_psicologo']) : 0;

/**
 * Obtener parámetro fecha
 * trim(): Elimina espacios en blanco
 * Formato esperado: YYYY-MM-DD
 */
$fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';

/**
 * Validar que ambos parámetros estén presentes y sean válidos
 * 
 * Validaciones:
 * - idPsicologo: Debe ser > 0
 * - fecha: No puede estar vacía
 * - fecha: Debe coincidir con formato YYYY-MM-DD
 * 
 * Regex: ^\d{4}-\d{2}-\d{2}$
 * - ^ y $: Coincidencia exacta (completa)
 * - \d{4}: Exactamente 4 dígitos (año)
 * - -: Guión literal
 * - \d{2}: Exactamente 2 dígitos (mes)
 * - -: Guión literal
 * - \d{2}: Exactamente 2 dígitos (día)
 */
if (!$idPsicologo || !$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['success' => false, 'message' => 'Parametros inválidos']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: CONSTRUCCIÓN DEL RANGO DE FECHA
// ────────────────────────────────────────────────────────────────────────────

/**
 * Crear timestamps para inicio y fin del día
 * Permite buscar todas las citas dentro de ese día
 * 
 * Formato MySQL DATETIME: YYYY-MM-DD HH:MM:SS
 */
$inicioDia = $fecha . ' 00:00:00';  // Principio del día (00:00:00)
$finDia = $fecha . ' 23:59:59';     // Final del día (23:59:59)

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: CONSULTA A BASE DE DATOS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Preparar y ejecutar consulta SQL
 * 
 * SELECT: Obtener hora formateada como HH:MM
 *   DATE_FORMAT(Fecha_Cita, '%H:%i') - Extrae horas y minutos
 * 
 * FROM: Tabla citas
 * 
 * WHERE Condiciones:
 * - ID_Psicologo = ?: Psicólogo específico
 * - Fecha_Cita BETWEEN ? AND ?: Dentro del rango del día
 * - Estado <> 'cancelada': Solo citas activas (excluir canceladas)
 * 
 * Prepared statement: Vincula parámetros con bind_param
 */
$stmt = $mysqli->prepare("SELECT DATE_FORMAT(Fecha_Cita, '%H:%i') AS hora FROM citas WHERE ID_Psicologo = ? AND Fecha_Cita BETWEEN ? AND ? AND Estado <> 'cancelada'");

/**
 * Vincular parámetros
 * 'iss': integer, string, string
 * - $idPsicologo (int): ID del psicólogo
 * - $inicioDia (string): Inicio del día
 * - $finDia (string): Final del día
 */
$stmt->bind_param('iss', $idPsicologo, $inicioDia, $finDia);

/**
 * Ejecutar consulta
 * Manejo de errores: HTTP 500 si falla
 */
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en consulta: ' . $stmt->error]);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: PROCESAMIENTO DE RESULTADOS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Obtener resultado de la consulta
 */
$result = $stmt->get_result();

/**
 * Array para almacenar horas ocupadas
 */
$horas = [];

/**
 * Iterar sobre cada fila del resultado
 * Agregar hora al array si no está vacía
 * Formato: HH:MM (ejemplo: "09:00", "14:30")
 */
while ($row = $result->fetch_assoc()) {
    if (!empty($row['hora'])) {
        $horas[] = $row['hora'];
    }
}

/**
 * Cerrar statement
 */
$stmt->close();

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 6: RESPUESTA JSON
// ────────────────────────────────────────────────────────────────────────────

/**
 * Retornar respuesta exitosa con lista de horas ocupadas
 * 
 * success: true - Indica que la consulta fue exitosa
 * horas: Array de strings con horarios ocupados en formato HH:MM
 *        Array vacío si no hay citas reservadas
 * 
 * El cliente puede usar este array para deshabilitar slots en calendario
 */
echo json_encode(['success' => true, 'horas' => $horas]);
