<?php
class block_whatsapp extends block_base
{
    public function init()
    {
        $this->title = get_string('pluginname', 'block_whatsapp');
    }

    public function has_config() {
        return true;
    }

    public function get_content()
    {
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass;
        ob_start();
        $this->display_whatsapp();  // Llamamos a la función que interactúa con Twilio
        $this->content->text = ob_get_clean();   
        
        $this->page->requires->js_call_amd('block_whatsapp/main', 'init');

        return $this->content;
    }

    public function applicable_formats() {
        return [
            'admin' => false,
            'site-index' => false,
            'course-view' => true,
            'mod' => false,
            'my' => false,
        ];
    }

    public function display_whatsapp()
    {
        global $COURSE;
        // Instanciamos el renderer del bloque
        $renderer = $this->page->get_renderer('block_whatsapp');
        // Verificamos si se ha enviado el formulario
        if (isset($_POST['send'])) {
            $message = $_POST['message']; // Obtener mensaje del input
            $send_time = $_POST['send_time']; // Obtener la hora programada

            if (isset($message) && !empty($message) && !empty($send_time)) {
                // Convertir la fecha a formato UNIX timestamp
                $send_time_timestamp = strtotime($send_time);
                
                // Guardar en la base de datos
                $this->store_scheduled_message($COURSE->id, $message, $send_time_timestamp);

                echo $renderer->render_alert("Mensaje programado correctamente");

            } else {
                echo $renderer->render_alert("Escriba un mensaje y seleccione una hora para enviar","warning");
            }
        }

        //echo $this->build_form();
        echo $renderer->render_form();
        echo $renderer->render_list_messages($COURSE->id);
    }

    // funcion para agregar el mensaje programado a la base de datos
    private function store_scheduled_message($courseid, $message, $send_time)
    {
        global $DB;

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->message = $message;
        $record->send_time = $send_time;
        $record->timecreated = time();

        $DB->insert_record('whatsapp_scheduled_messages', $record);
    }

    
} 
