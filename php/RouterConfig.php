<?php

namespace system\Routes;

use system\Routes\Router;

/**
 * Clase RouteConfig
 * 
 * Esta clase se encarga de la configuración de las rutas del sistema.
 */
class RouteConfig
{
    /**
     * Registra una nueva ruta en el sistema.
     *
     * @param string $uri La URI de la ruta que se va a registrar.
     */
    public static function register($uri)
    {
        $router = new Router();
        date_default_timezone_set("America/Lima");
        $router->get('login', 'app\Controllers\LoginController@index');
        $router->get('', 'app\Controllers\IndexController@index', ['auth' => ['SUPERADMIN', 'ADMIN', 'ESTANDAR']]);

        // Ejecutar el enrutador
        $router->run($uri);
    }
}
