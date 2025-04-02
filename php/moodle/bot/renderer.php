<?php

class block_bot_renderer extends plugin_renderer_base
{
    // Método de ejemplo para renderizar una tabla con participantes
    public function render_bot()
    {
        global $OUTPUT;

        // Obtener el nombre del bot
        $bot_name = get_config('block_bot', 'botname');

        return '<div id="container-bot" class="container-bot hidden">
                    <div class="chat-header">
                        <img id="bot-img" src="' . $OUTPUT->image_url('bot-conversacional', 'block_bot') . '" alt="Bot...">
                        <div class="brand">
                            <h4>' . $bot_name . '</h4>
                            <small>Conversación con un asistente del aula virtual</small>
                        </div>
                        <div>
                            <button id="btn-close-chat">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="chat-box" id="chat-box">
                        <div class="chat-box-present">
                            <img src="' . $OUTPUT->image_url('bot-conversacional', 'block_bot') . '" alt="Bot...">
                            <div class="brand">
                                <h4>' . $bot_name . '</h4>
                                <small>Conversación con un asistente del aula virtual</small>
                            </div>
                        </div>
                        ' . $this->build_last_messages() . '
                    </div>
                    <div class="separator"></div>
                    <div class="container-input">
                        <input type="text" id="user-input" class="user-input" placeholder="Escribe tu mensaje...">
                        <button class="send-btn" id="send-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                <path d="M120-160v-640l760 320-760 320Zm80-120 474-200-474-200v140l240 60-240 60v140Zm0 0v-400 400Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
        
                <button id="btn-open-chat">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                        <path d="M240-400h320v-80H240v80Zm0-120h480v-80H240v80Zm0-120h480v-80H240v80ZM80-80v-720q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v480q0 33-23.5 56.5T800-240H240L80-80Zm126-240h594v-480H160v525l46-45Zm-46 0v-480 480Z"/>
                    </svg>
                </button>';
    }

    private function get_bot_chats()
    {
        global $DB;
        global $USER;
        $userid = $USER->id;
        // Crear consulta
        $sql = "SELECT b.userid, b.messageid, bm.message, bm.timecreated, bm.isbot, bm.type
            FROM {bot_chat} b
            JOIN {bot_chat_messages} bm ON b.messageid = bm.id
            WHERE b.userid = :userid";

        // retornar los estudiantes del curso
        return $DB->get_recordset_sql($sql, array('userid' => $userid));
    }

    private function build_last_messages()
    {
        $messages = $this->get_bot_chats();
        $output = '';

        foreach ($messages as $message) {
            // Asignar clase según si es bot o usuario
            $message_class = ($message->isbot == 0) ? 'bot-message' : 'user-message';
            $output .= "<div class='{$message_class}'>";
            $type_class = ($message->type == 0) ? 'message-error' : 'message';
            $output .= "<div class='{$type_class}'>" . htmlspecialchars($message->message) . "</div>";
            $output .= '</div>'; // Cerrar el div de cada mensaje
        }

        return $output;
    }

}

