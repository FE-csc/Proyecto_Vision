<?php
/**
 * calendarioPaciente.php
 * ----------------------
 * Backend que devuelve las citas de un paciente en formato JSON para FullCalendar.
 * 
 * Flujo:
 * 1. Recibe el parámetro GET `idPaciente`.
 * 2. Consulta la base de datos por las citas de ese paciente.
 * 3. Devuelve un arreglo JSON con los datos necesarios para renderizar en FullCalendar.
 */

session_start();
require_once 'db.php';

// Validar que se reciba el parámetro idPaciente
if (!isset($_GET['idPaciente'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro idPaciente faltante']);
    exit;
}

$idPaciente = (int)$_GET['idPaciente'];

// Consulta SQL: obtener citas y datos del psicólogo
$sql = "SELECT 
            c.ID_Cita, c.Fecha_Cita, c.Estado, c.Duracion, c.Motivo,
            s.Nombre_Psicologo, s.Apellido_Psicologo
        FROM citas c
        JOIN psicologos s ON c.ID_Psicologo = s.ID_Psicologo
        WHERE c.ID_Paciente = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($cita = $result->fetch_assoc()) {
    // Construir nombre completo del psicólogo
    $psicologoNombreCompleto = trim($cita['Nombre_Psicologo'] . ' ' . $cita['Apellido_Psicologo']);
    // Hora de inicio formateada
    $hora = date('H:i', strtotime($cita['Fecha_Cita']));
    // Texto que se mostrará en el bloque del calendario
    $titulo = $psicologoNombreCompleto;

    // Armar evento para FullCalendar
    $events[] = [
        'id' => $cita['ID_Cita'],
        'title' => $titulo,
        'start' => $cita['Fecha_Cita'], // inicio
        'end' => date('Y-m-d H:i:s', strtotime($cita['Fecha_Cita'].' +'.$cita['Duracion'].' minutes')), // fin calculado
        'estado' => $cita['Estado'],
        'psicologo' => $psicologoNombreCompleto,
        'motivo' => $cita['Motivo'],
        'fecha' => date('d/m/Y', strtotime($cita['Fecha_Cita'])),
        'hora' => $hora
    ];
}
$stmt->close();

// Devolver JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($events);
