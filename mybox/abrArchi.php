<?php
// =========================================================
// abrArchi.php - Descargar/visualizar archivos desde BD
// =========================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

include_once('codigos/conexion.inc');

$usuario_nombre = $_SESSION["usuario"];
$archivo_id = isset($_GET['archivo_id']) ? (int)$_GET['archivo_id'] : 0;
$es_compartido = isset($_GET['shared']);

if ($archivo_id <= 0) {
    die("<p style='color:red;'>❌ ID de archivo no válido.</p>");
}

// Obtener ID del usuario actual
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario_nombre]);
$usuario_id = $stmt->fetchColumn();

// ============================================
// 1️⃣ Obtener información del archivo
// ============================================
$stmt = $conn->prepare("
    SELECT 
        a.id, 
        a.usuario_id, 
        a.nombre, 
        a.nombre_original, 
        a.extension, 
        a.mime_type, 
        a.tamano, 
        a.contenido,
        u.usuario AS propietario
    FROM archivos a
    INNER JOIN usuarios u ON a.usuario_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$archivo_id]);
$archivo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$archivo) {
    die("<p style='color:red;'>❌ Archivo no encontrado.</p>");
}

// ============================================
// 2️⃣ Validar permisos
// ============================================
$tiene_permiso = false;

// Caso 1: Es el propietario
if ($archivo['usuario_id'] == $usuario_id) {
    $tiene_permiso = true;
}

// Caso 2: Archivo compartido con el usuario
if (!$tiene_permiso) {
    $stmt = $conn->prepare("
        SELECT 1 FROM compartidos 
        WHERE archivo_id = ? 
        AND compartido_con_id = ? 
        AND tipo = 'archivo'
    ");
    $stmt->execute([$archivo_id, $usuario_id]);
    if ($stmt->fetchColumn()) {
        $tiene_permiso = true;
    }
}

if (!$tiene_permiso) {
    die("<p style='color:red;'>❌ No tienes permiso para acceder a este archivo.</p>");
}

// ============================================
// 3️⃣ Servir el archivo
// ============================================

// Limpiar cualquier salida previa
ob_clean();
flush();

$extension = strtolower($archivo['extension']);
$mime_type = $archivo['mime_type'];

// Determinar si se debe mostrar inline (en navegador) o forzar descarga
$mostrar_inline = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'html', 'css', 'js']);

if ($mostrar_inline) {
    header("Content-Disposition: inline; filename=\"" . $archivo['nombre_original'] . "\"");
} else {
    header("Content-Disposition: attachment; filename=\"" . $archivo['nombre_original'] . "\"");
}

header("Content-Type: " . $mime_type);
header("Content-Length: " . $archivo['tamano']);
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// Enviar el contenido del archivo
echo $archivo['contenido'];
exit();
?>
