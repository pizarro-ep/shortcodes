<?php

namespace system\Core;

/**
 * Clase Controller
 *
 * Esta clase proporciona métodos para manejar la lógica del controlador en la aplicación.
 *
 * Métodos:
 * - render(string $view, array $data = []): Renderiza una vista y pasa los datos proporcionados a la misma.
 */
class Controller
{
    /**
     * Renderiza una vista y pasa los datos proporcionados a la misma.
     *
     * @param string $view El nombre de la vista a renderizar.
     * @param array $data (Opcional) Un arreglo asociativo de datos a pasar a la vista.
     *
     * @return void
     */
    public function render($view, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . '/../../app/Views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            logError("La vista '{$view}' no se encontró en '{$viewPath}'");
            die("La vista '{$view}' no se encontró en '{$viewPath}'");
        }
    }
    
}
