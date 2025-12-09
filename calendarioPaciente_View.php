<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: calendarioPaciente_View.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Vista principal para que un paciente visualice su calendario de citas
 *   - Interfaz responsiva con Tailwind CSS y tema oscuro
 *   - Integración con FullCalendar 6.1.8 para visualización interactiva
 *   - Modal para mostrar detalles de citas al hacer clic
 *   - Carga dinámica de eventos mediante AJAX desde calendarioPaciente.php
 * 
 * Funcionalidad:
 *   - Valida sesión del usuario autenticado
 *   - Obtiene ID_Paciente desde tabla pacientes
 *   - Renderiza layout con header navegable
 *   - Muestra calendario con vistas: Mes, Semana, Día
 *   - Permite interacción con eventos (click para detalles)
 *   - Cierre de modal por botón, backdrop o ESC
 * 
 * Flujo de acceso:
 *   1. Usuario debe estar autenticado ($_SESSION['user_id'])
 *   2. Obtener paciente asociado al usuario
 *   3. Si no existe paciente, mostrar error y terminar
 *   4. Renderizar HTML con datos en atributo data-paciente
 *   5. calendarioPaciente.js carga eventos vía AJAX
 * 
 * Parámetros y variables:
 *   - $_SESSION['user_id']: ID del usuario autenticado
 *   - $idPaciente: ID del paciente (obtenido de BD)
 *   - data-paciente: Atributo HTML con ID del paciente para JavaScript
 * 
 * Dependencias:
 *   - db.php (conexión MySQL)
 *   - calendarioPaciente.js (lógica del calendario)
 *   - calendarioPaciente.php (endpoint API para citas)
 *   - Librerías externas: FullCalendar 6.1.8, jQuery 3.5.1, Tailwind CSS 3
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * SECCIÓN 1: VALIDACIÓN DE SESIÓN Y OBTENCIÓN DE DATOS
 * ────────────────────────────────────────────────────────────────────────────
 */

/**
 * Iniciar sesión PHP
 * Permite acceder a $_SESSION para autenticación
 */
session_start();

/**
 * Incluir archivo de configuración de base de datos
 * Proporciona conexión MySQLi en variable global $mysqli
 */
require_once 'db.php';

/**
 * VALIDACIÓN: Verificar que existe sesión activa
 * 
 * $_SESSION['user_id']:
 *   - Se establece al login exitoso en login.php
 *   - Contiene el ID del usuario autenticado
 * 
 * Si no existe:
 *   - Redirigir a login.html con parámetro redirect
 *   - El usuario vuelve a esta página después de autenticarse
 *   - Terminar ejecución
 */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?redirect=' . urldecode(basename($_SERVER['PHP_SELF'])));
    exit;
}

/**
 * OBTENER ID DEL USUARIO DESDE SESIÓN
 * Convertir a entero para seguridad de tipo
 */
$idUsuario = (int)$_SESSION['user_id'];

/**
 * INICIALIZAR VARIABLE DE ID PACIENTE
 * Se obtendrá de la base de datos
 */
$idPaciente = null;

/**
 * CONSULTA: Obtener ID del paciente asociado a este usuario
 * 
 * Lógica:
 *   - Tabla pacientes tiene columna ID_Usuario (foreign key)
 *   - Un usuario autenticado puede tener como máximo un registro paciente
 *   - Si es psicólogo/admin, no tendrá registro de paciente
 * 
 * Seguridad:
 *   - Prepared statement previene inyección SQL
 *   - Parámetro vinculado: idUsuario
 */
$query = "SELECT ID_Paciente FROM pacientes WHERE ID_Usuario = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$stmt->bind_result($idPaciente);
$stmt->fetch();
$stmt->close();

/**
 * VALIDACIÓN: Verificar que se encontró un paciente
 * 
 * Si $idPaciente es NULL:
 *   - Usuario no es un paciente registrado
 *   - Podría ser psicólogo o administrador
 *   - Mostrar error y terminar ejecución
 */
if (!$idPaciente) {
    die("No se encontró un paciente asociado a este usuario.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <!-- ────────────────────────────────────────────────────────────────────────
       SECCIÓN 2: METAETIQUETAS Y CONFIGURACIÓN DEL DOCUMENTO
       ──────────────────────────────────────────────────────────────────────── -->
  
  /**
   * CHARSET: Codificación de caracteres UTF-8
   * Necesario para soportar acentos y caracteres especiales españoles
   */
  <meta charset="utf-8" />
  
  /**
   * FUENTES GOOGLE FONTS
   * 
   * preconnect: Optimizar carga de fuentes (conectar al dominio gstatic)
   * Fuentes cargadas:
   *   - Inter: Tipografía sans-serif moderna (pesos: 400, 500, 700, 900)
   *   - Noto Sans: Tipografía alternativa (pesos: 400, 500, 700, 900)
   * 
   * onload: Evitar bloqueo del renderizado mientras carga la fuente
   */
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
  <link as="style" href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900" onload="this.rel='stylesheet'" rel="stylesheet" />
  
  /**
   * TAILWIND CSS
   * Framework CSS utility-first para estilos responsivos
   * 
   * cdn.tailwindcss.com:
   *   - Versión CDN (desarrollo)
   *   - plugins=forms: Incluye formularios estilizados
   *   - plugins=container-queries: Consultas de contenedor
   */
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  
  /**
   * TAILWIND CONFIG: Configuración personalizada
   * 
   * darkMode: "class"
   *   - Activa tema oscuro basado en clase .dark en <html>
   *   - Permite toggle de tema sin refrescar página
   * 
   * Colores personalizados:
   *   - primary: #13a4ec (azul corporativo)
   *   - background-light: #f6f7f8 (fondo claro)
   *   - background-dark: #101c22 (fondo oscuro)
   * 
   * Tipografía:
   *   - display: Inter (tipografía principal)
   */
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { 
            "primary": "#13a4ec", 
            "background-light": "#f6f7f8", 
            "background-dark": "#101c22" 
          },
          fontFamily: { 
            "display": ["Inter"] 
          },
        },
      },
    }
  </script>
  
  <!-- ────────────────────────────────────────────────────────────────────────
       SECCIÓN 3: LIBRERÍAS EXTERNAS (FullCalendar, jQuery)
       ──────────────────────────────────────────────────────────────────────── -->
  
  /**
   * FULLCALENDAR CSS
   * Estilos base para los componentes del calendario
   * Versión 6.1.8 (latest stable)
   */
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
  
  /**
   * FULLCALENDAR JavaScript
   * Librería principal que proporciona calendario interactivo
   * Características:
   *   - Múltiples vistas (mes, semana, día)
   *   - Drag & drop de eventos
   *   - Eventos dinámicos
   *   - Soporte para horarios
   */
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  
  /**
   * FULLCALENDAR LOCALES
   * Traducciones para idiomas (incluidas todas)
   * Permite configurar locale: 'es' en calendarioPaciente.js
   */
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
  
  /**
   * JQUERY
   * Librería JavaScript para AJAX y manipulación del DOM
   * Versión 3.5.1
   * Usada en calendarioPaciente.js para $.ajax
   */
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  
  /**
   * CALENDARIO DEL PACIENTE - Script lógica
   * defer: Cargar script después de renderizar HTML
   * Inicializa FullCalendar y maneja eventos
   */
  <script defer src="calendarioPaciente.js"></script>
  
  <!-- ────────────────────────────────────────────────────────────────────────
       SECCIÓN 4: ESTILOS PERSONALIZADOS PARA FULLCALENDAR
       ──────────────────────────────────────────────────────────────────────── -->
  <style>
  /**
   * PERSONALIZACIÓN DE BOTONES FULLCALENDAR
   * 
   * .fc .fc-button:
   *   - background-color: Color azul corporativo #13a4ec
   *   - color: Texto blanco
   *   - border: none (sin borde)
   *   - border-radius: Esquinas redondeadas (0.5rem = 8px)
   *   - padding: Espaciado interno
   *   - box-shadow: Sombra sutil
   *   - transition: Animación suave en hover
   */
  .fc .fc-button {
    background-color: #13a4ec !important;
    color: #fff !important;
    font-weight: bold;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, opacity 0.2s ease;
  }
  
  /**
   * HOVER STATE: Efecto al pasar mouse
   * transform: scale(1.05) - Aumentar tamaño 5%
   * opacity: 0.9 - Reducir opacidad ligeramente
   */
  .fc .fc-button:hover {
    transform: scale(1.05);
    opacity: 0.9;
  }
  
  /**
   * DISABLED STATE: Botones deshabilitados
   * opacity: 0.5 - Oscurecer botón
   * cursor: not-allowed - Indicar que no se puede clickear
   */
  .fc .fc-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  /**
   * FOCUS/ACTIVE STATE: Remover estilos de navegador
   * outline: none - Sin borde de enfoque
   * box-shadow: none - Sin sombra
   * border: none - Sin borde
   */
  .fc .fc-button:focus,
  .fc .fc-button:active {
    outline: none !important;
    box-shadow: none !important;
    border: none !important;
  }
  </style>
  
  /**
   * TÍTULO DE LA PÁGINA
   * Se muestra en la pestaña del navegador
   */
  <title>Calendario del Paciente</title>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-[#333] dark:text-[#ccc]">
  <!-- ────────────────────────────────────────────────────────────────────────
       SECCIÓN 5: ESTRUCTURA PRINCIPAL DEL LAYOUT
       ──────────────────────────────────────────────────────────────────────── -->
  
  /**
   * CONTENEDOR FLEX PRINCIPAL
   * flex: Activar flexbox
   * min-h-screen: Mínimo altura de pantalla (100vh)
   * flex-col: Disposición vertical (header, main, footer)
   */
  <div class="flex min-h-screen flex-col">
    
    <!-- ════════════════════════════════════════════════════════════════════════
         HEADER - Barra de navegación superior
         ════════════════════════════════════════════════════════════════════════ -->
    <header class="border-b border-primary/20 dark:border-primary/10">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
        
        /**
         * LOGO Y TÍTULO
         * Botón con SVG que regresa a la página principal (Index.php)
         */
        <div class="flex items-center gap-4">
         <a href="Index.php">
          <button>
            <div class="w-8 h-8 text-primary">
              <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                  fill="currentColor"></path>
              </svg>
            </div>
          </button>
        </a>
          /**
           * NOMBRE DE LA APLICACIÓN
           * Se muestra junto al logo
           */
          <h2 class="text-xl font-bold text-gray-800 dark:text-white">Vision</h2>
        </div>
        
        /**
         * NAVEGACIÓN PRINCIPAL
         * hidden md:flex: Ocultar en móvil, mostrar en desktop
         * Enlaces a páginas principales de la aplicación
         */
        <nav class="hidden items-center gap-8 md:flex">
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Index.php"><button>Pagina Principal</button></a>
            <a class="text-sm font-medium text-slate-700 hover:text-primary dark:text-slate-300 dark:hover:text-primary"
            href="Servicios.php"><button>Servicios</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Nosotros.php"><button>Sobre Nosotros</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="mensaje.php"><button>Contactacto</button></a>
        </nav>
        
        /**
         * PERFIL DE USUARIO
         * Icono de usuario que enlaza a perfil.php
         * Imagen de fondo (avatar)
         */
        <div class="flex items-center gap-4">
          <a href="perfil.php">
              <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
                style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
              </div>
            </a>
        </div>
      </div>
    </header>

    <!-- ════════════════════════════════════════════════════════════════════════
         MAIN - Contenido principal con calendario
         ════════════════════════════════════════════════════════════════════════ -->
    <main class="flex-1">
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        
        /**
         * SECCIÓN DE TÍTULO E INSTRUCCIONES
         */
        <div class="mb-8 text-center">
          <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">Calendario del Paciente</h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Haz clic en una cita para ver sus detalles.</p>
        </div>

        /**
         * CONTENEDOR DEL CALENDARIO
         * 
         * Atributos importantes:
         *   - id="calendar": Identificador para FullCalendar
         *   - data-paciente: Atributo personalizado con ID del paciente
         *     Se pasa a calendarioPaciente.js para obtener citas específicas
         *   - class: Estilos Tailwind para tema, padding, bordes, redondeo
         * 
         * El atributo htmlspecialchars():
         *   - Codifica caracteres especiales (&, <, >, ", ')
         *   - Previene inyección XSS
         *   - ENT_QUOTES: Codifica comillas simples y dobles
         *   - UTF-8: Codificación de caracteres
         */
        <div class="rounded-xl border border-primary/20 bg-background-light dark:bg-background-dark p-4 sm:p-6 lg:p-8">
          <div id="calendar"
               data-paciente="<?php echo htmlspecialchars($idPaciente, ENT_QUOTES, 'UTF-8'); ?>"
               class="bg-white dark:bg-[#0f172a] rounded-lg p-2"></div>
        </div>
      </div>
    </main>
  </div>

  <!-- ────────────────────────────────────────────────────────────────────────
       SECCIÓN 6: MODAL PARA DETALLES DE CITAS
       ──────────────────────────────────────────────────────────────────────── -->

  /**
   * MODAL CONTAINER
   * 
   * Estructura:
   *   - fixed: Posicionamiento fijo (no se mueve al scroll)
   *   - inset-0: Cubre toda la pantalla (top: 0, right: 0, bottom: 0, left: 0)
   *   - z-50: Muy alto z-index (encima de otros elementos)
   *   - hidden: Inicialmente oculto (clase agregada/removida por JavaScript)
   * 
   * El ID "modal" es referenciado en calendarioPaciente.js:
   *   - modalEl.classList.remove('hidden'): Mostrar
   *   - modalEl.classList.add('hidden'): Ocultar
   */
  <div id="modal" class="fixed inset-0 z-50 hidden">
    
    /**
     * BACKDROP OSCURO
     * 
     * absolute inset-0: Cubre toda el área del modal
     * bg-black/50: Negro semitransparente (50% opacidad)
     * 
     * Al hacer click aquí, calendarioPaciente.js cierra el modal
     */
    <div class="absolute inset-0 bg-black/50"></div>
    
    /**
     * CAJA MODAL
     * 
     * Posicionamiento:
     *   - relative: Para que z-index funcione correctamente
     *   - mx-auto: Centrar horizontalmente
     *   - mt-24: Margen superior (no pegada al top)
     *   - w-full max-w-md: Ancho responsivo, máximo 28rem
     * 
     * Estilos visuales:
     *   - rounded-lg: Esquinas redondeadas
     *   - bg-white dark:bg-[#0f172a]: Fondo blanco/oscuro
     *   - p-6: Padding interno
     *   - shadow-lg: Sombra pronunciada
     */
    <div class="relative mx-auto mt-24 w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-[#0f172a]">
      
      /**
       * HEADER DEL MODAL
       */
      <div class="flex items-center justify-between mb-3">
        /**
         * TÍTULO DE LA MODAL
         */
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Detalles de la Cita</h2>
        
        /**
         * BOTÓN CERRAR
         * 
         * id="closeModal": Referenciado en calendarioPaciente.js
         * Al hacer click:
         *   - Agregar clase 'hidden' para ocultarla
         *   - También se puede cerrar con ESC o backdrop
         */
        <button id="closeModal" class="rounded-md px-3 py-1 text-sm font-semibold text-white bg-primary hover:opacity-90">Cerrar</button>
      </div>
      
      /**
       * CONTENIDO DE LA MODAL
       * 
       * id="modal-content":
       *   - Se rellena dinámicamente desde calendarioPaciente.js
       *   - Contenido HTML generado al hacer click en un evento
       * 
       * Ejemplo de contenido (generado por JavaScript):
       *   <p><strong>📅 Fecha:</strong> 10/12/2024</p>
       *   <p><strong>⏰ Hora:</strong> 14:00</p>
       *   <p><strong>👤 Psicólogo:</strong> Dr. Juan García</p>
       *   <p><strong>📝 Motivo:</strong> Consulta inicial</p>
       *   <p><strong>📌 Estado:</strong> Confirmada</p>
       */
      <div id="modal-content" class="space-y-2 text-sm text-gray-800 dark:text-gray-200">
      </div>
    </div>
  </div>
</body>
</html>
