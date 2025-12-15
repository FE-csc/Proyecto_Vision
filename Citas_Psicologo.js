/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT: Citas_Psicologo.js
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Gestión completa de citas para psicólogos
 *   - Carga, visualiza, filtra y ordena citas de un psicólogo
 *   - Permite cambiar el estado de citas (Pendiente, Confirmada, Completada, Cancelada)
 *   - Implementa búsqueda por nombre de paciente y filtrado por estado
 *   - Interfaz responsiva con Tailwind CSS y tema oscuro
 * 
 * Funcionalidades principales:
 *   - Cargar citas desde Citas_Psicologo.php
 *   - Renderizar tabla dinámicamente con datos de citas
 *   - Filtrar citas por nombre de paciente (búsqueda en tiempo real)
 *   - Filtrar citas por estado
 *   - Ordenar citas por fecha (ascendente/descendente)
 *   - Ordenar citas por ID (ascendente/descendente)
 *   - Modal para cambiar estado de cita
 *   - Alertas de éxito y error con auto-cerrado
 *   - Loader/spinner durante operaciones asincrónicas
 * 
 * Parámetros de API:
 *   GET Citas_Psicologo.php?action=get_citas
 *     - Respuesta: { success: true, data: [...], message: "..." }
 *   
 *   POST Citas_Psicologo.php
 *     - Body: { id_cita: number, estado: string }
 *     - Respuesta: { success: true, message: "..." }
 * 
 * Estados de cita:
 *   - Pendiente: Cita pendiente de confirmación (naranja)
 *   - Confirmada: Cita confirmada con paciente (verde)
 *   - Completada: Sesión realizada (azul)
 *   - Cancelada: Cita cancelada (rojo)
 * 
 * Dependencias:
 *   - Citas_Psicologo.php (backend API)
 *   - Elementos HTML: tbody_citas, loader, alertaGeneral, alertaExito
 *   - Filtros: f_paciente, f_estado, f_ordenamiento
 *   - Modal: modalCambiarEstado, modalEstadoSelect
 * ════════════════════════════════════════════════════════════════════════════════
 */

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: REFERENCIAS A ELEMENTOS DEL DOM
// ════════════════════════════════════════════════════════════════════════════════

/**
 * Obtener referencias a elementos del DOM para la gestión de citas
 * 
 * tbody:                Cuerpo de la tabla donde se renderizan las citas
 * loader:               Spinner/loader que indica carga
 * alertaGeneral:        Alerta para mostrar errores
 * alertaExito:          Alerta para mostrar mensajes de éxito
 * filtroNombrePaciente: Input para filtrar por nombre
 * filtroEstado:         Select para filtrar por estado
 * modalCambiarEstado:   Modal para cambiar estado de cita
 * modalEstadoSelect:    Select dentro del modal
 */
const tbody = document.getElementById('tbody_citas');
const loader = document.getElementById('loader');
const alertaGeneral = document.getElementById('alertaGeneral');
const alertaExito = document.getElementById('alertaExito');
const filtroNombrePaciente = document.getElementById('f_paciente');
const filtroEstado = document.getElementById('f_estado');
const modalCambiarEstado = document.getElementById('modalCambiarEstado');
const modalEstadoSelect = document.getElementById('modalEstadoSelect');

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: VARIABLES GLOBALES
// ════════════════════════════════════════════════════════════════════════════════

/**
 * citas: Array que almacena todas las citas del psicólogo cargadas desde el servidor
 * Se usa como referencia para filtrar y ordenar sin hacer nuevas solicitudes
 * 
 * citaActualEnEdicion: ID de la cita que se está editando en el modal
 */
let citas = [];
let citaActualEnEdicion = null;

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: FUNCIONES DE ALERTAS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * MOSTRAR ALERTA DE ÉXITO
 * 
 * Parámetros:
 *   - mensaje: Texto a mostrar en la alerta
 * 
 * Comportamiento:
 *   - Muestra alerta verde en la parte superior
 *   - Hace scroll hacia arriba automáticamente
 *   - Se auto-cierra después de 3 segundos
 */
function mostrarAlertaExito(mensaje) {
    alertaExito.textContent = mensaje;
    alertaExito.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => {
        alertaExito.classList.add('hidden');
    }, 3000);
}

/**
 * MOSTRAR ALERTA GENERAL (Error o Warning)
 * 
 * Parámetros:
 *   - mensaje: Texto a mostrar
 *   - tipo: 'error' (rojo) o 'warning' (amarillo)
 * 
 * Comportamiento:
 *   - Limpia clases anteriores
 *   - Aplica estilos según tipo
 *   - Hace scroll hacia arriba
 *   - Se auto-cierra después de 4 segundos
 * 
 * Clases CSS:
 *   - Error: bg-red-100, border-red-400, text-red-700
 *   - Warning: bg-yellow-100, border-yellow-400, text-yellow-700
 */
function mostrarAlertaGeneral(mensaje, tipo = 'error') {
    alertaGeneral.textContent = mensaje;
    alertaGeneral.classList.remove('hidden', 'bg-red-100', 'bg-yellow-100', 'border-red-400', 'border-yellow-400', 'text-red-700', 'text-yellow-700');
    
    if (tipo === 'error') {
        alertaGeneral.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-700');
    } else if (tipo === 'warning') {
        alertaGeneral.classList.add('bg-yellow-100', 'border', 'border-yellow-400', 'text-yellow-700');
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => {
        alertaGeneral.classList.add('hidden');
    }, 4000);
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 4: CARGAR CITAS DEL SERVIDOR
// ════════════════════════════════════════════════════════════════════════════════

/**
 * CARGAR CITAS DESDE EL SERVIDOR
 * 
 * Flujo:
 *   1. Mostrar loader
 *   2. Realizar solicitud GET a Citas_Psicologo.php?action=get_citas
 *   3. Validar respuesta HTTP y success flag
 *   4. Almacenar en variable global 'citas'
 *   5. Renderizar tabla o mostrar mensaje vacío
 *   6. Mostrar alerta de error si falla
 *   7. Ocultar loader siempre (finally)
 * 
 * Nota: credentials='include' envía cookies de sesión para autenticación
 */
async function cargarCitas() {
    try {
        console.log("Cargando citas del psicólogo...");
        loader.classList.remove('hidden');
        tbody.innerHTML = '';
        
        /**
         * SOLICITUD AJAX GET
         * action=get_citas: Indica qué acción ejecutar en el backend
         * credentials='include': Incluye cookies de sesión
         */
        const response = await fetch('Citas_Psicologo.php?action=get_citas', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        /**
         * PARSING DE RESPUESTA
         * Se obtiene primero como texto para debugging
         * Luego se parsea como JSON
         */
        const text = await response.text();
        
        console.log("Respuesta RAW:", text);
        console.log("Status HTTP:", response.status);
        
        const data = JSON.parse(text);
        console.log("Datos parseados:", data);
        
        /**
         * VALIDACIÓN DE RESPUESTA
         * Se verifica tanto el status HTTP como el flag 'success'
         */
        if (!response.ok || !data.success) {
            mostrarAlertaGeneral((data.message || 'Error al cargar las citas'), 'error');
            loader.classList.add('hidden');
            return;
        }
        
        /**
         * ALMACENAR CITAS EN VARIABLE GLOBAL
         * Se usará para filtrar y ordenar sin nuevas solicitudes al servidor
         */
        citas = data.data;
        console.log("Citas cargadas:", citas);
        
        /**
         * RENDERIZAR TABLA
         * Si no hay citas, mostrar mensaje vacío
         * Si hay citas, renderizar en tabla
         */
        if (citas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-8">No tienes citas programadas</td></tr>';
        } else {
            renderizarCitas(citas);
        }
        
    } catch (err) {
        console.error("Error:", err);
        mostrarAlertaGeneral('Error al cargar las citas: ' + err.message, 'error');
    } finally {
        loader.classList.add('hidden');
    }
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 5: RENDERIZAR TABLA DE CITAS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * RENDERIZAR CITAS EN LA TABLA
 * 
 * Parámetros:
 *   - citasAMostrar: Array de citas a mostrar
 * 
 * Proceso:
 *   1. Validar que haya citas
 *   2. Para cada cita:
 *      - Formatear fecha (DD/MM/YYYY)
 *      - Formatear hora (HH:MM)
 *      - Concatenar nombre completo del paciente
 *      - Obtener teléfono y correo (o N/A si no existe)
 *      - Asignar clase CSS según estado
 *   3. Crear fila HTML con datos
 *   4. Agregar botón para cambiar estado
 * 
 * Clases de estado:
 *   - estado-pendiente: Naranja
 *   - estado-confirmada: Verde
 *   - estado-completada: Azul
 *   - estado-cancelada: Rojo
 */
function renderizarCitas(citasAMostrar) {
    /**
     * VALIDACIÓN: Si no hay citas, mostrar mensaje
     */
    if (citasAMostrar.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-gray-500 py-8">No hay resultados</td></tr>';
        return;
    }
    
    /**
     * MAPEAR ARRAY DE CITAS A FILAS HTML
     * Para cada cita se genera una fila <tr> con todos sus datos
     */
    tbody.innerHTML = citasAMostrar.map(cita => {
        /**
         * FORMATEAR FECHA
         * Convertir de ISO 8601 a formato DD/MM/YYYY
         */
        const fecha = new Date(cita.Fecha_Cita);
        const fechaFormato = fecha.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
        
        /**
         * FORMATEAR HORA
         * Convertir a formato HH:MM
         */
        const horaFormato = fecha.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        /**
         * DATOS DEL PACIENTE
         * Concatenar nombre y apellido, usar N/A si falta información
         */
        const nombrePaciente = `${cita.Nombre_Paciente} ${cita.Apellido_Paciente}`;
        const telefonoPaciente = cita.Paciente_Telefono || 'N/A';
        const correoPaciente = cita.Correo_Paciente || 'N/A';
        
        /**
         * DETERMINAR CLASE DE ESTADO
         * Se usa para colorear la insignia de estado con CSS
         */
        let claseEstado = '';
        if (cita.Estado === 'Pendiente') claseEstado = 'estado-pendiente';
        else if (cita.Estado === 'Confirmada') claseEstado = 'estado-confirmada';
        else if (cita.Estado === 'Completada') claseEstado = 'estado-completada';
        else if (cita.Estado === 'Cancelada') claseEstado = 'estado-cancelada';
        
        /**
         * GENERAR FILA HTML
         * Contiene todos los datos de la cita y botón de acción
         */
        return `
            <tr class="hover:bg-gray-100 dark:hover:bg-neutral-border-dark/50">
                <td class="p-4 text-sm">#${cita.ID_Cita}</td>
                <td class="p-4 text-sm font-medium">${nombrePaciente}</td>
                <td class="p-4 text-sm">${telefonoPaciente}</td>
                <td class="p-4 text-sm">${correoPaciente}</td>
                <td class="p-4 text-sm">${fechaFormato} ${horaFormato}</td>
                <td class="p-4 text-sm">${cita.Motivo}</td>
                <td class="p-4 text-sm text-center">${cita.Duracion}</td>
                <td class="p-4 text-sm">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${claseEstado}">
                        ${cita.Estado}
                    </span>
                </td>
                <td class="p-4 text-sm text-right">
                    <button onclick="abrirModalCambiarEstado(${cita.ID_Cita}, '${cita.Estado}', '${nombrePaciente}')" 
                            class="text-primary hover:text-primary/80 font-medium text-xs">
                        Cambiar estado
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 6: FILTROS DE CITAS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * APLICAR FILTROS A LAS CITAS
 * 
 * Se ejecuta cuando cambia cualquier filtro (nombre, estado)
 * 
 * Filtros disponibles:
 *   1. Por nombre de paciente: búsqueda parcial insensible a mayúsculas
 *   2. Por estado: coincidencia exacta
 * 
 * Proceso:
 *   1. Empezar con todas las citas
 *   2. Aplicar filtro de nombre si hay valor
 *   3. Aplicar filtro de estado si hay selección
 *   4. Renderizar resultado filtrado
 */
function aplicarFiltros() {
    let citasFiltradas = citas;
    
    /**
     * FILTRO POR NOMBRE DE PACIENTE
     * Búsqueda en tiempo real (insensible a mayúsculas)
     */
    const nombrePaciente = filtroNombrePaciente.value.toLowerCase().trim();
    if (nombrePaciente) {
        citasFiltradas = citasFiltradas.filter(cita => {
            const nombreCompleto = `${cita.Nombre_Paciente} ${cita.Apellido_Paciente}`.toLowerCase();
            return nombreCompleto.includes(nombrePaciente);
        });
    }
    
    /**
     * FILTRO POR ESTADO
     * Coincidencia exacta con los valores del select
     */
    const estado = filtroEstado.value.trim();
    if (estado) {
        citasFiltradas = citasFiltradas.filter(cita => cita.Estado === estado);
    }
    
    /**
     * RENDERIZAR RESULTADO FILTRADO
     */
    renderizarCitas(citasFiltradas);
}

/**
 * EVENT LISTENERS PARA FILTROS
 * Se ejecutan en tiempo real cuando el usuario escribe/selecciona
 */
filtroNombrePaciente.addEventListener('input', aplicarFiltros);
filtroEstado.addEventListener('change', aplicarFiltros);

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 7: MODAL Y CAMBIO DE ESTADO
// ════════════════════════════════════════════════════════════════════════════════

/**
 * ABRIR MODAL PARA CAMBIAR ESTADO
 * 
 * Parámetros:
 *   - idCita: ID de la cita a editar
 *   - estadoActual: Estado actual de la cita
 *   - nombrePaciente: Nombre del paciente para contexto
 * 
 * Acciones:
 *   1. Guardar ID en variable global
 *   2. Mostrar información en el modal
 *   3. Pre-llenar select con estado actual
 *   4. Mostrar modal (remover clase 'hidden')
 */
function abrirModalCambiarEstado(idCita, estadoActual, nombrePaciente) {
    citaActualEnEdicion = idCita;
    document.getElementById('modalCitaInfo').textContent = `Paciente: ${nombrePaciente} | Estado actual: ${estadoActual}`;
    modalEstadoSelect.value = estadoActual;
    modalCambiarEstado.classList.remove('hidden');
}

/**
 * CERRAR MODAL
 * 
 * Acciones:
 *   1. Ocultar modal (agregar clase 'hidden')
 *   2. Limpiar variable de cita en edición
 */
function cerrarModal() {
    modalCambiarEstado.classList.add('hidden');
    citaActualEnEdicion = null;
}

/**
 * GUARDAR CAMBIO DE ESTADO
 * 
 * Flujo:
 *   1. Validar que hay cita en edición
 *   2. Obtener nuevo estado del select
 *   3. Mostrar loader
 *   4. Realizar solicitud POST a Citas_Psicologo.php
 *   5. Validar respuesta
 *   6. Si éxito:
 *      - Mostrar alerta de éxito
 *      - Cerrar modal
 *      - Recargar citas
 *   7. Si error: mostrar alerta de error
 *   8. Ocultar loader siempre (finally)
 */
async function guardarCambioEstado() {
    if (!citaActualEnEdicion) return;
    
    const nuevoEstado = modalEstadoSelect.value;
    
    try {
        loader.classList.remove('hidden');
        
        /**
         * SOLICITUD AJAX POST
         * Envía ID de cita y nuevo estado al servidor
         */
        const response = await fetch('Citas_Psicologo.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_cita: citaActualEnEdicion,
                estado: nuevoEstado
            })
        });
        
        const data = await response.json();
        
        /**
         * VALIDAR RESPUESTA DEL SERVIDOR
         */
        if (!data.success) {
            mostrarAlertaGeneral(data.message, 'error');
            return;
        }
        
        /**
         * SI ÉXITO: Mostrar alerta, cerrar modal y recargar
         */
        mostrarAlertaExito('Estado de cita actualizado correctamente');
        cerrarModal();
        cargarCitas();
        
    } catch (err) {
        console.error("Error:", err);
        mostrarAlertaGeneral('Error al actualizar la cita: ' + err.message, 'error');
    } finally {
        loader.classList.add('hidden');
    }
}

/**
 * CERRAR MODAL AL HACER CLICK EN EL BACKDROP
 * 
 * Permite cerrar el modal haciendo click fuera de él
 * (en el área oscura/transparente)
 */
modalCambiarEstado.addEventListener('click', (e) => {
    if (e.target === modalCambiarEstado) {
        cerrarModal();
    }
});

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 8: ORDENAMIENTO DE CITAS
// ════════════════════════════════════════════════════════════════════════════════

/**
 * APLICAR ORDENAMIENTO DESDE SELECT
 * 
 * Parámetros soportados:
 *   - fecha_asc: Ordenar por fecha ascendente
 *   - fecha_desc: Ordenar por fecha descendente
 *   - id_asc: Ordenar por ID ascendente
 *   - id_desc: Ordenar por ID descendente
 * 
 * Proceso:
 *   1. Obtener valor del select
 *   2. Hacer copia del array para no modificar original
 *   3. Aplicar sort según parámetro
 *   4. Renderizar resultado
 */
function aplicarOrdenamiento() {
    const selectOrdenamiento = document.getElementById('f_ordenamiento');
    const tipoOrdenamiento = selectOrdenamiento.value;
    
    if (!tipoOrdenamiento || citas.length === 0) {
        renderizarCitas(citas);
        return;
    }
    
    let citasOrdenadas = [...citas];
    
    /**
     * ORDENAR SEGÚN TIPO SELECCIONADO
     */
    if (tipoOrdenamiento === 'fecha_asc') {
        citasOrdenadas.sort((a, b) => new Date(a.Fecha_Cita) - new Date(b.Fecha_Cita));
    } else if (tipoOrdenamiento === 'fecha_desc') {
        citasOrdenadas.sort((a, b) => new Date(b.Fecha_Cita) - new Date(a.Fecha_Cita));
    } else if (tipoOrdenamiento === 'id_asc') {
        citasOrdenadas.sort((a, b) => a.ID_Cita - b.ID_Cita);
    } else if (tipoOrdenamiento === 'id_desc') {
        citasOrdenadas.sort((a, b) => b.ID_Cita - a.ID_Cita);
    }
    
    renderizarCitas(citasOrdenadas);
}

/**
 * ORDENAR POR FECHA (Función directa)
 * 
 * Parámetros:
 *   - direccion: 'asc' (ascendente) o 'desc' (descendente)
 * 
 * Se ejecuta cuando usuario hace click en botones de ordenamiento
 */
function ordenarPorFecha(direccion) {
    if (citas.length === 0) {
        mostrarAlertaGeneral('Error: Primero debes cargar las citas', 'error');
        return;
    }
    
    const citasOrdenadas = [...citas].sort((a, b) => {
        const fechaA = new Date(a.Fecha_Cita);
        const fechaB = new Date(b.Fecha_Cita);
        
        if (direccion === 'asc') {
            return fechaA - fechaB;
        } else {
            return fechaB - fechaA;
        }
    });
    
    renderizarCitas(citasOrdenadas);
}

/**
 * ORDENAR POR ID (Función directa)
 * 
 * Parámetros:
 *   - direccion: 'asc' (ascendente) o 'desc' (descendente)
 * 
 * Se ejecuta cuando usuario hace click en botones de ordenamiento
 */
function ordenarPorID(direccion) {
    if (citas.length === 0) {
        mostrarAlertaGeneral('Error: Primero debes cargar las citas', 'error');
        return;
    }
    
    const citasOrdenadas = [...citas].sort((a, b) => {
        if (direccion === 'asc') {
            return a.ID_Cita - b.ID_Cita;
        } else {
            return b.ID_Cita - a.ID_Cita;
        }
    });
    
    renderizarCitas(citasOrdenadas);
}

// ════════════════════════════════════════════════════════════════════════════════
// SECCIÓN 9: INICIALIZACIÓN
// ════════════════════════════════════════════════════════════════════════════════

/**
 * INICIALIZACIÓN AL CARGAR LA PÁGINA
 * 
 * Se ejecuta cuando el DOM está completamente cargado
 * Inicia la carga de citas
 */
document.addEventListener('DOMContentLoaded', () => {
    cargarCitas();
});
