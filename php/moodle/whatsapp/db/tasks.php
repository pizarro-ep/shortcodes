<?php
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'block_whatsapp\task\process_scheduled_messages',
        'blocking' => 0,
        'minute' => '*/1', // Ejecutar cada minuto para pruebas
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'capability' => 'moodle/site:config',
    ),
);
