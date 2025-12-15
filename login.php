<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: login.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: Endpoint de autenticación API para inicio de sesión
// Procesa credenciales de usuario (email y contraseña) y genera sesión PHP
// FUNCIONALIDAD PRINCIPAL: Validación de credenciales + Creación de sesión + JSON response
// MÉTODOS SOPORTADOS: POST
// DEPENDENCIAS: db.php (conexión MySQLi), password_verify() (PHP built-in)
// RESPUESTA ESPERADA: JSON con {"success": bool, "usuario": {...}} o {"success": false, "error": "..."}
// ROLES AUTORIZADOS: Público (sin autenticación previa requerida)
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y CONFIGURACIÓN
// ──────────────────────────────────────────────────────────────────────────────
// Inicia la sesión PHP para poder almacenar datos de autenticación
// Las variables de sesión persisten entre peticiones HTTP
session_start();

// Incluye el archivo de configuración de base de datos
// db.php contiene la conexión MySQLi a Clever Cloud:
// - Host: b8y9eccnoennbc03jxfn-mysql.services.clever-cloud.com:3306
// - Database: b8y9eccnoennbc03jxfn
// - Variable global: $mysqli (conexión MySQLi)
include 'db.php';

// Define el tipo de respuesta HTTP como JSON con codificación UTF-8
// Importante para que el frontend (auth.js) interprete correctamente la respuesta
header('Content-Type: application/json; charset=utf-8');
// ──────────────────────────────────────────────────────────────────────────────

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VALIDACIÓN DEL MÉTODO HTTP
// ──────────────────────────────────────────────────────────────────────────────
// Solo procesa peticiones POST (envío de formulario)
// Rechaza otros métodos como GET, PUT, DELETE, etc.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ──────────────────────────────────────────────────────────────────────────
    // SECCIÓN 3: EXTRACCIÓN Y SANITIZACIÓN DE PARÁMETROS
    // ──────────────────────────────────────────────────────────────────────────
    // Obtiene el email del cuerpo POST
    // trim() elimina espacios en blanco al inicio y final
    $email = trim($_POST['email']);
    
    // Obtiene la contraseña del cuerpo POST
    // trim() aquí también es importante para contraseñas con espacios accidentales
    $password = trim($_POST['password']);
    
    // ──────────────────────────────────────────────────────────────────────────
    // SECCIÓN 4: CONSULTA PREPARADA (PARAMETRIZADA)
    // ──────────────────────────────────────────────────────────────────────────
    // Prepara una consulta SQL con placeholder '?' para prevenir SQL injection
    // SELECT: id (PK), email (email del usuario), password_hash (hash bcrypt), ID_Role (1=Paciente, 2=Psicólogo, 3=Admin)
    // WHERE email = ?: Solo busca por el email proporcionado
    $stmt = $mysqli->prepare("SELECT id, email, password_hash, ID_Role FROM users WHERE email = ?");
    
    // bind_param("s", $email): Vincula la variable $email al parámetro de consulta
    // "s" = tipo string (previene SQL injection)
    // Cualquier intento de inyección SQL es neutralizado por la parametrización
    $stmt->bind_param("s", $email);
    
    // Ejecuta la consulta preparada con el email sanitizado
    $stmt->execute();
    
    // Obtiene el resultado de la consulta
    $resultado = $stmt->get_result();
    
    // ──────────────────────────────────────────────────────────────────────────
    // SECCIÓN 5: VALIDACIÓN DE EXISTENCIA DE USUARIO
    // ──────────────────────────────────────────────────────────────────────────
    // Verifica si la consulta devolvió exactamente 1 registro (el usuario existe)
    if ($resultado->num_rows === 1) {
        
        // Obtiene los datos del usuario como array asociativo
        // Keys: 'id', 'email', 'password_hash', 'ID_Role'
        $user = $resultado->fetch_assoc();

        // ──────────────────────────────────────────────────────────────────────────
        // SECCIÓN 6: VERIFICACIÓN DE CONTRASEÑA CON HASH BCRYPT
        // ──────────────────────────────────────────────────────────────────────────
        // password_verify() compara la contraseña en texto plano con el hash almacenado
        // Utiliza bcrypt (algoritmo criptográfico seguro) para verificación
        // - $password: Contraseña ingresada por el usuario
        // - $user['password_hash']: Hash bcrypt almacenado en la base de datos
        // Retorna true si coinciden, false si no coinciden
        if (password_verify($password, $user['password_hash'])) {
            
            // ──────────────────────────────────────────────────────────────────────────
            // SECCIÓN 7: CREACIÓN DE VARIABLES DE SESIÓN
            // ──────────────────────────────────────────────────────────────────────────
            // Almacena los datos del usuario autenticado en la sesión PHP
            // Estas variables estarán disponibles en todas las páginas del sitio
            // mientras la sesión esté activa
            
            // ID único del usuario (clave primaria de la tabla users)
            $_SESSION['user_id'] = $user['id'];
            
            // Email del usuario autenticado (usado para mostrar en interfaz)
            $_SESSION['user_email'] = $user['email'];
            
            // Rol del usuario para control de acceso:
            // - 1: Paciente (acceso a calendario de citas, notas)
            // - 2: Psicólogo (acceso a dashboard completo, notas de pacientes)
            // - 3: Administrador (acceso a panel de control administrativo)
            $_SESSION['user_role'] = $user['ID_Role'];
            
            // ──────────────────────────────────────────────────────────────────────────
            // SECCIÓN 8: RESPUESTA JSON EXITOSA
            // ──────────────────────────────────────────────────────────────────────────
            // Responde al frontend (auth.js) con éxito en formato JSON
            // El frontend usará esta respuesta para:
            // 1. Redirigir al usuario a la página correspondiente según su rol
            // 2. Actualizar el estado de autenticación en la UI
            // 3. Guardar información del usuario en localStorage si es necesario
            echo json_encode([
                "success" => true,  // Indica que el login fue exitoso
                "usuario" => [
                    "id" => $user['id'],           // ID del usuario
                    "email" => $user['email'],     // Email del usuario
                    "Rol" => $user['ID_Role']      // Rol para determinar redirección
                ]
            ]);
        } else {
            // ──────────────────────────────────────────────────────────────────────────
            // SECCIÓN 9A: RESPUESTA JSON - CONTRASEÑA INCORRECTA
            // ──────────────────────────────────────────────────────────────────────────
            // El email existe pero la contraseña no coincide
            // Por seguridad, se da un mensaje genérico sin confirmar la existencia del email
            echo json_encode([
                "success" => false,
                "error" => "Contraseña incorrecta."
            ]);
        }
    } else {
        // ──────────────────────────────────────────────────────────────────────────
        // SECCIÓN 9B: RESPUESTA JSON - EMAIL NO REGISTRADO
        // ──────────────────────────────────────────────────────────────────────────
        // El email no existe en la base de datos (num_rows !== 1)
        // No se encontró ningún usuario con ese email
        echo json_encode([
            "success" => false,
            "error" => "El correo no está registrado."
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SECCIÓN 10: LIBERACIÓN DE RECURSOS
    // ──────────────────────────────────────────────────────────────────────────
    // Cierra la consulta preparada y libera memoria
    // Importante para evitar memory leaks en aplicaciones con múltiples peticiones
    $stmt->close();
}
?>