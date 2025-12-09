<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

$sql = "SELECT DISTINCT
            p.ID_Paciente, 
            p.Nombre_Paciente, 
            p.Apellido_Paciente, 
            p.Telefono_Paciente, 
            p.Edad, 
            p.Fecha_Registro, 
            CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) AS Nombre_Completo,
            -- Última sesión registrada en notas_clinicas (Fecha_Sesion)
            (SELECT MAX(n.Fecha_Sesion) FROM notas_clinicas n WHERE n.ID_Paciente = p.ID_Paciente AND n.ID_Psicologo = ?) AS Ultima_Sesion,
            -- Última cita programada (opcional)
            (SELECT MAX(c.Fecha_Cita) FROM citas c WHERE c.ID_Paciente = p.ID_Paciente AND c.ID_Psicologo = ?) AS Ultima_Cita
        FROM pacientes p
        INNER JOIN citas c ON p.ID_Paciente = c.ID_Paciente
        WHERE c.ID_Psicologo = ?
        ORDER BY p.Nombre_Paciente, p.Apellido_Paciente";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en preparación de consulta: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param('iii', $ID_Psicologo, $ID_Psicologo, $ID_Psicologo);
$stmt->execute();
$res = $stmt->get_result();

if (!$res) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener pacientes: ' . $mysqli->error]);
    exit;
}

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
echo json_encode(['success' => true, 'data' => $rows]);

$mysqli->close();