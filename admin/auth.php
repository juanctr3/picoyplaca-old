<?php
/**
 * admin/auth.php
 * Archivo de seguridad. Se debe incluir al inicio de TODAS las páginas protegidas.
 * Verifica si el usuario ha iniciado sesión. Si no, lo manda al login.
 */

session_start();

// Verificamos si la variable de sesión existe y es verdadera
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Si no está logueado, lo mandamos a la puerta (login.php)
    header('Location: login.php');
    exit;
}
?>
