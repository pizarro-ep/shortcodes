<?php
class block_bot extends block_base {
    public function init() {
        $this->title = 'Chatbot';
    }

    function has_config() {
        return true;
    }

    public function get_content() {
        global $PAGE;
        
        if ($this->content !== null) {
            return $this->content;
        }

        $this->page->requires->css('/blocks/bot/css/styles.css');

        $this->content = new stdClass();
        ob_start();
        $this->display_bot();  // Método que renderiza el chatbot en el bloque;  
        $this->content->text = ob_get_clean();      

        $this->page->requires->js('/blocks/bot/js/main.js');

        return $this->content;
    }

    public function display_bot(){
        $renderer = $this->page->get_renderer('block_bot');

        echo $renderer->render_bot();
    }

    
    private function store_bot_chat($message, $isBot)
    {
        global $DB;
        global $USER;

        $record = new stdClass();
        $record->message = $message;
        $record->isbot = $isBot;
        $record->timecreated = time();

        $idinsert = $DB->insert_record('bot_chat_messages', $record);

        $recordchat = new stdClass();
        $recordchat->userid = $USER->id;
        $recordchat->messageid = $idinsert;

        $DB->insert_record('bot_chat', $recordchat);
    }
    
}
