
<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * EDITAR_CITA.PHP - API PARA REPROGRAMACIÓN DE CITAS
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Endpoint para editar/reprogramar citas existentes. Permite modificar
 * psicólogo, fecha y hora de una cita, validando permisos y disponibilidad.
 * 
 * MÉTODO: POST
 * FORMATO: JSON
 * 
 * PARÁMETROS ESPERADOS:
 * - id_cita (int): ID de la cita a editar
 * - id_psicologo (int, opcional): Nuevo psicólogo
 * - fecha (string, opcional): Nueva fecha (YYYY-MM-DD)
 * - hora (string, opcional): Nueva hora (HH:MM)
 * 
 * RESPUESTAS JSON:
 * - success: true/false
 * - message: Descripción del resultado
 * 
 * SEGURIDAD:
 * - Validación de sesión activa
 * - Verificación de pertenencia de cita al paciente
 * - Validación de disponibilidad del horario
 * - Prepared statements para prevenir SQL injection
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: CONFIGURACIÓN Y VALIDACIÓN DE SESIÓN
// ────────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

/**
 * Validación de autenticación
 * Solo usuarios logueados pueden editar citas
 */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: PROCESAMIENTO DE ENTRADA
// ────────────────────────────────────────────────────────────────────────────

/**
 * Decodificar datos JSON del body
 * Fallback a $_POST si no hay JSON válido
 */
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

/**
 * Extraer y validar ID de cita
 * Acepta tanto 'id' como 'id_cita' para compatibilidad
 */
$idCita = intval($input['id'] ?? $input['id_cita'] ?? 0);

if (!$idCita) {
    echo json_encode(['success' => false, 'message' => 'ID de cita no proporcionado']);
    exit;
}

$userId = $_SESSION['user_id'];

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: OBTENCIÓN DEL ID DEL PACIENTE
// ────────────────────────────────────────────────────────────────────────────

/**
 * Consulta para obtener ID_Paciente basado en ID_Usuario de la sesión
 * Necesario para verificar pertenencia de la cita
 */
$stmtP = $mysqli->prepare("SELECT ID_Paciente FROM pacientes WHERE ID_Usuario = ?");
$stmtP->bind_param('i', $userId);
$stmtP->execute();
$resP = $stmtP->get_result();
$paciente = $resP->fetch_assoc();
$stmtP->close();

if (!$paciente) {
    echo json_encode(['success' => false, 'message' => 'Perfil de paciente no encontrado']);
    exit;
}
$idPaciente = $paciente['ID_Paciente'];

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: OBTENCIÓN DE DATOS ACTUALES DE LA CITA
// ────────────────────────────────────────────────────────────────────────────

/**
 * Consulta para obtener información actual de la cita
 * - Verifica que la cita exista
 * - Verifica que pertenezca al paciente (seguridad)
 * - Obtiene valores actuales para usar como fallback
 * 
 * SQL: SELECT con formateo de fecha
 * - DATE(): Extrae solo la fecha
 * - DATE_FORMAT(): Formatea hora como HH:MM
 */
$stmtGet = $mysqli->prepare("SELECT ID_Psicologo, DATE(Fecha_Cita) as Fecha, DATE_FORMAT(Fecha_Cita, '%H:%i') as Hora FROM citas WHERE ID_Cita = ? AND ID_Paciente = ?");
$stmtGet->bind_param('ii', $idCita, $idPaciente);
$stmtGet->execute();
$resCita = $stmtGet->get_result();
$citaActual = $resCita->fetch_assoc();
$stmtGet->close();

if (!$citaActual) {
    echo json_encode(['success' => false, 'message' => 'Cita no encontrada o no tienes permiso para editarla']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: PREPARACIÓN DE NUEVOS VALORES
// ────────────────────────────────────────────────────────────────────────────

/**
 * Determinar valores finales para la actualización
 * Si el campo no viene en $input, se mantiene el valor actual (fallback)
 * 
 * Campos editables:
 * - ID_Psicologo: Cambiar de psicólogo
 * - Fecha: Nueva fecha de cita
 * - Hora: Nuevo horario
 */
$nuevoIdPsicologo = !empty($input['id_psicologo']) ? intval($input['id_psicologo']) : $citaActual['ID_Psicologo'];
$nuevaFecha       = !empty($input['fecha']) ? trim($input['fecha']) : $citaActual['Fecha'];
$nuevaHora        = !empty($input['hora']) ? trim($input['hora']) : $citaActual['Hora'];

/**
 * Construir datetime completo
 * Formato: YYYY-MM-DD HH:MM:SS (MySQL DATETIME)
 */
$nuevaFechaCompleta = $nuevaFecha . ' ' . $nuevaHora;

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 6: VALIDACIÓN DE DISPONIBILIDAD
// ────────────────────────────────────────────────────────────────────────────

/**
 * Verificar que el nuevo horario no esté ocupado
 * 
 * Condiciones de la consulta:
 * - Mismo psicólogo (ID_Psicologo)
 * - Misma fecha/hora (Fecha_Cita)
 * - Estado diferente de 'cancelada' (solo citas activas)
 * - ID_Cita diferente (excluir la cita actual)
 * 
 * Si encuentra algún resultado, el horario está ocupado
 */
$stmtCheck = $mysqli->prepare("
    SELECT 1 FROM citas 
    WHERE ID_Psicologo = ? 
      AND Fecha_Cita = ? 
      AND Estado <> 'cancelada' 
      AND ID_Cita != ? 
    LIMIT 1
");
$stmtCheck->bind_param('isi', $nuevoIdPsicologo, $nuevaFechaCompleta, $idCita);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'El horario seleccionado ya está ocupado.']);
    $stmtCheck->close();
    exit;
}
$stmtCheck->close();

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 7: ACTUALIZACIÓN DE LA CITA
// ────────────────────────────────────────────────────────────────────────────

/**
 * UPDATE de la cita con nuevos valores
 * 
 * Campos actualizados:
 * - ID_Psicologo: Nuevo psicólogo asignado
 * - Fecha_Cita: Nueva fecha/hora completa
 * 
 * Seguridad:
 * - WHERE con ID_Cita y ID_Paciente (doble verificación)
 * - Prepared statement con bind_param('isii')
 * 
 * Respuestas:
 * - Éxito: success=true, message de confirmación
 * - Error: success=false, message con detalle del error MySQL
 */
$stmtUpdate = $mysqli->prepare("UPDATE citas SET ID_Psicologo = ?, Fecha_Cita = ? WHERE ID_Cita = ? AND ID_Paciente = ?");
$stmtUpdate->bind_param('isii', $nuevoIdPsicologo, $nuevaFechaCompleta, $idCita, $idPaciente);

if ($stmtUpdate->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cita actualizada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar en BD: ' . $mysqli->error]);
}
$stmtUpdate->close();
?>