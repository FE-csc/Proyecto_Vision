<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * FILE: register.php
 * ════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: API endpoint para registro de nuevos usuarios
 * Crea cuenta en tabla users con email, password hash, y rol por defecto
 * FUNCIONALIDAD: POST request → Validar datos → Verificar email único → Crear usuario
 * DEPENDENCIAS: MySQLi db.php (conexión a BD), password_hash (bcrypt)
 * MÉTODOS: POST (JSON body con email y password)
 * RESPUESTAS: JSON con success boolean + message
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * PARÁMETROS (POST body JSON):
 * - email: Correo electrónico del usuario (requerido, validado)
 * - password: Contraseña en texto plano (requerido, mínimo 6 caracteres)
 * 
 * RESPUESTAS HTTP:
 * - 201 Created: Usuario creado exitosamente
 * - 400 Bad Request: Validación fallida (email inválido, password corta, JSON inválido)
 * - 405 Method Not Allowed: No es POST request
 * - 409 Conflict: Email ya existe en base de datos
 * - 500 Internal Server Error: Error en consultas SQL
 * 
 * RESPUESTA JSON:
 * {
 *   "success": boolean,
 *   "message": "Descripción del resultado"
 * }
 * 
 * FLUJO DE VALIDACIÓN:
 * 1. Verificar método HTTP (debe ser POST)
 * 2. Parsear JSON del body
 * 3. Validar email con filter_var (FILTER_VALIDATE_EMAIL)
 * 4. Validar longitud de password (mínimo 6 caracteres)
 * 5. Verificar que email no exista en base de datos
 * 6. Hash password con password_hash (bcrypt, PASSWORD_DEFAULT)
 * 7. Insertar en tabla users (email, password_hash, ID_ROLE=1)
 * 
 * SEGURIDAD:
 * - Prepared statements: Previenen SQL injection
 * - password_hash(): Bcrypt password hashing (irreversible)
 * - filter_var(): Validación de email
 * - Trimming y lowercase: Normalización de email
 * - Verificación de email único: Evita duplicados
 * 
 * NOTAS:
 * - Rol por defecto: 1 (Paciente) para todos los registros
 * - Los psicólogos se crean manualmente por admin
 * - Email se normaliza (lowercase, trimmed)
 * - Password se recibe en texto plano (HTTPS obligatorio en producción)
 */

// Establecer content-type como JSON
header('Content-Type: application/json; charset=utf-8');

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: VALIDACIÓN DEL MÉTODO HTTP
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Verificar que la petición sea POST (no GET, PUT, DELETE, etc.)
 * 
 * FLUJO:
 * 1. $_SERVER['REQUEST_METHOD']: Método HTTP usado (GET, POST, etc.)
 * 2. Compara con string 'POST'
 * 3. Si no es POST:
 *    - http_response_code(405): Method Not Allowed
 *    - Retorna JSON con error
 *    - exit: Termina ejecución
 * 4. Si es POST: Continúa con siguiente sección
 * 
 * PROPÓSITO: Proteger endpoint para que solo acepte POST
 * GET no debe usarse para crear recursos
 * 
 * HTTP 405:
 * - Código estándar para método no permitido
 * - Cliente sabe que debe intentar POST
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Usa POST.']);
    exit;
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: PARSEO Y VALIDACIÓN DE JSON DEL BODY
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Decodificar JSON del request body
 * 
 * FLUJO:
 * 1. file_get_contents('php://input'): Lee el body crudo (JSON string)
 * 2. json_decode(..., true): Convierte JSON string a array PHP
 *    - Segundo parámetro true = retorna array (no object)
 * 3. Verifica que resultado sea array (is_array)
 * 4. Si no es array (JSON inválido):
 *    - http_response_code(400): Bad Request
 *    - Retorna JSON con error
 *    - exit: Termina ejecución
 * 5. Si es array válido: Continúa
 * 
 * PROPÓSITO: Obtener datos del cliente en formato JSON
 * Asegura que JSON sea válido antes de usarlo
 * 
 * EJEMPLO BODY ESPERADO:
 * {
 *   "email": "usuario@example.com",
 *   "password": "micontraseña123"
 * }
 * 
 * HTTP 400:
 * - Código para request malformado
 * - JSON inválido es error del cliente
 * 
 * SEGURIDAD:
 * - json_decode sin segundo parámetro retorna object
 * - Usamos true para obtener array (más seguro)
 * - Validamos que sea array antes de acceder
 */

// Decodificar JSON del body
$input = json_decode(file_get_contents('php://input'), true);

// Validar que el JSON sea válido
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Body inválido. Se espera JSON.']);
    exit;
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: EXTRACCIÓN Y NORMALIZACIÓN DE DATOS
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Extraer email y password del input, normalizar email
 * 
 * FLUJO:
 * 1. Extrae email: $input['email'] ?? '' (default vacío si no existe)
 * 2. trim(): Elimina espacios al inicio/final
 * 3. strtolower(): Convierte a minúsculas (normaliza)
 * 4. Extrae password: $input['password'] ?? '' (default vacío)
 * 5. Sin procesamiento especial (se valida después)
 * 6. Define $rol = 1 (Paciente por defecto)
 * 
 * NORMALIZACIÓN DE EMAIL:
 * - trim(): Elimina espacios en blanco (usuario frecuentemente copia mal)
 * - strtolower(): RFC 5321 permite mayúsculas pero normalizamos a minúsculas
 *   Evita duplicados (Usuario@Example.com != usuario@example.com)
 * 
 * PROPÓSITO: Preparar datos para validación y base de datos
 * 
 * VARIABLE $rol:
 * - 1 = Paciente (rol por defecto para auto-registro)
 * - 2 = Psicólogo (creado por admin, no por auto-registro)
 * - 3 = Administrador (creado por admin)
 * 
 * NOTAS:
 * - Password NO se normaliza (es sensible a mayúsculas)
 * - Password se trimea solo si usuario agregó espacios accidentalmente
 *   Pero no se normaliza a lowercase
 */

// Extraer y normalizar email
$email = trim(strtolower($input['email'] ?? ''));
// Extraer password (sin normalizar: es sensible a mayúsculas)
$password = $input['password'] ?? '';
// Rol por defecto: Paciente (1)
$rol = 1;

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: VALIDACIÓN DE EMAIL
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Validar que el email tenga formato correcto (RFC 5322)
 * 
 * FLUJO:
 * 1. filter_var($email, FILTER_VALIDATE_EMAIL): Valida formato RFC
 * 2. Si retorna false (email inválido):
 *    - Establece HTTP 400 (Bad Request)
 *    - Retorna JSON con éxito=false
 *    - Detiene ejecución (exit)
 * 3. Si retorna true o string: Email válido, continúa
 * 
 * VALIDACIÓN FILTER_VALIDATE_EMAIL:
 * - Verifica estructura local@domain (antes@después del @)
 * - Valida TLD válido (.com, .es, etc.)
 * - NO verifica si mailbox existe (imposible sin SMTP)
 * - NO verifica si dominio existe (requeriría DNS lookup)
 * - Solo verifica formato sintáctico
 * 
 * PROPÓSITO: Rechazar emails malformados antes de guardar
 * 
 * EJEMPLOS VÁLIDOS:
 * - usuario@example.com ✓
 * - juan.perez@clinic.es ✓
 * - admin+test@hospital.org ✓
 * 
 * EJEMPLOS INVÁLIDOS:
 * - usuario (falta @domain)
 * - @example.com (falta local)
 * - usuario@.com (falta dominio)
 * - usuario@domain (falta TLD)
 * - usuario @example.com (espacio en local)
 * 
 * HTTP 400 Bad Request:
 * - Cliente envió datos con formato inválido
 * - Culpa del cliente, no del servidor
 * - Mensaje: "Ingrese un correo electrónico válido."
 * 
 * SEGURIDAD: Previene inyección de caracteres especiales en email
 */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ingrese un correo electrónico válido.']);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: VALIDACIÓN DE PASSWORD
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Validar longitud mínima de contraseña
 * 
 * FLUJO:
 * 1. strlen($password): Obtiene longitud de string password
 * 2. Compara: ¿longitud < 6 caracteres?
 * 3. Si es menor a 6:
 *    - http_response_code(400): Bad Request
 *    - Retorna JSON con error
 *    - exit: Termina ejecución
 * 4. Si es >= 6: Continúa
 * 
 * REGLA DE LONGITUD: Mínimo 6 caracteres
 * - No es ideal (debería ser 8+)
 * - Pero cumple con requisito base
 * - Contraseña más fuerte = más caracteres
 * 
 * PROPÓSITO: Asegurar que contraseña tenga mínima complejidad
 * Previene contraseñas triviales como "123456"
 * 
 * VALIDACIÓN RECOMENDADA:
 * - Mínimo 8 caracteres (estándar)
 * - Mix de mayúsculas, minúsculas, números, símbolos
 * - NO incluir email del usuario
 * - Verificar contra lista de contraseñas comunes
 * 
 * NOTA ACTUAL: Solo se valida longitud (6+ caracteres)
 * 
 * HTTP 400:
 * - Request inválido por datos insuficientes
 * - Culpa del cliente por password débil
 */

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 6: VERIFICACIÓN DE EMAIL ÚNICO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Verificar que el email no esté registrado en la base de datos
 * 
 * FLUJO:
 * 1. require_once 'db.php': Incluye conexión MySQLi ($mysqli)
 * 2. $mysqli->prepare(): Prepara sentencia SQL SELECT
 *    - Busca si existe fila con ese email
 *    - ? es placeholder para parámetro (email)
 * 3. Verifica que prepare() fue exitoso (!$stmt = error)
 *    - Si falla: Error de sintaxis SQL (culpa del servidor)
 *    - Retorna 500 Internal Server Error
 * 4. $stmt->bind_param('s', $email):
 *    - 's' = tipo string (email es string)
 *    - Vincula variable $email al placeholder ?
 *    - Previene SQL injection (parámetro separado)
 * 5. $stmt->execute(): Ejecuta SELECT
 * 6. $stmt->store_result(): Obtiene información de resultado
 *    - Sin cargar datos reales a memoria
 *    - Solo para verificar num_rows
 * 7. $stmt->num_rows > 0: ¿Existe alguna fila?
 *    - Si existe (num_rows > 0): Email ya registrado
 *      * Cierra statement ($stmt->close())
 *      * Retorna 409 Conflict (recurso ya existe)
 *      * Error: "El correo ya está en uso."
 *    - Si no existe: Email disponible, continúa
 * 8. $stmt->close(): Libera recursos
 * 
 * PROPÓSITO: Garantizar que cada email sea único en tabla users
 * Previene registros duplicados de mismo usuario
 * 
 * PREPARED STATEMENT:
 * - Placeholder ?: Separado de SQL string
 * - bind_param(): Vincula valor seguramente
 * - Imposible inyectar SQL (usuario no puede quebrar estructura)
 * 
 * EJEMPLO ENTRADA MALICIOSA (sin prepared statement):
 * email: ' OR '1'='1
 * SQL sin prepared: SELECT FROM users WHERE email = '' OR '1'='1'
 * Resultado: Retorna todos los usuarios (MALA)
 * 
 * CON PREPARED STATEMENT:
 * email: ' OR '1'='1
 * SQL: SELECT FROM users WHERE email = ?
 * Parameter: ' OR '1'='1 (tratado como string literal, no SQL)
 * Resultado: Busca email literal = ' OR '1'='1' (SEGURO)
 * 
 * HTTP 409:
 * - Conflict: Recurso ya existe
 * - Email ya está registrado
 * - Cliente debe elegir otro email
 * 
 * HTTP 500:
 * - Error de servidor (problema de BD)
 * - Conexión perdida, sintaxis SQL incorrecta, etc.
 * 
 * MEJORAS FUTURAS:
 * - Enviar email de verificación antes de confirmar cuenta
 * - Implementar cuenta inactiva hasta verificación
 * - Prevenir spam de registros falsos
 */

// Incluir conexión a base de datos
require_once 'db.php';

// Preparar consulta SELECT para verificar email único
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");

// Verificar que prepare fue exitoso
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la consulta.']);
    exit;
}

// Vincular email como parámetro (type: string 's')
$stmt->bind_param('s', $email);

// Ejecutar SELECT
$stmt->execute();

// Obtener información del resultado (sin cargar datos)
$stmt->store_result();

// Verificar si existe algún email igual
if ($stmt->num_rows > 0) {
    // Email ya existe en base de datos
    $stmt->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'El correo ya está en uso.']);
    exit;
}

// Email no existe, cerrar statement
$stmt->close();


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 7: HASHING DE PASSWORD E INSERCIÓN EN BD
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Hashear contraseña con bcrypt e insertar usuario en tabla users
 * 
 * FLUJO:
 * 1. password_hash($password, PASSWORD_DEFAULT):
 *    - Hashea password con algoritmo más seguro disponible
 *    - PASSWORD_DEFAULT = bcrypt (actualmente)
 *    - Crea hash irreversible (no se puede recuperar password)
 *    - Incluye salt aleatorio en hash
 *    - Resultado: 60 caracteres aproximadamente
 * 2. $mysqli->prepare(): Prepara INSERT statement
 *    - Inserta en tabla users (email, password_hash, ID_ROLE)
 *    - 3 ? para 3 parámetros
 * 3. Verifica prepare() exitoso
 *    - Si falla: Error de sintaxis SQL
 *    - Retorna 500 Internal Server Error
 * 4. bind_param('ssi', ...):
 *    - 's' (primera): tipo string para email
 *    - 's' (segunda): tipo string para password_hash
 *    - 'i' (tercera): tipo integer para $rol (ID_ROLE)
 *    - Vincula variables a placeholders
 *    - Previene SQL injection
 * 5. execute(): Ejecuta INSERT
 *    - Si retorna true: Usuario insertado exitosamente
 *      * http_response_code(201): Created (recurso creado)
 *      * JSON: success=true, message="Cuenta creada correctamente"
 *    - Si retorna false: Error en inserción
 *      * http_response_code(500): Internal Server Error
 *      * JSON: success=false, message="No se pudo crear la cuenta"
 * 6. $insert->close(): Libera statement
 * 7. $mysqli->close(): Cierra conexión BD
 * 
 * PROPÓSITO: Almacenar usuario con contraseña hasheada de forma irreversible
 * 
 * PASSWORD HASHING (BCRYPT):
 * - password_hash(plaintext, PASSWORD_DEFAULT)
 * - Password en texto plano → hash irreversible
 * - Imposible recuperar password original del hash
 * - Único (cada hash es diferente aunque password igual)
 * - Slow (tarda ~0.5 segundos por hash = frena ataques)
 * - Salted (incluye sal aleatoria = mismo password ≠ mismo hash)
 * 
 * VENTAJAS BCRYPT vs SHA256:
 * - Bcrypt: Lento (seguro), evoluciona (cost factor)
 * - SHA256: Rápido (inseguro), estático, sin salt por defecto
 * 
 * TABLA USERS ESTRUCTURA:
 * - id: INT PRIMARY KEY AUTO_INCREMENT
 * - email: VARCHAR(255) UNIQUE (solo un por email)
 * - password_hash: VARCHAR(255) (para almacenar 60 caracteres bcrypt)
 * - ID_ROLE: INT (1=Paciente, 2=Psicólogo, 3=Admin)
 * - created_at: TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * 
 * HTTP 201 Created:
 * - Recurso creado exitosamente
 * - Cliente obtiene confirmación de éxito
 * - User debe ir a login ahora
 * 
 * HTTP 500 Internal Server Error:
 * - Error en servidor (BD inaccesible, constraint violated, etc.)
 * - Cliente no puede hacer nada
 * - Servidor debe investigar logs
 * 
 * SEGURIDAD RESUMEN:
 * - Email: Validado con filter_var, único en BD
 * - Password: Mínimo 6, hasheado con bcrypt, no almacenado en texto
 * - Prepared statements: Previenen SQL injection
 * - Type safety: 'ssi' especifica tipos de parámetros
 * 
 * FLUJO COMPLETO REGISTRO:
 * 1. Cliente POST JSON {email, password}
 * 2. Validar método (POST)
 * 3. Parsear JSON
 * 4. Normalizar email (trim, lowercase)
 * 5. Validar email (filter_var)
 * 6. Validar password (strlen >= 6)
 * 7. Verificar email único (SELECT)
 * 8. Hashear password (bcrypt)
 * 9. Insertar en BD (INSERT)
 * 10. Retornar 201 + success message
 * 
 * PRÓXIMOS PASOS DEL USUARIO:
 * - Ir a login.php
 * - Email + password
 * - Crear sesión ($_SESSION)
 * - Redirigir a dashboard
 */

// Hashear password con bcrypt (irreversible)
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Preparar statement INSERT para tabla users
$insert = $mysqli->prepare("INSERT INTO users (email, password_hash, ID_ROLE) VALUES (?, ?, ?)");

// Verificar que prepare fue exitoso
if (!$insert) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al preparar la inserción.']);
    exit;
}

// Vincular parámetros (string email, string hash, int rol)
$insert->bind_param('ssi', $email, $password_hash, $rol);

// Ejecutar INSERT
if ($insert->execute()) {
    // Inserción exitosa
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Cuenta creada correctamente.']);
} else {
    // Error en inserción (BD inaccesible, constraint violated, etc.)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear la cuenta.']);
}

// Cerrar statement e conexión
$insert->close();
$mysqli->close();
