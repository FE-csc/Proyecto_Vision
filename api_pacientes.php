<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: api_pacientes.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - API para obtener la lista de pacientes asociados a un psicólogo
 *   - Retorna información completa del paciente incluyendo datos de contacto
 *   - Incluye información de últimas sesiones y citas programadas
 *   - Valida sesión y autorización antes de retornar datos
 *   - Implementa consultas optimizadas con subconsultas para datos relacionados
 * 
 * Endpoint:
 *   - GET /api_pacientes.php
 *     Retorna lista de todos los pacientes del psicólogo autenticado
 * 
 * Estructura de respuesta JSON:
 *   {
 *     "success": boolean,
 *     "data": [
 *       {
 *         "ID_Paciente": int,
 *         "Nombre_Paciente": string,
 *         "Apellido_Paciente": string,
 *         "Nombre_Completo": string,
 *         "Telefono_Paciente": string,
 *         "Edad": int,
 *         "Fecha_Registro": datetime,
 *         "Ultima_Sesion": date|null,
 *         "Ultima_Cita": date|null
 *       },
 *       ...más pacientes
 *     ],
 *     "message": string
 *   }
 * 
 * Errores posibles:
 *   - 401: No autorizado (sin sesión activa o sin perfil de psicólogo)
 *   - 500: Error en la consulta a la base de datos
 * ════════════════════════════════════════════════════════════════════════════════
 */

// Iniciar sesión para acceder a datos del usuario autenticado
session_start();

// Configurar headers para respuesta JSON y CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Incluir archivo de conexión a la base de datos
require_once 'db.php';

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: VALIDACIÓN DE SESIÓN Y AUTORIZACIÓN
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Verificar que el usuario tenga una sesión activa
 * Si no hay sesión, retornar error 401 Unauthorized
 */
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

/**
 * Obtener el ID del psicólogo asociado al usuario autenticado
 * Esta validación garantiza que solo psicólogos registrados puedan acceder
 * Si el usuario no tiene perfil de psicólogo, se rechaza la solicitud
 */
$ID_Psicologo = obtenerIdPsicologo($mysqli, $_SESSION['user_id']);
if (!$ID_Psicologo) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No se encontró el perfil del psicólogo']);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: CONSULTA PARA OBTENER LISTA DE PACIENTES
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Consulta SQL para obtener pacientes del psicólogo
 * 
 * Características:
 *   - Usa DISTINCT para evitar duplicados en caso de múltiples citas
 *   - INNER JOIN con tabla citas para obtener solo pacientes con citas
 *   - Incluye subconsultas para obtener:
 *     * Última sesión registrada en notas clínicas (Fecha_Sesion)
 *     * Última cita programada (Fecha_Cita)
 *   - Ordenado alfabéticamente por nombre y apellido
 * 
 * Tablas utilizadas:
 *   - pacientes: Información demográfica del paciente
 *   - citas: Citas programadas (usado para relación)
 *   - notas_clinicas: Historial de sesiones (usado en subconsulta)
 */
$sql = "SELECT DISTINCT
            p.ID_Paciente, 
            p.Nombre_Paciente, 
            p.Apellido_Paciente, 
            p.Telefono_Paciente, 
            p.Edad, 
            p.Fecha_Registro, 
            CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) AS Nombre_Completo,
            -- Subconsulta 1: Última sesión registrada en notas_clinicas (Fecha_Sesion)
            -- Obtiene la fecha más reciente de sesión del paciente con este psicólogo
            (SELECT MAX(n.Fecha_Sesion) FROM notas_clinicas n WHERE n.ID_Paciente = p.ID_Paciente AND n.ID_Psicologo = ?) AS Ultima_Sesion,
            -- Subconsulta 2: Última cita programada
            -- Obtiene la fecha más reciente de cita del paciente con este psicólogo
            (SELECT MAX(c.Fecha_Cita) FROM citas c WHERE c.ID_Paciente = p.ID_Paciente AND c.ID_Psicologo = ?) AS Ultima_Cita
        FROM pacientes p
        INNER JOIN citas c ON p.ID_Paciente = c.ID_Paciente
        WHERE c.ID_Psicologo = ?
        ORDER BY p.Nombre_Paciente, p.Apellido_Paciente";

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: PREPARAR Y EJECUTAR CONSULTA
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Preparar la consulta SQL con parámetros seguros
 * Validar que la preparación sea exitosa
 */
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en preparación de consulta: ' . $mysqli->error]);
    exit;
}

/**
 * Vincular parámetros de la consulta
 * Se usan tres veces el ID_Psicologo:
 *   1. Para la subconsulta de última sesión
 *   2. Para la subconsulta de última cita
 *   3. Para la condición WHERE principal
 * Tipo: 'iii' = three integers (tres enteros)
 */
$stmt->bind_param('iii', $ID_Psicologo, $ID_Psicologo, $ID_Psicologo);

/**
 * Ejecutar la consulta preparada
 */
$stmt->execute();
$res = $stmt->get_result();

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 4: PROCESAR RESULTADOS Y RETORNAR RESPUESTA
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Validar que la consulta se ejecutó correctamente
 */
if (!$res) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener pacientes: ' . $mysqli->error]);
    exit;
}

/**
 * Acumular todos los resultados en un array asociativo
 * Cada elemento contiene la información completa del paciente
 */
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

/**
 * Retornar respuesta exitosa con los datos de los pacientes
 * Array JSON con estructura:
 *   - success: true (indicador de operación exitosa)
 *   - data: array de objetos con información de pacientes
 */
echo json_encode(['success' => true, 'data' => $rows]);

// ════════════════════════════════════════════════════════════════════════════════
// FINALIZACIÓN
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Cerrar conexión a la base de datos
 */
$mysqli->close();

?>