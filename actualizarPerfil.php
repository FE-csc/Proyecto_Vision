<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: actualizarPerfil.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Gestiona la obtención y actualización/creación del perfil del paciente
 *   - Proporciona endpoint GET para cargar datos del perfil
 *   - Proporciona endpoint POST para crear o actualizar datos del paciente
 *   - Implementa validación de datos en el servidor
 *   - Valida sesión y autorización antes de cualquier operación
 * 
 * Endpoints:
 *   - GET /actualizarPerfil.php?action=get_profile
 *     Retorna los datos del perfil en formato JSON
 * 
 *   - POST /actualizarPerfil.php
 *     Recibe JSON con datos de paciente y crea o actualiza el perfil
 * 
 * Respuestas JSON:
 *   {
 *     "success": boolean,
 *     "message": string,
 *     "data": object|null
 *   }
 * ════════════════════════════════════════════════════════════════════════════════
 */

// Iniciar sesión para acceder a las variables de sesión del usuario
session_start();

// Incluir archivo de conexión a la base de datos
require_once 'db.php';

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: ENDPOINT GET - OBTENER DATOS DEL PERFIL
// ════════════════════════════════════════════════════════════════════════════════
/**
 * Procesa solicitudes GET con parámetro action=get_profile
 * 
 * Validaciones:
 *   - Verifica que la sesión del usuario esté activa
 *   - Obtiene los datos del paciente asociado al usuario
 * 
 * Respuesta:
 *   - 200: JSON con datos del paciente
 *   - 401: No autorizado (sin sesión activa)
 *   - 500: Error en la consulta a la base de datos
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_profile') {
    // Establecer header de respuesta en formato JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Validar que el usuario tenga una sesión activa
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado. Por favor inicia sesión.']);
        exit;
    }
    
    // Obtener el ID del usuario de la sesión
    $user_id = $_SESSION['user_id'];
    
    /**
     * Consulta SQL para obtener datos del paciente
     * 
     * Usa LEFT JOIN para incluir usuarios que no tienen perfil de paciente aún
     * Campos obtenidos:
     *   - ID_Paciente: ID único del registro en tabla pacientes (puede ser NULL)
     *   - ID_Usuario: ID del usuario registrado
     *   - Nombre_Paciente, Apellido_Paciente, Edad, Telefono_Paciente
     *   - Fecha_Registro: Cuándo se creó o actualizó el perfil
     *   - email: Email del usuario (tabla users)
     */
    $stmt = $mysqli->prepare("
        SELECT 
            p.ID_Paciente, 
            u.id AS ID_Usuario, 
            p.Nombre_Paciente, 
            p.Apellido_Paciente, 
            p.Edad, 
            p.Telefono_Paciente, 
            p.Fecha_Registro,
            u.email
        FROM users u
        LEFT JOIN pacientes p ON p.ID_Usuario = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    
    // Validar que la consulta se haya preparado correctamente
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error en la consulta']);
        exit;
    }
    
    // Vincular el parámetro user_id a la consulta (tipo 'i' = integer)
    $stmt->bind_param('i', $user_id);
    
    // Ejecutar la consulta preparada
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta']);
        $stmt->close();
        exit;
    }
    
    // Obtener el resultado de la consulta
    $result = $stmt->get_result();
    
    // Validar que se encontró un registro
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No se encontró paciente para este usuario']);
        $stmt->close();
        exit;
    }
    
    // Obtener los datos del paciente como un array asociativo
    $paciente = $result->fetch_assoc();
    $stmt->close();
    
    // Retornar datos en formato JSON
    echo json_encode(['success' => true, 'message' => 'Datos obtenidos correctamente', 'data' => $paciente]);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: ENDPOINT POST - CREAR O ACTUALIZAR PERFIL
// ════════════════════════════════════════════════════════════════════════════════
/**
 * Procesa solicitudes POST para crear o actualizar el perfil del paciente
 * 
 * Flujo:
 *   1. Valida que sea una solicitud AJAX con JSON
 *   2. Verifica que el usuario tenga sesión activa
 *   3. Decodifica el JSON recibido
 *   4. Valida todos los campos (nombre, apellido, edad, teléfono)
 *   5. Verifica que el usuario no intente modificar datos de otro usuario
 *   6. Si no existe perfil: ejecuta INSERT
 *   7. Si existe perfil: ejecuta UPDATE
 * 
 * Validaciones:
 *   - Nombre y apellido: obligatorios y no vacíos
 *   - Edad: entre 18 y 120 años
 *   - Teléfono: mínimo 6 caracteres
 *   - Autorización: el user_id debe coincidir con la sesión
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si la solicitud viene de AJAX o tiene contenido JSON
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax || strpos($_SERVER['CONTENT_TYPE'] ?? '' , 'application/json') !== false) {
        // Establecer header de respuesta en formato JSON
        header('Content-Type: application/json; charset=utf-8');

        // Validar que el usuario tenga una sesión activa
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado. Por favor inicia sesión.']);
            exit;
        }

        // Leer el cuerpo de la solicitud (entrada JSON) y decodificarlo
        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);

        // Validar que el JSON sea válido
        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
            exit;
        }

        // Extraer y sanitizar los datos del JSON recibido
        $id_usuario = intval($input['id_usuario'] ?? 0);
        $nombre = trim($input['nombre'] ?? '');
        $apellido = trim($input['apellido'] ?? '');
        $edad = intval($input['edad'] ?? 0);
        $telefono = trim($input['telefono'] ?? '');

        // VALIDACIÓN CRÍTICA: El ID del usuario debe coincidir con la sesión actual
        // Esto previene que un usuario modifique datos de otro usuario
        if ($id_usuario !== $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            exit;
        }

        // VALIDACIÓN: Nombre y apellido son campos obligatorios
        if (empty($nombre) || empty($apellido)) {
            echo json_encode(['success' => false, 'message' => 'Nombre y apellido obligatorios.']);
            exit;
        }

        // VALIDACIÓN: Edad debe estar en rango válido (18-120 años)
        if ($edad < 18 || $edad > 120) {
            echo json_encode(['success' => false, 'message' => 'Edad inválida. Debe ser mayor de 18 años.']);
            exit;
        }

        // VALIDACIÓN: Teléfono debe tener mínimo 6 caracteres
        if (empty($telefono) || strlen($telefono) < 6) {
            echo json_encode(['success' => false, 'message' => 'Teléfono inválido (mínimo 6 caracteres).']);
            exit;
        }

        /**
         * VERIFICAR SI EL PACIENTE YA EXISTE
         * 
         * Consulta la tabla pacientes para saber si el usuario ya tiene un perfil
         * Esto determina si debemos hacer INSERT (nuevo) o UPDATE (existente)
         */
        $check = $mysqli->prepare("SELECT ID_Paciente FROM pacientes WHERE ID_Usuario = ? LIMIT 1");
        $check->bind_param('i', $id_usuario);
        $check->execute();
        $res = $check->get_result();
        
        if ($res->num_rows === 0) {
            // ═══════════════════════════════════════════════════════════════════════
            // CASO 1: CREAR NUEVO PERFIL DE PACIENTE (INSERT)
            // ═══════════════════════════════════════════════════════════════════════
            
            /**
             * INSERT: Crear nuevo registro en tabla pacientes
             * 
             * Campos insertados:
             *   - ID_Usuario: Vinculación con el usuario
             *   - Nombre_Paciente, Apellido_Paciente: Datos personales
             *   - Edad: Edad del paciente
             *   - Telefono_Paciente: Teléfono de contacto
             *   - Fecha_Registro: Timestamp actual (NOW())
             */
            $insert = $mysqli->prepare("INSERT INTO pacientes (ID_Usuario, Nombre_Paciente, Apellido_Paciente, Edad, Telefono_Paciente, Fecha_Registro) VALUES (?, ?, ?, ?, ?, NOW())");
            
            if (!$insert) {
                // Error al preparar la consulta
                error_log("Error preparando INSERT: " . $mysqli->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error preparando INSERT.']);
                exit;
            }
            
            // Vincular parámetros: 'issis' = integer, string, string, integer, string
            $insert->bind_param('issis', $id_usuario, $nombre, $apellido, $edad, $telefono);
            
            // Ejecutar INSERT
            if ($insert->execute()) {
                echo json_encode(['success' => true, 'message' => 'Perfil creado correctamente.']);
            } else {
                // Error durante la ejecución
                error_log("Error ejecutando INSERT: " . $insert->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al crear perfil.']);
            }
            $insert->close();
        } else {
            // ═══════════════════════════════════════════════════════════════════════
            // CASO 2: ACTUALIZAR PERFIL EXISTENTE (UPDATE)
            // ═══════════════════════════════════════════════════════════════════════
            
            // Obtener el ID del paciente existente
            $paciente = $res->fetch_assoc();
            $id_paciente = $paciente['ID_Paciente'];
            
            /**
             * UPDATE: Actualizar registro existente en tabla pacientes
             * 
             * Campos actualizados:
             *   - Nombre_Paciente, Apellido_Paciente: Datos personales
             *   - Edad: Edad del paciente
             *   - Telefono_Paciente: Teléfono de contacto
             * 
             * Condiciones WHERE:
             *   - ID_Paciente debe coincidir
             *   - ID_Usuario debe coincidir (doble validación de seguridad)
             */
            $update = $mysqli->prepare("UPDATE pacientes SET Nombre_Paciente = ?, Apellido_Paciente = ?, Edad = ?, Telefono_Paciente = ? WHERE ID_Paciente = ? AND ID_Usuario = ?");
            
            if (!$update) {
                // Error al preparar la consulta
                error_log("Error preparando UPDATE: " . $mysqli->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
                exit;
            }
            
            // Vincular parámetros: 'ssisii' = string, string, integer, string, integer, integer
            $update->bind_param('ssisii', $nombre, $apellido, $edad, $telefono, $id_paciente, $id_usuario);
            
            // Ejecutar UPDATE
            if (!$update->execute()) {
                // Error durante la ejecución
                error_log("Error ejecutando UPDATE: " . $update->error);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos']);
                $update->close();
                exit;
            }
            
            /**
             * Verificar si se realizaron cambios
             * 
             * affected_rows > 0: Significa que se actualizó algún registro
             * affected_rows = 0: Significa que no hubo cambios (datos idénticos)
             */
            if ($update->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se realizaron cambios.']);
            }
            
            $update->close();
        }
        $check->close();
        exit;
    }
}
// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: RENDERIZACIÓN DE PÁGINA - VALIDACIÓN DE SESIÓN Y DATOS
// ════════════════════════════════════════════════════════════════════════════════
/**
 * Validar que el usuario tenga una sesión activa
 * Si no hay sesión, redirigir a la página de login
 * 
 * Nota: Esta sección se ejecuta solo si la solicitud no es GET/POST con AJAX
 *       Es decir, solo cuando se carga la página normalmente en el navegador
 */
if (!isset($_SESSION['user_id'])) {
    // Redirigir a login si no hay sesión
    header('Location: login.html');
    exit;
}

// Obtener datos del paciente para mostrar en la página
$resultado = obtenerPacientePorUsuario($_SESSION['user_id']);
$paciente = $resultado['success'] ? $resultado['data'] : null;

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 4: FUNCIÓN AUXILIAR - OBTENER DATOS DEL PACIENTE
// ════════════════════════════════════════════════════════════════════════════════
/**
 * Obtiene los datos del perfil del paciente desde la base de datos
 * 
 * @param int $user_id - ID del usuario del cual obtener el perfil
 * 
 * @return array Retorna un array asociativo con:
 *   [
 *     'success' => boolean,      // Indicador de éxito de la operación
 *     'message' => string,       // Mensaje descriptivo
 *     'data' => array|null       // Datos del paciente o null si hay error
 *   ]
 * 
 * Estructura de datos retornada:
 *   {
 *     'ID_Paciente': int|null,
 *     'ID_Usuario': int,
 *     'Nombre_Paciente': string|null,
 *     'Apellido_Paciente': string|null,
 *     'Edad': int|null,
 *     'Telefono_Paciente': string|null,
 *     'Fecha_Registro': string|null,
 *     'email': string
 *   }
 * 
 * Errores posibles:
 *   - Error al preparar la consulta SQL
 *   - Error al ejecutar la consulta SQL
 *   - Usuario no encontrado en la base de datos
 */
function obtenerPacientePorUsuario($user_id) {
    global $mysqli;
    
    /**
     * Consulta SQL para obtener datos del paciente
     * 
     * Usa LEFT JOIN para incluir usuarios sin perfil de paciente aún
     * Si el paciente no existe, los campos de paciente serán NULL
     * 
     * Tabla users: Almacena datos de autenticación y email
     * Tabla pacientes: Almacena perfil del paciente (vinculado a users)
     */
    $stmt = $mysqli->prepare("
        SELECT 
            p.ID_Paciente, 
            u.id AS ID_Usuario, 
            p.Nombre_Paciente, 
            p.Apellido_Paciente, 
            p.Edad, 
            p.Telefono_Paciente, 
            p.Fecha_Registro,
            u.email
        FROM users u
        LEFT JOIN pacientes p ON p.ID_Usuario = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    
    // Validar que la consulta se haya preparado correctamente
    if (!$stmt) {
        return ['success' => false, 'message' => 'Error en la consulta', 'data' => null];
    }
    
    // Vincular el parámetro user_id (tipo 'i' = integer)
    $stmt->bind_param('i', $user_id);
    
    // Ejecutar la consulta preparada
    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Error al ejecutar consulta', 'data' => null];
    }
    
    // Obtener el resultado de la consulta
    $result = $stmt->get_result();
    
    // Validar que se encontró un registro
    if ($result->num_rows === 0) {
        // No se encontró el usuario en la base de datos
        $stmt->close();
        return ['success' => false, 'message' => 'No se encontró paciente para este usuario', 'data' => null];
    }
    
    // Obtener los datos del paciente como un array asociativo
    $paciente = $result->fetch_assoc();
    $stmt->close();
    
    // Retornar respuesta exitosa con los datos del paciente
    return ['success' => true, 'message' => 'Paciente encontrado', 'data' => $paciente];
}

