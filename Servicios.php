<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: Servicios.php
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * DESCRIPCIÓN GENERAL:
 * Página estática que presenta el catálogo de servicios psicológicos ofrecidos
 * por Vision. Lista 5 tipos de servicios con descripciones detalladas.
 * 
 * FUNCIONALIDAD PRINCIPAL:
 * - Verificación de sesión para control de interfaz de navegación
 * - Renderizado responsivo de servicios en grid de dos columnas
 * - Controles condicionales: botón de reserva vs avatar de usuario
 * 
 * SERVICIOS OFRECIDOS:
 * 1. Terapia individual - Espacio seguro para explorar pensamientos y emociones
 * 2. Terapia familiar - Mejora comunicación y resuelve conflictos familiares
 * 3. Terapia grupal - Entorno de apoyo para compartir experiencias
 * 4. Evaluaciones y diagnósticos - Evaluaciones integrales de fortalezas/dificultades
 * 5. Consultoría y apoyo - Orientación a padres, educadores y profesionales
 * 
 * AUTENTICACIÓN:
 * - Verifica sesión PHP (session_start)
 * - Si usuario NO está logueado: Muestra botón "Reservar cita" → login.html
 * - Si usuario ESTÁ logueado: Muestra avatar con enlace a perfil.php
 * 
 * DEPENDENCIAS:
 * - auth.js: Manejo de sesión en cliente
 * - index.php, Nosotros.php, mensaje.php: Enlaces de navegación
 * - login.html: Página de autenticación (redirect=reserva.php)
 * - perfil.php: Panel de perfil del usuario autenticado
 * - Politica.php, Terminos.php: Enlaces legales en footer
 * 
 * DISEÑO:
 * - Framework: Tailwind CSS
 * - Tipografía: Lexend (400, 500, 700, 900)
 * - Iconografía: Material Symbols Outlined
 * - Temas: Light/Dark mode
 * - Color primario: #13a4ec (azul corporativo)
 */

session_start();
$Loggeado = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- Meta Tags: Codificación y configuración de documento -->
    <meta charset="utf-8" />
    <title>Servicios - Vision</title>
    
    <!-- Preconexión a Google Fonts para optimización de carga -->
    <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
    <!-- Font Lexend: Tipografía moderna para toda la página -->
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;700;900&amp;display=swap" rel="stylesheet" />
    <!-- Material Symbols: Iconografía para servicios -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    
    <!-- Tailwind CSS: Framework de utilidades para diseño responsive -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Configuración de tema Tailwind: Colores corporativos y tipografía -->
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#13a4ec",              // Azul corporativo
                        "background-light": "#f6f7f8",    // Fondo claro
                        "background-dark": "#101c22",     // Fondo oscuro
                    },
                    fontFamily: {
                        "display": ["Lexend"]              // Familia tipográfica principal
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "1rem",
                        "xl": "1.5rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
</head>

<!--
    ESTRUCTURA DE PÁGINA:
    - Página informativa sobre servicios psicológicos
    - Controles de autenticación condicionales en header
    - Grid responsivo con descripciones de servicios
    - auth.js para sincronización de sesión en cliente
-->

<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
    <div class="relative flex min-h-screen w-full flex-col">
        <!-- HEADER: Navegación y controles de usuario -->
        <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
            <div class="container mx-auto flex items-center justify-between whitespace-nowrap px-6 py-4">
                <!-- Logo y nombre de la marca -->
                <div class="flex items-center gap-3">
                    <a href="index.php">
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
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
                </div>
                
                <!-- Navegación principal: Visible en pantallas medias y superiores -->
                <nav class="hidden md:flex items-center gap-8">
                    <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
                        href="index.php"><button>Pagina Principal</button></a>
                    <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
                        href="Nosotros.php"><button>Sobre nosotros</button></a>
                    <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
                        href="mensaje.php"><button>Contacto</button></a>
                </nav>
                
                <!-- Controles de autenticación: Condicionales según sesión activa -->
                <div class="flex items-center gap-4">
                    <?php if (!$Loggeado): ?>
                        <!-- Usuario no autenticado: Botón de reserva con redirect a login -->
                        <a href="login.html?redirect=reserva.php"><button
                                class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">Reservar
                                cita</button></a>
                    <?php else: ?>
                        <!-- Usuario autenticado: Avatar con enlace a perfil -->
                        <a href="perfil.php">
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
                                style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <main class="flex-grow">
            <!-- Encabezado descriptivo de servicios -->
            <section class="py-16 sm:py-24">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-3xl mx-auto text-center">
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Nuestros servicios
                        </h2>
                        <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">Descubre los servicios psicológicos que ofrecemos para
                            apoyar la salud mental y el bienestar de jóvenes y familias.</p>
                    </div>
                </div>
            </section>
            
            <!-- Grid de servicios: Dos columnas en pantallas grandes -->
            <div class="bg-background-light dark:bg-background-dark pb-16 sm:pb-24">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
                        <!-- Columna izquierda: 3 servicios (Terapia individual, familiar, grupal) -->
                        <div class="space-y-10">
                            <!-- Servicio 1: Terapia individual -->
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                                    <span class="material-symbols-outlined">person</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Terapia individual</h3>
                                <p class="mt-2 text-base text-slate-600 dark:text-slate-400">Ofrecemos un espacio seguro y confidencial para
                                    explorar pensamientos y emociones. Aplicamos enfoques basados en la evidencia para tratar ansiedad,
                                    depresión, estrés y fomentar el crecimiento personal.</p>
                            </div>
                            
                            <!-- Servicio 2: Terapia familiar -->
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                                    <span class="material-symbols-outlined">group</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Terapia familiar</h3>
                                <p class="mt-2 text-base text-slate-600 dark:text-slate-400">Mejora la comunicación y ayuda a resolver
                                    conflictos dentro del núcleo familiar. Es útil para problemas de conducta, transiciones de vida y para
                                    fortalecer los vínculos familiares.</p>
                            </div>
                            
                            <!-- Servicio 3: Terapia grupal -->
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                                    <span class="material-symbols-outlined">groups</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Terapia grupal</h3>
                                <p class="mt-2 text-base text-slate-600 dark:text-slate-400">Conéctate con otros en un entorno seguro y de
                                    apoyo. Comparte experiencias y aprende estrategias para manejar la ansiedad social, la autoestima y otros
                                    desafíos.</p>
                            </div>
                        </div>
                        
                        <!-- Columna derecha: 2 servicios (Evaluaciones, Consultoría) -->
                        <div class="space-y-10">
                            <!-- Servicio 4: Evaluaciones y diagnósticos -->
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                                    <span class="material-symbols-outlined">plagiarism</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Evaluaciones y diagnósticos</h3>
                                <p class="mt-2 text-base text-slate-600 dark:text-slate-400">Realizamos evaluaciones integrales para
                                    comprender fortalezas y dificultades. Los resultados orientan la planificación del tratamiento y las
                                    recomendaciones de apoyo.</p>
                            </div>
                            
                            <!-- Servicio 5: Consultoría y apoyo -->
                            <div class="relative pl-12">
                                <div
                                    class="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-primary/20 text-primary">
                                    <span class="material-symbols-outlined">support_agent</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Consultoría y apoyo</h3>
                                <p class="mt-2 text-base text-slate-600 dark:text-slate-400">Brindamos orientación a padres, educadores y
                                    profesionales para abordar preocupaciones de salud mental y crear entornos de apoyo para jóvenes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- FOOTER: Información y enlaces legales -->
        <footer class="bg-background-light dark:bg-background-dark border-t border-slate-200 dark:border-slate-800">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <!-- Enlaces legales: Política y Términos -->
                    <div class="flex gap-4">
                        <a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary" href="Politica.php">Política de
                            privacidad</a>
                        <a class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary" href="Terminos.php">Términos y
                            condiciones</a>
                    </div>
                    <!-- Copyright y derechos reservados -->
                    <p class="text-sm text-slate-500 dark:text-slate-400">© 2025 Vision. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>

</body>

</html>

<!-- Script de autenticación: Manejo de sesión en cliente
     - Verifica estado de autenticación y actualiza interfaz dinámicamente
     - Sincroniza estado de sesión con UI (botones, avatares)
-->
<script src="auth.js"></script>