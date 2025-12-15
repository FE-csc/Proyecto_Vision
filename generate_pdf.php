<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * GENERATE_PDF.PHP - GENERADOR DE INFORMES DE SESIÓN EN PDF
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Genera documentos PDF profesionales con información de sesiones clínicas.
 * Utiliza Dompdf para convertir HTML en PDF descargable.
 * 
 * MÉTODO: POST
 * FORMATO: application/x-www-form-urlencoded
 * 
 * PARÁMETROS ESPERADOS:
 * - patient_name (string): Nombre del paciente
 * - patient_age (string): Edad del paciente
 * - patient_id (string): Cédula de identidad
 * - session_date (string): Fecha de la sesión (YYYY-MM-DD)
 * - session_time (string): Hora de la sesión (HH:MM)
 * - session_notes (string): Notas clínicas y observaciones
 * 
 * DEPENDENCIAS:
 * - Dompdf: composer require dompdf/dompdf
 * - Fuente DejaVu Sans (incluida en Dompdf)
 * 
 * SALIDA:
 * - Archivo PDF descargable con formato profesional
 * - Nombre: Informe_Sesion_[fecha].pdf
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: VALIDACIÓN DE MÉTODO HTTP
// ────────────────────────────────────────────────────────────────────────────

/**
 * Permitir solo peticiones POST
 * Retorna HTTP 405 (Method Not Allowed) para otros métodos
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método no permitido. Use POST.";
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: CARGA DE DOMPDF
// ────────────────────────────────────────────────────────────────────────────

/**
 * Cargar autoloader de Composer
 * Incluye Dompdf y sus dependencias
 */
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    http_response_code(500);
    echo 'Las dependencias no están instaladas. Ejecute "composer install" en el directorio del proyecto para instalarlas.';
    exit;
}

/**
 * Algunas instalaciones en hosting compartido no tienen Composer configurado
 * correctamente. Para evitar el error "Class \"Dompdf\Options\" not found",
 * intentamos cargar directamente el autoloader de Dompdf si la clase aún no
 * existe. Si no está disponible, devolvemos un error claro al cliente en lugar
 * de un fatal error.
 */
if (!class_exists('Dompdf\\Options')) {
    $dompdfAutoload = __DIR__ . '/vendor/dompdf/dompdf/autoload.inc.php';
    if (file_exists($dompdfAutoload)) {
        require_once $dompdfAutoload;
    } else {
        http_response_code(500);
        echo 'Dependencia Dompdf faltante. Ejecute "composer install" en el directorio del proyecto para instalarla.';
        exit;
    }
}

use Dompdf\Dompdf;
use Dompdf\Options;

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: FUNCIÓN DE SANITIZACIÓN
// ────────────────────────────────────────────────────────────────────────────

/**
 * Función para sanitizar entrada de usuario
 * Previene XSS y asegura codificación UTF-8 correcta
 * 
 * @param string $s - Cadena a sanitizar
 * @return string Cadena limpia y segura
 */
function safe($s) {
    return htmlspecialchars(trim($s), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: EXTRACCIÓN Y SANITIZACIÓN DE DATOS DEL FORMULARIO
// ────────────────────────────────────────────────────────────────────────────

/**
 * Extraer y sanitizar todos los campos del POST
 * Valores por defecto: cadena vacía si no están presentes
 */
$patient_name = isset($_POST['patient_name']) ? safe($_POST['patient_name']) : '';
$patient_age = isset($_POST['patient_age']) ? safe($_POST['patient_age']) : '';
$patient_id = isset($_POST['patient_id']) ? safe($_POST['patient_id']) : '';
$session_date = isset($_POST['session_date']) ? safe($_POST['session_date']) : '';
$session_time = isset($_POST['session_time']) ? safe($_POST['session_time']) : '';
$session_notes = isset($_POST['session_notes']) ? safe($_POST['session_notes']) : '';

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: FORMATEO DE FECHA EN ESPAÑOL
// ────────────────────────────────────────────────────────────────────────────

/**
 * Convertir fecha de formato YYYY-MM-DD a formato legible en español
 * Ejemplo: 2025-12-09 → 9 de diciembre de 2025
 */
$display_date = $session_date;
if ($session_date) {
    $d = DateTime::createFromFormat('Y-m-d', $session_date);
    if ($d) {
        // Array de nombres de meses en español
        $meses = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];
        $mes = $meses[$d->format('n') - 1];
        $display_date = $d->format('d') . ' de ' . $mes . ' de ' . $d->format('Y');
    }
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 6: CONSTRUCCIÓN DEL HTML PARA EL PDF
// ────────────────────────────────────────────────────────────────────────────

/**
 * Template HTML con estilos CSS embebidos
 * 
 * ESTRUCTURA:
 * - Header con logo y título "Vision"
 * - Sección de información del paciente (nombre, edad, cédula)
 * - Sección de detalles de la sesión (fecha, hora)
 * - Sección de notas clínicas
 * - Footer con fecha de generación
 * 
 * ESTILOS:
 * - Fuente: DejaVu Sans (compatible con Dompdf)
 * - Colores: Paleta consistente con la marca (#13a4ec)
 * - Layout: Responsive con flexbox
 * - Espaciado profesional con márgenes y paddings
 */
$html = '<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        color: #0d171b;
        background: #fff;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 800px;
        margin: 20px auto;
        padding: 24px;
        border: 1px solid #e7eff3;
        border-radius: 12px;
    }
    .header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }
    .logo {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: #13a4ec;
    }
    .header-text h1 {
        font-size: 24px;
        margin: 0;
        color: #0d171b;
    }
    .header-text p {
        margin: 2px 0;
        color: #6b7280;
        font-size: 13px;
    }
    h2 {
        font-size: 16px;
        margin: 18px 0 10px;
        color: #0d171b;
        border-bottom: 2px solid #13a4ec;
        padding-bottom: 6px;
    }
    .section {
        margin: 16px 0;
    }
    .row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin: 8px 0;
    }
    .field {
        flex: 1;
        min-width: 200px;
    }
    .label {
        font-weight: bold;
        font-size: 13px;
        color: #334155;
        display: block;
        margin-bottom: 4px;
    }
    .value {
        font-size: 14px;
        color: #0d171b;
        padding: 8px;
        background: #f8fafc;
        border-radius: 4px;
    }
    .notes {
        white-space: pre-wrap;
        word-wrap: break-word;
        font-size: 13px;
        line-height: 1.5;
        padding: 12px;
        background: #f8fafc;
        border-radius: 4px;
        border-left: 3px solid #13a4ec;
    }
    .footer {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid #e7eff3;
        font-size: 11px;
        color: #9ca3af;
        text-align: right;
    }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo"></div>
    <div class="header-text">
      <h1>Vision</h1>
      <p>Informe de sesión clínica</p>
    </div>
  </div>

  <h2>Información del paciente</h2>
  <div class="section">
    <div class="row">
      <div class="field">
        <span class="label">Nombre:</span>
        <div class="value">' . ($patient_name ?: '-') . '</div>
      </div>
      <div class="field">
        <span class="label">Edad:</span>
        <div class="value">' . ($patient_age ? $patient_age . ' años' : '-') . '</div>
      </div>
    </div>
    <div class="row">
      <div class="field">
        <span class="label">Cédula de identidad:</span>
        <div class="value">' . ($patient_id ?: '-') . '</div>
      </div>
    </div>
  </div>

  <h2>Detalles de la sesión</h2>
  <div class="section">
    <div class="row">
      <div class="field">
        <span class="label">Fecha:</span>
        <div class="value">' . ($display_date ?: '-') . '</div>
      </div>
      <div class="field">
        <span class="label">Hora:</span>
        <div class="value">' . ($session_time ?: '-') . '</div>
      </div>
    </div>
  </div>

  <h2>Notas clínicas y observaciones</h2>
  <div class="section">
    <div class="notes">' . ($session_notes ?: '<em>No hay notas registradas.</em>') . '</div>
  </div>

  <div class="footer">Documento generado el ' . date('d/m/Y \\a \\l\\a\\s H:i') . '</div>
</div>
</body>
</html>';

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 7: CONFIGURACIÓN Y GENERACIÓN DEL PDF CON DOMPDF
// ────────────────────────────────────────────────────────────────────────────

/**
 * Bloque try-catch para manejo de errores en generación de PDF
 */
try {
    /**
     * Configurar opciones de Dompdf
     * 
     * - isRemoteEnabled: Permitir carga de recursos remotos (imágenes, fuentes)
     * - isHtml5ParserEnabled: Usar parser HTML5 moderno
     * - defaultFont: Fuente predeterminada (DejaVu Sans soporta UTF-8)
     */
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    
    /**
     * Crear instancia de Dompdf con opciones configuradas
     */
    $dompdf = new Dompdf($options);
    
    /**
     * Cargar HTML construido en la variable $html
     */
    $dompdf->loadHtml($html);
    
    /**
     * Configurar tamaño y orientación del papel
     * - Tamaño: A4 (estándar internacional)
     * - Orientación: portrait (vertical)
     */
    $dompdf->setPaper('A4', 'portrait');
    
    /**
     * Renderizar HTML a PDF
     * Convierte el HTML cargado en documento PDF
     */
    $dompdf->render();
    
    /**
     * Construir nombre de archivo
     * Formato: Informe_Sesion_[fecha].pdf
     * Ejemplo: Informe_Sesion_2025-12-09.pdf
     */
    $filename = 'Informe_Sesion_' . ($session_date ?: date('Ymd')) . '.pdf';
    
    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 8: ENVÍO DE HEADERS Y DESCARGA DEL PDF
    // ────────────────────────────────────────────────────────────────────────
    
    /**
     * Configurar headers HTTP para descarga de PDF
     * 
     * - Content-Type: Indica que el contenido es un PDF
     * - Content-Disposition: Fuerza descarga con nombre específico
     * - Cache-Control: Evita cacheo del documento
     * - Expires: Establece expiración inmediata
     */
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    /**
     * Enviar contenido del PDF al navegador
     * output() retorna el PDF como string binario
     */
    echo $dompdf->output();
    exit;
    
} catch (Exception $e) {
    /**
     * Manejo de errores
     * Si falla la generación del PDF, retornar HTTP 500 con mensaje
     */
    http_response_code(500);
    echo "Error al generar el PDF: " . $e->getMessage();
    exit;
}
?>
