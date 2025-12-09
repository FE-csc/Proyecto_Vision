<?php
/**
 * Reserva_Cita.php
 * ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: API para crear/registrar nuevas citas clínicas
 * 
 * FUNCIONALIDAD PRINCIPAL:
 * - Recibe solicitud de reserva de cita desde cliente (JSON o POST)
 * - Valida autenticación del usuario (paciente)
 * - Obtiene ID de paciente desde usuario autenticado
 * - Inserta nueva cita en base de datos con estado "Pendiente"
 * - Retorna respuesta JSON con ID de cita creada o error
 * 
 * PARÁMETROS DE ENTRADA (JSON o POST):
 * - fecha: Fecha de la cita (formato: YYYY-MM-DD)
 * - hora: Hora de la cita (formato: HH:mm)
 * - id_psicologo: ID del psicólogo asignado
 * - motivo: Razón/motivo de la cita (opcional)
 * - duracion: Duración en minutos (default: 60)
 * 
 * RESPUESTA JSON:
 * - success: boolean (true/false)
 * - id_cita: ID de la cita creada (si success=true)
 * - message: Mensaje de error (si success=false)
 * 
 * VALIDACIONES:
 * - Requiere usuario autenticado en sesión
 * - Valida presencia de fecha, hora e ID de psicólogo
 * - Verifica existencia del perfil de paciente
 * - Valida completitud de datos antes de insertar
 * 
 * PROCESAMIENTO:
 * - Combina fecha y hora en un datetime para inserción
 * - Establece estado inicial como "Pendiente"
 * - Usa prepared statements para seguridad SQL
 * - Retorna JSON con resultado de operación
 * 
 * DEPENDENCIAS:
 * - db.php: Conexión a base de datos ($mysqli)
 * - Tabla: citas, pacientes
 * - Campos de citas: ID_Paciente, ID_Psicologo, Fecha_Cita, Motivo, Estado, Duracion
 * 
 * ACCESO: Solo pacientes autenticados
 * MÉTODO: POST (JSON recomendado, soporta también POST form-data)
 * ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: VALIDACIÓN DE AUTENTICACIÓN
// Verifica que el usuario esté autenticado antes de procesar
// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']); 
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: RECEPCIÓN Y PROCESAMIENTO DE DATOS
// Obtiene datos desde JSON (recomendado) o POST form-data
// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST; 
}
if (!is_array($input) || empty($input)) {
    echo json_encode(['success' => false, 'message' => 'Sin datos recibidos']);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: EXTRACCIÓN Y VALIDACIÓN DE PARÁMETROS
// Obtiene y valida cada parámetro requerido
// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

$userId = $_SESSION['user_id'];
$fecha = isset($input['fecha']) ? trim($input['fecha']) : '';
$hora = isset($input['hora']) ? trim($input['hora']) : '';
$idPsicologo = isset($input['id_psicologo']) ? intval($input['id_psicologo']) : 0;
$motivo = isset($input['motivo']) ? trim($input['motivo']) : null; // Campo opcional
$duracion = isset($input['duracion']) ? intval($input['duracion']) : 60; // Default: 60 minutos

// Validar parámetros requeridos
if (!$fecha || !$hora || !$idPsicologo) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']); 
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 4: OBTENCIÓN DEL PERFIL DE PACIENTE
// Consulta base de datos para obtener ID de paciente del usuario autenticado
// ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

$q = $mysqli->prepare("SELECT ID_Paciente FROM pacientes WHERE ID_Usuario = ?");
$q->bind_param('i', $userId);
$q->execute();
$res = $q->get_result();
$paciente = $res->fetch_assoc();

if (!$paciente) {
    echo json_encode(['success' => false, 'message' => 'Perfil de paciente no encontrado']); 
    exit;
}

$idPaciente = $paciente['ID_Paciente'];
$fechaCompleta = $fecha . ' ' . $hora;


$stmt = $mysqli->prepare("INSERT INTO citas 
    (ID_Paciente, ID_Psicologo, Fecha_Cita, Motivo, Estado, Duracion) 
    VALUES (?, ?, ?, ?, 'Pendiente', ?)");
$stmt->bind_param('iissi', $idPaciente, $idPsicologo, $fechaCompleta, $motivo, $duracion);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id_cita' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error BD: ' . $mysqli->error]);
}
?>
