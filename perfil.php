<?php
session_start();
require_once 'lib/config.php';
require_once 'lib/socialnetwork-lib.php';

// Evitar mostrar errores al usuario en producción
ini_set('error_reporting', 0);

// Redirigir si no hay sesión de usuario activa
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit(); // Asegura que el script se detenga después de la redirección
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Convertir a entero para seguridad

    try {
        // Conexión a la base de datos usando PDO
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Consulta para obtener información del usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $use = $stmt->fetch(PDO::FETCH_ASSOC);

        // Consulta para verificar la relación de amigos
        $stmt = $pdo->prepare("SELECT * FROM amigos WHERE de = :id AND para = :session_id OR de = :session_id AND para = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':session_id', $_SESSION['id'], PDO::PARAM_INT);
        $stmt->execute();
        $ami = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Error en la conexión: " . $e->getMessage());
    }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo htmlspecialchars($use['nombre']); ?> | REDSOCIAL</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
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
        <div class="col-md-3">

              <!-- Profile Image -->
            <div class="box box-primary">
              <div class="box-body box-profile">
                  <img class="profile-user-img img-responsive" src="avatars/<?php echo htmlspecialchars($use['avatar']); ?>" alt="User profile picture">

                  <h3 class="profile-username text-center"><?php echo htmlspecialchars($use['nombre']); ?></h3> 
                  <?php if($use['verificado'] != 0) {?>
                      <center><span class="glyphicon glyphicon-ok"></span></center>
                  <?php } ?>

                  <p class="text-muted text-center">Software Engineer</p>

                  <ul class="list-group list-group-unbordered">
                      <li class="list-group-item">
                          <b>seguidores</b> <a class="pull-right">1,322</a>
                      </li>
                      <li class="list-group-item">
                          <b>siguiendo</b> <a class="pull-right">543</a>
                      </li>
                      <li class="list-group-item">
                          <b>amigos</b> <a class="pull-right">13,287</a>
                      </li>
                  </ul>

                  <?php if($_SESSION['id'] != $id) {?>
                      <div class="solicitud-amistad">
                          <?php if($ami && $ami['estado'] == 0) { ?>
                              <center><h4>Esperando respuesta</h4></center>
                          <?php } else { ?>

                              <?php if($use['privada'] == 1 && (!$ami || $ami['estado'] == 0)) { ?>
                                  <form action="" method="post">
                                      <input type="submit" class="btn btn-primary btn-block" name="seguir" value="Enviar solicitud de amistad">
                                  </form>
                              <?php } ?>
                              <?php if($use['privada'] == 1 && $ami && $ami['estado'] == 1) { ?>
                                  <form action="" method="post">
                                      <input type="submit" class="btn btn-danger btn-block" name="dejarseguir" value="Dejar de seguir">
                                  </form>
                              <?php } ?>
                              <?php if($use['privada'] == 0 && (!$ami || $ami['estado'] == 0)) { ?>
                                  <form action="" method="post">
                                      <input type="submit" class="btn btn-primary btn-block" name="seguirdirecto" value="Seguir">
                                  </form>
                              <?php } ?>
                              <?php if($use['privada'] == 0 && $ami && $ami['estado'] == 1) { ?>
                                  <form action="" method="post">
                                      <input type="submit" class="btn btn-danger btn-block" name="dejarseguir" value="Dejar de seguir">
                                  </form>
                              <?php } ?>

                          <?php } ?>
                      </div>
                  <?php } ?>

                  <?php
                  if(isset($_POST['seguir'])) {
                      try {
                          $stmt = $pdo->prepare("INSERT INTO amigos (de, para, fecha, estado) VALUES (:session_id, :id, now(), '0')");
                          $stmt->bindParam(':session_id', $_SESSION['id'], PDO::PARAM_INT);
                          $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                          $stmt->execute();
                          header("Location: perfil.php?id=$id");
                          exit();
                      } catch (PDOException $e) {
                          die("Error: " . $e->getMessage());
                      }
                  }

                  if(isset($_POST['seguirdirecto'])) {
                      try {
                          $stmt = $pdo->prepare("INSERT INTO amigos (de, para, fecha, estado) VALUES (:session_id, :id, now(), '1')");
                          $stmt->bindParam(':session_id', $_SESSION['id'], PDO::PARAM_INT);
                          $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                          $stmt->execute();
                          header("Location: perfil.php?id=$id");
                          exit();
                      } catch (PDOException $e) {
                          die("Error: " . $e->getMessage());
                      }
                  }

                  if(isset($_POST['dejarseguir'])) {
                      try {
                          $stmt = $pdo->prepare("DELETE FROM amigos WHERE de = :id AND para = :session_id OR de = :session_id AND para = :id");
                          $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                          $stmt->bindParam(':session_id', $_SESSION['id'], PDO::PARAM_INT);
                          $stmt->execute();
                          header("Location: perfil.php?id=$id");
                          exit();
                      } catch (PDOException $e) {
                          die("Error: " . $e->getMessage());
                      }
                  }
                  ?>

                  <br>
                  <a href="chat.php?usuario=<?php echo $id; ?>"><input type="button" class="btn btn-default btn-block" name="dejarseguir" value="Enviar chat"></a>

              </div>
              <!-- /.box-body -->
        </div>


          <!-- About Me Box -->
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">Sobre mi</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <strong><i class="fa fa-book margin-r-5"></i> Educación</strong>

              <p class="text-muted">
                B.S. in Computer Science from the University of Tennessee at Knoxville
              </p>

              <hr>

              <strong><i class="fa fa-map-marker margin-r-5"></i> Location</strong>

              <p class="text-muted">Malibu, California</p>

              <hr>

              <strong><i class="fa fa-pencil margin-r-5"></i> Skills</strong>

              <p>
                <span class="label label-danger">UI Design</span>
                <span class="label label-success">Coding</span>
                <span class="label label-info">Javascript</span>
                <span class="label label-warning">PHP</span>
                <span class="label label-primary">Node.js</span>
              </p>

              <hr>

              <strong><i class="fa fa-file-text-o margin-r-5"></i> Notes</strong>

              <p>tengo sueño</p>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
          <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
              <li class="<?php echo $pag == 'miactividad' ? 'active' : ''; ?>"><a href="?id=<?php echo $id;?>&perfil=miactividad">Actividad</a></li>
              <li class="<?php echo $pag == 'informacion' ? 'active' : ''; ?>"><a href="?id=<?php echo $id;?>&perfil=informacion">Información</a></li>
              <li class="<?php echo $pag == 'fotos' ? 'active' : ''; ?>"><a href="?id=<?php echo $id;?>&perfil=fotos">Fotos</a></li>
            </ul>
            <div class="tab-content">

                
          <!-- codigo scroll -->
          <div class="scroll">

          <?php
          if($use['privada'] != 1) { ?>
          
            <?php
            $pagina = isset($_GET['perfil']) ? strtolower($_GET['perfil']) : 'miactividad';
            require_once $pagina.'.php';
            ?>

          <?php } elseif ($use['privada'] == 1 && $ami && $ami['estado'] == 1) { ?>
              
            <?php
            $pagina = isset($_GET['perfil']) ? strtolower($_GET['perfil']) : 'miactividad';
            require_once $pagina.'.php';
            ?>

          <?php } elseif ($use['privada'] == 1 && $_SESSION['id'] == $id) { ?>
              
            <?php
            $pagina = isset($_GET['perfil']) ? strtolower($_GET['perfil']) : 'miactividad';
            require_once $pagina.'.php';
            ?>


          <?php } else { ?>

          <center><h2>Este perfil es privado, envia una solicitud</h2></center>

          <?php } ?>

          </div>

            
                
              </div>
  
          </div>
          <!-- /.nav-tabs-custom -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Create the tabs -->
    <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
      <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
      <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
    </ul>
    <!-- Tab panes -->
    <div class="tab-content">
      <!-- Home tab content -->
      <div class="tab-pane" id="control-sidebar-home-tab">
        <h3 class="control-sidebar-heading">Recent Activity</h3>
        <ul class="control-sidebar-menu">
          <li>
            <a href="javascript:void(0)">
              <i class="menu-icon fa fa-birthday-cake bg-red"></i>

              <div class="menu-info">
                <h4 class="control-sidebar-subheading">Langdon's Birthday</h4>

                <p>Will be 23 on April 24th</p>
              </div>
            </a>
          </li>
          <li>
            <a href="javascript:void(0)">
              <i class="menu-icon fa fa-user bg-yellow"></i>

              <div class="menu-info">
                <h4 class="control-sidebar-subheading">Frodo Updated His Profile</h4>

                <p>New phone +1(800)555-1234</p>
              </div>
            </a>
          </li>
          <li>
            <a href="javascript:void(0)">
              <i class="menu-icon fa fa-envelope-o bg-light-blue"></i>

              <div class="menu-info">
                <h4 class="control-sidebar-subheading">Nora Joined Mailing List</h4>

                <p>nora@example.com</p>
              </div>
            </a>
          </li>
          <li>
            <a href="javascript:void(0)">
              <i class="menu-icon fa fa-file-code-o bg-green"></i>

              <div class="menu-info">
                <h4 class="control-sidebar-subheading">Cron Job 254 Executed</h4>

                <p>Execution time 5 seconds</p>
              </div>
            </a>
          </li>
        </ul>
        <!-- /.control-sidebar-menu -->

        <h3 class="control-sidebar-heading">Tasks Progress</h3>
        <ul class="control-sidebar-menu">
          <li>
            <a href="javascript:void(0)">
              <h4 class="control-sidebar-subheading">
                Custom Template Design
                <span class="label label-danger pull-right">70%</span>
              </h4>

              <div class="progress progress-xxs">
                <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
              </div>
            </a>
          </li>
          <li>
            <a href="javascript:void(0)">
              <h4 class="control-sidebar-subheading">
                Update Resume
                <span class="label label-success pull-right">95%</span>
              </h4>

              <div class="progress progress-xxs">
                <div class="progress-bar progress-bar-success" style="width: 95%"></div>
              </div>
            </a>
          </li>
          <li>
            <a href="javascript:void(0)">
              <h4 class="control-sidebar-subheading">
                Laravel Integration
                <span class="label label-warning pull-right">50%</span>
              </h4>

              <div class="progress progress-xxs">
                <div class="progress-bar progress-bar-warning" style="width: 50%"></div>
              </div>
            </a>
          </li>
          <li>
            <a href="javascript:void(0)">
              <h4 class="control-sidebar-subheading">
                Back End Framework
                <span class="label label-primary pull-right">68%</span>
              </h4>

              <div class="progress progress-xxs">
                <div class="progress-bar progress-bar-primary" style="width: 68%"></div>
              </div>
            </a>
          </li>
        </ul>
        <!-- /.control-sidebar-menu -->

      </div>
      <!-- /.tab-pane -->
      <!-- Stats tab content -->
      <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
      <!-- /.tab-pane -->
      <!-- Settings tab content -->
      <div class="tab-pane" id="control-sidebar-settings-tab">
        <form method="post">
          <h3 class="control-sidebar-heading">General Settings</h3>

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Report panel usage
              <input type="checkbox" class="pull-right" checked>
            </label>

            <p>
              Some information about this general settings option
            </p>
          </div>
          <!-- /.form-group -->

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Allow mail redirect
              <input type="checkbox" class="pull-right" checked>
            </label>

            <p>
              Other sets of options are available
            </p>
          </div>
          <!-- /.form-group -->

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Expose author name in posts
              <input type="checkbox" class="pull-right" checked>
            </label>

            <p>
              Allow the user to show his name in blog posts
            </p>
          </div>
          <!-- /.form-group -->

          <h3 class="control-sidebar-heading">Chat Settings</h3>

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Show me as online
              <input type="checkbox" class="pull-right" checked>
            </label>
          </div>
          <!-- /.form-group -->

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Turn off notifications
              <input type="checkbox" class="pull-right">
            </label>
          </div>
          <!-- /.form-group -->

          <div class="form-group">
            <label class="control-sidebar-subheading">
              Delete chat history
              <a href="javascript:void(0)" class="text-red pull-right"><i class="fa fa-trash-o"></i></a>
            </label>
          </div>
          <!-- /.form-group -->
        </form>
      </div>
      <!-- /.tab-pane -->
    </div>
  </aside>
  <!-- /.control-sidebar -->
  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->

<!-- jQuery 3 -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- Bootstrap 3.3.6 -->
<script src="bootstrap/js/bootstrap.min.js"></script>
<!-- SlimScroll -->
<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="plugins/slimScroll/jquery.slimscroll.js"></script>
<!-- FastClick -->
<script src="plugins/fastclick/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/app.js"></script>
<script src="dist/js/app.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="dist/js/demo.js"></script>
</body>
</html>
<?php

} // finaliza if GET
?>
