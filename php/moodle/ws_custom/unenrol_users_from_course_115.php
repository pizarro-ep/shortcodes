<?php
// Constantes del servicio web
define('WEB_SERVICE_TOKEN', '80de18c8874540b61e8849a5f0f1841c'); //     |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('WEB_SERVICE_DOMAIN', 'http://172.22.9.249/moodle'); //          |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('WEB_SERVICES_REST', '/webservice/rest/server.php');
define('WEB_SERVICES_REST_FORMAT', 'json');

// Funciones a utilizar
define('FN_GET_USERS_ENROLMENT_COURSES', 'local_wsc_get_user_enrol_course');
define('FN_CORE_USER_UPDATE_USERS', 'core_user_update_users');

// Variables para la desmatriculación
define('UNENROL_COURSE_ID', 4); // ID del curso                         |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('UNENROL_USER_TYPE_STUDENT_ID', 5); // ID del tipo de usuario estudiante
define('ENROL_DURATION_IN_DAYS', 0); //                                 |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('ENROL_EXTRA_DURATION_IN_DAYS', 0); //                           |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|
define('DAY_IN_SECONDS', 60 * 60 * 24);
define("USER_CUSTOM_FIELD_SHORTNAME", "region"); // shortname del campo personalizado
define("USER_CUSTOM_FIELD_VALUE", ""); // Valor del campo personalizado, vacío para que se elimine del cohorte
define("LIMIT_PER_REQUEST", 100); //                                    |--[CAMBIAR DE ACUERDO A LAS NECESIDADES]--|


try {
    $response = get_user_enrol();
    $users = json_decode($response);

    $chunks = array_chunk($users, LIMIT_PER_REQUEST);
    $custom_fields = [['type' => USER_CUSTOM_FIELD_SHORTNAME, 'value' => USER_CUSTOM_FIELD_VALUE]];
    foreach ($chunks as $chunk) {
        $user_data = [];
        foreach ($chunk as $user) {
            if ($user->enroltime != null) {
                $days_since_enrolment = get_enrol_time($user->enroltime);

                // 1. Está matriculado mas de la duración de matricula + los dias extra
                // 2. Está matriculado mas de la duración de matricula y el curso ha sido completado
                if (
                    $days_since_enrolment >= (ENROL_DURATION_IN_DAYS + ENROL_EXTRA_DURATION_IN_DAYS)
                    || ($days_since_enrolment >= ENROL_DURATION_IN_DAYS && $user->completed)
                ) {
                    $user_data[] = ['id' => $user->id, 'customfields' => $custom_fields];
                }
            }
            echo json_encode($user) . "\n";
        }
        if (!empty($user_data)) {
            $result = update_user_profile($user_data);
            echo $result;
        }
    }
    echo "Proceso completado con éxito.\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

function get_enrol_time(int $enrol_time)
{
    return (time() - $enrol_time) / DAY_IN_SECONDS; // Tiempo transcurrido desde la matricula
}

function get_user_enrol(): bool|string
{
    try { 
        $params = common_params();
        $params['wsfunction'] = FN_GET_USERS_ENROLMENT_COURSES;
        $params['courseid'] = UNENROL_COURSE_ID;
        $params['roleid'] = UNENROL_USER_TYPE_STUDENT_ID;
        return fn_curl($params);
    } catch (Exception $e) {
        throw $e;
    }
}

function update_user_profile(array $users): bool|string
{
    try {
        $params = common_params();
        $params['wsfunction'] = FN_CORE_USER_UPDATE_USERS;
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

function fn_curl(array $params): bool|string
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
