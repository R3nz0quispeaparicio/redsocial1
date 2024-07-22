const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http, {
    cors: {
        origin: "http://localhost", 
        methods: ["GET", "POST"],
        credentials: true
    }
});
const mysql = require('mysql2/promise');

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
    // Validación de mensajes
    if (!msg.userId || !msg.receiverId || !msg.message || !msg.timestamp) {
      console.error('Mensaje incompleto recibido:', msg);
      return;
    }

    console.log('Mensaje recibido en el servidor:', msg);
    const roomName = [msg.userId, msg.receiverId].sort().join('-');
    
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
      io.to(roomName).emit('chat message', msg);
    } catch (error) {
      console.error('Error al guardar el mensaje en la base de datos:', error);
    }
    
    setTimeout(() => {
      messageCache.delete(messageKey);
    }, 60000);
  });

  socket.on('disconnect', () => {
    console.log('Un usuario se ha desconectado', socket.id);
  });

  socket.on('error', (error) => {
    console.error('Error de socket:', error);
  });
});

const PORT = process.env.PORT || 3000;
http.listen(3000, () => {
  console.log('Servidor corriendo en http://localhost:3000');
});