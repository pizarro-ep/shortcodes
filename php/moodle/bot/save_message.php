<?php
require_once('../../config.php');
global $DB, $USER;

// Obtener los datos enviados desde la solicitud AJAX
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['message'])) {
    $message = $data['message'];
    $isbot = $data['isbot'];  // 0 si es el usuario, 1 si es el bot
    $type = $data['type'];  // 0 si es error, 1 si es exito

    // Guardar el mensaje en la base de datos
    $record = new stdClass();
    $record->message = $message;
    $record->isbot = $isbot;
    $record->type = $type;
    $record->timecreated = time();

    // Insertar el mensaje en la tabla bot_chat_messages
    $messageid = $DB->insert_record('bot_chat_messages', $record);

    // Relacionar el mensaje con el usuario actual
    $recordchat = new stdClass();
    $recordchat->userid = $USER->id;
    $recordchat->messageid = $messageid;

    // Insertar en la tabla bot_chat
    $DB->insert_record('bot_chat', $recordchat);

    // Devolver una respuesta de éxito en formato JSON
    echo json_encode(['success' => true]);
} else {
    // Error: no se recibió el mensaje
    echo json_encode(['success' => false]);
}
