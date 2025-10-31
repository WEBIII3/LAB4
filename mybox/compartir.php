<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicia la sesión
session_start();
include_once('codigos/conexion.inc');

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

$usuario = $_SESSION["usuario"];
$ruta_relativa = $_GET['dir'] ?? ''; // Ruta relativa dentro del usuario

// Configura ruta base y absoluta
$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";
$ruta_usuario = "$ruta_base/$usuario";
$ruta_completa = realpath("$ruta_usuario/$ruta_relativa");

// Verifica existencia del recurso
if (!$ruta_completa || !file_exists($ruta_completa)) {
    die("<p style='color:red;'>❌ El recurso seleccionado no existe en el servidor.</p>");
}

// Variable de estado para mensajes
$mensaje = "";
$tipo_mensaje = "info";

// Obtiene lista de usuarios excepto el actual
try {
    $stmt = $conn->prepare("SELECT usuario, nombre FROM usuarios WHERE usuario != ?");
    $stmt->execute([$usuario]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<p style='color:red;'>❌ Error al obtener usuarios: " . $e->getMessage() . "</p>");
}

// Procesa el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amigo = trim($_POST['amigo']);

    if ($amigo === '') {
        $mensaje = "⚠️ Debes seleccionar un usuario con quien compartir.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Verifica que el usuario destino exista
            $q = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
            $q->execute([$amigo]);

            if ($q->rowCount() === 0) {
                $mensaje = "❌ El usuario '$amigo' no existe.";
                $tipo_mensaje = "danger";
            } else {
                // Inserta el registro en la tabla compartidos
                $stmt = $conn->prepare("INSERT INTO compartidos (propietario, ruta, compartido_con) VALUES (?, ?, ?)");
                $stmt->execute([$usuario, $ruta_relativa, $amigo]);

                echo "<script>alert('✅ Recurso compartido exitosamente con $amigo'); window.location.href='carpetas.php';</script>";
                exit();
            }
        } catch (PDOException $e) {
            $mensaje = "❌ Error SQL: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>MyBox - Compartir recurso</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; border-bottom: 1px solid #ccc; text-align: left; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        .acciones a { margin-right: 10px; }
        .icono { font-size: 22px; }
        .botones { margin: 15px 0; }
    </style>
</head>
<body class="container cuerpo">

<header class="row">
    <div class="row">
        <div class="col-lg-6 col-sm-6">
            <img src="imagenes/encabe.png" alt="logo institucional" width="100%">
        </div>
    </div>
    <div class="row">
        <?php include_once('partes/menu.inc'); ?>
    </div>
    <br />
</header>

<main class="row">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>🤝 Compartir recurso</strong>
        </div>
        <div class="panel-body">

            <div class="botones">
                <a href="carpetas.php">⬅️ Volver a mis carpetas</a>
            </div>

            <?php if ($mensaje !== ""): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form method="post" style="max-width:600px;">
                <p><strong>Ruta seleccionada:</strong> <?php echo htmlspecialchars($ruta_relativa); ?></p>

                <label><strong>Selecciona el usuario con quien compartir:</strong></label><br>
                <select name="amigo" class="form-control" required>
                    <option value="">-- Selecciona un usuario --</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo htmlspecialchars($u['usuario']); ?>">
                            <?php echo htmlspecialchars($u['usuario'] . " (" . $u['nombre'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <br><br>
                <button type="submit" class="btn btn-success">Compartir</button>
                <a href="carpetas.php" class="btn btn-secondary">Cancelar</a>
            </form>

        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>

