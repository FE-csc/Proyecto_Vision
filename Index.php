<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: Index.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: Landing page principal (homepage) del sistema Visión
// Página de bienvenida pública para todos los visitantes del sitio
// Muestra información sobre servicios, equipo, y opciones de acceso
// FUNCIONALIDAD PRINCIPAL: Renderizado de página estática con contenido HTML5 y Tailwind CSS
// DEPENDENCIAS: auth.js (para redirecciones de login en el lado del cliente)
// ROLES AUTORIZADOS: Público (sin autenticación requerida)
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN Y VERIFICACIÓN DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
// Inicia la sesión PHP para detectar si el usuario está autenticado
session_start();

// Variable booleana que indica si el usuario tiene una sesión activa
// Se utiliza para mostrar/ocultar botones de login o perfil en la barra de navegación
// Si isset($_SESSION['user_id']) es verdadero, muestra ícono de perfil
// Si es falso, muestra botón "Inicio de Sesión"
$Loggeado = isset($_SESSION['user_id']);
// ──────────────────────────────────────────────────────────────────────────────
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 2: CONFIGURACIÓN DEL DOCUMENTO Y META TAGS
         ──────────────────────────────────────────────────────────────────────────
         Define la codificación UTF-8 para soporte de caracteres especiales
         Configura viewport para diseño responsive en dispositivos móviles (mobile-first)
         Establece el título de la página que aparece en la pestaña del navegador
         ────────────────────────────────────────────────────────────────────────── -->
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title> Vision</title>
    
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 3: PRECONEXIONES Y FUENTES EXTERNAS
         ──────────────────────────────────────────────────────────────────────────
         Preconecta a Google Fonts para mejorar velocidad de carga
         - rel="preconnect" establece conexión previa para optimizar rendimiento
         - crossorigin permite que el navegador cachee la fuente correctamente
         Importa fuente Inter en pesos 400, 500, 700, 900 desde Google Fonts API
         ────────────────────────────────────────────────────────────────────────── -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap" rel="stylesheet" />
    
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 4: TAILWIND CSS Y CONFIGURACIÓN DE TEMA
         ──────────────────────────────────────────────────────────────────────────
         Carga Tailwind CSS v3 con plugins de formularios y container queries
         Configura tema personalizado:
         - darkMode: "class" permite cambio manual de tema oscuro/claro (no automático)
         - Colores personalizados: primary (#13a4ec = azul) para identidad de marca
         - background-light (#f6f7f8 = gris claro) para tema claro
         - background-dark (#101c22 = gris oscuro) para tema oscuro
         - Fuente principal: Inter (moderna, buena legibilidad)
         - Radio de bordes personalizado (default: 0.5rem, lg: 1rem, xl: 1.5rem, full: círculo)
         ────────────────────────────────────────────────────────────────────────── -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
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
    
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 5: ESTILOS PERSONALIZADOS ADICIONALES
         ──────────────────────────────────────────────────────────────────────────
         .text-shadow: Clase CSS personalizada que agrega sombra de texto
         - Aplicada a títulos sobre imágenes de fondo para mejor contraste y legibilidad
         - Valores: desplazamiento X=0, desplazamiento Y=2px, blur=4px, opacidad negra 0.4
         Mejora la legibilidad del texto sobre fondos de imagen
         ────────────────────────────────────────────────────────────────────────── -->
    <style>
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>


<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 6: ESTRUCTURA PRINCIPAL DEL LAYOUT
         ──────────────────────────────────────────────────────────────────────────
         Layout principal de la página con flexbox y altura mínima de pantalla completa
         - relative: Para posicionamiento de elementos absolutos si es necesario
         - min-h-screen: Altura mínima igual a la altura del viewport (100vh)
         - w-full: Ancho completo disponible
         - flex h-full grow flex-col: Disposición vertical con crecimiento flexible
         Estructura: Header (sticky) + Main (flex-1) + Footer (siempre al final)
         ────────────────────────────────────────────────────────────────────────── -->
    <div class="relative min-h-screen w-full">
        <div class="layout-container flex h-full grow flex-col">
            
            <!-- ══════════════════════════════════════════════════════════════════
                SECCIÓN 7: BARRA DE NAVEGACIÓN (HEADER)
                ══════════════════════════════════════════════════════════════════
                Navbar fija en la parte superior con backdrop blur para efecto glassmorphism
                - sticky top-0 z-50: Se queda fija al scrollear con z-index alto (encima de contenido)
                - bg-background-light/80 dark:bg-background-dark/80: Opacidad 80% para ver contenido detrás
                - backdrop-blur-sm: Efecto de desenfoque sutilizado de fondo
                
                ELEMENTOS:
                1. Logo + Nombre de empresa (izquierda)
                2. Navegación principal (centro, oculta en móvil)
                3. Botón Login o Ícono de perfil (derecha, dinámico según sesión)
                ═══════════════════════════════════════════════════════════════════ -->
            <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
                <div class="container mx-auto flex items-center justify-between whitespace-nowrap px-6 py-4">
                    
                    <!-- LOGO Y NOMBRE DE EMPRESA -->
                    <div class="flex items-center gap-3">
                        <!-- Logo clicable que redirecciona a Index.php (home) -->
                        <a href="Index.php">
                            <button>
                                <div class="w-8 h-8 text-primary">
                                    <!-- SVG con icono estilizado de visión (mitad de círculo) -->
                                    <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                                            fill="currentColor"></path>
                                    </svg>
                                </div>
                            </button>
                        </a>
                        <!-- Nombre de marca: "Vision" -->
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white"> Vision</h2>
                    </div>
                    
                    <!-- NAVEGACIÓN PRINCIPAL (MENÚ DESKTOP) -->
                    <!-- hidden md:flex: Oculta en pantallas pequeñas, visible en md (768px+) -->
                    <nav class="hidden md:flex items-center gap-8">
                        <!-- Enlace a página Nosotros -->
                        <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
                            href="Nosotros.php"><button>Sobre nosotros</button></a>
                        <!-- Enlace a página de Servicios -->
                        <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
                            href="Servicios.php"><button>Servicios</button></a>
                        <!-- Enlace a formulario de Contacto -->
                        <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
                            href="mensaje.php"><button>Contacto</button></a>
                    </nav>
                    
                    <!-- ELEMENTOS DE USUARIO (DERECHA) -->
                    <div class="flex items-center gap-4">
                        <?php if (!$Loggeado): ?>
                            <!-- BOTÓN DE LOGIN (si NO está autenticado) -->
                            <!-- Redirecciona a login.html para inicio de sesión -->
                            <a href="login.html">
                                <button
                                    class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">
                                    <span class="truncate">Inicio de Sesion</span>
                                </button>
                            </a>
                        <?php else: ?>
                            <!-- ÍCONO DE PERFIL (si ESTÁ autenticado) -->
                            <!-- Avatar estilizado usando imagen de Flaticon que se vincula a Perfil.php -->
                            <!-- El usuario logueado puede hacer clic para acceder a su perfil -->
                            <a href="perfil.php">
                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
                                    style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
                                </div>
                            </a>

                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <main class="flex-1">
                <!-- ══════════════════════════════════════════════════════════════════
                    SECCIÓN 8: SECCIÓN HERO (BANNER PRINCIPAL)
                    ══════════════════════════════════════════════════════════════════
                    Sección de impacto visual en la parte superior con:
                    - Imagen de fondo con gradiente oscuro superpuesto (parallax effect)
                    - Texto centrado y responsivo (4xl en móvil, 6xl en desktop)
                    - Altura: 60vh (60% del viewport) con mínimo 480px
                    
                    ELEMENTOS:
                    1. Gradiente oscuro 0-30% opacidad a 60% opacidad (para legibilidad)
                    2. Imagen de fondo de Unsplash (terapia/bienestar)
                    3. Título principal: "Tu mente, tu rumbo, tu visión"
                    4. Subtítulo: Descripción de la propuesta de valor
                    5. CTA Button: "Sobre Nosotros" (redirige a Nosotros.php)
                    ═══════════════════════════════════════════════════════════════════ -->
                <section class="relative h-[60vh] min-h-[480px] flex items-center justify-center text-center bg-cover bg-center"
                    style='background-image: linear-gradient(rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.6) 100%), url("https://images.unsplash.com/photo-1544027993-37dbfe43562a?q=80&w=2070&auto=format&fit=crop");'>
                    <div class="container mx-auto px-6 max-w-4xl">
                        <!-- TÍTULO PRINCIPAL (H1) -->
                        <!-- Tamaño responsivo: 4xl móvil → 6xl desktop -->
                        <!-- text-shadow aplicado para contraste sobre imagen -->
                        <h1 class="text-4xl md:text-6xl font-black text-white text-shadow leading-tight">Tu mente, tu rumbo,<br>tu visión.</h1>
                        
                        <!-- SUBTÍTULO DESCRIPTIVO -->
                        <!-- Introduce la propuesta de valor de la plataforma -->
                        <p class="mt-4 text-lg md:text-xl text-white/90 text-shadow max-w-3xl mx-auto">
                            Bienvenido(a) a Visión, un espacio pensado para jóvenes que buscan cuidar su salud mental y descubrir su
                            mejor versión.
                            Aquí podés reservar tus citas fácilmente, hablar con profesionales en psicología y dar el primer paso
                            hacia una mente más clara y un futuro con propósito.
                        </p>
                        
                        <!-- BOTÓN CTA (CALL TO ACTION) -->
                        <!-- Redirecciona a la página "Sobre Nosotros" para más información -->
                        <!-- Incluye transformación de escala en hover (hover:scale-105) -->
                        <a href="Nosotros.php"
                            class="mt-8 inline-flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-12 px-8 bg-primary text-white text-base font-bold shadow-xl hover:bg-primary/90 transition-transform transform hover:scale-105 mx-auto">
                            <span class="truncate">Sobre Nosotros</span>
                        </a>
                    </div>
                </section>
                
                <!-- ══════════════════════════════════════════════════════════════════
                    SECCIÓN 9: SERVICIOS OFRECIDOS
                    ══════════════════════════════════════════════════════════════════
                    Sección que presenta los 3 servicios principales de la plataforma
                    - Grid responsivo: 1 columna móvil → 2 columnas tablet → 3 columnas desktop
                    - Tarjetas con icono, título y descripción
                    - Efecto hover con sombra aumentada
                    
                    SERVICIOS MOSTRADOS:
                    1. Terapia individual (sesiones personalizadas)
                    2. Sesiones de Grupo (apoyo entre pares)
                    3. Talleres (educación en salud mental)
                    ═══════════════════════════════════════════════════════════════════ -->
                <section class="py-16 sm:py-24">
                    <div class="container mx-auto px-6">
                        <!-- HEADER DE LA SECCIÓN -->
                        <div class="text-center max-w-3xl mx-auto">
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Nuestros servicios</h2>
                            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                                Ofrecemos una variedad de servicios diseñados para apoyar la salud mental de los jóvenes, desde terapia
                                individual hasta sesiones grupales y talleres.
                            </p>
                        </div>
                        
                        <!-- GRID DE TARJETAS DE SERVICIOS -->
                        <!-- grid-cols-1 md:grid-cols-2 lg:grid-cols-3: Responsivo (1→2→3 columnas) -->
                        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            
                            <!-- TARJETA 1: TERAPIA INDIVIDUAL -->
                            <div
                                class="bg-background-light dark:bg-background-dark border border-gray-200 dark:border-gray-700 rounded-lg p-6 flex flex-col items-center text-center transition-shadow hover:shadow-lg">
                                <!-- Icono con fondo coloreado -->
                                <div class="bg-primary/10 dark:bg-primary/20 rounded-full p-4">
                                    <!-- SVG de usuario (icono Material Design) -->
                                    <svg class="text-primary" fill="currentColor" height="32px" viewBox="0 0 256 256" width="32px"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M117.25,157.92a60,60,0,1,0-66.5,0A95.83,95.83,0,0,0,3.53,195.63a8,8,0,1,0,13.4,8.74,80,80,0,0,1,134.14,0,8,8,0,0,0,13.4-8.74A95.83,95.83,0,0,0,117.25,157.92ZM40,108a44,44,0,1,1,44,44A44.05,44.05,0,0,1,40,108Zm210.14,98.7a8,8,0,0,1-11.07-2.33A79.83,79.83,0,0,0,172,168a8,8,0,0,1,0-16,44,44,0,1,0-16.34-84.87,8,8,0,1,1-5.94-14.85,60,60,0,0,1,55.53,105.64,95.83,95.83,0,0,1,47.22,37.71A8,8,0,0,1,250.14,206.7Z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-gray-900 dark:text-white"> Terapia individual</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Sesiones de terapia personalizadas, individuales, para
                                    abordar necesidades y desafíos específicos.</p>
                            </div>
                            
                            <!-- TARJETA 2: SESIONES DE GRUPO -->
                            <div
                                class="bg-background-light dark:bg-background-dark border border-gray-200 dark:border-gray-700 rounded-lg p-6 flex flex-col items-center text-center transition-shadow hover:shadow-lg">
                                <!-- Icono con fondo coloreado -->
                                <div class="bg-primary/10 dark:bg-primary/20 rounded-full p-4">
                                    <!-- SVG de calendario (icono Material Design) -->
                                    <svg class="text-primary" fill="currentColor" height="32px" viewBox="0 0 256 256" width="32px"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-96-88v64a8,8,0,0,1-16,0V132.94l-4.42,2.22a8,8,0,0,1-7.16-14.32l16-8A8,8,0,0,1,112,120Zm59.16,30.45L152,176h16a8,8,0,0,1,0,16H136a8,8,0,0,1-6.4-12.8l28.78-38.37A8,8,0,1,0,145.07,132a8,8,0,1,1-13.85-8A24,24,0,0,1,176,136,23.76,23.76,0,0,1,171.16,150.45Z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-gray-900 dark:text-white">Sesiones de Grupo</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Sesiones grupales interactivas que fomentan el apoyo
                                    entre pares y el intercambio de experiencias.</p>
                            </div>
                            
                            <!-- TARJETA 3: TALLERES -->
                            <div
                                class="bg-background-light dark:bg-background-dark border border-gray-200 dark:border-gray-700 rounded-lg p-6 flex flex-col items-center text-center transition-shadow hover:shadow-lg">
                                <!-- Icono con fondo coloreado -->
                                <div class="bg-primary/10 dark:bg-primary/20 rounded-full p-4">
                                    <!-- SVG de chat (icono Material Design) -->
                                    <svg class="text-primary" fill="currentColor" height="32px" viewBox="0 0 256 256" width="32px"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M140,128a12,12,0,1,1-12-12A12,12,0,0,1,140,128ZM84,116a12,12,0,1,0,12,12A12,12,0,0,0,84,116Zm88,0a12,12,0,1,0,12,12A12,12,0,0,0,172,116Zm60,12A104,104,0,0,1,79.12,219.82L45.07,231.17a16,16,0,0,1-20.24-20.24l11.35-34.05A104,104,0,1,1,232,128Zm-16,0A88,88,0,1,0,51.81,172.06a8,8,0,0,1,.66,6.54L40,216,77.4,203.53a7.85,7.85,0,0,1,2.53-.42,8,8,0,0,1,4,1.08A88,88,0,0,0,216,128Z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-gray-900 dark:text-white">Talleres</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Talleres educativos sobre diversos temas de salud
                                    mental y estrategias de afrontamiento.</p>
                            </div>
                        </div>

                    </div>
                </section>
                            <div
                                class="bg-background-light dark:bg-background-dark border border-gray-200 dark:border-gray-700 rounded-lg p-6 flex flex-col items-center text-center transition-shadow hover:shadow-lg">
                                <div class="bg-primary/10 dark:bg-primary/20 rounded-full p-4">
                                    <svg class="text-primary" fill="currentColor" height="32px" viewBox="0 0 256 256" width="32px"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M140,128a12,12,0,1,1-12-12A12,12,0,0,1,140,128ZM84,116a12,12,0,1,0,12,12A12,12,0,0,0,84,116Zm88,0a12,12,0,1,0,12,12A12,12,0,0,0,172,116Zm60,12A104,104,0,0,1,79.12,219.82L45.07,231.17a16,16,0,0,1-20.24-20.24l11.35-34.05A104,104,0,1,1,232,128Zm-16,0A88,88,0,1,0,51.81,172.06a8,8,0,0,1,.66,6.54L40,216,77.4,203.53a7.85,7.85,0,0,1,2.53-.42,8,8,0,0,1,4,1.08A88,88,0,0,0,216,128Z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-gray-900 dark:text-white">Talleres</h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">Talleres educativos sobre diversos temas de salud
                                    mental y estrategias de afrontamiento.</p>
                            </div>
                        </div>

                    </div>
                </section>
                
                <!-- ══════════════════════════════════════════════════════════════════
                    SECCIÓN 10: CONOCER AL EQUIPO
                    ══════════════════════════════════════════════════════════════════
                    Sección que presenta a los profesionales de la plataforma
                    - Fondo ligeramente coloreado para diferenciación visual
                    - Card con imagen, nombre y especialidad del psicólogo
                    - Efecto hover de zoom en la imagen (scale-105)
                    - Actualmente muestra a Dra. Melina Larrota como psicóloga principal
                    ═══════════════════════════════════════════════════════════════════ -->
                <section class="py-16 sm:py-24 bg-background-light dark:bg-gray-900/50">
                    <div class="container mx-auto px-6">
                        <!-- HEADER DE LA SECCIÓN -->
                        <div class="text-center max-w-3xl mx-auto">
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">Conoce a nuestro equipo
                                comprometido</h2>
                            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                                Nuestro equipo de psicólogos experimentados está dedicado a brindar una atención efectiva en un entorno
                                seguro y de apoyo.

                            </p>
                        </div>
                        
                        <!-- TARJETA DE PSICÓLOGO -->
                        <!-- max-w-md: Ancho máximo de 28rem (es el tamaño típico de card) -->
                        <!-- group: Permite efectos de hover sobre todos los elementos hijo -->
                        <div class="mt-12 flex justify-center">
                            <div class="group max-w-md w-full">
                                <!-- IMAGEN DEL PSICÓLOGO CON EFECTO HOVER -->
                                <!-- overflow-hidden: Recorta la imagen al radio del borde -->
                                <!-- group-hover:scale-105: Efecto zoom al pasar el ratón -->
                                <div class="overflow-hidden rounded-lg">
                                    <div
                                        class="w-full bg-center bg-no-repeat aspect-square bg-cover rounded-lg transition-transform duration-500 group-hover:scale-105"
                                        style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAhXzTO1QPifHDTeupswtwR16nco09B1Pj6ebK31u0HADGmMb1o0mHgMoyWjceNsH1Ng0R9cPHeUGxtx9vPtVN-zW92aF2nqIswOjNJMKXS44Qm8uGA9mjajDB1Acs16nDN_3VEdmZIuctY6EGc9CcC5oL7nj1awEgdS6uZbSXa1oh_jINa7PfkDyUYS4kq3g14yUW4kpMCm-uY4aSYdVVJK9OztjjdsAsQQtqJQzIvl3mti46mI7mWKD5-SrNShW0VPZC1hSoCLi0");'>
                                    </div>
                                </div>
                                
                                <!-- INFORMACIÓN DEL PSICÓLOGO -->
                                <div class="mt-6 text-center">
                                    <!-- Nombre del profesional en tamaño prominente -->
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Dra. Melina Larrota</p>
                                    <!-- Especialidad y descripción de la psicóloga -->
                                    <p class="text-base text-gray-600 dark:text-gray-400 mt-2">Psicóloga clínica especializada en salud mental de adolescentes y adultos jóvenes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- ══════════════════════════════════════════════════════════════════
                    SECCIÓN 11: LLAMADA A LA ACCIÓN FINAL (CTA)
                    ══════════════════════════════════════════════════════════════════
                    Última sección antes del footer con mensaje motivacional
                    Botón principal que redirecciona a la página de reserva de citas
                    - Efecto hover con escala y cambio de color
                    - Mensaje de acción: "Reserva tu primera cita"
                    ═══════════════════════════════════════════════════════════════════ -->
                <section class="py-16 sm:py-24">
                    <div class="container mx-auto px-6 text-center">
                        <!-- TÍTULO MOTIVACIONAL -->
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">¿Listo(a) para comenzar tu camino?
                        </h2>
                        
                        <!-- DESCRIPCIÓN -->
                        <p class="mt-4 text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                            Reserva tu primera cita hoy y da el primer paso hacia una versión más saludable y feliz de ti. Estamos
                            aquí para ayudarte.
                        </p>
                        
                        <!-- BOTÓN DE RESERVA -->
                        <!-- Redirecciona a View_Reserva.php para iniciar el proceso de reserva de cita -->
                        <div class="mt-8">
                            <a href="View_Reserva.php"
                                class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-12 px-8 bg-primary text-white text-base font-bold shadow-xl hover:bg-primary/90 transition-transform transform hover:scale-105 mx-auto">
                                <span class="truncate">Reserva tu primera cita</span>
                            </a>
                        </div>
                    </div>
                </section>
            </main>
            
            <!-- ══════════════════════════════════════════════════════════════════
                SECCIÓN 12: PIE DE PÁGINA (FOOTER)
                ══════════════════════════════════════════════════════════════════
                Sección final con información legal, navegación y copyright
                - Borde superior para separación visual
                - Tres columnas responsivas: logo/nombre, enlaces legales, copyright
                - Enlaces a Política de Privacidad y Términos y Condiciones
                
                ELEMENTOS:
                1. Logo + Nombre de empresa (izquierda)
                2. Enlaces de navegación legal (centro)
                3. Texto de copyright (derecha)
                ═══════════════════════════════════════════════════════════════════ -->
            <footer class="bg-background-light dark:bg-background-dark border-t border-gray-200 dark:border-gray-800">
                <div class="container mx-auto px-6 py-8">
                    <!-- CONTENEDOR PRINCIPAL DEL FOOTER -->
                    <!-- flex-col md:flex-row: Stack vertical en móvil, horizontal en desktop -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        
                        <!-- BRANDING DEL FOOTER (LOGO + NOMBRE) -->
                        <!-- Mantiene consistencia con el logo del header -->
                        <div class="flex items-center gap-2">
                            <!-- SVG del logo Vision -->
                            <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                                    fill="currentColor"></path>
                            </svg>
                            <!-- Nombre de la empresa en footer -->
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Vision</span>
                        </div>
                        
                        <!-- NAVEGACIÓN LEGAL -->
                        <!-- flex-wrap: Permite que los items salten a la siguiente línea en pantallas pequeñas -->
                        <!-- gap-x-6 gap-y-2: Espaciado horizontal entre items y vertical si se envuelven -->
                        <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
                            <!-- Enlace a Política de Privacidad -->
                            <a class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary" href="Politica.php"><button>Politica de privacidad</button></a>

                            <!-- Enlace a Términos y Condiciones -->
                            <a class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary" href="Terminos.php"><button>Terminos y
                                    condiciones</button></a>
                        </nav>
                        
                        <!-- TEXTO DE COPYRIGHT -->
                        <!-- Indica derechos de autor y año -->
                        <p class="text-sm text-gray-500 dark:text-gray-400">© 2025 Vision. Todos los derechos reservados.</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

</body>

</html>

<!-- ════════════════════════════════════════════════════════════════════════════════
     SECCIÓN 13: SCRIPTS EXTERNOS Y LIBRERIAS JAVASCRIPT
     ════════════════════════════════════════════════════════════════════════════════
     Script que carga al final del documento para no bloquear la carga de la página
     - auth.js: Archivo de autenticación local que manejará redirecciones después del login
                Este script probablemente valida la sesión o redirige automáticamente
                si el usuario ya está autenticado (basado en el estado de $Loggeado)
     ════════════════════════════════════════════════════════════════════════════════ -->
<script src="auth.js"></script>