<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DOCTOR.PHP - PANEL DE CONTROL PARA PSICÓLOGOS
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Vista principal del panel de psicólogo que proporciona acceso a:
 * - Gestión de citas (visualización, filtrado, actualización de estado)
 * - Calendario de citas
 * - Notas clínicas
 * - Generación de PDFs
 * - Configuración de perfil
 * 
 * DEPENDENCIAS:
 * - db.php: Conexión a base de datos
 * - script_Doctor.js: Lógica de interfaz de usuario
 * - Tailwind CSS 3
 * - Material Symbols Icons
 * - Google Fonts (Inter)
 * 
 * SEGURIDAD:
 * - Validación de sesión activa
 * - Verificación de rol de psicólogo
 * - Protección contra acceso no autorizado
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y VALIDACIÓN DE SESIÓN
// ────────────────────────────────────────────────────────────────────────────

session_start();

/**
 * Validación de sesión activa
 * Si el usuario no está autenticado, redirige a login con URL de retorno
 */
if (empty($_SESSION['user_id'])) {
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

if (!isset($_SESSION['user_role']) || (int) $_SESSION['user_role'] !== 2) {
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

require_once 'db.php';

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: OBTENCIÓN DE DATOS DEL PSICÓLOGO
// ────────────────────────────────────────────────────────────────────────────

$Psicologo = null;
$idPsicologo = null;

/**
 * Consulta para obtener información completa del psicólogo
 * 
 * JOIN con tabla especialidades para obtener nombre de especialidad
 * Filtra por ID_Usuario de la sesión activa
 */
$query = "SELECT 
PS.ID_Psicologo, ES.Nombre_Especialidad, PS.Nombre_Psicologo, PS.Apellido_Psicologo 
FROM psicologos AS PS
 JOIN especialidades AS ES ON PS.ID_Especialidad = ES.ID_Especialidad
 WHERE ID_Usuario = ?";

if ($stmt = $mysqli->prepare($query)) {
    // Vincular parámetro: ID de usuario de la sesión
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si se encuentra el psicólogo, almacenar datos
    if ($row = $result->fetch_assoc()) {
        $Psicologo = $row;
        $idPsicologo = $row['ID_Psicologo'];
    }
    $stmt->close();
}

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: PREPARACIÓN DE DATOS PARA LA INTERFAZ
// ────────────────────────────────────────────────────────────────────────────

/**
 * Valores por defecto usando operador null coalescing
 * Previene errores si no se encuentran datos del psicólogo
 */
$nombreMostrar = $Psicologo['Nombre_Psicologo'] ?? 'Usuario';
$apellidoMostrar = $Psicologo['Apellido_Psicologo'] ?? '';
$nombreCompleto = trim($nombreMostrar . ' ' . $apellidoMostrar);
$Especialidad = $Psicologo['Nombre_Especialidad'] ?? '';

/**
 * Array de datos para JavaScript
 * Se serializa como JSON y se inyecta en el script
 * Permite acceso a información del psicólogo desde el frontend
 */
$jsData = [
    'nombre' => $nombreMostrar,
    'apellido' => $apellidoMostrar,
    'idPsicologo' => $idPsicologo,
    'Especialidad' => $Especialidad
];
?>

<!-- ════════════════════════════════════════════════════════════════════════════
SECCIÓN 4: ESTRUCTURA HTML Y METADATOS
════════════════════════════════════════════════════════════════════════════ -->

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Vision - Panel de Psicólogo</title>
    
    <!-- ──────────────────────────────────────────────────────────────────────
    FUENTES Y ESTILOS
    ────────────────────────────────────────────────────────────────────── -->
    <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
    <link as="style" href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900" onload="this.rel='stylesheet'" rel="stylesheet" />
    
    <!-- Tailwind CSS 3 con plugins de formularios y container queries -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- ──────────────────────────────────────────────────────────────────────
    CONFIGURACIÓN DE TAILWIND
    Modo oscuro por clase, colores personalizados, fuente display
    ────────────────────────────────────────────────────────────────────── -->
    <script id="tailwind-config">
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
    
    <!-- Material Symbols Icons para iconografía -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

    <!-- ──────────────────────────────────────────────────────────────────────
    ESTILOS PERSONALIZADOS
    Clases CSS para badges de estado de citas
    ────────────────────────────────────────────────────────────────────── -->
    <style>
      .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
      /* Estados de citas con colores distintivos */
      .estado-pendiente { background-color: #fbbf24; color: #78350f; }
      .estado-confirmada { background-color: #86efac; color: #15803d; }
      .estado-completada { background-color: #93c5fd; color: #1e3a8a; }
      .estado-cancelada { background-color: #fca5a5; color: #7f1d1d; }
    </style>

    <!-- ──────────────────────────────────────────────────────────────────────
    INYECCIÓN DE DATOS DE SESIÓN A JAVASCRIPT
    Objeto Window.Auth con información del psicólogo logueado
    ────────────────────────────────────────────────────────────────────── -->
    <script>
        // Datos del psicólogo desde PHP (JSON serializado)
        const usuarioSesion = <?php echo json_encode($jsData); ?>;
        
        /**
         * Objeto Auth global
         * Proporciona métodos para acceder a datos del usuario y cerrar sesión
         */
        window.Auth = {
            getUser: function () {
                return {
                    firstName: usuarioSesion.nombre,
                    lastName: usuarioSesion.apellido,
                    idPsicologo: usuarioSesion.idPsicologo,
                    especialidadPsi: usuarioSesion.especialidadPsi
                };
            },
            logout: function (options) {
                window.location.href = 'logout.php';
            }
        };
    </script>
</head>

<!-- ════════════════════════════════════════════════════════════════════════════
SECCIÓN 5: ESTRUCTURA DEL BODY Y HEADER
════════════════════════════════════════════════════════════════════════════ -->

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">

    <!-- ──────────────────────────────────────────────────────────────────────
    HEADER STICKY CON LOGO Y AVATAR
    ────────────────────────────────────────────────────────────────────── -->
    <header class="bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo Vision -->
                <div class="flex items-center gap-3">
                    <div class="text-primary h-8 w-8">
                        <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_6_319)">
                                <path d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z" fill="currentColor"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_6_319">
                                    <rect fill="white" height="48" width="48"></rect>
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
                </div>
                <!-- Avatar del usuario -->
                <div class="flex items-center">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 ml-4"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- ════════════════════════════════════════════════════════════════════════════
    SECCIÓN 6: LAYOUT PRINCIPAL CON SIDEBAR Y CONTENIDO
    ════════════════════════════════════════════════════════════════════════════ -->

    <!-- ════════════════════════════════════════════════════════════════════════════
    SECCIÓN 6: LAYOUT PRINCIPAL CON SIDEBAR Y CONTENIDO
    ════════════════════════════════════════════════════════════════════════════ -->

    <div class="flex min-h-screen">
        
        <!-- ──────────────────────────────────────────────────────────────────────
        SIDEBAR: NAVEGACIÓN PRINCIPAL
        Menú lateral con perfil, opciones de navegación y botón de logout
        ────────────────────────────────────────────────────────────────────── -->
        <aside class="w-64 bg-background-light dark:bg-background-dark border-r border-gray-200 dark:border-gray-700/50 flex flex-col p-6">
            
            <!-- Perfil del psicólogo con avatar y datos -->
            <div class="flex items-center gap-3 mb-10">
                <div id="perfilAvatar" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12" style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'></div>
                <div class="flex flex-col">
                    <h1 id="perfilName" class="text-gray-900 dark:text-white font-bold text-lg">
                        <?php echo htmlspecialchars($nombreCompleto); ?>
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Psicólogo</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                    </p>
                </div>
            </div>

            <!-- ──────────────────────────────────────────────────────────────────
            MENÚ DE NAVEGACIÓN
            Opciones principales del panel de psicólogo
            ────────────────────────────────────────────────────────────────── -->
            <nav class="flex flex-col gap-2">
                <!-- Citas (activo por defecto) -->
                <a id="CitasBtn" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-primary text-white font-medium cursor-pointer">
                    <span class="material-symbols-outlined">event_list</span>
                    <span>Citas</span>
                </a>
                
                <!-- Calendario -->
                <a id="CalendarioBtn" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer" href="calendarioCitas_View.php">
                    <span class="material-symbols-outlined">calendar_month</span>
                    <span>Calendario</span>
                </a>
                
                <!-- Notas clínicas -->
                <a id="NotasBtn" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer" href="VerNotas.php">
                    <span class="material-symbols-outlined">sticky_note_2</span>
                    <span>Notas</span>
                </a>
                
                <!-- Generar PDF -->
                <a id="PdfBtn" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer" href="ResumenPdf.php">
                    <span class="material-symbols-outlined">edit_document</span>
                    <span>Generar PDF</span>
                </a>
                
                <!-- Configuración -->
                <a id="Configuracion_Btn" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                    <span class="material-symbols-outlined">settings</span>
                    <span>Ajustes</span>
                </a>
            </nav>

            <!-- Botón de logout en la parte inferior -->
            <div class="mt-auto">
                <a id="logoutBtn" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Cerrar sesión</span>
                </a>
            </div>
        </aside>

        <!-- ════════════════════════════════════════════════════════════════════════════
        SECCIÓN 7: ÁREA DE CONTENIDO PRINCIPAL
        ════════════════════════════════════════════════════════════════════════════ -->

        <!-- ════════════════════════════════════════════════════════════════════════════
        SECCIÓN 7: ÁREA DE CONTENIDO PRINCIPAL
        ════════════════════════════════════════════════════════════════════════════ -->

        <main class="flex-1 p-8">
            <div class="max-w-6xl mx-auto">

                <!-- Alertas globales (éxito y error) -->
                <div id="alertaExitoGlobal" class="hidden mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-sm transition-all"></div>
                <div id="alertaGeneralGlobal" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow-sm transition-all"></div>

                <!-- ──────────────────────────────────────────────────────────────────
                PANEL DE BIENVENIDA (DASHBOARD)
                Mensaje inicial al cargar la página
                ────────────────────────────────────────────────────────────────── -->
                <div id="dashboardPanel" class="hidden">
                    <h1 id="welcomeTitle" class="text-4xl font-bold text-gray-900 dark:text-white mb-8">¡Bienvenido, <?php echo htmlspecialchars($nombreCompleto); ?>!</h1>
                    <p class="text-gray-600 dark:text-gray-400">Selecciona una opción del menú para comenzar.</p>
                </div>

                <!-- ══════════════════════════════════════════════════════════════════
                SECCIÓN 8: PANEL DE GESTIÓN DE CITAS
                ══════════════════════════════════════════════════════════════════ -->
                
                <div id="CitasPanel" class="bg-white dark:bg-background-dark/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700/50">
                    
                    <!-- Encabezado con título y botón de recarga -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Gestión de Citas</h2>
                        <button id="btnRecargarCitas" class="text-primary hover:text-primary/80" title="Recargar">
                            <span class="material-symbols-outlined">refresh</span>
                        </button>
                    </div>

                    <!-- ──────────────────────────────────────────────────────────────
                    FILTROS Y BÚSQUEDA
                    Permite filtrar citas por paciente, estado y ordenamiento
                    ────────────────────────────────────────────────────────────── -->
                    <div class="mb-6 flex flex-wrap items-center gap-4">
                        
                        <!-- Campo de búsqueda por paciente -->
                        <div class="flex-grow">
                            <label class="flex flex-col w-full min-w-[200px]">
                                <div class="flex w-full items-stretch rounded-lg h-10 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus-within:ring-2 focus-within:ring-primary">
                                    <div class="flex items-center justify-center pl-3 text-gray-500">
                                        <span class="material-symbols-outlined text-[20px]">search</span>
                                    </div>
                                    <input id="f_paciente" class="flex w-full border-none bg-transparent h-full px-3 text-sm focus:ring-0" placeholder="Buscar por paciente..." />
                                </div>
                            </label>
                        </div>
                        
                        <!-- Filtro por estado -->
                        <select id="f_estado" class="h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm px-3">
                            <option value="">Estado: Todos</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                        
                        <!-- Ordenamiento -->
                        <select id="f_ordenamiento" class="h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm px-3">
                            <option value="">Ordenar: Por Defecto</option>
                            <option value="fecha_asc">Fecha (Más Antiguas)</option>
                            <option value="fecha_desc">Fecha (Más Recientes)</option>
                            <option value="id_asc">ID (Menor a Mayor)</option>
                            <option value="id_desc">ID (Mayor a Menor)</option>
                        </select>
                    </div>

                    <!-- Loader animado (visible durante carga) -->
                    <div id="loaderCitas" class="hidden py-8 flex justify-center">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
                    </div>

                    <!-- ──────────────────────────────────────────────────────────────
                    TABLA DE CITAS
                    Muestra todas las citas con información detallada
                    ────────────────────────────────────────────────────────────── -->
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500">ID</th>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Paciente</th>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Contacto</th>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha</th>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Motivo</th>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</th>
                                        <th class="p-4 text-xs font-semibold uppercase tracking-wide text-gray-500 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <!-- Tbody poblado dinámicamente por JavaScript -->
                                <tbody id="tbody_citas" class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════════════
                SECCIÓN 9: PANEL DE CONFIGURACIÓN
                ══════════════════════════════════════════════════════════════════ -->

                <!-- ══════════════════════════════════════════════════════════════════
                SECCIÓN 9: PANEL DE CONFIGURACIÓN
                ══════════════════════════════════════════════════════════════════ -->
                
                <div id="Configuracion_Panel" class="hidden bg-white dark:bg-background-dark/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700/50">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Ajustes</h2>
                    <div class="space-y-6">
                        
                        <!-- Opción: Editar información personal -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Información Personal</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100 text-lg">Datos del Perfil</p>
                                <p class="text-xs text-gray-400">Nombre, edad, teléfono</p>
                            </div>
                            <button id="openProfileModalBtn" class="px-4 py-2 bg-primary/10 text-primary hover:bg-primary/20 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">edit</span> Editar
                            </button>
                        </div>

                        <!-- Opción: Correo electrónico (solo lectura) -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-500">Correo electrónico</p>
                                <p id="settingsEmail" class="font-semibold text-gray-900 dark:text-gray-100">
                                    <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════
    SECCIÓN 10: MODALES Y OVERLAYS
    ════════════════════════════════════════════════════════════════════════════ -->

    <!-- ──────────────────────────────────────────────────────────────────────
    MODAL DE CONFIRMACIÓN DE LOGOUT
    ────────────────────────────────────────────────────────────────────── -->
    <div id="confirmarOverlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-50"></div>
    <div id="confirmaModal" class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-all duration-300 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-[90%] max-w-md shadow-xl border border-gray-200 dark:border-gray-700 transform scale-95">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Confirmar cierre de sesión</h3>
            <div class="flex justify-end gap-3 mt-4">
                <button id="cancelarLogout" class="px-4 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Cancelar</button>
                <button id="confirmarLogout" class="px-4 py-2 rounded-md bg-red-600 text-white">Cerrar sesión</button>
            </div>
        </div>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────
    MODAL DE EDICIÓN DE PERFIL
    Formulario para actualizar datos personales del psicólogo
    ────────────────────────────────────────────────────────────────────── -->
    <div id="profileModalOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 z-50"></div>
    <div id="perfileModalContainer" class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-0 transition-all duration-300 z-[60] p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-2xl shadow-2xl border border-gray-200 dark:border-gray-700 transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]">
            
            <!-- Header del modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">person_edit</span>
                    Editar Información Personal
                </h2>
                <button id="closeProfileBtn" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors text-gray-500">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Contenido: Formulario de edición -->
            <div class="p-6 overflow-y-auto">
                <div id="modalAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Campo: Nombre -->
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label><input id="edit_nombre" type="text" class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary outline-none"></div>
                    <!-- Campo: Apellido -->
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apellido</label><input id="edit_apellido" type="text" class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary outline-none"></div>
                    <!-- Campo: Edad -->
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Edad</label><input id="edit_edad" type="number" class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary outline-none"></div>
                    <!-- Campo: Teléfono -->
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label><input id="edit_telefono" type="text" class="w-full px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary outline-none"></div>
                </div>
            </div>
            
            <!-- Footer: Botones de acción -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
                <button id="cancelProfileBtn" class="px-4 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium hover:bg-gray-50 dark:hover:bg-gray-600">Cancelar</button>
                <button id="saveProfileBtn" class="px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90 flex items-center gap-2"><span class="material-symbols-outlined text-sm">save</span> Guardar Cambios</button>
            </div>
        </div>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────
    MODAL DE CAMBIAR ESTADO DE CITA
    Permite actualizar el estado de una cita específica
    ────────────────────────────────────────────────────────────────────── -->
    <div id="modalCambiarEstadoOverlay" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[70] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-sm shadow-xl border border-gray-200 dark:border-gray-700 transform scale-100 transition-all">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Actualizar Estado de Cita</h3>
            <!-- Información de la cita (poblada dinámicamente) -->
            <p id="modalCitaInfo" class="text-sm text-gray-600 dark:text-gray-400 mb-4"></p>
            
            <!-- Selector de nuevo estado -->
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nuevo Estado</label>
            <select id="modalEstadoSelect" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary outline-none mb-6">
                <option value="Pendiente">Pendiente</option>
                <option value="Confirmada">Confirmada</option>
                <option value="Completada">Completada</option>
                <option value="Cancelada">Cancelada</option>
            </select>
            
            <!-- Botones de acción -->
            <div class="flex justify-end gap-2">
                <button id="btnCancelarEstado" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium">Cancelar</button>
                <button id="btnGuardarEstado" class="px-4 py-2 rounded-lg bg-primary text-white hover:opacity-90 text-sm font-medium">Guardar</button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════════
    SECCIÓN 11: SCRIPTS
    ════════════════════════════════════════════════════════════════════════════ -->
    
    <!-- script_Doctor.js: Lógica completa de la interfaz de usuario -->
    <script src="script_Doctor.js"></script>
</body>

</html>