<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');

// Verifica que el usuario tenga permisos
//require_login();

try {
    global $USER;

    $question = [
        'text' => '¿Cuál es la capital de Francia?',
        'options' => ['Berlín', 'Madrid', 'París'],
        'correct' => 'París'
    ];
    
    // Crear objeto de pregunta
    $qdata = new stdClass();
    $qdata->category = 1; // ID de categoría válida
    $qdata->qtype = 'multichoice';
    $qdata->name = (object)['text' => 'Pregunta generada'];
    $qdata->questiontext = (object)['text' => $question['text'], 'format' => FORMAT_HTML];
    $qdata->generalfeedback = (object)['text' => '', 'format' => FORMAT_HTML];
    $qdata->defaultmark = 1;
    $qdata->penalty = 0.1;
    $qdata->hidden = 0;
    
    // Configuración tipo multichoice
    $qdata->single = 1;
    $qdata->shuffleanswers = 1;
    $qdata->answernumbering = 'abc';
    $qdata->correctfeedback = ['text' => '', 'format' => FORMAT_HTML];
    $qdata->partiallycorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
    $qdata->incorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
    $qdata->id = null;
    $qdata->contextid = context_system::instance()->id;
    
    // Respuestas
    $qdata->answers = [];
    foreach ($question['options'] as $option) {
        $answer = new stdClass();
        $answer->answer = $option;
        $answer->answerformat = FORMAT_HTML;
        $answer->fraction = ($option === $question['correct']) ? 1.0 : 0.0;
        $answer->feedback = '';
        $answer->feedbackformat = FORMAT_HTML;
        $qdata->answers[] = $answer;
    }
    
    // Guardar pregunta
    require_once($CFG->dirroot . '/question/editlib.php');
    require_once($CFG->dirroot . '/question/format.php');
    
    $formdata = clone $qdata;
    
    $qtype = question_bank::get_qtype('multichoice');
    $questionid = $qtype->save_question($qdata, $formdata);
    
    echo "Pregunta guardada con ID: $questionid";
    
} catch (Exception $e) {
    echo $e->getMessage();
}