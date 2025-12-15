<?php
// =========================================================
// registrar.php - Registro de nuevos usuarios
// =========================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('codigos/conexion.inc');

if(isset($_POST['txtUsua']) && isset($_POST['txtContra']) && isset($_POST['txtNomb']) && isset($_POST['txtEmail'])){
    
    $usuario = trim($_POST['txtUsua']);
    $contra = trim($_POST['txtContra']);
    $nombre = trim($_POST['txtNomb']);
    $email = trim($_POST['txtEmail']);
    
    // Validaciones básicas
    if (empty($usuario) || empty($contra) || empty($nombre) || empty($email)) {
        echo "<script>alert('❌ Todos los campos son obligatorios'); window.location.href='registrar.php';</script>";
        exit();
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('❌ Email no válido'); window.location.href='registrar.php';</script>";
        exit();
    }
    
    try {
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? OR email = ?");
        $stmt->execute([$usuario, $email]);
        
        if ($stmt->fetchColumn()) {
            echo "<script>alert('⚠️ El usuario o email ya existe'); window.location.href='registrar.php';</script>";
            exit();
        }
        
        // Insertar nuevo usuario con contraseña hasheada
        $hash_contra = hash("sha256", $contra);
        
        $stmt = $conn->prepare("
            INSERT INTO usuarios (usuario, contra, nombre, email) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$usuario, $hash_contra, $nombre, $email]);
        
        // Obtener el ID del usuario recién creado
        $nuevo_usuario_id = $conn->lastInsertId();
        
        // Iniciar sesión automáticamente
        session_start();
        $_SESSION["autenticado"] = "SI";
        $_SESSION["usuario_id"] = $nuevo_usuario_id;
        $_SESSION["nombre"] = $nombre;
        $_SESSION["usuario"] = $usuario;
        $_SESSION["email"] = $email;
        
        // Redirigir a carpetas (ya no necesitamos creadir.php porque no hay directorios físicos)
        header("location: carpetas.php");
        exit();
        
    } catch (Exception $e) {
        echo "<script>alert('❌ Error al registrar: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='registrar.php';</script>";
        exit();
    } finally {
        unset($_POST['txtUsua']);
        unset($_POST['txtContra']);
        unset($_POST['txtNomb']);
        unset($_POST['txtEmail']);
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>MyBox - Registrarse</title>
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
        <div class="panel panel-primary datos3">
            <div class="panel-heading">
                <strong>📝 Registro de Usuario</strong>
            </div>
            <div class="panel-body">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                    <fieldset>
                        <label>Usuario:</label>
                        <input type="text" name="txtUsua" size="22" maxlength="15" required 
                               pattern="[A-Za-z0-9_]+" 
                               title="Solo letras, números y guión bajo" /><br>
                        
                        <label>Contraseña:</label>
                        <input type="password" name="txtContra" size="22" maxlength="15" required /><br>
                        
                        <label>Nombre Completo:</label>
                        <input type="text" name="txtNomb" size="40" maxlength="30" required /><br>
                        
                        <label>Correo Electrónico:</label>
                        <input type="email" name="txtEmail" size="55" maxlength="50" required /><br>
                    </fieldset>
                    <input type="submit" value="Registrarse" class="btn btn-success" />
                    <a href="index.php" class="btn btn-default">Volver al login</a>
                </form>
            </div>
        </div>
    </main>

    <footer class="row">
    </footer>
    <?php include_once('partes/final.inc'); ?>
</body>
</html>
