<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * EDITARNOTAS.PHP - VISTA DE EDICIÓN DE NOTAS CLÍNICAS
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Interfaz para modificar notas clínicas existentes. Permite editar el
 * contenido de notas de sesión de pacientes.
 * 
 * FUNCIONALIDADES:
 * - Carga de nota existente mediante ID en query string
 * - Edición de contenido de nota en textarea
 * - Actualización mediante API REST (api_notas.php)
 * - Validación de sesión activa
 * 
 * PARÁMETROS URL:
 * - id (int): ID de la nota a editar
 * 
 * DEPENDENCIAS:
 * - api_notas.php: API para operaciones CRUD de notas
 * - VerNotas.php: Vista de listado de notas (redirección)
 * 
 * SEGURIDAD:
 * - Validación de sesión al inicio
 * - Redirección a login si no autenticado
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

// ────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: VALIDACIÓN DE SESIÓN
// ────────────────────────────────────────────────────────────────────────────

session_start();

/**
 * Verificar sesión activa
 * Redirige a login con URL de retorno si no está autenticado
 */
if (empty($_SESSION['user_id'])) {
    header('Location: login.html?redirect=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}
?>

<!-- ════════════════════════════════════════════════════════════════════════════
SECCIÓN 2: ESTRUCTURA HTML Y CONFIGURACIÓN
════════════════════════════════════════════════════════════════════════════ -->

<!-- ════════════════════════════════════════════════════════════════════════════
SECCIÓN 2: ESTRUCTURA HTML Y CONFIGURACIÓN
════════════════════════════════════════════════════════════════════════════ -->

<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Editar Nota del Paciente</title>

<!-- ──────────────────────────────────────────────────────────────────────
Tailwind CSS 3 con plugins
────────────────────────────────────────────────────────────────────── -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<!-- ──────────────────────────────────────────────────────────────────────
Fuentes: Inter y Material Symbols
────────────────────────────────────────────────────────────────────── -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>

<!-- ──────────────────────────────────────────────────────────────────────
Estilos personalizados
────────────────────────────────────────────────────────────────────── -->
<style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
  </style>

<!-- ──────────────────────────────────────────────────────────────────────
Configuración de Tailwind: Dark mode, colores, fuentes
────────────────────────────────────────────────────────────────────── -->
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

<!-- ════════════════════════════════════════════════════════════════════════════
SECCIÓN 3: BODY Y ESTRUCTURA DE CONTENIDO
════════════════════════════════════════════════════════════════════════════ -->

<body class="font-display bg-background-light dark:bg-background-dark">
<div class="relative flex min-h-screen w-full justify-center">
<main class="w-full max-w-7xl p-8">
<div class="flex flex-col gap-8 max-w-4xl mx-auto">

<!-- ──────────────────────────────────────────────────────────────────────
Header: Título y breadcrumb
────────────────────────────────────────────────────────────────────── -->
<div class="flex flex-col gap-2">
<a class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors w-fit" href="VerNotas.php">
<span class="material-symbols-outlined">arrow_back</span>
<span class="text-sm font-medium">Regresar a Notas y Evolución</span>
</a>
<h1 class="text-slate-900 dark:text-slate-100 text-4xl font-black leading-tight tracking-[-0.033em]">Editar Nota del Paciente</h1>
<p class="text-slate-500 dark:text-slate-400 text-base font-normal leading-normal">Modifique la nota clínica.</p>
</div>

<!-- ──────────────────────────────────────────────────────────────────────
Formulario de edición de nota
────────────────────────────────────────────────────────────────────── -->
<!-- ──────────────────────────────────────────────────────────────────────
Formulario de edición de nota
────────────────────────────────────────────────────────────────────── -->
<div class="w-full bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col gap-6">

<!-- Detalles de la nota (solo lectura) -->
<div class="flex flex-col gap-1">
<h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight">Detalles de la Nota</h2>
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm text-slate-600 dark:text-slate-300">
<!-- Poblado dinámicamente por JavaScript -->
<p><span class="font-medium text-slate-800 dark:text-slate-200">Paciente:</span> -</p>
<p><span class="font-medium text-slate-800 dark:text-slate-200">Fecha de la nota:</span> -</p>
</div>
</div>

<!-- Campo de texto: Contenido de la nota -->
<label class="flex flex-col w-full">
<p class="text-slate-900 dark:text-slate-100 text-base font-medium leading-normal pb-2">Contenido de la Nota</p>
<textarea class="form-textarea w-full min-h-[300px] resize-y rounded-lg text-slate-900 dark:text-slate-100 focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:border-primary dark:focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500 p-4 text-base font-normal leading-normal" placeholder="Escriba aquí el contenido de la nota clínica..."></textarea>
</label>

<!-- Botones de acción -->
<div class="flex justify-end items-center gap-4 mt-4">
<!-- Botón Cancelar -->
<button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-slate-100 text-sm font-bold leading-normal tracking-[0.015em] hover:bg-slate-300 dark:hover:bg-slate-600">
<span class="truncate">Cancelar</span>
</button>
<!-- Botón Guardar -->
<button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 gap-2">
<span class="material-symbols-outlined text-base">save</span>
<span class="truncate">Guardar Cambios</span>
</button>
</div>
</div>

</div>
</main>
</div>

<!-- ════════════════════════════════════════════════════════════════════════════
SECCIÓN 4: JAVASCRIPT - LÓGICA DE EDICIÓN
════════════════════════════════════════════════════════════════════════════ -->

<script>
/**
 * ────────────────────────────────────────────────────────────────────────────
 * VARIABLES GLOBALES
 * ────────────────────────────────────────────────────────────────────────────
 */

let idNota = null; // ID de la nota a editar (obtenido de query string)

/**
 * ────────────────────────────────────────────────────────────────────────────
 * INICIALIZACIÓN
 * ────────────────────────────────────────────────────────────────────────────
 */

document.addEventListener('DOMContentLoaded', () => {
  /**
   * Obtener ID de nota desde query string (?id=123)
   * Si no existe, redirigir a vista de notas
   */
  const params = new URLSearchParams(window.location.search);
  idNota = params.get('id');
  
  if (!idNota) {
    alert('No se indicó ID de nota');
    window.location.href = 'VerNotas.php';
    return;
  }
  
  // Cargar datos de la nota
  cargarNota();

  /**
   * Configurar event listeners de botones
   */
  const btnGuardar = document.querySelector('button[class*="bg-primary"]');
  const btnCancelar = document.querySelector('button[class*="bg-slate-200"]');
  
  btnGuardar.addEventListener('click', guardarCambios);
  btnCancelar.addEventListener('click', () => { 
    if (confirm('¿Cancelar cambios?')) window.location.href = 'VerNotas.php'; 
  });
});

/**
 * ────────────────────────────────────────────────────────────────────────────
 * FUNCIÓN: cargarNota()
 * Obtiene los datos de la nota desde la API y pobla el formulario
 * ────────────────────────────────────────────────────────────────────────────
 */
async function cargarNota() {
  try {
    // Petición GET a api_notas.php con action=obtener e id
    const res = await fetch(`api_notas.php?action=obtener&id=${idNota}`);
    const j = await res.json();
    
    if (j.success) {
      const n = j.data;
      
      // Actualizar detalles del paciente y fecha
      const detalles = document.querySelectorAll('.grid p');
      detalles[0].innerHTML = `<span class="font-medium text-slate-800 dark:text-slate-200">Paciente:</span> ${n.Nombre_Paciente}`;
      detalles[1].innerHTML = `<span class="font-medium text-slate-800 dark:text-slate-200">Fecha de la nota:</span> ${formatDate(n.Fecha_Sesion)}`;
      
      // Poblar textarea con contenido de la nota
      const ta = document.querySelector('textarea');
      ta.value = n.Contenido || '';
      
      /**
       * Crear input oculto para fecha si es necesario
       * Permite actualizar la fecha en el PUT si se modifica
       */
      let inputDate = document.querySelector('input[type="date"]');
      if (!inputDate) {
        const label = document.createElement('input');
        label.type = 'hidden';
        label.value = n.Fecha_Sesion || '';
        label.id = 'fecha_sesion_hidden';
        document.querySelector('form')?.appendChild(label);
      } else {
        inputDate.value = n.Fecha_Sesion || '';
      }
    } else {
      alert('Error: ' + (j.message||''));
      window.location.href = 'VerNotas.php';
    }
  } catch (err) { 
    console.error(err); 
    alert('Error de conexión'); 
  }
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * FUNCIÓN: guardarCambios()
 * Envía los cambios a la API para actualizar la nota
 * ────────────────────────────────────────────────────────────────────────────
 */
async function guardarCambios() {
  // Obtener contenido del textarea
  const contenido = document.querySelector('textarea').value.trim();
  
  // Validación: El contenido no puede estar vacío
  if (!contenido) { 
    alert('El contenido no puede estar vacío'); 
    return; 
  }
  
  // Obtener fecha (de input date o hidden)
  let fecha = document.querySelector('input[type="date"]')?.value || 
              document.getElementById('fecha_sesion_hidden')?.value || 
              null;
  
  /**
   * Construir body de la petición
   * - Contenido: Texto completo de la nota
   * - Resumen: Primeros 255 caracteres (para campo en BD)
   * - Fecha_Sesion: Fecha de la sesión (opcional)
   */
  const body = { 
    Contenido: contenido, 
    Resumen: contenido.substring(0,255) 
  };
  if (fecha) body.Fecha_Sesion = fecha;

  try {
    /**
     * Petición POST a api_notas.php con action=actualizar
     * Método: POST
     * Headers: Content-Type JSON
     * Body: JSON con datos actualizados
     */
    const res = await fetch(`api_notas.php?action=actualizar&id=${idNota}`, {
      method: 'POST', 
      headers: { 'Content-Type': 'application/json' }, 
      body: JSON.stringify(body)
    });
    const j = await res.json();
    
    if (j.success) { 
      alert('Nota actualizada'); 
      window.location.href = 'VerNotas.php'; 
    } else {
      alert('Error: ' + (j.message||''));
    }
  } catch (err) { 
    console.error(err); 
    alert('Error de conexión al guardar'); 
  }
}

/**
 * ────────────────────────────────────────────────────────────────────────────
 * FUNCIÓN UTILITARIA: formatDate()
 * Formatea una fecha en formato legible en español
 * ────────────────────────────────────────────────────────────────────────────
 * 
 * @param {string} d - Fecha en formato ISO (YYYY-MM-DD)
 * @returns {string} Fecha formateada en español (DD/MM/YYYY)
 */
function formatDate(d) {
  if (!d) return '-';
  const date = new Date(d);
  return date.toLocaleDateString('es-ES');
}

</script>

</body></html>