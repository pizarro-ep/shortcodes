<?php

namespace system\Core;

/**
 * Clase Autoloader
 *
 * Esta clase proporciona un método estático para registrar una función de autoload
 * que carga clases automáticamente basándose en su namespace.
 *
 * Métodos:
 * - register(): Registra la función de autoload que convierte el namespace de la clase
 *   en una ruta de archivo y verifica si el archivo existe en el directorio de la aplicación
 *   o en el directorio de librerías descargadas.
 */
class Autoloader
{
    /**
     * Registra una función de autoload para cargar clases automáticamente.
     *
     * La función de autoload convierte el namespace de la clase en una ruta de archivo
     * y verifica si el archivo existe en el directorio de la aplicación o en el
     * directorio de librerías descargadas. Si el archivo no se encuentra, muestra un error.
     *
     * @return void
     */
    private static $loadedClasses = [];

    public static function register()
    {
        spl_autoload_register(function ($class) {
            if (isset(self::$loadedClasses[$class])) {
                return; // Ya cargado
            }
    
            $file = __DIR__ . '/../../' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                self::$loadedClasses[$class] = $file;
                return;
            }
    
            $libFile = __DIR__ . '/../../libs/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($libFile)) {
                require_once $libFile;
                self::$loadedClasses[$class] = $libFile;
                return;
            }
    
            throw new \Exception("Clase {$class} no encontrada.");
        });
    }
    
}
