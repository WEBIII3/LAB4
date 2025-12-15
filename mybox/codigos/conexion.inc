<?php
// =========================================================
// codigos/conexion.inc - Configuración de conexión a BD
// =========================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de conexión
$servidor  = "localhost";
$basedatos = "mybox";
$usuario   = "server";
$contra    = "server";

try {
    // Conexión PDO (principal - para la mayoría de operaciones)
    $conn = new PDO("mysql:host=$servidor;dbname=$basedatos;charset=utf8mb4", $usuario, $contra);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configuración importante para archivos grandes
    $conn->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
    $conn->exec("SET NAMES utf8mb4");
    
    // También creamos $conex (para compatibilidad con index.php y registrar.php que usan mysqli)
    $conex = mysqli_connect($servidor, $usuario, $contra, $basedatos);

    if (!$conex) {
        die("<p style='color:red;'>❌ Error de conexión MySQLi: " . mysqli_connect_error() . "</p>");
    }
    
    // Configurar charset para mysqli también
    mysqli_set_charset($conex, "utf8mb4");
    
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Error de conexión PDO: " . $e->getMessage() . "</p>");
}
?>
