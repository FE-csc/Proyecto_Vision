<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * ELIMINAR_CITA.PHP - API PARA CANCELACIÓN DE CITAS
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Endpoint para cancelar citas existentes. Implementa eliminación lógica
 * (soft delete) estableciendo estado como 'Cancelada' en lugar de borrar físicamente.
 * 
 * MÉTODO: POST
 * FORMATO: application/x-www-form-urlencoded
 * 
 * PARÁMETROS ESPERADOS:
 * - id (int): ID de la cita a cancelar
 * 
 * RESPUESTAS JSON:
 * - success: true/false
 * - message: Descripción del resultado (en caso de error)
 * 
 * SEGURIDAD:
 * - Validación de sesión activa
 * - Verificación de pertenencia de cita al paciente
 * - Prepared statements para prevenir SQL injection
 * - Solo el paciente dueño de la cita puede cancelarla
 * 
 * COMPORTAMIENTO:
 * - No elimina físicamente (DELETE)
 * - Actualiza estado a 'Cancelada' y limpia Fecha_Cita
 * - Permite mantener historial de citas canceladas
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: CONFIGURACIÓN INICIAL Y VALIDACIÓN DE SESIÓN
// ────────────────────────────────────────────────────────────────────────────

session_start();
header('Content-Type: application/json');

/**
 * Validación de autenticación
 * Solo usuarios logueados pueden cancelar citas
 */
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

require_once 'db.php';

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN DE MÉTODO Y PARÁMETROS
// ────────────────────────────────────────────────────────────────────────────

/**
 * Verificar método POST y parámetro 'id'
 * La petición debe ser POST con el ID de la cita a cancelar
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    
    $citaId = $_POST['id'];
    $userId = $_SESSION['user_id'];

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 3: OBTENCIÓN DEL ID DEL PACIENTE
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Consulta para obtener ID_Paciente basado en ID_Usuario de la sesión
     * Necesario para verificar pertenencia de la cita
     * 
     * SQL: SELECT con prepared statement
     * Vincula: ID_Usuario (integer)
     */
    $queryPaciente = "SELECT ID_Paciente FROM pacientes WHERE ID_Usuario = ?";
    $stmtP = $mysqli->prepare($queryPaciente);
    $stmtP->bind_param("i", $userId);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    
    if ($row = $resP->fetch_assoc()) {
        $idPaciente = $row['ID_Paciente'];

        // ────────────────────────────────────────────────────────────────────────
        // SECCIÓN 4: CANCELACIÓN (SOFT DELETE) DE LA CITA
        // ────────────────────────────────────────────────────────────────────────

        /**
         * UPDATE para cancelación lógica de la cita
         * 
         * Operaciones:
         * - Fecha_Cita = NULL: Libera el horario
         * - Estado = 'Cancelada': Marca como cancelada
         * 
         * Condiciones WHERE:
         * - ID_Cita: La cita específica
         * - ID_Paciente: Asegura que pertenece al usuario actual
         * 
         * Seguridad: Doble verificación con ID_Cita y ID_Paciente
         * evita que un usuario cancele citas de otros
         */
        $queryDelete = "UPDATE citas SET Fecha_Cita = NULL, Estado = 'Cancelada' WHERE  ID_Cita = ? AND ID_Paciente = ?";
        
        if ($stmt = $mysqli->prepare($queryDelete)) {
            $stmt->bind_param("ii", $citaId, $idPaciente);
            
            if ($stmt->execute()) {
                /**
                 * Verificar affected_rows para confirmar que se actualizó
                 * affected_rows > 0: Cita encontrada y cancelada
                 * affected_rows = 0: Cita no existe o no pertenece al usuario
                 */
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No se encontró la cita o no tienes permiso para eliminarla.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al preparar consulta.']);
        }

    } else {
        // No se encontró perfil de paciente para el usuario
        echo json_encode(['success' => false, 'message' => 'Perfil de paciente no encontrado.']);
    }
    $stmtP->close();

} else {
    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 5: MANEJO DE SOLICITUDES INVÁLIDAS
    // ────────────────────────────────────────────────────────────────────────────
    
    /**
     * Respuesta para:
     * - Método diferente a POST
     * - Parámetro 'id' no presente
     */
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
}
?>