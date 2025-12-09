<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * FILE: Perfil.php
 * ════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: Panel principal de usuario paciente (dashboard personal)
 * Muestra resumen de citas, calendario, historial y configuración personal
 * FUNCIONALIDAD: Vista HTML + PHP backend para renderizar datos de sesión
 * DEPENDENCIAS: MySQLi db.php, JavaScript Usuario_Dashboard.js, jQuery, FullCalendar
 * AUTENTICACIÓN: Session-based (user_id required, redirect to login si no autenticado)
 * ROLES: Solo accesible para pacientes autenticados (Paciente = rol 1)
 * DISEÑO: Tailwind CSS responsive (sidebar + main content) con dark mode
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * SECCIONES HTML:
 * 1. Header: Logo, navegación principal, información de usuario
 * 2. Sidebar: Menú de navegación (Resumen, Calendario, Reserva, Historial, Ajustes)
 * 3. Dashboard Panel: Resumen de próxima cita con detalles (fecha, doctor, especialidad)
 * 4. Historial Panel: Citas próximas y citas pasadas
 * 5. Configuración Panel: Editar perfil, correo, opciones de ayuda
 * 6. Modales: Calendario (FullCalendar), detalles cita, editar perfil, logout
 * 
 * FLUJOS PRINCIPALES:
 * - Carga datos paciente de sesión y BD
 * - Renderiza nombre/email en múltiples ubicaciones
 * - Carga próxima cita vía fetch a proxima_cita.php
 * - Inicializa eventos en Usuario_Dashboard.js
 * 
 * SEGURIDAD:
 * - Verifica session user_id, redirige a login si no existe
 * - htmlspecialchars() en valores PHP para prevenir XSS
 * - Prepared statements en PHP para SQL injection prevention
 */

session_start();

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: VALIDACIÓN DE AUTENTICACIÓN
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Middleware de autenticación: Verificar que usuario esté autenticado
 * 
 * FLUJO:
 * 1. Verifica si $_SESSION['user_id'] existe y no está vacío
 * 2. Si no existe/vacío: redirige a login.html con parámetro redirect
 * 3. El redirect permite que después de login vuelva a esta página
 * 4. Si existe: continúa cargando el perfil
 * 
 * PROPÓSITO: Proteger acceso a panel personal solo para usuarios autenticados
 * Si usuario intenta acceder sin session, se redirige a login automáticamente
 */

if (empty($_SESSION['user_id'])) {
    // Redirigir a login con parámetro de retorno (Perfil.php)
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: CARGA DE DATOS DEL PACIENTE
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Obtener información del paciente desde base de datos
 * 
 * FLUJO:
 * 1. Incluye conexión centralizada (db.php con $mysqli object)
 * 2. Prepara SQL SELECT para obtener datos de tabla pacientes
 * 3. Filtra por ID_Usuario = $_SESSION['user_id'] (usuario actual)
 * 4. Ejecuta prepared statement (previene SQL injection)
 * 5. Extrae resultado: ID_Paciente, Nombre_Paciente, Apellido_Paciente
 * 6. Si existe registro: guarda en $paciente array
 * 7. Si no existe: $paciente permanece null
 * 
 * DATOS OBTENIDOS:
 * - ID_Paciente: ID único en tabla pacientes (usado en múltiples búsquedas)
 * - Nombre_Paciente: Nombre de pila
 * - Apellido_Paciente: Apellido del paciente
 * 
 * PROPÓSITO: Renderizar nombre completo en header, sidebar y bienvenida
 * También obtener ID_Paciente para búsquedas de citas
 */

require_once 'db.php';

// Variables para guardar datos del paciente
$paciente = null;
$idPaciente = null;

// SQL query para obtener datos básicos del paciente
$query = "SELECT ID_Paciente, Nombre_Paciente, Apellido_Paciente FROM pacientes WHERE ID_Usuario = ?";

if ($stmt = $mysqli->prepare($query)) {
    // Bind parameter: user_id de sesión (tipo int)
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch resultado como array asociativo
    if ($row = $result->fetch_assoc()) {
        $paciente = $row;
        $idPaciente = $row['ID_Paciente'];
    }
    $stmt->close();
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: PREPARACIÓN DE DATOS PARA RENDERIZADO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Procesar datos para mostrar en HTML
 * 
 * FLUJO:
 * 1. Obtiene nombre del paciente (fallback a 'Usuario' si no existe)
 * 2. Obtiene apellido (fallback a string vacío)
 * 3. Concatena nombre + apellido (trim para eliminar espacios extra)
 * 4. Prepara array $jsData con datos para pasar a JavaScript
 * 5. Se convierte a JSON para embeber en <script> tag
 * 
 * DATOS EN jsData:
 * - nombre: Nombre_Paciente
 * - apellido: Apellido_Paciente
 * - email: user_email de $_SESSION
 * - idPaciente: ID_Paciente para búsquedas
 * 
 * PROPÓSITO: Tener datos disponibles en JavaScript sin nuevas requests AJAX
 * El window.Auth object expone getUser() para acceder en Usuario_Dashboard.js
 */

// Obtener nombre mostrable (con fallback)
$nombreMostrar = $paciente['Nombre_Paciente'] ?? 'Usuario';
$apellidoMostrar = $paciente['Apellido_Paciente'] ?? '';
$nombreCompleto = trim($nombreMostrar . ' ' . $apellidoMostrar);

// Preparar datos para JavaScript (JSON embebido en <script>)
$jsData = [
    'nombre' => $nombreMostrar,
    'apellido' => $apellidoMostrar,
    'email' => $_SESSION['user_email'] ?? '',
    'idPaciente' => $idPaciente
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 6: METADATOS Y ENLACES DE RECURSOS (HEAD) -->
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!--
     * META TAGS:
     * - charset="utf-8": Codificación de caracteres (UTF-8 para español)
     * - viewport: Responsive design, escala inicial 1.0
     * - title: "Vision - Panel de Usuario" (mostrado en pestaña del navegador)
     * 
     * RECURSOS EXTERNOS:
     * 1. Google Fonts (Inter): Font familiar para tipografía
     * 2. Tailwind CSS: Framework CSS para estilos responsive (con plugins)
     * 3. Material Symbols: Iconografía de Google (iconos para botones/menús)
     * 4. FullCalendar 6.1.8: Librería de calendario interactivo
     * 
     * DISEÑO:
     * - Dark mode compatible (Tailwind darkMode: "class")
     * - Colores personalizados (primary=#13a4ec, fondos)
     * - Responsive: mobile-first con breakpoints (md:, lg:, etc.)
    -->
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Vision - Panel de Usuario</title>
    <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
    <link as="style" href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900"
        onload="this.rel='stylesheet'" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        /**
         * ────────────────────────────────────────────────────────────────────────────
         * SECCIÓN 6.1: CONFIGURACIÓN DE TAILWIND CSS
         * ────────────────────────────────────────────────────────────────────────────
         * 
         * tailwind.config: Personalización de colores, fonts, dark mode
         * 
         * COLORES PERSONALIZADOS:
         * - primary (#13a4ec): Azul para botones, links, acciones principales
         * - background-light (#f6f7f8): Fondo claro para light mode
         * - background-dark (#101c22): Fondo oscuro para dark mode
         * 
         * MODO OSCURO:
         * - darkMode: "class" usa clase CSS (no detecta sistema automático)
         * - Aplicar dark: al elemento body para activar dark mode
         * 
         * EJEMPLOS DE USO:
         * - bg-primary: Fondo azul
         * - dark:bg-background-dark: Fondo oscuro en dark mode
         * - hover:text-primary: Color primario al hover
         * - dark:text-gray-200: Texto gris claro en dark mode
         */
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#13a4ec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101c22",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                },
            },
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <!-- FullCalendar: Librería de calendario interactivo con eventos -->
    <!-- Versión 6.1.8 - incluye locales (español, etc.) -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    <!-- ────────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 6.2: ESTILOS PERSONALIZADOS DE FULLCALENDAR -->
    <!-- ────────────────────────────────────────────────────────────────────────────── -->
    <!--
     * Overrides de CSS para FullCalendar:
     * - .fc .fc-button: Color primario azul en botones de navegación
     * - .fc-event: Cambiar cursor a pointer para indicar eventos clickeables
    -->
    <style>
        /**
         * Botones del calendario (Mes anterior, Hoy, Mes siguiente, etc.)
         * 
         * PROPIEDADES:
         * - background-color: #13a4ec !important (azul primario)
         * - border: none (sin borde)
         * - color: white (texto blanco)
         * - text-transform: capitalize (primera letra mayúscula)
         * 
         * !important: Asegurar que se aplique sobre estilos por defecto
         */
        .fc .fc-button {
            background-color: #13a4ec !important;
            border: none;
            color: white;
            text-transform: capitalize;
        }

        /**
         * Eventos en el calendario
         * 
         * PROPIEDADES:
         * - cursor: pointer (indica que es clickeable)
         * 
         * PROPÓSITO: Cambiar apariencia del mouse al pasar sobre evento
         */
        .fc-event {
            cursor: pointer;
        }
    </style>

    <script>
        /**
         * ════════════════════════════════════════════════════════════════════════════════
         * SECCIÓN 4: CONFIGURACIÓN DE JAVASCRIPT GLOBAL
         * ════════════════════════════════════════════════════════════════════════════════
         * 
         * Configuración inicial de datos de sesión y métodos de autenticación
         * que serán usados por Usuario_Dashboard.js para operaciones de usuario
         */

        // ──────────────────────────────────────────────────────────────────────────────
        // SUBSECCIÓN 4.1: Datos de usuario embebidos en JSON
        // ──────────────────────────────────────────────────────────────────────────────
        /**
         * usuarioSesion: JSON con datos del usuario actual
         * 
         * Estructura:
         * {
         *   nombre: "Juan",
         *   apellido: "Pérez",
         *   email: "juan@example.com",
         *   idPaciente: 42
         * }
         * 
         * Origen: Embebido desde PHP via json_encode($jsData)
         * Seguridad: htmlspecialchars() en PHP previene XSS
         * Acceso: window.Auth.getUser() retorna estos datos
         */
        const usuarioSesion = <?php echo json_encode($jsData); ?>;
        
        /**
         * ──────────────────────────────────────────────────────────────────────────────
         * SUBSECCIÓN 4.2: Objeto window.Auth (API de autenticación)
         * ──────────────────────────────────────────────────────────────────────────────
         * 
         * Proporciona métodos globales para:
         * 1. Obtener información del usuario actual
         * 2. Cerrar sesión/logout
         * 
         * MÉTODOS:
         * - getUser(): Retorna objeto con firstName, lastName, email, idPaciente
         * - logout(options): Redirige a logout.php (destruye sesión)
         * 
         * PROPÓSITO: Interfaz consistente entre Perfil.php y Usuario_Dashboard.js
         * Usuario_Dashboard.js llama a window.Auth.getUser() para obtener datos
         */
        window.Auth = {
            /**
             * getUser(): Obtener datos del usuario actual
             * 
             * RETORNA:
             * {
             *   firstName: "Juan",
             *   lastName: "Pérez",
             *   email: "juan@example.com",
             *   idPaciente: 42
             * }
             * 
             * PROPÓSITO: Proveer datos de usuario a JavaScript de forma consistente
             * Usado por Usuario_Dashboard.js para nombres, búsquedas, etc.
             */
            getUser: function () {
                return {
                    firstName: usuarioSesion.nombre,
                    lastName: usuarioSesion.apellido,
                    email: usuarioSesion.email,
                    idPaciente: usuarioSesion.idPaciente
                };
            },
            
            /**
             * logout(options): Cerrar sesión del usuario
             * 
             * PARÁMETROS:
             * - options: objeto con opciones (no usado actualmente)
             * 
             * FLUJO:
             * 1. window.location.href = 'logout.php' redirige a logout
             * 2. logout.php destruye $_SESSION
             * 3. Usuario vuelve a estar no autenticado
             * 
             * PROPÓSITO: Proveer método de logout para Usuario_Dashboard.js
             * Llamado cuando usuario hace click en "Cerrar sesión"
             */
            logout: function (options) {
                window.location.href = 'logout.php';
            }
        };
    </script>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">

    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 7: HEADER - BARRA DE NAVEGACIÓN SUPERIOR -->
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!--
     * HEADER: Barra superior sticky con logo, navegación y avatar
     * 
     * ESTRUCTURA:
     * 1. Logo + Nombre (Vision)
     * 2. Navegación principal (centrada, hidden en mobile)
     *    - Página principal, Servicios, Sobre nosotros, Contacto
     * 3. Avatar del usuario (derecha)
     * 
     * CARACTERÍSTICAS:
     * - Sticky top-0: Permanece en la parte superior al scrollear
     * - Blur-sm: Efecto blur de fondo semi-transparente
     * - Dark mode: Colors adaptados (dark:bg-..., dark:border-...)
     * - Responsive: Logo visible siempre, nav escondida en <md (mobile)
     * 
     * ENLACES:
     * - Index.php: Página de inicio
     * - Servicios.php: Información de servicios
     * - Nosotros.php: Información sobre clínica
     * - mensaje.php: Formulario de contacto
    -->
    <header
        class="bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo + Nombre de marca -->
                <div class="flex items-center gap-3">
                    <!-- Logo SVG: Icon azul primario -->
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
                    <!-- Nombre de marca -->
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
                </div>
                
                <!-- Navegación principal (hidden en mobile <md) -->
                <nav class="hidden md:flex items-center gap-8">
                    <!-- Página principal -->
                    <a class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary transition-colors"
                        href="Index.php">Página principal</a>
                    <!-- Servicios -->
                    <a class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary transition-colors"
                        href="Servicios.php">Servicios</a>
                    <!-- Sobre nosotros -->
                    <a class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary transition-colors"
                        href="Nosotros.php">Sobre nosotros</a>
                    <!-- Contacto -->
                    <a class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary transition-colors"
                        href="mensaje.php">Contacto</a>
                </nav>
                
                <!-- Avatar del usuario (derecha) -->
                <div class="flex items-center">
                    <!-- Avatar placeholder (background-image: url(...)) -->
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 ml-4"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 8: LAYOUT PRINCIPAL - SIDEBAR + CONTENIDO -->
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!--
     * ESTRUCTURA:
     * - Flex layout: aside (sidebar) + main (contenido)
     * - Sidebar: w-64 ancho fijo, navegación, logout
     * - Main: flex-1 expande al resto del espacio
     * 
     * SIDEBAR (aside):
     * 1. Perfil del usuario: Avatar, nombre, rol, email
     * 2. Navegación: Resumen, Calendario, Reserva, Historial, Ajustes
     * 3. Logout: Botón cerrar sesión en mt-auto (abajo)
     * 
     * MAIN:
     * - Paneles dinámicos: Dashboard, Historial, Configuración
     * - Solo uno visible a la vez (hidden en los otros)
     * - Cargados/ocultados por Usuario_Dashboard.js
    -->
    <div class="flex min-h-screen">
        <!-- ────────────────────────────────────────────────────────────────────────── -->
        <!-- SECCIÓN 8.1: SIDEBAR - NAVEGACIÓN Y PERFIL DE USUARIO -->
        <!-- ────────────────────────────────────────────────────────────────────────── -->
        <!--
         * SIDEBAR FEATURES:
         * - w-64: Ancho de 256px
         * - Sticky en scroll (dark mode compatible)
         * - Perfil: Avatar, nombre, rol, email
         * - Nav: 5 opciones principales (btn con onclick en JS)
         * - Logout: Botón en la parte inferior (mt-auto)
         * 
         * IDs IMPORTANTES:
         * - overviewBtn: Mostrar dashboard/resumen
         * - CalendarioBtn: Mostrar modal calendario
         * - ReservarBtn: Link a página de reserva
         * - HistorialBtn: Mostrar historial de citas
         * - Configuracion_Btn: Mostrar panel de ajustes
         * - logoutBtn: Cerrar sesión (js: cerrarSesion())
        -->
        <aside
            class="w-64 bg-background-light dark:bg-background-dark border-r border-gray-200 dark:border-gray-700/50 flex flex-col p-6">
            <!-- Perfil del usuario (nombre, email, rol) -->
            <div class="flex items-center gap-3 mb-10">
                <!-- Avatar: imagen de usuario por defecto -->
                <div id="perfilAvatar" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12"
                    style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'></div>
                <div class="flex flex-col">
                    <!-- Nombre completo del paciente -->
                    <h1 id="perfilName" class="text-gray-900 dark:text-white font-bold text-lg">
                        <?php echo htmlspecialchars($nombreCompleto); ?>
                    </h1>
                    <!-- Rol: Siempre "Paciente" para usuarios en Perfil.php -->
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Paciente</p>
                    <!-- Email del usuario (de sesión) -->
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                    </p>
                </div>
            </div>

            <!-- Navegación principal del panel -->
            <nav class="flex flex-col gap-2">
                <!-- Resumen (Dashboard): Próxima cita y overview -->
                <a id="overviewBtn"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg bg-primary text-white font-medium cursor-pointer">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Resumen</span>
                </a>
                <!-- Calendario: Modal con FullCalendar -->
                <a id="CalendarioBtn"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <span>Calendario</span>
                </a>
                <!-- Reserva cita: Navega a View_Reserva.php -->
                <a id="ReservarBtn"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer"
                    href="View_Reserva.php">
                    <span class="material-symbols-outlined">calendar_add_on</span>
                    <span>Reserva cita</span>
                </a>
                <!-- Historial: Muestra citas pasadas y próximas -->
                <a id="HistorialBtn"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                    <span class="material-symbols-outlined">history</span>
                    <span>Historial</span>
                </a>
                <!-- Ajustes: Editar perfil, correo, ayuda -->
                <a id="Configuracion_Btn"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                    <span class="material-symbols-outlined">settings</span>
                    <span>Ajustes</span>
                </a>
            </nav>

            <!-- Logout (mt-auto: en la parte inferior) -->
            <div class="mt-auto">
                <a id="logoutBtn"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </aside>

        <!-- ────────────────────────────────────────────────────────────────────────── -->
        <!-- SECCIÓN 8.2: MAIN CONTENT - PANELES DINÁMICOS -->
        <!-- ────────────────────────────────────────────────────────────────────────── -->
        <!--
         * MAIN CONTENT AREA: flex-1 (expande al espacio disponible)
         * 
         * PANELES (solo uno visible a la vez):
         * 1. #dashboardPanel: Resumen con próxima cita (visible por defecto)
         * 2. #HistorialPanel: Citas próximas y pasadas (hidden)
         * 3. #Configuracion_Panel: Editar perfil y ajustes (hidden)
         * 
         * Los paneles se muestran/ocultan por Usuario_Dashboard.js
         * cuando hace click en los botones de navegación
         * 
         * DATOS DINÁMICOS:
         * - Próxima cita: Cargada via fetch a proxima_cita.php
         * - Citas historial: Cargadas por Usuario_Dashboard.js (AJAX)
         * - Edición de perfil: Modal con formulario
        -->
        <main class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">

                <!-- ────────────────────────────────────────────────────────────────── -->
                <!-- SECCIÓN 8.2.1: PANEL DASHBOARD (RESUMEN) -->
                <!-- ────────────────────────────────────────────────────────────────── -->
                <!--
                 * DASHBOARD PRINCIPAL:
                 * 1. Bienvenida personalizada: "¡Bienvenido, [nombre]!"
                 * 2. Card de próxima cita con 3 columnas:
                 *    - Fecha y Hora
                 *    - Profesional (doctor/psicólogo)
                 *    - Tipo de Cita (estado) y Duración
                 * 
                 * DATOS CARGADOS EN JAVASCRIPT (sección 5):
                 * - #fecha: Fecha formateada
                 * - #hora: Hora formateada
                 * - #doctor: Nombre doctor
                 * - #Espcialidad: Especialidad
                 * - #tipo_cita: Estado cita
                 * - #duracion: Minutos
                -->
                <div id="dashboardPanel">
                    <!-- Título de bienvenida personalizado -->
                    <h1 id="welcomeTitle" class="text-4xl font-bold text-gray-900 dark:text-white mb-8">¡Bienvenido,
                        <?php echo htmlspecialchars($nombreCompleto); ?>!
                    </h1>
                    <!-- Card: Próxima cita -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg p-8">
                        <div class="flex flex-col md:flex-row gap-8">
                            <!-- Texto descriptivo -->
                            <div>
                                <h2 class="text-slate-900 dark:text-white text-2xl font-bold">Próxima Cita</h2>
                                <p class="text-slate-500 dark:text-slate-400">Detalles de tu próxima consulta médica.
                                </p>
                            </div>

                            <!-- Grid: 3 columnas con info de cita -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                                <!-- Columna 1: Fecha y Hora -->
                                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                    <!-- Icon: Calendario -->
                                    <span class="material-symbols-outlined text-primary mt-1">calendar_month</span>
                                    <div>
                                        <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">Fecha y Hora
                                        </h3>
                                        <!-- #fecha: Cargada por JS (sección 5) -->
                                        <p id="fecha" class="text-slate-800 dark:text-slate-200 text-lg font-bold">
                                            Cargando...</p>
                                        <!-- #hora: Cargada por JS (sección 5) -->
                                        <p id="hora" class="text-slate-600 dark:text-slate-300"></p>
                                    </div>
                                </div>

                                <!-- Columna 2: Profesional -->
                                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                    <!-- Icon: Persona -->
                                    <span class="material-symbols-outlined text-primary mt-1">person</span>
                                    <div>
                                        <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">Profesional
                                        </h3>
                                        <!-- #doctor: Cargada por JS (sección 5) -->
                                        <p id="doctor" class="text-slate-800 dark:text-slate-200 text-lg font-bold"></p>
                                        <!-- #Espcialidad: Cargada por JS (sección 5) -->
                                        <p id="Espcialidad" class="text-slate-600 dark:text-slate-300"></p>
                                    </div>
                                </div>

                                <!-- Columna 3: Tipo de Cita y Duración -->
                                <div class="flex items-start gap-4 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                    <!-- Icon: Servicios médicos -->
                                    <span class="material-symbols-outlined text-primary mt-1">medical_services</span>
                                    <div>
                                        <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">Tipo de Cita
                                        </h3>
                                        <!-- #tipo_cita: Cargada por JS (sección 5) -->
                                        <p id="tipo_cita" class="text-slate-800 dark:text-slate-200 text-lg font-bold">
                                        </p>
                                        <!-- #duracion: Cargada por JS (sección 5) -->
                                        <p id="duracion" class="text-slate-600 dark:text-slate-300"></p>
                                    </div>
                                </div>

                            </div>

                            <!-- Divisor visual -->
                            <div class="md:w-px bg-slate-200 dark:bg-slate-800"></div>
                            
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────────── -->
                <!-- SECCIÓN 8.2.2: PANEL HISTORIAL (CITAS PASADAS/PRÓXIMAS) -->
                <!-- ────────────────────────────────────────────────────────────────── -->
                <!--
                 * PANEL DE HISTORIAL: Muestra todas las citas del usuario
                 * 
                 * SECCIONES:
                 * 1. Tus citas: Citas próximas (#appointmentsContainer)
                 * 2. Citas pasadas: Historial completado (#pastContainer)
                 * 
                 * DATOS CARGADOS POR JS (Usuario_Dashboard.js):
                 * - Citas próximas: fetch a Obtener_Cita.php
                 * - Citas pasadas: fetch a Obtener_Cita.php con filtro
                 * - Renderiza cards dinámicamente
                -->
                <div id="HistorialPanel"
                    class="hidden bg-white dark:bg-background-dark/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700/50">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Historial</h2>
                    <div class="space-y-6">
                        <!-- Sección: Citas próximas -->
                        <section>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Tus citas</h2>
                            <!-- Grid donde se renderizan citas próximas (JS) -->
                            <div id="appointmentsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
                        </section>
                        <!-- Sección: Citas pasadas -->
                        <section class="mt-12">
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Citas pasadas</h2>
                            <!-- Grid donde se renderizan citas pasadas (JS) -->
                            <div id="pastContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
                        </section>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────────────────────────── -->
                <!-- SECCIÓN 8.2.3: PANEL CONFIGURACIÓN (AJUSTES Y PERFIL) -->
                <!-- ────────────────────────────────────────────────────────────────── -->
                <!--
                 * PANEL DE CONFIGURACIÓN: Ajustes de usuario
                 * 
                 * OPCIONES:
                 * 1. Información Personal: Editar nombre, edad, teléfono (modal)
                 * 2. Correo Electrónico: Display-only (no editable directamente)
                 * 3. Ayuda: Link a mensaje.php (contacto)
                 * 
                 * MODALES ASOCIADOS:
                 * - #profileModalOverlay, #perfileModalContainer (editar perfil)
                 * - Maneja errores, validación, actualización
                -->
                <div id="Configuracion_Panel"
                    class="hidden bg-white dark:bg-background-dark/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700/50">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Ajustes</h2>
                    <div class="space-y-6">
                        <!-- Opción 1: Editar información personal -->
                        <div
                            class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Información Personal</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100 text-lg">Datos del Perfil</p>
                                <p class="text-xs text-gray-400">Nombre, edad, teléfono</p>
                            </div>
                            <!-- Botón abre modal de editar perfil (JS: openProfileModalBtn) -->
                            <button id="openProfileModalBtn"
                                class="px-4 py-2 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">edit</span> Editar
                            </button>
                        </div>

                        <!-- Opción 2: Correo electrónico (display-only) -->
                        <div
                            class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-500">Correo electrónico</p>
                                <!-- #settingsEmail: Email de sesión (no editable) -->
                                <p id="settingsEmail" class="font-semibold text-gray-900 dark:text-gray-100">
                                    <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Footer con link de ayuda -->
                    <div class="mt-8 border-t pt-4 flex justify-between items-center">
                        <div class="text-sm text-gray-500">¿Necesitas ayuda? <a href="mensaje.php"
                                class="text-primary">Contáctanos</a></div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 9: MODALES - CALENDARIO, DETALLES CITA, LOGOUT, EDITAR PERFIL -->
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!--
     * MODALES FLOTANTES: Componentes emergentes para diferentes funciones
     * 
     * MODALES INCLUIDOS:
     * 1. calendarModal: FullCalendar con citas del usuario
     * 2. calDetailModal: Detalles de cita seleccionada
     * 3. confirmaModal: Confirmación antes de logout
     * 4. apptOpcionesModal: Opciones de cita (editar, eliminar)
     * 5. profileModal: Editar información personal
     * 
     * ESTRUCTURA DE MODAL:
     * - Overlay: Fondo oscuro con blur (backdrop)
     * - Container: Div flotante con contenido
     * - Controlado por JavaScript (mostrar/ocultar con opacity y pointer-events)
     * 
     * ANIMACIONES:
     * - transition-opacity: Fade in/out del overlay
     * - transition-all scale-95: Scale + opacity para container
     * - duration-300: 300ms de duración
    -->

    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!-- SUBSECCIÓN 9.1: MODAL DE CALENDARIO -->
    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!--
     * MODAL CALENDARIO: Muestra todas las citas en calendario interactivo
     * 
     * COMPONENTES:
     * 1. Overlay: Fondo negro/blur (calendarModalOverlay)
     * 2. Container: Contenedor flotante (calendarModalContainer)
     * 3. Header: Título + botón cerrar
     * 4. Content: Div #calendar (se inicializa con FullCalendar en JS)
     * 
     * FUNCIONALIDAD:
     * - Click en evento: Muestra detalles en otro modal (calDetailModal)
     * - Navegación: Botones mes anterior/siguiente
     * - Locale: Español (es-ES)
     * 
     * CONTROL JS:
     * - #CalendarioBtn: Abre modal
     * - #closeCalendarBtn: Cierra modal
     * - Usuario_Dashboard.js: Inicializa FullCalendar
    -->
    <div id="calendarModalOverlay"
        class="fixed inset-0 bg-black/60 backdrop-blur-md opacity-0 pointer-events-none transition-opacity duration-300 z-40">
    </div>
    <div id="calendarModalContainer"
        class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-all duration-300 z-50 p-4">
        <div
            class="bg-white dark:bg-gray-900 w-full max-w-5xl h-[85vh] rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col transform scale-95 transition-transform duration-300">
            <!-- Header: Título + Close button -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">calendar_month</span>
                    Mi Calendario
                </h2>
                <!-- Botón cerrar modal (onclick en JS) -->
                <button id="closeCalendarBtn"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors">
                    <span class="material-symbols-outlined text-gray-500">close</span>
                </button>
            </div>
            <!-- Contenedor para FullCalendar -->
            <div class="flex-1 overflow-hidden p-4 bg-background-light dark:bg-background-dark/50">
                <!-- FullCalendar se renderiza aquí (JS: new FullCalendar.Calendar) -->
                <div id="calendar" class="h-full w-full bg-white dark:bg-gray-900 rounded-lg shadow-inner p-2"></div>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!-- SUBSECCIÓN 9.2: MODAL DETALLES DE CITA -->
    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!--
     * MODAL DETALLES DE CITA: Muestra información detallada de una cita
     * 
     * CONTENIDO DINÁMICO:
     * - #calDetailContent: Se rellena por JavaScript con detalles de cita
     * - Incluye: fecha, hora, doctor, especialidad, estado, duración
     * 
     * CONTROL JS:
     * - Usuario_Dashboard.js: Rellena #calDetailContent
     * - #calDetailCloseBtn: Cierra modal
     * - #calDetailBackdrop: Click en fondo cierra modal
    -->
    <div id="calDetailModal" class="fixed inset-0 flex items-center justify-center z-[60] hidden">
        <!-- Backdrop clickeable -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="calDetailBackdrop"></div>
        <!-- Card con detalles -->
        <div
            class="relative bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-sm shadow-2xl border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">Detalles
                de la Cita</h3>
            <!-- Contenido dinámico (rellena JS) -->
            <div id="calDetailContent" class="space-y-2 text-sm text-gray-700 dark:text-gray-300"></div>
            <!-- Footer: Botón cerrar -->
            <div class="mt-6 flex justify-end">
                <button id="calDetailCloseBtn"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!-- SUBSECCIÓN 9.3: MODAL CONFIRMACIÓN DE LOGOUT -->
    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!--
     * MODAL CONFIRMACIÓN LOGOUT: Confirmar antes de cerrar sesión
     * 
     * FLUJO:
     * 1. Usuario hace click en "Cerrar sesión" (#logoutBtn)
     * 2. Se muestra este modal
     * 3. Si confirma: redirige a logout.php
     * 4. Si cancela: cierra modal y vuelve a Perfil.php
     * 
     * CONTROL JS:
     * - #cancelarLogout: Cierra modal
     * - #confirmarLogout: Ejecuta logout (fetch a logout.php o redirect)
    -->
    <div id="confirmarOverlay"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    </div>
    <div id="confirmaModal"
        class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-all duration-300 z-50">
        <div
            class="bg-white dark:bg-gray-800 rounded-lg p-6 w-[90%] max-w-md shadow-xl border border-gray-200 dark:border-gray-700 transform scale-95">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Confirmar cierre de sesión</h3>
            <!-- Botones: Cancelar / Confirmar -->
            <div class="flex justify-end gap-3 mt-4">
                <button id="cancelarLogout" class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
                <button id="confirmarLogout" class="px-4 py-2 rounded-md bg-red-600 text-white">Cerrar sesión</button>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!-- SUBSECCIÓN 9.4: MODAL OPCIONES DE CITA -->
    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!--
     * MODAL OPCIONES DE CITA: Acciones disponibles para una cita
     * 
     * OPCIONES:
     * 1. Editar: onclick="window.Editar_cita()"
     * 2. Cancelar: onclick="window.Eliminar_cita()"
     * 3. Volver atrás: onclick="window.Cerrar_CitaModal()"
     * 
     * FUNCIONES GLOBALES (Usuario_Dashboard.js):
     * - window.Editar_cita()
     * - window.Eliminar_cita()
     * - window.Cerrar_CitaModal()
    -->
    <button id="appOpcionesOverlay" onclick="window.Cerrar_CitaModal()"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-40"></button>
    <div id="apptOpcionesModal" class="fixed inset-0 flex items-center justify-center pointer-events-none z-50 px-4">
        <div id="apptModalCard"
            class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-sm shadow-2xl border border-gray-200 dark:border-gray-700 transform scale-95 opacity-0 transition-all duration-300">
            <!-- Header -->
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Opciones de Cita</h3>
                <p id="modalCita_Info" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Selecciona una acción</p>
            </div>
            <!-- Botones de opciones -->
            <div class="space-y-3">
                <!-- Editar cita -->
                <button onclick="window.Editar_cita()"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg">
                    <span class="material-symbols-outlined text-sm">edit</span> Editar
                </button>
                <!-- Cancelar cita -->
                <button onclick="window.Eliminar_cita()"
                    class="w-full flex items-center justify-center gap-2 bg-white dark:bg-gray-800 border border-red-200 text-red-600 hover:bg-red-50 font-medium py-2.5 px-4 rounded-lg">
                    <span class="material-symbols-outlined text-sm">delete</span> Cancelar cita
                </button>
                <!-- Volver atrás -->
                <button onclick="window.Cerrar_CitaModal()"
                    class="w-full text-gray-500 hover:text-gray-700 text-sm font-medium py-2">Volver atrás</button>
            </div>
        </div>
    </div>

    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!-- SUBSECCIÓN 9.5: MODAL EDITAR PERFIL -->
    <!-- ────────────────────────────────────────────────────────────────────────── -->
    <!--
     * MODAL EDITAR PERFIL: Formulario para actualizar información personal
     * 
     * CAMPOS EDITABLES:
     * 1. Nombre (#edit_nombre)
     * 2. Apellido (#edit_apellido)
     * 3. Edad (#edit_edad)
     * 4. Teléfono (#edit_telefono)
     * 
     * VALIDACIÓN:
     * - Error messages: #err_nombre, #err_apellido, #err_edad, #err_telefono
     * - Alert general: #modalAlert (success/error)
     * 
     * BOTONES:
     * - #cancelProfileBtn: Cierra modal sin guardar
     * - #saveProfileBtn: Valida y guarda cambios (fetch a actualizarPerfil.php)
     * 
     * CONTROL JS:
     * - #openProfileModalBtn: Abre modal
     * - Usuario_Dashboard.js: Maneja eventos y AJAX
    -->
    <div id="profileModalOverlay"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-50">
    </div>
    <div id="perfileModalContainer"
        class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-all duration-300 z-[60] p-4">
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-2xl shadow-2xl border border-gray-200 dark:border-gray-700 transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]">

            <!-- Header: Título + Close button -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">person_edit</span>
                    Editar Información Personal
                </h2>
                <button id="closeProfileBtn"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors text-gray-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Contenido del formulario -->
            <div class="p-6 overflow-y-auto">
                <!-- Alert general (success/error) -->
                <div id="modalAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>

                <!-- Grid de inputs (2 columnas en desktop) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Campo: Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
                        <input id="edit_nombre" type="text"
                            class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <!-- Error message -->
                        <p id="err_nombre" class="text-red-500 text-xs mt-1"></p>
                    </div>

                    <!-- Campo: Apellido -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apellido</label>
                        <input id="edit_apellido" type="text"
                            class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <!-- Error message -->
                        <p id="err_apellido" class="text-red-500 text-xs mt-1"></p>
                    </div>

                    <!-- Campo: Edad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Edad</label>
                        <input id="edit_edad" type="number"
                            class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <!-- Error message -->
                        <p id="err_edad" class="text-red-500 text-xs mt-1"></p>
                    </div>

                    <!-- Campo: Teléfono -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
                        <input id="edit_telefono" type="text"
                            class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <!-- Error message -->
                        <p id="err_telefono" class="text-red-500 text-xs mt-1"></p>
                    </div>
                </div>
            </div>

            <!-- Footer: Botones (Cancelar / Guardar) -->
            <div
                class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
                <!-- Botón cancelar -->
                <button id="cancelProfileBtn"
                    class="px-4 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Cancelar
                </button>
                <!-- Botón guardar -->
                <button id="saveProfileBtn"
                    class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 transition-opacity flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span> Guardar Cambios
                </button>
            </div>
        </div>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 10: SCRIPTS - INICIALIZACIÓN Y FUNCIONES JAVASCRIPT -->
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!--
     * IMPORTACIÓN DE SCRIPTS:
     * 1. Usuario_Dashboard.js: Lógica principal del panel (eventos, AJAX, modales)
     * 2. Inline script: Carga próxima cita (fetch a proxima_cita.php)
     * 
     * PROPÓSITO:
     * - Usuario_Dashboard.js: Maneja interactividad del panel
     * - Inline script: Inicializa datos de próxima cita en el dashboard
    -->

    <!-- Script principal del panel (eventos, modales, calendario, AJAX) -->
    <script src="Usuario_Dashboard.js"></script>
    <!-- Script inline: Cargar próxima cita en dashboard -->
    <!-- Ver SECCIÓN 5 para documentación detallada de este script -->
    <script>
        /**
         * ════════════════════════════════════════════════════════════════════════════════
         * SECCIÓN 5: CARGA DE PRÓXIMA CITA (FETCH ASINCRÓNICO)
         * ════════════════════════════════════════════════════════════════════════════════
         * 
         * Este script ejecuta cuando el DOM está completamente cargado
         * Obtiene datos de la próxima cita del usuario vía AJAX fetch
         * Renderiza los detalles en los elementos HTML correspondientes
         */

        /**
         * DOMContentLoaded: Esperar a que HTML esté completamente cargado
         * 
         * FLUJO:
         * 1. Obtiene idPaciente de la variable PHP <?php echo $idPaciente; ?>
         * 2. Construye URL con parámetro: proxima_cita.php?idPaciente=42
         * 3. Hace fetch GET (AJAX asincrónico)
         * 4. Parsea respuesta JSON
         * 5. Si hay error/sin citas: muestra "Sin citas" en fecha
         * 6. Si hay cita: parsea fechas y renderiza en elementos HTML
         * 
         * PROPÓSITO: Cargar y mostrar próxima cita sin recargar página
         * Ejecuta cuando página carga, no requiere click del usuario
         */
        document.addEventListener("DOMContentLoaded", () => {
            // Obtener idPaciente desde PHP (embebido en HTML)
            // Este valor viene del SELECT a tabla pacientes en PHP
            const idPaciente = <?php echo $idPaciente; ?>;;

            /**
             * Fetch GET a proxima_cita.php
             * 
             * PARÁMETRO:
             * - idPaciente: ID del paciente para filtrar citas
             * 
             * RESPUESTA ESPERADA (JSON):
             * {
             *   "Fecha_Cita": "2024-12-15 14:30:00",
             *   "Nombre_Psicologo": "Carlos",
             *   "Apellido_Psicologo": "López",
             *   "Nombre_Especialidad": "Psicología Clínica",
             *   "Estado": "Confirmada",
             *   "Duracion": "60"
             * }
             * 
             * O error:
             * {
             *   "error": "No hay citas próximas",
             *   "message": "Sin citas programadas"
             * }
             */
            fetch(`proxima_cita.php?idPaciente=${idPaciente}`)
                // Convertir respuesta a JSON
                .then(res => res.json())
                .then(data => {
                    // ─ Validar si hay error o sin citas
                    if (data.error || data.message) {
                        document.getElementById("fecha").innerText = "Sin citas";
                        return;
                    }

                    // ─ Parsear fecha ISO string a Date object
                    // Formato: "2024-12-15 14:30:00" → JavaScript Date
                    const fecha = new Date(data.Fecha_Cita);

                    // ─ Formatear fecha a español (ej: "15 de diciembre de 2024")
                    const opciones = { day: "numeric", month: "long", year: "numeric" };
                    const fechaFormateada = fecha.toLocaleDateString("es-ES", opciones);
                    
                    // ─ Formatear hora a español (ej: "14:30")
                    const horaFormateada = fecha.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });

                    // ─ Renderizar en elementos HTML
                    // #fecha: Mostrar fecha formateada
                    document.getElementById("fecha").innerText = fechaFormateada;
                    // #hora: Mostrar hora formateada
                    document.getElementById("hora").innerText = horaFormateada;
                    // #doctor: Mostrar nombre completo del psicólogo
                    document.getElementById("doctor").innerText = `${data.Nombre_Psicologo} ${data.Apellido_Psicologo}`;
                    // #Espcialidad: Mostrar especialidad (ej: "Psicología Clínica")
                    document.getElementById("Espcialidad").innerText = data.Nombre_Especialidad;
                    // #tipo_cita: Mostrar estado de cita (ej: "Confirmada", "Pendiente")
                    document.getElementById("tipo_cita").innerText = data.Estado;
                    // #duracion: Mostrar duración en minutos
                    document.getElementById("duracion").innerText = data.Duracion + " minutos";
                });
        });
    </script>


</body>

</html>