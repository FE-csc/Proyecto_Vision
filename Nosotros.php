<?php
session_start();
$Loggeado = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Vision Nosotros</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap"
    rel="stylesheet" />
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#13a4ec",
            "background-light": "#f6f7f8",
            "background-dark": "#101c22",
          },
          fontFamily: {
            display: ["Inter"],
          },
          borderRadius: {
            DEFAULT: "0.5rem",
            lg: "1rem",
            xl: "1.5rem",
            full: "9999px"
          },
        },
      },
    };
  </script>
  <style>
    .material-symbols-outlined {
      font-variation-settings: "FILL" 1, "wght" 400, "GRAD" 0, "opsz" 24;
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>


<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
  <div class="flex h-auto min-h-screen w-full flex-col">
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
      <div class="container mx-auto flex items-center justify-between whitespace-nowrap px-6 py-4">
        <div class="flex items-center gap-3">
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
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Vision</h2>
        </div>
        <nav class="hidden md:flex items-center gap-8">
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Index.php"><button>Pagina Principal</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Servicios.php"><button>Servicios</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="mensaje.php"><button>Contacto</button></a>
        </nav>
        <div class="flex items-center gap-4">
          <?php if (!$Loggeado): ?>
            <a href="login.html">
              <button class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">
                Inicio de Sesion
              </button>
            </a>
          <?php else: ?>
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
      <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="relative mb-12 h-[500px] w-full overflow-hidden rounded-xl">
          <div class="absolute inset-0 bg-cover bg-center"
            style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAhXzTO1QPifHDTeupswtwR16nco09B1Pj6ebK31u0HADGmMb1o0mHgMoyWjceNsH1Ng0R9cPHeUGxtx9vPtVN-zW92aF2nqIswOjNJMKXS44Qm8uGA9mjajDB1Acs16nDN_3VEdmZIuctY6EGc9CcC5oL7nj1awEgdS6uZbSXa1oh_jINa7PfkDyUYS4kq3g14yUW4kpMCm-uY4aSYdVVJK9OztjjdsAsQQtqJQzIvl3mti46mI7mWKD5-SrNShW0VPZC1hSoCLi0");'>
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
          <div class="relative flex h-full flex-col items-start justify-end p-8 text-white md:p-12">
            <h1 class="text-4xl font-black leading-tight tracking-tight md:text-5xl">Conoce Dr. Melina Larrota</h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-200 md:text-lg">
              Una psicóloga compasiva especializada en la salud mental de adolescentes y adultos jóvenes.
              La Dra. Sharma crea un espacio seguro y de apoyo para que los jóvenes puedan explorar sus sentimientos y
              desarrollar estrategias de afrontamiento.

            </p>
            <a href="View_Reserva.php"
              class="mt-8 inline-flex cursor-pointer items-center justify-center overflow-hidden rounded-full bg-primary px-6 py-3 text-base font-bold text-white transition-transform hover:scale-105">
              <span class="truncate">Reservar una consulta</span>
            </a>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-3 lg:gap-16">
          <div class="space-y-8 lg:col-span-2">
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
            <section>

              <h2 class="text-2xl font-bold text-slate-900 dark:text-white sm:text-3xl">Nuestro enfoque </h2>
              <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                En Vision, entendemos que buscar ayuda puede resultar abrumador. Por eso, nos esforzamos por
                crear un entorno acogedor y libre de juicios, donde los jóvenes se sientan cómodos compartiendo sus
                experiencias.
                Nuestro enfoque es colaborativo: trabajamos junto a los jóvenes para establecer metas y desarrollar
                estrategias que funcionen para ellos.
                Cuando es apropiado, también involucramos a padres o tutores para asegurar un sistema de apoyo
                integral.
            </section>
            <section>
              <h2 class="text-2xl font-bold text-slate-900 dark:text-white sm:text-3xl">Inforfmacion clinica</h2>
              <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                Nuestra clínica está ubicada en una zona tranquila y accesible, diseñada para sentirse menos como una
                oficina tradicional y más como un espacio acogedor.
                Ofrecemos horarios de cita flexibles, incluyendo tardes y fines de semana, para adaptarnos a agendas
                ocupadas.
                También brindamos sesiones de terapia en línea para quienes prefieren recibir apoyo de forma remota.
              </p>
            </section>
          </div>
          <div class="space-y-6">
            <div class="h-64 w-full rounded-lg bg-cover bg-center"
              style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAnGJQrUObHWudqV4UiFHXtdZm5275Nf6UfqHtN5P_vnTIIypGa2fPrvQfy4zczWS6_fwy6ZrDwyj4aT5QU5hayhLk8Da2BGoqkGRHKhyVnN3DXG8nwQUVSz2z4_IW2XaV9wo4QZ4505HgkEn3VgXZocRVi-n5HQe9-Zb8bMkkB7fusryzPwNUaYquNGDKg4b5CKbt1yh-ttyyjIEk5zescGFg53nFDzktDxPgmtqfzlhqAT2kGr93INrS1iqF9dF_eXKpNEA0FNvw");'>
            </div>
            <div class="h-96 w-full rounded-lg bg-cover bg-center"
              style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDbxuH0eDBtDif7Xo9keuxOaou1g2vJC4OfScLjpZ7VXsCuGU6Qc1Ik-UGEz_HRFKNXFIq4O9nOfQbKp7jdSwcehn5yrtJRQgx4kG8bff-dwT7aImuL4_vA7Ln43zy4MHzQnDxZokGbtEHFjrCG9yG3AMBfHtLAx-evLbPZECAQntoI7synBTZ2kciXDBGHaWrG-XVUZxx9JI7vXb5HsU_XGy_Y-4_uTKADvuo-gzUrbfUUenD3pMkZNBUUnj2TwwCDRwBU-jWgkrc");'>
            </div>
          </div>
        </div>

      </div>
    </main>
    <footer class="border-t border-slate-200 bg-background-light dark:border-slate-800 dark:bg-background-dark">
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
          <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
            <a class="text-sm text-slate-600 hover:text-primary dark:text-slate-400 dark:hover:text-primary"
              <a class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary" href="Politica.php"><button>Politica de privacidad</button></a>
            <a class="text-sm text-slate-600 hover:text-primary dark:text-slate-400 dark:hover:text-primary"
              href="Terminos.php">Terminos de servicio</a>
          </div>
          <p class="text-sm text-slate-500 dark:text-slate-400">© 2025 Vision. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>

</body>

</html>

<script src="auth.js"></script>

