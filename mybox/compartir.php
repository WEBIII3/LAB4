<?php
// =========================================================
// compartir.php - Compartir archivos o carpetas
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

// Determinar si es archivo o carpeta
$archivo_id = isset($_GET['archivo_id']) ? (int)$_GET['archivo_id'] : null;
$carpeta_id = isset($_GET['carpeta_id']) ? (int)$_GET['carpeta_id'] : null;

if (!$archivo_id && !$carpeta_id) {
    die("<p style='color:red;'>❌ Debes especificar un archivo o carpeta para compartir.</p>");
}

$tipo = $archivo_id ? 'archivo' : 'carpeta';
$recurso_id = $archivo_id ?: $carpeta_id;
$recurso_nombre = '';

// Obtener información del recurso
if ($tipo === 'archivo') {
    $stmt = $conn->prepare("SELECT nombre FROM archivos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$recurso_id, $usuario_id]);
    $recurso_nombre = $stmt->fetchColumn();
} else {
    $stmt = $conn->prepare("SELECT nombre FROM carpetas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$recurso_id, $usuario_id]);
    $recurso_nombre = $stmt->fetchColumn();
}

if (!$recurso_nombre) {
    die("<p style='color:red;'>❌ Recurso no encontrado o no tienes permisos.</p>");
}

// Obtener lista de usuarios excepto el actual
$stmt = $conn->prepare("SELECT id, usuario, nombre FROM usuarios WHERE id != ?");
$stmt->execute([$usuario_id]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensaje = "";
$tipo_mensaje = "info";

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amigo_id = (int)$_POST['amigo'];

    if ($amigo_id <= 0) {
        $mensaje = "⚠️ Debes seleccionar un usuario válido.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Verificar que el usuario destino existe
            $stmt = $conn->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $stmt->execute([$amigo_id]);
            $amigo_nombre = $stmt->fetchColumn();

            if (!$amigo_nombre) {
                $mensaje = "❌ El usuario seleccionado no existe.";
                $tipo_mensaje = "danger";
            } else {
                // Verificar si ya está compartido
                $stmt = $conn->prepare("
                    SELECT id FROM compartidos 
                    WHERE propietario_id = ? 
                    AND " . ($tipo === 'archivo' ? 'archivo_id' : 'carpeta_id') . " = ? 
                    AND compartido_con_id = ?
                ");
                $stmt->execute([$usuario_id, $recurso_id, $amigo_id]);

                if ($stmt->fetchColumn()) {
                    $mensaje = "⚠️ Ya has compartido este recurso con $amigo_nombre.";
                    $tipo_mensaje = "warning";
                } else {
                    // Insertar en compartidos
                    if ($tipo === 'archivo') {
                        $stmt = $conn->prepare("
                            INSERT INTO compartidos (propietario_id, archivo_id, compartido_con_id, tipo) 
                            VALUES (?, ?, ?, 'archivo')
                        ");
                        $stmt->execute([$usuario_id, $archivo_id, $amigo_id]);
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO compartidos (propietario_id, carpeta_id, compartido_con_id, tipo) 
                            VALUES (?, ?, ?, 'carpeta')
                        ");
                        $stmt->execute([$usuario_id, $carpeta_id, $amigo_id]);
                    }

                    echo "<script>alert('✅ Recurso compartido exitosamente con $amigo_nombre'); window.location.href='carpetas.php';</script>";
                    exit();
                }
            }
        } catch (PDOException $e) {
            $mensaje = "❌ Error: " . htmlspecialchars($e->getMessage());
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener lista de usuarios con quienes ya está compartido
$compartidos_con = [];
if ($tipo === 'archivo') {
    $stmt = $conn->prepare("
        SELECT u.usuario, u.nombre, c.fecha 
        FROM compartidos c
        INNER JOIN usuarios u ON c.compartido_con_id = u.id
        WHERE c.propietario_id = ? AND c.archivo_id = ?
    ");
    $stmt->execute([$usuario_id, $archivo_id]);
} else {
    $stmt = $conn->prepare("
        SELECT u.usuario, u.nombre, c.fecha 
        FROM compartidos c
        INNER JOIN usuarios u ON c.compartido_con_id = u.id
        WHERE c.propietario_id = ? AND c.carpeta_id = ?
    ");
    $stmt->execute([$usuario_id, $carpeta_id]);
}
$compartidos_con = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>MyBox - Compartir recurso</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; border-bottom: 1px solid #ccc; text-align: left; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .alert-success { background: #d4edda; color: #155724; }
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
            <strong>🤝 Compartir <?php echo $tipo === 'archivo' ? 'archivo' : 'carpeta'; ?></strong>
        </div>
        <div class="panel-body">

            <div class="botones" style="margin-bottom: 15px;">
                <a href="carpetas.php" class="btn btn-default">⬅️ Volver a mis carpetas</a>
            </div>

            <?php if ($mensaje !== ""): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <div style="background: #f5f5f5; padding: 10px; margin: 15px 0; border-radius: 4px;">
                <strong>Recurso seleccionado:</strong> 
                <?php echo $tipo === 'archivo' ? '📄' : '📁'; ?> 
                <?php echo htmlspecialchars($recurso_nombre); ?>
            </div>

            <form method="post" style="max-width:600px;">
                <div class="form-group">
                    <label><strong>Selecciona el usuario con quien compartir:</strong></label><br>
                    <select name="amigo" class="form-control" required>
                        <option value="">-- Selecciona un usuario --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['usuario'] . " (" . $u['nombre'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <br>
                <button type="submit" class="btn btn-success">Compartir</button>
                <a href="carpetas.php" class="btn btn-secondary">Cancelar</a>
            </form>

            <?php if (count($compartidos_con) > 0): ?>
            <hr>
            <h4>👥 Compartido con:</h4>
            <table class="table table-bordered">
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Fecha compartido</th>
                </tr>
                <?php foreach ($compartidos_con as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['usuario']); ?></td>
                    <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                    <td><?php echo date("d/m/Y H:i", strtotime($c['fecha'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p style="color: #999; margin-top: 20px;">
                <em>Este recurso aún no se ha compartido con nadie.</em>
            </p>
            <?php endif; ?>

        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>
