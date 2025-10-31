<?php
// Inicia la sesión
session_start();

// Verifica que el usuario esté autenticado
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

// Obtiene la ruta base desde variable de entorno o valor por defecto
$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";

// Usuario autenticado
$usuario = $_SESSION["usuario"];
$ruta_usuario = "$ruta_base/$usuario";

// Determina el subdirectorio actual (si aplica)
$dir_relativo = isset($_GET['dir']) ? $_GET['dir'] : '';
$ruta_destino = realpath($ruta_usuario . '/' . $dir_relativo);

// Valida ruta destino
if (!$ruta_destino || strpos($ruta_destino, $ruta_usuario) !== 0) {
    die("<p style='color:red;'>❌ Acceso no autorizado o ruta inválida.</p>");
}

// Acción del formulario
$Accion_Formulario = htmlspecialchars($_SERVER['PHP_SELF']) . '?dir=' . urlencode($dir_relativo);

// Procesa el formulario al enviar
if (isset($_POST["OC_Aceptar"]) && $_POST["OC_Aceptar"] == "frmArchi") {

    // Verifica si se subió un archivo
    if (!isset($_FILES['txtArchi']) || $_FILES['txtArchi']['error'] != UPLOAD_ERR_OK) {
        echo "<p style='color:red;'>❌ Error: no se seleccionó archivo o hubo un problema en la subida.</p>";
    } else {
        $archivo = $_FILES['txtArchi'];

        // Límite de tamaño 20 MB
        if ($archivo['size'] > 20 * 1024 * 1024) {
            echo "<p style='color:red;'>⚠️ Archivo demasiado grande (máx 20MB).</p>";
        } else {
            // Limpia el nombre del archivo
            $nombre = basename($archivo['name']);
            $nombre = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $nombre); // solo letras, números, guiones y puntos
            $ruta_final = $ruta_destino . '/' . $nombre;

            // Mueve el archivo subido
            if (move_uploaded_file($archivo['tmp_name'], $ruta_final)) {
                chmod($ruta_final, 0644);
                header("Location: carpetas.php?dir=" . urlencode($dir_relativo));
                exit();
            } else {
                echo "<p style='color:red;'>❌ Error: no se pudo mover el archivo al destino.</p>";
                echo "<p>Ruta destino: $ruta_final</p>";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>Agregar archivo</title>
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
    <div class="panel panel-primary datos1">
        <div class="panel-heading"><strong>📤 Agregar archivo</strong></div>
        <div class="panel-body">
            <form action="<?php echo $Accion_Formulario; ?>" method="post" enctype="multipart/form-data" name="frmArchi">
                <fieldset>
                    <label><strong>Selecciona archivo (máx 20MB)</strong></label><br>
                    <input name="txtArchi" type="file" id="txtArchi" size="60" required>
                    <br><br>
                    <input type="submit" name="Submit" value="Cargar" class="btn btn-primary">
                </fieldset>
                <input type="hidden" name="OC_Aceptar" value="frmArchi">
            </form>
        </div>
    </div>
</main>

<?php include_once('partes/final.inc'); ?>
</body>
</html>

