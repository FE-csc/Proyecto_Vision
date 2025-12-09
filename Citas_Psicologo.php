<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: Citas_Psicologo.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Endpoint RESTful para gestión de citas del psicólogo
 *   - Implementa métodos GET (obtener citas) y POST (actualizar estado)
 *   - Realiza validación de sesión y autorización
 *   - Verifica que la cita pertenezca al psicólogo autenticado
 *   - Utiliza prepared statements para seguridad SQL
 *   - Retorna respuestas JSON con mensajes de estado
 * 
 * Funcionalidades:
 *   - GET ?action=get_citas: Obtener todas las citas del psicólogo autenticado
 *   - POST: Actualizar estado de una cita específica
 * 
 * Parámetros GET:
 *   - action=get_citas (requerido para GET)
 * 
 * Parámetros POST (JSON body):
 *   - id_cita: ID de la cita a actualizar
 *   - estado: Nuevo estado (Pendiente, Confirmada, Completada, Cancelada)
 * 
 * Respuestas JSON:
 *   GET Exitosa:
 *   {
 *     success: true,
 *     data: [...citas],
 *     count: 5,
 *     id_psicologo: 1
 *   }
 *   
 *   POST Exitosa:
 *   {
 *     success: true,
 *     message: "Estado de cita actualizado correctamente",
 *     id_cita: 1,
 *     nuevo_estado: "Confirmada"
 *   }
 *   
 *   Error:
 *   {
 *     success: false,
 *     message: "Descripción del error"
 *   }
 * 
 * Códigos HTTP:
 *   - 200: OK - Solicitud exitosa
 *   - 400: Bad Request - Datos incompletos o inválidos
 *   - 401: Unauthorized - No autenticado
 *   - 403: Forbidden - Sin permiso (no es psicólogo o cita no pertenece)
 *   - 405: Method Not Allowed - Método HTTP no soportado
 *   - 500: Internal Server Error - Error del servidor
 * 
 * Dependencias:
 *   - db.php (conexión MySQL)
 *   - Tabla: citas, pacientes, psicologos, users
 *   - Sesión: $_SESSION['user_id']
 * ════════════════════════════════════════════════════════════════════════════════
 */

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: INICIALIZACIÓN Y AUTENTICACIÓN
// ════════════════════════════════════════════════════════════════════════════════

/**
 * HEADER: Indicar que la respuesta es JSON
 * Especifica el tipo de contenido y codificación
 */
header('Content-Type: application/json');

/**
 * INICIAR SESIÓN PHP
 * Permite acceder a $_SESSION para validar usuario autenticado
 */
session_start();

/**
 * INCLUIR CONEXIÓN A BASE DE DATOS
 * Proporciona variable global $mysqli con conexión MySQLi
 */
require_once 'db.php';

/**
 * ASIGNAR CONEXIÓN A VARIABLE LOCAL
 * Se usa $conn en todo el archivo (convención del código existente)
 */
$conn = $mysqli;

/**
 * VALIDAR CONEXIÓN A LA BASE DE DATOS
 * 
 * Si la conexión falla:
 *   - Establecer código HTTP 500
 *   - Retornar error JSON
 *   - Terminar ejecución
 */
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

/**
 * VALIDAR SESIÓN DE USUARIO
 * 
 * $_SESSION['user_id'] debe estar establecida (login exitoso)
 * 
 * Si no existe:
 *   - Establecer código HTTP 401 (Unauthorized)
 *   - Retornar error JSON
 *   - Terminar ejecución
 */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

/**
 * OBTENER ID DEL USUARIO AUTENTICADO
 * Se usa para obtener el ID del psicólogo asociado
 */
$user_id = $_SESSION['user_id'];
error_log("DEBUG: user_id = " . $user_id);


// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: GET - OBTENER CITAS DEL PSICÓLOGO
// ════════════════════════════════════════════════════════════════════════════════

/**
 * MANEJO DE SOLICITUD GET CON action=get_citas
 * 
 * Flujo:
 *   1. Obtener ID_Psicologo desde tabla psicologos usando user_id
 *   2. Validar que el usuario es un psicólogo registrado
 *   3. Consultar todas las citas de ese psicólogo
 *   4. Hacer JOIN con tablas de paciente y usuario para información completa
 *   5. Retornar array JSON con citas
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_citas') {
    try {
        /**
         * PASO 1: OBTENER ID DEL PSICÓLOGO
         * 
         * Consulta: Buscar fila en tabla psicologos donde ID_Usuario coincida
         * Usamos LIMIT 1 porque debe haber solo un registro por usuario
         */
        $sql_psicologo = "SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario = ? LIMIT 1";
        
        error_log("DEBUG: Buscando psicólogo con ID_Usuario = " . $user_id);
        
        /**
         * PREPARED STATEMENT: Preparar consulta con parámetro
         * Protege contra inyección SQL
         */
        $stmt_psicologo = $conn->prepare($sql_psicologo);
        if (!$stmt_psicologo) {
            error_log("ERROR: Prepare failed: " . $conn->error);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        /**
         * VINCULACIÓN DE PARÁMETRO
         * 'i' = integer (tipo de dato de user_id)
         */
        if (!$stmt_psicologo->bind_param('i', $user_id)) {
            error_log("ERROR: Bind param failed: " . $stmt_psicologo->error);
            throw new Exception("Bind param failed: " . $stmt_psicologo->error);
        }
        
        /**
         * EJECUTAR CONSULTA
         */
        if (!$stmt_psicologo->execute()) {
            error_log("ERROR: Execute failed: " . $stmt_psicologo->error);
            throw new Exception("Execute failed: " . $stmt_psicologo->error);
        }
        
        /**
         * OBTENER RESULTADO
         */
        $result_psicologo = $stmt_psicologo->get_result();
        
        error_log("DEBUG: Resultado num_rows = " . $result_psicologo->num_rows);
        
        /**
         * VALIDAR QUE SE ENCONTRÓ UN PSICÓLOGO
         * 
         * Si no existe registro:
         *   - El usuario no es un psicólogo registrado
         *   - Retornar error 403 (Forbidden)
         */
        if ($result_psicologo->num_rows === 0) {
            error_log("ERROR: No se encontró psicólogo para user_id = " . $user_id);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No eres un psicólogo registrado']);
            $stmt_psicologo->close();
            exit;
        }
        
        /**
         * EXTRAER ID DEL PSICÓLOGO
         * Se usará para obtener las citas
         */
        $psicologo = $result_psicologo->fetch_assoc();
        $id_psicologo = $psicologo['ID_Psicologo'];
        $stmt_psicologo->close();
        
        error_log("DEBUG: Psicólogo encontrado: ID_Psicologo = " . $id_psicologo);
        error_log("DEBUG: id_psicologo = " . $id_psicologo);
        
        /**
         * PASO 2: OBTENER TODAS LAS CITAS DEL PSICÓLOGO
         * 
         * Consulta con JOINs múltiples:
         *   - JOIN pacientes: Datos del paciente (nombre, apellido, teléfono)
         *   - JOIN users: Correo del paciente
         *   - WHERE ID_Psicologo = ?: Filtrar por psicólogo autenticado
         *   - ORDER BY Fecha_Cita ASC: Ordenar por fecha ascendente
         * 
         * Campos retornados:
         *   - Cita: ID_Cita, ID_Paciente, ID_Psicologo, Fecha_Cita, Motivo, Estado, Duracion
         *   - Paciente: Nombre_Paciente, Apellido_Paciente, Telefono_Paciente, Correo_Paciente
         */
        $sql = "SELECT 
                    c.ID_Cita,
                    c.ID_Paciente,
                    c.ID_Psicologo,
                    c.Fecha_Cita,
                    c.Motivo,
                    c.Estado,
                    c.Duracion,
                    p.Nombre_Paciente,
                    p.Apellido_Paciente,
                    p.Telefono_Paciente,
                    u.email as Correo_Paciente
                FROM citas c
                JOIN pacientes p ON c.ID_Paciente = p.ID_Paciente
                JOIN users u ON p.ID_Usuario = u.id
                WHERE c.ID_Psicologo = ? 
                ORDER BY c.Fecha_Cita ASC";
        
        error_log("DEBUG: SQL Query = " . $sql);
        
        /**
         * PREPARAR CONSULTA PRINCIPAL
         */
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("ERROR: Prepare failed: " . $conn->error);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        /**
         * VINCULAR PARÁMETRO
         * id_psicologo obtenido en paso 1
         */
        if (!$stmt->bind_param('i', $id_psicologo)) {
            error_log("ERROR: Bind param failed: " . $stmt->error);
            throw new Exception("Bind param failed: " . $stmt->error);
        }
        
        /**
         * EJECUTAR CONSULTA
         */
        if (!$stmt->execute()) {
            error_log("ERROR: Execute failed: " . $stmt->error);
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        /**
         * OBTENER RESULTADO
         */
        $result = $stmt->get_result();
        
        /**
         * ITERAR RESULTADOS
         * Convertir cada fila a array asociativo y agregar al array de citas
         */
        $citas = [];
        while ($row = $result->fetch_assoc()) {
            $citas[] = $row;
        }
        
        /**
         * CERRAR STATEMENT
         * Liberar recursos
         */
        $stmt->close();
        
        error_log("DEBUG: Citas encontradas = " . count($citas));
        
        /**
         * RETORNAR RESPUESTA EXITOSA
         * 
         * Estructura:
         *   - success: true (indica operación exitosa)
         *   - data: array de citas
         *   - count: número de citas encontradas
         *   - id_psicologo: ID del psicólogo (para debugging)
         */
        echo json_encode([
            'success' => true,
            'data' => $citas,
            'count' => count($citas),
            'id_psicologo' => $id_psicologo
        ]);
        
    } catch (Exception $e) {
        /**
         * MANEJO DE EXCEPCIONES
         * Si ocurre cualquier error, registrarlo y retornar en JSON
         */
        error_log("ERROR: Exception caught: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al cargar las citas: ' . $e->getMessage()
        ]);
    }
    exit;
}


// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: POST - ACTUALIZAR ESTADO DE CITA
// ════════════════════════════════════════════════════════════════════════════════

/**
 * MANEJO DE SOLICITUD POST
 * 
 * Flujo:
 *   1. Decodificar JSON del body
 *   2. Validar que id_cita y estado están presentes
 *   3. Validar que el estado es uno de los permitidos
 *   4. Obtener ID_Psicologo del usuario autenticado
 *   5. Verificar que la cita pertenece a este psicólogo
 *   6. Actualizar estado de la cita
 *   7. Retornar resultado
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        /**
         * DECODIFICAR BODY JSON
         * 
         * file_get_contents('php://input'): Lee el cuerpo de la solicitud
         * true: Decodificar como array asociativo (no objeto)
         */
        $input = json_decode(file_get_contents('php://input'), true);
        
        /**
         * VALIDAR QUE DATOS ESTÁN PRESENTES
         * 
         * Parámetros requeridos:
         *   - id_cita: ID de la cita a actualizar
         *   - estado: Nuevo estado
         * 
         * Si falta alguno:
         *   - Retornar error 400 (Bad Request)
         */
        if (!$input || !isset($input['id_cita']) || !isset($input['estado'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        
        /**
         * CONVERSIÓN Y LIMPIEZA DE DATOS
         * 
         * id_cita: Convertir a entero
         * estado: Convertir a string y remover espacios
         */
        $id_cita = intval($input['id_cita']);
        $estado = trim($input['estado']);
        
        /**
         * VALIDAR ESTADO
         * 
         * Solo se permiten estos estados:
         *   - Pendiente
         *   - Confirmada
         *   - Completada
         *   - Cancelada
         * 
         * Si estado no está en la lista:
         *   - Retornar error 400 (Bad Request)
         */
        $estados_validos = ['Pendiente', 'Confirmada', 'Completada', 'Cancelada'];
        if (!in_array($estado, $estados_validos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estado inválido']);
            exit;
        }
        
        /**
         * PASO 1: OBTENER ID DEL PSICÓLOGO AUTENTICADO
         * 
         * Consulta: Buscar ID_Psicologo donde ID_Usuario = user_id
         */
        $sql_psicologo = "SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario = ?";
        $stmt_psicologo = $conn->prepare($sql_psicologo);
        $stmt_psicologo->bind_param('i', $user_id);
        $stmt_psicologo->execute();
        $result_psicologo = $stmt_psicologo->get_result();
        
        /**
         * VALIDAR QUE EL USUARIO ES UN PSICÓLOGO
         */
        if ($result_psicologo->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No eres un psicólogo registrado']);
            $stmt_psicologo->close();
            exit;
        }
        
        /**
         * EXTRAER ID DEL PSICÓLOGO
         */
        $psicologo = $result_psicologo->fetch_assoc();
        $id_psicologo = $psicologo['ID_Psicologo'];
        $stmt_psicologo->close();
        
        /**
         * PASO 2: VERIFICAR QUE LA CITA PERTENECE AL PSICÓLOGO
         * 
         * Consulta: Buscar cita donde ID_Cita = id_cita AND ID_Psicologo = id_psicologo
         * 
         * Esto es un control de autorización:
         *   - Evita que un psicólogo actualice citas de otro
         *   - Si la cita no existe o pertenece a otro psicólogo: error 403
         */
        $sql_check = "SELECT c.ID_Cita FROM citas c 
                      WHERE c.ID_Cita = ? AND c.ID_Psicologo = ?";
        
        $stmt_check = $conn->prepare($sql_check);
        if (!$stmt_check) {
            throw new Exception($conn->error);
        }
        
        /**
         * VINCULAR DOS PARÁMETROS (ambos enteros)
         */
        $stmt_check->bind_param('ii', $id_cita, $id_psicologo);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        /**
         * VALIDAR QUE LA CITA EXISTE Y PERTENECE AL PSICÓLOGO
         */
        if ($result_check->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para actualizar esta cita']);
            $stmt_check->close();
            exit;
        }
        
        $stmt_check->close();
        
        /**
         * PASO 3: ACTUALIZAR EL ESTADO DE LA CITA
         * 
         * UPDATE:
         *   - Estado = nuevo_estado
         *   - updated_at = CURRENT_TIMESTAMP (registrar cuándo fue actualizado)
         * WHERE:
         *   - ID_Cita = id_cita
         */
        $sql_update = "UPDATE citas SET Estado = ?, updated_at = CURRENT_TIMESTAMP WHERE ID_Cita = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        if (!$stmt_update) {
            throw new Exception($conn->error);
        }
        
        /**
         * VINCULAR PARÁMETROS
         * 's' = string (estado)
         * 'i' = integer (id_cita)
         */
        $stmt_update->bind_param('si', $estado, $id_cita);
        $stmt_update->execute();
        
        /**
         * VALIDAR QUE SE REALIZÓ LA ACTUALIZACIÓN
         * 
         * affected_rows:
         *   - 0: No se cambió nada (estado ya era igual)
         *   - 1: Se actualizó correctamente
         *   - -1: Error en la consulta
         */
        if ($stmt_update->affected_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'No se realizaron cambios'
            ]);
        } else {
            /**
             * ÉXITO: Retornar respuesta con datos de la actualización
             */
            echo json_encode([
                'success' => true,
                'message' => 'Estado de cita actualizado correctamente',
                'id_cita' => $id_cita,
                'nuevo_estado' => $estado
            ]);
        }
        
        /**
         * CERRAR STATEMENT
         */
        $stmt_update->close();
        
    } catch (Exception $e) {
        /**
         * MANEJO DE EXCEPCIONES
         */
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar: ' . $e->getMessage()
        ]);
    }
    exit;
}

/**
 * MÉTODO NO PERMITIDO
 * 
 * Si la solicitud no es GET (con action=get_citas) ni POST
 * Retornar error 405 (Method Not Allowed)
 */
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
?>
