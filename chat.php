<?php
session_start();
require 'lib/config.php';
require 'lib/socialnetwork-lib.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error en la conexión a la base de datos");
}
function saveMessage($pdo, $from, $to, $message) {
    $stmt = $pdo->prepare("INSERT INTO chats (de, para, mensaje, fecha, leido) VALUES (?, ?, ?, NOW(), 0)");
    return $stmt->execute([$from, $to, $message]);
}

// Obtener el nombre del usuario con el que se está chateando
if (isset($_GET['usuario'])) {
    $usuario_id = filter_input(INPUT_GET, 'usuario', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id_use = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetchColumn();
    if (!$usuario) {
        die("Usuario no encontrado");
    }
} else {
    die("Parámetro 'usuario' no encontrado en la URL.");
}

// Obtener historial de chat
$sess = $_SESSION['id'];
$stmt = $pdo->prepare("SELECT c.*, u.usuario, u.avatar FROM chats c 
                       JOIN usuarios u ON c.de = u.id_use
                       WHERE (c.de = ? AND c.para = ?) OR (c.de = ? AND c.para = ?) 
                       ORDER BY c.fecha ASC");
$stmt->execute([$usuario_id, $sess, $sess, $usuario_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Chat con <?php echo htmlspecialchars($usuario); ?></title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.3.2/socket.io.js"></script>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

    <?php echo Headerb($pdo); ?>
    <?php echo Side($pdo); ?>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Chat con <?php echo htmlspecialchars($usuario); ?>
            </h1>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary direct-chat direct-chat-primary">
                        <div class="box-body">
                            <div id="chat-messages" class="direct-chat-messages" style="height: 400px; overflow-y: scroll;">
                                <?php foreach ($chats as $ch): ?>
                                    <div class="direct-chat-msg <?php echo ($ch['de'] == $sess) ? 'right' : ''; ?>">
                                        <div class="direct-chat-info clearfix">
                                            <span class="direct-chat-name pull-<?php echo ($ch['de'] == $sess) ? 'right' : 'left'; ?>">
                                                <?php echo htmlspecialchars($ch['usuario']); ?>
                                            </span>
                                            <span class="direct-chat-timestamp pull-<?php echo ($ch['de'] == $sess) ? 'left' : 'right'; ?>">
                                                <?php echo htmlspecialchars($ch['fecha']); ?>
                                            </span>
                                        </div>
                                        <img class="direct-chat-img" src="avatars/<?php echo htmlspecialchars($ch['avatar']); ?>">
                                        <div class="direct-chat-text">
                                            <?php echo htmlspecialchars($ch['mensaje']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="box-footer">
                            <form id="chat-form" action="">
                                <div class="input-group">
                                    <input id="chat-input" type="text" name="mensaje" placeholder="Escribe un mensaje" class="form-control">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-primary btn-flat">Enviar</button>
                                    </span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php include 'footer.php'; ?>
    <?php echo ControlSidebar(); ?>
</div>

<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="plugins/fastclick/fastclick.js"></script>
<script src="dist/js/app.min.js"></script>
<script>
    const socket = io("http://localhost:3000");
    
    const chatForm = document.getElementById("chat-form");
    const chatInput = document.getElementById("chat-input");
    const chatMessages = document.getElementById("chat-messages");
    let messageQueue = [];

    socket.on('connect', () => {
        console.log('Conectado al servidor de Socket.IO');
        socket.emit('join chat', {
            userId: <?php echo $_SESSION['id']; ?>,
            receiverId: <?php echo $usuario_id; ?>
        });
    });

    chatForm.addEventListener("submit", (e) => {
        e.preventDefault();
        if (chatInput.value) {
            const message = {
                message: chatInput.value,
                userId: <?php echo $_SESSION['id']; ?>,
                username: "<?php echo htmlspecialchars($_SESSION['usuario']); ?>",
                avatar: "<?php echo htmlspecialchars($_SESSION['avatar']); ?>",
                receiverId: <?php echo $usuario_id; ?>,
                timestamp: Date.now()
            };
            console.log('Enviando mensaje:', message);// Mostrar el mensaje localmente inmediatamente
            appendMessage(message);
            
            socket.emit("chat message", message);
            chatInput.value = "";
        }
    });

    socket.on("chat message", (msg) => {
    console.log('Mensaje recibido:', msg);
    if (msg.userId == <?php echo $usuario_id; ?> || msg.userId == <?php echo $_SESSION['id']; ?>) {
        // Si el mensaje es del usuario actual, no lo mostramos de nuevo
        if (msg.userId != <?php echo $_SESSION['id']; ?>) {
            appendMessage(msg);
        }
    }
});

function appendMessage(msg) {
    console.log('Añadiendo mensaje al DOM:', msg);
    const messageElement = document.createElement("div");
    messageElement.classList.add("direct-chat-msg");
    if (msg.userId == <?php echo $_SESSION['id']; ?>) {
        messageElement.classList.add("right");
    }
    
    messageElement.innerHTML = `
        <div class="direct-chat-info clearfix">
            <span class="direct-chat-name pull-${msg.userId == <?php echo $_SESSION['id']; ?> ? "right" : "left"}">${escapeHtml(msg.username)}</span>
            <span class="direct-chat-timestamp pull-${msg.userId == <?php echo $_SESSION['id']; ?> ? "left" : "right"}">${new Date().toLocaleString()}</span>
        </div>
        <img class="direct-chat-img" src="avatars/${escapeHtml(msg.avatar)}" alt="User Avatar">
        <div class="direct-chat-text">${escapeHtml(msg.message)}</div>
    `;
    
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    socket.on('connect_error', (error) => {
        console.error('Error de conexión:', error);
    });
</script>
</body>
</html>