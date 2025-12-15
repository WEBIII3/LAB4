<?php
// =========================================================
// index.php - Página de login
// =========================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once('codigos/conexion.inc');

$Accion_Formulario = $_SERVER['PHP_SELF'];

if (isset($_POST['txtUsua']) && isset($_POST['txtContra'])) {

    $usuario = trim($_POST['txtUsua']);
    $contra = trim($_POST['txtContra']);

    // Aplica el mismo hash SHA256
    $hash = hash("sha256", $contra);

    // Busca usuario con contraseña hasheada
    $auxSql = sprintf(
        "SELECT id, nombre, usuario, email FROM usuarios 
         WHERE usuario='%s' AND contra='%s'",
        mysqli_real_escape_string($conex, $usuario), 
        $hash
    );

    $regis = mysqli_query($conex, $auxSql);

    // Limpia datos del formulario
    unset($_POST['txtUsua']);
    unset($_POST['txtContra']);

    if (mysqli_num_rows($regis) > 0) {
        $tupla = mysqli_fetch_assoc($regis);

        // Usuario válido, crea sesión
        $_SESSION["autenticado"] = "SI";
        $_SESSION["usuario_id"] = $tupla['id'];
        $_SESSION["nombre"] = $tupla['nombre'];
        $_SESSION["usuario"] = $tupla['usuario'];
        $_SESSION["email"] = $tupla['email'];

        // Redirigir a carpetas
        header("Location: carpetas.php");
        exit();
    } else {
        // Usuario o contraseña incorrectos
        header("Location: errores/400.php");
        exit();
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>MyBox - Ingreso</title>
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
        <div class="panel panel-primary logueo">
            <div class="panel-heading">
                <strong>🔐 Autentificación MyBox</strong>  
            </div>
            <div class="panel-body">
                <form action="<?php echo $Accion_Formulario; ?>" method="post">
                    <fieldset>
                        <label>Usuario:</label>
                        <input type="text" name="txtUsua" size="22" maxlength="15" required /><br>                    
                        <label>Contraseña:</label>
                        <input type="password" name="txtContra" size="22" maxlength="15" required />
                    </fieldset>
                    <input type="submit" value="Ingresar" class="btn btn-primary" />
                </form>
                <br>
                <p>¿No tienes cuenta? <a href="registrar.php">Regístrate aquí</a></p>
            </div>                                
        </div>            
    </main>
            
    <footer class="row">
    </footer>
    <?php include_once('partes/final.inc'); ?>        
</body>
</html>
