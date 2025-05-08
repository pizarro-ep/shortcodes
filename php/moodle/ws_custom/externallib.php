<?php
namespace local_ws_custom;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/completion/completion_completion.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use completion_info;

class local_custom_ws_uce extends external_api
{

    public static function get_user_enrol_course_parameters()
    {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID del curso'),
            'roleid' => new external_value(PARAM_INT, 'ID del rol')
        ]);
    }

    public static function get_user_enrol_course($courseid, $roleid)
    {
        global $DB;

        self::validate_parameters(self::get_user_enrol_course_parameters(), ['courseid' => $courseid, 'roleid' => $roleid]);

        $context = \context_course::instance($courseid);
        self::validate_context($context);

        $users = [];
        $sql = "SELECT u.id, u.firstname, u.lastname, ue.timecreated AS enroltime
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {role_assignments} ra ON ra.userid = u.id
                JOIN {context} c ON c.id = ra.contextid
                WHERE e.courseid = :courseid
                AND c.contextlevel = 50
                AND c.instanceid = :courseid2
                AND ra.roleid = :roleid";
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid, "courseid2" => $courseid, "roleid" => $roleid]);

        $completion = new completion_info($DB->get_record('course', ['id' => $courseid]));

        foreach ($records as $user) {
            $users[] = [
                'id' => $user->id,
                'fullname' => $user->firstname . ' ' . $user->lastname,
                'enroltime' => $user->enroltime,
                'completed' => $completion->is_course_complete($user->id) ? true : false
            ];
        }

        return $users;
    }

    public static function get_user_enrol_course_returns()
    {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'ID del usuario'),
                'fullname' => new external_value(PARAM_TEXT, 'Nombre completo'),
                'enroltime' => new external_value(PARAM_TEXT, 'Fecha de matrícula'),
                'completed' => new external_value(PARAM_BOOL, '¿Completó el curso?'),
            ])
        );
    }
}


class local_custom_ws_ucu extends external_api
{

    public static function get_users_courses_unenrollment_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function get_users_courses_unenrollment()
    {
        global $DB;

        $sql = "SELECT u.id as userid, u.firstname as firstname, u.lastname as lastname,
                u.lastlogin as lastlogin, u.lastaccess as lastaccess 
                FROM {user} u
                LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                LEFT JOIN {role} r ON r.id = ra.roleid
                LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                LEFT JOIN (
                    SELECT ue.userid
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid
                    WHERE e.status = 0
                ) enrolados ON enrolados.userid = u.id
                WHERE (r.shortname = 'student' OR ra.id IS NULL)
                AND enrolados.userid IS NULL
                AND u.deleted = 0
                AND u.id != 1    -- Excluir el usuario invitado
                AND u.email != 'root@localhost' -- Excluir el root@localhost
                GROUP BY u.id, u.firstname, u.lastname, u.lastlogin, u.lastaccess;";
        $results = $DB->get_records_sql($sql);

        $data = [];
        foreach ($results as $record) {
            $data[] = [
                'userid' => $record->userid,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'lastlogin' => $record->lastlogin,
                'lastaccess' => $record->lastaccess
            ];
        }

        return $data;
    }

    public static function get_users_courses_unenrollment_returns()
    {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'ID del usuario'),
                'firstname' => new external_value(PARAM_TEXT, 'Nombre del usuario'),
                'lastname' => new external_value(PARAM_TEXT, 'Apellido del usuario'),
                'lastlogin' => new external_value(PARAM_INT, 'Último inicio de sesión'),
                'lastaccess' => new external_value(PARAM_INT, 'Última visita o actividad'),
            ])
        );
    }
}

class local_custom_ws_gu extends external_api
{
    public static function get_users_inactive_parameters()
    {
        return new external_function_parameters([
            'days' => new external_value(PARAM_INT, 'Días de inactividad')
        ]);
    }

    public static function get_users_inactive(int $days = 365)
    {
        global $DB;

        $inactive_time = time() - ($days * 60 * 60 * 24);

        $sql = "SELECT u.id as userid, u.firstname as firstname, u.lastname as lastname,
                u.lastlogin as lastlogin, u.lastaccess as lastaccess
                FROM {user} u
                WHERE u.lastaccess <= :inactive_time
                AND u.deleted = 0
                AND u.id != 1    -- Excluir el usuario invitado
                AND u.email != 'root@localhost' -- Excluir el root@localhost
                GROUP BY u.id, u.firstname, u.lastname, u.lastlogin, u.lastaccess;"; 

        $params = ['inactive_time' => $inactive_time];
        $results = $DB->get_records_sql($sql, $params);

        $data = [];
        foreach ($results as $record) {
            $data[] = [
                'userid' => $record->userid,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'lastlogin' => $record->lastlogin,
                'lastaccess' => $record->lastaccess
            ];
        }
        return $data;
    }

    public static function get_users_inactive_returns()
    {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'ID del usuario'),
                'firstname' => new external_value(PARAM_TEXT, 'Nombre del usuario'),
                'lastname' => new external_value(PARAM_TEXT, 'Apellido dle usuario'),
                'lastlogin' => new external_value(PARAM_INT, 'Último inicio de sesión'),
                'lastaccess' => new external_value(PARAM_INT, 'Última visita o actividad')
            ])
        );
    }
}
