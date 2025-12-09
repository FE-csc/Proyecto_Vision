/**
 * ════════════════════════════════════════════════════════════════════════════════
 * FILE: panelAdmin.js
 * ════════════════════════════════════════════════════════════════════════════════
 * DESCRIPCIÓN: Lógica del cliente para el panel de administración usando jQuery AJAX
 * Carga usuarios, cambia roles, elimina usuarios, gestiona logout
 * FUNCIONALIDAD: Load users → Render table → Handle actions (role change, delete)
 * DEPENDENCIAS: jQuery 3.5.1 (AJAX), panelAdmin.php (API endpoint), panelAdmin_View.php (HTML)
 * EVENTOS: Document ready, input change, button clicks
 * MÉTODOS: GET/POST AJAX a panelAdmin.php
 * ════════════════════════════════════════════════════════════════════════════════
 */

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: FUNCIÓN AUXILIAR - CONVERTIR ROL NUMÉRICO A TEXTO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * rolLabel(rol)
 * @param {number} rol - ID numérico del rol (1, 2, 3)
 * @returns {string} Nombre legible del rol
 * 
 * MAPEO DE ROLES:
 * 1 = Paciente (usuario que solicita citas)
 * 2 = Doctor (psicólogo que atiende pacientes)
 * 3 = Administrador (gestiona usuarios y sistema)
 * 
 * PROPÓSITO: Mostrar etiquetas legibles en la tabla en lugar de números
 */
function rolLabel(rol){
  if(rol == 1) return 'Paciente';
  if(rol == 2) return 'Doctor';
  if(rol == 3) return 'Administrador';
  return 'Desconocido';
}

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: FUNCIÓN PRINCIPAL - CARGAR Y RENDERIZAR USUARIOS
// ──────────────────────────────────────────────────────────────────────────────
/**
 * cargarUsuarios()
 * Obtiene lista de usuarios del servidor (con filtros) y renderiza tabla HTML
 * 
 * FLUJO:
 * 1. Envía AJAX GET a panelAdmin.php con parámetros de filtro
 * 2. Recibe JSON array con datos de usuarios
 * 3. Limpia tbody (tabla body)
 * 4. Construye filas HTML dinámicamente con template literals
 * 5. Muestra acciones (cambiar rol, eliminar) en cada fila
 * 
 * PARÁMETROS ENVIADOS:
 * - action: 'list' (indica tipo de operación)
 * - nombre: valor del campo #f_nombre (búsqueda por nombre)
 * - correo: valor del campo #f_correo (búsqueda por email)
 * - rol: valor del select #f_rol (filtro por rol)
 * 
 * RESPUESTA ESPERADA:
 * {
 *   "items": [
 *     {
 *       "ID_Usuario": 1,
 *       "ID_Paciente": 101,
 *       "ID_Psicologo": null,
 *       "Nombre_Paciente": "Juan",
 *       "Apellido_Paciente": "Pérez",
 *       "email": "juan@example.com",
 *       "Telefono_Paciente": "123456789",
 *       "ID_Role": 1
 *     }
 *   ]
 * }
 */
function cargarUsuarios(){
  $.ajax({
    url: 'panelAdmin.php',
    method: 'GET',
    data: {
      action: 'list',
      nombre: $('#f_nombre').val() || '',
      correo: $('#f_correo').val() || '',
      rol: $('#f_rol').val() || ''
    },
    dataType: 'json',
    success: function(data){
      // Obtener referencia al tbody (cuerpo de la tabla)
      const tbody = $('#tbody_usuarios');
      // Limpiar filas anteriores
      tbody.empty();
      
      // Iterar sobre cada usuario en la respuesta
      data.items.forEach(u => {
        // Construir fila HTML con template literal (backticks)
        tbody.append(`
          <tr>
            <!-- Columna 1: ID del usuario en sistema -->
            <td class="p-4">${u.ID_Usuario}</td>
            <!-- Columna 2: ID específico (Paciente o Psicólogo) -->
            <!-- Mostrar '-' si no tiene ID de ninguno de los dos -->
            <td class="p-4">${u.ID_Paciente || u.ID_Psicologo || '-'}</td>
            <!-- Columna 3: Nombre (Paciente o Psicólogo según corresponda) -->
            <td class="p-4">${u.Nombre_Paciente || u.Nombre_Psicologo}</td>
            <!-- Columna 4: Apellido (Paciente o Psicólogo según corresponda) -->
            <td class="p-4">${u.Apellido_Paciente || u.Apellido_Psicologo}</td>
            <!-- Columna 5: Email del usuario -->
            <td class="p-4">${u.email}</td>
            <!-- Columna 6: Teléfono (Paciente o Psicólogo, o '-' si no tiene) -->
            <td class="p-4">${u.Telefono_Paciente || u.Pisicologo_Telefono || '-'}</td>
            <!-- Columna 7: Rol actual (convertido a texto con rolLabel) -->
            <td class="p-4">${rolLabel(u.ID_Role)}</td>
            <!-- Columna 8: Botones de acciones -->
            <td class="p-4 text-right">
              <!-- Botón cambiar a Doctor (rol=2) -->
              <button onclick="cambiarRol(${u.ID_Usuario},2)" class="px-2 py-1 bg-blue-500 text-white rounded">Doctor</button>
              <!-- Botón cambiar a Paciente (rol=1) -->
              <button onclick="cambiarRol(${u.ID_Usuario},1)" class="px-2 py-1 bg-green-500 text-white rounded">Paciente</button>
              <!-- Botón cambiar a Administrador (rol=3) -->
              <button onclick="cambiarRol(${u.ID_Usuario},3)" class="px-2 py-1 bg-purple-500 text-white rounded">Admin</button>
              <!-- Botón eliminar usuario permanentemente -->
              <button onclick="eliminarUsuario(${u.ID_Usuario})" class="px-2 py-1 bg-red-500 text-white rounded">Eliminar</button>
            </td>
          </tr>
        `);
      });
    },
    error: function(xhr){
      // Si hay error en la petición, mostrar alerta con detalles
      alert('Error al cargar usuarios: ' + xhr.responseText);
    }
  });
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: FUNCIÓN PARA CAMBIAR ROL DE UN USUARIO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * cambiarRol(id, rol)
 * @param {number} id - ID del usuario a modificar
 * @param {number} rol - Nuevo rol (1=Paciente, 2=Doctor, 3=Admin)
 * 
 * FLUJO:
 * 1. Convierte rol numérico a texto legible
 * 2. Pide confirmación del administrador (confirm dialog)
 * 3. Si cancela, retorna sin hacer nada
 * 4. Si confirma, envía POST AJAX a panelAdmin.php
 * 5. Recibe respuesta con mensaje de éxito/error
 * 6. Muestra alerta con resultado
 * 7. Recarga la tabla de usuarios
 * 
 * PETICIÓN AJAX:
 * POST panelAdmin.php?action=updateRol
 * Body JSON: { usuario_id: 5, rol_nuevo: 2 }
 * 
 * PROPÓSITO: Cambiar el rol de un usuario en el sistema
 * EJEMPLO: cambiarRol(5, 2) → cambiar usuario 5 a Doctor
 */
function cambiarRol(id, rol){
    // Convertir rol numérico a texto para mostrar en confirmación
    let rolTexto = rolLabel(rol);
    // Solicitar confirmación antes de cambiar
    if(!confirm(`¿Seguro que deseas cambiar de rol al usuario ${id} a ${rolTexto}`)) return;
    
    // Realizar petición AJAX POST
    $.ajax({
      url: 'panelAdmin.php?action=updateRol',
      method: 'POST',
      contentType: 'application/json',
      // Enviar datos en formato JSON
      data: JSON.stringify({ usuario_id: id, rol_nuevo: rol }),
      dataType: 'json',
      success: function(data){
        // Mostrar mensaje de respuesta (success o error)
        alert(data.message || data.error);
        // Recargar tabla después de cambiar
        cargarUsuarios();
      },
      error: function(xhr){
        // Si hay error en la petición, mostrar alerta
        alert('Error al cambiar rol: ' + xhr.responseText);
      }
    });
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: FUNCIÓN PARA ELIMINAR UN USUARIO
// ──────────────────────────────────────────────────────────────────────────────
/**
 * eliminarUsuario(id)
 * @param {number} id - ID del usuario a eliminar
 * 
 * FLUJO:
 * 1. Solicita confirmación del administrador (double-check safety)
 * 2. Si cancela, retorna sin hacer nada
 * 3. Si confirma, envía POST AJAX a panelAdmin.php
 * 4. Backend elimina el usuario de la base de datos
 * 5. Recibe respuesta con mensaje
 * 6. Muestra alerta con resultado
 * 7. Recarga la tabla
 * 
 * PETICIÓN AJAX:
 * POST panelAdmin.php?action=deleteUser
 * Body JSON: { id: 5 }
 * 
 * ⚠️ ADVERTENCIA: Esta acción es irreversible
 * Elimina permanentemente el usuario y sus datos relacionados
 * 
 * PROPÓSITO: Eliminar un usuario del sistema
 * EJEMPLO: eliminarUsuario(5) → elimina usuario con ID 5
 */
function eliminarUsuario(id){
  // Solicitar confirmación (doble protección contra eliminaciones accidentales)
  if(!confirm(`¿Seguro que deseas eliminar al usuario ${id} Esta accion no se puede deshacer.?`)) return;
  
  // Realizar petición AJAX POST para eliminar
  $.ajax({
    url: 'panelAdmin.php?action=deleteUser',
    method: 'POST',
    contentType: 'application/json',
    // Enviar solo el ID del usuario a eliminar
    data: JSON.stringify({ id }),
    dataType: 'json',
    success: function(data){
      // Mostrar mensaje de respuesta
      alert(data.message || data.error);
      // Recargar tabla después de eliminar
      cargarUsuarios();
    },
    error: function(xhr){
      // Si hay error en la petición, mostrar alerta
      alert('Error al eliminar usuario: ' + xhr.responseText);
    }
  });
}



// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: FUNCIÓN PARA CERRAR SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
/**
 * cerrarSesion()
 * Sin parámetros
 * 
 * FLUJO:
 * 1. Solicita confirmación del usuario
 * 2. Si cancela, retorna sin hacer nada
 * 3. Si confirma, envía POST AJAX a panelAdmin.php
 * 4. Backend destruye la sesión del usuario
 * 5. Recibe respuesta con mensaje de confirmación
 * 6. Muestra alerta
 * 7. Redirige a index.php (página de inicio)
 * 
 * PETICIÓN AJAX:
 * POST panelAdmin.php?action=logout
 * No requiere body
 * 
 * PROPÓSITO: Destruir la sesión actual y desloguear al usuario
 * EJEMPLO: cerrarSesion() → cierra sesión y redirige a home
 * 
 * NOTA: Redirección a index.php para volver a página de inicio
 */
function cerrarSesion(){
  // Solicitar confirmación antes de cerrar sesión
  if (!confirm("¿Seguro que deseas cerrar sesión?")) return;
  
  // Realizar petición AJAX POST para destruir sesión
  $.ajax({
    url: 'panelAdmin.php?action=logout',
    method: 'POST',
    dataType: 'json',
    success: function(data){
      // Mostrar mensaje de sesión cerrada
      alert(data.message || "Sesión cerrada");
      // Redirigir a página de inicio después de cerrar sesión
      window.location.href = 'index.php';
    },
    error: function(e){
      // Si hay error, mostrar alerta
      alert('Error al cerrar sesión: ' + e.responseText);
    }
  });
}


// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 6: INICIALIZACIÓN Y EVENT LISTENERS (DOCUMENT.READY)
// ──────────────────────────────────────────────────────────────────────────────
/**
 * INICIALIZACIÓN: Event listeners y setup
 * 
 * FLUJO:
 * 1. jQuery $(document).ready) espera a que el DOM esté completamente cargado
 * 2. Ejecuta cargarUsuarios() para mostrar tabla inicial
 * 3. Configura listeners en campos de búsqueda/filtro
 * 4. Implementa debounce de 300ms en entrada de nombre
 * 5. Configura listener para cambios en rol (sin debounce)
 * 6. Configura listener para botón de logout
 * 
 * DEBOUNCE:
 * Espera 300ms después del último cambio antes de cargar usuarios
 * Evita hacer demasiadas peticiones AJAX mientras el usuario escribe
 * La variable debounceTimer almacena el ID del timeout actual
 * 
 * EVENTOS CONFIGURADOS:
 * - #f_nombre (input): buscar por nombre con debounce 300ms
 * - #f_rol (select): cambiar rol con debounce 300ms + cambio instantáneo
 * - #btnLogout (button): cerrar sesión sin navegación por defecto
 * 
 * PROPÓSITO: Conectar elementos HTML con funciones JS, iniciar tabla
 */

// Variable para controlar el temporizador debounce
// Almacena el ID del timeout actual para poder cancelarlo si hay nuevos cambios
let debounceTimer;

$(document).ready(function(){
  // ─ Cargar usuarios al cargar página
  // Ejecuta cargarUsuarios() inmediatamente para rellenar tabla
  cargarUsuarios();

  // ─ Recargar filtros con debounce (espera 300ms entre cambios)
  // Los selectores #f_nombre y #f_rol se escuchan con eventos 'input' y 'change'
  $('#f_nombre, #f_rol').on('input change', function(){
    // Cancelar timeout anterior si existe (el usuario está escribiendo)
    clearTimeout(debounceTimer);
    // Esperar 300ms después del último cambio antes de cargar usuarios
    // Solo después de que el usuario deje de escribir, se ejecuta cargarUsuarios
    debounceTimer = setTimeout(cargarUsuarios, 300);
  });

  // ─ Filtro de rol tiene cambio instantáneo ADICIONAL
  // Este listener se ejecuta siempre además del debounce anterior
  // Permite cambios inmediatos al seleccionar en dropdown (sin esperar 300ms)
  $('#f_rol').on('change', function(){
    // Cargar usuarios inmediatamente sin esperar debounce
    cargarUsuarios();
  });

  // ─ Manejar clic en botón de cerrar sesión
  // Evita que el enlace navege por defecto (e.preventDefault)
  // En lugar de navegar, ejecuta la función cerrarSesion() con AJAX
  $('#btnLogout').on('click', function(e){
    // Prevenir comportamiento por defecto del enlace
    e.preventDefault();
    // Ejecutar función para cerrar sesión (con confirmación y AJAX)
    cerrarSesion();
  });
});
