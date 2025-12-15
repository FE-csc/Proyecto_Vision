<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: mensaje.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: Página de contacto con formulario para enviar mensajes
// Permite a usuarios autenticados enviar mensajes de consulta a través de enviar.php
// Incluye validación cliente-lado + servidor-lado con PHPMailer
// FUNCIONALIDAD: Renderizado de página + formulario HTML + validación JS + AJAX
// DEPENDENCIAS: auth.js (validación sesión), enviar.php (procesamiento con PHPMailer)
// ROLES AUTORIZADOS: Solo usuarios autenticados (redirige a login si no lo está)
// MÉTODOS: GET (mostrar página), POST (AJAX a enviar.php)
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
session_start();
// Variable booleana para verificar autenticación en PHP (mostrar/ocultar login)
$Loggeado = isset($_SESSION['user_id']);
// ──────────────────────────────────────────────────────────────────────────────
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <!-- ──────────────────────────────────────────────────────────────────────────
       SECCIÓN 2: METADATOS Y CONFIGURACIÓN DEL DOCUMENTO
       ────────────────────────────────────────────────────────────────────────── -->
  <meta charset="utf-8" />
  <title>Vision Contacto</title>
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <!-- ──────────────────────────────────────────────────────────────────────────
       SECCIÓN 3: CONFIGURACIÓN TAILWIND CSS
       ────────────────────────────────────────────────────────────────────────── -->
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
          borderRadius: { "DEFAULT": "0.5rem", "lg": "1rem", "xl": "1.5rem", "full": "9999px" },
        },
      },
    }
  </script>
  <!-- ──────────────────────────────────────────────────────────────────────────
       SECCIÓN 4: ESTILOS PERSONALIZADOS PARA FORMULARIOS
       ────────────────────────────────────────────────────────────────────────── -->
  <style>
    /* Colores de borde para inputs y textareas */
    .form-input,
    .form-textarea {
      border-color: #e2e8f0;
    }
    /* En tema oscuro: bordes más claros */
    .dark .form-input,
    .dark .form-textarea {
      border-color: #334155;
    }
    /* Estado focus: borde azul con sombra */
    .form-input:focus,
    .form-textarea:focus {
      border-color: #13a4ec;
      box-shadow: 0 0 0 1px #13a4ec;
    }
  </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-[#101c22] dark:text-[#f6f7f8]">
  <!-- ══════════════════════════════════════════════════════════════════════════════
       SECCIÓN 5: VALIDACIÓN DE SEGURIDAD Y FLUJO DE AUTENTICACIÓN
       ══════════════════════════════════════════════════════════════════════════════
       FLUJO DE SEGURIDAD:
       1. PHP: $Loggeado verifica sesión ($_SESSION['user_id'])
       2. HTML: Botón de login/perfil basado en $Loggeado
       3. JavaScript (auth.js): Redirige a login si no autenticado
       4. Formulario: Validación cliente-lado antes de envío
       5. AJAX: Envío a enviar.php (PHPMailer + respuesta JSON)
       ══════════════════════════════════════════════════════════════════════════════ -->
  <div class="flex flex-col min-h-screen">
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 6: BARRA DE NAVEGACIÓN (HEADER)
         ────────────────────────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-50 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
      <div class="container mx-auto flex items-center justify-between whitespace-nowrap px-6 py-4">
        <!-- Logo y branding -->
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
          <h2 class="text-xl font-bold"> Vision</h2>
        </div>
        <!-- Navegación principal (visible en desktop) -->
        <nav class="hidden md:flex items-center gap-8">
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Index.php"><button>Pagina Principal</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Nosotros.php"><button>Sobre nosotros</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary"
            href="Servicios.php"><button>Servicios</button></a>
        </nav>
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 7: VALIDACIÓN JAVASCRIPT (auth.js)
             ────────────────────────────────────────────────────────────────────────── -->
        <!-- Si auth.js detecta que NO está logueado, redirige a login.html con ?next=mensaje.php -->
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            try {
              if (window.Auth && typeof window.Auth.isLoggedIn === 'function') {
                if (!window.Auth.isLoggedIn()) {
                  var next = 'mensaje.php';
                  location.href = 'login.html?next=' + encodeURIComponent(next);
                }
              }
            } catch (e) {/* ignore */ }
          });
        </script>
        <!-- Elementos de usuario (derecha): login o avatar) -->
        <div class="flex items-center gap-4">
          <?php if (!$Loggeado): ?>
          <!-- Botón LOGIN si NO está autenticado -->
          <a href="login.html"><button
              class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full h-10 px-6 bg-primary text-white text-sm font-bold shadow-lg hover:bg-primary/90 transition-colors">Inicio de Sesion</button></a>
          <?php else: ?>
          <!-- Avatar del usuario si ESTÁ autenticado -->
          <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"
            style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
          </div>
          <?php endif; ?>
        </div>
    </header>
    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 8: CONTENIDO PRINCIPAL (MAIN)
         ────────────────────────────────────────────────────────────────────────── -->
    <main class="flex-grow container mx-auto px-6 py-16">
      <div class="max-w-4xl mx-auto">
        <!-- Header de la página -->
        <div class="text-center mb-12">
          <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Ponte en contacto</h1>
          <p class="text-lg text-black/60 dark:text-white/60 max-w-2xl mx-auto">Estamos aquí para apoyarte. Contáctanos
            si tienes alguna pregunta o para agendar una cita.</p>
        </div>
        <!-- ──────────────────────────────────────────────────────────────────────────
             SECCIÓN 9: GRID CON FORMULARIO E INFORMACIÓN DE CONTACTO
             ────────────────────────────────────────────────────────────────────────── -->
        <!-- grid: 1 columna móvil, 2 columnas desktop -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
          <!-- COLUMNA IZQUIERDA: FORMULARIO DE CONTACTO -->
          <div class="space-y-6">
            <h3 class="text-2xl font-bold">Formulario de contacto</h3>
            <!-- ──────────────────────────────────────────────────────────────────────────
                 SECCIÓN 10: FORMULARIO HTML CON VALIDACIÓN
                 ────────────────────────────────────────────────────────────────────────── -->
            <!-- id="contactForm": ID para JavaScript (getElementById en script)
                 novalidate: Desactiva validación HTML5 nativa para usar validación JS personalizada
                 class="space-y-6": Tailwind margin-bottom entre elementos
            -->
            <form id="contactForm" class="space-y-6" novalidate>

              <!-- ──────────────────────────────────────────────────────────────────────────
                   SECCIÓN 10A: ÁREA DE ALERTAS (MENSAJES DE ERROR/ÉXITO)
                   ────────────────────────────────────────────────────────────────────────── -->
              <!-- id="formAlert": Contenedor para mostrar mensajes de validación
                   role="status": ARIA role para screen readers (anunciar cambios automáticamente)
                   aria-live="polite": Anunciar cambios sin interrumpir lectura actual
                   class="hidden": Oculto por defecto (JS lo muestra con classList.remove('hidden'))
              -->
              <div id="formAlert" role="status" aria-live="polite" class="hidden rounded-md p-3 text-sm"></div>

              <!-- ──────────────────────────────────────────────────────────────────────────
                   SECCIÓN 10B: CAMPO DE NOMBRE
                   ────────────────────────────────────────────────────────────────────────── -->
              <!-- Validación: Mínimo 2 caracteres (validación JS en validateForm)
                   Accesibilidad: aria-required="true", aria-describedby vincula con descripción
                   sr-only: "screen reader only" - visible solo para lectores de pantalla
              -->
              <div>
                <label class="block text-sm font-medium mb-2" for="name">Nombre y apellidos</label>
                <input required name="name" aria-required="true" aria-describedby="nameHelp"
                  class="form-input w-full bg-background-light dark:bg-background-dark rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                  id="name" placeholder="Ingresa tu nombre" type="text" />
                <p id="nameHelp" class="sr-only">Tu nombre completo (requerido, mínimo 2 caracteres)</p>
              </div>

              <!-- ──────────────────────────────────────────────────────────────────────────
                   SECCIÓN 10C: CAMPO DE CORREO ELECTRÓNICO
                   ────────────────────────────────────────────────────────────────────────── -->
              <!-- type="email": Validación HTML5 (secundaria a JS)
                   Validación JS regex: /^[^@\s]+@[^@\s]+\.[^@\s]+$/
                     - ^[^@\s]+: Comienza con 1+ caracteres que NO sean @ ni espacios
                     - @: Debe contener exactamente una @
                     - [^@\s]+: Usuario (No @ ni espacios)
                     - \.: Punto literal (.)
                     - [^@\s]+$: Dominio (No @ ni espacios), termina así
              -->
              <div>
                <label class="block text-sm font-medium mb-2" for="email">Correo electronico</label>
                <input required name="email" aria-required="true" aria-describedby="emailHelp"
                  class="form-input w-full bg-background-light dark:bg-background-dark rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                  id="email" placeholder="Ingresa tu email" type="email" />
                <p id="emailHelp" class="sr-only">Correo electrónico válido en formato usuario@dominio.com (requerido)</p>
              </div>

              <!-- ──────────────────────────────────────────────────────────────────────────
                   SECCIÓN 10D: CAMPO DE TELÉFONO
                   ────────────────────────────────────────────────────────────────────────── -->
              <!-- type="tel": Tipo específico para números telefónicos
                   pattern="[0-9+\-()\s]{6,}": Validación HTML5
                     - [0-9+\-()\s]: Caracteres permitidos (dígitos, +, -, (, ), espacios)
                     - {6,}: Mínimo 6 caracteres
                   Ejemplo válidos: "123456", "+34 123 45 67", "(555) 123-4567"
              -->
              <div>
                <label class="block text-sm font-medium mb-2" for="phone">Numero de telefono</label>
                <input required name="phone" aria-required="true" aria-describedby="phoneHelp"
                  pattern="[0-9+\-()\s]{6,}"
                  class="form-input w-full bg-background-light dark:bg-background-dark rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                  id="phone" placeholder="Ingresa tu numero de telefono" type="tel" />
                <p id="phoneHelp" class="sr-only">Número de teléfono: 6+ dígitos. Acepta +, -, (, ), espacios. Ej: +34 123 45 67</p>
              </div>

              <!-- ──────────────────────────────────────────────────────────────────────────
                   SECCIÓN 10E: CAMPO DE MENSAJE
                   ────────────────────────────────────────────────────────────────────────── -->
              <!-- type="textarea": Área de texto para mensaje extendido
                   Validación: Mínimo 5 caracteres (validateForm en JS)
                   rows="5": Altura inicial de 5 líneas
                   placeholder: Instrucción para el usuario
              -->
              <div>
                <label class="block text-sm font-medium mb-2" for="message">Mensaje</label>
                <textarea required name="message" aria-required="true" aria-describedby="messageHelp"
                  class="form-textarea w-full bg-background-light dark:bg-background-dark rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                  id="message" placeholder="Como te podemos ayudar?" rows="5"></textarea>
                <p id="messageHelp" class="sr-only">Describe brevemente cómo podemos ayudarte (requerido, mínimo 5 caracteres)</p>
              </div>

              <!-- ──────────────────────────────────────────────────────────────────────────
                   SECCIÓN 10F: BOTÓN DE ENVÍO
                   ────────────────────────────────────────────────────────────────────────── -->
              <!-- id="submitBtn": Referencia JS para deshabilitar durante envío
                   type="submit": Dispara evento submit del formulario (capturado en JavaScript)
                   Estados:
                     - Default: "Enviar mensaje" (clickeable)
                     - Loading: Deshabilitado + aria-busy="true" (JS establece esto)
                     - Success: Verde con mensaje (JS manipula classList)
              -->
              <div>
                <button id="submitBtn"
                  class="w-full bg-primary text-white font-bold text-sm px-6 py-3 rounded-lg hover:bg-primary/90 transition-colors"
                  type="submit">
                  Enviar mensaje
                </button>
              </div>
            </form>
          </div>

          <!-- ──────────────────────────────────────────────────────────────────────────
               SECCIÓN 11: COLUMNA DERECHA - INFORMACIÓN DE CONTACTO Y MAPA
               ────────────────────────────────────────────────────────────────────────── -->
          <div class="space-y-8">
            <!-- LOCALIZACIÓN: Dirección física de la clínica -->
            <div>
              <h3 class="text-2xl font-bold mb-4">Nuestra localizacion</h3>
              <p class="text-black/60 dark:text-white/60 mb-2">Visita nuestra oficina en:</p>
              <!-- <address>: Elemento semántico para dirección
                   not-italic: Tailwind para remover el italic predeterminado de <address>
              -->
              <address class="not-italic text-black/80 dark:text-white/80">
                El Alto de Guadalupe<br />
                El Alto, 100m al Sur de Novacentro<br />
              </address>
            </div>

            <!-- MAPA INCRUSTADO: Google Maps o servicio de mapas
                 aspect-video: Tailwind para relación de aspecto 16:9
                 loading="lazy": Carga diferida del iframe (mejora performance)
                 allowfullscreen: Permite pantalla completa en el mapa
            -->
            <div class="w-full aspect-video rounded-xl overflow-hidden">
              <iframe
                class="w-full h-full rounded-xl"
                style="border:0; min-height:300px;"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Novacentro,+El+Alto+de+Guadalupe,+Costa+Rica&output=embed">
              </iframe>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- ──────────────────────────────────────────────────────────────────────────
         SECCIÓN 12: PIE DE PÁGINA (FOOTER)
         ────────────────────────────────────────────────────────────────────────── -->
    <!-- Footer: Información legal y copyright
         border-t: Borde superior para separación visual
         mt-16: Margen superior (separación del contenido principal)
    -->
    <footer class="border-t border-black/10 dark:border-white/10 mt-16">
      <div class="container mx-auto px-6 py-8 text-center text-black/60 dark:text-white/60">
        <!-- Enlaces a páginas legales -->
        <div class="flex justify-center gap-6 mb-4">
          <a class="text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary"
            href="Politica.php"><button>Politica de privacidad</button></a>
          <a class="text-sm hover:text-primary transition-colors" href="#">Terminos de servicio</a>
        </div>
        <!-- Copyright -->
        <p class="text-sm">© 2025 Vision. All rights reserved.</p>
      </div>
    </footer>
  </div>

  <!-- ══════════════════════════════════════════════════════════════════════════════
       SECCIÓN 13: SCRIPTS JAVASCRIPT - FORMULARIO Y VALIDACIÓN
       ══════════════════════════════════════════════════════════════════════════════ -->
  <script>
    /* ──────────────────────────────────────────────────────────────────────────
       IIFE (Immediately Invoked Function Expression): Crea ámbito aislado
       Propósito: Evitar contaminación del scope global con variables de formulario
       Beneficios: Variables privadas (form, alertBox, etc.) solo accesibles dentro
    */
    (function () {
      // ──────────────────────────────────────────────────────────────────────────
      // SECCIÓN 13A: REFERENCIAS AL DOM (Document Object Model)
      // ──────────────────────────────────────────────────────────────────────────
      /*
         getElementById() obtiene referencias a elementos del HTML
         Ventaja: Almacenarlas evita múltiples búsquedas DOM (mejora performance)
      */
      const form = document.getElementById('contactForm');
      const alertBox = document.getElementById('formAlert');

      // ──────────────────────────────────────────────────────────────────────────
      // SECCIÓN 13B: FUNCIÓN showAlert() - MOSTRAR MENSAJES TEMPORALES
      // ──────────────────────────────────────────────────────────────────────────
      /**
       * showAlert(message, type)
       * @param {string} message - Texto del mensaje a mostrar
       * @param {string} type - 'success' (verde) o 'error' (rojo). Default: 'success'
       * 
       * FUNCIONALIDAD:
       * 1. Remueve clase 'hidden' para mostrar el alertBox
       * 2. Establece el texto del mensaje
       * 3. Aplica estilos según tipo:
       *    - error: bg-red-50 + text-red-700
       *    - success: bg-primary (#13a4ec) + text-white
       * 4. Limpia timeout anterior (evita múltiples autoreinicializaciones)
       * 5. Configura timeout para ocultar automáticamente después de 5000ms (5s)
       * 
       * NOTA: showAlert._t almacena el ID del timeout actual en la función misma
       *       Esto permite cancelar el timeout anterior con clearTimeout()
       */
      function showAlert(message, type = 'success') {
        alertBox.classList.remove('hidden');
        alertBox.textContent = message;
        if (type === 'error') {
          alertBox.classList.remove('bg-primary', 'text-white');
          alertBox.classList.add('bg-red-50', 'text-red-700');
        } else {
          alertBox.classList.remove('bg-red-50', 'text-red-700');
          alertBox.classList.add('bg-primary', 'text-white');
        }

        // Auto-ocultar después de 5 segundos (5000 milisegundos)
        clearTimeout(showAlert._t);
        showAlert._t = setTimeout(() => {
          alertBox.classList.add('hidden');
        }, 5000);
      }

      // ──────────────────────────────────────────────────────────────────────────
      // SECCIÓN 13C: FUNCIÓN validateForm() - VALIDACIÓN DE CAMPOS
      // ──────────────────────────────────────────────────────────────────────────
      /**
       * validateForm(data)
       * @param {FormData} data - Objeto FormData con campos del formulario
       * @returns {Array} Array de mensajes de error (vacío si todo es válido)
       * 
       * VALIDACIONES REALIZADAS:
       * 
       * 1. NOMBRE: Debe existir y tener al menos 2 caracteres
       *    - data.get('name').trim().length >= 2
       *    - trim() elimina espacios antes/después
       * 
       * 2. EMAIL: Debe cumplir regex /^[^@\s]+@[^@\s]+\.[^@\s]+$/
       *    Breakdown de regex:
       *    - ^[^@\s]+     Comienza con 1+ caracteres (excepto @ y espacios)
       *    - @            Debe contener exactamente una @
       *    - [^@\s]+      Usuario (1+ caracteres excepto @ y espacios)
       *    - \.           Punto literal (.)
       *    - [^@\s]+$     Dominio (1+ caracteres excepto @ y espacios), termina aquí
       *    Ejemplos válidos: "user@example.com", "name.user@domain.co.uk"
       *    Ejemplos inválidos: "usuario@", "ejemplo@.com", "sin-arroba.com"
       * 
       * 3. TELÉFONO: Debe cumplir regex /^[0-9+\-()\s]{6,}$/
       *    Breakdown de regex:
       *    - ^[0-9+\-()\s]{6,}$  6 o más caracteres de: dígitos, +, -, (, ), espacios
       *    Ejemplos válidos: "123456", "+34 123-45-67", "(555) 123-4567", "555 1234567"
       *    Ejemplos inválidos: "12345" (menos de 6), "abc123" (letras no permitidas)
       * 
       * 4. MENSAJE: Debe existir y tener al menos 5 caracteres
       *    - data.get('message').trim().length >= 5
       */
      function validateForm(data) {
        const errors = [];
        if (!data.get('name') || data.get('name').trim().length < 2) {
          errors.push('Ingresa un nombre válido.');
        }
        const email = data.get('email') || '';

        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
          errors.push('Ingresa un correo electrónico válido.');
        }
        const phone = data.get('phone') || '';
        if (!/^[0-9+\-()\s]{6,}$/.test(phone)) {
          errors.push('Ingresa un número de teléfono válido (6+ dígitos).');
        }
        if (!data.get('message') || data.get('message').trim().length < 5) {
          errors.push('El mensaje debe tener al menos 5 caracteres.');
        }
        return errors;
      }

      // ──────────────────────────────────────────────────────────────────────────
      // SECCIÓN 13D: EVENT LISTENER - MANEJO DEL ENVÍO DEL FORMULARIO
      // ──────────────────────────────────────────────────────────────────────────
      /*
         addEventListener('submit'): Captura el evento cuando usuario hace click en button[type=submit]
         function(ev): Callback que recibe el evento
      */
      form.addEventListener('submit', function (ev) {
        // ev.preventDefault(): Cancela el envío predeterminado (no recarga la página)
        ev.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        
        // ESTADO LOADING: Deshabilitar botón para evitar envíos duplicados
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-busy', 'true');  // Indica a screen readers que está procesando

        // Crear FormData del formulario (captura todos los campos automáticamente)
        const formData = new FormData(form);
        
        // Ejecutar validación JavaScript
        const errors = validateForm(formData);

        // SI HAY ERRORES: Mostrar primero, luego habilitar botón nuevamente
        if (errors.length) {
          showAlert(errors.join(' '), 'error');
          submitBtn.disabled = false;
          submitBtn.removeAttribute('aria-busy');
          return;  // Detener ejecución aquí
        }

        // ──────────────────────────────────────────────────────────────────────────
        // SECCIÓN 13E: ENVÍO AJAX - FETCH A enviar.php
        // ──────────────────────────────────────────────────────────────────────────
        /*
           fetch(): API moderna para peticiones HTTP (reemplaza XMLHttpRequest)
           Ventajas: Sintaxis más limpia, Promises, mejor manejo de errores
        */
        fetch("enviar.php", {
          method: "POST",      // Método HTTP: POST (envía datos en body)
          body: new FormData(form)  // Cuerpo: FormData serializado automáticamente
        })
        // THEN 1: Procesar respuesta y convertir a JSON
        .then(res => res.json())
        // THEN 2: Procesar datos JSON de la respuesta
        .then(data => {
            // data.ok: Campo booleano establecido por enviar.php
            if (data.ok) {
                // ÉXITO: Mostrar mensaje de confirmación en verde
                showAlert("Mensaje enviado correctamente");
                form.reset();  // Limpiar todos los campos del formulario
            } else {
                // ERROR DE SERVIDOR: Mostrar error específico retornado por PHP
                showAlert("Error: " + data.error, "error");
            }
        })
        // CATCH: Errores de red o conexión
        .catch(() => showAlert("No se pudo conectar con el servidor", "error"))
        // FINALLY: Limpiar estado después de cualquier resultado
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.removeAttribute('aria-busy');
        });
      });

    })();  // Fin de la IIFE: Se ejecuta inmediatamente
  </script>

</body>

</html>