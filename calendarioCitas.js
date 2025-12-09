/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT: calendarioCitas.js
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Inicializa y configura FullCalendar en el contenedor #calendar
 *   - Carga eventos (citas) desde el servidor mediante AJAX
 *   - Implementa coloración de eventos según estado de la cita
 *   - Proporciona modal interactivo para ver detalles de citas
 *   - Soporta múltiples formas de cerrar modal (botón, backdrop, ESC)
 *   - Interfaz completamente en español
 * 
 * Características principales:
 *   - Vista por semana (timeGridWeek) por defecto
 *   - Opciones de vista: Mes, Semana, Día
 *   - Horario configurado de 6 AM a 9 PM
 *   - Indicador de hora actual en tiempo real
 *   - Carga dinámica de eventos desde calendarioCitas.php
 *   - Codificación de colores por estado de cita
 * 
 * Dependencias:
 *   - FullCalendar 6.1.8
 *   - jQuery 3.5.1 (para AJAX)
 *   - calendarioCitas_View.php (vista HTML)
 *   - calendarioCitas.php (backend API)
 * 
 * Estados de cita y colores:
 *   - Pendiente:   Naranja (#f59e0b)
 *   - Confirmada:  Verde (#10b981)
 *   - Completada:  Azul (#3b82f6)
 *   - Cancelada:   Rojo (#ef4444)
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * Esperar a que el DOM esté completamente cargado antes de inicializar FullCalendar
 * Esto asegura que todos los elementos HTML necesarios estén disponibles
 */
document.addEventListener('DOMContentLoaded', function() {
  
  // ────────────────────────────────────────────────────────────────────────────
  // SECCIÓN 1: REFERENCIAS A ELEMENTOS DEL DOM
  // ────────────────────────────────────────────────────────────────────────────

  /**
   * Obtener referencias a elementos del DOM para manipular el calendario y modal
   * 
   * - calendarEl: Contenedor principal del calendario
   * - modalEl: Ventana modal que muestra detalles
   * - modalContent: Área de contenido dentro del modal
   * - closeBtn: Botón para cerrar el modal
   */
  const calendarEl = document.getElementById('calendar');
  const modalEl = document.getElementById('modal');
  const modalContent = document.getElementById('modal-content');
  const closeBtn = document.getElementById('closeModal');

  /**
   * Validar que exista el contenedor del calendario
   * Si no existe, salir del script
   */
  if (!calendarEl) return;
  
  /**
   * Obtener el ID del psicólogo desde atributo data-doctor
   * Este ID se usa para cargar las citas específicas de este psicólogo
   * Se pasa desde calendarioCitas_View.php
   */
  const idDoctor = calendarEl.dataset.doctor;

  // ────────────────────────────────────────────────────────────────────────────
  // SECCIÓN 2: INICIALIZACIÓN DE FULLCALENDAR
  // ────────────────────────────────────────────────────────────────────────────

  /**
   * Crear instancia de FullCalendar con configuración personalizada
   */
  const calendar = new FullCalendar.Calendar(calendarEl, {
    /**
     * VISTA INICIAL
     * timeGridWeek: Muestra una semana con horas (por defecto)
     * Otras opciones: dayGridMonth, timeGridDay, etc.
     */
    initialView: 'timeGridWeek',
    
    /**
     * IDIOMA: Español
     * Traduce todos los textos: nombres de meses, días, botones, etc.
     */
    locale: 'es',
    
    /**
     * INDICADOR DE HORA ACTUAL
     * Muestra una línea que indica la hora actual en el calendario
     */
    nowIndicator: true,
    
    /**
     * HORARIO DE FUNCIONAMIENTO
     * slotMinTime: Hora inicial (6:00 AM)
     * slotMaxTime: Hora final (9:00 PM - 21:00)
     * Las citas fuera de este rango no se mostrarán
     */
    slotMinTime: '06:00:00',
    slotMaxTime: '21:00:00',
    
    /**
     * PRIMER DÍA DE LA SEMANA
     * 1 = Lunes (en lugar de Domingo)
     * Configuración común en países hispanohablantes
     */
    firstDay: 1,
    
    /**
     * BARRA DE HERRAMIENTAS (Header Toolbar)
     * 
     * left: Botones de navegación (anterior, siguiente, hoy)
     * center: Título del mes/semana/día actual
     * right: Botones de cambio de vista (mes, semana, día)
     */
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    
    /**
     * TEXTOS DE BOTONES PERSONALIZADOS
     * Traduce los nombres de los botones a español
     */
    buttonText: {
      today: 'Hoy',
      month: 'Mes',
      week: 'Semana',
      day: 'Día'
    },
    
    /**
     * FORMATO DE HORA DE EVENTOS
     * '2-digit': Formato de 2 dígitos (01, 02, ... 23, 24)
     * meridiem: false = No mostrar AM/PM (usar formato 24h)
     * Ejemplo: "14:30" en lugar de "2:30 PM"
     */
    eventTimeFormat: { 
      hour: '2-digit', 
      minute: '2-digit', 
      meridiem: false 
    },

    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 3: CARGAR EVENTOS VÍA AJAX
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Función para cargar eventos dinámicamente
     * 
     * Se ejecuta cuando:
     *   - Se inicializa el calendario
     *   - Se cambia de mes/semana/día
     *   - Se navega el calendario
     * 
     * @param {object} fetchInfo - Información sobre el rango de fechas solicitado
     * @param {function} successCallback - Llamar con array de eventos
     * @param {function} failureCallback - Llamar si hay error
     */
    events: function(fetchInfo, successCallback, failureCallback) {
      /**
       * Realizar solicitud AJAX GET a calendarioCitas.php
       * 
       * Parámetros:
       *   - idDoctor: ID del psicólogo para obtener sus citas
       * 
       * Respuesta esperada:
       *   Array de objetos con estructura:
       *   [
       *     {
       *       title: "Nombre Paciente",
       *       start: "2024-12-10T14:00:00",
       *       extendedProps: {
       *         fecha: "10/12/2024",
       *         hora: "14:00",
       *         paciente: "Nombre Paciente",
       *         motivo: "Consulta general",
       *         estado: "Confirmada"
       *       }
       *     },
       *     ...más citas
       *   ]
       */
      $.ajax({
        url: 'calendarioCitas.php',
        type: 'GET',
        data: { idDoctor: idDoctor },
        dataType: 'json',
        success: function(response) {
          /**
           * Si la solicitud fue exitosa, pasar eventos al calendario
           * FullCalendar procesará automáticamente el array
           */
          successCallback(response);
        },
        error: function(xhr, status, error) {
          /**
           * Si hay error, registrar en consola para debugging
           * y llamar al callback de error
           */
          console.error("Error cargando eventos:", error);
          failureCallback(error);
        }
      });
    },

    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 4: COLOREAR EVENTOS SEGÚN ESTADO
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Función que se ejecuta cuando un evento es montado en el DOM
     * Permite personalizar el aspecto visual de cada evento
     * 
     * @param {object} info - Información del evento y elemento DOM
     */
    eventDidMount: function(info) {
      /**
       * Obtener el estado de la cita desde las propiedades extendidas
       * Convertir a minúsculas para comparación segura
       */
      const estado = (info.event.extendedProps.estado || '').toLowerCase();
      
      /**
       * Variables para color de fondo y texto
       * Color por defecto: gris
       */
      let bg = '#9ca3af';
      let color = 'white';

      /**
       * CODIFICACIÓN DE COLORES POR ESTADO
       * Cambia el color de fondo del evento según su estado
       * 
       * Paleta de colores:
       *   - Pendiente: Naranja (#f59e0b) - Requiere atención
       *   - Confirmada: Verde (#10b981) - Confirmada y lista
       *   - Completada: Azul (#3b82f6) - Sesión finalizada
       *   - Cancelada: Rojo (#ef4444) - Cancelada/No asignada
       */
      switch (estado) {
        case 'pendiente':
          bg = '#f59e0b';  // Naranja
          break;
        case 'confirmada':
          bg = '#10b981';  // Verde
          break;
        case 'completada':
          bg = '#3b82f6';  // Azul
          break;
        case 'cancelada':
          bg = '#ef4444';  // Rojo
          break;
      }

      /**
       * Aplicar estilos CSS al elemento del evento
       * Estos cambios se reflejan inmediatamente en el calendario
       */
      info.el.style.backgroundColor = bg;
      info.el.style.color = color;
      info.el.style.borderColor = bg;
    },

    // ────────────────────────────────────────────────────────────────────────
    // SECCIÓN 5: MOSTRAR MODAL AL HACER CLICK EN EVENTO
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Función que se ejecuta cuando el usuario hace click en un evento
     * Abre el modal con los detalles de la cita
     * 
     * @param {object} info - Información del evento clickeado
     */
    eventClick: function(info) {
      /**
       * Obtener propiedades extendidas del evento
       * Contiene información adicional como fecha, hora, paciente, etc.
       */
      const props = info.event.extendedProps;
      
      /**
       * Generar contenido HTML del modal con los detalles de la cita
       * 
       * Información mostrada:
       *   - 📅 Fecha: Fecha formateada de la cita
       *   - ⏰ Hora: Hora exacta de la cita
       *   - 👤 Paciente: Nombre completo del paciente
       *   - 📝 Motivo: Razón o tipo de consulta
       *   - 📌 Estado: Estado actual de la cita
       */
      modalContent.innerHTML = `
        <p><strong>📅 Fecha:</strong> ${props.fecha}</p>
        <p><strong>⏰ Hora:</strong> ${props.hora}</p>
        <p><strong>👤 Paciente:</strong> ${props.paciente}</p>
        <p><strong>📝 Motivo:</strong> ${props.motivo}</p>
        <p><strong>📌 Estado:</strong> ${props.estado}</p>
      `;
      
      /**
       * Mostrar modal: Remover clase 'hidden' para hacerlo visible
       */
      modalEl.classList.remove('hidden');
    }
  });

  // ────────────────────────────────────────────────────────────────────────
  // SECCIÓN 6: RENDERIZAR EL CALENDARIO
  // ────────────────────────────────────────────────────────────────────────

  /**
   * Renderizar (dibujar) el calendario en el contenedor
   * Esto crea toda la estructura HTML y carga los eventos iniciales
   */
  calendar.render();

  // ────────────────────────────────────────────────────────────────────────
  // SECCIÓN 7: MANEJO DE EVENTOS PARA CERRAR MODAL
  // ────────────────────────────────────────────────────────────────────────

  /**
   * OPCIÓN 1: Cerrar modal con botón "Cerrar"
   * Validar que el botón existe antes de agregar listener
   */
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      // Ocultar modal: Agregar clase 'hidden'
      modalEl.classList.add('hidden');
    });
  }

  /**
   * OPCIÓN 2: Cerrar modal haciendo click en el fondo oscuro (backdrop)
   * Seleccionar el elemento del overlay semitransparente
   * Si el usuario hace click en el área oscura, cerrar modal
   */
  const backdrop = modalEl?.querySelector('.absolute.inset-0');
  backdrop?.addEventListener('click', () => {
    // Ocultar modal
    modalEl.classList.add('hidden');
  });

  /**
   * OPCIÓN 3: Cerrar modal presionando la tecla Escape (ESC)
   * Proporciona una forma rápida de cerrar el modal con teclado
   * 
   * Nota: Esta es una buena práctica de UX
   */
  document.addEventListener('keydown', (e) => {
    // Verificar si la tecla presionada es Escape
    if (e.key === 'Escape') {
      // Ocultar modal
      modalEl.classList.add('hidden');
    }
  });
});










