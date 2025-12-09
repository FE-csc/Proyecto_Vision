<?php
/**
 * ResumenPdf.php
 * ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: Interfaz para generar reportes de sesiones en formato PDF
 * 
 * FUNCIONALIDAD PRINCIPAL:
 * - Formulario para capturar detalles de la sesión clínica
 * - Información del paciente (nombre, edad, cédula)
 * - Fecha y hora de la sesión
 * - Campo de notas clínicas extenso para observaciones
 * - Generación de PDF enviando datos a generate_pdf.php
 * 
 * CAMPOS DE ENTRADA:
 * - patient_name: Nombre completo del paciente
 * - patient_age: Edad en años (0-150)
 * - patient_id: Número de cédula de identidad
 * - session_date: Fecha de la sesión (formato date)
 * - session_time: Hora de la sesión (formato time)
 * - session_notes: Notas y observaciones clínicas detalladas
 * 
 * VALIDACIÓN:
 * - Requiere autenticación de usuario ($SESSION['user_id'])
 * - Redirige a login.html si no está autenticado
 * - Redirección preserva página actual en parámetro 'redirect'
 * 
 * PROCESAMIENTO:
 * - Formulario POST a generate_pdf.php
 * - Abre PDF en nueva ventana (target="_blank")
 * 
 * DEPENDENCIAS:
 * - generate_pdf.php: Procesa datos y genera PDF
 * - Tailwind CSS: Estilos responsive
 * - Material Symbols: Iconografía
 * - JavaScript: Auto-población de fecha/hora actual
 * 
 * ACCESO: Psicólogos autenticados únicamente
 * ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
 */
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}
?>

<!DOCTYPE html>

<html class="light" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Vision - Informe de sesión</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#13a4ec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101c22",
                        "text-light": "#0d171b",
                        "text-dark": "#e7eff3",
                        "card-light": "#ffffff",
                        "card-dark": "#1a2a33",
                        "border-light": "#e7eff3",
                        "border-dark": "#334155"
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.5rem", "lg": "1rem", "xl": "1.5rem", "full": "9999px" },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24
        }
    </style>
</head>

<body class="font-display bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark">
    <div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
        <div class="flex h-full grow flex-col">
            <header
                class="bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm sticky top-0 z-50 border-b border-slate-200 dark:border-slate-800">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">


                        <div class="flex items-center gap-3">

                            <a id="perfilBtn"
                                class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary/10 dark:hover:bg-primary/20 font-medium cursor-pointer"
                                href="Doctor.php">
                                <span class="material-symbols-outlined">arrow_back_ios_new</span>
                            </a>


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
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Vision</h1>
                        </div>

                    </div>
                </div>
            </header>
            <main class="flex w-full flex-1 flex-col items-center px-4 py-8 sm:px-6 lg:px-8">
                <form method="post" action="generate_pdf.php" target="_blank">
                    <div class="w-full max-w-4xl space-y-8">
                        <!-- Page Heading and Patient Card -->
                        <section>
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <h1 class="text-4xl font-black leading-tight tracking-[-0.033em]">Informe de sesión</h1>
                            </div>
                        </section>
                        <!-- ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ -->
                        <!-- SECCIÓN 1: DETALLES DEL PACIENTE Y SESIÓN -->
                        <!-- Captura información básica del paciente y parámetros de la sesión -->
                        <!-- ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ -->
                        <section
                            class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark p-6 shadow-sm">
                            <h2 class="text-2xl font-bold leading-tight tracking-[-0.015em] pb-6">Detalles del paciente
                                y sesión</h2>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                <!-- Campo: Nombre del paciente (requerido, ocupa 2 columnas) -->
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label class="text-sm font-medium" for="patient-name">Nombre del paciente</label>
                                    <input name="patient_name"
                                        class="w-full rounded-md border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark py-2 pl-3 text-sm focus:border-primary focus:ring-primary"
                                        id="patient-name" type="text"
                                        placeholder="Ingrese el nombre completo del paciente" />
                                </div>
                                <!-- Campo: Edad del paciente en años (validación 0-150) -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium" for="patient-age">Edad</label>
                                    <input name="patient_age"
                                        class="w-full rounded-md border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark py-2 pl-3 text-sm focus:border-primary focus:ring-primary"
                                        id="patient-age" type="number" placeholder="Años" min="0" max="150" />
                                </div>
                                <!-- Campo: Cédula de identidad del paciente (ocupa 2 columnas) -->
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label class="text-sm font-medium" for="patient-id">Cédula de identidad</label>
                                    <input name="patient_id"
                                        class="w-full rounded-md border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark py-2 pl-3 text-sm focus:border-primary focus:ring-primary"
                                        id="patient-id" type="text" placeholder="Ingrese el número de cédula" />
                                </div>
                                <!-- Campo: Fecha de la sesión (input date con calendar icon) -->
                                <!-- Auto-poblado con fecha actual mediante JavaScript -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium" for="session-date">Fecha de la sesión</label>
                                    <div class="relative">
                                        <input name="session_date"
                                            class="w-full rounded-md border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark py-2 pl-3 pr-10 text-sm focus:border-primary focus:ring-primary"
                                            id="session-date" type="date" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span class="material-symbols-outlined text-slate-500 dark:text-slate-400"
                                                style="font-size: 20px;">calendar_today</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1" id="session-date-display"></p>
                                </div>
                                <!-- Campo: Hora de la sesión (input time con schedule icon) -->
                                <!-- Auto-poblado con hora actual mediante JavaScript -->
                                <div class="flex flex-col gap-2">
                                    <label class="text-sm font-medium" for="session-time">Hora de la sesión</label>
                                    <div class="relative">
                                        <input name="session_time"
                                            class="w-full rounded-md border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark py-2 pl-3 pr-10 text-sm focus:border-primary focus:ring-primary"
                                            id="session-time" type="time" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span class="material-symbols-outlined text-slate-500 dark:text-slate-400"
                                                style="font-size: 20px;">schedule</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Session Type eliminado -->
                                <!-- Keywords/Tags eliminado -->
                            </div>
                        </section>
                        <!-- ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ -->
                        <!-- SECCIÓN 2: NOTAS CLÍNICAS Y OBSERVACIONES -->
                        <!-- Editor de textarea para documentación detallada de la sesión -->
                        <!-- ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ -->
                        <section
                            class="rounded-xl border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark shadow-sm">
                            <h2 class="p-6 pb-2 text-2xl font-bold leading-tight tracking-[-0.015em]">Notas clínicas y
                                observaciones
                            </h2>
                            <div class="p-6 pt-4">
                                <textarea name="session_notes"
                                    class="w-full resize-y rounded-md border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark p-3 text-sm focus:border-primary focus:ring-primary focus:ring-1"
                                    id="session-notes"
                                    placeholder="Ingrese notas detalladas sobre el contenido de la sesión, progreso del paciente, observaciones y plan de tratamiento aquí..."
                                    rows="12"></textarea>
                            </div>
                        </section>
                        <!-- ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ -->
                        <!-- SECCIÓN 3: BOTONES DE ACCIÓN -->
                        <!-- Cancelar: Vuelve a la página anterior del navegador -->
                        <!-- Generar PDF: Envía formulario a generate_pdf.php en nueva ventana -->
                        <!-- ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════ -->
                    </div>
                    <section class="flex flex-col items-center gap-4 sm:flex-row sm:justify-end">
                        <button type="button" onclick="history.back()"
                            class="w-full rounded-lg px-6 py-3 text-sm font-semibold sm:w-auto">Cancelar</button>
                        <button type="submit"
                            class="w-full rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm sm:w-auto">Generar
                            PDF</button>
                    </section>
                </form>
        </div>
        </main>
    </div>
    </div>
    <script>
            /**
             * IIFE: Inicialización de campos de fecha y hora
             * ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
             * Funcionalidad:
             * - Auto-pobla el campo de fecha con la fecha actual si está vacío
             * - Auto-pobla el campo de hora con la hora actual si está vacío
             * - Muestra fecha formateada en español en elemento de display
             * - Manejo seguro de errores para compatibilidad
             * 
             * Variables locales:
             * - pad(): Función para agregar cero a números de un dígito
             * - dateInput: Referencia al input#session-date
             * - timeInput: Referencia al input#session-time
             * - display: Referencia al párrafo de display#session-date-display
             * - now: Objeto Date con fecha/hora actual
             * ════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════
             */
            (function () {
                function pad(n) { return n < 10 ? ('0' + n) : String(n) }
                var dateInput = document.getElementById('session-date');
                var timeInput = document.getElementById('session-time');
                var display = document.getElementById('session-date-display');
                var now = new Date();
                try {
                    // Auto-poblar fecha actual en formato ISO (YYYY-MM-DD)
                    if (dateInput && !dateInput.value) {
                        dateInput.value = now.toISOString().slice(0, 10);
                    }
                    // Auto-poblar hora actual en formato 24h (HH:mm)
                    if (timeInput && !timeInput.value) {
                        timeInput.value = pad(now.getHours()) + ':' + pad(now.getMinutes());
                    }
                    // Mostrar fecha en formato legible en español
                    if (display) {
                        display.textContent = now.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
                    }
                } catch (e) { console && console.error && console.error(e) }
            })();
    </script>
</body>

</html>