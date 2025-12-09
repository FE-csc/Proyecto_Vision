<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * ENVIAR.PHP - API DE ENVÍO DE FORMULARIO DE CONTACTO
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Endpoint para procesar y enviar mensajes del formulario de contacto del sitio web.
 * Utiliza PHPMailer para enviar correos electrónicos vía SMTP de Gmail.
 * 
 * MÉTODO: POST
 * FORMATO: application/x-www-form-urlencoded
 * 
 * PARÁMETROS ESPERADOS:
 * - name (string): Nombre del remitente
 * - email (string): Email del remitente
 * - phone (string): Teléfono del remitente
 * - message (string): Mensaje de contacto
 * 
 * RESPUESTAS JSON:
 * - ok: true/false
 * - message: Mensaje de éxito
 * - error: Descripción del error (si falla)
 * 
 * DEPENDENCIAS:
 * - PHPMailer 7.0.1
 * - Cuenta Gmail con contraseña de aplicación
 * 
 * CONFIGURACIÓN SMTP:
 * - Servidor: smtp.gmail.com
 * - Puerto: 587 (STARTTLS)
 * - Autenticación: Gmail App Password
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: IMPORTACIÓN DE CLASES DE PHPMAILER
// ────────────────────────────────────────────────────────────────────────────

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Cargar archivos necesarios de PHPMailer
 * Ruta relativa a la carpeta PHPMailer-7.0.1
 */
require 'PHPMailer-7.0.1\PHPMailer-7.0.1\src\Exception.php';
require 'PHPMailer-7.0.1\PHPMailer-7.0.1\src\PHPMailer.php';
require 'PHPMailer-7.0.1/PHPMailer-7.0.1/src/SMTP.php';

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: CONFIGURACIÓN DE RESPUESTA Y VALIDACIÓN DE CAMPOS
// ────────────────────────────────────────────────────────────────────────────

header("Content-Type: application/json");

/**
 * Validar que todos los campos requeridos estén presentes en POST
 * Campos obligatorios: name, email, phone, message
 */
if(!isset($_POST['name'], $_POST['email'], $_POST['phone'], $_POST['message'])){
    echo json_encode(['ok' => false, 'error' => 'Faltan campos']);
    exit;
}

// Extraer datos del formulario
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$message = $_POST['message'];

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: CONFIGURACIÓN DE PHPMAILER
// ────────────────────────────────────────────────────────────────────────────

/**
 * Crear instancia de PHPMailer con manejo de excepciones
 * @param true - Habilita excepciones para mejor manejo de errores
 */
$mail = new PHPMailer(true);

/**
 * Configuración de debug (nivel 3)
 * Muestra información detallada de la conexión SMTP
 * Útil para diagnóstico de problemas de envío
 */
$mail->SMTPDebug = 3;
$mail->Debugoutput = 'html';

try {
    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 4: CONFIGURACIÓN SMTP DE GMAIL
    // ────────────────────────────────────────────────────────────────────────
    
    /**
     * Configurar servidor SMTP de Gmail
     * 
     * - isSMTP(): Usar protocolo SMTP
     * - Host: Servidor de Gmail
     * - SMTPAuth: Requiere autenticación
     * - Username: Correo de Gmail
     * - Password: Contraseña de aplicación (App Password)
     * - SMTPSecure: STARTTLS para conexión segura
     * - Port: 587 para STARTTLS (alternativa: 465 para SSL)
     * 
     * NOTA: Es necesario generar una contraseña de aplicación en Gmail
     * desde la configuración de seguridad de la cuenta
     */
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'warioregon123@gmail.com';  
    $mail->Password   = 'uyhv odfb csit upag';   
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 5: CONFIGURACIÓN DE REMITENTE Y DESTINATARIO
    // ────────────────────────────────────────────────────────────────────────
    
    /**
     * Configurar dirección de origen
     * setFrom(email, nombre_visible)
     */
    $mail->setFrom('warioregon123@gmail.com', 'FORMULARIO WEB');
    
    /**
     * Configurar destinatario
     * addAddress(email) - Puede agregar múltiples destinatarios
     */
    $mail->addAddress('warioregon123@gmail.com');

    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 6: CONTENIDO DEL CORREO
    // ────────────────────────────────────────────────────────────────────────
    
    /**
     * Configurar formato y contenido del email
     * 
     * - isHTML(true): Permitir formato HTML
     * - Subject: Asunto del correo
     * - Body: Cuerpo en HTML con datos del formulario
     */
    $mail->isHTML(true);
    $mail->Subject = "Nuevo mensaje de contacto";
    $mail->Body    = "
        <h3>Nuevo mensaje enviado desde tu página</h3>
        <p><b>Nombre:</b> $name</p>
        <p><b>Email:</b> $email</p>
        <p><b>Teléfono:</b> $phone</p>
        <p><b>Mensaje:</b><br>$message</p>
    ";

    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 7: ENVÍO Y RESPUESTA
    // ────────────────────────────────────────────────────────────────────────
    
    /**
     * Enviar correo electrónico
     * Si tiene éxito, devolver respuesta JSON positiva
     */
    $mail->send();

    echo json_encode(['ok' => true, 'message' => 'Enviado correctamente']);
    
} catch (Exception $e) {
    /**
     * Manejo de errores
     * Captura excepciones de PHPMailer y devuelve error en JSON
     * ErrorInfo contiene detalles del error SMTP
     */
    echo json_encode(['ok' => false, 'error' => $mail->ErrorInfo]);
}
