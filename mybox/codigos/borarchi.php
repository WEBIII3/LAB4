<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

// Obtiene datos
$usuario = $_SESSION["usuario"];
$relativo = $_GET['dir'] ?? '';
$tipo = $_GET['type'] ?? '';

// Rutas base y completas
$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";
$ruta_usuario = "$ruta_base/$usuario";
$ruta_completa = realpath($ruta_usuario . '/' . $relativo);

// --- Seguridad: evita escapes fuera del directorio del usuario ---
if (!$ruta_completa || strpos($ruta_completa, $ruta_usuario) !== 0) {
    die("<p style='color:red;'>❌ Acceso no autorizado o ruta inválida.</p>");
}

// --- Verifica existencia ---
if (!file_exists($ruta_completa)) {
    die("<p style='color:red;'>❌ El archivo o carpeta no existe.</p>");
}

// --- Eliminación ---
try {
    if (is_dir($ruta_completa)) {
        // Eliminar carpeta recursivamente
        $it = new RecursiveDirectoryIterator($ruta_completa, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($ruta_completa);
        $msg = "✅ Carpeta eliminada correctamente.";
    } else {
        unlink($ruta_completa);
        $msg = "✅ Archivo eliminado correctamente.";
    }

    echo "<h3 style='color:green;'>$msg</h3>";
    header("Refresh:1; url=../carpetas.php");
    exit();
} catch (Exception $e) {
    echo "<p style='color:red;'>⚠️ Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

