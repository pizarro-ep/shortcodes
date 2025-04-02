<?php
namespace block_whatsapp\task;

defined('MOODLE_INTERNAL') || die();

class process_scheduled_messages extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('processscheduledmessages', 'block_whatsapp');
    }

    public function execute()
    {
        // Enviar mensaje
        $this->process_scheduled_messages();
    }

    // Función para procesar mensajes programados
    private function process_scheduled_messages()
    {
        global $DB;

        $now = time();  // Obtener fecha y hora actual
        // Obtener mensajes pendientes para ahora
        $messages = $DB->get_records_select('whatsapp_scheduled_messages', 'send_time <= ?', array($now));

        foreach ($messages as $msg) {
            // Obtener participantes del curso
            $participants = $this->get_course_participants($msg->courseid);
            foreach ($participants as $participant) {
                $phone_number = $participant->phone2;   // Obtener número de telefono
                if (!empty($phone_number)) {
                    // Enviar mensaje programado
                    //send_whatsapp_message("+51" . $phone_number, $msg->message);
                    $isSend = send_whatsapp_notification("51" . $phone_number, $participant->firstname . ' ' . $participant->lastname, "SV", "31 de octubre", "Gracias!!");
                    print $isSend;
                }
            }
            // Eliminar el mensaje de la base de datos después de enviarlo
            $DB->delete_records('whatsapp_scheduled_messages', array('id' => $msg->id));
        }
    }

    // Función para obtener los estudiantes del curso
    private function get_course_participants($courseid)
    {
        global $DB;
        // Crear consulta
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.phone2
            FROM {user} u
            JOIN {user_enrolments} ue ON u.id = ue.userid
            JOIN {enrol} e ON ue.enrolid = e.id
            WHERE e.courseid = :courseid";

        // retornar los estudiantes del curso
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }
}

function send_whatsapp_notification($to, $firstname, $course, $deadline, $aditional_info)
{
    // Aquí colocamos el código de envío de notificaciones usando la API de WhatsApp

    //$accessToken = 'EAAc4ltHYgxYBOZBYMuXBKomIfAOaNudQJKMvvQnMMSxkh4swuFigjaPD3RTSZApZCOQHSHr08QsvTkrIcsZAapkCYW0CwZAjkNMZAV5nUqqZC5jVqb1u7X4J7NQat7Sixcs3NIBZBZAVwZC1wgPgKCLVJj3ZC5zlYFPWtY8wUrXwNsDJZCw7YZCIxRyxYExPCHI1ocKicvOIjYeKDVpCZCrh5Er4Fn19LJCZA3JqKIm7j4XpfGyMC8ZD';
    //$phoneNumberId = '435195309681204';
    $accessToken = get_config('block_whatsapp', 'apikey');
    $phoneNumberId = get_config('block_whatsapp', 'phonenumberid');

    $templateName = 'notification_course';
    $url = "https://graph.facebook.com/v20.0/$phoneNumberId/messages";

    // Datos dinámicos para la plantilla
    $data = [
        "messaging_product" => "whatsapp",
        "to" => $to,
        "type" => "template",
        "template" => [
            "name" => $templateName,
            "language" => ["code" => "es"],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [
                        ["type" => "text", "text" => $firstname],
                        ["type" => "text", "text" => $course],
                        ["type" => "text", "text" => $deadline],
                        ["type" => "text", "text" => $aditional_info]
                    ]
                ]
            ]
        ]
    ];

    // Enviar la solicitud a la API de WhatsApp
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Manejo de la respuesta
    return "Enviado a {$firstname}: " . json_encode($response);
}
