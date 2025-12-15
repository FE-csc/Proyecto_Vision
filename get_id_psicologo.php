<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * GET_ID_PSICOLOGO.PHP - API PARA OBTENER ID DEL PSICÓLOGO
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Endpoint que retorna el ID del psicólogo autenticado basado en su sesión.
 * Utiliza la función obtenerIdPsicologo() de db.php para realizar la consulta.
 * 
 * MÉTODO: GET
 * FORMATO: application/json
 * 
 * RESPUESTAS JSON:
 * - success: true/false
 * - ID_Psicologo: ID del psicólogo (si existe)
 * - message: Descripción del error (si falla)
 * 
 * CÓDIGOS HTTP:
 * - 200: Éxito
 * - 401: No autorizado (sesión inválida o perfil no encontrado)
 * 
 * SEGURIDAD:
 * - Validación obligatoria de sesión activa
 * - Verificación de existencia del perfil de psicólogo
 * - Usa helper function obtenerIdPsicologo() de db.php
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y CONFIGURACIÓN
// ────────────────────────────────────────────────────────────────────────────

session_start();
header('Content-Type: application/json');

require_once 'db.php';

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN DE SESIÓN
// ────────────────────────────────────────────────────────────────────────────

/**
 * Verificar que existe una sesión de usuario autenticado
 * Solo psicólogos logueados pueden obtener su ID
 * Retorna HTTP 401 si no está autenticado
 */
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: OBTENCIÓN DEL ID DEL PSICÓLOGO
// ────────────────────────────────────────────────────────────────────────────

/**
 * Llamar a función helper obtenerIdPsicologo()
 * Ubicación: db.php
 * 
 * Realiza una consulta para obtener el ID_Psicologo basado en ID_Usuario
 * 
 * @param mysqli $mysqli - Conexión a base de datos
 * @param int $_SESSION['user_id'] - ID del usuario de la sesión
 * @return int|false ID_Psicologo o false si no existe el perfil
 */
$ID_Psicologo = obtenerIdPsicologo($mysqli, $_SESSION['user_id']);

if (!$ID_Psicologo) {
    // No se encontró perfil de psicólogo para el usuario
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No se encontró el perfil del psicólogo']);
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: RESPUESTA EXITOSA
// ────────────────────────────────────────────────────────────────────────────

/**
 * Retornar respuesta JSON con el ID del psicólogo
 * success: true indica que la operación fue exitosa
 * ID_Psicologo: ID que se usa en consultas posteriores
 */
echo json_encode(['success' => true, 'ID_Psicologo' => $ID_Psicologo]);

/**
 * Cerrar conexión a base de datos
 */
$mysqli->close();
?>
