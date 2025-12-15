<?php
// =========================================================
// codigos/salir.php - Cerrar sesión
// =========================================================

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

session_start();

// Destruir todas las variables de sesión
unset($_SESSION['autenticado']);
unset($_SESSION['usuario_id']);
unset($_SESSION['usuario']);
unset($_SESSION['nombre']);
unset($_SESSION['email']);

// Destruir la sesión completamente
session_destroy();

// Redirigir al login
header("Location: ../index.php");
exit();
?>
