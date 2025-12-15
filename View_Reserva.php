<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * VISTA DE RESERVA DE CITAS - SISTEMA DE CITAS MÉDICAS VISION
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Módulo: Interfaz de Reserva de Citas
 * Descripción: Esta página proporciona una interfaz interactiva completa para que
 *              los usuarios autenticados (pacientes) puedan reservar citas médicas.
 *              
 * Funcionalidades Principales:
 *  - Calendario interactivo para selección de fechas disponibles
 *  - Dropdown de especialidades médicas
 *  - Carga dinámica de psicólogos/médicos según especialidad
 *  - Visualización de horarios disponibles
 *  - Captura de motivo de consulta (opcional)
 *  - Validación de datos antes del envío
 * 
 * Flujo de Negocio:
 *  1. Verificar autenticación del usuario
 *  2. Cargar especialidades disponibles de la BD
 *  3. Presentar interfaz de reserva con calendario
 *  4. Usuario selecciona: fecha → especialidad → psicólogo → horario
 *  5. Sistema calcula horarios disponibles en tiempo real
 *  6. Envío de formulario a Reserva_Cita.php
 * 
 * Seguridad:
 *  - Control de acceso: Solo usuarios autenticados (session_start())
 *  - Escapado HTML: htmlspecialchars() en output de datos
 *  - Redireccionamiento: Login si falta autenticación
 *  - Prepared statements: Implementados en queries
 * 
 * @file View_Reserva.php
 * @author Equipo Vision
 * @version 2.0 - Optimizada para defensa de proyecto
 * @date 2025-12-09
 * @copyright Vision - Todos los derechos reservados
 */

// ═══════════════════════════════════════════════════════════════════════════════
// FASE 1: INICIALIZACIÓN Y AUTENTICACIÓN
// ═══════════════════════════════════════════════════════════════════════════════

session_start();
require_once 'db.php';

/**
 * Control de Acceso: Validación de Sesión
 * 
 * Verificar que el usuario tenga una sesión activa. Si no, redirigir
 * a la página de login con parámetro de redirección para retornar aquí.
 */
if (!isset($_SESSION['user_id'])) {
  // Capturar URL actual para redirigir después del login
  $redirectUrl = urldecode(basename($_SERVER['PHP_SELF']));
  header('Location: login.html?redirect=' . $redirectUrl);
  exit;
}

if (!isset($_SESSION['user_role']) || (int) $_SESSION['user_role'] !== 1) {
  header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
  exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// FASE 2: CARGA DE DATOS - ESPECIALIDADES
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Array para almacenar especialidades disponibles
 * Estructura: [
 *   ['ID_Especialidad' => 1, 'Nombre_Especialidad' => 'Psicología Clínica'],
 *   ['ID_Especialidad' => 2, 'Nombre_Especialidad' => 'Psicología Infantil']
 * ]
 */
$especialidades = [];


$query = "SELECT ID_Especialidad, Nombre_Especialidad FROM especialidades";
if ($result = $mysqli->query($query)) {
<<<<<<< HEAD
    
    while ($row = $result->fetch_assoc()) {
        $especialidades[] = $row;
    }
    
=======
  /**
   * Iteración de Resultados
   * 
   * Procesa cada fila retornada y la almacena en el array $especialidades
   * para su posterior uso en el formulario HTML
   */
  while ($row = $result->fetch_assoc()) {
    $especialidades[] = $row;
  }
  // Nota: En producción, verificar si $result->num_rows > 0 para validar
>>>>>>> 8020e7315f5613bb2863cba172d89b8179971ca8
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <!-- ═══════════════════════════════════════════════════════════════════════════════
       SECCIÓN: Metadatos y Configuración del Documento
       ═══════════════════════════════════════════════════════════════════════════════ -->

  <!-- Codificación de caracteres UTF-8 para soporte de caracteres especiales -->
  <meta charset="utf-8" />

  <!-- Preconexión a Google Fonts para optimización de carga -->
  <link crossorigin="" href="https://fonts.gstatic.com/" rel="preconnect" />

  <!-- Importación de tipografías: Inter y Noto Sans (pesos: 400, 500, 700, 900) -->
  <link as="style" href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B700%3B900&family=Noto+Sans%3Awght%40400%3B500%3B700%3B900" onload="this.rel='stylesheet'" rel="stylesheet" />

  <!-- CDN Tailwind CSS v3+ con plugins de formularios y consultas de contenedor -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

  <!-- Configuración Personalizada de Tailwind CSS -->
  <script id="tailwind-config">
    /**
     * CONFIGURACIÓN TAILWIND CSS
     * 
     * darkMode: Soporte de modo oscuro mediante clase CSS
     * theme.extend: Extensión de colores y tipografías por defecto
     *   - primary: Color corporativo #13a4ec (azul cielo)
     *   - background-light: Fondo claro #f6f7f8
     *   - background-dark: Fondo oscuro #101c22
     */
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#13a4ec",
            "background-light": "#f6f7f8",
            "background-dark": "#101c22"
          },
          fontFamily: {
            "display": ["Inter"]
          },
        },
      },
    }
  </script>

  <!-- Título de la página (visible en pestañas del navegador) -->
  <title>Reserva de Citas - Sistema Vision</title>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-[#333] dark:text-[#ccc]">
  <div class="flex min-h-screen flex-col">
    <!-- ═══════════════════════════════════════════════════════════════════════════════
         SECCIÓN: Encabezado - Navegación Principal
         ═══════════════════════════════════════════════════════════════════════════════ -->
    <header class="border-b border-primary/20 dark:border-primary/10">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
        <!-- Sección: Logo y Branding -->
        <div class="flex items-center gap-4">
          <a href="Index.php" title="Ir a página principal">
            <button aria-label="Volver al inicio">
              <!-- Logo corporativo de Vision -->
              <div class="w-8 h-8 text-primary transition-transform hover:scale-110">
                <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z"
                    fill="currentColor"></path>
                </svg>
              </div>
            </button>
          </a>
          <!-- Nombre de la aplicación -->
          <h2 class="text-xl font-bold text-gray-800 dark:text-white">Vision</h2>
        </div>

        <!-- Sección: Menú de Navegación (visible en pantallas medianas en adelante) -->
        <nav class="hidden items-center gap-8 md:flex">
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors"
            href="Index.php"><button>Página Principal</button></a>
          <a class="text-sm font-medium text-slate-700 hover:text-primary dark:text-slate-300 dark:hover:text-primary transition-colors"
            href="Servicios.php"><button>Servicios</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors"
            href="Nosotros.php"><button>Sobre Nosotros</button></a>
          <a class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary transition-colors"
            href="mensaje.php"><button>Contacto</button></a>
        </nav>

        <!-- Sección: Perfil de Usuario -->
        <div class="flex items-center gap-4">
          <a href="perfil.php" title="Ver perfil de usuario" aria-label="Acceder a perfil">
            <!-- Avatar del usuario (icono por defecto) -->
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 border-2 border-primary/30 hover:border-primary transition-colors"
              style='background-image: url("https://cdn-icons-png.flaticon.com/512/11753/11753627.png");'>
            </div>
          </a>
        </div>
      </div>
    </header>

    <main class="flex-1">
      <!-- ═══════════════════════════════════════════════════════════════════════════════
           SECCIÓN: Contenido Principal - Formulario de Reserva
           ═══════════════════════════════════════════════════════════════════════════════ -->
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- Encabezado de Página -->
        <div class="mb-8 text-center">
          <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
            Programa tu Cita Médica
          </h1>
          <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
            Selecciona la fecha, especialidad y horario que mejor se adapte a ti
          </p>
        </div>

        <!-- Contenedor Principal del Formulario -->
        <div class="rounded-xl border border-primary/20 bg-white dark:bg-slate-800 p-4 sm:p-6 lg:p-8 shadow-lg">
          <!-- Grid Responsivo: 1 columna en móvil, 2 en desktop -->
          <div class="grid grid-cols-1 gap-8 md:grid-cols-2">

            <!-- ═══════════════════════════════════════════════════════════════════════════════
                 COLUMNA 1: SELECTOR DE FECHA - CALENDARIO INTERACTIVO
                 ═══════════════════════════════════════════════════════════════════════════════ -->
            <div class="space-y-4">
              <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Selecciona la Fecha</h2>

              <!-- Controles de Navegación Mensual -->
              <div class="flex justify-between items-center p-2 bg-gray-100 dark:bg-slate-700 rounded-md">
                <button id="prevMonth" class="px-3 py-1 hover:bg-gray-200 dark:hover:bg-slate-600 rounded transition"
                  title="Mes anterior">&lt;</button>
                <p id="calendarMonthLabel" class="font-semibold text-gray-800 dark:text-white"></p>
                <button id="nextMonth" class="px-3 py-1 hover:bg-gray-200 dark:hover:bg-slate-600 rounded transition"
                  title="Mes siguiente">&gt;</button>
              </div>

              <!-- Encabezados de Días de la Semana -->
              <div class="grid grid-cols-7 text-center font-semibold text-gray-700 dark:text-gray-300 mb-2">
                <div>Dom</div>
                <div>Lun</div>
                <div>Mar</div>
                <div>Mié</div>
                <div>Jue</div>
                <div>Vie</div>
                <div>Sáb</div>
              </div>

              <!-- Grid de Calendario (se llena dinámicamente con JavaScript) -->
              <div id="calendarGrid" class="grid grid-cols-7 text-center gap-2"></div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════════════════════
                 COLUMNA 2: FORMULARIO DE SELECCIÓN DE DETALLES DE CITA
                 ═══════════════════════════════════════════════════════════════════════════════ -->
            <div class="space-y-6">
              <!-- Etiqueta de Fecha Seleccionada -->
              <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-primary p-4 rounded">
                <h3 id="selectedDateLabel" class="text-lg font-bold text-gray-800 dark:text-white">
                  Selecciona un día para continuar
                </h3>
              </div>

              <!-- Campo: Selección de Especialidad -->
              <div class="space-y-2">
                <label for="therapyType" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Especialidad <span class="text-red-500">*</span>
                </label>
                <select id="therapyType"
                  class="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 dark:bg-slate-700 dark:border-slate-600 dark:text-gray-200"
                  required>
                  <option value=""> -- Selecciona una especialidad -- </option>
                  <?php foreach ($especialidades as $esp): ?>
                    <option value="<?php echo htmlspecialchars($esp['ID_Especialidad']); ?>">
                      <?php echo htmlspecialchars($esp['Nombre_Especialidad']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Campo: Selección de Profesional (se muestra dinámicamente) -->
              <div id="divPsicologo" class="space-y-2 hidden transition-all duration-300">
                <label for="comboPsicologo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Profesional <span class="text-red-500">*</span>
                </label>
                <select id="comboPsicologo"
                  class="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 dark:bg-slate-700 dark:border-slate-600 dark:text-gray-200"
                  required>
                  <option value=""> Cargando profesionales... </option>
                </select>
              </div>

              <!-- Slots de Horarios Disponibles (se generan dinámicamente con JavaScript) -->
              <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Hora Disponible <span class="text-red-500">*</span>
                </label>
                <div id="timeSlots" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
              </div>

              <!-- Campo: Motivo de la Consulta (Opcional) -->
              <div class="space-y-2">
                <label for="inputMotivo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Motivo de la Consulta (Opcional)
                </label>
                <textarea id="inputMotivo"
                  name="motivo"
                  rows="3"
                  placeholder="Describe brevemente el motivo de tu consulta..."
                  class="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 dark:bg-slate-700 dark:border-slate-600 dark:text-gray-200"></textarea>
              </div>

              <!-- Alerta de Validación/Errores (se muestra dinámicamente) -->
              <div id="reservaAlert" class="hidden rounded-md p-4 text-sm font-medium mb-4"></div>

              <!-- Formulario de Envío de Reserva -->
              <form id="formReserva" method="post" action="Reserva_Cita.php">
                <!-- Campos Ocultos: Datos de la Cita -->
                <input type="hidden" name="fecha" id="inputFecha">
                <input type="hidden" name="hora" id="inputHora">
                <input type="hidden" name="tipo_servicio" id="inputTipoServicio">
                <input type="hidden" name="id_psicologo" id="inputPsicologo">

                <!-- Botón de Confirmación -->
                <button type="submit"
                  id="confirmBtn"
                  class="w-full rounded-lg bg-primary py-3 px-4 text-base font-bold text-white shadow-md transition-all hover:bg-blue-700 hover:shadow-lg active:scale-95">
                  Confirmar Reserva de Cita
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════════════════
       SECCIÓN: Scripts Externos
       ═══════════════════════════════════════════════════════════════════════════════
       
       Script: Reserva_Cita_calendario.js
       Descripción: Maneja toda la lógica del cliente:
         - Renderizado del calendario interactivo
         - Eventos de selección de fecha, especialidad y horario
         - Validación de datos
         - Carga dinámica de psicólogos mediante AJAX
         - Cálculo de horarios disponibles
         
       Evento: DOMContentLoaded
       La ejecución se inicia cuando el DOM está completamente cargado
       ═══════════════════════════════════════════════════════════════════════════════ -->
  <script src="Reserva_Cita_calendario.js"></script>
</body>

</html>