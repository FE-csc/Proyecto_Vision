<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: calendarioCitas_View.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Vista principal que muestra el calendario de citas del psicólogo
 *   - Implementa interfaz responsiva con Tailwind CSS
 *   - Integra FullCalendar para visualización interactiva de eventos
 *   - Permite ver detalles de citas en un modal
 *   - Valida sesión y obtiene información del psicólogo autenticado
 * 
 * Características:
 *   - Soporte para tema claro/oscuro (dark mode)
 *   - Calendario interactivo con navegación mensual
 *   - Modal para detalles de citas
 *   - Integración con AJAX para datos en tiempo real
 *   - Diseño responsivo para dispositivos móviles
 *   - Localización en español de FullCalendar
 * 
 * Dependencias:
 *   - FullCalendar 6.1.8 (librería de calendario)
 *   - Tailwind CSS (estilos)
 *   - jQuery (AJAX)
 *   - calendarioCitas.js (lógica del cliente)
 *   - db.php (conexión a base de datos)
 * ════════════════════════════════════════════════════════════════════════════════
 */

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: INICIALIZACIÓN DE SESIÓN Y VALIDACIÓN
// ════════════════════════════════════════════════════════════════════════════════

// Iniciar sesión para acceder a datos del usuario autenticado
session_start();

// Incluir archivo de conexión a la base de datos
require_once 'db.php';

/**
 * Validar que el usuario tenga una sesión activa
 * Si no hay sesión, redirigir a login con parámetro de redirección
 * 
 * El parámetro 'redirect' permite volver a esta página después del login
 */
if (!isset($_SESSION['user_id'])) {
  header('Location: login.html?redirect=' . urldecode(basename($_SERVER['PHP_SELF'])));
  exit;
}

if (!isset($_SESSION['user_role']) || (int) $_SESSION['user_role'] !== 2) {
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: OBTENER DATOS DEL PSICÓLOGO
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Obtener ID del psicólogo desde la sesión del usuario
 * 
 * Proceso:
 *   1. Obtener ID_Usuario de $_SESSION
 *   2. Buscar el ID_Psicologo asociado en tabla psicologos
 *   3. Validar que el psicólogo existe
 */
$idUsuario = (int) $_SESSION['user_id'];
$idPsicologo = null;

/**
 * Consulta preparada para obtener ID_Psicologo
 * Usa prepared statement para prevenir SQL injection
 */
$query = "SELECT ID_Psicologo FROM psicologos WHERE ID_Usuario = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($idPsicologo);
$stmt->fetch();
$stmt->close();

/**
 * Validar que se encontró un psicólogo asociado
 * Si no existe, mostrar error y detener ejecución
 */
if (!$idPsicologo) {
  die("No se encontró un psicólogo asociado a este usuario.");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <!-- ══════════════════════════════════════════════════════════════════════════════
       SECCIÓN 3: HEAD - METAETIQUETAS Y CONFIGURACIÓN DEL DOCUMENTO
       ══════════════════════════════════════════════════════════════════════════════ -->
  
  <meta charset="utf-8" />
  
  <!-- Precargar fuentes de Google para mejor rendimiento -->
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
  
  <!-- Fuente Material Symbols Outlined (iconografía moderna) -->
  <link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
    rel="stylesheet" />
  
  <!-- Fuentes personalizadas: Inter y Noto Sans -->
  <link as="style"
    href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900"
    onload="this.rel='stylesheet'" rel="stylesheet" />
  
  <!-- ──────────────────────────────────────────────────────────────────────────────
       Tailwind CSS: Framework CSS utility-first
       plugins: forms (estilos para formularios), container-queries (responsive)
       ────────────────────────────────────────────────────────────────────────────── -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  
  <!-- Configuración personalizada de Tailwind -->
  <script id="tailwind-config">
    tailwind.config = {
      // Dark mode: basado en clase 'dark' en elemento raíz
      darkMode: "class",
      theme: {
        extend: {
          // Colores personalizados
          colors: { 
            "primary": "#13a4ec",                    // Azul principal de la aplicación
            "background-light": "#f6f7f8",           // Fondo claro
            "background-dark": "#101c22"             // Fondo oscuro
          },
          // Familia de fuente para títulos y texto
          fontFamily: { "display": ["Inter"] },
        },
      },
    }
  </script>
  
  <!-- ──────────────────────────────────────────────────────────────────────────────
       FullCalendar: Librería para visualización de calendario interactivo
       Versión: 6.1.8
       ────────────────────────────────────────────────────────────────────────────── -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  
  <!-- Traducciones de FullCalendar al español y otros idiomas -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
  
  <!-- ──────────────────────────────────────────────────────────────────────────────
       jQuery: Librería para simplificar AJAX y manipulación del DOM
       ────────────────────────────────────────────────────────────────────────────── -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  
  <!-- Script principal de la aplicación: calendarioCitas.js -->
  <script defer src="calendarioCitas.js"></script>
  
  <!-- ──────────────────────────────────────────────────────────────────────────────
       Estilos personalizados para FullCalendar
       ────────────────────────────────────────────────────────────────────────────── -->
  <style>
    /**
     * Estilos personalizados para los botones de FullCalendar
     * Cambia colores, forma y comportamiento del calendario
     */
    .fc .fc-button {
      background-color: #13a4ec !important;    /* Color azul principal */
      color: #fff !important;                   /* Texto blanco */
      font-weight: bold;                        /* Texto más grueso */
      border: none;                             /* Sin borde */
      border-radius: 0.5rem;                    /* Esquinas redondeadas */
      padding: 0.5rem 1rem;                     /* Espaciado interno */
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Sombra sutil */
      transition: transform 0.2s ease, opacity 0.2s ease; /* Transición suave */
    }

    /* Estado hover: aumentar tamaño y reducir opacidad */
    .fc .fc-button:hover {
      transform: scale(1.05);                   /* Aumentar 5% */
      opacity: 0.9;                             /* Reducir opacidad al 90% */
    }

    /* Botones deshabilitados */
    .fc .fc-button:disabled {
      opacity: 0.5;                             /* Reducir opacidad */
      cursor: not-allowed;                      /* Cursor no permitido */
    }

    /* Remover outline/focus por defecto de FullCalendar */
    .fc .fc-button:focus,
    .fc .fc-button:active {
      outline: none !important;                 /* Sin outline */
      box-shadow: none !important;              /* Sin sombra */
      border: none !important;                  /* Sin borde */
    }
  </style>
  
  <title>Calendario de Citas</title>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-[#333] dark:text-[#ccc]">
  <!-- ══════════════════════════════════════════════════════════════════════════════
       SECCIÓN 4: LAYOUT PRINCIPAL DEL DOCUMENTO
       ══════════════════════════════════════════════════════════════════════════════ -->
  <div class="flex min-h-screen flex-col">
    
    <!-- ────────────────────────────────────────────────────────────────────────────
         HEADER: Barra superior con navegación
         ──────────────────────────────────────────────────────────────────────────── -->
    <header
      class="bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

          <!-- Logo y nombre de la aplicación -->
          <div class="flex items-center gap-3">

            <!-- Botón de retroceso: regresa a Doctor.php -->
            <a id="perfilBtn"
              class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer"
              href="Doctor.php">
              <span class="material-symbols-outlined">arrow_back_ios_new</span>
            </a>

            <!-- Icono de la aplicación (logo) -->
            <div class="text-primary h-8 w-8">
              <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_6_319)">
                  <path
                    d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                    fill="currentColor"></path>
                </g>
                <defs>
                  <clipPath id="clip0_6_319">
                    <rect fill="white" height="48" width="48"></rect>
                  </clipPath>
                </defs>
              </svg>
            </div>
            
            <!-- Nombre de la aplicación -->
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
          </div>

        </div>
      </div>
    </header>
    
    <!-- ────────────────────────────────────────────────────────────────────────────
         MAIN: Contenido principal de la página
         ──────────────────────────────────────────────────────────────────────────── -->
    <main class="flex-1">
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Sección de título y descripción -->
        <div class="mb-8 text-center">
          <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
            Calendario de Citas
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Haz clic en una cita para ver sus detalles.
          </p>
        </div>

        <!-- Contenedor del calendario con bordes y estilos -->
        <div class="rounded-xl border border-primary/20 bg-background-light dark:bg-background-dark p-4 sm:p-6 lg:p-8">
          <!-- 
            Contenedor de FullCalendar
            Atributo data-doctor: ID del psicólogo (usado por calendarioCitas.js)
            Se pasa como atributo HTML para que JavaScript pueda acceder
          -->
          <div id="calendar" 
            data-doctor="<?php echo htmlspecialchars($idPsicologo, ENT_QUOTES, 'UTF-8'); ?>"
            class="bg-white dark:bg-[#0f172a] rounded-lg p-2">
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════════════
       SECCIÓN 5: MODAL DE DETALLES DE CITA
       ════════════════════════════════════════════════════════════════════════════════ -->
  
  <!-- 
    Modal: Ventana emergente que muestra detalles de una cita seleccionada
    
    Estructura:
      - Overlay semitransparente (background oscuro)
      - Contenedor modal con información de la cita
      - Botón cerrar para ocultar el modal
  -->
  <div id="modal" class="fixed inset-0 z-50 hidden">
    <!-- Overlay: Fondo semitransparente que cubre toda la pantalla -->
    <div class="absolute inset-0 bg-black/50"></div>
    
    <!-- Contenedor modal principal -->
    <div class="relative mx-auto mt-24 w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-[#0f172a]">
      <!-- Encabezado del modal con título y botón cerrar -->
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
          Detalles de la Cita
        </h2>
        <button id="closeModal"
          class="rounded-md px-3 py-1 text-sm font-semibold text-white bg-primary hover:opacity-90">
          Cerrar
        </button>
      </div>
      
      <!-- 
        Contenido del modal: se rellena dinámicamente desde calendarioCitas.js
        Contiene información como:
          - Nombre del paciente
          - Fecha y hora de la cita
          - Estado de la cita
          - Notas o observaciones
      -->
      <div id="modal-content" class="space-y-2 text-sm text-gray-800 dark:text-gray-200">
        <!-- Se rellena dinámicamente desde calendarioCitas.js -->
      </div>
    </div>
  </div>
</body>

</html>