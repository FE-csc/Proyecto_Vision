/**
 * ════════════════════════════════════════════════════════════════════════════
 * EDITAR_CITA_CALENDARIO.JS - INTERFAZ DE EDICIÓN/REPROGRAMACIÓN DE CITAS
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * Gestiona la interfaz de calendario y selección de horarios para editar
 * una cita existente. Permite cambiar fecha, hora, psicólogo y tipo de servicio.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * - Renderizado de calendario mensual interactivo
 * - Selección de fecha y hora
 * - Filtrado de psicólogos por especialidad
 * - Validación de horarios ocupados
 * - Actualización de cita mediante API
 * 
 * DEPENDENCIAS:
 * - get_psicologos.php: Obtiene psicólogos por especialidad
 * - Horas_Ocupadas.php: Verifica disponibilidad de horarios
 * - Editar_Cita.php: Endpoint para actualizar la cita
 * 
 * PATRÓN: IIFE (Immediately Invoked Function Expression) para evitar
 * contaminación del scope global
 * 
 * @author Proyecto Vision
 * @version 1.0
 */

(function () {
  
    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 1: REFERENCIAS A ELEMENTOS DEL DOM
    // ────────────────────────────────────────────────────────────────────────────
    
    // Elementos del calendario
    const calendarGrid = document.getElementById('calendarGrid');
    const monthLabel = document.getElementById('calendarMonthLabel');
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');
    const selectedDateLabel = document.getElementById('selectedDateLabel');
    const timeSlots = document.getElementById('timeSlots');

    // Formulario y botón de confirmación
    const formReservaEditar = document.getElementById('formReservaEditar');
    const confirmBtn = document.getElementById('confirmBtn');

    // Selección de especialidad y psicólogo
    const therapyTypeSelect = document.getElementById('therapyType');
    const divPsicologo = document.getElementById('divPsicologo');
    const comboPsicologo = document.getElementById('comboPsicologo');

    // Campos ocultos del formulario
    const id_cita = document.getElementById('id_cita');
    const inputFecha = document.getElementById('inputFecha');
    const inputHora = document.getElementById('inputHora');
    const inputPsicologo = document.getElementById('inputPsicologo');
    const inputTipoServicio = document.getElementById('inputTipoServicio');

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 2: CONFIGURACIÓN Y CONSTANTES
    // ────────────────────────────────────────────────────────────────────────────
    
    /**
     * Horarios disponibles para citas (formato 24h)
     * Rango: 9:00 AM - 6:00 PM
     */
    const timeOptions = ['09:00', '10:00', '11:00', '12:00','13:00','14:00', '15:00', '16:00','17:00','18:00'];

    /**
     * Clases CSS para estilos del calendario
     * Diseño con Tailwind CSS incluyendo modo oscuro
     */
    const baseDayClass = 'h-12 rounded-xl border border-transparent bg-white dark:bg-slate-900 shadow-sm text-sm font-semibold text-gray-700 dark:text-gray-200 transition transform hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary/60';
    const selectedDayClasses = ['bg-primary', 'text-white', 'border-primary', 'shadow-lg'];
    const todayRingClasses = ['ring-2', 'ring-primary/50'];
    
    /**
     * Fecha actual normalizada (sin horas)
     * todayKey: Identificador único para comparaciones
     */
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayKey = `${today.getFullYear()}-${today.getMonth()}-${today.getDate()}`;

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 3: VARIABLES DE ESTADO
    // ────────────────────────────────────────────────────────────────────────────
    
    let viewDate = new Date();          // Mes/año actualmente visualizado
    let selectedDate = null;            // Fecha seleccionada por el usuario
    let selectedTime = null;            // Hora seleccionada
    let selectedDayBtn = null;          // Botón de día actualmente seleccionado (DOM)
    let horasOcupadas = [];             // Array de horas no disponibles para la fecha/psicólogo

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 4: EVENT LISTENERS - SELECCIÓN DE ESPECIALIDAD Y PSICÓLOGO
    // ────────────────────────────────────────────────────────────────────────────
   
    /**
     * Handler: Cambio de tipo de terapia/especialidad
     * - Actualiza el campo oculto inputTipoServicio
     * - Carga psicólogos disponibles mediante API
     * - Muestra/oculta el combo de psicólogos según selección
     * 
     * @listens change therapyTypeSelect
     */
    therapyTypeSelect.addEventListener('change', async function () {
        const idEspecialidad = this.value;
        inputTipoServicio.value = idEspecialidad;

        // Reset del combo de psicólogos
        comboPsicologo.innerHTML = '<option value="">-- Selecciona un psicologo --</option>';
        inputPsicologo.value = '';

        // Si no hay especialidad seleccionada, ocultar combo
        if (!idEspecialidad) {
            divPsicologo.classList.add('hidden');
            return;
        }

        divPsicologo.classList.remove('hidden');

        try {
            // Petición GET a API de psicólogos filtrados por especialidad
            const response = await fetch(`get_psicologos.php?especialidad=${encodeURIComponent(idEspecialidad)}`);
            const data = await response.json();

            if (data.success && data.psicologos && data.psicologos.length > 0) {
                // Poblar combo con psicólogos disponibles
                data.psicologos.forEach((doc) => {
                    const option = document.createElement('option');
                    option.value = doc.id;
                    option.textContent = doc.nombre;
                    comboPsicologo.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.textContent = 'No hay psicologos disponibles';
                comboPsicologo.appendChild(option);
            }
        } catch (error) {
            console.error('Error cargando psicologos', error);
            const option = document.createElement('option');
            option.textContent = 'Error al cargar psicologos';
            comboPsicologo.appendChild(option);
        }
    });

    /**
     * Handler: Cambio de psicólogo seleccionado
     * - Actualiza campo oculto inputPsicologo
     * - Recarga las horas ocupadas para el psicólogo seleccionado
     * 
     * @listens change comboPsicologo
     */
    comboPsicologo.addEventListener('change', function () {
        inputPsicologo.value = this.value;
        cargarHorasOcupadas();
    });

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 5: FUNCIONES UTILITARIAS DE FECHAS Y ESTILOS
    // ────────────────────────────────────────────────────────────────────────────
    
    /**
     * Compara si dos fechas corresponden al mismo día
     * @param {Date} a - Primera fecha
     * @param {Date} b - Segunda fecha
     * @returns {boolean} true si son el mismo día
     */
    function sameDay(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    /**
     * Resetea los estilos CSS de un botón de día a su estado base
     * @param {HTMLElement} btn - Botón a resetear
     * @param {boolean} isToday - Si el día corresponde a hoy (aplica ring especial)
     */
    function resetDayStyles(btn, isToday) {
        btn.className = baseDayClass;
        if (isToday) {
            btn.classList.add(...todayRingClasses);
        }
    }

    /**
     * Aplica estilos CSS para indicar día seleccionado
     * @param {HTMLElement} btn - Botón a estilizar
     */
    function applySelectedStyles(btn) {
        btn.classList.remove('bg-white', 'dark:bg-slate-900', 'text-gray-700', 'dark:text-gray-200');
        btn.classList.add(...selectedDayClasses);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 6: RENDERIZADO DEL CALENDARIO
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Renderiza el calendario mensual completo
     * - Calcula primer día del mes y cantidad de días
     * - Genera botones para cada día con estilos apropiados
     * - Marca el día actual y el día seleccionado
     * - Añade event listeners para selección de fecha
     */
    function renderMonth() {
        calendarGrid.innerHTML = '';
        const year = viewDate.getFullYear();
        const month = viewDate.getMonth();

        // Actualizar etiqueta del mes (ej: "diciembre 2025")
        monthLabel.textContent = viewDate.toLocaleString('es-ES', { month: 'long', year: 'numeric' });

        // Calcular cuántos días vacíos al inicio (lunes=1, domingo=0)
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Renderizar placeholders para días antes del inicio del mes
        for (let i = 0; i < firstDay; i++) {
            const placeholder = document.createElement('div');
            placeholder.className = 'h-12';
            calendarGrid.appendChild(placeholder);
        }

        // Renderizar cada día del mes
        for (let day = 1; day <= daysInMonth; day++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = day;
            btn.className = baseDayClass;

            const dateObj = new Date(year, month, day);
            const dateKey = `${dateObj.getFullYear()}-${dateObj.getMonth()}-${dateObj.getDate()}`;
            btn.dataset.dateKey = dateKey;

            // Marcar día actual con ring
            if (sameDay(dateObj, today)) {
                btn.classList.add(...todayRingClasses);
            }
            // Aplicar estilos de selección si es el día seleccionado
            if (sameDay(dateObj, selectedDate)) {
                applySelectedStyles(btn);
                selectedDayBtn = btn;
            }

            btn.addEventListener('click', () => selectDate(dateObj, btn));
            calendarGrid.appendChild(btn);
        }
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 7: SELECCIÓN DE FECHA
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Maneja la selección de una fecha en el calendario
     * - Actualiza el estado de fecha/hora seleccionada
     * - Actualiza estilos visuales del calendario
     * - Actualiza campos ocultos del formulario
     * - Recarga horarios disponibles
     * 
     * @param {Date} date - Fecha seleccionada
     * @param {HTMLElement} btn - Botón del día seleccionado
     */
    function selectDate(date, btn) {
        selectedDate = date;
        selectedTime = null;
        inputHora.value = '';

        // Resetear estilos del botón previamente seleccionado
        if (selectedDayBtn) {
            const isToday = selectedDayBtn.dataset.dateKey === todayKey;
            resetDayStyles(selectedDayBtn, isToday);
        }
        selectedDayBtn = btn;
        applySelectedStyles(btn);

        // Formatear fecha para campo oculto (YYYY-MM-DD)
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const dayName = date.toLocaleDateString('es-ES', { weekday: 'long' });
        inputFecha.value = `${year}-${month}-${day}`;
        
        // Actualizar etiqueta visible con formato legible
        selectedDateLabel.textContent = `Selecciona un horario para el ${dayName} ${day}/${month}/${year}`;

        // Recargar horarios disponibles para la nueva fecha
        cargarHorasOcupadas();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 8: RENDERIZADO DE SLOTS DE HORARIOS
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Renderiza los slots de horarios disponibles para la fecha seleccionada
     * - Crea radio buttons para cada hora en timeOptions
     * - Deshabilita horarios ocupados basándose en horasOcupadas[]
     * - Aplica estilos visuales para indicar disponibilidad
     * - Maneja selección de hora y actualización de campo oculto
     */
    function renderTimeSlots() {
        timeSlots.innerHTML = '';

        // Si no hay fecha seleccionada, mostrar mensaje
        if (!selectedDate) {
            timeSlots.innerHTML = '<p class="col-span-2 text-sm text-gray-500 dark:text-gray-400">Selecciona una fecha.</p>';
            return;
        }

        timeOptions.forEach((time) => {
            // Crear contenedor label para cada slot
            const label = document.createElement('label');
            label.className = 'flex cursor-pointer items-center gap-2 rounded-xl border border-transparent bg-white dark:bg-slate-900 px-3 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-primary transition transform';

            // Radio button (oculto visualmente con sr-only)
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'slot';
            input.value = time;
            input.className = 'sr-only';

            // Texto del horario
            const span = document.createElement('span');
            span.textContent = time;

            // Verificar si la hora está ocupada
            const isTaken = horasOcupadas.includes(time);
            if (isTaken) {
                input.disabled = true;
                label.classList.add('opacity-50', 'cursor-not-allowed');
            }

            // Marcar como seleccionado si coincide con selectedTime
            if (selectedTime === time) {
                input.checked = true;
                label.classList.add('border-primary', 'bg-primary/10', 'shadow-md');
            }

            // Event listener para selección de hora
            input.addEventListener('change', () => {
                selectedTime = time;
                inputHora.value = time;
                // Limpiar estilos de todos los labels
                document.querySelectorAll('#timeSlots label').forEach((l) => {
                    l.classList.remove('border-primary', 'bg-primary/10', 'shadow-md');
                });
                // Aplicar estilos al label seleccionado
                label.classList.add('border-primary', 'bg-primary/10', 'shadow-md');
            });

            label.appendChild(input);
            label.appendChild(span);
            timeSlots.appendChild(label);
        });
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 9: CARGA DE HORAS OCUPADAS
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Consulta y carga las horas ocupadas para el psicólogo y fecha seleccionados
     * - Realiza petición GET a Horas_Ocupadas.php
     * - Actualiza array horasOcupadas[]
     * - Re-renderiza los slots de tiempo con la nueva información
     * 
     * @async
     */
    async function cargarHorasOcupadas() {
        horasOcupadas = [];

        // Validar que estén seleccionados psicólogo y fecha
        if (!inputPsicologo.value || !inputFecha.value) {
            renderTimeSlots();
            return;
        }

        try {
            // Petición GET con parámetros id_psicologo y fecha
            const res = await fetch(`Horas_Ocupadas.php?id_psicologo=${encodeURIComponent(inputPsicologo.value)}&fecha=${encodeURIComponent(inputFecha.value)}`);
            const data = await res.json();
            
            // Actualizar array de horas ocupadas si la respuesta es exitosa
            if (data.success && Array.isArray(data.horas)) {
                horasOcupadas = data.horas;
            }
        } catch (err) {
            console.error('Error cargando horas ocupadas', err);
        }
        
        // Re-renderizar slots con la información actualizada
        renderTimeSlots();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 10: INICIALIZACIÓN
    // ────────────────────────────────────────────────────────────────────────────
    
    // Renderizar calendario y slots al cargar la página
    renderMonth();
    renderTimeSlots();

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 11: ENVÍO DEL FORMULARIO DE EDICIÓN
    // ────────────────────────────────────────────────────────────────────────────
    
    /**
     * Handler: Envío del formulario para reprogramar cita
     * - Valida que todos los campos requeridos estén completos
     * - Envía petición POST a Editar_Cita.php con datos JSON
     * - Maneja respuesta y redirige al perfil si es exitoso
     * - Muestra alertas de error si falla
     * 
     * @listens submit formReservaEditar
     */
    formReservaEditar.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validaciones de campos obligatorios
        if (!inputFecha.value) { alert('Por favor, selecciona una fecha.'); return; }
        if (!inputHora.value) { alert('Por favor, selecciona una hora.'); return; }
        if (!inputPsicologo.value) { alert('Por favor, selecciona un psicologo.'); return; }

        // Deshabilitar botón para prevenir doble envío
        confirmBtn.disabled = true;
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Procesando...';

        try {
            // Petición POST con datos de la cita en formato JSON
            const resp = await fetch('Editar_Cita.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_cita: id_cita.value,
                    fecha: inputFecha.value,
                    hora: inputHora.value,
                    id_psicologo: inputPsicologo.value,
                    tipo_servicio: inputTipoServicio.value
                })
            });

            const data = await resp.json();

            if (data.success) {
                // Éxito: Mostrar alerta y redirigir al perfil
                alert('Cita Reprogramada con exito');
                window.location.href = 'Perfil.php';
            } else {
                // Error del servidor: Mostrar mensaje y re-habilitar botón
                alert(data.message || 'Error al reservar');
                confirmBtn.disabled = false;
                confirmBtn.textContent = originalText;
            }
        } catch (err) {
            // Error de red: Mostrar mensaje y re-habilitar botón
            console.error(err);
            alert('Error de red');
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
        }
    });

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 12: NAVEGACIÓN DEL CALENDARIO
    // ────────────────────────────────────────────────────────────────────────────
    
    /**
     * Handler: Botón "Mes Anterior"
     * Retrocede el calendario un mes y re-renderiza
     * 
     * @listens click prevBtn
     */
    prevBtn.addEventListener('click', () => {
        viewDate.setMonth(viewDate.getMonth() - 1);
        renderMonth();
    });
    
    /**
     * Handler: Botón "Mes Siguiente"
     * Avanza el calendario un mes y re-renderiza
     * 
     * @listens click nextBtn
     */
    nextBtn.addEventListener('click', () => {
        viewDate.setMonth(viewDate.getMonth() + 1);
        renderMonth();
    });
})();
