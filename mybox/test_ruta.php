<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h3>Diagnóstico MyBox</h3>";
echo "<p><b>HOME_PATH:</b> " . getenv("HOME_PATH") . "</p>";

$usuario = $_SESSION["usuario"] ?? "bwalker";
$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";
$ruta_usuario = "$ruta_base/$usuario";

echo "<p><b>Ruta usuario:</b> $ruta_usuario</p>";

if (file_exists($ruta_usuario)) {
    echo "<p style='color:green'>✅ Existe correctamente.</p>";
} else {
    echo "<p style='color:red'>❌ No existe esa ruta.</p>";
}
?>
