<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: db.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Archivo de configuración de conexión a base de datos MySQL
 *   - Establece conexión global con MySQLi
 *   - Maneja errores de conexión con respuesta JSON
 *   - Proporciona función helper para obtener ID de psicólogo
 * 
 * Funcionalidades:
 *   - Crear conexión MySQLi global ($mysqli)
 *   - Validar conexión y retornar error HTTP 500 si falla
 *   - Función obtenerIdPsicologo(): obtiene ID_Psicologo desde user_id
 * 
 * Uso:
 *   Incluir este archivo en cualquier script PHP que necesite acceso a BD:
 *   require_once 'db.php';
 *   
 *   Luego usar la variable global $mysqli para consultas
 * 
 * Configuración de conexión:
 *   - Host: b8y9eccnoennbc03jxfn-mysql.services.clever-cloud.com
 *   - Usuario: uonk3hj3tnpjdpfq
 *   - Base de datos: b8y9eccnoennbc03jxfn
 *   - Proveedor: Clever Cloud (MySQL hosting)
 * 
 * Respuesta de error (si falla conexión):
 *   HTTP 500 Internal Server Error
 *   {
 *     success: false,
 *     message: "Error de conexión a la BD: [mensaje de error]"
 *   }
 * 
 * Variable global exportada:
 *   - $mysqli: Objeto MySQLi con conexión activa
 * 
 * Funciones helper:
 *   - obtenerIdPsicologo(&$mysqli, $user_id): Obtiene ID_Psicologo
 * 
 * Dependencias:
 *   - Extensión PHP: mysqli (debe estar habilitada)
 *   - Acceso a red: conexión al servidor MySQL de Clever Cloud
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * SECCIÓN 1: CONEXIÓN A BASE DE DATOS
 * ────────────────────────────────────────────────────────────────────────────
 */

/**
 * CREAR CONEXIÓN MySQLi
 * 
 * Constructor MySQLi:
 *   new mysqli(host, usuario, contraseña, base_datos)
 * 
 * Parámetros:
 *   - host: Servidor MySQL (Clever Cloud)
 *   - usuario: Nombre de usuario de la base de datos
 *   - password: Contraseña del usuario
 *   - database: Nombre de la base de datos
 * 
 * Variable global:
 *   $mysqli: Objeto de conexión que se usará en todos los scripts
 */
$mysqli = new mysqli('b8y9eccnoennbc03jxfn-mysql.services.clever-cloud.com', 'uonk3hj3tnpjdpfq', 'jywVsjXDFGN6cHLfQscM', 'b8y9eccnoennbc03jxfn');

/**
 * VALIDAR CONEXIÓN
 * 
 * $mysqli->connect_error:
 *   - null: Conexión exitosa
 *   - string: Mensaje de error de conexión
 * 
 * Si hay error:
 *   1. Establecer código HTTP 500 (Internal Server Error)
 *   2. Retornar JSON con mensaje de error
 *   3. Terminar ejecución del script
 * 
 * Posibles causas de error:
 *   - Servidor MySQL caído
 *   - Credenciales incorrectas
 *   - Base de datos no existe
 *   - Firewall bloqueando conexión
 *   - Sin conexión a internet
 */
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la BD: ' . $mysqli->connect_error]);
    exit;
}

/**
 * SECCIÓN 2: FUNCIONES HELPER
 * ────────────────────────────────────────────────────────────────────────────
 */

/**
 * FUNCIÓN: OBTENER ID DEL PSICÓLOGO
 * 
 * Obtiene el ID_Psicologo desde la tabla psicologos usando el ID_Usuario
 * Esta función es muy usada porque relaciona usuarios con psicólogos
 * 
 * Parámetros:
 *   @param mysqli &$mysqli - Referencia a la conexión de base de datos
 *                            El & indica que se pasa por referencia (no copia)
 *   @param int $user_id - ID del usuario autenticado (de $_SESSION['user_id'])
 * 
 * Retorna:
 *   @return int|null - ID_Psicologo si existe, null si no se encuentra
 * 
 * Proceso:
 *   1. Preparar consulta con prepared statement
 *   2. Vincular parámetro user_id
 *   3. Ejecutar consulta
 *   4. Obtener resultado
 *   5. Si hay fila: retornar ID_Psicologo
 *   6. Si no hay fila: retornar null
 * 
 * Uso:
 *   $id_psicologo = obtenerIdPsicologo($mysqli, $_SESSION['user_id']);
 *   if ($id_psicologo === null) {
 *     // El usuario no es un psicólogo registrado
 *   }
 * 
 * Seguridad:
 *   - Usa prepared statement para prevenir inyección SQL
 *   - Valida que el statement se prepare correctamente
 */
function obtenerIdPsicologo(&$mysqli, $user_id) {
    /**
     * PREPARAR CONSULTA
     * 
     * SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario = ?
     * 
     * El ? es un placeholder que será reemplazado por $user_id
     */
    $stmt = $mysqli->prepare("SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario = ?");
    
    /**
     * VALIDAR QUE SE PREPARÓ CORRECTAMENTE
     * 
     * Si falla (por ejemplo, error de sintaxis SQL):
     *   - Retornar null
     */
    if (!$stmt) return null;
    
    /**
     * VINCULAR PARÁMETRO
     * 
     * bind_param('i', $user_id):
     *   - 'i': tipo integer
     *   - $user_id: valor a vincular
     */
    $stmt->bind_param('i', $user_id);
    
    /**
     * EJECUTAR CONSULTA
     */
    $stmt->execute();
    
    /**
     * OBTENER RESULTADOS
     */
    $res = $stmt->get_result();
    
    /**
     * PROCESAR RESULTADO
     * 
     * fetch_assoc(): Obtiene la fila como array asociativo
     * 
     * Si hay fila:
     *   - Retornar ID_Psicologo
     * Si no hay fila:
     *   - Retornar null (el usuario no es psicólogo)
     */
    if ($row = $res->fetch_assoc()) {
        return $row['ID_Psicologo'];
    }
    return null;
}
