<?php
// ════════════════════════════════════════════════════════════════════════════════
// FILE: logout.php
// ════════════════════════════════════════════════════════════════════════════════
// DESCRIPCIÓN: Script de cierre de sesión (logout)
// Destruye la sesión actual del usuario y redirige a la página principal
// FUNCIONALIDAD PRINCIPAL: Limpieza de sesión + Redirección
// MÉTODOS SOPORTADOS: GET/POST (típicamente GET desde enlace de logout)
// DEPENDENCIAS: session (PHP built-in)
// RESULTADO: Destrucción de variables de sesión + Redirección a index.php
// ROLES AUTORIZADOS: Cualquier usuario autenticado
// ════════════════════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 1: INICIALIZACIÓN DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
// Inicia la sesión PHP para poder acceder y modificar sus variables
// Necesario incluso en logout porque hay que destruir la sesión existente
// Si no se inicia, session_destroy() no tendrá efecto
session_start();

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 2: LIMPIEZA DE VARIABLES DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
// session_unset() elimina todas las variables almacenadas en $_SESSION
// Específicamente destruye:
// - $_SESSION['user_id']: ID del usuario autenticado
// - $_SESSION['user_email']: Email del usuario
// - $_SESSION['user_role']: Rol del usuario (1=Paciente, 2=Psicólogo, 3=Admin)
// - Cualquier otra variable de sesión que haya sido almacenada
// Esta función limpia el contenido pero mantiene la sesión activa
session_unset();

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 3: DESTRUCCIÓN DE SESIÓN
// ──────────────────────────────────────────────────────────────────────────────
// session_destroy() elimina completamente la sesión
// Elimina el archivo de sesión del servidor (típicamente en /tmp o directorio configurado)
// Después de esto, el usuario no tiene ninguna sesión activa
// Todas las variables de autenticación se han perdido permanentemente
session_destroy();

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 4: REDIRECCIÓN A PÁGINA PRINCIPAL
// ──────────────────────────────────────────────────────────────────────────────
// header('Location: index.php') envía una redirección HTTP 302 (Found)
// Al usuario se le redirige automáticamente a la página de inicio (index.php)
// Esta es la landing page pública que cualquier visitante puede ver
// - Si no estaba autenticado: sigue viendo la página normal
// - Si estaba autenticado: ahora sin sesión, el botón de login reaparece
header('Location: index.php');

// ──────────────────────────────────────────────────────────────────────────────
// SECCIÓN 5: FINALIZACIÓN DEL SCRIPT
// ──────────────────────────────────────────────────────────────────────────────
// exit detiene la ejecución del script inmediatamente
// Garantiza que no se ejecute código adicional después de la redirección
// Importante para evitar que se envíe contenido antes de los headers
// Asegura un logout limpio sin efectos secundarios
exit;
?>