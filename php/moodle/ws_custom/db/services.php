<?php
$functions = [
    'local_wsc_get_user_enrol_course' => [
        'classname'   => 'local_ws_custom\local_custom_ws_uce',
        'methodname'  => 'get_user_enrol_course',
        'classpath'   => '',  
        'description' => 'Devuelve usuarios con su fecha de matrícula en un curso y su condición de completado',
        'type'        => 'read',
        'capabilities' => 'moodle/user:viewalldetails',
        'ajax'        => false,
    ],
    'local_wsc_get_users_courses_unenrollment' => [
        'classname'   => 'local_ws_custom\local_custom_ws_ucu',
        'methodname'  => 'get_users_courses_unenrollment',
        'classpath'   => '',
        'description' => 'Obtiene los usuarios no matriculados en ningún curso',
        'type'        => 'read',
        'capabilities' => 'moodle/user:viewalldetails',
        'ajax'        => false,
    ],
    'local_wsc_get_users_inactive' => [
        'classname' => 'local_ws_custom\local_custom_ws_gu',
        'methodname' => 'get_users_inactive',
        'classpath' => '',
        'description' => 'Otiene los usuarios inactivos por n días',
        'type' => 'read',
        'capabilities' => 'moodle/user:viewalldetails',
        'ajax' => false,
    ]
];

$services = [
    'Servicio de fecha de matrícula de usuarios' => [
        'functions' => ['local_wsc_get_user_enrol_course'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
    'Servicio de cursos matriculados del usuario' => [
        'functions' => ['local_wsc_get_users_courses_unenrollment'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
    'Servicio de usuarios inactivos' => [
        'functions' => ['local_wsc_get_users_inactive'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ]
];
