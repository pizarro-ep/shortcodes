<?php
// Constantes del servicio web
define('WEB_SERVICE_TOKEN', '80de18c8874540b61e8849a5f0f1841c'); //    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('WEB_SERVICE_DOMAIN', 'http://172.22.9.249/moodle'); //    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('WEB_SERVICES_REST', '/webservice/rest/server.php');
define('WEB_SERVICES_REST_FORMAT', 'json');

// Funciones a utilizar 
define('FN_CORE_USER_CREATE_USERS', 'core_user_create_users');

define("LIMIT_PER_REQUEST", 100); //    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|

try {
    $custom_fields = [['type' => 'region', 'value' => 'LIMA']];
    $users = [
        ['username' => 'user_prueba','firstname' => 'Prueba','lastname' => 'Prueba','email' => 'Tq4wI@example.com','password' => '123456.Ab', 'customfields' => $custom_fields],
        ['username' => 'user_prueba_2','firstname' => 'Prueba2','lastname' => 'Prueba2','email' => 'Tq4wI2@example.com','password' => '123456.Ab', 'customfields' => $custom_fields],
        ['username' => 'user_prueba_3','firstname' => 'Prueba3','lastname' => 'Prueba3','email' => 'Tq4wI3@example.com','password' => '123456.Ab', 'customfields' => $custom_fields],
    ];
    $response = create_users($users);
    echo $response;
    echo "Proceso completado con éxito.\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

function create_users(array $users): bool|string
{
    try {
        $params = common_params();
        $params['wsfunction'] = FN_CORE_USER_CREATE_USERS;
        $params['users'] = $users;
        return fn_curl($params);
    } catch (Exception $e) {
        throw $e;
    }
}

function common_params(): array
{
    return ['wstoken' => WEB_SERVICE_TOKEN, 'moodlewsrestformat' => WEB_SERVICES_REST_FORMAT];
}

function fn_curl($params): bool|string
{
    try {
        $url = WEB_SERVICE_DOMAIN . WEB_SERVICES_REST;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        return $response;
    } catch (Exception $e) {
        throw $e;
    } finally {
        curl_close($curl);
    }
}
