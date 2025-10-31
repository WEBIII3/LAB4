<?php
// Configuración de conexión
$servidor  = "localhost";
$basedatos = "mybox";
$usuario   = "myboxuser";
$contra    = "MyBoxSecure123!";

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Conexión PDO
    $conn = new PDO("mysql:host=$servidor;dbname=$basedatos;charset=utf8mb4", $usuario, $contra);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // También creamos $conex (para compatibilidad con index.php que usa mysqli)
    $conex = mysqli_connect($servidor, $usuario, $contra, $basedatos);

    if (!$conex) {
        die("<p style='color:red;'>❌ Error de conexión MySQLi: " . mysqli_connect_error() . "</p>");
    }
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Error de conexión PDO: " . $e->getMessage() . "</p>");
}
?>

