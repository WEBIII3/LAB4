<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

include_once('codigos/conexion.inc');

// Ruta base del sistema
$ruta_base = getenv("HOME_PATH") ?: "/home/myboxusers";
$usuario = $_SESSION["usuario"];

// ============================================
// 1️⃣ Determinar si es vista propia o compartida
// ============================================
if (isset($_GET['shared'])) {
    $propietario = $_GET['shared'];
    $usuario_vista = $propietario;
    $es_compartido = true;
} else {
    $usuario_vista = $usuario;
    $es_compartido = false;
}

// ============================================
// 2️⃣ Construcción de rutas y validaciones
// ============================================
$dir_relativo = isset($_GET['dir']) ? $_GET['dir'] : '';
$ruta_usuario = "$ruta_base/$usuario_vista";
$ruta_actual = realpath($ruta_usuario . '/' . $dir_relativo);

if (!$ruta_actual || strpos($ruta_actual, $ruta_usuario) !== 0) {
    die("❌ Acceso no autorizado o ruta inválida.");
}

// ============================================
// 3️⃣ Obtener lista de archivos y carpetas
// ============================================
$elementos = scandir($ruta_actual);
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>MyBox - Carpetas</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; border-bottom: 1px solid #ccc; text-align: left; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
        .acciones a { margin-right: 10px; }
        .icono { font-size: 22px; }
        .botones { margin: 15px 0; }
    </style>
    <script>
        function confirmarBorrado(nombre, tipo) {
            return confirm("¿Seguro que desea eliminar " + tipo + " '" + nombre + "'?");
        }
    </script>
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
            <strong><?php echo $es_compartido ? "📤 Carpeta compartida de $usuario_vista" : "📁 Mi Box Personal"; ?></strong>
        </div>
        <div class="panel-body">

            <!-- =======================
                 BOTONES DE ACCIÓN
            ======================= -->
            <div class="botones">
                <?php if ($dir_relativo != ''): ?>
                    <?php
                        $arr = explode('/', $dir_relativo);
                        array_pop($arr);
                        $padre = implode('/', $arr);
                        $urlBack = "?dir=" . urlencode($padre);
                        if ($es_compartido) $urlBack .= "&shared=" . urlencode($usuario_vista);
                    ?>
                    <a href="<?php echo $urlBack; ?>">⬅️ Subir nivel</a>
                <?php endif; ?>

                <?php if (!$es_compartido): ?>
                    | <a href="agrearchi.php?dir=<?php echo urlencode($dir_relativo); ?>">📤 Subir archivo</a>
                    | <a href="crear_carpeta.php?dir=<?php echo urlencode($dir_relativo); ?>">📁 Crear carpeta</a>
                    | <a href="compartir.php?dir=<?php echo urlencode($dir_relativo); ?>">🤝 Compartir</a>
                <?php endif; ?>
            </div>

            <!-- =======================
                 TABLA DE ELEMENTOS
            ======================= -->
            <table class="table table-striped">
                <tr>
                    <th>Ícono</th>
                    <th>Nombre</th>
                    <th>Tamaño (MB)</th>
                    <th>Último acceso</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
                <?php
                $contador = 0;
                foreach ($elementos as $elem) {
                    if ($elem == '.' || $elem == '..') continue;
                    $ruta_elem = "$ruta_actual/$elem";
                    $rel_path = trim($dir_relativo . '/' . $elem, '/');

                    $es_dir = is_dir($ruta_elem);
                    $tam = $es_dir ? '-' : round(filesize($ruta_elem) / 1048576, 2);
                    $icono = $es_dir ? '📁' : '📦';
                    $tipo = $es_dir ? 'Carpeta' : 'Archivo';

                    if (!$es_dir) {
                        $ext = strtolower(pathinfo($elem, PATHINFO_EXTENSION));
                        $icono = match ($ext) {
                            'pdf' => '📄',
                            'jpg', 'jpeg', 'png' => '🖼️',
                            'doc', 'docx' => '📝',
                            default => '📦'
                        };
                    }

                    echo "<tr>
                        <td class='icono'>$icono</td>";

                    if ($es_dir) {
                        $link = "?dir=" . urlencode($rel_path);
                        if ($es_compartido) $link .= "&shared=" . urlencode($usuario_vista);
                        echo "<td><a href='$link'>$elem</a></td>";
                    } else {
                        $link = "abrArchi.php?dir=" . urlencode($rel_path);
                        if ($es_compartido) $link .= "&shared=" . urlencode($usuario_vista);
                        echo "<td><a href='$link'>$elem</a></td>";
                    }

                    echo "<td>$tam</td>
                          <td>" . date("d/m/Y H:i", filemtime($ruta_elem)) . "</td>
                          <td>$tipo</td>
                          <td class='acciones'>";
                    if (!$es_compartido) {
                        echo "<a href='codigos/borarchi.php?dir=" . urlencode($rel_path) . "&type=" . ($es_dir ? 'carpeta' : 'archivo') . "' onclick='return confirmarBorrado(\"$elem\", \"$tipo\")'>🗑️ Eliminar</a> |
                              <a href='compartir.php?dir=" . urlencode($rel_path) . "'>🤝 Compartir</a>";
                    } else {
                        echo "<em>Recurso compartido</em>";
                    }
                    echo "</td></tr>";
                    $contador++;
                }

                if ($contador == 0) {
                    echo "<tr><td colspan='6'>📂 Carpeta vacía.</td></tr>";
                }
                ?>
            </table>

            <?php if (!$es_compartido): ?>
            <hr>
            <h4>📤 Archivos y carpetas compartidos contigo:</h4>
            <table class="table table-bordered">
                <tr><th>Propietario</th><th>Ruta</th><th>Acción</th></tr>
                <?php
                $stmt = $conn->prepare("SELECT propietario, ruta FROM compartidos WHERE compartido_con = ?");
                $stmt->execute([$usuario]);
                $compartidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($compartidos) == 0) {
                    echo "<tr><td colspan='3'>No tienes recursos compartidos.</td></tr>";
                } else {
                    foreach ($compartidos as $c) {
                        $prop = htmlspecialchars($c['propietario']);
                        $ruta_rel = htmlspecialchars($c['ruta']);
                        $ruta_real = realpath("$ruta_base/$prop/$ruta_rel");

                        if (is_dir($ruta_real)) {
                            $link = "carpetas.php?shared=$prop&dir=$ruta_rel";
                            $accion = "📂 Abrir carpeta";
                        } else {
                            $link = "abrArchi.php?shared=$prop&dir=$ruta_rel";
                            $accion = "📄 Ver o descargar";
                        }

                        echo "<tr>
                            <td>$prop</td>
                            <td>$ruta_rel</td>
                            <td><a href='$link'>$accion</a></td>
                        </tr>";
                    }
                }
                ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>

