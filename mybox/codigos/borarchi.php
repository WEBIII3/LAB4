<?php
// =========================================================
// codigos/borarchi.php - Eliminar archivos o carpetas
// =========================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

include_once('conexion.inc');

$usuario_nombre = $_SESSION["usuario"];
$tipo = $_GET['type'] ?? '';

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario_nombre]);
$usuario_id = $stmt->fetchColumn();

try {
    if ($tipo === 'archivo') {
        $archivo_id = isset($_GET['archivo_id']) ? (int)$_GET['archivo_id'] : 0;
        
        if ($archivo_id <= 0) {
            throw new Exception("ID de archivo no válido");
        }

        // Verificar que el archivo pertenece al usuario
        $stmt = $conn->prepare("SELECT carpeta_id FROM archivos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$archivo_id, $usuario_id]);
        $carpeta_id = $stmt->fetchColumn();

        if ($carpeta_id === false) {
            throw new Exception("Archivo no encontrado o sin permisos");
        }

        // Eliminar archivo (esto también eliminará compartidos por CASCADE)
        $stmt = $conn->prepare("DELETE FROM archivos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$archivo_id, $usuario_id]);

        $msg = "✅ Archivo eliminado correctamente.";
        $redirect = $carpeta_id ? "../carpetas.php?carpeta_id=$carpeta_id" : "../carpetas.php";

    } elseif ($tipo === 'carpeta') {
        $carpeta_id = isset($_GET['carpeta_id']) ? (int)$_GET['carpeta_id'] : 0;
        
        if ($carpeta_id <= 0) {
            throw new Exception("ID de carpeta no válido");
        }

        // Verificar que la carpeta pertenece al usuario
        $stmt = $conn->prepare("SELECT carpeta_padre_id FROM carpetas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$carpeta_id, $usuario_id]);
        $carpeta_padre_id = $stmt->fetchColumn();

        if ($carpeta_padre_id === false) {
            throw new Exception("Carpeta no encontrada o sin permisos");
        }

        // Función recursiva para eliminar carpeta y todo su contenido
        function eliminar_carpeta_recursiva($conn, $carpeta_id, $usuario_id) {
            // Obtener subcarpetas
            $stmt = $conn->prepare("SELECT id FROM carpetas WHERE carpeta_padre_id = ? AND usuario_id = ?");
            $stmt->execute([$carpeta_id, $usuario_id]);
            $subcarpetas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Eliminar cada subcarpeta recursivamente
            foreach ($subcarpetas as $sub_id) {
                eliminar_carpeta_recursiva($conn, $sub_id, $usuario_id);
            }

            // Eliminar archivos de esta carpeta
            $stmt = $conn->prepare("DELETE FROM archivos WHERE carpeta_id = ? AND usuario_id = ?");
            $stmt->execute([$carpeta_id, $usuario_id]);

            // Eliminar la carpeta
            $stmt = $conn->prepare("DELETE FROM carpetas WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$carpeta_id, $usuario_id]);
        }

        eliminar_carpeta_recursiva($conn, $carpeta_id, $usuario_id);

        $msg = "✅ Carpeta y todo su contenido eliminados correctamente.";
        $redirect = $carpeta_padre_id ? "../carpetas.php?carpeta_id=$carpeta_padre_id" : "../carpetas.php";

    } else {
        throw new Exception("Tipo de recurso no especificado");
    }

    echo "<h3 style='color:green;'>$msg</h3>";
    header("Refresh:1; url=$redirect");
    exit();

} catch (Exception $e) {
    echo "<p style='color:red;'>⚠️ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='../carpetas.php'>Volver a carpetas</a></p>";
}
?>
