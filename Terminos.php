<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: Terminos.php
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * DESCRIPCIÓN GENERAL:
 * Página estática con los términos y condiciones legales del servicio Vision.
 * Presenta información sobre aceptación de términos, servicios, políticas de 
 * cancelación, confidencialidad, pago y conducta del usuario.
 * 
 * FUNCIONALIDAD PRINCIPAL:
 * - Verificación de sesión para control de interfaz de navegación
 * - Renderizado de contenido legal en formato responsive
 * - Control condicional de botón de login/perfil según estado de sesión
 * 
 * FLUJO DE NEGOCIO:
 * 1. Inicia sesión y verifica estado de autenticación (sesión activa)
 * 2. Carga estructura HTML con Tailwind CSS
 * 3. Renderia header con navegación y controles condicionales de usuario
 * 4. Muestra contenido legal en 9 secciones temáticas
 * 5. Pie de página con enlace a política de privacidad
 * 6. Ejecuta auth.js para manejo dinámico de sesión en UI
 * 
 * AUTENTICACIÓN Y SEGURIDAD:
 * - Verifica sesión PHP con session_start()
 * - Variable $Loggeado controla visibilidad de interfaz
 * - Si hay sesión activa: muestra avatar del usuario (perfil.php)
 * - Si no hay sesión: muestra botón de inicio de sesión
 * 
 * DEPENDENCIAS:
 * - auth.js: Manejo de sesión en cliente (verificación adicional)
 * - index.php: Página principal
 * - Servicios.php: Catálogo de servicios
 * - Nosotros.php: Información de la empresa
 * - mensaje.php: Formulario de contacto
 * - Politica.php: Política de privacidad
 * - login.html: Página de autenticación
 * - perfil.php: Panel de perfil del usuario
 * 
 * SECCIONES LEGALES DOCUMENTADAS:
 * 1. Aceptación de términos
 * 2. Descripción de servicios (psicología para jóvenes)
 * 3. Política de citas y cancelaciones (24 horas mínimo)
 * 4. Confidencialidad de información personal y sesiones
 * 5. Política de pagos
 * 6. Normas de conducta del usuario
 * 7. Renuncia de responsabilidad (emergencias psicológicas)
 * 8. Derecho a modificar términos
 * 9. Contacto: support@vision.com
 * 
 * DATOS TÉCNICOS:
 * - Última actualización: 26 de julio de 2024
 * - Codificación: UTF-8
 * - Viewport: Responsive (mobile-first)
 * - Framework CSS: Tailwind CSS con plugins de formularios
 * - Fuente: Inter (400, 500, 700, 900)
 * - Temas: Light (background-light) y Dark (background-dark)
 * - Color primario: #13a4ec (azul corporativo)
 */

session_start();
$Loggeado = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags: Codificación y viewport para responsividad -->
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Términos y condiciones - Vision</title>
    <link href="data:image/x-icon;base64," rel="icon" type="image/x-icon" />
    
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
                        "display": ["Inter"]               // Familia tipográfica principal
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
    
    <!-- Preconexión a Google Fonts para optimización de carga -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <!-- Font Inter: Tipografía moderna para títulos y cuerpo -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap"
        rel="stylesheet" />
</head>

<!--
    COMENTARIO DE ESTRUCTURA:
    - Página estática con contenido legal
    - Autenticación: Verifica sesión y muestra controles condicionales
    - Layout: Header sticky + contenido principal + footer
    - auth.js: Ejecutado al final para manejo dinámico de sesión
-->

<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
    <div class="flex flex-col min-h-screen">
        <!-- HEADER: Navegación y controles de usuario -->
        <header class="bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm sticky top-0 z-10">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20 border-b border-primary/20">
                    <!-- Logo y nombre de la marca -->
                    <div class="flex items-center gap-4">
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
                        <h2 class="text-xl font-bold">Vision</h2>
                    </div>
                    
                    <!-- Navegación principal: Visible en pantallas medias y superiores -->
                    <nav class="hidden md:flex items-center gap-8">
                        <a class="text-sm font-medium hover:text-primary transition-colors" href="index.php">Página
                            principal</a>
                        <a class="text-sm font-medium hover:text-primary transition-colors"
                            href="Servicios.php">Servicios</a>
                        <a class="text-sm font-medium hover:text-primary transition-colors" href="Nosotros.php">Sobre
                            nosotros</a>
                        <a class="text-sm font-medium hover:text-primary transition-colors"
                            href="mensaje.php">Contacto</a>
                    </nav>
                    
                    <!-- Controles de autenticación: Condicionales según sesión activa -->
                    <?php if (!$Loggeado): ?>
                    <!-- Usuario no autenticado: Botón de login -->
                    <a href="login.html">
                        <button
                            class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">
                            <span class="truncate">Inicio de Sesion</span>
                        </button>
                    </a>
                    <?php else: ?>
                    <!-- Usuario autenticado: Avatar con enlace a perfil -->
                    <a href="Perfil.php">
                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
                            style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
                        </div>
                    </a>

                    <?php endif; ?>

                </div>
            </div>
        </header>
        <main class="flex-grow">
            <!-- Contenedor principal: Ancho máximo responsivo -->
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="max-w-4xl mx-auto">
                    <!-- Encabezado: Título y fecha de actualización -->
                    <div class="mb-12 text-center">
                        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                            Términos y condiciones</h1>
                        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">Última actualización: 26 de julio de
                            2024</p>
                    </div>
                    
                    <!-- Contenido legal: Nueve secciones temáticas -->
                    <div class="space-y-10">
                        <!-- Sección 1: Aceptación de términos -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">1. Aceptación de los términos
                            </h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Al acceder o usar el sitio y los servicios de Vision, aceptas estar sujeto a estos
                                Términos y Condiciones. Si no estás de acuerdo, por favor no utilices nuestros
                                servicios.
                            </p>
                        </div>
                        
                        <!-- Sección 2: Descripción de servicios -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">2. Servicios</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Vision ofrece servicios de psicología para jóvenes, incluyendo terapia individual,
                                consultas y talleres. Nuestro objetivo es apoyar la salud mental y el bienestar a través
                                de atención profesional.
                            </p>
                        </div>
                        
                        <!-- Sección 3: Política de citas y cancelaciones -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">3. Citas y cancelaciones</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Las citas deben programarse con antelación mediante nuestro sistema de reservas. Las
                                cancelaciones deben realizarse al menos 24 horas antes de la cita para evitar cargos.
                                Cancelaciones tardías o inasistencias pueden tener costo.
                            </p>
                        </div>
                        
                        <!-- Sección 4: Confidencialidad de datos -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">4. Confidencialidad</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Nos comprometemos a mantener la confidencialidad de tu información personal y de las
                                sesiones. La información compartida se mantendrá confidencial, salvo cuando la ley exija
                                su divulgación (por ejemplo, riesgo de daño).
                            </p>
                        </div>
                        
                        <!-- Sección 5: Política de pagos -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">5. Pago</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                El pago por los servicios vence en el momento de la reserva o según lo acordado.
                                Aceptamos varias formas de pago según se especifique en el sitio. Las tarifas pueden
                                cambiar con previo aviso.
                            </p>
                        </div>
                        
                        <!-- Sección 6: Normas de conducta -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">6. Conducta del usuario</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Debes usar nuestros servicios de forma respetuosa y conforme a la ley. El uso indebido
                                (acoso, conductas abusivas o violación de derechos) puede implicar la suspensión o
                                término del servicio.
                            </p>
                        </div>
                        
                        <!-- Sección 7: Renuncia de responsabilidad -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">7. Renuncia de responsabilidad
                            </h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Vision ofrece apoyo y orientación, pero no sustituye un servicio de intervención en
                                crisis. Si estás en una situación de emergencia, contacta con los servicios de
                                emergencia o una línea de crisis inmediatamente.
                            </p>
                        </div>
                        
                        <!-- Sección 8: Cambios en términos -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">8. Cambios en los términos</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Nos reservamos el derecho a modificar estos Términos y Condiciones. Los cambios se
                                publicarán en el sitio y el uso continuado constituye aceptación de los mismos.
                            </p>
                        </div>
                        
                        <!-- Sección 9: Información de contacto -->
                        <div class="space-y-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">9. Contáctanos</h3>
                            <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                Si tienes preguntas o dudas sobre estos Términos y Condiciones, contáctanos en <a
                                    class="text-primary hover:underline"
                                    href="mailto:support@vision.com">support@vision.com</a>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- FOOTER: Información y enlaces legales -->
        <footer class="bg-background-light dark:bg-background-dark border-t border-primary/20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <!-- Enlaces legales: Política de privacidad -->
                    <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                        <a class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary"
                            href="Politica.php">Política de privacidad</a>
                    </div>
                    <!-- Copyright y derechos reservados -->
                    <p class="text-sm text-gray-600 dark:text-gray-400">© 2025 Vision. Todos los derechos reservados.
                    </p>
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