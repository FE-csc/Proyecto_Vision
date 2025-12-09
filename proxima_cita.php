<?php
header("Content-Type: application/json");
include "db.php";   // <-- Importante para tener $mysqli

// Verificar ID del paciente
$idPaciente = $_GET['idPaciente'] ?? null;

if (!$idPaciente) {
    echo json_encode(["error" => "Falta el ID del paciente"]);
    exit;
}

// Consulta de la próxima cita
$sql = "
SELECT 
    c.ID_Cita,
    c.Fecha_Cita,
    c.Motivo,
    c.Estado,
    c.Duracion,
    p.Nombre_Psicologo,
    p.Apellido_Psicologo
FROM citas c
INNER JOIN psicologos p ON c.ID_Psicologo = p.ID_Psicologo
WHERE c.ID_Paciente = ?
  AND c.Fecha_Cita >= NOW()
ORDER BY c.Fecha_Cita ASC
LIMIT 1
";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Error al preparar la consulta: " . $mysqli->error]);
    exit;
}

$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["message" => "No hay próximas citas"]);
    exit;
}

$cita = $result->fetch_assoc();
echo json_encode($cita);
?>
