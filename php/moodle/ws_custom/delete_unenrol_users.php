<?php
// Constantes del servicio web
define('WEB_SERVICE_TOKEN', '80de18c8874540b61e8849a5f0f1841c'); //    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('WEB_SERVICE_DOMAIN', 'http://172.22.9.249/moodle'); //    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('WEB_SERVICES_REST', '/webservice/rest/server.php');
define('WEB_SERVICES_REST_FORMAT', 'json');

// Funciones a utilizar 
define('FN_ENROL_GET_USER_COURSES_UNENROLLMENT', 'local_wsc_get_users_courses_unenrollment');
define('FN_CORE_USER_DELETE_USERS', 'core_user_delete_users');

// Variables para la desmatriculación
define('UNENROL_COURSE_ID', 4); // ID del curso     |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('UNENROL_USER_TYPE_STUDENT_ID', 5); // ID del tipo de usuario estudiante

define('DAY_IN_SECONDS', 60 * 60 * 24);
define("LIMIT_PER_REQUEST", 100); //    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|

try {
    $response = get_users_courses_unenrollment();
    $users = json_decode($response);

    $chunks = array_chunk($users, LIMIT_PER_REQUEST);
    foreach ($chunks as $chunk) {
        $user_data = [];
        foreach ($chunk as $user) {
            if ($user->userid !== null && user_lastaccess_is_more_than_n_years($user->lastaccess, 2)) {
                $user_data[] = $user->userid;
            }
        }
        if (!empty($user_data)) {
            delete_users($user_data);
        }
    }
    echo "Proceso completado con éxito.\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

function user_lastaccess_is_more_than_n_years($lastaccess, int $years)
{
    $time_before_lastaccess = (time() - $lastaccess) / DAY_IN_SECONDS;
    return $time_before_lastaccess > (365 * $years);
}

function get_users_courses_unenrollment(): bool|string
{
    try {
        $params = ['wstoken' => WEB_SERVICE_TOKEN, 'moodlewsrestformat' => WEB_SERVICES_REST_FORMAT,];
        $params['wsfunction'] = FN_ENROL_GET_USER_COURSES_UNENROLLMENT;
        return fn_curl($params);
    } catch (Exception $e) {
        throw $e;
    }
}

function delete_users(array $userids): bool|string
{
    try {
        $params = common_params();
        $params['wsfunction'] = FN_CORE_USER_DELETE_USERS;
        $params['userids'] = $userids;
        return fn_curl($params);
    } catch (Exception $e) {
        throw $e;
    }
}

function common_params()
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
