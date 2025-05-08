<?php
require_once('../../config.php');

// Verifica que el usuario tenga permisos, si es necesario.
require_login();

// Configuración de la API de Gemini
define('GEMINI_API_URL', "https://generativelanguage.googleapis.com/v1beta/models/");
define('GEMINI_API_KEY', "AIzaSyDIZoRd-BlpnUfp4IeBOKoEOtS2pzfhLG4");
define('MODEL_ID', "gemini-2.0-flash-lite");
define('GENERATE_CONTENT_API', "streamGenerateContent");

$api_key = get_config('block_aiquestiongen', 'apikey');  // Cargar la API key desde la configuración
$url = get_config('block_aiquestiongen', 'apiurl');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['prompt'])) {
    // Enviar el tema a la API de Gemini y generar el banco de preguntas
    $response = send_topic_to_gemini("");
    header('Content-Type: application/json');
    $response = proccess_gemini_response($response);
    echo json_encode(["status" => "success", "response" => $response]);
} else {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "No se ha proporcionado un tema"]);
}

// Función para enviar el tema a la API de Gemini
function send_topic_to_gemini($text)
{
    $params = array(
        "contents" => [
            [
                "role" => 'user',
                "parts" => [
                    [
                        "text" => 'Genera 2 preguntas de opción múltiple en JSON con la siguiente estructura:
            {"text": "...", "options": ["...", "...", "..."], "correct": "..."}'
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 1,
            "topP" => 0.85,
            "topK" => 40,
            "maxOutputTokens" => 8192,
            "responseMimeType" => 'application/json',
        ]
    );

    $url = GEMINI_API_URL . MODEL_ID . ":" . GENERATE_CONTENT_API . "?key=" . GEMINI_API_KEY;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Función para procesar la respuesta de la API de Gemini
function proccess_gemini_response($response)
{
    $result = '';
    foreach ($response as $entry) {
        if (isset($entry['candidates'])) {
            foreach ($entry['candidates'] as $candidate) {
                if (isset($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        $result .= $part['text'];
                    }
                }
            }
        }
    }
    return $result;
}


