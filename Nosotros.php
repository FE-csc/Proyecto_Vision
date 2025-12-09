<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: Nosotros.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: Página de información sobre la Dra. Melina Larrota
// Presenta perfil profesional, enfoque terapéutico, información clínica e imágenes
// Incluye galería de fotos de la clínica y CTA para reservar consulta
// FUNCIONALIDAD: Renderizado estático con contenido informativo + nav + footer
// DEPENDENCIAS: auth.js (validación sesión), View_Reserva.php (reserva citas)
// ROLES AUTORIZADOS: Todos (público + autenticados)
// MÉTODOS: GET (mostrar página)
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
session_start();
// Variable booleana para verificar autenticación (mostrar login o avatar)
$Loggeado = isset($_SESSION['user_id']);
// ──────────────────────────────────────────────────────────────────────────────
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- ──────────────────────────────────────────────────────────────────────────
       SECCIÓN 2: METADATOS Y CONFIGURACIÓN DEL DOCUMENTO
       ────────────────────────────────────────────────────────────────────────── -->
  <!-- UTF-8: Soporte para caracteres especiales (acentos, ñ, etc.) -->
  <meta charset="utf-8" />
  <!-- Viewport: Responsive design y escalado automático en dispositivos móviles -->
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <!-- Título de la página en pestaña del navegador -->
  <title>Vision Nosotros</title>
  <!-- Tailwind CSS CDN con plugins para formularios y media queries -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <!-- Preconectar a Google Fonts para mejorar velocidad de carga -->
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <!-- Cargar font family Inter en pesos 400-900 para tipografía consistente -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap"
    rel="stylesheet" />
  <!-- ──────────────────────────────────────────────────────────────────────────
       SECCIÓN 3: CONFIGURACIÓN TAILWIND CSS
       ────────────────────────────────────────────────────────────────────────── -->
  <script>
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
            display: ["Inter"],
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
       SECCIÓN 4: ESTILOS PERSONALIZADOS
       ────────────────────────────────────────────────────────────────────────── -->
  <style>
    /* Material Symbols Outlined: Iconografía de Google (aunque no se usa en esta página) */
    .material-symbols-outlined {
      /* font-variation-settings: Configurar variables tipográficas de Material Symbols */
      /* FILL: 1 (relleno sólido), wght: 400 (peso normal), GRAD: 0, opsz: 24 (tamaño) */
      font-variation-settings: "FILL" 1, "wght" 400, "GRAD" 0, "opsz" 24;
    }
  </style>
  <!-- Cargar iconos Material Symbols de Google -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>


<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
  <!-- ──────────────────────────────────────────────────────────────────────────
       SECCIÓN 5: ESTRUCTURA GENERAL (FLEX CONTAINER)
       ────────────────────────────────────────────────────────────────────────── -->
  <!-- div.flex con flex-col: Layout vertical (header, main, footer) -->
  <!-- min-h-screen: Altura mínima de pantalla completa (100vh) -->
  <div class="flex h-auto min-h-screen w-full flex-col">
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 6: BARRA DE NAVEGACIÓN (HEADER)
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- sticky top-0 z-50: Fija en la parte superior, encima de todo -->
    <!-- bg-background-light/80: Fondo semi-transparente (80% de opacidad) -->
    <!-- backdrop-blur-sm: Efecto blur detrás del header (glassmorphism) -->
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
      <div class="container mx-auto flex items-center justify-between whitespace-nowrap px-6 py-4">
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 7: LOGO Y MARCA
             ────────────────────────────────────────────────────────────────────────── -->
        <!-- Logo y título de la marca -->
        <div class="flex items-center gap-3">
          <!-- Botón logo enlazado a Index.php (página principal) -->
          <a href="Index.php">
            <button>
              <!-- Logo SVG: Círculo con símbolo de Vision en azul primario -->
              <div class="w-8 h-8 text-primary">
                <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                    fill="currentColor"></path>
                </svg>
              </div>
            </button>
          </a>
          <!-- Nombre de la clínica -->
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Vision</h2>
        </div>
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 8: MENÚ DE NAVEGACIÓN (OCULTO EN MÓVIL, VISIBLE EN DESKTOP)
             ────────────────────────────────────────────────────────────────────────── -->
        <!-- hidden md:flex: Oculto en móvil, visible en pantallas md y mayores -->
        <nav class="hidden md:flex items-center gap-8">
          <!-- Enlaces de navegación a secciones principales -->
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Index.php"><button>Pagina Principal</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Servicios.php"><button>Servicios</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="mensaje.php"><button>Contacto</button></a>
        </nav>
        
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 9: BOTÓN DE AUTENTICACIÓN (LOGIN / AVATAR PERFIL)
             ────────────────────────────────────────────────────────────────────────── -->
        <div class="flex items-center gap-4">
          <!-- Condicional PHP: Mostrar LOGIN si NO autenticado, AVATAR si autenticado -->
          <?php if (!$Loggeado): ?>
            <!-- Botón LOGIN: Enlaza a login.html para autenticación -->
            <a href="login.html">
              <button class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">
                Inicio de Sesion
              </button>
            </a>
          <?php else: ?>
            <!-- Avatar del usuario autenticado: Enlaza a perfil.php -->
            <a href="perfil.php">
              <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
                style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
              </div>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </header>
    
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 10: CONTENIDO PRINCIPAL (MAIN)
         ────────────────────────────────────────────────────────────────────────── -->
    <main class="flex-grow">
      <!-- max-w-5xl: Ancho máximo para legibilidad en pantallas grandes -->
      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 11: BANNER HERO CON IMAGEN DE FONDO
             ────────────────────────────────────────────────────────────────────────── -->
        <!-- relative: Contenedor posicionado para hijos absolutos -->
        <!-- h-[500px]: Altura fija de 500px -->
        <!-- overflow-hidden rounded-xl: Clip contenido y bordes redondeados -->
        <div class="relative mb-12 h-[500px] w-full overflow-hidden rounded-xl">
          <!-- Imagen de fondo de la Dra. Melina Larrota -->
          <div class="absolute inset-0 bg-cover bg-center"
            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAhXzTO1QPifHDTeupswtwR16nco09B1Pj6ebK31u0HADGmMb1o0mHgMoyWjceNsH1Ng0R9cPHeUGxtx9vPtVN-zW92aF2nqIswOjNJMKXS44Qm8uGA9mjajDB1Acs16nDN_3VEdmZIuctY6EGc9CcC5oL7nj1awEgdS6uZbSXa1oh_jINa7PfkDyUYS4kq3g14yUW4kpMCm-uY4aSYdVVJK9OztjjdsAsQQtqJQzIvl3mti46mI7mWKD5-SrNShW0VPZC1hSoCLi0");'>
          </div>
          <!-- Overlay gradiente: Oscurece imagen hacia abajo para mejor legibilidad del texto -->
          <!-- bg-gradient-to-t: Gradiente de arriba a abajo (to-top pero visual es al revés) -->
          <!-- from-black/60: Negro con 60% de opacidad en la parte inferior -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
          
          <!-- Contenido del banner: Posicionado en la esquina inferior izquierda -->
          <div class="relative flex h-full flex-col items-start justify-end p-8 text-white md:p-12">
            <!-- Título principal: Nombre de la doctora -->
            <h1 class="text-4xl font-black leading-tight tracking-tight md:text-5xl">Conoce Dra. Melina Larrota</h1>
            <!-- Descripción breve: Especialidad y enfoque -->
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-200 md:text-lg">
              Una psicóloga compasiva especializada en la salud mental de adolescentes y adultos jóvenes.
              La Dra. Melina Larrota crea un espacio seguro y de apoyo para que los jóvenes puedan explorar sus sentimientos y
              desarrollar estrategias de afrontamiento.
            </p>
            <!-- CTA: Botón para reservar consulta -->
            <a href="View_Reserva.php"
              class="mt-8 inline-flex cursor-pointer items-center justify-center overflow-hidden rounded-full bg-primary px-6 py-3 text-base font-bold text-white transition-transform hover:scale-105">
              <span class="truncate">Reservar una consulta</span>
            </a>
          </div>
        </div>
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 12: GRID CON CONTENIDO Y IMÁGENES LATERALES
             ────────────────────────────────────────────────────────────────────────── -->
        <!-- grid grid-cols-1 lg:grid-cols-3: 1 columna móvil, 3 columnas desktop -->
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-3 lg:gap-16">
          <!-- COLUMNA IZQUIERDA: Contenido textual (2 de 3 columnas en desktop) -->
          <div class="space-y-8 lg:col-span-2">
            <!-- ──────────────────────────────────────────────────────────────────────────
                 SECCIÓN 13A: INFORMACIÓN SOBRE LA DRA. MELINA LARROTA
                 ────────────────────────────────────────────────────────────────────────── -->
            <section>
              <h2 class="text-2xl font-bold text-slate-900 dark:text-white sm:text-3xl">Acerca de la Dra. Melina Larrota</h2>
              <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                La Dra. Melina Larrota posee un doctorado en psicología clínica y cuenta con más de 10 años de experiencia
                trabajando con jóvenes. Su enfoque es integrador, combinando la terapia cognitivo-conductual (TCC), la
                atención plena (mindfulness) y la psicología positiva para adaptar el tratamiento a las necesidades
                individuales de cada persona.
                Cree en empoderar a los jóvenes para que tomen el control de su bienestar mental y desarrollen
                resiliencia para el futuro.
              </p>
            </section>
            
            <!-- ──────────────────────────────────────────────────────────────────────────
                 SECCIÓN 13B: ENFOQUE TERAPÉUTICO
                 ────────────────────────────────────────────────────────────────────────── -->
            <section>
              <h2 class="text-2xl font-bold text-slate-900 dark:text-white sm:text-3xl">Nuestro enfoque</h2>
              <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                En Vision, entendemos que buscar ayuda puede resultar abrumador. Por eso, nos esforzamos por
                crear un entorno acogedor y libre de juicios, donde los jóvenes se sientan cómodos compartiendo sus
                experiencias.
                Nuestro enfoque es colaborativo: trabajamos junto a los jóvenes para establecer metas y desarrollar
                estrategias que funcionen para ellos.
                Cuando es apropiado, también involucramos a padres o tutores para asegurar un sistema de apoyo
                integral.
            </section>
            
            <!-- ──────────────────────────────────────────────────────────────────────────
                 SECCIÓN 13C: INFORMACIÓN CLÍNICA Y HORARIOS
                 ────────────────────────────────────────────────────────────────────────── -->
            <section>
              <h2 class="text-2xl font-bold text-slate-900 dark:text-white sm:text-3xl">Información clínica</h2>
              <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                Nuestra clínica está ubicada en una zona tranquila y accesible, diseñada para sentirse menos como una
                oficina tradicional y más como un espacio acogedor.
                Ofrecemos horarios de cita flexibles, incluyendo tardes y fines de semana, para adaptarnos a agendas
                ocupadas.
                También brindamos sesiones de terapia en línea para quienes prefieren recibir apoyo de forma remota.
              </p>
            </section>
          </div>
          
          <!-- ──────────────────────────────────────────────────────────────────────────
               SECCIÓN 14: GALERÍA DE IMÁGENES DE LA CLÍNICA (SIDEBAR)
               ────────────────────────────────────────────────────────────────────────── -->
          <!-- COLUMNA DERECHA: Imágenes de la clínica y ambiente (1 de 3 columnas en desktop) -->
          <div class="space-y-6">
            <!-- IMAGEN 1: Vista interior de la clínica (altura media - 256px) -->
            <!-- h-64: Altura de 256 píxeles -->
            <!-- rounded-lg: Bordes redondeados grandes -->
            <!-- bg-cover bg-center: Imagen ajustada y centrada -->
            <div class="h-64 w-full rounded-lg bg-cover bg-center"
              style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAnGJQrUObHWudqV4UiFHXtdZm5275Nf6UfqHtN5P_vnTIIypGa2fPrvQfy4zczWS6_fwy6ZrDwyj4aT5QU5hayhLk8Da2BGoqkGRHKhyVnN3DXG8nwQUVSz2z4_IW2XaV9wo4QZ4505HgkEn3VgXZocRVi-n5HQe9-Zb8bMkkB7fusryzPwNUaYquNGDKg4b5CKbt1yh-ttyyjIEk5zescGFg53nFDzktDxPgmtqfzlhqAT2kGr93INrS1iqF9dF_eXKpNEA0FNvw");'>
            </div>
            <!-- IMAGEN 2: Consultorio o área de terapia (altura mayor - 384px) -->
            <!-- h-96: Altura de 384 píxeles -->
            <div class="h-96 w-full rounded-lg bg-cover bg-center"
              style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDbxuH0eDBtDif7Xo9keuxOaou1g2vJC4OfScLjpZ7VXsCuGU6Qc1Ik-UGEz_HRFKNXFIq4O9nOfQbKp7jdSwcehn5yrtJRQgx4kG8bff-dwT7aImuL4_vA7Ln43zy4MHzQnDxZokGbtEHFjrCG9yG3AMBfHtLAx-evLbPZECAQntoI7synBTZ2kciXDBGHaWrG-XVUZxx9JI7vXb5HsU_XGy_Y-4_uTKADvuo-gzUrbfUUenD3pMkZNBUUnj2TwwCDRwBU-jWgkrc");'>
            </div>
          </div>
        </div>

      </div>
    </main>
    
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 15: PIE DE PÁGINA (FOOTER)
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- border-t: Borde superior para separación visual -->
    <!-- bg-background-light: Fondo claro (contrasta con main) -->
    <footer class="border-t border-slate-200 bg-background-light dark:border-slate-800 dark:bg-background-dark">
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- flex flex-col sm:flex-row: Columna en móvil, fila en desktop -->
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
          <!-- Enlaces a páginas legales -->
          <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
            <!-- Política de privacidad -->
            <a class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary" href="Politica.php"><button>Política de privacidad</button></a>
            <!-- Términos de servicio -->
            <a class="text-sm text-slate-600 hover:text-primary dark:text-slate-400 dark:hover:text-primary"
              href="Terminos.php">Términos de servicio</a>
          </div>
          <!-- Copyright -->
          <p class="text-sm text-slate-500 dark:text-slate-400">© 2025 Vision. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>

</body>

</html>

<!-- ════════════════════════════════════════════════════════════════════════════════
     SECCIÓN 16: SCRIPTS EXTERNOS
     ════════════════════════════════════════════════════════════════════════════════ -->
<!-- auth.js: Script para validación de sesión -->
<!-- Importado al final: Permite que el DOM cargue primero -->
<script src="auth.js"></script>

