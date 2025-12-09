
/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT: create.js
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Maneja la creación de cuentas completas de pacientes
 *   - Valida múltiples campos: nombre, apellido, edad, email, teléfono, contraseña
 *   - Realiza solicitud AJAX POST a register.php
 *   - Muestra alertas de éxito o error con estilos dinámicos
 *   - Desactiva botón durante el envío para evitar dobles envíos
 *   - Implementado con IIFE para evitar contaminación del scope global
 * 
 * Funcionalidades:
 *   - Validación de email con expresión regular
 *   - Validación de teléfono con expresión regular (mínimo 6 caracteres)
 *   - Validación de edad mínima (18 años)
 *   - Validación de longitud mínima de contraseña (6 caracteres)
 *   - Envío asincrónico de datos a register.php
 *   - Manejo de respuestas exitosas y errores
 *   - Gestión de errores de red
 *   - Limpieza de formulario tras registro exitoso
 *   - Feedback visual durante la operación
 * 
 * Parámetros POST (JSON):
 *   - first: Nombre del paciente
 *   - last: Apellido del paciente
 *   - age: Edad (número entero, mínimo 18)
 *   - phone: Teléfono (formato flexible)
 *   - email: Correo electrónico
 *   - password: Contraseña en texto plano (será hasheada en servidor)
 * 
 * Respuestas esperadas (register.php):
 *   Exitosa (200):
 *   {
 *     success: true,
 *     message: "Cuenta creada correctamente"
 *   }
 *   
 *   Error (400/409/500):
 *   {
 *     success: false,
 *     message: "Descripción del error"
 *   }
 * 
 * Elementos HTML requeridos:
 *   - createForm: Formulario con id
 *   - formAlert: Div para mostrar alertas
 *   - createSubmit: Botón de envío
 *   - first-name: Input para nombre
 *   - last-name: Input para apellido
 *   - age: Input para edad (tipo number)
 *   - email: Input para correo
 *   - phone: Input para teléfono
 *   - password: Input para contraseña
 * 
 * Dependencias:
 *   - register.php (backend de registro)
 *   - Fetch API (nativa del navegador)
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * IIFE (Immediately Invoked Function Expression)
 * 
 * Ventajas:
 *   - Crea un scope local para evitar contaminar el scope global
 *   - Las variables (form, alertEl, submitBtn) solo existen dentro de esta función
 *   - No se pueden acceder desde la consola o código externo
 *   - Evita conflictos con otros scripts que tengan variables con mismo nombre
 */
(function () {
    
    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 1: REFERENCIAS A ELEMENTOS DEL DOM
    // ────────────────────────────────────────────────────────────────────────────
    
    /**
     * Obtener referencias a elementos del DOM
     * 
     * - createForm: Elemento <form> principal con el id "createForm"
     * - alertEl: Div donde se muestran alertas de éxito/error
     * - submitBtn: Botón de envío del formulario
     * 
     * Se obtienen al inicio y se reutilizan en toda la función
     * para mejorar rendimiento (evita querySelector repetidos)
     */
    const form = document.getElementById('createForm');
    const alertEl = document.getElementById('formAlert');
    const submitBtn = document.getElementById('createSubmit');

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 2: FUNCIÓN DE MOSTRAR ALERTAS
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * MOSTRAR ALERTA
     * 
     * Parámetros:
     *   - message: Texto a mostrar en la alerta
     *   - type: 'error' (rojo) o 'success' (verde)
     * 
     * Comportamiento:
     *   1. Limpiar clases previas (className = '')
     *   2. Agregar clases base (rounded, p-3, text-sm)
     *   3. Agregar clases según tipo (colores y fondos)
     *   4. Asignar texto del mensaje
     *   5. Mostrar alerta (remover clase 'hidden')
     * 
     * Clases Tailwind:
     *   - Error: bg-red-50 (fondo rojo claro) + text-red-700 (texto rojo oscuro)
     *   - Success: bg-green-50 (fondo verde claro) + text-green-700 (texto verde oscuro)
     */
    function showAlert(message, type = 'error') {
        alertEl.className = '';
        alertEl.classList.add('rounded', 'p-3', 'text-sm');
        if (type === 'error') {
            alertEl.classList.add('bg-red-50', 'text-red-700');
        } else {
            alertEl.classList.add('bg-green-50', 'text-green-700');
        }
        alertEl.textContent = message;
        alertEl.classList.remove('hidden');
    }

    /**
     * LIMPIAR ALERTA
     * 
     * Acciones:
     *   1. Ocultar elemento (agregar clase 'hidden')
     *   2. Limpiar contenido de texto
     */
    function clearAlert() {
        alertEl.classList.add('hidden');
        alertEl.textContent = '';
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 3: FUNCIONES DE VALIDACIÓN
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * VALIDAR EMAIL CON EXPRESIÓN REGULAR
     * 
     * Parámetro:
     *   - email: String con el correo a validar
     * 
     * Expresión regular: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
     * 
     * Desglose:
     *   - ^: Inicio de la cadena
     *   - [^\s@]+: Uno o más caracteres que no sean espacio o @
     *   - @: Carácter arroba obligatorio
     *   - [^\s@]+: Uno o más caracteres (dominio)
     *   - \.: Punto literal (escapado)
     *   - [^\s@]+: Uno o más caracteres (extensión como "com", "org")
     *   - $: Fin de la cadena
     * 
     * Validaciones que cubre:
     *   - Requiere al menos un carácter antes de @
     *   - Requiere @ símbolo
     *   - Requiere punto después de @
     *   - Requiere texto después del punto
     *   - No permite espacios en blanco
     * 
     * Retorna: true si es válido, false si no
     */
    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * VALIDAR TELÉFONO CON EXPRESIÓN REGULAR
     * 
     * Parámetro:
     *   - phone: String con el teléfono a validar
     * 
     * Expresión regular: /^[0-9+\-()\s]{6,}$/
     * 
     * Desglose:
     *   - ^: Inicio de la cadena
     *   - [0-9+\-()\s]: Clase de caracteres permitidos:
     *     * 0-9: Dígitos del 0 al 9
     *     * +: Signo más (para códigos de país)
     *     * \-: Guión (escapado)
     *     * (): Paréntesis
     *     * \s: Espacios en blanco
     *   - {6,}: Mínimo 6 caracteres (sin límite máximo)
     *   - $: Fin de la cadena
     * 
     * Formatos válidos:
     *   - "123456789"
     *   - "+1 234 567 8900"
     *   - "(123) 456-7890"
     *   - "123-456-7890"
     * 
     * Retorna: true si es válido, false si no
     */
    function validatePhone(phone) {
        return /^[0-9+\-()\s]{6,}$/.test(phone);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 4: VALIDACIÓN INICIAL DEL FORMULARIO
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * VALIDACIÓN INICIAL
     * 
     * Si no existe el formulario en el DOM:
     *   - Terminar ejecución
     *   - Evita errores si el script se carga en página sin formulario
     */
    if (!form) return;

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 5: HANDLER DE ENVÍO DEL FORMULARIO
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * EVENT LISTENER: Submit del formulario
     * 
     * Se ejecuta cuando el usuario hace clic en el botón "Crear cuenta"
     * o presiona Enter en el formulario
     */
    form.addEventListener('submit', async function (e) {
        /**
         * PREVENIR COMPORTAMIENTO POR DEFECTO
         * 
         * Sin esto, el formulario haría un refresh de página
         * y los datos se enviarían de forma tradicional (no AJAX)
         */
        e.preventDefault();
        
        /**
         * LIMPIAR ALERTA PREVIA
         * Si hay una alerta visible de un intento anterior, ocultarla
         */
        clearAlert();

        // ────────────────────────────────────────────────────────────────────────
        // PASO 1: OBTENER VALORES DEL FORMULARIO
        // ────────────────────────────────────────────────────────────────────────

        /**
         * OBTENER Y LIMPIAR VALORES
         * 
         * .trim(): Remover espacios al inicio y final
         * 
         * Campos de texto:
         *   - first: Nombre
         *   - last: Apellido
         *   - email: Correo electrónico
         *   - phone: Teléfono
         * 
         * Campos especiales:
         *   - ageVal: Valor del input (string)
         *   - age: Valor convertido a entero
         *   - password: Contraseña (sin trim para preservar espacios)
         */
        const first = document.getElementById('first-name').value.trim();
        const last = document.getElementById('last-name').value.trim();
        const ageVal = document.getElementById('age').value;
        const age = parseInt(ageVal, 10);
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;

        // ────────────────────────────────────────────────────────────────────────
        // PASO 2: VALIDACIÓN DEL LADO DEL CLIENTE
        // ────────────────────────────────────────────────────────────────────────

        /**
         * VALIDAR NOMBRE Y APELLIDO
         * 
         * Ambos campos son requeridos
         * Si alguno está vacío:
         *   - Mostrar alerta de error
         *   - Retornar (exit) para no continuar
         */
        if (!first || !last) { 
            showAlert('Debe ingresar nombre y apellido', 'error'); 
            return; 
        }
        
        /**
         * VALIDAR EDAD (EXISTENCIA Y FORMATO)
         * 
         * Verificaciones:
         *   1. !ageVal: Campo vacío
         *   2. Number.isNaN(age): No es un número válido
         * 
         * Si falla:
         *   - Mostrar alerta
         *   - Retornar
         */
        if (!ageVal || Number.isNaN(age)) { 
            showAlert('Ingrese una edad válida', 'error'); 
            return; 
        }
        
        /**
         * VALIDAR EDAD MÍNIMA (18 AÑOS)
         * 
         * Restricción de negocio:
         *   - Solo usuarios mayores de edad pueden registrarse
         * 
         * Si es menor de 18:
         *   - Mostrar alerta específica
         *   - Retornar
         */
        if (age < 18) { 
            showAlert('Debes ser mayor de 18 años para crear una cuenta', 'error'); 
            return; 
        }
        
        /**
         * VALIDAR EMAIL
         * 
         * Usa función validateEmail con expresión regular
         * 
         * Si no pasa validación:
         *   - Mostrar alerta de error
         *   - Retornar
         */
        if (!validateEmail(email)) { 
            showAlert('Ingrese un correo electrónico válido', 'error'); 
            return; 
        }
        
        /**
         * VALIDAR TELÉFONO
         * 
         * Usa función validatePhone con expresión regular
         * Requiere al menos 6 caracteres con formato flexible
         * 
         * Si no pasa validación:
         *   - Mostrar alerta
         *   - Retornar
         */
        if (!validatePhone(phone)) { 
            showAlert('Ingrese un teléfono válido', 'error'); 
            return; 
        }
        
        /**
         * VALIDAR CONTRASEÑA
         * 
         * Requisitos:
         *   - Mínimo 6 caracteres
         *   - Esto es validación del cliente
         *   - El servidor TAMBIÉN debe validar (nunca confiar solo en cliente)
         * 
         * Si no cumple:
         *   - Mostrar alerta
         *   - Retornar
         */
        if (password.length <= 6) { 
            showAlert('La contraseña debe tener al menos 6 caracteres', 'error'); 
            return; 
        }

        // ────────────────────────────────────────────────────────────────────────
        // PASO 3: DESACTIVAR BOTÓN Y CAMBIAR TEXTO
        // ────────────────────────────────────────────────────────────────────────

        /**
         * PREVENIR DOBLES ENVÍOS
         * 
         * submitBtn.disabled = true:
         *   - Desactiva el botón (no se puede clickear)
         *   - Navegadores muestran efecto visual de desactivado (gris, sin cursor)
         * 
         * submitBtn.textContent = 'Creando...':
         *   - Cambiar texto del botón para feedback visual
         *   - Indica al usuario que se está procesando
         */
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creando...';

        // ────────────────────────────────────────────────────────────────────────
        // PASO 4: SOLICITUD AJAX (ASYNC/AWAIT)
        // ────────────────────────────────────────────────────────────────────────

        try {
            /**
             * FETCH POST A register.php
             * 
             * Parámetros:
             *   - method: 'POST' - Método HTTP
             *   - headers: Content-Type JSON - Indica que enviamos JSON
             *   - body: JSON.stringify({...}) - Datos serializados
             * 
             * Datos enviados:
             *   {
             *     first: "Juan",
             *     last: "Pérez",
             *     age: 25,
             *     phone: "123456789",
             *     email: "usuario@example.com",
             *     password: "contraseña123"
             *   }
             */
            const resp = await fetch('register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ first, last, age, phone, email, password })
            });

            /**
             * PARSEAR RESPUESTA JSON
             * 
             * await resp.json(): Esperar a que se decodifique el JSON
             * data: Objeto JavaScript con respuesta del servidor
             */
            const data = await resp.json();

            // ────────────────────────────────────────────────────────────────────
            // PASO 5: PROCESAR RESPUESTA
            // ────────────────────────────────────────────────────────────────────

            /**
             * VALIDAR RESPUESTA EXITOSA
             * 
             * resp.ok: true si status HTTP es 2xx (200, 201, etc)
             * data.success: true si el servidor procesó correctamente
             * 
             * Ambos deben ser verdaderos para considerar éxito
             */
            if (resp.ok && data.success) {
                /**
                 * ÉXITO: Mostrar alerta verde
                 * 
                 * data.message: Mensaje del servidor (o texto por defecto)
                 */
                showAlert(data.message || 'Cuenta creada correctamente.', 'success');
                
                /**
                 * LIMPIAR FORMULARIO
                 * 
                 * form.reset(): Resetea todos los inputs a valores vacíos
                 * También resetea selects, checkboxes, etc.
                 */
                form.reset();
            } else {
                /**
                 * ERROR: Mostrar alerta roja
                 * 
                 * Códigos que llegan aquí:
                 *   - 400: Bad Request (datos inválidos)
                 *   - 409: Conflict (email ya existe)
                 *   - 500: Internal Server Error
                 * 
                 * El servidor envía un mensaje en data.message
                 */
                showAlert(data.message || 'Ocurrió un error al crear la cuenta', 'error');
            }
        } catch (err) {
            /**
             * ERROR DE RED O PARSING
             * 
             * Se ejecuta si:
             *   - No hay conexión a internet
             *   - El servidor no responde
             *   - La respuesta no es JSON válido
             * 
             * Mostrar mensaje genérico de error de red
             */
            showAlert('Error de red. Intenta nuevamente.', 'error');
            console.error(err);
        } finally {
            /**
             * RESTAURAR ESTADO DEL BOTÓN
             * 
             * Se ejecuta SIEMPRE, haya éxito o error
             * 
             * Acciones:
             *   1. Habilitar botón (disabled = false)
             *   2. Restaurar texto original
             * 
             * Esto permite que el usuario intente enviar de nuevo si quiere
             */
            submitBtn.disabled = false;
            submitBtn.textContent = 'Crear cuenta';
        }
    });
})();
