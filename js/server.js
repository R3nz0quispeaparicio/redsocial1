const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});
const mysql = require('mysql2/promise');
// Configuración de la base de datos
const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'redsocial'
};

let messageCache = new Set();

io.on('connection', (socket) => {
  console.log('Un usuario se ha conectado', socket.id);

  socket.on('join chat', (data) => {
    const roomName = [data.userId, data.receiverId].sort().join('-');
    socket.join(roomName);
    console.log(`Usuario ${data.userId} se unió al chat ${roomName}`);
  });

  socket.on('chat message', async (msg) => {
    console.log('Mensaje recibido en el servidor:', msg);
    const roomName = [msg.userId, msg.receiverId].sort().join('-');
    
    // Verificar si el mensaje ya está en el caché
    const messageKey = `${msg.userId}-${msg.timestamp}`;
    if (messageCache.has(messageKey)) {
      console.log('Mensaje duplicado detectado, ignorando');
      return;
    }
    
    messageCache.add(messageKey);
    
    try {
      const connection = await mysql.createConnection(dbConfig);
      const [result] = await connection.execute(
        'INSERT INTO chats (de, para, mensaje, fecha, leido) VALUES (?, ?, ?, FROM_UNIXTIME(?), 0)',
        [msg.userId, msg.receiverId, msg.message, msg.timestamp / 1000]
      );
      console.log('Mensaje guardado en la base de datos');
      connection.end();

      // Emitir el mensaje después de guardarlo exitosamente
      io.to(roomName).emit('chat message', msg);
    } catch (error) {
      console.error('Error al guardar el mensaje en la base de datos:', error);
    }
    
    // Limpiar el caché después de un tiempo
    setTimeout(() => {
      messageCache.delete(messageKey);
    }, 60000); // Limpiar después de 1 minuto
  });

  socket.on('disconnect', () => {
    console.log('Un usuario se ha desconectado', socket.id);
  });
});

const PORT = process.env.PORT || 8080;
http.listen(PORT, () => {
  console.log(`Servidor escuchando en el puerto ${PORT}`);
});