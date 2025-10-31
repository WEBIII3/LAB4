<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

$usuario = $_SESSION["usuario"];
$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";
$ruta_usuario = "$ruta_base/$usuario";

// Verifica que exista la carpeta base
if (!is_dir($ruta_usuario)) {
    die("<p style='color:red;'>❌ No se encontró la carpeta del usuario: $ruta_usuario</p>");
}

// Si el usuario envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_carpeta = trim($_POST['nombre_carpeta']);

    if ($nombre_carpeta === '') {
        echo "<script>alert('⚠️ Debes escribir un nombre para la carpeta.'); window.location.href='crear_carpeta.php';</script>";
        exit();
    }

    $nombre_carpeta = basename($nombre_carpeta);
    $ruta_nueva = "$ruta_usuario/$nombre_carpeta";

    if (file_exists($ruta_nueva)) {
        echo "<script>alert('⚠️ Ya existe una carpeta con ese nombre.'); window.location.href='crear_carpeta.php';</script>";
        exit();
    }

    if (mkdir($ruta_nueva, 0775, true)) {
        // 🔁 Redirige inmediatamente a carpetas.php sin mostrar nada más
        header("Location: carpetas.php");
        exit();
    } else {
        echo "<script>alert('❌ Error al crear la carpeta. Verifica permisos.'); window.location.href='crear_carpeta.php';</script>";
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
            <form method="post">
                <label>Nombre de la carpeta:</label><br>
                <input type="text" name="nombre_carpeta" placeholder="Ej: Documentos 2025" required>
                <br><br>
                <button type="submit" class="btn btn-success">Crear carpeta</button>
                <a href="carpetas.php" class="btn btn-secondary">Volver</a>
            </form>
        </div>
    </div>
</main>

<?php include_once('partes/final.inc'); ?>
</body>
</html>

