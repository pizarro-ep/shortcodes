<?php

class block_whatsapp_renderer extends plugin_renderer_base {
    
    // Método de ejemplo para renderizar una tabla con participantes
    public function render_form() {
        $output = html_writer::start_tag('form', ['method' => 'POST']);
        $output .= html_writer::start_tag('div', ['class' => 'mb-3']);
        $output .= html_writer::tag('textarea', '', ['type' => 'text', 'name' => 'message', 'class' => 'form-control', 'placeholder' => 'Escriba el mensaje', 'rows' => '2']);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', ['class' => 'mb-3']);
        $output .= html_writer::tag('label', "Fecha y hora de envío", ['for' => 'date']);
        $output .= html_writer::empty_tag('input', ['type' => 'datetime-local', 'name' => 'send_time', 'class' => 'form-control', 'required']);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', ['class' => 'd-grid']);
        $output .= html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa-brands fa-whatsapp mr-2']) . "Programar mensaje", ['type' => 'submit', 'name' => 'send', 'class' => 'btn btn-success w-100']);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');
        return $output;
    }

    // Otro método para renderizar una alerta personalizada
    public function render_alert($message, $type = 'success') {
        return html_writer::tag('div', html_writer::tag('button', html_writer::tag('span', '&times;', ['aria-hidden' => 'true']), ['type' => 'button', 'class' => 'close ms-2', 'data-dismiss' => 'alert']) . $message, ['class' => "alert alert-$type"]);
    }

    // Metodo para mostrar la tabla de mensajes programados
    public function render_list_messages($courseid){
        global $DB;
        $messages = $DB->get_records_select('whatsapp_scheduled_messages', 'courseid = ?', [$courseid]);
        $list = html_writer::start_tag('ul', ['class' =>'list-group mt-3']);
        foreach ($messages as $msg) {
            $timestamp = date('Y-m-d', $msg->send_time);
            $list .= html_writer::tag('li', "<b>$timestamp</b><br> $msg->message", ['class' => 'list-group-item']);            
        }
        $list .= html_writer::end_tag('ul');
        return $list;
    }

    public function delete_list_message($id){
        global $DB;
        $DB->delete_records('whatsapp_scheduled_messages', ['id' => $id]);
    }
}

