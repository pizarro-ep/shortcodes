<?php

namespace system\Core;

use mysqli;

/**
 * Clase DataBase
 * 
 * Esta clase maneja las operaciones relacionadas con la base de datos.
 * 
 * @package Cuestionary
 * @subpackage Core
 * @category Database
 * @author Zero Ep
 * @version 1.0
 */

class DataBase
{
    private static $instance = null; // Almacena la única instancia de la clase
    private $connection; // Conexión activa

    /**
     * Constructor privado para evitar la creación de instancias de esta clase.
     */
    private function __construct()
    {
        // Iniciar la sesión si aún no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        try {
            // Cargar las variables de entorno
            $configPath = __DIR__ . '/../config.php';
            if (!file_exists($configPath)) {
                die('Archivo de configuración no encontrado.');
            }
            require_once $configPath;

            // Crear conexión
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

            // Verificar la conexión
            if ($this->connection->connect_error) {
                logError("Error de conexión a la base de datos " . $this->connection->connect_error);
                throw new \Exception("Error de conexión a la base de datos: " . $this->connection->connect_error);
            }
        } catch (\Throwable $th) {
            logError($th->getMessage());
            $_SESSION['message'] = "Error en la conexión a la base de datos.";
            $this->closeConnection();
            header("Location: " . BASE_URL . "/error/internal");
            exit;
        }
    }

    /**
     * Método privado para evitar la clonación de instancias de esta clase.
     * Este método está vacío intencionalmente para prevenir la creación de clones.
     */
    private function __clone()
    {
        // Vacío para evitar clones
    }
    public function __destruct()
    {
        $this->closeConnection(); // Cierra automáticamente al final del script
    }

    /**
     * Obtiene la instancia única de la clase DataBase.
     *
     * Este método implementa el patrón Singleton para asegurar que solo exista
     * una instancia de la clase DataBase. Si la instancia aún no ha sido creada,
     * se crea una nueva instancia. Si ya existe una instancia, se devuelve la
     * instancia existente.
     *
     * @return self La instancia única de la clase DataBase.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexión a la base de datos.
     *
     * @return mysqli La conexión a la base de datos.
     */
    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    /**
     * Cierra la conexión a la base de datos si está abierta.
     *
     * Este método verifica si la conexión a la base de datos no es nula.
     * Si la conexión está abierta, la cierra y establece la propiedad 
     * $this->connection a null.
     *
     * @return void
     */
    public function closeConnection(): void
    {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}
