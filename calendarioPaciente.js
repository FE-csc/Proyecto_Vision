/**
 * calendarioPaciente.js
 * ---------------------
 * Script que inicializa FullCalendar en el contenedor #calendar.
 * 
 * Funcionalidades:
 * - Configura idioma español y botones traducidos.
 * - Carga eventos desde calendarioPaciente.php usando AJAX (jQuery).
 * - Aplica colores según estado de la cita.
 * - Muestra detalles en un modal al hacer click en un evento.
 * - Permite cerrar el modal con botón, fondo o tecla Esc.
 */

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const modalEl = document.getElementById('modal');
  const modalContent = document.getElementById('modal-content');
  const closeBtn = document.getElementById('closeModal');

  if (!calendarEl) return;
  const idPaciente = calendarEl.dataset.paciente;

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    locale: 'es',
    nowIndicator: true,
    slotMinTime: '06:00:00',
    slotMaxTime: '21:00:00',
    firstDay: 1,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    buttonText: {
      today: 'Hoy',
      month: 'Mes',
      week: 'Semana',
      day: 'Día'
    },
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },

    // Cargar eventos vía AJAX con jQuery
    events: function(fetchInfo, successCallback, failureCallback) {
      $.ajax({
        url: 'calendarioPaciente.php',
        type: 'GET',
        data: { idPaciente: idPaciente },
        dataType: 'json',
        success: function(response) {
          successCallback(response);
        },
        error: function(xhr, status, error) {
          console.error("Error cargando eventos:", error);
          failureCallback(error);
        }
      });
    },

    // Colorear eventos según estado
    eventDidMount: function(info) {
      const estado = (info.event.extendedProps.estado || '').toLowerCase();
      let bg = '#9ca3af';
      let color = 'white';

      switch (estado) {
        case 'pendiente':  bg = '#f59e0b'; break;
        case 'confirmada': bg = '#10b981'; break;
        case 'completada': bg = '#3b82f6'; break;
        case 'cancelada':  bg = '#ef4444'; break;
      }

      info.el.style.backgroundColor = bg;
      info.el.style.color = color;
      info.el.style.borderColor = bg;
    },

    // Abrir modal con detalles al hacer click en un evento
    eventClick: function(info) {
      const props = info.event.extendedProps;
      modalContent.innerHTML = `
        <p><strong>📅 Fecha:</strong> ${props.fecha}</p>
        <p><strong>⏰ Hora:</strong> ${props.hora}</p>
        <p><strong>👨‍⚕️ Psicólogo:</strong> ${props.psicologo}</p>
        <p><strong>📝 Motivo:</strong> ${props.motivo}</p>
        <p><strong>📌 Estado:</strong> ${props.estado}</p>
      `;
      modalEl.classList.remove('hidden');
    }
  });

  calendar.render();

  // Cerrar modal
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modalEl.classList.add('hidden');
    });
  }

  // Cerrar con el fondo oscuro
  const backdrop = modalEl?.querySelector('.absolute.inset-0');
  backdrop?.addEventListener('click', () => {
    modalEl.classList.add('hidden');
  });

  // Cerrar con Esc
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      modalEl.classList.add('hidden');
    }
  });
});
