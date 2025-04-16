<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Role.php';

Database::connect('mysql:host=localhost;dbname=test', 'root', 'ZeroCorp@12');

echo json_encode(Role::select()->isBetween("id", 2, 4)->getResults());

function RoleInit()
{
    $role = new Role();
    $role->role = "ADMINISTRADOR";
    $role->save();
    $role = new Role();
    $role->role = "MODERADOR";
    $role->save();
    $role = new Role();
    $role->role = "USUARIO";
    $role->save();
    $role = new Role();
    $role->role = "ANONIMO";
    $role->save();
    $role = new Role();
    $role->role = "INVITADO";
    $role->save();
}