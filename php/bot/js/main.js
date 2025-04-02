const chatBox = document.getElementById('chat-box');
const userInput = document.getElementById('user-input');
const sendBtn = document.getElementById('send-btn');
const openBtn = document.getElementById('btn-open-chat');
const closeBtn = document.getElementById('btn-close-chat');
const container = document.getElementById('container-bot');

// Función para agregar un mensaje en el chat
function addMessage(message, sender, isTempory = false) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add(sender === 'user' ? 'user-message' : 'bot-message');

    const messageContent = document.createElement('div');
    messageContent.classList.add('message');
    messageContent.textContent = message;

    if (isTempory) {
        messageContent.classList.add('temporary');
    }

    messageDiv.appendChild(messageContent);
    chatBox.appendChild(messageDiv);

    chatBox.scrollTop = chatBox.scrollHeight; // Hacer scroll hacia abajo automáticamente

    return messageContent;
}

// Función para manejar el envío de mensajes
sendBtn.addEventListener('click', () => {
    const userMessage = userInput.value.trim();
    if (userMessage !== '') {
        // Agregar el mensaje del usuario
        addMessage(userMessage, 'user');

        // Mostrar mensaje temporal del bot
        const tempMessage = addMessage('Cargando...', 'bot', true);

        save_message(userMessage, 1); // Guardar el mensaje del usuario en la base de datos

        // Enviar el mensaje al backend de Flask
        fetch('/moodle/blocks/bot/response_bot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: userMessage })
        })
            .then(response => response.json())
            .then(data => {
                // Agregar la respuesta del bot
                const botMessage = data.response;
                tempMessage.textContent = botMessage;
                tempMessage.classList.remove('temporary'); // Quitar el mensaje temporal
                save_message(botMessage, 0); // Guardar la respuesta del bot en la base de datos
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => {
                tempMessage.textContent = 'Error al generar respuesta. Inténtalo de nuevo.' /*+ error*/;
                tempMessage.classList.remove('temporary');
                tempMessage.className = 'message-error'
                save_message(tempMessage.textContent, 0, 0); // Guardar la respuesta del bot en la base de datos
                chatBox.scrollTop = chatBox.scrollHeight;
            });

        // Limpiar el campo de entrada
        userInput.value = '';
    }

});

function save_message(msg, isbot, type = 1) {
    const message = msg.trim();
    if (message !== '') {
        fetch('/moodle/blocks/bot/save_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message, isbot: isbot, type: type })
        })
            .then(response => response.json())
            .then(data => {
                return data.success;
            })
            .catch(error => { console.error('Ocurrió un error en la solicitud', /*error*/); });
    }
}

// Función para abrir y cerrar el chat
openBtn.addEventListener('click', () => {
    container.classList.toggle('hidden');
    if (!container.classList.contains('hidden')) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
})
closeBtn.addEventListener('click', () => {
    container.classList.add('hidden');
})

// Permitir enviar con la tecla Enter
userInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendBtn.click();
        userInput.focus();
    }
});