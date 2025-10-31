<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";
$usuario_actual = $_SESSION["usuario"];
$archivo_relativo = isset($_GET['dir']) ? trim($_GET['dir'], '/') : '';
$propietario = isset($_GET['shared']) ? basename($_GET['shared']) : $usuario_actual;

// Valida parámetros mínimos
if ($archivo_relativo === '') {
    die("<p style='color:red;'>❌ No se especificó ningún archivo.</p>");
}

// Si es un archivo compartido, validar permisos
if (isset($_GET['shared'])) {
    include_once('codigos/conexion.inc');
    $stmt = $conn->prepare("
        SELECT 1 FROM compartidos 
        WHERE propietario = ? 
        AND ruta = ? 
        AND compartido_con = ?
    ");
    $stmt->execute([$propietario, $archivo_relativo, $usuario_actual]);
    $permitido = $stmt->fetchColumn();
    if (!$permitido) {
        die("<p style='color:red;'>❌ No tienes permiso para acceder a este recurso compartido.</p>");
    }
}

// Ruta absoluta final
$ruta_usuario = "$ruta_base/$propietario";
$ruta = realpath("$ruta_usuario/$archivo_relativo");

// Validaciones
if (!$ruta || !file_exists($ruta) || strpos($ruta, $ruta_usuario) !== 0) {
    die("<p style='color:red;'>❌ Acceso no autorizado o archivo inexistente.</p>");
}

// Si el archivo es en realidad una carpeta → redirigir a carpetas.php
if (is_dir($ruta)) {
    header("Location: carpetas.php?shared=" . urlencode($propietario) . "&dir=" . urlencode($archivo_relativo));
    exit();
}

// Obtiene tipo MIME
$mime = mime_content_type($ruta);
$ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

// Visualizar imágenes o PDF
if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif'])) {
    header("Content-Type: $mime");
    header("Content-Length: " . filesize($ruta));
    readfile($ruta);
    exit();
}

// Forzar descarga
header("Content-Disposition: attachment; filename=\"" . basename($ruta) . "\"");
header("Content-Type: $mime");
header("Content-Length: " . filesize($ruta));
readfile($ruta);
exit();
?>

