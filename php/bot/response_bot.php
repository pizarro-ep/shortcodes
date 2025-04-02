<?php
require_once('../../config.php');

// Verifica que el usuario tenga permisos, si es necesario.
require_login();

$api_key = get_config('block_bot', 'apikey');  // Cargar la API key desde la configuración
$url = get_config('bolck_bot', 'apiurl'); //'http://127.0.0.1:5000/chat';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['message'])) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n". "x-api-key: $api_key\r\n", // Agregar la API key
            'method'  => 'POST', // Método POST
            'content' => json_encode($data) // Codificar los datos como JSON
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents("http://127.0.0.1:5000/chat", false, $context);
    if ($response === FALSE) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al contactar el servidor de Flask']);
        exit;
    }
    header('Content-Type: application/json');
    echo $response;
} else {
    echo json_encode(['success' => false]);
}