<?php
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/enrol/manual/locallib.php');
require_once($CFG->dirroot . '/cohort/lib.php'); // Para trabajar con cohortes
require_once($CFG->dirroot . '/user/lib.php'); // Para trabajar con usuarios
require_once($CFG->libdir . '/completionlib.php'); // Para trabajar con completación del curso
require_once($CFG->dirroot . '/lib/enrollib.php'); // Para trabajar con matrículas

define('UNENROL_COURSE_ID', 4); // ID del curso 
define('UNENROL_COURSE_NAME', 'Curso 1'); // Nmbre del curso usado en el cohorte
define('USER_TYPE_STUDENT_ID', 5); // ID del tipo de usuario estudiante
define('ENROL_DURATION_IN_DAYS', 7);
define('ENROL_EXTRA_DURATION_IN_DAYS', 2);
define('DAY_IN_SECONDS', 60 * 60 * 24);

try {
    // Obtener instancias de matrícula en el curso
    $instances = enrol_get_instances(UNENROL_COURSE_ID, true);

    $context = context_course::instance(UNENROL_COURSE_ID);

    // Obtiene todos los usuarios matriculados (estudiantes)
    $users = get_enrolled_users_to_course(UNENROL_COURSE_ID, USER_TYPE_STUDENT_ID); 

    foreach ($users as $user) {
        if (!isset($user->timecreated))
            continue; // Saltar usuarios sin fecha de matriculación válida

        $userid = $user->id;
        $days_since_enrolment = (time() - $user->enrol_time) / DAY_IN_SECONDS; // Tiempo transcurrido desde la matricula
        $oldregion = get_user_region($user->id);

        if ($days_since_enrolment >= (ENROL_DURATION_IN_DAYS + ENROL_EXTRA_DURATION_IN_DAYS)) { // Está matriculado más de la duaracion de matricula + los días extra 
            unenrol_user_from_instances($instances, $user);
            change_region_field($user, UNENROL_COURSE_NAME, $oldregion);
            /*if (user_is_enrol_in_other_courses($userid, UNENROL_COURSE_ID)) { // Verificar si está matriculado en otros cursos
                unenrol_user_from_instances($instances, $user); // Desmatricular de todas las instancias posibles del curso
                change_region_field($user, UNENROL_COURSE_NAME, $oldregion); // Cambiar campo 'region' a valor vacío
            } else { // Si no esta matriculado en otros cursos eliminar al usuario
                delete_user_from_database($userid);
            }*/
        } elseif ($days_since_enrolment >= ENROL_DURATION_IN_DAYS && user_completion_course($user, UNENROL_COURSE_ID)) { // Está matriculado más de la duración de matricula
            unenrol_user_from_instances($instances, $user);
            change_region_field($user, UNENROL_COURSE_NAME, $oldregion);
            
            /*if (user_is_enrol_in_other_courses($userid, UNENROL_COURSE_ID)) {
                unenrol_user_from_instances($instances, $user);
            } else {
                delete_user_from_database($userid);
            }*/
        }
    }
    echo "Proceso completado con éxito.\n";
} catch (\Throwable $th) {
    echo "Ocurrió un error al ejecutar el proceso: " . $th->getMessage() . "\n" . $th->getTraceAsString();
}

function get_enrolled_users_to_course($courseid, $studentroleid) {
    global $DB;

    $sql = "
        SELECT u.*, ue.timecreated AS enrol_time
        FROM mdl_user u
        JOIN mdl_user_enrolments ue ON ue.userid = u.id
        JOIN mdl_enrol e ON e.id = ue.enrolid
        JOIN mdl_role_assignments ra ON ra.userid = u.id
        JOIN mdl_context c ON c.id = ra.contextid
        WHERE e.courseid = :courseid1
          AND ra.roleid = :roleid
          AND c.contextlevel = 50
          AND c.instanceid = :courseid2
    ";

    $params = [
        'courseid1' => $courseid,
        'courseid2' => $courseid,
        'roleid' => $studentroleid
    ];

    return $DB->get_records_sql($sql, $params);
}


function get_user_region($userid): string|null
{
    global $DB;
    // Obtener el ID del campo personalizado 'region'
    $fieldid = get_region_fieldid();
    if (!$fieldid)
        return null;

    // Obtener el valor del campo 'region' para el usuario
    $data = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid], '*', IGNORE_MISSING);

    return $data ? trim($data->data) : null;
}

function get_region_fieldid()
{
    global $DB;
    $field = $DB->get_record('user_info_field', ['shortname' => 'region'], '*', IGNORE_MISSING);
    return $field ? $field->id : 0;
}


function change_region_field($user, $course_name, $oldregion)
{
    global $DB;

    // Cambiar campo 'region' a valor vacío
    $DB->set_field('user_info_data', 'data', '', ['userid' => $user->id, 'fieldid' => get_region_fieldid()]);
    echo "Campo 'region' limpiado para el usuario {$user->username}\n";

    // Eliminar del cohorte correspondiente
    if ($oldregion) {
        $cohortname = "$course_name - " . strtoupper($oldregion);
        $cohort = $DB->get_record('cohort', ['name' => $cohortname], '*', IGNORE_MISSING);
        if ($cohort) {
            cohort_remove_member($cohort->id, $user->id);
            echo "Removido del cohorte: {$cohortname}\n";
        } else {
            echo "Cohorte no encontrado: {$cohortname}\n";
        }
    }
}

function user_is_enrol_in_other_courses($userid, $courseid): bool
{
    global $DB;
    // Verificar si el usuario está matriculado en otros cursos
    $other_courses = $DB->get_records_sql("
            SELECT e.courseid
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE ue.userid = :userid AND e.courseid != :courseid
        ", ['userid' => $userid, 'courseid' => $courseid]);
    return !empty($other_courses);
}

function unenrol_user_from_instances($instances, $user)
{
    global $DB;
    // Desmatricular de todas las instancias posibles
    foreach ($instances as $instance) {
        $plugin = enrol_get_plugin($instance->enrol);
        if ($plugin && $DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
            $plugin->unenrol_user($instance, $user->id);
            echo "Desmatriculado de instancia '{$instance->enrol}': {$user->username}\n";
        }
    }
}

function user_completion_course($user, $courseid): bool
{
    global $DB;
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $completion = new completion_info($course);

    if ($completion->is_enabled()) {
        $is_complete = $completion->is_course_complete($user->id);
        return $is_complete;
    } else {
        return false;
    }
}

function delete_user_from_database($userid)
{
    global $DB;
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    delete_user($user);// Eliminar al usuario del sistema
}

?>