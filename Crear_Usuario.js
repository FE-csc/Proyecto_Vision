/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT: Crear_Usuario.js
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Maneja la creación de nuevas cuentas de usuario (registro)
 *   - Valida email y contraseña antes de enviar al servidor
 *   - Realiza solicitud AJAX POST a register.php
 *   - Muestra alertas de éxito o error con estilos dinámicos
 *   - Desactiva botón durante el envío para evitar dobles envíos
 *   - Implementado con IIFE (Immediately Invoked Function Expression) para evitar contaminación global
 * 
 * Funcionalidades:
 *   - Validación de email con expresión regular
 *   - Validación de longitud mínima de contraseña (6 caracteres)
 *   - Envío asincrónico de datos a registro.php
 *   - Manejo de respuestas exitosas y errores
 *   - Gestión de errores de red
 *   - Limpieza de formulario tras registro exitoso
 *   - Feedback visual durante la operación
 * 
 * Parámetros POST (JSON):
 *   - email: Correo electrónico del nuevo usuario
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
 *   - email: Input para correo
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
    // SECCIÓN 3: VALIDACIÓN DE EMAIL
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
     *   - \.  : Punto literal (escapado)
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

    // ────────────────────────────────────────────────────────────────────────────
    // SECCIÓN 4: VALIDACIÓN DE FORMULARIO
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
         * OBTENER EMAIL
         * 
         * document.getElementById('email'): Obtener input
         * .value: Obtener contenido actual
         * .trim(): Remover espacios al inicio y final
         * 
         * Ejemplo:
         *   Input: "  usuario@example.com  "
         *   Resultado: "usuario@example.com"
         */
        const email = document.getElementById('email').value.trim();
        
        /**
         * OBTENER CONTRASEÑA
         * 
         * No se usa trim() porque espacios pueden ser intencionales en contraseña
         * .value: Obtener valor sin procesar
         */
        const password = document.getElementById('password').value;

        // ────────────────────────────────────────────────────────────────────────
        // PASO 2: VALIDACIÓN DEL LADO DEL CLIENTE
        // ────────────────────────────────────────────────────────────────────────

        /**
         * VALIDAR EMAIL
         * 
         * Si email no pasa validación:
         *   - Mostrar alerta de error
         *   - Retornar (exit) para no continuar
         */
        if (!validateEmail(email)) { 
            showAlert('Ingrese un correo electrónico válido', 'error'); 
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
        // PASO 3: DESACTIVAS BOTÓN Y CAMBIAR TEXTO
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
             *   - headers: Content-Type JSON - Indica que envíamos JSON
             *   - body: JSON.stringify({...}) - Datos serializados
             * 
             * Datos enviados:
             *   {
             *     email: "usuario@example.com",
             *     password: "contraseña123"
             *   }
             */
            const resp = await fetch('register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
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
