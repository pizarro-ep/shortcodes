<?php

class block_aiquestiongen_renderer extends plugin_renderer_base {
    
    // Método de ejemplo para renderizar una tabla con participantes
    public function render_aiquestiongen() {
        $output = html_writer::start_tag('form', ['method' => 'POST']);
        $output .= html_writer::tag('label', "Instrucciones para la pregunta", ['for' => 'propt']);
        $output .= html_writer::start_tag('div', ['class' => 'aiqg-container']);
        $output .= $this->render_textarea('prompt', '', "Escriba las instrucciones");
        $output .= $this->render_button('Enviar', 'fa-solid fa-square-arrow-up-right');
        $output .= html_writer::end_tag('div');     
        $output .= html_writer::tag('div', "", ['id' => 'output']);
        $output .= html_writer::end_tag('form');
        return $output;
    }

    private function render_button($text, $icon): string {
        return html_writer::tag('button', html_writer::tag('i', '', ['class' => "$icon mr-2"]) . $text, ['type' => 'button', 'id' => 'send', 'class' => 'aiqg-button']);
    }

    private function render_textarea($name, $text, $placeholder): string {
        return html_writer::tag('textarea', $text, ['name' => $name, 'class' => 'aiqg-textarea', 'placeholder' => $placeholder, 'rows' => '1']);
    }

    public function render_alert($message, $type = 'success') {
        return html_writer::tag('div', html_writer::tag('button', html_writer::tag('span', '&times;', ['aria-hidden' => 'true']), ['type' => 'button', 'class' => 'close ms-2', 'data-dismiss' => 'alert']) . $message, ['class' => "alert alert-$type"]);
    }
}

