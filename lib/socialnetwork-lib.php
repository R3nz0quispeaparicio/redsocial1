<?php

// Consulta para obtener los datos del usuario y almacenar en la sesión
$stmt = $pdo->prepare("SELECT id_use, usuario, avatar, fecha_reg FROM usuarios WHERE id_use = :userId");
$stmt->bindParam(':userId', $_SESSION['id']);
$stmt->execute();
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData) {
    $_SESSION['usuario'] = $userData['usuario'];
    $_SESSION['avatar'] = $userData['avatar'];
    $_SESSION['fecha_reg'] = $userData['fecha_reg'];
}

// Función para el encabezado
function Headerb($pdo) {
    ?>
    <!-- START HEADER -->
    <header class="main-header">
    
        <!-- Logo -->
        <a href="index.php" class="logo">
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg"><b>RED</b>SOCIAL</span>
        </a>
    
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
            <!-- Navbar Right Menu -->
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
    
                    <?php
                    // Consulta de notificaciones
                    $noti_query = $pdo->prepare("SELECT * FROM notificaciones WHERE user2 = :user2 AND leido = '0' ORDER BY id_not DESC");
                    $noti_query->bindParam(':user2', $_SESSION['id']);
                    $noti_query->execute();
                    $cuantas = $noti_query->rowCount();

                    // Marcar notificaciones como leídas
                    if ($cuantas > 0) {
                        marcarNotificacionesComoLeidas($pdo, $_SESSION['id']);
                    }
                    ?>
    
                    <!-- Notifications: style can be found in dropdown.less -->
                    <li class="dropdown notifications-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-bell-o"></i>
                            <?php if ($cuantas > 0): ?>
                                <span class="label label-warning"><?php echo $cuantas; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li class="header">Tienes <?php echo $cuantas; ?> notificaciones</li>
                            <li>
                                <!-- inner menu: contains the actual data -->
                                <ul class="menu">
    
                                    <?php                
                                    while($no = $noti_query->fetch(PDO::FETCH_ASSOC)) {
    
                                        $users_query = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :id_use");
                                        $users_query->bindParam(':id_use', $no['user1']);
                                        $users_query->execute();
                                        $usa = $users_query->fetch(PDO::FETCH_ASSOC);
                                    ?>
    
                                    <li>
                                        <a href="publicaciones.php?id=<?php echo $no['id_pub']; ?>">
                                            <i class="fa fa-users text-aqua"></i> El usuario <?php echo $usa['usuario']; ?> <?php echo $no['tipo']; ?> tu publicación
                                        </a>
                                    </li>
    
                                    <?php } ?>
    
                                </ul>
                            </li>
                        </ul>
                    </li>
    
                    <!-- User Account: style can be found in dropdown.less -->
                    <li class="dropdown user user-menu">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <?php
                            $stmt = $pdo->prepare("SELECT avatar FROM usuarios WHERE id_use = :userId");
                            $stmt->bindParam(':userId', $_SESSION['id']);
                            $stmt->execute();
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $avatarPath = $result['avatar'] ? 'avatars/' . $result['avatar'] : 'avatars/default-avatar.jpg';
                            ?>
                            <img src="<?php echo $avatarPath; ?>" class="user-image" alt="User Image">
                            <span class="hidden-xs"><?php echo ucwords($_SESSION['usuario']); ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <!-- User image -->
                            <li class="user-header">
                                <img src="<?php echo $avatarPath; ?>" class="img-circle" alt="User Image">
                                <p>
                                    <?php echo ucwords($_SESSION['usuario']); ?>
                                    <small>Miembro desde <?php echo date('Y M d', strtotime($_SESSION['fecha_reg'])); ?></small>
                                </p>
                            </li>
                            <!-- Menu Body -->
                            <li class="user-body">
                                <div class="row">
                                    <div class="col-xs-6 text-center">
                                        <a href="#">Seguidores</a>
                                    </div>
                                    <div class="col-xs-6 text-center">
                                        <a href="#">Seguidos</a>
                                    </div>
                                </div>
                                <!-- /.row -->
                            </li>
                            <!-- Menu Footer-->
                            <li class="user-footer">
                                <div class="pull-left">
                                    <a href="editarperfil.php?id=<?php echo $_SESSION['id'];?>" class="btn btn-default btn-flat">Editar perfil</a>
                                </div>
                                <div class="pull-right">
                                    <a href="logout.php" class="btn btn-default btn-flat">Cerrar sesión</a>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <!-- Control Sidebar Toggle Button -->
                    <li>
                        <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
                    </li>
                </ul>
            </div>
    
        </nav>
    </header>
    <!-- END HEADER -->
    <?php
}

// Función para la barra lateral
function Side($pdo) {
    ?>
    <!-- START LEFT SIDE -->
    <!-- Left side column. contains the logo and sidebar -->
    <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel">
                <div class="pull-left">
                    <?php
                    $stmt = $pdo->prepare("SELECT avatar FROM usuarios WHERE id_use = :userId");
                    $stmt->bindParam(':userId', $_SESSION['id']);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $avatarPath = $result['avatar'] ? 'avatars/' . $result['avatar'] : 'avatars/default-avatar.jpg';
                    ?>
                    <img src="<?php echo $avatarPath; ?>" width="50" alt="User Image">
                </div>
                <div class="pull-left info">
                    <p><?php echo ucwords($_SESSION['usuario']); ?></p>
                    <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
                </div>
            </div>
            <!-- search form -->
            <form action="#" method="get" class="sidebar-form">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Encuentra a tus amigos">
                    <span class="input-group-btn">
                        <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i></button>
                    </span>
                </div>
            </form>
            <!-- /.search form -->
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu">
                <li class="header">MENÚ DE NAVEGACIÓN</li>
                <li>
                    <a href="index.php">
                        <i class="fa fa-dashboard"></i> <span>Noticias</span>
                    </a>
                </li>
                <li>
                    <a href="mensajes.php">
                        <i class="fa fa-comment"></i> <span>Chat</span>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM chats WHERE para = :userId AND leido = 0");
                        $stmt->bindParam(':userId', $_SESSION['id']);
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $unreadMessages = $result['count'];
                        if ($unreadMessages > 0):
                        ?>
                        <span class="pull-right-container">
                            <small class="label pull-right bg-green"><?php echo $unreadMessages; ?></small>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="index.php">
                        <i class="fa fa-user"></i> <span>Mis seguidores</span>
                    </a>
                </li>
                <li>
                    <a href="index.php">
                        <i class="fa fa-arrow-right"></i> <span>Seguidos</span>
                    </a>
                </li>
                <li>
                    <a href="index.php">
                        <i class="fa fa-heart"></i> <span>Me gusta</span>
                    </a>
                </li>
            </ul>
        </section>
        <!-- /.sidebar -->
    </aside>
    <!-- END LEFT SIDE -->
    <?php
}

// Función para el Control Sidebar
function ControlSidebar() {
    ?>
    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Create the tabs -->
        <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
            <li class="active"><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-home"></i></a></li>
            <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content">
            <!-- Home tab content -->
            <div class="tab-pane active" id="control-sidebar-home-tab">
                <h3 class="control-sidebar-heading">Actividad Reciente</h3>
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
                                <span class="pull-right-container">
                                    <span class="label label-danger pull-right">70%</span>
                                </span>
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
                                <span class="pull-right-container">
                                    <span class="label label-success pull-right">95%</span>
                                </span>
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
                                <span class="pull-right-container">
                                    <span class="label label-warning pull-right">50%</span>
                                </span>
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
                                <span class="pull-right-container">
                                    <span class="label label-primary pull-right">68%</span>
                                </span>
                            </h4>
    
                            <div class="progress progress-xxs">
                                <div class="progress-bar progress-bar-primary" style="width: 68%"></div>
                            </div>
                        </a>
                    </li>
                </ul>
    
            </div>
            <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
            <div class="tab-pane" id="control-sidebar-settings-tab">
                <form method="post">
                    <h3 class="control-sidebar-heading">Configuración general</h3>
    
                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Informe sobre el uso del panel
                            <input type="checkbox" class="pull-right" checked>
                        </label>
    
                        <p>
                            Alguna información sobre esta opción de configuración general
                        </p>
                    </div>
                    <!-- /.form-group -->
    
                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Permitir redirección de correo
                            <input type="checkbox" class="pull-right" checked>
                        </label>
    
                        <p>
                            Hay otros conjuntos de opciones disponibles.
                        </p>
                    </div>
                    <!-- /.form-group -->
    
                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Mostrar el nombre del autor en las publicaciones 
                            <input type="checkbox" class="pull-right" checked>
                        </label>
    
                        <p>
                            Permitir que el usuario muestre su nombre en las publicaciones del blog
                        </p>
                    </div>
                    <!-- /.form-group -->
    
                    <h3 class="control-sidebar-heading">Configuraciones del chat</h3>
    
                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Muéstrame como en línea
                            <input type="checkbox" class="pull-right" checked>
                        </label>
                    </div>
                    <!-- /.form-group -->
    
                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Desactivar las notificaciones
                            <input type="checkbox" class="pull-right">
                        </label>
                    </div>
                    <!-- /.form-group -->
    
                    <div class="form-group">
                        <label class="control-sidebar-subheading">
                            Eliminar historial de chat
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
    <!-- Add the sidebar's background. This div must be placed immediately after the control sidebar -->
    <div class="control-sidebar-bg"></div>
    <?php
}

// Función para marcar notificaciones como leídas
function marcarNotificacionesComoLeidas($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE notificaciones SET leido = 1 WHERE user2 = :user2");
    $stmt->bindParam(':user2', $userId);
    $stmt->execute();
}
?>