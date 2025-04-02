<?php

namespace system\Routes;

use system\Auth\Auth;

class Router
{
    private $routes = [];

    // Métodos para todos los tipos de solicitudes HTTP
    public function get($uri, $action, $options = [])
    {
        $this->addRoute('GET', "/" . PROJECT_NAME . '/' . $uri, $action, $options);
    }

    public function post($uri, $action, $options = [])
    {
        $this->addRoute('POST', "/" . PROJECT_NAME . '/' . $uri, $action, $options);
    }

    public function put($uri, $action, $options = [])
    {
        $this->addRoute('PUT', "/" . PROJECT_NAME . '/' . $uri, $action, $options);
    }

    public function delete($uri, $action, $options = [])
    {
        $this->addRoute('DELETE', "/" . PROJECT_NAME . '/' . $uri, $action, $options);
    }

    public function patch($uri, $action, $options = [])
    {
        $this->addRoute('PATCH', $uri, $action, $options);
    }

    private function addRoute($method, $uri, $action, $options)
    {
        $this->routes[] = compact('method', 'uri', 'action', 'options');
    }

    /**
     * Ejecuta el enrutador con la URI de solicitud proporcionada.
     *
     * @param string $requestUri La URI de la solicitud que se va a procesar.
     * @return void
     */
    public function run($requestUri)
    {
        foreach ($this->routes as $route) {
            $params = [];

            if ($this->match($route, $requestUri, $params)) {
                // Combina los parámetros de la URL, query params y body params
                $slug = $params['id'] ?? null;  // Obtener el slug, por ejemplo el ID de la URL
                unset($params['id']);  // Eliminar el ID de los parámetros después de extraerlo

                $queryParams = $this->getQueryParams();  // Los parámetros de la consulta (GET)
                $bodyParams = $this->getBodyParams();   // Los parámetros del cuerpo de la solicitud (POST, PUT, PATCH, DELETE)

                // Ejecutar el controlador con los parámetros adecuados
                $this->execute($route, $slug, $queryParams, $bodyParams);
                return;
            }
        }
        $this->handleNotFound();
    }

    /**
     * Compara una ruta con una URI de solicitud y extrae los parámetros si hay coincidencia.
     *
     * @param array $route La ruta que contiene el método HTTP y la URI.
     * @param string $requestUri La URI de la solicitud.
     * @param array &$params Parámetros extraídos de la URI de la solicitud.
     * @return bool Devuelve true si la ruta coincide con la URI de la solicitud, de lo contrario false.
     */
    private function match($route, $requestUri, &$params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== $route['method']) {
            return false;
        }

        // Convertir la URI de la ruta en una expresión regular
        $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['uri']) . '$#';
        if (preg_match($pattern, parse_url($requestUri, PHP_URL_PATH), $matches)) {
            // Capturar solo parámetros nombrados
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    /**
     * Obtiene los parámetros de consulta (GET) de la URL.
     *
     * @return array Un array asociativo que contiene los parámetros de consulta.
     */
    private function getQueryParams()
    {
        // Obtener parámetros de consulta (GET)
        return $_GET;
    }

    /**
     * Obtiene los parámetros del cuerpo de la solicitud (POST, PUT, PATCH, DELETE).
     *
     * Este método verifica el método de la solicitud y el tipo de contenido para determinar
     * cómo procesar los parámetros del cuerpo de la solicitud.
     *
     * @return array Los parámetros del cuerpo de la solicitud como un array asociativo.
     *               Si el contenido es JSON, se decodifica y se devuelve como un array.
     *               Si el contenido es un formulario, se devuelve el array $_POST.
     *               Si el método de la solicitud no es uno de los especificados o el tipo de contenido no es compatible, se devuelve un array vacío.
     */
    private function getBodyParams()
    {
        // Obtener parámetros del cuerpo de la solicitud (POST, PUT, PATCH)
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Si el contenido es JSON
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                return json_decode(file_get_contents("php://input"), true) ?? [];
            }
            // Si el contenido es formulario
            elseif ($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded' || strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                return $_POST;
            }
        }
        return [];
    }

    /**
     * Ejecuta la acción del controlador correspondiente a la ruta especificada.
     *
     * @param array $route Información de la ruta que incluye el controlador y método a ejecutar.
     * @param string $slug Parámetro de la URL que se pasa al método del controlador.
     * @param array $queryParams Parámetros de consulta (GET) que se pasan al método del controlador.
     * @param array $bodyParams Parámetros del cuerpo de la solicitud (POST, PUT, PATCH, DELETE) que se pasan al método del controlador.
     *
     * @throws \Throwable Si ocurre un error durante la ejecución del método del controlador.
     */
    private function execute($route, $slug, $queryParams, $bodyParams)
    {
        if (!empty($route['options']['auth'])) {
            Auth::validateRequest($route['options']['auth']);
        }

        try {
            [$controller, $method] = explode('@', $route['action']);
            $controllerInstance = new $controller();

            // Pasar los tres parámetros al controlador: slug, queryParams (GET), bodyParams (POST, PUT, PATCH, DELETE)
            $controllerInstance->$method($slug, $queryParams, $bodyParams);
        } catch (\Throwable $th) {
            logError("Ocurrió un error al ejecutar el método {$method} del controlador {$controller}: {$th->getMessage()}");
            http_response_code(500);
            Header('Location: ' . BASE_URL . '/error/internal');
            exit;
        }
    }

    /**
     * Maneja las solicitudes que no se encuentran.
     *
     * Esta función establece el código de respuesta HTTP a 404,
     * redirige al usuario a la página de error "notFound" y termina la ejecución del script.
     *
     * @return void
     */
    private function handleNotFound()
    {
        http_response_code(404);
        Header('Location: ' . BASE_URL . '/error/notFound');
        exit;
    }
}
