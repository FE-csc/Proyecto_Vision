<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php';

// Validar sesión
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener ID del psicólogo desde la sesión
$ID_Psicologo = obtenerIdPsicologo($mysqli, $_SESSION['user_id']);
if (!$ID_Psicologo) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No se encontró el perfil del psicólogo']);
    exit;
}

echo json_encode(['success' => true, 'ID_Psicologo' => $ID_Psicologo]);
$mysqli->close();
?>
