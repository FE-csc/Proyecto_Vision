<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: api_notas.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - API RESTful para gestionar notas clínicas de pacientes
 *   - Proporciona endpoints CRUD completos (Create, Read, Update, Delete)
 *   - Implementa búsqueda y filtrado de notas
 *   - Válida sesión y autorización antes de cualquier operación
 *   - Retorna respuestas en formato JSON
 * 
 * Endpoints disponibles:
 *   - GET  ?action=listar           - Listar todas las notas del psicólogo
 *   - GET  ?action=obtener&id=N     - Obtener una nota específica por ID
 *   - GET  ?action=buscar&q=texto   - Buscar notas por texto
 *   - POST ?action=crear            - Crear nueva nota clínica
 *   - PUT  ?action=actualizar&id=N  - Actualizar una nota existente
 *   - DELETE ?action=eliminar&id=N  - Eliminar una nota
 * 
 * Parámetros adicionales:
 *   - paciente_id: Filtrar notas por paciente específico (usado con ?action=listar)
 * 
 * Estructura de respuestas JSON:
 *   Éxito:
 *   {
 *     "success": true,
 *     "data": array|object,
 *     "id": int (solo en create),
 *     "message": string
 *   }
 * 
 *   Error:
 *   {
 *     "success": false,
 *     "message": string
 *   }
 * ════════════════════════════════════════════════════════════════════════════════
 */

// Iniciar sesión para acceder a datos del usuario autenticado
session_start();

// Configurar headers CORS y JSON para la API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir archivo de conexión a la base de datos
require_once 'db.php';

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: VALIDACIÓN INICIAL DE SESIÓN Y AUTORIZACIÓN
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
// SECCIÓN 2: OBTENER MÉTODO HTTP Y ACCIÓN SOLICITADA
// ════════════════════════════════════════════════════════════════════════════════

// Obtener el método HTTP de la solicitud (GET, POST, PUT, DELETE, etc.)
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Obtener la acción solicitada desde el parámetro GET 'action'
 * La acción determina qué operación se ejecutará (listar, obtener, crear, etc.)
 */
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: FUNCIONES AUXILIARES
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Función auxiliar para retornar errores en formato JSON
 * 
 * @param string $message - Mensaje de error a mostrar
 * @param int $code - Código HTTP de error (default: 400)
 */
function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 4: FUNCIÓN - LISTAR NOTAS CLÍNICAS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Lista las notas clínicas del psicólogo autenticado
 * 
 * Características:
 *   - Muestra todas las notas del psicólogo por defecto
 *   - Permite filtrar por paciente si se proporciona parámetro 'paciente_id'
 *   - Ordena por fecha de sesión más reciente primero
 *   - Une datos de pacientes y psicólogos para información completa
 * 
 * @param mysqli $mysqli - Conexión a la base de datos
 * @param int $ID_Psicologo - ID del psicólogo autenticado
 * 
 * Parámetros GET opcionales:
 *   - paciente_id: ID del paciente para filtrar notas
 */
function listarNotas($mysqli, $ID_Psicologo) {
    /**
     * CASO 1: Listar notas de un paciente específico
     * Si se proporciona parámetro 'paciente_id', filtrar solo esas notas
     */
    if (isset($_GET['paciente_id']) && intval($_GET['paciente_id'])>0) {
        $pid = intval($_GET['paciente_id']);
        
        // Consulta SQL para obtener notas de un paciente específico
        $sql = "SELECT n.ID_Nota, n.ID_Paciente, n.ID_Psicologo, n.ID_Cita, n.Fecha_Sesion, n.Contenido, n.Resumen, n.Fecha_Creacion, n.Fecha_Actualizacion,
                    CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) AS Nombre_Paciente,
                    CONCAT(ps.Nombre_Psicologo, ' ', ps.Apellido_Psicologo) AS Nombre_Psicologo
                FROM notas_clinicas n
                INNER JOIN pacientes p ON n.ID_Paciente = p.ID_Paciente
                INNER JOIN psicologos ps ON n.ID_Psicologo = ps.ID_Psicologo
                WHERE n.ID_Paciente = ? AND n.ID_Psicologo = ?
                ORDER BY n.Fecha_Sesion DESC, n.Fecha_Actualizacion DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $pid, $ID_Psicologo);
        if (!$stmt->execute()) return json_error('Error al obtener notas: '.$mysqli->error, 500);
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success' => true, 'data' => $rows]);
        return;
    }

    /**
     * CASO 2: Listar todas las notas del psicólogo
     * Si no hay filtro de paciente, retornar todas las notas del psicólogo
     */
    $sql = "SELECT n.ID_Nota, n.ID_Paciente, n.ID_Psicologo, n.ID_Cita, n.Fecha_Sesion, n.Contenido, n.Resumen, n.Fecha_Creacion, n.Fecha_Actualizacion,
                CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) AS Nombre_Paciente,
                CONCAT(ps.Nombre_Psicologo, ' ', ps.Apellido_Psicologo) AS Nombre_Psicologo
            FROM notas_clinicas n
            INNER JOIN pacientes p ON n.ID_Paciente = p.ID_Paciente
            INNER JOIN psicologos ps ON n.ID_Psicologo = ps.ID_Psicologo
            WHERE n.ID_Psicologo = ?
            ORDER BY n.Fecha_Sesion DESC, n.Fecha_Actualizacion DESC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $ID_Psicologo);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res) return json_error('Error al obtener notas: ' . $mysqli->error, 500);

    // Acumular todos los resultados en un array
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]);
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 5: FUNCIÓN - OBTENER UNA NOTA POR ID
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Obtiene una nota clínica específica por su ID
 * 
 * Características:
 *   - Solo retorna la nota si pertenece al psicólogo autenticado
 *   - Incluye información completa del paciente y psicólogo
 * 
 * @param mysqli $mysqli - Conexión a la base de datos
 * @param int $id - ID de la nota a obtener
 * @param int $ID_Psicologo - ID del psicólogo autenticado
 * 
 * Validaciones:
 *   - La nota debe existir
 *   - La nota debe pertenecer al psicólogo autenticado
 */
function obtenerNota($mysqli, $id, $ID_Psicologo) {
    // Sanitizar el ID (convertir a entero)
    $id = intval($id);
    
    // Consulta SQL para obtener una nota específica con seguridad
    $sql = "SELECT n.ID_Nota, n.ID_Paciente, n.ID_Psicologo, n.ID_Cita, n.Fecha_Sesion, n.Contenido, n.Resumen, n.Fecha_Creacion, n.Fecha_Actualizacion,
                CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) AS Nombre_Paciente,
                CONCAT(ps.Nombre_Psicologo, ' ', ps.Apellido_Psicologo) AS Nombre_Psicologo
            FROM notas_clinicas n
            INNER JOIN pacientes p ON n.ID_Paciente = p.ID_Paciente
            INNER JOIN psicologos ps ON n.ID_Psicologo = ps.ID_Psicologo
            WHERE n.ID_Nota = ? AND n.ID_Psicologo = ? LIMIT 1";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $id, $ID_Psicologo);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Verificar si la nota fue encontrada
    if ($row = $res->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        // Nota no encontrada o pertenece a otro psicólogo
        json_error('Nota no encontrada', 404);
    }
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 6: FUNCIÓN - BUSCAR NOTAS POR TEXTO
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Busca notas clínicas por texto en múltiples campos
 * 
 * Características:
 *   - Busca en el nombre del paciente, resumen y contenido
 *   - Usa búsqueda LIKE con caracteres comodín (%)
 *   - Solo retorna notas del psicólogo autenticado
 *   - Ordena por fecha de sesión más reciente
 * 
 * @param mysqli $mysqli - Conexión a la base de datos
 * @param string $q - Término de búsqueda
 * @param int $ID_Psicologo - ID del psicólogo autenticado
 * 
 * Parámetros GET:
 *   - q: Término a buscar (requerido)
 */
function buscarNotas($mysqli, $q, $ID_Psicologo) {
    // Preparar el término de búsqueda con caracteres comodín para LIKE
    $qLike = "%" . $q . "%";
    
    // Consulta SQL para buscar en múltiples campos
    $sql = "SELECT n.ID_Nota, n.ID_Paciente, n.ID_Psicologo, n.ID_Cita, n.Fecha_Sesion, n.Contenido, n.Resumen, n.Fecha_Creacion, n.Fecha_Actualizacion,
                CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) AS Nombre_Paciente,
                CONCAT(ps.Nombre_Psicologo, ' ', ps.Apellido_Psicologo) AS Nombre_Psicologo
            FROM notas_clinicas n
            INNER JOIN pacientes p ON n.ID_Paciente = p.ID_Paciente
            INNER JOIN psicologos ps ON n.ID_Psicologo = ps.ID_Psicologo
            WHERE n.ID_Psicologo = ? AND (CONCAT(p.Nombre_Paciente, ' ', p.Apellido_Paciente) LIKE ?
               OR n.Resumen LIKE ?
               OR n.Contenido LIKE ?)
            ORDER BY n.Fecha_Sesion DESC, n.Fecha_Actualizacion DESC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isss', $ID_Psicologo, $qLike, $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Acumular todos los resultados en un array
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'data' => $rows]);
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 7: FUNCIÓN - CREAR NOTA CLÍNICA
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Crea una nueva nota clínica en la base de datos
 * 
 * Características:
 *   - Valida que todos los campos requeridos estén presentes
 *   - El resumen se genera automáticamente si no se proporciona (primeros 255 caracteres)
 *   - El ID_Cita es opcional (puede ser NULL)
 *   - Retorna el ID de la nota creada
 * 
 * @param mysqli $mysqli - Conexión a la base de datos
 * 
 * Campos requeridos (JSON):
 *   - ID_Paciente: int - ID del paciente
 *   - ID_Psicologo: int - ID del psicólogo
 *   - Fecha_Sesion: string - Fecha de la sesión (YYYY-MM-DD)
 *   - Contenido: string - Contenido de la nota clínica
 * 
 * Campos opcionales (JSON):
 *   - ID_Cita: int - ID de la cita asociada (puede omitirse)
 *   - Resumen: string - Resumen de la nota (se genera automáticamente si falta)
 */
function crearNota($mysqli) {
    // Decodificar JSON recibido en el cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) return json_error('Datos inválidos', 400);
    
    // Validar que todos los campos requeridos estén presentes
    if (empty($data['ID_Paciente']) || empty($data['ID_Psicologo']) || empty($data['Fecha_Sesion']) || empty($data['Contenido'])) {
        return json_error('Faltan campos requeridos', 400);
    }

    // Extraer y sanitizar datos del JSON
    $idPaciente = intval($data['ID_Paciente']);
    $idPsicologo = intval($data['ID_Psicologo']);
    // ID_Cita es opcional, puede ser null si no se proporciona
    $idCita = isset($data['ID_Cita']) && $data['ID_Cita'] !== '' ? intval($data['ID_Cita']) : null;
    $fechaSesion = $data['Fecha_Sesion'];
    $contenido = $data['Contenido'];
    // Generar resumen automático si no se proporciona (primeros 255 caracteres)
    $resumen = isset($data['Resumen']) ? $data['Resumen'] : mb_substr($contenido, 0, 255);

    /**
     * Insertar nueva nota clínica
     * 
     * Nota: Se usa una consulta diferente si ID_Cita es null
     * porque los parámetros preparados requieren tipos específicos
     */
    $sql = "INSERT INTO notas_clinicas (ID_Paciente, ID_Psicologo, ID_Cita, Fecha_Sesion, Contenido, Resumen) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    
    if ($idCita === null) {
        // Insertar sin ID_Cita (campo opcional)
        $sql2 = "INSERT INTO notas_clinicas (ID_Paciente, ID_Psicologo, Fecha_Sesion, Contenido, Resumen) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql2);
        $stmt->bind_param('iisss', $idPaciente, $idPsicologo, $fechaSesion, $contenido, $resumen);
    } else {
        // Insertar con ID_Cita
        $stmt->bind_param('iiisss', $idPaciente, $idPsicologo, $idCita, $fechaSesion, $contenido, $resumen);
    }

    // Ejecutar INSERT y retornar el ID generado
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $mysqli->insert_id]);
    } else {
        json_error('Error al crear nota: ' . $mysqli->error, 500);
    }
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 8: FUNCIÓN - ACTUALIZAR NOTA CLÍNICA
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Actualiza una nota clínica existente
 * 
 * Características:
 *   - Requiere el campo 'Contenido' para actualizar
 *   - El resumen se regenera automáticamente si no se proporciona
 *   - Puede actualizar opcionalmente la Fecha_Sesion
 *   - Actualiza automáticamente Fecha_Actualizacion con timestamp actual
 * 
 * @param mysqli $mysqli - Conexión a la base de datos
 * @param int $id - ID de la nota a actualizar
 * 
 * Campos requeridos (JSON):
 *   - Contenido: string - Nuevo contenido de la nota
 * 
 * Campos opcionales (JSON):
 *   - Resumen: string - Nuevo resumen (se regenera si falta)
 *   - Fecha_Sesion: string - Nueva fecha de sesión
 * 
 * Validaciones:
 *   - La nota debe existir
 *   - Debe haber cambios reales para marcar como actualizado
 */
function actualizarNota($mysqli, $id) {
    // Sanitizar el ID de la nota
    $id = intval($id);
    
    // Decodificar JSON recibido en el cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['Contenido'])) return json_error('Contenido requerido', 400);

    // Extraer campos de la solicitud
    $contenido = $data['Contenido'];
    // Generar resumen automático si no se proporciona
    $resumen = isset($data['Resumen']) ? $data['Resumen'] : mb_substr($contenido, 0, 255);
    // Fecha de sesión es opcional
    $fechaSesion = isset($data['Fecha_Sesion']) && $data['Fecha_Sesion'] !== '' ? $data['Fecha_Sesion'] : null;

    /**
     * Actualizar nota con o sin Fecha_Sesion
     * Se ejecutan consultas diferentes según si se proporciona fecha
     */
    if ($fechaSesion) {
        // UPDATE incluyendo Fecha_Sesion
        $sql = "UPDATE notas_clinicas SET Contenido = ?, Resumen = ?, Fecha_Sesion = ?, Fecha_Actualizacion = CURRENT_TIMESTAMP WHERE ID_Nota = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssi', $contenido, $resumen, $fechaSesion, $id);
    } else {
        // UPDATE sin Fecha_Sesion
        $sql = "UPDATE notas_clinicas SET Contenido = ?, Resumen = ?, Fecha_Actualizacion = CURRENT_TIMESTAMP WHERE ID_Nota = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $contenido, $resumen, $id);
    }

    // Ejecutar UPDATE
    if ($stmt->execute()) {
        // Verificar si se realizaron cambios reales
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Nota actualizada']);
        } else {
            // No se realizaron cambios (nota no existe o datos idénticos)
            json_error('Nota no encontrada o sin cambios', 404);
        }
    } else {
        json_error('Error al actualizar: ' . $mysqli->error, 500);
    }
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 9: FUNCIÓN - ELIMINAR NOTA CLÍNICA
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Elimina una nota clínica de la base de datos
 * 
 * Características:
 *   - Elimina el registro completamente de la tabla notas_clinicas
 *   - Verifica que la nota exista antes de intentar eliminar
 *   - Retorna error 404 si la nota no existe
 * 
 * @param mysqli $mysqli - Conexión a la base de datos
 * @param int $id - ID de la nota a eliminar
 * 
 * Validaciones:
 *   - La nota debe existir
 */
function eliminarNota($mysqli, $id) {
    // Sanitizar el ID de la nota
    $id = intval($id);
    
    // Preparar consulta DELETE
    $stmt = $mysqli->prepare("DELETE FROM notas_clinicas WHERE ID_Nota = ?");
    $stmt->bind_param('i', $id);
    
    // Ejecutar DELETE
    if ($stmt->execute()) {
        // Verificar si se eliminó algún registro
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Eliminado']);
        } else {
            // No se encontró la nota para eliminar
            json_error('Nota no encontrada', 404);
        }
    } else {
        json_error('Error al eliminar: ' . $mysqli->error, 500);
    }
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 10: ENRUTADOR DE ACCIONES - SWITCH PRINCIPAL
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Switch que determina qué función ejecutar según la acción solicitada
 * 
 * Acciones soportadas:
 *   - listar: GET - Listar todas las notas del psicólogo
 *   - obtener: GET - Obtener una nota específica (requiere parámetro 'id')
 *   - buscar: GET - Buscar notas por texto (requiere parámetro 'q')
 *   - crear: POST - Crear nueva nota clínica
 *   - actualizar: POST/PUT - Actualizar nota existente (requiere parámetro 'id')
 *   - eliminar: POST/DELETE - Eliminar una nota (requiere parámetro 'id')
 * 
 * Validaciones:
 *   - Se verifica el método HTTP permitido para cada acción
 *   - Se validan los parámetros requeridos
 *   - Se retorna error 400 si la acción no es válida
 *   - Se retorna error 405 si el método HTTP no es permitido
 */
switch ($action) {
    case 'listar':
        // Listar todas las notas del psicólogo (opcionalmente filtradas por paciente)
        listarNotas($mysqli, $ID_Psicologo);
        break;
        
    case 'obtener':
        // Obtener una nota específica por ID
        if (isset($_GET['id'])) {
            obtenerNota($mysqli, $_GET['id'], $ID_Psicologo);
        } else {
            json_error('ID requerido', 400);
        }
        break;
        
    case 'buscar':
        // Buscar notas por texto en múltiples campos
        if (isset($_GET['q'])) {
            buscarNotas($mysqli, $_GET['q'], $ID_Psicologo);
        } else {
            // Si no hay término de búsqueda, listar todas las notas
            listarNotas($mysqli, $ID_Psicologo);
        }
        break;
        
    case 'crear':
        // Crear nueva nota (solo método POST permitido)
        if ($method === 'POST') {
            crearNota($mysqli);
        } else {
            json_error('Método no permitido', 405);
        }
        break;
        
    case 'actualizar':
        // Actualizar nota existente (métodos POST o PUT permitidos)
        if ($method === 'POST' || $method === 'PUT') {
            if (isset($_GET['id'])) {
                actualizarNota($mysqli, $_GET['id']);
            } else {
                json_error('ID requerido', 400);
            }
        } else {
            json_error('Método no permitido', 405);
        }
        break;
        
    case 'eliminar':
        // Eliminar nota (métodos POST o DELETE permitidos)
        if ($method === 'POST' || $method === 'DELETE') {
            if (isset($_GET['id'])) {
                eliminarNota($mysqli, $_GET['id']);
            } else {
                json_error('ID requerido', 400);
            }
        } else {
            json_error('Método no permitido', 405);
        }
        break;
        
    default:
        // Acción no reconocida
        json_error('Acción no válida', 400);
        break;
}

// Cerrar conexión a la base de datos
$mysqli->close();

?>