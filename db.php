<?php
$mysqli = new mysqli('b8y9eccnoennbc03jxfn-mysql.services.clever-cloud.com', 'uonk3hj3tnpjdpfq', 'jywVsjXDFGN6cHLfQscM', 'b8y9eccnoennbc03jxfn');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: ' . $mysqli->connect_error]);
    exit;
}

/**
 * Obtiene el ID del psicólogo basado en el ID del usuario de la sesión
 * @param mysqli $mysqli Conexión a la BD
 * @param int $user_id ID del usuario (from $_SESSION['user_id'])
 * @return int|null ID del psicólogo o null si no existe
 */
function obtenerIdPsicologo(&$mysqli, $user_id) {
    $stmt = $mysqli->prepare("SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['ID_Psicologo'];
    }
    return null;
}
