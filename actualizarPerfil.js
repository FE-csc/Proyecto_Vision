/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT: Actualizar Perfil de Usuario
 * ════════════════════════════════════════════════════════════════════════════════
 * Descripción:
 *   - Gestiona la edición y actualización de datos del perfil de usuario
 *   - Proporciona validación en tiempo real de campos
 *   - Implementa modo edición/lectura con interfaz interactiva
 *   - Comunica con el servidor PHP mediante AJAX para guardar cambios
 * 
 * Funcionalidades principales:
 *   1. Cargar datos del perfil desde el servidor
 *   2. Activar modo edición con visualización de errores
 *   3. Validar datos antes de enviar (nombre, apellido, edad, teléfono)
 *   4. Guardar cambios via AJAX POST a actualizarPerfil.php
 *   5. Permitir cancelar cambios sin guardar
 * ════════════════════════════════════════════════════════════════════════════════
 */

// ────────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: REFERENCIAS A ELEMENTOS DEL DOM
// ────────────────────────────────────────────────────────────────────────────────

// Campos de entrada del formulario
const nombre = document.getElementById("nombre");
const apellido = document.getElementById("apellido");
const edad = document.getElementById("edad");
const telefono = document.getElementById("telefono");
const email = document.getElementById("email");
const fecha = document.getElementById("fecha");

// Elementos que muestran mensajes de error de validación
const errorNombre = document.getElementById("errorNombre");
const errorApellido = document.getElementById("errorApellido");
const errorEdad = document.getElementById("errorEdad");
const errorTelefono = document.getElementById("errorTelefono");

// Contenedores de alertas generales (éxito, error, advertencia)
const alertaGeneral = document.getElementById("alertaGeneral");
const alertaExito = document.getElementById("alertaExito");

// Botones de acción del formulario
const btnEditar = document.getElementById("btnEditar");
const btnGuardar = document.getElementById("btnGuardar");
const btnCancelar = document.getElementById("btnCancelar");

// ────────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: VARIABLES GLOBALES
// ────────────────────────────────────────────────────────────────────────────────

// Objeto que almacena los IDs y valores actuales del usuario
// Estructura: { id_paciente: number, id_usuario: number }
let datosActuales = {};

// Copia de los datos originales para poder restaurarlos al cancelar la edición
let datosOriginales = {};

// Bandera que indica si el formulario está en modo edición (true) o lectura (false)
let modoEdicion = false;

// ────────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: FUNCIONES UTILITARIAS
// ────────────────────────────────────────────────────────────────────────────────

/**
 * Limpia todos los mensajes de error del formulario
 * Elimina el contenido de texto de los elementos de error
 */
function limpiarErrores() {
    errorNombre.textContent = '';
    errorApellido.textContent = '';
    errorEdad.textContent = '';
    errorTelefono.textContent = '';
}

/**
 * Limpia el mensaje de éxito ocultándolo
 * Remueve la clase 'hidden' y limpia el contenido de texto
 */
function limpiarExitos() {
    alertaExito.classList.add('hidden');
    alertaExito.textContent = '';
}

/**
 * Muestra una alerta general de error o advertencia al usuario
 * 
 * @param {string} mensaje - El texto del mensaje a mostrar
 * @param {string} tipo - Tipo de alerta: 'error' (rojo) o 'warning' (amarillo)
 * 
 * Comportamiento:
 *   - Aplica estilos Tailwind CSS según el tipo
 *   - Hace scroll automático hacia la parte superior
 *   - Se oculta automáticamente después de 4 segundos
 */
function mostrarAlertaGeneral(mensaje, tipo) {
    alertaGeneral.textContent = mensaje;
    // Remover todas las clases de estilos previas
    alertaGeneral.classList.remove('hidden', 'bg-red-100', 'bg-yellow-100', 'border', 'border-red-400', 'border-yellow-400', 'text-red-700', 'text-yellow-700');
    
    // Aplicar estilos según el tipo de alerta
    if (tipo === 'error') {
        alertaGeneral.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-700');
    } else if (tipo === 'warning') {
        alertaGeneral.classList.add('bg-yellow-100', 'border', 'border-yellow-400', 'text-yellow-700');
    }
    
    // Realizar scroll suave hacia la parte superior de la página
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Ocultar automáticamente la alerta después de 4 segundos
    setTimeout(() => {
        alertaGeneral.classList.add('hidden');
    }, 4000);
}

/**
 * Muestra una alerta de éxito al usuario
 * 
 * @param {string} mensaje - El texto del mensaje de éxito a mostrar
 * 
 * Comportamiento:
 *   - Prepend un emoji de verificación (✅)
 *   - Hace scroll automático hacia la parte superior
 *   - Se oculta automáticamente después de 3 segundos
 */
function mostrarAlertaExito(mensaje) {
    alertaExito.textContent = '✅ ' + mensaje;
    alertaExito.classList.remove('hidden');
    
    // Realizar scroll suave hacia la parte superior de la página
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Ocultar automáticamente la alerta después de 3 segundos
    setTimeout(() => {
        alertaExito.classList.add('hidden');
    }, 3000);
}

// ────────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: CARGAR DATOS INICIALES DEL SERVIDOR
// ────────────────────────────────────────────────────────────────────────────────

/**
 * Obtiene y carga los datos del perfil del usuario desde el servidor
 * 
 * Proceso:
 *   1. Realiza una petición GET a actualizarPerfil.php?action=get_profile
 *   2. Parsea la respuesta JSON
 *   3. Rellena los campos del formulario con los datos recibidos
 *   4. Guarda los IDs de usuario y paciente para operaciones posteriores
 *   5. Crea una copia de los datos originales para cancelación
 * 
 * Errores manejados:
 *   - Error HTTP (response.ok === false)
 *   - Respuesta JSON inválida
 *   - Datos faltantes en la respuesta
 */
async function cargarDatos() {
    try {
        console.log("🔄 Solicitando datos del perfil...");
        
        // Realizar petición GET asíncrona al servidor para obtener los datos del perfil
        const response = await fetch("actualizarPerfil.php?action=get_profile");
        
        // Obtener el texto completo de la respuesta para inspección
        const text = await response.text();
        console.log("Respuesta RAW del servidor:", text);
        console.log("Status code:", response.status);
        
        // Validar que la respuesta HTTP sea exitosa (status 200-299)
        if (!response.ok) {
            console.error("❌ El servidor respondió con error:", response.status);
            alert("Error del servidor: " + text);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Parsear la respuesta como JSON
        const data = JSON.parse(text);
        console.log("Respuesta parseada:", data);
        
        // Validar que la respuesta contenga datos válidos
        if (!data.success || !data.data) {
            console.error("Error en la respuesta:", data.message);
            alert(data.message || "No se pudieron cargar los datos del perfil");
            return;
        }
        
        // Asignar los datos recibidos a una variable local para fácil lectura
        const p = data.data;
        
        // Guardar los IDs necesarios para operaciones de actualización (UPDATE)
        datosActuales = {
            id_paciente: p.ID_Paciente || 0, // 0 si el usuario no tiene paciente asociado
            id_usuario: p.ID_Usuario        // ID del usuario actual (siempre debe existir)
        };
        
        // Rellenar todos los campos del formulario con los datos del servidor
        nombre.value = p.Nombre_Paciente || '';
        apellido.value = p.Apellido_Paciente || '';
        edad.value = p.Edad || '';
        telefono.value = p.Telefono_Paciente || '';
        email.value = p.email || '';
        // Extraer solo la fecha (sin hora) del campo Fecha_Registro
        fecha.value = p.Fecha_Registro ? p.Fecha_Registro.split(' ')[0] : '';
        
        // Guardar una copia de los datos originales para restaurarlos si se cancela la edición
        datosOriginales = {
            nombre: nombre.value,
            apellido: apellido.value,
            edad: edad.value,
            telefono: telefono.value
        };
        
        console.log("✅ Datos cargados correctamente");
        console.log("IDs guardados:", datosActuales);
        
    } catch (err) {
        console.error("❌ Error cargando datos:", err);
        alert("Error al cargar los datos del perfil. Por favor, recarga la página.");
    }
}

/**
 * Ejecuta la función cargarDatos() cuando el DOM esté completamente listo
 * 
 * Verificación de estado del documento:
 *   - Si document.readyState === 'loading': Espera el evento DOMContentLoaded
 *   - Si ya cargó: Ejecuta inmediatamente
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cargarDatos);
} else {
    cargarDatos();
}

// ────────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: MANEJADORES DE EVENTOS - BOTONES DE ACCIÓN
// ────────────────────────────────────────────────────────────────────────────────

/**
 * EVENTO: Click en botón EDITAR
 * 
 * Acciones realizadas:
 *   1. Limpia todos los mensajes de error previos
 *   2. Limpia la alerta de éxito
 *   3. Habilita todos los campos para edición
 *   4. Aplica estilos visuales de modo edición (fondo blanco, borde azul)
 *   5. Oculta el botón Editar
 *   6. Muestra los botones Guardar y Cancelar
 *   7. Cambia la bandera modoEdicion a true
 */
btnEditar.addEventListener("click", () => {
    // Limpiar cualquier mensaje de error o éxito previo
    limpiarErrores();
    limpiarExitos();
    
    // Habilitar todos los campos de entrada para que el usuario pueda modificarlos
    [nombre, apellido, edad, telefono].forEach(campo => {
        campo.disabled = false;
        // Remover estilos de solo lectura
        campo.classList.remove('bg-subtle-light', 'dark:bg-subtle-dark');
        // Aplicar estilos de campo activo/editable
        campo.classList.add('bg-white', 'dark:bg-gray-800', 'ring-2', 'ring-primary');
    });
    
    // Cambiar la visibilidad de los botones según el contexto
    btnEditar.classList.add('hidden');      // Ocultar el botón Editar
    btnGuardar.classList.remove('hidden');  // Mostrar el botón Guardar
    btnCancelar.classList.remove('hidden'); // Mostrar el botón Cancelar
    
    // Actualizar el estado global del formulario
    modoEdicion = true;
    console.log("✏️ Modo edición activado");
});

/**
 * EVENTO: Click en botón GUARDAR
 * 
 * Proceso:
 *   1. Valida todos los campos (nombre, apellido, edad, teléfono)
 *   2. Si hay errores, muestra mensajes de error y retorna
 *   3. Si es válido, prepara los datos en un objeto JSON
 *   4. Envía los datos al servidor mediante POST AJAX a actualizarPerfil.php
 *   5. Si el servidor responde exitosamente:
 *      - Muestra mensaje de éxito
 *      - Desactiva modo edición
 *      - Oculta botones Guardar y Cancelar
 *      - Muestra botón Editar
 *   6. Si hay error, muestra mensaje de error al usuario
 */
btnGuardar.addEventListener("click", async () => {
    // Limpiar todos los mensajes de error previos antes de validar
    limpiarErrores();
    
    // Bandera para controlar si hay errores de validación
    let hayError = false;
    
    // VALIDACIÓN 1: Nombre obligatorio y no vacío
    if (!nombre.value.trim()) {
        errorNombre.textContent = 'El nombre es obligatorio';
        hayError = true;
    }
    
    // VALIDACIÓN 2: Apellido obligatorio y no vacío
    if (!apellido.value.trim()) {
        errorApellido.textContent = 'El apellido es obligatorio';
        hayError = true;
    }
    
    // VALIDACIÓN 3: Edad debe estar entre 18 y 120 años
    const edadNum = parseInt(edad.value);
    if (edadNum < 18 || edadNum > 120) {
        errorEdad.textContent = 'Edad inválida (debe ser 18-120 años)';
        hayError = true;
    }
    
    // VALIDACIÓN 4: Teléfono requerido con mínimo 6 caracteres
    if (!telefono.value.trim() || telefono.value.trim().length < 6) {
        errorTelefono.textContent = 'Teléfono inválido (mínimo 6 caracteres)';
        hayError = true;
    }
    
    // Si hay errores de validación, detener la ejecución y no enviar nada al servidor
    if (hayError) {
        return;
    }
    
    // Validación crítica: Verificar que los IDs necesarios están disponibles
    if (!datosActuales.id_paciente || !datosActuales.id_usuario) {
        alert("Error: No se han cargado los datos del paciente correctamente");
        console.error("Datos actuales:", datosActuales);
        return;
    }
    
    // Preparar objeto JSON con los datos validados para enviar al servidor
    const datos = {
        id_paciente: datosActuales.id_paciente || 0,  // 0 si el usuario no tiene paciente asociado
        id_usuario: datosActuales.id_usuario,         // ID del usuario actual
        nombre: nombre.value.trim(),                   // Eliminar espacios en blanco
        apellido: apellido.value.trim(),               // Eliminar espacios en blanco
        edad: edadNum,                                 // Valor numérico validado
        telefono: telefono.value.trim()                // Eliminar espacios en blanco
    };
    
    console.log("📤 Enviando datos al servidor:", datos);

    try {
        // Enviar los datos validados al servidor mediante POST AJAX
        const response = await fetch("actualizarPerfil.php", {
            method: "POST",                           // Método HTTP POST
            headers: { 
                "Content-Type": "application/json",   // Tipo de contenido JSON
                "X-Requested-With": "XMLHttpRequest"  // Identificador de AJAX
            },
            body: JSON.stringify(datos)                // Serializar objeto a JSON
        });

        // Registrar el código de estado HTTP
        console.log("📥 Status de respuesta:", response.status);
        const text = await response.text();
        console.log("📥 Respuesta RAW del servidor:", text);

        // Validar que la respuesta HTTP sea exitosa
        if (!response.ok) {
            console.error("❌ Error HTTP:", response.status, text);
            throw new Error(`Error HTTP ${response.status}: ${text}`);
        }

        // Parsear la respuesta JSON
        const result = JSON.parse(text);
        console.log("📥 Respuesta parseada:", result);
        
        // Verificar si la operación fue exitosa
        if (result.success) {
            // ✅ ÉXITO: Los cambios se guardaron correctamente en la base de datos
            
            // Limpiar todos los mensajes de error
            limpiarErrores();
            
            // Mostrar notificación de éxito al usuario
            mostrarAlertaExito('Datos actualizados correctamente');
            
            // Actualizar la copia de datos originales con los nuevos valores
            datosOriginales = {
                nombre: nombre.value,
                apellido: apellido.value,
                edad: edad.value,
                telefono: telefono.value
            };
            
            // Deshabilitar todos los campos y volver al modo de solo lectura
            [nombre, apellido, edad, telefono].forEach(campo => {
                campo.disabled = true;
                // Remover estilos de edición
                campo.classList.remove('bg-white', 'dark:bg-gray-800', 'ring-2', 'ring-primary');
                // Aplicar estilos de solo lectura
                campo.classList.add('bg-subtle-light', 'dark:bg-subtle-dark');
            });
            
            // Actualizar visibilidad de los botones
            btnEditar.classList.remove('hidden');    // Mostrar botón Editar
            btnGuardar.classList.add('hidden');      // Ocultar botón Guardar
            btnCancelar.classList.add('hidden');     // Ocultar botón Cancelar
            
            // Cambiar el estado global del formulario
            modoEdicion = false;
            console.log("✅ Modo edición desactivado - Cambios guardados correctamente");
        } else {
            // ❌ ERROR: El servidor rechazó los cambios por alguna razón
            
            // Mostrar el mensaje de error apropiado al usuario
            if (result.message.includes('No se realizaron cambios')) {
                // Si no hay cambios, mostrar como advertencia
                mostrarAlertaGeneral("⚠️ " + result.message, 'warning');
            } else {
                // Si hay otro error, mostrar como error
                mostrarAlertaGeneral("❌ " + result.message, 'error');
            }
        }
    } catch (err) {
        // Capturar cualquier error no manejado (error de red, parsing, etc.)
        console.error("❌ Error completo:", err);
        mostrarAlertaGeneral("❌ Error al guardar: " + err.message, 'error');
    }
});

/**
 * EVENTO: Click en botón CANCELAR
 * 
 * Acciones realizadas:
 *   1. Restaura todos los valores originales en los campos
 *   2. Limpia los mensajes de error y éxito
 *   3. Deshabilita los campos para modo de solo lectura
 *   4. Cambia los estilos a modo lectura
 *   5. Oculta botones Guardar y Cancelar
 *   6. Muestra botón Editar
 *   7. Desactiva el modo edición
 * 
 * Nota: Los cambios realizados pero no guardados se pierden
 */
btnCancelar.addEventListener("click", () => {
    // Restaurar los valores originales en todos los campos del formulario
    nombre.value = datosOriginales.nombre;
    apellido.value = datosOriginales.apellido;
    edad.value = datosOriginales.edad;
    telefono.value = datosOriginales.telefono;
    
    // Limpiar todos los mensajes de error y éxito
    limpiarErrores();
    limpiarExitos();
    
    // Deshabilitar todos los campos y aplicar estilos de solo lectura
    [nombre, apellido, edad, telefono].forEach(campo => {
        campo.disabled = true;
        // Remover estilos de campo activo/editable
        campo.classList.remove('bg-white', 'dark:bg-gray-800', 'ring-2', 'ring-primary');
        // Aplicar estilos de campo deshabilitado/lectura
        campo.classList.add('bg-subtle-light', 'dark:bg-subtle-dark');
    });
    
    // Actualizar visibilidad de los botones
    btnEditar.classList.remove('hidden');    // Mostrar botón Editar
    btnGuardar.classList.add('hidden');      // Ocultar botón Guardar
    btnCancelar.classList.add('hidden');     // Ocultar botón Cancelar
    
    // Cambiar el estado global del formulario
    modoEdicion = false;
    console.log("❌ Edición cancelada - Datos restaurados a valores originales");
});

// ────────────────────────────────────────────────────────────────────────────────
// FIN DEL SCRIPT
// ────────────────────────────────────────────────────────────────────────────────