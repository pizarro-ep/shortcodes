<?php
class block_aiquestiongen extends block_base
{
    public function init()
    {
        $this->title = get_string('pluginname', 'block_aiquestiongen');
    }

    public function has_config()
    {
        return true;
    } 

    public function get_content() {
        global $PAGE;
        
        if ($this->content !== null) {
            return $this->content;
        } 

        $this->page->requires->css('/blocks/aiquestiongen/css/styles.css');

        $this->content = new stdClass();
        ob_start();
        $this->display_aiquestiongen();  // Método que renderiza el chatbot en el bloque;  
        $this->content->text = ob_get_clean();      

        $this->page->requires->js('/blocks/aiquestiongen/js/main.js');

        return $this->content;
    } 

    public function display_aiquestiongen(){
        $renderer = $this->page->get_renderer('block_aiquestiongen');
        if (isset($_POST['send'])) {
            $prompt = $_POST['prompt']; // Obtener mensaje del input

            // AQUI EJECUTAR generate.php
            // Enviar el tema a la API de Gemini y generar el banco de preguntas
            


            echo $renderer->render_alert("Las preguntas se han generado correctamente. Se han guardado en la base de datos.","success");
        }else {
            echo $renderer->render_alert("Escriba las instrucciones","warning");
        }

        echo $renderer->render_aiquestiongen();
    }

    public function applicable_formats()
    {
        return [ 
            'course-view' => true, 
        ];
    }
}
