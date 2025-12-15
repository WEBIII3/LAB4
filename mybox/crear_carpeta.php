<?php
// =========================================================
// crear_carpeta.php - Crear carpetas en la base de datos
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

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario_nombre]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    die("<p style='color:red;'>❌ Error: Usuario no encontrado.</p>");
}

// Determina el ID de la carpeta padre
$carpeta_padre_id = isset($_GET['carpeta_id']) ? (int)$_GET['carpeta_id'] : null;
$ruta_padre = '';

// Si hay carpeta padre, obtener su ruta
if ($carpeta_padre_id) {
    $stmt = $conn->prepare("SELECT ruta_completa FROM carpetas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$carpeta_padre_id, $usuario_id]);
    $ruta_padre = $stmt->fetchColumn();
    
    if ($ruta_padre === false) {
        die("<p style='color:red;'>❌ Carpeta padre no encontrada.</p>");
    }
}

// Procesa el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_carpeta = trim($_POST['nombre_carpeta']);

    if ($nombre_carpeta === '') {
        echo "<script>alert('⚠️ Debes escribir un nombre para la carpeta.'); window.location.href='crear_carpeta.php" . ($carpeta_padre_id ? "?carpeta_id=$carpeta_padre_id" : "") . "';</script>";
        exit();
    }

    // Limpiar nombre de carpeta
    $nombre_carpeta = basename($nombre_carpeta);
    $nombre_carpeta = preg_replace('/[^A-Za-z0-9_\-\s]/', '_', $nombre_carpeta);

    // Construir ruta completa
    $ruta_completa = $ruta_padre ? "$ruta_padre/$nombre_carpeta" : $nombre_carpeta;

    try {
        // Verificar si ya existe una carpeta con ese nombre
        $stmt = $conn->prepare("
            SELECT id FROM carpetas 
            WHERE usuario_id = ? AND carpeta_padre_id <=> ? AND nombre = ?
        ");
        $stmt->execute([$usuario_id, $carpeta_padre_id, $nombre_carpeta]);
        
        if ($stmt->fetchColumn()) {
            echo "<script>alert('⚠️ Ya existe una carpeta con ese nombre en esta ubicación.'); window.location.href='crear_carpeta.php" . ($carpeta_padre_id ? "?carpeta_id=$carpeta_padre_id" : "") . "';</script>";
            exit();
        }

        // Crear la carpeta en la base de datos
        $stmt = $conn->prepare("
            INSERT INTO carpetas (usuario_id, nombre, carpeta_padre_id, ruta_completa) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $nombre_carpeta, $carpeta_padre_id, $ruta_completa]);

        // Redirigir
        $redirect_url = $carpeta_padre_id ? "carpetas.php?carpeta_id=$carpeta_padre_id" : "carpetas.php";
        header("Location: $redirect_url");
        exit();

    } catch (PDOException $e) {
        echo "<script>alert('❌ Error al crear la carpeta: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='crear_carpeta.php" . ($carpeta_padre_id ? "?carpeta_id=$carpeta_padre_id" : "") . "';</script>";
        exit();
    }
}
?>

<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>Crear nueva carpeta</title>
</head>
<body class="container cuerpo">
<header class="row">
    <div class="col-lg-6 col-sm-6">
        <img src="imagenes/encabe.png" alt="logo institucional" width="100%">
    </div>
    <div class="row">
        <?php include_once('partes/menu.inc'); ?>
    </div>
    <br>
</header>

<main class="row">
    <div class="panel panel-primary">
        <div class="panel-heading"><strong>📁 Crear nueva carpeta</strong></div>
        <div class="panel-body">
            
            <div style="margin-bottom: 15px;">
                <?php 
                $volver_url = $carpeta_padre_id ? "carpetas.php?carpeta_id=$carpeta_padre_id" : "carpetas.php";
                ?>
                <a href="<?php echo $volver_url; ?>" class="btn btn-default">⬅️ Volver</a>
            </div>
            
            <?php if ($ruta_padre): ?>
                <p><strong>Ubicación actual:</strong> <?php echo htmlspecialchars($ruta_padre); ?></p>
            <?php else: ?>
                <p><strong>Ubicación actual:</strong> Raíz</p>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>Nombre de la carpeta:</label><br>
                    <input type="text" 
                           name="nombre_carpeta" 
                           class="form-control" 
                           placeholder="Ej: Documentos 2025" 
                           maxlength="255"
                           required>
                </div>
                <br>
                <button type="submit" class="btn btn-success">Crear carpeta</button>
                <a href="<?php echo $volver_url; ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</main>

<?php include_once('partes/final.inc'); ?>
</body>
</html>
