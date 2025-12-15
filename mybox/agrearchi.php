<?php
// ==================================================
// agrearchi.php - Subir archivos a la base de datos
// ==================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

include_once('codigos/conexion.inc');

// Usuario autenticado
$usuario_nombre = $_SESSION["usuario"];

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario_nombre]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    die("<p style='color:red;'>❌ Error: Usuario no encontrado en la base de datos.</p>");
}

// Determina el ID de la carpeta actual
$carpeta_id = isset($_GET['carpeta_id']) ? (int)$_GET['carpeta_id'] : null;
$ruta_actual = '';

// Si hay carpeta, obtener su ruta
if ($carpeta_id) {
    $stmt = $conn->prepare("SELECT ruta_completa FROM carpetas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$carpeta_id, $usuario_id]);
    $ruta_actual = $stmt->fetchColumn();
    
    if ($ruta_actual === false) {
        die("<p style='color:red;'>❌ Carpeta no encontrada o no tienes permisos.</p>");
    }
}

// Acción del formulario
$url_params = $carpeta_id ? "?carpeta_id=$carpeta_id" : "";
$Accion_Formulario = htmlspecialchars($_SERVER['PHP_SELF']) . $url_params;

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
            try {
                // Limpia el nombre del archivo
                $nombre_original = basename($archivo['name']);
                $nombre_limpio = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $nombre_original);
                
                // Obtener información del archivo
                $extension = strtolower(pathinfo($nombre_limpio, PATHINFO_EXTENSION));
                $mime_type = mime_content_type($archivo['tmp_name']);
                $tamano = $archivo['size'];
                
                // Leer contenido del archivo
                $contenido = file_get_contents($archivo['tmp_name']);
                
                // Construir ruta completa
                $ruta_completa = $ruta_actual ? "$ruta_actual/$nombre_limpio" : $nombre_limpio;
                
                // Verificar si ya existe un archivo con ese nombre en esa carpeta
                $stmt = $conn->prepare("
                    SELECT id FROM archivos 
                    WHERE usuario_id = ? AND carpeta_id <=> ? AND nombre = ?
                ");
                $stmt->execute([$usuario_id, $carpeta_id, $nombre_limpio]);
                
                if ($stmt->fetchColumn()) {
                    echo "<p style='color:orange;'>⚠️ Ya existe un archivo con ese nombre en esta ubicación.</p>";
                } else {
                    // Insertar archivo en la base de datos
                    $stmt = $conn->prepare("
                        INSERT INTO archivos 
                        (usuario_id, carpeta_id, nombre, nombre_original, extension, mime_type, tamano, contenido, ruta_completa) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $usuario_id,
                        $carpeta_id,
                        $nombre_limpio,
                        $nombre_original,
                        $extension,
                        $mime_type,
                        $tamano,
                        $contenido,
                        $ruta_completa
                    ]);
                    
                    // Redirigir a carpetas.php
                    $redirect_url = $carpeta_id ? "carpetas.php?carpeta_id=$carpeta_id" : "carpetas.php";
                    header("Location: $redirect_url");
                    exit();
                }
                
            } catch (PDOException $e) {
                echo "<p style='color:red;'>❌ Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</p>";
            } catch (Exception $e) {
                echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
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
            
            <div style="margin-bottom: 15px;">
                <?php 
                $volver_url = $carpeta_id ? "carpetas.php?carpeta_id=$carpeta_id" : "carpetas.php";
                ?>
                <a href="<?php echo $volver_url; ?>" class="btn btn-default">⬅️ Volver</a>
            </div>
            
            <?php if ($ruta_actual): ?>
                <p><strong>Ubicación actual:</strong> <?php echo htmlspecialchars($ruta_actual); ?></p>
            <?php else: ?>
                <p><strong>Ubicación actual:</strong> Raíz</p>
            <?php endif; ?>
            
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
