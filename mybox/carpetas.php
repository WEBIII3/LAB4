<?php
// ============================================================
// carpetas.php - Explorador de archivos desde base de datos
// ============================================================

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

// Obtener ID del usuario actual
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario_nombre]);
$usuario_id = $stmt->fetchColumn();

// ============================================
// 1️⃣ Determinar si es vista propia o compartida
// ============================================
$es_compartido = isset($_GET['shared']);
$propietario_id = $usuario_id;
$propietario_nombre = $usuario_nombre;

if ($es_compartido) {
    $propietario_nombre = $_GET['shared'];
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$propietario_nombre]);
    $propietario_id = $stmt->fetchColumn();
    
    if (!$propietario_id) {
        die("❌ Usuario propietario no encontrado.");
    }
}

// ============================================
// 2️⃣ Determinar carpeta actual
// ============================================
$carpeta_actual_id = isset($_GET['carpeta_id']) ? (int)$_GET['carpeta_id'] : null;
$carpeta_actual_nombre = 'Raíz';
$carpeta_actual_ruta = '';
$carpeta_padre_id = null;

if ($carpeta_actual_id) {
    $stmt = $conn->prepare("
        SELECT nombre, ruta_completa, carpeta_padre_id 
        FROM carpetas 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$carpeta_actual_id, $propietario_id]);
    $carpeta_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($carpeta_info) {
        $carpeta_actual_nombre = $carpeta_info['nombre'];
        $carpeta_actual_ruta = $carpeta_info['ruta_completa'];
        $carpeta_padre_id = $carpeta_info['carpeta_padre_id'];
    } else {
        die("❌ Carpeta no encontrada o sin permisos.");
    }
    
    // Validar permisos si es compartido
    if ($es_compartido && $carpeta_actual_id) {
        $stmt = $conn->prepare("
            SELECT 1 FROM compartidos 
            WHERE propietario_id = ? 
            AND carpeta_id = ? 
            AND compartido_con_id = ?
        ");
        $stmt->execute([$propietario_id, $carpeta_actual_id, $usuario_id]);
        if (!$stmt->fetchColumn()) {
            die("❌ No tienes permiso para acceder a esta carpeta compartida.");
        }
    }
}

// ============================================
// 3️⃣ Obtener subcarpetas
// ============================================
$stmt = $conn->prepare("
    SELECT id, nombre, fecha_creacion 
    FROM carpetas 
    WHERE usuario_id = ? AND carpeta_padre_id <=> ?
    ORDER BY nombre ASC
");
$stmt->execute([$propietario_id, $carpeta_actual_id]);
$subcarpetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// 4️⃣ Obtener archivos
// ============================================
$stmt = $conn->prepare("
    SELECT id, nombre, extension, mime_type, tamano, fecha_subida 
    FROM archivos 
    WHERE usuario_id = ? AND carpeta_id <=> ?
    ORDER BY nombre ASC
");
$stmt->execute([$propietario_id, $carpeta_actual_id]);
$archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// 5️⃣ Obtener recursos compartidos (solo vista propia)
// ============================================
$compartidos_archivos = [];
$compartidos_carpetas = [];

if (!$es_compartido && $carpeta_actual_id === null) {
    // Archivos compartidos
    $stmt = $conn->prepare("
        SELECT 
            c.id AS compartido_id,
            a.id AS archivo_id,
            a.nombre,
            a.ruta_completa,
            u.usuario AS propietario
        FROM compartidos c
        INNER JOIN archivos a ON c.archivo_id = a.id
        INNER JOIN usuarios u ON c.propietario_id = u.id
        WHERE c.compartido_con_id = ? AND c.tipo = 'archivo'
    ");
    $stmt->execute([$usuario_id]);
    $compartidos_archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Carpetas compartidas
    $stmt = $conn->prepare("
        SELECT 
            c.id AS compartido_id,
            cp.id AS carpeta_id,
            cp.nombre,
            cp.ruta_completa,
            u.usuario AS propietario
        FROM compartidos c
        INNER JOIN carpetas cp ON c.carpeta_id = cp.id
        INNER JOIN usuarios u ON c.propietario_id = u.id
        WHERE c.compartido_con_id = ? AND c.tipo = 'carpeta'
    ");
    $stmt->execute([$usuario_id]);
    $compartidos_carpetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
        .breadcrumb { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 4px; }
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
            <strong>
                <?php echo $es_compartido ? "📤 Carpeta compartida de $propietario_nombre" : "📁 Mi Box Personal"; ?>
            </strong>
        </div>
        <div class="panel-body">

            <!-- BREADCRUMB -->
            <div class="breadcrumb">
                <?php if ($carpeta_padre_id !== null): ?>
                    <?php
                    $url_padre = "carpetas.php?carpeta_id=$carpeta_padre_id";
                    if ($es_compartido) $url_padre .= "&shared=$propietario_nombre";
                    ?>
                    <a href="<?php echo $url_padre; ?>">⬅️ Subir nivel</a> |
                <?php endif; ?>
                
                <?php if ($carpeta_actual_id === null): ?>
                    <strong>📂 Raíz</strong>
                <?php else: ?>
                    <a href="carpetas.php<?php echo $es_compartido ? "?shared=$propietario_nombre" : ''; ?>">📂 Raíz</a>
                    / <strong><?php echo htmlspecialchars($carpeta_actual_nombre); ?></strong>
                <?php endif; ?>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <?php if (!$es_compartido): ?>
            <div class="botones">
                <?php
                $url_agregar = $carpeta_actual_id ? "agrearchi.php?carpeta_id=$carpeta_actual_id" : "agrearchi.php";
                $url_crear = $carpeta_actual_id ? "crear_carpeta.php?carpeta_id=$carpeta_actual_id" : "crear_carpeta.php";
                ?>
                <a href="<?php echo $url_agregar; ?>" class="btn btn-primary">📤 Subir archivo</a>
                <a href="<?php echo $url_crear; ?>" class="btn btn-success">📁 Crear carpeta</a>
            </div>
            <?php endif; ?>

            <!-- TABLA DE SUBCARPETAS -->
            <?php if (count($subcarpetas) > 0): ?>
            <h4>📁 Carpetas</h4>
            <table class="table table-striped">
                <tr>
                    <th>Ícono</th>
                    <th>Nombre</th>
                    <th>Fecha creación</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($subcarpetas as $carpeta): ?>
                <tr>
                    <td class='icono'>📁</td>
                    <td>
                        <?php
                        $url = "carpetas.php?carpeta_id={$carpeta['id']}";
                        if ($es_compartido) $url .= "&shared=$propietario_nombre";
                        ?>
                        <a href="<?php echo $url; ?>"><?php echo htmlspecialchars($carpeta['nombre']); ?></a>
                    </td>
                    <td><?php echo date("d/m/Y H:i", strtotime($carpeta['fecha_creacion'])); ?></td>
                    <td class='acciones'>
                        <?php if (!$es_compartido): ?>
                            <a href="codigos/borarchi.php?carpeta_id=<?php echo $carpeta['id']; ?>&type=carpeta" 
                               onclick="return confirmarBorrado('<?php echo htmlspecialchars($carpeta['nombre']); ?>', 'carpeta')">
                                🗑️ Eliminar
                            </a> |
                            <a href="compartir.php?carpeta_id=<?php echo $carpeta['id']; ?>">🤝 Compartir</a>
                        <?php else: ?>
                            <em>Carpeta compartida</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <!-- TABLA DE ARCHIVOS -->
            <?php if (count($archivos) > 0): ?>
            <h4>📄 Archivos</h4>
            <table class="table table-striped">
                <tr>
                    <th>Ícono</th>
                    <th>Nombre</th>
                    <th>Tamaño</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
                <?php foreach ($archivos as $archivo): ?>
                <?php
                $icono = match(strtolower($archivo['extension'])) {
                    'pdf' => '📄',
                    'jpg', 'jpeg', 'png', 'gif' => '🖼️',
                    'doc', 'docx' => '📝',
                    'xls', 'xlsx' => '📊',
                    'zip', 'rar' => '🗜️',
                    default => '📦'
                };
                $tamano_mb = round($archivo['tamano'] / 1048576, 2);
                ?>
                <tr>
                    <td class='icono'><?php echo $icono; ?></td>
                    <td>
                        <?php
                        $url = "abrArchi.php?archivo_id={$archivo['id']}";
                        if ($es_compartido) $url .= "&shared=$propietario_nombre";
                        ?>
                        <a href="<?php echo $url; ?>"><?php echo htmlspecialchars($archivo['nombre']); ?></a>
                    </td>
                    <td><?php echo $tamano_mb; ?> MB</td>
                    <td><?php echo strtoupper($archivo['extension']); ?></td>
                    <td><?php echo date("d/m/Y H:i", strtotime($archivo['fecha_subida'])); ?></td>
                    <td class='acciones'>
                        <?php if (!$es_compartido): ?>
                            <a href="codigos/borarchi.php?archivo_id=<?php echo $archivo['id']; ?>&type=archivo" 
                               onclick="return confirmarBorrado('<?php echo htmlspecialchars($archivo['nombre']); ?>', 'archivo')">
                                🗑️ Eliminar
                            </a> |
                            <a href="compartir.php?archivo_id=<?php echo $archivo['id']; ?>">🤝 Compartir</a>
                        <?php else: ?>
                            <em>Archivo compartido</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <!-- MENSAJE SI ESTÁ VACÍO -->
            <?php if (count($subcarpetas) == 0 && count($archivos) == 0): ?>
                <p style="text-align:center; padding:30px; color:#999;">
                    📂 Esta carpeta está vacía
                </p>
            <?php endif; ?>

            <!-- RECURSOS COMPARTIDOS CONMIGO -->
            <?php if (!$es_compartido && $carpeta_actual_id === null && (count($compartidos_archivos) > 0 || count($compartidos_carpetas) > 0)): ?>
            <hr>
            <h4>📤 Recursos compartidos contigo</h4>
            
            <?php if (count($compartidos_carpetas) > 0): ?>
            <h5>Carpetas compartidas</h5>
            <table class="table table-bordered">
                <tr><th>Propietario</th><th>Nombre</th><th>Acción</th></tr>
                <?php foreach ($compartidos_carpetas as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['propietario']); ?></td>
                    <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                    <td>
                        <a href="carpetas.php?carpeta_id=<?php echo $c['carpeta_id']; ?>&shared=<?php echo $c['propietario']; ?>">
                            📂 Abrir carpeta
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
            
            <?php if (count($compartidos_archivos) > 0): ?>
            <h5>Archivos compartidos</h5>
            <table class="table table-bordered">
                <tr><th>Propietario</th><th>Nombre</th><th>Acción</th></tr>
                <?php foreach ($compartidos_archivos as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['propietario']); ?></td>
                    <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                    <td>
                        <a href="abrArchi.php?archivo_id=<?php echo $c['archivo_id']; ?>&shared=<?php echo $c['propietario']; ?>">
                            📄 Ver o descargar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>
