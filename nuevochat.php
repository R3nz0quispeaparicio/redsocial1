<?php
session_start();

// Incluir configuración y funciones
require_once 'lib/config.php';
require_once 'lib/socialnetwork-lib.php';

// Evitar mostrar errores al usuario en producción
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');

// Redirigir si no hay sesión de usuario activa
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Error en la conexión: " . $e->getMessage());
    die("Error en la conexión a la base de datos");
}

// Función para sanear entradas de usuario
function sanitizeInput($input) {
    return htmlspecialchars(stripslashes(trim($input)));
}

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = sanitizeInput($_POST['to']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);

    // Aquí deberías agregar la lógica para crear un nuevo chat en la base de datos
    // Por ejemplo:
    $stmt = $pdo->prepare("INSERT INTO c_chats (de, para) VALUES (?, ?)");
    $stmt->execute([$_SESSION['id'], $to]);
    $chatId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO chats (id_cch, de, para, mensaje, fecha, leido) VALUES (?, ?, ?, ?, NOW(), 0)");
    $stmt->execute([$chatId, $_SESSION['id'], $to, $message]);

    // Redirigir al usuario al nuevo chat
    header("Location: chat.php?usuario=" . $to);
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Nuevo Chat - REDSOCIAL</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
    <link rel="stylesheet" href="plugins/iCheck/flat/blue.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.3.2/socket.io.js"></script>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <?php echo Headerb($pdo); ?>
    <?php echo Side($pdo); ?>
  
    <div class="content-wrapper">
        <section class="content-header">
            <h1>Nuevo Chat</h1>
        </section>
    
        <section class="content">
            <div class="row">
                <div class="col-md-3">
                    <a href="mensajes.php" class="btn btn-primary btn-block margin-bottom">Volver a los chats</a>
                </div>
        
                <div class="col-md-9">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Iniciar nuevo chat</h3>
                        </div>
                        <form id="new-chat-form" method="post" action="">
                            <div class="box-body">
                                <div class="form-group">
                                    <select class="form-control" name="to" required>
                                        <option value="">Seleccionar usuario</option>
                                        <?php
                                        // Obtener lista de usuarios
                                        $stmt = $pdo->prepare("SELECT id_use, usuario FROM usuarios WHERE id_use != ?");
                                        $stmt->execute([$_SESSION['id']]);
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . $row['id_use'] . "'>" . htmlspecialchars($row['usuario']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="form-control" name="subject" placeholder="Asunto">
                                </div>
                                <div class="form-group">
                                    <textarea id="compose-textarea" name="message" class="form-control" style="height: 300px" required></textarea>
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="pull-right">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-envelope-o"></i> Enviar</button>
                                </div>
                                <button type="reset" class="btn btn-default"><i class="fa fa-times"></i> Descartar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
  
    <footer class="main-footer">
        <div class="pull-right hidden-xs">
            <b>Versión</b> 2.3.8
        </div>
    </footer>
  
    <?php echo ControlSidebar(); ?>
</div>

<script src="https://code.jquery.com/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="plugins/fastclick/fastclick.js"></script>
<script src="dist/js/app.min.js"></script>
<script>
    const socket = io("<?php echo $socket_io_url; ?>");
    
    const newChatForm = document.getElementById("new-chat-form");
    const composeTextarea = document.getElementById("compose-textarea");

    newChatForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(newChatForm);
        
        // Emitir el evento de nuevo chat a través de Socket.IO
        socket.emit("new chat", {
            from: <?php echo $_SESSION['id']; ?>,
            to: formData.get("to"),
            subject: formData.get("subject"),
            message: formData.get("message")
        });

        // Enviar el formulario de manera tradicional
        newChatForm.submit();
    });

    socket.on("chat created", (data) => {
        // Manejar la respuesta del servidor después de crear el chat
        console.log("Nuevo chat creado:", data);
        // Puedes agregar aquí lógica adicional si es necesario
    });
</script>
</body>
</html>