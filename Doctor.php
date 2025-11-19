<?php
/**
 * Inicia la sesion y valida si existe un usuario
 * Si no existe, lo redirige al login con parametro redirect.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?redirect=' . urldecode(basename($_SERVER['PHP_SELF'])));
    exit;
}
echo "hola doctor";