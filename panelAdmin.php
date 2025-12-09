<?php 
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * FILE: panelAdmin.php
 * ════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: API backend para administración de usuarios (CRUD + role management)
 * Maneja listado, cambio de roles, eliminación y logout
 * FUNCIONALIDAD: JSON API endpoints via action parameter (GET/POST)
 * DEPENDENCIAS: MySQLi prepared statements, db.php conexión, $_SESSION auth
 * AUTENTICACIÓN: Solo administradores (ID_Role = 3) pueden acceder
 * MÉTODOS: GET para list, POST para updateRol/deleteUser/logout
 * RESPUESTAS: JSON con items array o message/error strings
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * OPERACIONES DISPONIBLES:
 * - action=list: Obtener usuarios con filtros (nombre, correo, rol)
 * - action=updateRol: Cambiar rol de usuario + crear registro psicólogo si es necesario
 * - action=deleteUser: Eliminar usuario permanentemente
 * - action=logout: Destruir sesión del administrador
 * 
 * VALIDACIÓN DE SEGURIDAD:
 * 1. session_start() lee sesión del usuario actual
 * 2. Verifica existencia de user_id en $_SESSION
 * 3. Consulta base de datos para verificar ID_Role = 3 (Administrador)
 * 4. Si no es admin, retorna 403 Forbidden + JSON error
 * 5. Todas las consultas usan prepared statements (previene SQL injection)
 * 6. Parámetros están vinculados con bind_param() tipados
 */

session_start();

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y VALIDACIÓN DE SEGURIDAD
// ──────────────────────────────────────────────────────────────────────────────
/**
 * MIDDLEWARE DE AUTENTICACIÓN Y AUTORIZACIÓN
 * 
 * FLUJO:
 * 1. Inicia sesión para acceder a $_SESSION['user_id']
 * 2. Incluye conexión centralizada a base de datos (db.php)
 * 3. Extrae user_id de sesión actual
 * 4. Valida que usuario esté autenticado (user_id no nulo)
 * 5. Consulta base de datos para obtener ID_Role del usuario
 * 6. Verifica que ID_Role = 3 (Administrador)
 * 7. Si no es admin, retorna 403 Forbidden + JSON error
 * 
 * PROPÓSITO: Proteger endpoints para que solo administradores puedan
 * listar usuarios, cambiar roles o eliminar cuentas
 * 
 * ERRORES:
 * - 401 Unauthorized: Usuario no autenticado (user_id nulo)
 * - 403 Forbidden: Usuario autenticado pero no es administrador (ID_Role ≠ 3)
 */

// Conexión centralizada a la base de datos (MySQLi object)
require_once "db.php";

// MIDDLEWARE: Validar si el usuario es administrador
// Extraer user_id de la sesión actual (null si no está autenticado)
$userID = $_SESSION['user_id']?? null;
if(!$userID) {
    http_response_code(401);
    exit(json_encode(['error' => 'No autenticado']));
}

// Consultar base de datos para obtener role del usuario actual (admin check)
$stmt = $mysqli->prepare("SELECT ID_Role FROM users WHERE id=?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

// Verificar que el usuario sea administrador (ID_Role = 3)
// Si no lo es, denegar acceso con 403 Forbidden
if((int)$role !== 3) {
    http_response_code(403);
    exit(json_encode(['error' => 'Acceso denegado']));
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: ACTION=LIST - OBTENER USUARIOS CON FILTROS
// ──────────────────────────────────────────────────────────────────────────────
/**
 * action=list - GET request para obtener lista de usuarios
 * 
 * FLUJO:
 * 1. Extrae parámetros de filtro de $_GET (nombre, correo, rol)
 * 2. Construye array de condiciones WHERE dinámicamente
 * 3. Construye types string para bind_param (tipado seguro)
 * 4. Si hay filtro de nombre: búsqueda LIKE en nombre/apellido (paciente + psicólogo)
 * 5. Si hay filtro de rol: filtrar por ID_Role exacto
 * 6. Ejecuta query con JOINs (users, pacientes, psicologos, Roles)
 * 7. Retorna JSON array con todos los usuarios coincidentes
 * 
 * PARÁMETROS ESPERADOS:
 * ?action=list&nombre=Juan&correo=&rol=1
 * - nombre: string para búsqueda LIKE (opcional)
 * - correo: string para búsqueda email (opcional)
 * - rol: número (1=Paciente, 2=Doctor, 3=Admin) (opcional)
 * 
 * RESPUESTA JSON:
 * {
 *   "items": [
 *     {
 *       "ID_Usuario": 1,
 *       "email": "user@example.com",
 *       "ID_Role": 1,
 *       "Role": "Paciente",
 *       "ID_Paciente": 101,
 *       "Nombre_Paciente": "Juan",
 *       "Apellido_Paciente": "Pérez",
 *       "Telefono_Paciente": "123456789",
 *       "ID_Psicologo": null,
 *       "Nombre_Psicologo": null,
 *       ...
 *     }
 *   ]
 * }
 * 
 * SEGURIDAD: Prepared statement con bind_param previene SQL injection
 * Los LIKE wildcards se escapan en el SQL, no en PHP
 */

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if($action === 'list') {
    // Extraer parámetros de filtro (default a string vacío si no existen)
    $nombre = $_GET['nombre'] ?? '';
    $correo = $_GET['correo'] ?? '';
    $rol = $_GET['rol'] ?? '';
    
    // Arrays para construir dinámicamente la cláusula WHERE
    $where = [];        // Array de condiciones WHERE
    $params = [];       // Array de valores para bind_param
    $types = '';        // String de tipos para bind_param (i=int, s=string)

    // ─ Filtro por nombre: búsqueda LIKE en nombre y apellido
    // Busca tanto en pacientes como en psicólogos
    if($nombre !== '') {
        // Agregar 4 condiciones LIKE (Nombre + Apellido x 2 tablas)
        $where[] = "(p.Nombre_Paciente LIKE ? OR p.Apellido_Paciente LIKE ? OR ps.Nombre_Psicologo LIKE ? OR ps.Apellido_Psicologo LIKE ?)";
        // Agregar wildcard LIKE (%) al principio y final
        $like = "%$nombre%";
        // Agregar 4 parámetros de búsqueda (mismo valor para 4 campos)
        $params = array_merge($params, [$like, $like, $like, $like]);
        // Agregar 4 tipos 's' (string) al tipo string
        $types .= 'ssss';
    }
    
    // ─ Filtro por rol: ID_Role exacto
    if($rol !== '') {
        // Agregar condición WHERE u.ID_Role = ?
        $where[] = "u.ID_Role=?";
        // Castear a int para evitar tipos incorrectos
        $params[] = (int)$rol;
        // Agregar tipo 'i' (integer)
        $types .= 'i';
    }

    // ─ Construir cláusula WHERE (WHERE x AND y si hay múltiples, vacío si no hay)
    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    
    // ─ SQL Query con LEFT JOINs (para obtener datos de paciente O psicólogo)
    // LEFT JOIN asegura que se muestren usuarios aunque no tengan paciente/psicólogo
    $sql = "SELECT u.id AS ID_Usuario, u.email, u.ID_Role, r.Role, p.ID_Paciente,
    p.Nombre_Paciente, p.Apellido_Paciente, p.Telefono_Paciente, ps.ID_Psicologo,
    ps.Nombre_Psicologo, ps.Apellido_Psicologo, ps.Psicologo_Telefono
    FROM users u JOIN Roles r ON u.ID_Role = r.ID_Role
    LEFT JOIN pacientes p ON u.id = p.ID_Usuario
    LEFT JOIN psicologos ps ON u.id = ps.ID_Usuario
    $sqlWhere 
    ORDER BY u.id ASC";

    // ─ Preparar statement y ejecutar con parámetros tipados
    $stmt = $mysqli->prepare($sql);
    if ($types !== '') {
        // bind_param: types + unpacked array de params
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    // Obtener resultado como array asociativo
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ─ Retornar JSON con array de usuarios
    header("Content-Type: application/json");
    echo json_encode(['items' => $items]);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: ACTION=UPDATEROL - CAMBIAR ROL DE USUARIO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * action=updateRol - POST request para cambiar rol de usuario
 * 
 * FLUJO:
 * 1. Decodifica JSON body con usuario_id y rol_nuevo
 * 2. Consulta base de datos para obtener rol anterior
 * 3. Valida que usuario exista (si no, retorna 404)
 * 4. Valida que rol_nuevo sea diferente (si es igual, retorna 409 conflict)
 * 5. Inicia transacción MySQL (begin_transaction)
 * 6. Actualiza ID_Role en tabla users
 * 7. Si nuevo rol es 2 (Psicólogo):
 *    - Verifica si existe registro en tabla psicologos
 *    - Si no existe, crea registro con datos por defecto
 * 8. Confirma transacción (commit)
 * 9. Retorna JSON con mensaje de éxito
 * 10. Si hay error, revierte cambios (rollback)
 * 
 * PETICIÓN ESPERADA:
 * POST panelAdmin.php?action=updateRol
 * Body JSON: { \"usuario_id\": 5, \"rol_nuevo\": 2 }
 * 
 * RESPUESTAS:
 * - 200 OK: { \"message\": \"Rol actualizado correctamente\" }
 * - 404 Not Found: { \"error\": \"Usuario no encontrado\" }
 * - 409 Conflict: { \"error\": \"Ya tiene ese rol\" }
 * - 500 Internal Server Error: { \"error\": \"Error interno\" }
 * 
 * LÓGICA ESPECIAL:
 * Si cambias a rol 2 (Psicólogo), crea registro automático en tabla psicologos
 * Esto asegura que toda relación foránea esté intacta
 * Los datos por defecto son: ID_Especialidad=1, Nombre=Pendiente, Apellido=Pendiente
 * 
 * SEGURIDAD: Transacciones aseguran consistency (all-or-nothing updates)
 */

elseif($action === 'updateRol') {
    // Decodificar JSON del body POST
    $data = json_decode(file_get_contents('php://input'), true);
    $usuarioID = (int)($data['usuario_id'] ?? 0);
    $rolNuevo = (int)($data['rol_nuevo'] ?? 0);

    // ─ Consultar rol anterior del usuario para validación
    $stmt = $mysqli->prepare("SELECT ID_Role FROM users WHERE id=?");
    $stmt->bind_param("i", $usuarioID);
    $stmt->execute();
    $stmt->bind_result($rolAnterior);
    $stmt->fetch();
    $stmt->close();

    // ─ Validar que usuario exista (si rolAnterior es NULL, usuario no existe)
    if($rolAnterior === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }
    
    // ─ Validar que nuevo rol sea diferente del anterior
    if ((int)$rolAnterior === $rolNuevo) {
        http_response_code(409);
        echo json_encode(['error' => 'Ya tiene ese rol']);
        exit;
    }

    // ─ Iniciar transacción (all-or-nothing: si algún UPDATE falla, revierte todo)
    $mysqli->begin_transaction();
    try {
        // Actualizar ID_Role en tabla users
        $stmt = $mysqli->prepare("UPDATE users SET ID_Role=? WHERE id=?");
        $stmt->bind_param("ii", $rolNuevo, $usuarioID);
        $stmt->execute();
        $stmt->close();

        // ─ Crear registro psicólogo automáticamente si rol_nuevo = 2 (Psicólogo)
        if($rolNuevo === 2) {
            // Verificar si usuario ya tiene registro psicólogo
            $check = $mysqli->prepare("SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario=?");
            $check->bind_param("i", $usuarioID);
            $check->execute();
            $check->store_result();
            
            // Si no existe, insertar registro con datos por defecto
            if ($check->num_rows === 0){
                $check->close();
                // Insertar registro psicólogo con datos temporales
                $insert = $mysqli->prepare("INSERT INTO psicologos (ID_Usuario, ID_Especialidad, Nombre_Psicologo, Apellido_Psicologo) VALUES (?,?,?,?)");
                $idEspecialidad = 1;       // Especialidad por defecto
                $nombrePend = 'Pendiente'; // Nombre temporal
                $apellidoPend = 'Pendiente'; // Apellido temporal
                $insert->bind_param('iiss', $usuarioID, $idEspecialidad, $nombrePend, $apellidoPend);
                $insert->execute();
                $insert->close();
            } else {
                // Si ya existe, solo cerrar statement
                $check->close();
            }
        }
        
        // ─ Confirmar transacción (commit todos los cambios)
        $mysqli->commit();
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Rol actualizado correctamente']);
    } catch (Throwable $e) {
        // ─ Si hay error, revertir todos los cambios (rollback)
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Error interno']);
    }
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: ACTION=DELETEUSER - ELIMINAR USUARIO PERMANENTEMENTE
// ──────────────────────────────────────────────────────────────────────────────
/**
 * action=deleteUser - POST request para eliminar usuario
 * 
 * FLUJO:
 * 1. Decodifica JSON body con id del usuario a eliminar
 * 2. Prepara prepared statement DELETE WHERE id=?
 * 3. Ejecuta eliminación (todos los registros con ese user id)
 * 4. Cierra statement
 * 5. Retorna JSON con mensaje de éxito
 * 
 * PETICIÓN ESPERADA:
 * POST panelAdmin.php?action=deleteUser
 * Body JSON: { \"id\": 5 }
 * 
 * RESPUESTA:
 * - 200 OK: { \"message\": \"Usuario eliminado\" }
 * 
 * ⚠️ ADVERTENCIA:
 * Esta es una operación destructiva e irreversible
 * Elimina el usuario de la tabla users
 * Puede haber cascadas en la base de datos que eliminen:
 * - Registros pacientes (ID_Usuario)
 * - Registros psicólogos (ID_Usuario)
 * - Citas relacionadas
 * 
 * SEGURIDAD: Prepared statement previene SQL injection
 */

elseif ($action === 'deleteUser') {
    // Decodificar JSON del body POST
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)$data['id'];

    // ─ Preparar y ejecutar DELETE statement
    // Elimina el usuario con el id especificado
    $stmt = $mysqli->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    // ─ Retornar JSON con confirmación
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Usuario eliminado']);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: ACTION=LOGOUT - DESTRUIR SESIÓN DEL ADMINISTRADOR
// ──────────────────────────────────────────────────────────────────────────────
/**
 * action=logout - POST request para cerrar sesión
 * 
 * FLUJO:
 * 1. Destruye la sesión actual (limpia $_SESSION y cookies)
 * 2. Retorna JSON con mensaje de confirmación
 * 
 * PETICIÓN ESPERADA:
 * POST panelAdmin.php?action=logout
 * No requiere body
 * 
 * RESPUESTA:
 * - 200 OK: { \"message\": \"Sesión cerrada\" }
 * 
 * PROPÓSITO: Permitir que administrador cierre sesión sin navegar
 * Útil desde formulario AJAX que mantiene página abierta
 * El cliente entonces redirecciona a index.php (en JavaScript)
 * 
 * SEGURIDAD: Destruye completamente la sesión
 * - session_destroy() elimina archivo de sesión
 * - Cliente debe eliminar cookies de sesión
 */

elseif ($action === 'logout') {
    // Destruir la sesión actual (limpia todos los datos de sesión)
    session_destroy();
    
    // Retornar JSON con confirmación
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Sesión cerrada']);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 6: FALLBACK - ACTION INVÁLIDA O NO ESPECIFICADA
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Fallback para acciones inválidas o no soportadas
 * 
 * FLUJO:
 * 1. Si no coincide ninguna acción anterior
 * 2. Retorna HTTP 400 Bad Request
 * 3. Retorna JSON error con mensaje
 * 
 * RESPUESTA:
 * - 400 Bad Request: { \"error\": \"Accion invalida\" }
 * 
 * PROPÓSITO: Informar al cliente que action parameter es inválido
 * Posibles razones:
 * - Falta el parámetro action completamente
 * - Action tiene un valor no soportado
 * - Typo en el nombre de la acción
 */

// Respuesta para cualquier acción que no sea válida
http_response_code(400);
echo json_encode(['error' => 'Accion invalida']);