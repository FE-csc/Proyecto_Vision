<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: Obtener_Cita.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: API REST endpoint para obtener citas del usuario autenticado
// Devuelve todas las citas del paciente en formato JSON (para calendarios, vistas)
// Realiza validación de sesión, consulta preparada y retorna datos formateados
// FUNCIONALIDAD: Fetch appointments → Query database → JSON response
// DEPENDENCIAS: db.php (MySQLi connection), Session management
// ROLES AUTORIZADOS: Pacientes autenticados (verifica $_SESSION['user_id'])
// MÉTODOS: GET (endpoint AJAX)
// RETORNA: JSON array con citas o array vacío si no autenticado
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y CONFIGURACIÓN
// ──────────────────────────────────────────────────────────────────────────────
session_start();
// Header: Especificar que la respuesta es JSON
// Permite que JavaScript interprete correctamente con res.json()
header('Content-Type: application/json');

// Incluir archivo de conexión a base de datos
// db.php inicializa $mysqli (MySQLi object connection)
require_once 'db.php';

// Variable para respuesta (aunque solo se usa en comentario original)
$response = [];
// ──────────────────────────────────────────────────────────────────────────────

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN DE AUTENTICACIÓN
// ──────────────────────────────────────────────────────────────────────────────
/*
   CONTROL DE ACCESO: Verificar que usuario está autenticado
   empty($_SESSION['user_id']): Retorna true si:
     - $_SESSION['user_id'] no existe
     - $_SESSION['user_id'] es null
     - $_SESSION['user_id'] es 0
     - $_SESSION['user_id'] es string vacío
   
   RESPUESTA SI NO AUTENTICADO:
     - json_encode([]) retorna "[]" (JSON array vacío)
     - exit: Detener ejecución para no continuar con query
*/
if (empty($_SESSION['user_id'])) {
    echo json_encode([]); 
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: EXTRACCIÓN DE VARIABLES
// ──────────────────────────────────────────────────────────────────────────────
/*
   Obtener ID del usuario autenticado de la sesión
   Este ID se usará como parámetro en la prepared statement
*/
$userId = $_SESSION['user_id'];

// ──────────────────────────────────────────────────────────────────────────────// SECCIÓN 4: CONSTRUCCIÓN DE QUERY SQL (PREPARED STATEMENT)
// ──────────────────────────────────────────────────────────────────────────────
/*
   QUERY: Obtener todas las citas del paciente actual
   
   ESTRUCTURA:
   1. SELECT: Columnas a retornar
      - c.ID_Cita AS id: ID único de la cita
      - DATE(c.Fecha_Cita) AS date: Fecha sin hora (YYYY-MM-DD)
      - DATE_FORMAT(c.Fecha_Cita, '%H:%i') AS time: Hora en formato HH:mm
      - e.Nombre_Especialidad: Tipo de especialidad (psicología, etc.)
      - CONCAT(...) AS nombre_completo_psicologo: Nombre + Apellido del psicólogo
      - c.Estado: Estado de la cita (Confirmada, Cancelada, etc.)
   
   2. INNER JOINs: Relaciones obligatorias
      - citas → pacientes: Cada cita pertenece a un paciente
      - citas → psicologos: Cada cita es con un psicólogo
      
   3. LEFT JOIN: Relación opcional
      - psicologos → especialidades: No todo psicólogo tiene especialidad asignada
      - LEFT preserva citas aunque no haya especialidad
   
   4. WHERE: Filtro
      - p.ID_Usuario = ?: Citas del usuario actual
      - ? se reemplazará con bind_param() (previene SQL injection)
   
   5. ORDER BY: Ordenamiento
      - c.Fecha_Cita ASC: Citas más antiguas primero
*/
$query = "SELECT 
            c.ID_Cita AS id,
            DATE(c.Fecha_Cita) AS date, 
            DATE_FORMAT(c.Fecha_Cita, '%H:%i') AS time, 
            e.Nombre_Especialidad AS Nombre_Especialidad,
            CONCAT(psi.Nombre_Psicologo, ' ', psi.Apellido_Psicologo) AS nombre_completo_psicologo, 
            c.Estado 
         FROM citas c
          INNER JOIN pacientes p ON c.ID_Paciente = p.ID_Paciente
          INNER JOIN psicologos psi ON c.ID_Psicologo = psi.ID_Psicologo
          LEFT JOIN especialidades e ON psi.ID_Especialidad = e.ID_Especialidad
          WHERE p.ID_Usuario = ? 
          ORDER BY c.Fecha_Cita ASC";

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: PREPARACIÓN Y EJECUCIÓN DE STATEMENT
// ──────────────────────────────────────────────────────────────────────────────
/*
   PREPARED STATEMENT: Prevenir SQL injection
   - $mysqli->prepare($query): Compila la query con placeholder "?"
   - Retorna MySQLi_stmt object o false si hay error
*/
if ($stmt = $mysqli->prepare($query)) {
    // bind_param("i", $userId): Vincular parámetro
    // "i": Integer type (ID siempre es entero)
    // $userId: Variable a inyectar (verificada como integer)
    $stmt->bind_param("i", $userId);
    
    // Ejecutar la query compilada
    $stmt->execute();
    
    // Obtener resultado como array asociativo
    $result = $stmt->get_result();
    
    // ──────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 6: PROCESAMIENTO DE RESULTADOS
    // ──────────────────────────────────────────────────────────────────────────────
    /*
       Iterar sobre cada fila del resultado
       fetch_assoc(): Retorna array asociativo (clave => valor)
       Ejemplo: ["id" => 1, "date" => "2025-01-15", "time" => "10:30", ...]
    */
    $citas = [];
    while ($row = $result->fetch_assoc()) {
        $citas[] = $row;
    }
    
    // ──────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 7: RESPUESTA JSON
    // ──────────────────────────────────────────────────────────────────────────────
    /*
       json_encode($citas): Convertir array PHP a JSON
       Ejemplo response:
       [
         {
           "id": 1,
           "date": "2025-01-15",
           "time": "10:30",
           "Nombre_Especialidad": "Psicología Clínica",
           "nombre_completo_psicologo": "Melina Larrota",
           "Estado": "Confirmada"
         }
       ]
    */
    echo json_encode($citas);
    
    // Cerrar statement y liberar recursos
    $stmt->close();
} else {
    // ──────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 8: MANEJO DE ERRORES
    // ──────────────────────────────────────────────────────────────────────────────
    /*
       Si prepare() falla (error de sintaxis SQL, etc.):
       - Retornar array vacío [] en lugar de error
       - Evita exponer detalles de la base de datos en la respuesta
       - Cliente recibe "no appointments" en lugar de error técnico
    */
    echo json_encode([]);
}
// ──────────────────────────────────────────────────────────────────────────────
?>