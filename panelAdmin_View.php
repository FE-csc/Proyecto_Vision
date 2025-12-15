<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: panelAdmin_View.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: Vista del panel de administración de usuarios
// Renderiza interfaz para gestionar pacientes, psicólogos y administradores
// Incluye tabla dinámica, filtros, búsqueda y paginación (renderizados por JS)
// FUNCIONALIDAD: Renderizado de layout + estructura HTML + JavaScript initialization
// DEPENDENCIAS: panelAdmin.js (lógica AJAX, renderizado dinámico), jQuery (AJAX calls)
// ROLES AUTORIZADOS: Solo Administrador (Rol = 3, verificado por backend)
// MÉTODOS: GET (mostrar página), AJAX vía panelAdmin.js para datos
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y VALIDACIÓN DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
session_start();

// CONTROL DE ACCESO: Verificar autenticación
// Si usuario NO está autenticado, redirigir a login con parámetro de retorno
if (empty($_SESSION['user_id'])) {
    // basename($_SERVER['PHP_SELF']) = nombre del archivo actual (panelAdmin_View.php)
    // urlencode() = codificar caracteres especiales en URL
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

if (!isset($_SESSION['user_role']) || (int) $_SESSION['user_role'] !== 3) {
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 2: METADATOS Y CONFIGURACIÓN DEL DOCUMENTO
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- UTF-8: Soporte para caracteres especiales (acentos, ñ, etc.) -->
    <meta charset="utf-8" />
    <!-- Viewport: Responsive design y escalado automático en dispositivos móviles -->
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <!-- Título de la página en pestaña del navegador -->
    <title>Panel de Administración</title>

    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 3: FONTS Y TIPOGRAFÍA
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- Preconectar a Google Fonts para mejorar velocidad de carga -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <!-- Cargar font family Inter en pesos 400-900 para tipografía consistente -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;600;700;900"
    />

    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 4: TAILWIND CSS Y CONFIGURACIÓN
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- Tailwind CSS CDN con plugins para formularios y media queries -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Configuración personalizada de Tailwind -->
    <script id="tailwind-config">
        tailwind.config = {
            /* darkMode: "class" - Permitir tema oscuro usando clase en <body> */
            darkMode: "class",
            theme: {
                extend: {
                    /* Colores personalizados para la marca Vision */
                    colors: {
                        primary: "#13a4ec",              /* Azul principal (botones, acentos) */
                        "background-light": "#f6f7f8",  /* Fondo claro (blanco grisáceo) */
                        "background-dark": "#101c22",   /* Fondo oscuro (gris muy oscuro) */
                    },
                    /* Font family personalizada: Inter (sans-serif moderna) */
                    fontFamily: {
                        display: ["Inter", "system-ui", "sans-serif"],
                    },
                    /* Border radius personalizado para elementos redondeados */
                    borderRadius: {
                        DEFAULT: "0.5rem",   /* 8px - Bordes estándar */
                        lg: "1rem",           /* 16px - Bordes más redondeados */
                        xl: "1.5rem",         /* 24px - Bordes muy redondeados */
                        full: "9999px"        /* Circular - Para botones redondeados */
                    },
                },
            },
        };
    </script>

    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 5: ICONOS MATERIAL SYMBOLS
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- Cargar iconos Material Symbols de Google -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
    />
    <!-- Estilos personalizados para iconos Material Symbols -->
    <style>
        /* Material Symbols Outlined: Configuración de variables tipográficas */
        .material-symbols-outlined {
            /* FILL: 0 (contorno), wght: 400 (peso normal), GRAD: 0, opsz: 24 (tamaño 24px) */
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-100">
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 6: LAYOUT PRINCIPAL (FLEX CONTAINER)
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- relative: Contenedor posicionado para elementos absolutos -->
    <!-- flex min-h-screen: Flexbox con altura mínima de pantalla completa -->
    <div class="relative flex min-h-screen w-full">
        <!-- main.flex-1: Main ocupa todo el espacio disponible después de navbar -->
        <main class="flex-1 w-full">

            <!-- ──────────────────────────────────────────────────────────────────────────
                 SECCIÓN 7: HEADER (BARRA DE NAVEGACIÓN SUPERIOR)
                 ────────────────────────────────────────────────────────────────────────── -->
            <!-- sticky top-0 z-20: Fija en la parte superior, encima de contenido -->
            <!-- border-b: Borde inferior para separación visual -->
            <!-- bg-background-light/80: Fondo semi-transparente (80% de opacidad) -->
            <!-- backdrop-blur-sm: Efecto blur detrás del header (glassmorphism) -->
            <header class="sticky top-0 z-20 border-b bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                    <!-- ──────────────────────────────────────────────────────────────────────────
                         SECCIÓN 7A: LOGO Y BRANDING
                         ────────────────────────────────────────────────────────────────────────── -->
                    <!-- Logo + título de la marca -->
                    <div class="flex items-center gap-3">
                        <!-- Logo SVG: Círculo con símbolo de Vision en azul primario -->
                        <div class="h-9 w-9 text-primary">
                            <svg
                                fill="none"
                                viewBox="0 0 48 48"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-full w-full"
                            >
                                <g clip-path="url(#clip0_6_319)">
                                    <path
                                        d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                                        fill="currentColor"
                                    ></path>
                                </g>
                                <defs>
                                    <clipPath id="clip0_6_319">
                                        <rect width="48" height="48" fill="white"></rect>
                                    </clipPath>
                                </defs>
                            </svg>
                        </div>
                        <!-- Nombre de la clínica y subtítulo -->
                        <div class="flex flex-col">
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
                            <!-- Subtítulo: Identifica esta página como panel de admin -->
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                             Panel de administración
                            </span>
                        </div>
                    </div>

                    <!-- ──────────────────────────────────────────────────────────────────────────
                         SECCIÓN 7B: AVATAR DEL ADMINISTRADOR
                         ────────────────────────────────────────────────────────────────────────── -->
                    <!-- Avatar / usuario autenticado -->
                    <div class="flex items-center gap-3">
                        <!-- Avatar del administrador autenticado -->
                        <!-- size-10: Ancho y alto de 40px (10 * 4px) -->
                        <!-- rounded-full: Circular (border-radius: 9999px) -->
                        <!-- bg-cover bg-center: Imagen ajustada y centrada -->
                        <div
                            class="size-10 rounded-full bg-cover bg-center bg-no-repeat border border-white/50 shadow-sm"
                            aria-label="Avatar administrador"
                            style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'
                        ></div>
                    </div>
                </div>
            </header>

            <!-- ──────────────────────────────────────────────────────────────────────────
                 SECCIÓN 8: CONTENIDO PRINCIPAL (ADMINISTRACIÓN DE USUARIOS)
                 ────────────────────────────────────────────────────────────────────────── -->
            <!-- section: Sección semántica para contenido principal -->
            <!-- mx-auto max-w-6xl: Ancho máximo centrado (1152px) -->
            <!-- space-y-6: Espaciado vertical de 24px entre elementos -->
            <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 space-y-6">
                <!-- ──────────────────────────────────────────────────────────────────────────
                     SECCIÓN 8A: TÍTULO Y DESCRIPCIÓN
                     ────────────────────────────────────────────────────────────────────────── -->
                <!-- Título sección -->
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold tracking-tight">Administración de usuarios</h2>
                </div>

                <!-- ──────────────────────────────────────────────────────────────────────────
                     SECCIÓN 8B: FILTROS Y BÚSQUEDA
                     ────────────────────────────────────────────────────────────────────────── -->
                <!-- Filtros: Búsqueda por nombre, filtro por rol, botón logout -->
                <!-- flex flex-wrap items-center gap-4: Layout flexible con wrapping en móvil -->
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <!-- CAMPO DE BÚSQUEDA -->
                    <!-- flex-1 min-w-[260px]: Ocupa espacio disponible, mínimo 260px -->
                    <div class="flex-1 min-w-[260px]">
                        <label class="flex flex-col w-full">
                            <!-- Etiqueta del campo -->
                            <span class="mb-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                                Buscar
                            </span>
                            <!-- Contenedor del input con icono -->
                            <!-- focus-within: Aplica estilos cuando el input dentro está enfocado -->
                            <div
                                class="flex w-full items-stretch rounded-lg h-11 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 focus-within:ring-2 focus-within:ring-primary focus-within:border-transparent shadow-sm"
                            >
                                <!-- Icono de búsqueda (Material Symbols) -->
                                <div class="flex items-center justify-center pl-3 pr-1 text-gray-500">
                                    <span class="material-symbols-outlined text-xl">search</span>
                                </div>
                                <!-- Input de búsqueda por nombre -->
                                <!-- id="f_nombre": Identificador para acceso por JavaScript -->
                                <input
                                    id="f_nombre"
                                    type="text"
                                    class="form-input flex w-full border-none bg-transparent h-full px-3 text-sm focus:ring-0 focus:outline-none"
                                    placeholder="Buscar por nombre"
                                />
                            </div>
                        </label>
                    </div>

                    <!-- FILTRO POR ROL -->
                    <!-- w-full sm:w-auto: Ancho completo en móvil, auto en desktop -->
                    <div class="w-full sm:w-auto">
                        <label class="flex flex-col">
                            <!-- Etiqueta del select -->
                            <span class="mb-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                                Tipo de usuario
                            </span>
                            <!-- Select para filtrar por rol -->
                            <!-- id="f_rol": Identificador para acceso por JavaScript -->
                            <select
                                id="f_rol"
                                class="h-11 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                                <!-- Opciones de rol: 1=Paciente, 2=Doctor, 3=Admin -->
                                <option value="">Todos</option>
                                <option value="1">Paciente</option>
                                <option value="2">Doctor</option>
                                <option value="3">Administrador</option>
                            </select>
                        </label>
                    </div>

                    <!-- BOTÓN LOGOUT -->
                    <!-- id="btnLogout": Identificador para evento click en JavaScript -->
                    <div>
                        <a id="btnLogout" class="flex items-center gap-3 px-4 py-5 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer">
                            <!-- Icono de logout (Material Symbols) -->
                            <span class="material-symbols-outlined">logout</span>
                            <!-- Etiqueta del botón -->
                            <span>Cerrar sesión</span>
                        </a>
                    </div>
                </div>

                <!-- ──────────────────────────────────────────────────────────────────────────
                     SECCIÓN 8C: TABLA DE USUARIOS
                     ────────────────────────────────────────────────────────────────────────── -->
                <!-- TABLA -->
                <div class="mt-6">
                    <!-- Contenedor de tabla con bordes y sombra -->
                    <!-- overflow-hidden: Clip contenido para no desbordarse de los bordes redondeados -->
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <!-- Wrapper para scroll horizontal en pantallas pequeñas -->
                        <!-- overflow-x-auto: Permite scroll horizontal si la tabla es muy ancha -->
                        <div class="overflow-x-auto">
                            <!-- Tabla HTML para mostrar usuarios -->
                            <table class="w-full min-w-[1024px] text-left text-sm">
                                <!-- ENCABEZADOS DE TABLA -->
                                <!-- bg-gray-100: Fondo gris para distinguir encabezados -->
                                <thead class="bg-gray-100 dark:bg-slate-800/60">
                                    <tr class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                        <!-- Columnas: ID Usuario, ID Paciente/Psicólogo, Nombre, Apellido, Correo, Teléfono, Rol, Acciones -->
                                        <th class="p-4">ID Usuario</th>
                                        <th class="p-4">ID Paciente / ID Psicólogo</th>
                                        <th class="p-4">Nombre</th>
                                        <th class="p-4">Apellido</th>
                                        <th class="p-4">Correo</th>
                                        <th class="p-4">Teléfono</th>
                                        <th class="p-4">Rol</th>
                                        <th class="p-4 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <!-- CUERPO DE TABLA (DINÁMICO VÍA JAVASCRIPT) -->
                                <!-- id="tbody_usuarios": Identificador para renderizar filas vía panelAdmin.js -->
                                <!-- Se renderiza vía AJAX con datos del servidor -->
                                <tbody id="tbody_usuarios" class="divide-y divide-gray-100 dark:divide-slate-800">
                                    <!-- Se renderiza vía panelAdmin.js -->
                                </tbody>
                            </table>
                        </div>

                        <!-- ──────────────────────────────────────────────────────────────────────────
                             SECCIÓN 8D: PAGINACIÓN
                             ────────────────────────────────────────────────────────────────────────── -->
                        <!-- Footer de tabla con paginación -->
                        <!-- border-t: Borde superior para separación -->
                        <!-- flex items-center justify-between: Layout con elementos distribuidos -->
                        <div class="flex items-center justify-between gap-4 px-4 py-3 border-t border-gray-100 dark:border-slate-800">
                            <!-- Texto de información de resultados -->
                            <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                Mostrando resultados
                            </span>
                            <!-- Controles de paginación -->
                            <div class="flex items-center gap-2">
                                <!-- Botón Anterior -->
                                <button
                                    type="button"
                                    class="flex h-8 items-center justify-center rounded-md border border-gray-200 dark:border-slate-700 px-3 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-primary/10 transition-colors"
                                >
                                    Anterior
                                </button>
                                <!-- Botón Siguiente -->
                                <button
                                    type="button"
                                    class="flex h-8 items-center justify-center rounded-md border border-gray-200 dark:border-slate-700 px-3 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-primary/10 transition-colors"
                                >
                                    Siguiente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════════════
         SECCIÓN 9: SCRIPTS EXTERNOS
         ══════════════════════════════════════════════════════════════════════════════ -->
    <!-- jQuery: Librería AJAX para requests HTTP -->
    <!-- Versión: 3.5.1 (compatible con navegadores modernos) -->
    <!-- Usado por: panelAdmin.js para AJAX calls -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- panelAdmin.js: Script para lógica del panel de administración -->
    <!-- Funcionalidades: Renderizado dinámico de tabla, filtros, búsqueda, paginación, logout -->
    <script src="panelAdmin.js"></script>
</body>
</html>
