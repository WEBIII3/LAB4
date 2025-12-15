<?php
// =========================================================
// codigos/creadir.php - Inicialización de espacio usuario
// =========================================================
// Este script ya NO crea directorios físicos
// Solo verifica que el usuario existe en la BD
// La carpeta raíz se crea automáticamente cuando sube primer archivo

session_start();

// Verificar autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

include_once('conexion.inc');

$usuario_nombre = $_SESSION["usuario"];

try {
    // Verificar que el usuario existe en la base de datos
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario_nombre]);
    $usuario_id = $stmt->fetchColumn();

    if (!$usuario_id) {
        throw new Exception("Usuario no encontrado en la base de datos");
    }

    // Todo OK - el espacio del usuario está listo (virtual)
    echo "<p style='color:green;'>✅ Espacio de usuario inicializado correctamente.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit();
}

// Redirigir al área de carpetas del usuario
header("Refresh:1; url=../carpetas.php");
exit();
?>
