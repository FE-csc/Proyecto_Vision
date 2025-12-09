/**
 * ════════════════════════════════════════════════════════════════════════════════
 * SCRIPT: auth.js
 * ════════════════════════════════════════════════════════════════════════════════
 * 
 * Descripción:
 *   - Maneja la autenticación y login de usuarios
 *   - Procesa el formulario de inicio de sesión
 *   - Valida credenciales contra el servidor (login.php)
 *   - Redirige a páginas según el rol del usuario (paciente, psicólogo, admin)
 *   - Implementa feedback visual durante el envío
 *   - Maneja errores de conexión y credenciales inválidas
 * 
 * Flujo de autenticación:
 *   1. Captura evento submit del formulario de login
 *   2. Obtiene email y contraseña del formulario
 *   3. Deshabilita botón para evitar doble envío
 *   4. Envía credenciales a login.php via POST
 *   5. Procesa respuesta JSON del servidor
 *   6. Si éxito: redirige según rol (1=paciente, 2=psicólogo, 3=admin)
 *   7. Si error: muestra alerta con mensaje de error
 * 
 * Roles de usuario:
 *   - Rol 1: Paciente          -> Redirecciona a Perfil.php
 *   - Rol 2: Psicólogo         -> Redirecciona a Doctor.php
 *   - Rol 3: Administrador     -> Redirecciona a panelAdmin_View.php
 * ════════════════════════════════════════════════════════════════════════════════
 */

/**
 * Esperar a que el DOM esté completamente cargado antes de ejecutar el script
 * Esto asegura que todos los elementos HTML estén disponibles
 */
document.addEventListener("DOMContentLoaded", () => {
  /**
   * Obtener referencia al formulario de login desde el DOM
   * Si el formulario no existe, salir del script
   */
  const form = document.getElementById("loginForm");

  if (!form) return;

  /**
   * EVENTO: Submit del formulario de login
   * 
   * Se ejecuta cuando el usuario presiona el botón de envío
   * o presiona Enter en un campo del formulario
   */
  form.addEventListener("submit", async (event) => {
    /**
     * Prevenir el comportamiento por defecto del formulario
     * Sin esto, la página se recarga y pierda los datos
     */
    event.preventDefault();

    // ───────────────────────────────────────────────────────────────────────────
    // SECCIÓN 1: OBTENER Y VALIDAR DATOS DEL FORMULARIO
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * Obtener valores del formulario
     * - trim(): Elimina espacios en blanco al inicio y final
     */
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    
    /**
     * Obtener referencia al botón de envío para cambiar su estado
     */
    const submitButton = form.querySelector("button[type='submit']");

    // ───────────────────────────────────────────────────────────────────────────
    // SECCIÓN 2: FEEDBACK VISUAL - DESHABILITAR BOTÓN
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * Deshabilitar botón de envío durante la solicitud
     * 
     * Esto previene que el usuario:
     *   - Envíe el formulario varias veces (doble envío)
     *   - Intente interactuar mientras se procesa la solicitud
     *   - Cause múltiples peticiones al servidor
     * 
     * También proporciona feedback visual (cambio de texto a "Cargando...")
     */
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerText = "Cargando...";
    }

    try {
      // ─────────────────────────────────────────────────────────────────────────
      // SECCIÓN 3: ENVIAR SOLICITUD DE AUTENTICACIÓN AL SERVIDOR
      // ─────────────────────────────────────────────────────────────────────────

      /**
       * Realizar petición AJAX POST a login.php
       * 
       * Detalles:
       *   - Método: POST (datos enviados en el cuerpo)
       *   - Headers: application/x-www-form-urlencoded (formato tradicional)
       *   - Body: email y password codificados en URL
       * 
       * encodeURIComponent():
       *   - Codifica caracteres especiales (espacios, @, etc.)
       *   - Previene inyección de código
       *   - Necesario para este formato de contenido
       */
      const response = await fetch("login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
      });

      /**
       * Parsear la respuesta como JSON
       * Esperamos: { success: boolean, usuario: object|null, error: string|null }
       */
      const data = await response.json();
      
      // ─────────────────────────────────────────────────────────────────────────
      // SECCIÓN 4: PROCESAR RESPUESTA DEL SERVIDOR
      // ─────────────────────────────────────────────────────────────────────────

      /**
       * Verificar si la autenticación fue exitosa
       */
      if (data.success) {
        /**
         * Autenticación exitosa
         * Redirigir al usuario según su rol
         * 
         * Roles disponibles:
         *   - Rol 1: Paciente          -> Perfil.php
         *   - Rol 2: Psicólogo         -> Doctor.php
         *   - Rol 3: Administrador     -> panelAdmin_View.php
         */
        if(data.usuario.Rol===1){
          // Rol 1: Paciente - Ir al perfil del paciente
          window.location.href = "Perfil.php";
        }
        if(data.usuario.Rol===2){
          // Rol 2: Psicólogo - Ir al panel del psicólogo
          window.location.href = "Doctor.php";
        }
        if(data.usuario.Rol===3){
          // Rol 3: Administrador - Ir al panel administrativo
          window.location.href = "panelAdmin_View.php";
        }
      
      } else {
        /**
         * ❌ Error de autenticación
         * 
         * Causas posibles:
         *   - Email no registrado
         *   - Contraseña incorrecta
         *   - Usuario inactivo o bloqueado
         * 
         * Mostrar mensaje de error del servidor
         * Si no hay mensaje específico, mostrar mensaje genérico
         */
        alert(data.error || "Credenciales incorrectas.");
      }
    } catch (error) {
      /**
       * ❌ Error de conexión o en el procesamiento
       * 
       * Causas posibles:
       *   - Servidor no disponible
       *   - Error de red
       *   - JSON inválido en la respuesta
       *   - Error en login.php
       * 
       * Registrar error en consola para debugging
       * Mostrar alerta al usuario
       */
      console.error("Error:", error);
      alert("No se pudo conectar con el servidor. Intente más tarde.");
    } finally {
        /**
         * SECCIÓN 5: RESTABLECER ESTADO DEL BOTÓN
         * 
         * Se ejecuta siempre, tanto si hay éxito como error
         * Restaura el botón al estado original
         * 
         * Nota: Si la redirección fue exitosa, esto se ejecuta
         * pero el usuario ya está en otra página
         */
        
        if (submitButton) {
            // Re-habilitar botón
            submitButton.disabled = false;
            // Restaurar texto original del botón
            submitButton.innerText = "Iniciar Sesión";
        }
    }
  });
});