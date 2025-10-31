<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once('codigos/conexion.inc');

$Accion_Formulario = $_SERVER['PHP_SELF'];

if (isset($_POST['txtUsua']) && isset($_POST['txtContra'])) {

    $usuario = $_POST['txtUsua'];
    $contra = $_POST['txtContra'];

    // Aplica el mismo hash que usas al registrar
    $hash = hash("sha256", $contra);

    // Busca usuario con contraseña en hash
    $auxSql = sprintf(
        "SELECT nombre, usuario FROM usuarios 
         WHERE usuario='%s' AND contra='%s'",
        $usuario, $hash
    );

    $regis = mysqli_query($conex, $auxSql);

    // Limpia datos del formulario
    unset($_POST['txtUsua']);
    unset($_POST['txtContra']);

    if (mysqli_num_rows($regis) > 0) {
        $tupla = mysqli_fetch_assoc($regis);

        // Usuario válido, crea sesión
        $_SESSION["autenticado"] = "SI";
        $_SESSION["nombre"] = $tupla['nombre'];
        $_SESSION["usuario"] = $tupla['usuario'];

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
    <title>Ingreso al Sitio</title>
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
                <strong>Autentificación</strong>  
            </div>
            <div class="panel-body">
                <form action="<?php echo $Accion_Formulario; ?>" method="post">
                    <fieldset>
                        <label>Usuario:</label>
                        <input type="text" name="txtUsua" size="22" maxlength="15" required /><br>                    
                        <label>Contrase&ntilde;a:</label>
                        <input type="password" name="txtContra" size="22" maxlength="15" required />
                    </fieldset>
                    <input type="submit" value="Aceptar" />
                </form>
            </div>                                
        </div>   
        <br>
        <a href="registrar.php">Registrarse Aquí</a>            
    </main>
            
    <footer class="row">
    </footer>
    <?php include_once('partes/final.inc'); ?>        
</body>
</html>

