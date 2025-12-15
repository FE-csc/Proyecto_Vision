<?php
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * FILE: Politica.php
 * ════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: Página de Política de Privacidad (información pública + condicional)
 * Explica cómo Vision maneja datos personales y privacidad de usuarios
 * FUNCIONALIDAD: Vista HTML con contenido estático + header dinámico (login/perfil)
 * DEPENDENCIAS: auth.js (session info), MySQLi session (user detection)
 * AUTENTICACIÓN: Página pública (no requiere login), pero detecta sesión activa
 * DISEÑO: Tailwind CSS responsive con dark mode, prose styling
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * SECCIONES DE CONTENIDO:
 * 1. Compromiso de privacidad
 * 2. Información que se recopila
 * 3. Cómo se usa la información
 * 4. Seguridad de datos
 * 5. Derechos del usuario
 * 6. Cambios en la política
 * 
 * COMPORTAMIENTO:
 * - Página pública (accessible sin login)
 * - Header dinámico: Botón login (no autenticado) o avatar (autenticado)
 * - Contenido informativo: 6 secciones con cuadros de fondo
 * - Footer: Copyright e información
 * 
 * SEGURIDAD:
 * - Session detection para mostrar estado de login
 * - Conditional rendering en header (login/perfil según $Loggeado)
 * - auth.js carga al final para funcionalidad de sesión
 */

session_start();

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: DETECCIÓN DE SESIÓN DE USUARIO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * Detectar si usuario está autenticado
 * 
 * FLUJO:
 * 1. session_start() inicia sesión (o reanuda existente)
 * 2. isset($_SESSION['user_id']) verifica si usuario está logueado
 * 3. $Loggeado = true si existe user_id en sesión
 * 4. $Loggeado = false si no existe (usuario anonimo)
 * 
 * PROPÓSITO: Renderizar header diferente según estado de autenticación
 * - Si no autenticado: Mostrar botón "Inicio de Sesión" (link a login.html)
 * - Si autenticado: Mostrar avatar (link a perfil.php)
 * 
 * VALORES:
 * - $_SESSION['user_id']: ID del usuario actual (solo si autenticado)
 * - $Loggeado: Boolean para uso en templates PHP
 */

// Detectar si el usuario tiene sesión activa (user_id en $_SESSION)
$Loggeado = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!-- SECCIÓN 2: METADATOS Y CONFIGURACIÓN (HEAD) -->
    <!-- ──────────────────────────────────────────────────────────────────────────── -->
    <!--
     * META TAGS:
     * - charset="utf-8": Codificación UTF-8 para caracteres especiales
     * - viewport: Responsive design (width=device-width, scale 1.0)
     * - title: "Política de privacidad - Vision"
     * 
     * RECURSOS:
     * 1. Tailwind CSS: Framework CSS para estilos responsive
     * 2. Google Fonts (Inter): Tipografía Inter 400,500,700,900 pesos
     * 3. Configuración Tailwind: Dark mode, colores personalizados
     * 
     * CONFIGURACIÓN TAILWIND:
     * - darkMode: "class" (controlado por clase CSS)
     * - Colores: primary (#13a4ec), background light/dark
     * - FontFamily: "Inter" para todo el sitio
     * - Border radius: Valores customizados
    -->
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Política de privacidad - Vision</title>
    <!-- Tailwind CSS CDN con plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Google Fonts preconnect para optimización -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <!-- Inter font: 400 (regular), 500 (medium), 700 (bold), 900 (black) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap"
        rel="stylesheet" />
    <!-- Configuración customizada de Tailwind -->
    <script id="tailwind-config">
        /**
         * ────────────────────────────────────────────────────────────────────────────
         * CONFIGURACIÓN DE TAILWIND CSS
         * ────────────────────────────────────────────────────────────────────────────
         * 
         * tailwind.config: Personalización de theme y colores
         * 
         * OPCIONES:
         * - darkMode: "class" - Usar clase CSS para controlar dark mode
         *   (agregar class="dark" en <html> para habilitar dark mode)
         * - theme.extend.colors: Agregar colores custom sin sobrescribir defaults
         * - theme.extend.fontFamily: Agregar Inter a la fuente por defecto
         * - theme.extend.borderRadius: Customizar valores de border-radius
         * 
         * COLORES PERSONALIZADOS:
         * - primary: #13a4ec (azul de marca para links, botones, acentos)
         * - background-light: #f6f7f8 (fondo gris claro para light mode)
         * - background-dark: #101c22 (fondo oscuro para dark mode)
         * 
         * EJEMPLOS DE USO:
         * - bg-primary: Fondo azul primario
         * - dark:bg-background-dark: Fondo oscuro en dark mode
         * - text-primary: Texto azul primario
         * - hover:text-primary: Azul primario al hover
         * - dark:text-slate-300: Texto gris claro en dark mode
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
    ════════════════════════════════════════════════════════════════════════════════
    SECCIÓN 3: BODY Y ESTRUCTURA PRINCIPAL
    ════════════════════════════════════════════════════════════════════════════════
    
    ESTRUCTURA:
    - min-h-screen: Mínimo altura de viewport (full screen)
    - flex flex-col: Layout vertical (header, main, footer)
    - Body classes: Fondo, tipografía, color de texto adaptado a dark mode
    
    LAYOUT:
    1. Header: Navegación superior (sticky)
    2. Main: Contenido principal (flex-grow para llenar espacio)
    3. Footer: Pie de página
-->

<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
    <div class="min-h-screen flex flex-col">
        <!-- ────────────────────────────────────────────────────────────────────── -->
        <!-- SECCIÓN 4: HEADER - NAVEGACIÓN Y AUTENTICACIÓN DINÁMICA -->
        <!-- ────────────────────────────────────────────────────────────────────── -->
        <!--
         * HEADER: Barra de navegación sticky con logo y menú
         * 
         * ESTRUCTURA:
         * 1. Logo + Nombre (Vision)
         * 2. Navegación principal (centrada, hidden en mobile)
         * 3. Botón login/Avatar (dinámico según $Loggeado)
         * 
         * CARACTERÍSTICAS:
         * - sticky top-0: Permanece en parte superior al scrollear
         * - backdrop-blur-sm: Efecto blur en fondo
         * - Dark mode: Colores adaptados (dark:bg-..., dark:border-...)
         * - Responsive: Nav hidden en <md (mobile)
         * 
         * NAVEGACIÓN:
         * - index.php: Página principal
         * - Servicios.php: Información de servicios
         * - Nosotros.php: Sobre nosotros
         * - mensaje.php: Contacto
         * 
         * LÓGICA CONDICIONAL:
         * - Si NO autenticado ($Loggeado = false):
         *   Mostrar botón "Inicio de Sesion" (link a login.html)
         * - Si autenticado ($Loggeado = true):
         *   Mostrar avatar (link a perfil.php)
        -->
        <header
            class="bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo + Brand name -->
                    <div class="flex items-center gap-3">
                        <!-- Logo SVG (icono azul primario) -->
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
                        <!-- Brand name -->
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
                    </div>
                    
                    <!-- Navegación principal (hidden en <md) -->
                    <nav class="hidden md:flex items-center gap-8">
                        <!-- Página principal -->
                        <a class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary transition-colors"
                            href="index.php">Página principal</a>
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
                    
                    <!-- Auth section (login/perfil dinámico) -->
                    <div class="flex items-center">
                        <?php if (!$Loggeado): ?>
                            <!-- No autenticado: Mostrar botón login -->
                            <a href="login.html">
                                <button
                                    class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">
                                    <span class="truncate">Inicio de Sesion</span>
                                </button>
                            </a>
                        <?php else: ?>
                            <!-- Autenticado: Mostrar avatar (link a perfil) -->
                            <a href="Perfil.php">
                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
                                    style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
                                </div>
                            </a>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        <!-- ────────────────────────────────────────────────────────────────────── -->
        <!-- SECCIÓN 5: MAIN - CONTENIDO PRINCIPAL (POLÍTICA DE PRIVACIDAD) -->
        <!-- ────────────────────────────────────────────────────────────────────── -->
        <!--
         * MAIN CONTENT: Contenido central de la página
         * 
         * ESTRUCTURA:
         * 1. Hero section: Título + descripción
         * 2. 6 secciones de contenido con cuadros de fondo
         * 3. Footer con última actualización
         * 
         * CARACTERÍSTICAS:
         * - flex-grow: Ocupa el espacio disponible (push footer abajo)
         * - max-w-4xl: Ancho máximo para lectura cómoda
         * - Prose styling: Estilos tipográficos para articulos
         * - Dark mode: Colores adaptados (dark:prose-invert)
         * 
         * SECCIONES:
         * 1. Nuestro compromiso
         * 2. Información que recogemos
         * 3. Cómo usamos tu información
         * 4. Seguridad de los datos
         * 5. Tus derechos
         * 6. Cambios en esta política
        -->
        <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
            <div class="max-w-4xl mx-auto">
                <!-- Hero: Título + descripción -->
                <div class="mb-12 text-center">
                    <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Política de privacidad</h2>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">Tu privacidad es importante para
                        nosotros. A continuación explicamos cómo protegemos y usamos tu información.</p>
                </div>
                
                <!-- Contenedor de secciones (prose styling) -->
                <div
                    class="space-y-10 prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white prose-p:text-slate-600 dark:prose-p:text-slate-300 prose-a:text-primary hover:prose-a:text-primary/80">
                    
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!-- SECCIÓN 5.1: NUESTRO COMPROMISO -->
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!--
                     * PROPOSITO:
                     * Explicar el compromiso de Vision con la privacidad del usuario
                     * 
                     * CONTENIDO:
                     * - Compromiso de proteger privacidad
                     * - Garantizar confidencialidad de datos
                     * - Explicación clara de qué datos se recopilan
                    -->
                    <div class="p-8 bg-white/50 dark:bg-background-dark/50 rounded-lg">
                        <h3 class="text-2xl font-bold mb-4">Nuestro compromiso</h3>
                        <p>En Vision estamos comprometidos a proteger tu privacidad y garantizar la confidencialidad de
                            tus datos personales. Esta política explica de forma clara qué datos recolectamos, para qué
                            los usamos y cómo los protegemos.</p>
                    </div>
                    
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!-- SECCIÓN 5.2: INFORMACIÓN QUE RECOGEMOS -->
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!--
                     * PROPOSITO:
                     * Informar al usuario qué datos se recopilan
                     * 
                     * TIPOS DE DATOS:
                     * PERSONALES:
                     * - Nombre (nombre y apellido)
                     * - Correo electrónico
                     * - Teléfono
                     * - Edad
                     * - Otros datos compartidos voluntariamente
                     * 
                     * NO PERSONALES:
                     * - Tipo de navegador
                     * - Información de dispositivo
                     * - Patrones de uso
                     * 
                     * USO:
                     * - Mejorar experiencia del usuario
                     * - Análisis y estadísticas
                    -->
                    <div class="p-8 bg-white/50 dark:bg-background-dark/50 rounded-lg">
                        <h3 class="text-2xl font-bold mb-4">Información que recogemos</h3>
                        <p>Podemos recopilar información personal cuando interactúas con nuestro sitio o usas nuestros
                            servicios, como tu nombre, correo, teléfono, edad y cualquier otra información que decidas
                            compartir. También recopilamos datos no personales (por ejemplo, el tipo de navegador) para
                            mejorar la experiencia.</p>
                    </div>
                    
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!-- SECCIÓN 5.3: CÓMO USAMOS TU INFORMACIÓN -->
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!--
                     * PROPOSITO:
                     * Explicar usos de los datos personales
                     * 
                     * USOS AUTORIZADOS:
                     * - Ofrecer servicios clínicos (agendar citas)
                     * - Responder consultas del usuario
                     * - Personalizar experiencia
                     * - Comunicaciones relacionadas con servicios
                     * 
                     * RESTRICCIONES:
                     * - NO se comparten datos sin permiso
                     * - Excepto si ley lo requiere
                     * - Máximo respeto a privacidad
                    -->
                    <div class="p-8 bg-white/50 dark:bg-background-dark/50 rounded-lg">
                        <h3 class="text-2xl font-bold mb-4">Cómo usamos tu información</h3>
                        <p>Usamos tu información para ofrecer nuestros servicios, como agendar citas o responder tus
                            consultas. También la usamos para personalizar tu experiencia en el sitio. No compartiremos
                            tus datos personales sin tu permiso, salvo que la ley lo exija.</p>
                    </div>
                    
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!-- SECCIÓN 5.4: SEGURIDAD DE LOS DATOS -->
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!--
                     * PROPOSITO:
                     * Informar sobre medidas de seguridad implementadas
                     * 
                     * MEDIDAS IMPLEMENTADAS:
                     * - Protección contra accesos no autorizados
                     * - Prácticas estándar de industria
                     * - Encriptación de datos sensibles
                     * - Control de acceso basado en roles
                     * 
                     * LIMITACIONES:
                     * - No hay seguridad absoluta en internet
                     * - Mejor esfuerzo pero no garantía
                     * - Responsabilidad compartida
                    -->
                    <div class="p-8 bg-white/50 dark:bg-background-dark/50 rounded-lg">
                        <h3 class="text-2xl font-bold mb-4">Seguridad de los datos</h3>
                        <p>Adoptamos medidas para proteger tu información frente a accesos no autorizados y empleamos
                            prácticas estándar de la industria. Aunque nadie puede garantizar seguridad absoluta en
                            internet, trabajamos para mantener tus datos lo más seguros posible.</p>
                    </div>
                    
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!-- SECCIÓN 5.5: TUS DERECHOS -->
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!--
                     * PROPOSITO:
                     * Informar sobre derechos del usuario respecto a sus datos
                     * 
                     * DERECHOS:
                     * - Derecho de acceso: Ver qué datos tenemos
                     * - Derecho de rectificación: Corregir datos incorrectos
                     * - Derecho de eliminación: Borrar datos personales
                     * - Derecho de portabilidad: Obtener copia de datos
                     * 
                     * EJERCER DERECHOS:
                     * - A través de página de contacto (mensaje.php)
                     * - Support está disponible para ayudar
                     * - Proceso rápido y transparente
                    -->
                    <div class="p-8 bg-white/50 dark:bg-background-dark/50 rounded-lg">
                        <h3 class="text-2xl font-bold mb-4">Tus derechos</h3>
                        <p>Tienes derecho a acceder, corregir o eliminar tus datos personales. Si quieres ejercer estos
                            derechos o tienes dudas, contáctanos a través de la página de contacto. Estamos para
                            ayudarte.</p>
                    </div>
                    
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!-- SECCIÓN 5.6: CAMBIOS EN ESTA POLÍTICA -->
                    <!-- ────────────────────────────────────────────────────────────────── -->
                    <!--
                     * PROPOSITO:
                     * Informar que la política puede cambiar en el futuro
                     * 
                     * PROCESO:
                     * - Cambios publicados en esta página
                     * - Notificación a usuarios si cambios importantes
                     * - Revisión periódica recomendada
                     * - Fecha de última actualización visible
                     * 
                     * RECOMENDACIONES:
                     * - Revisar ocasionalmente
                     * - Estar al día con cambios
                     * - Contactar con dudas
                    -->
                    <div class="p-8 bg-white/50 dark:bg-background-dark/50 rounded-lg">
                        <h3 class="text-2xl font-bold mb-4">Cambios en esta política</h3>
                        <p>Podemos actualizar esta política ocasionalmente. Si realizamos cambios importantes, los
                            publicaremos en esta página. Te recomendamos revisarla de vez en cuando para estar al día.
                        </p>
                    </div>
                </div>
                
                <!-- ────────────────────────────────────────────────────────────────── -->
                <!-- SECCIÓN 5.7: INFORMACIÓN DE ACTUALIZACIÓN -->
                <!-- ────────────────────────────────────────────────────────────────── -->
                <!--
                 * PROPOSITO:
                 * Mostrar fecha de última actualización de la política
                 * 
                 * IMPORTANCIA:
                 * - Permite al usuario saber cuán reciente es la información
                 * - Referencia para cambios posteriores
                 * - Transparencia sobre actualizaciones
                 * 
                 * FECHA:
                 * - Estática: 26 de octubre de 2024
                 * - Debe actualizarse cuando se modifique la política
                -->
                <div class="mt-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Última actualización: 26 de octubre de 2025.
                    </p>
                </div>
            </div>
        </main>
        <!-- ────────────────────────────────────────────────────────────────────── -->
        <!-- SECCIÓN 6: FOOTER - PIE DE PÁGINA -->
        <!-- ────────────────────────────────────────────────────────────────────── -->
        <!--
         * FOOTER: Pie de página con información de copyright
         * 
         * ESTRUCTURA:
         * - Borde superior (border-t)
         * - Fondo adaptado a dark mode
         * - Texto centrado pequeño
         * 
         * CONTENIDO:
         * - Copyright: © 2025 Vision
         * - Derechos reservados
         * 
         * CARACTERÍSTICAS:
         * - Links oscuros (slate-300/400)
         * - Responsive: padding adaptado
         * - Sticky bottom: No desaparece al scrollear
        -->
        <footer class="bg-background-light dark:bg-background-dark border-t border-slate-200 dark:border-slate-800">
            <div
                class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                <p>© 2025 Vision. Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>

</body>

</html>

<!-- ──────────────────────────────────────────────────────────────────────────── -->
<!-- SECCIÓN 7: SCRIPT EXTERNO - FUNCIONALIDAD DE SESIÓN -->
<!-- ──────────────────────────────────────────────────────────────────────────── -->
<!--
 * IMPORTACIÓN DE auth.js
 * 
 * PROPÓSITO:
 * - Incluir funcionalidad de autenticación/sesión desde archivo externo
 * - auth.js maneja:
 *   - Detectar sesión actual
 *   - Mostrar/ocultar elementos según estado de login
 *   - Manejar logout
 *   - UX relacionado con sesión (avatar, perfil, etc.)
 * 
 * UBICACIÓN:
 * - Al final del body (después del HTML, antes de cierre </body>)
 * - Optimiza carga (no bloquea rendering)
 * - Acceso a DOM completo (todos los elementos cargados)
 * 
 * NOTA:
 * - El estado de sesión se detecta primero en PHP ($Loggeado)
 * - auth.js proporciona funcionalidad adicional de sesión en client-side
 * - Ambos trabajan juntos para experiencia completa
-->
<script src="auth.js"></script>