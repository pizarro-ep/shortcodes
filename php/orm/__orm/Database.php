<?php 

class Database
{
    private static ?PDO $connection = null;

    public static function connect(string $dsn, string $username, string $password): void
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO($dsn, $username, $password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                register_shutdown_function([self::class, 'close']);
            } catch (PDOException $e) {
                throw $e;
            }
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            throw new Exception("Conexión a Base de Datos no establecida.");
        }
        return self::$connection;
    }

    public static function close(): void
    {
        self::$connection = null;
    }

    public static function getFriendlyMessage(Exception $e): string
    {
        if ($e instanceof PDOException) {
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                return "Este registro ya existe o hay un conflicto de datos.";
            }
            return "Ocurrió un error con la base de datos.";
        }

        return "Ha ocurrido un error inesperado.";
    }

}