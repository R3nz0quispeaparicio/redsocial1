<?php
session_start();
include 'lib/config.php';
include 'lib/socialnetwork-lib.php';

// Establecer el nivel de error adecuado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si el usuario no ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit(); // Detener la ejecución después de redirigir
}

// Función para obtener la conexión PDO
function getPDOConnection() {
    $host = 'localhost';
    $dbname = 'redsocial';
    $username = 'root';
    $password = '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $username, $password);
        // Configurar PDO para lanzar excepciones en caso de errores
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Verificar si se proporciona un ID válido para editar el perfil
if(isset($_GET['id'])) {
    $id = $_GET['id'];

    // Consulta preparada para obtener el usuario
    $sql = "SELECT * FROM usuarios WHERE id_use = :id";
    
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $use = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si el usuario de la sesión coincide con el usuario que se está editando
        if($_SESSION['id'] != $id) {
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Error al obtener el usuario: " . $e->getMessage());
    }
}

// Procesar la actualización del perfil
if(isset($_POST['actualizar'])) {
    $nombre = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $email = $_POST['email'];
    $sexo = $_POST['sexo'];
    $nacimiento = $_POST['nacimiento'];

    try {
        $pdo = getPDOConnection();

        // Verificar si el nombre de usuario ya está en uso
        $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM usuarios WHERE usuario = :usuario AND id_use != :id");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row['count'] == 0) {
            // Procesar la carga de la imagen del avatar si se proporciona
            if(isset($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['tmp_name'] != '') {
                $type = 'jpg'; // Asumiendo que solo se permiten archivos jpg
                $rfoto = $_FILES['avatar']['tmp_name'];
                $name = $id.'.'.$type;
                $destino = 'avatars/'.$name;
                move_uploaded_file($rfoto, $destino);
                $nombrea = $name;
            } else {
                $nombrea = $use['avatar']; // Mantener el avatar existente
            }

            // Actualizar los datos del usuario
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, usuario = :usuario, email = :email, sexo = :sexo, nacimiento = :nacimiento, avatar = :avatar WHERE id_use = :id");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':sexo', $sexo);
            $stmt->bindParam(':nacimiento', $nacimiento);
            $stmt->bindParam(':avatar', $nombrea);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Redireccionar después de la actualización
            header("Location: editarperfil.php?id=".$_SESSION['id']);
            exit();
        } else {
            echo 'El nombre de usuario ya está en uso, por favor elija otro.';
        }
    } catch (PDOException $e) {
        die("Error al actualizar el perfil: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>EDITAR MI PERFIL</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

<?php echo Headerb($pdo); ?>

<?php echo Side($pdo); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-md-8">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Editar mi perfil</h3>
          </div>
          <form role="form" method="post" action="" enctype="multipart/form-data">
            <div class="box-body">
              <div class="form-group">
                <label for="exampleInputEmail1">Nombre completo</label>
                <input type="text" name="nombre" class="form-control" placeholder="Nombre completo" value="<?php echo isset($use['nombre']) ? htmlspecialchars($use['nombre']) : ''; ?>">
              </div>
              <div class="form-group">
                <label for="exampleInputEmail1">Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="Usuario" value="<?php echo isset($use['usuario']) ? htmlspecialchars($use['usuario']) : ''; ?>">
              </div>
              <div class="form-group">
                <label for="exampleInputEmail1">Email</label>
                <input type="text" name="email" class="form-control" placeholder="Email" value="<?php echo isset($use['email']) ? htmlspecialchars($use['email']) : ''; ?>">
              </div>
              <div class="form-group">
                <label for="exampleInputFile">Cambiar mi avatar</label>
                <input type="file" name="avatar">
              </div>
              <div class="checkbox">
                <label>
                  <input type="radio" value="H" name="sexo" <?php echo isset($use['sexo']) && $use['sexo'] == 'H' ? 'checked' : ''; ?>> Hombre <br>
                  <input type="radio" value="M" name="sexo" <?php echo isset($use['sexo']) && $use['sexo'] == 'M' ? 'checked' : ''; ?>> Mujer
                </label>
              </div>
              <div class="form-group">
                <label>Fecha de nacimiento</label>
                <div class="input-group">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="text" name="nacimiento" placeholder="<?php echo isset($use['nacimiento']) ? htmlspecialchars($use['nacimiento']) : ''; ?>" class="form-control" data-inputmask="'alias': 'yyyy-mm-dd'" data-mask>
                </div>
              </div>
            </div>
            <div class="box-footer">
              <button type="submit" name="actualizar" class="btn btn-primary">Actualizar datos</button>
            </div>
          </form>
        </div>
      </div>
      <div class="col-md-4">
        <!-- Contenido adicional como listas de usuarios, etc. -->
      </div>
    </div>
  </section>
</div>


</div>
<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/input-mask/jquery.inputmask.js"></script>
<script src="plugins/input-mask/jquery.inputmask.date.extensions.js"></script>
<script src="plugins/input-mask/jquery.inputmask.extensions.js"></script>
<script src="dist/js/app.min.js"></script>
<script>
  $(function () {
    $("[data-mask]").inputmask();
  });
</script>
</body>
</html>
