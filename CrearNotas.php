<?php

/**
 * ════════════════════════════════════════════════════════════════════════════════
 * ARCHIVO: CrearNotas.php
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Formulario para crear nuevas notas clínicas de pacientes
 *   - Interfaz completa con validación de sesión
 *   - Carga dinámica de lista de pacientes del psicólogo
 *   - Envío AJAX de datos a api_notas.php
 *   - Diseño responsivo con Tailwind CSS y tema oscuro
 * 
 * Funcionalidades:
 *   - Validar sesión de usuario autenticado
 *   - Obtener ID del psicólogo desde get_id_psicologo.php
 *   - Cargar lista de pacientes desde api_pacientes.php
 *   - Autocompletar fecha actual
 *   - Validar campos antes de enviar
 *   - Crear nota clínica mediante POST a api_notas.php
 *   - Redirigir a VerNotas.php tras éxito
 * 
 * Flujo de operación:
 *   1. PHP valida sesión (si no existe, redirige a login)
 *   2. Renderiza HTML con formulario
 *   3. JavaScript obtiene ID_Psicologo al cargar página
 *   4. Carga pacientes del psicólogo en select
 *   5. Usuario completa formulario
 *   6. JavaScript valida y envía POST a api_notas.php
 *   7. Si éxito, redirige a VerNotas.php
 * 
 * APIs utilizadas:
 *   - get_id_psicologo.php: Obtiene ID_Psicologo del usuario autenticado
 *   - api_pacientes.php: Lista pacientes del psicólogo
 *   - api_notas.php?action=crear: Crea nueva nota clínica
 * 
 * Dependencias:
 *   - Sesión PHP con user_id
 *   - Tailwind CSS 3
 *   - Google Fonts (Inter)
 *   - Material Symbols Icons
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * SECCIÓN 1: VALIDACIÓN DE SESIÓN
 * ────────────────────────────────────────────────────────────────────────────
 */

/**
 * Iniciar sesión PHP
 * Permite acceder a $_SESSION para validar usuario
 */
session_start();

/**
 * VALIDAR SESIÓN DE USUARIO
 * 
 * empty($_SESSION['user_id']):
 *   - true: No existe o está vacío
 *   - false: Existe y tiene valor
 * 
 * Si no está autenticado:
 *   - Redirigir a login.html con parámetro redirect
 *   - El parámetro permite volver a esta página después del login
 *   - Terminar ejecución
 */
if (empty($_SESSION['user_id'])) {
  header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
  exit;
}

if (!isset($_SESSION['user_role']) || (int) $_SESSION['user_role'] !== 2) {
  header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
  exit;
}
?>


<!DOCTYPE html>
<html class="light" lang="en">

<head>
  <!-- ════════════════════════════════════════════════════════════════════════════════
     SECCIÓN 2: HEAD - METAETIQUETAS Y RECURSOS EXTERNOS
     ════════════════════════════════════════════════════════════════════════════════ -->


  <meta charset="utf-8" />


  <meta content="width=device-width, initial-scale=1.0" name="viewport" />

  <title>Crear Nueva Nota de Paciente</title>


  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>


  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet" />


  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />


  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
  </style>
  <script id="tailwind-config">
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
            "display": ["Inter", "sans-serif"]
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

<body class="font-display bg-background-light dark:bg-background-dark">
  <div class="relative flex min-h-screen w-full justify-center">
    <main class="w-full max-w-7xl p-8">
      <div class="flex flex-col gap-8 max-w-4xl mx-auto">
        <div class="flex flex-col gap-2">
          <a href="VerNotas.php" class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors w-fit mb-2">
            <span class="material-symbols-outlined">arrow_back</span>
            <span class="text-sm font-medium">Regresar a Notas y Evolución</span>
          </a>
          <h1 class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-[-0.033em]">Crear Nueva Nota de Paciente</h1>
          <p class="text-slate-500 dark:text-slate-400 text-base font-normal leading-normal">Complete el formulario para agregar una nueva nota clínica para un paciente.</p>
        </div>
        <div class="w-full bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 md:p-8">
          <form class="flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <label class="flex flex-col w-full gap-2">
                <p class="text-slate-900 dark:text-slate-100 text-base font-medium leading-normal">Paciente</p>
                <div class="relative w-full">
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">person</span>
                  <select class="form-select w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-slate-100 focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:border-primary dark:focus:border-primary h-14 placeholder:text-slate-400 dark:placeholder:text-slate-500 pl-12 pr-4 text-base font-normal leading-normal appearance-none">
                    <option value="">-- Seleccione paciente --</option>
                  </select>
                  <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 pointer-events-none">expand_more</span>
                </div>
              </label>
              <label class="flex flex-col w-full gap-2">
                <p class="text-slate-900 dark:text-slate-100 text-base font-medium leading-normal">Fecha</p>
                <div class="relative w-full">
                  <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">calendar_today</span>
                  <input class="form-input w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-slate-100 focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:border-primary dark:focus:border-primary h-14 placeholder:text-slate-400 dark:placeholder:text-slate-500 pl-12 pr-4 text-base font-normal leading-normal" type="date" value="" />
                </div>
              </label>
            </div>
            <label class="flex flex-col w-full gap-2">
              <p class="text-slate-900 dark:text-slate-100 text-base font-medium leading-normal">Nota Clínica</p>
              <textarea class="form-textarea w-full min-w-0 flex-1 resize-y overflow-hidden rounded-lg text-slate-900 dark:text-slate-100 focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:border-primary dark:focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 p-4 text-base font-normal leading-normal min-h-[200px]" placeholder="Escriba aquí los detalles de la sesión, observaciones y el progreso del paciente..."></textarea>
            </label>
            <div class="flex justify-end gap-4 mt-4">
              <button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-slate-100 text-sm font-bold leading-normal tracking-[0.015em]" type="button">
                <span class="truncate">Cancelar</span>
              </button>
              <button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] gap-2" type="submit">
                <span class="truncate">Guardar Nota</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>

  <script>
    /**
     * ════════════════════════════════════════════════════════════════════════════════
     * SECCIÓN 3: JAVASCRIPT - LÓGICA DEL FORMULARIO
     * ════════════════════════════════════════════════════════════════════════════════
     */

    /**
     * VARIABLE GLOBAL: ID DEL PSICÓLOGO
     * 
     * Se obtiene al cargar la página mediante get_id_psicologo.php
     * Se usa al crear la nota para asociarla al psicólogo autenticado
     */
    let ID_PSICOLOGO = null;

    /**
     * INICIALIZACIÓN AL CARGAR LA PÁGINA
     * 
     * Se ejecuta cuando el DOM está completamente cargado
     * 
     * Flujo:
     *   1. Obtener ID del psicólogo
     *   2. Cargar lista de pacientes
     *   3. Establecer fecha actual en input
     *   4. Configurar event listeners del formulario
     */
    document.addEventListener('DOMContentLoaded', () => {
      /**
       * OBTENER ID DEL PSICÓLOGO
       * 
       * Promesa que se resuelve cuando se obtiene el ID
       * Si falla, muestra alerta y registra error
       */
      obtenerIdPsicologo().then(() => {
        /**
         * CARGAR PACIENTES
         * Llena el select con los pacientes del psicólogo
         */
        cargarPacientes();

        /**
         * ESTABLECER FECHA ACTUAL
         * 
         * new Date().toISOString():
         *   - Obtiene fecha actual en formato ISO 8601
         *   - Ejemplo: "2024-12-09T14:30:00.000Z"
         * 
         * .split('T')[0]:
         *   - Divide por 'T' y toma la primera parte
         *   - Resultado: "2024-12-09"
         *   - Formato compatible con input type="date"
         */
        const hoy = new Date().toISOString().split('T')[0];
        const inputFecha = document.querySelector('input[type="date"]');
        if (inputFecha) inputFecha.value = hoy;

        /**
         * CONFIGURAR EVENT LISTENER DEL FORMULARIO
         * 
         * Submit: Ejecuta función guardarNota
         */
        const form = document.querySelector('form');
        form.addEventListener('submit', guardarNota);

        /**
         * CONFIGURAR BOTÓN CANCELAR
         * 
         * Muestra confirmación antes de redirigir a VerNotas.php
         */
        document.querySelector('button[type="button"]').addEventListener('click', () => {
          if (confirm('Cancelar?')) window.location.href = 'VerNotas.php';
        });
      }).catch(err => {
        /**
         * MANEJO DE ERROR
         * Si falla la obtención del ID del psicólogo, mostrar alerta
         */
        alert('Error al cargar datos del psicólogo');
        console.error(err);
      });
    });

    /**
     * FUNCIÓN: OBTENER ID DEL PSICÓLOGO
     * 
     * Realiza solicitud GET a get_id_psicologo.php
     * 
     * Respuesta esperada:
     * {
     *   success: true,
     *   ID_Psicologo: 1
     * }
     * 
     * Retorna: Promise que se resuelve cuando se obtiene el ID
     * Lanza: Error si la solicitud falla o success es false
     */
    async function obtenerIdPsicologo() {
      try {
        /**
         * SOLICITUD AJAX GET
         * Obtiene ID del psicólogo autenticado
         */
        const res = await fetch('get_id_psicologo.php');
        const j = await res.json();

        /**
         * VALIDAR RESPUESTA
         */
        if (j.success) {
          /**
           * ALMACENAR ID EN VARIABLE GLOBAL
           * Se usará al crear la nota
           */
          ID_PSICOLOGO = j.ID_Psicologo;
        } else {
          /**
           * LANZAR ERROR SI FALLA
           */
          throw new Error(j.message || 'No se pudo obtener el ID del psicólogo');
        }
      } catch (err) {
        console.error(err);
        throw err;
      }
    }

    /**
     * FUNCIÓN: CARGAR PACIENTES
     * 
     * Realiza solicitud GET a api_pacientes.php
     * Obtiene lista de pacientes del psicólogo
     * 
     * Respuesta esperada:
     * {
     *   success: true,
     *   data: [
     *     { ID_Paciente: 1, Nombre_Completo: "Juan Pérez" },
     *     { ID_Paciente: 2, Nombre_Paciente: "Ana", Apellido_Paciente: "García" },
     *     ...
     *   ]
     * }
     * 
     * Proceso:
     *   1. Hacer fetch a api_pacientes.php
     *   2. Parsear respuesta JSON
     *   3. Iterar array de pacientes
     *   4. Crear <option> para cada paciente
     *   5. Agregar al <select>
     */
    async function cargarPacientes() {
      try {
        /**
         * SOLICITUD AJAX GET
         */
        const res = await fetch('api_pacientes.php');
        const j = await res.json();

        /**
         * VALIDAR RESPUESTA
         */
        if (j.success) {
          /**
           * OBTENER REFERENCIA AL SELECT
           */
          const select = document.querySelector('select');

          /**
           * ITERAR PACIENTES
           * 
           * Para cada paciente:
           *   1. Crear elemento <option>
           *   2. Asignar ID como value
           *   3. Asignar nombre como texto visible
           *   4. Agregar al select
           * 
           * Nombre:
           *   - Usar Nombre_Completo si existe
           *   - Caso contrario: concatenar Nombre_Paciente + Apellido_Paciente
           */
          j.data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.ID_Paciente;
            opt.textContent = p.Nombre_Completo || (p.Nombre_Paciente + ' ' + p.Apellido_Paciente);
            select.appendChild(opt);
          });
        } else {
          /**
           * REGISTRAR ERROR SI FALLA
           */
          console.error(j.message);
        }
      } catch (err) {
        console.error(err);
      }
    }

    /**
     * FUNCIÓN: GUARDAR NOTA
     * 
     * Handler del evento submit del formulario
     * 
     * Parámetros:
     *   - e: Event object del submit
     * 
     * Proceso:
     *   1. Prevenir submit por defecto
     *   2. Obtener valores del formulario
     *   3. Validar que todos los campos estén completos
     *   4. Construir objeto JSON con datos
     *   5. Enviar POST a api_notas.php?action=crear
     *   6. Si éxito: mostrar alerta y redirigir
     *   7. Si error: mostrar mensaje de error
     */
    async function guardarNota(e) {
      /**
       * PREVENIR COMPORTAMIENTO POR DEFECTO
       * Evita refresh de página
       */
      e.preventDefault();

      /**
       * OBTENER VALORES DEL FORMULARIO
       */
      const select = document.querySelector('select');
      const fecha = document.querySelector('input[type="date"]').value;
      const contenido = document.querySelector('textarea').value.trim();

      /**
       * VALIDACIÓN DE CAMPOS
       * 
       * Campos requeridos:
       *   - Paciente (select debe tener valor)
       *   - Fecha (input date debe tener valor)
       *   - Contenido (textarea no debe estar vacío)
       * 
       * Si falta alguno:
       *   - Mostrar alerta
       *   - Retornar (exit) sin enviar
       */
      if (!select.value) {
        alert('Seleccione paciente');
        return;
      }
      if (!fecha) {
        alert('Seleccione fecha');
        return;
      }
      if (!contenido) {
        alert('Escriba el contenido');
        return;
      }

      /**
       * CONSTRUIR OBJETO JSON CON DATOS DE LA NOTA
       * 
       * Campos:
       *   - ID_Paciente: ID del paciente seleccionado (convertido a entero)
       *   - ID_Psicologo: ID del psicólogo autenticado (variable global)
       *   - Fecha_Sesion: Fecha de la sesión (formato YYYY-MM-DD)
       *   - Contenido: Texto completo de la nota
       *   - Resumen: Primeros 255 caracteres del contenido (para vista previa)
       */
      const body = {
        ID_Paciente: parseInt(select.value),
        ID_Psicologo: ID_PSICOLOGO,
        Fecha_Sesion: fecha,
        Contenido: contenido,
        Resumen: contenido.substring(0, 255)
      };

      try {
        /**
         * SOLICITUD AJAX POST
         * 
         * Endpoint: api_notas.php?action=crear
         * Method: POST
         * Headers: Content-Type: application/json
         * Body: Objeto JSON serializado
         */
        const res = await fetch('api_notas.php?action=crear', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(body)
        });

        /**
         * PARSEAR RESPUESTA JSON
         */
        const j = await res.json();

        /**
         * PROCESAR RESPUESTA
         */
        if (j.success) {
          /**
           * ÉXITO:
           *   - Mostrar alerta de confirmación
           *   - Redirigir a página de listado de notas
           */
          alert('Nota creada');
          window.location.href = 'VerNotas.php';
        } else {
          /**
           * ERROR DEL SERVIDOR:
           *   - Mostrar mensaje de error del servidor
           */
          alert('Error: ' + (j.message || ''));
        }
      } catch (err) {
        /**
         * ERROR DE RED O PARSING:
         *   - Registrar en consola
         *   - Mostrar alerta genérica
         */
        console.error(err);
        alert('Error de conexión');
      }
    }
  </script>

</body>

</html>