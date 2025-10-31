<?php
// Iniciar sesión
session_start();

// Verificar autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

// Obtener ruta base desde variable de entorno
$base = getenv("HOME_PATH") ?: "/home/myboxusers";
$ruta = $base . '/' . $_SESSION["usuario"];


// Crear el directorio si no existe
if (!is_dir($ruta)) {
    if (!mkdir($ruta, 0700, true)) {
        echo "<p style='color:red;'>❌ ERROR: No se pudo crear el directorio del usuario.</p>";
        echo "<p>Ruta: $ruta</p>";
        exit();
    } else {
        echo "<p style='color:green;'>✅ Directorio creado: $ruta</p>";
    }
}

// Redirigir al área de carpetas del usuario
header("Location: ../carpetas.php");
exit();
?>
