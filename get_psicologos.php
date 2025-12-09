<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * GET_PSICOLOGOS.PHP - API PARA OBTENER PSICÓLOGOS POR ESPECIALIDAD
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Endpoint que retorna una lista de psicólogos filtrados por especialidad.
 * Utilizado para poblar selectores/combos en formularios de reserva de citas.
 * 
 * MÉTODO: GET
 * FORMATO: application/json
 * 
 * PARÁMETROS ESPERADOS:
 * - especialidad (int): ID de la especialidad para filtrar psicólogos
 * 
 * RESPUESTAS JSON:
 * - success: true/false
 * - psicologos: Array de objetos con id y nombre (si exitoso)
 * - message: Descripción del error (si falla)
 * 
 * CÓDIGOS HTTP:
 * - 200: Éxito
 * - 401: No autorizado (sesión inválida)
 * - 500: Error en base de datos
 * 
 * SEGURIDAD:
 * - Validación obligatoria de sesión activa
 * - Prepared statement para prevenir SQL injection
 * - Validación y sanitización de parámetro especialidad (intval)
 * 
 * DEPENDENCIAS:
 * - db.php: Conexión a base de datos
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
 * Solo usuarios logueados pueden acceder a esta API
 */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN DE PARÁMETROS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Obtener y validar parámetro 'especialidad'
 * - intval(): Garantiza que sea número entero
 * - Valor por defecto: 0 (inválido)
 */
$idEsp = isset($_GET['especialidad']) ? intval($_GET['especialidad']) : 0;

if (!$idEsp) {
    echo json_encode(['success' => false, 'message' => 'Especialidad requerida']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: CONSULTA A BASE DE DATOS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Preparar consulta SQL
 * 
 * SELECT: ID_Psicologo, nombres
 * FROM: Tabla psicologos
 * WHERE: ID_Especialidad coincide con parámetro
 * 
 * Prepared statement previene SQL injection
 */
if (!$stmt = $mysqli->prepare("SELECT ID_Psicologo, Nombre_Psicologo, Apellido_Psicologo FROM psicologos WHERE ID_Especialidad = ?")) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en consulta (prep): '.$mysqli->error]);
    exit;
}

/**
 * Vincular parámetro ID_Especialidad (integer)
 */
$stmt->bind_param('i', $idEsp);

/**
 * Ejecutar consulta
 * Manejo de errores con HTTP 500 si falla
 */
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en consulta (exec): '.$stmt->error]);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: PROCESAMIENTO DE RESULTADOS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Obtener resultado de la consulta
 */
$res = $stmt->get_result();

/**
 * Array para almacenar psicólogos en formato simplificado
 */
$psicologos = [];

/**
 * Iterar sobre cada fila del resultado
 * Construir array con estructura:
 * [
 *   'id' => ID_Psicologo,
 *   'nombre' => 'Nombre Apellido' (combinado)
 * ]
 */
while ($row = $res->fetch_assoc()) {
    $psicologos[] = [
        'id' => $row['ID_Psicologo'],
        'nombre' => trim($row['Nombre_Psicologo'].' '.$row['Apellido_Psicologo'])
    ];
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: RESPUESTA JSON
// ────────────────────────────────────────────────────────────────────────────

/**
 * Retornar respuesta exitosa con lista de psicólogos
 * success: true
 * psicologos: Array con id y nombre de cada psicólogo
 */
echo json_encode(['success' => true, 'psicologos' => $psicologos]);
?>
