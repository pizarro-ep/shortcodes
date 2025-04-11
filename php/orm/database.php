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

abstract class Core extends QueryBuilder
{
    protected static string $table;
    protected static array $fillable = [];
    private static ?int $limit;
    private static ?int $offset;
    private static ?string $orderby;
    protected int $id;

    private static string $sql = "";

    public static function find(int $id): ?static
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return $data ? static::mapToObject($data) : null;
        } catch (PDOException $th) {
            throw $th;
        }
    }

    public static function findAll(): ?array
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT * FROM " . static::$table . self::getPager());
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $data ? array_map(fn($row) => static::mapToObject($row), $data) : null;
        } catch (PDOException $th) {
            throw $th;
        } finally {
            self::setPager();
        }
    }

    public static function search(array $args): ?array
    {
        try {
            $pdo = Database::getConnection();
            $args = static::getFillableFieldsStatic($args);
            $conditions = [];
            $params = [];

            

            foreach ($args as $col => $val) {
                $paramKey = ":$col";
                if (is_array($val)) {
                    $conditions[] = "$col REGEXP $paramKey";
                    $params[$paramKey] = implode("|", array_filter($val, fn($v) => is_string($v)));
                } elseif (is_string($val)) {
                    $conditions[] = "$col LIKE $paramKey";
                    $params[$paramKey] = "%$val%";
                }
            }

            $whereClause = count($conditions) ? 'WHERE ' . implode(" AND ", $conditions) : '';
            $sql = "SELECT * FROM " . static::$table . " $whereClause" . self::getPager();
            //self::setPager();

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $data ? array_map(fn($row) => static::mapToObject($row), $data) : null;

        } catch (Exception $e) {
            throw new Exception("No se pudo realizar la búsqueda.");
        } finally {
            self::setPager();
        }
    }

    public static function filter(array $args): ?array
    {
        try {
            $pdo = Database::getConnection();
            $args = static::getFillableFieldsStatic($args);
            $conditions = [];
            $params = [];

            foreach ($args as $col => $val) {
                if (is_array($val)) {
                    $operator = strtoupper(array_key_first($val));
                    $paramKey = ":{$col}_" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $operator));

                    switch ($operator) {
                        case 'IS':
                        case '=':
                            $conditions[] = "$col = $paramKey";
                            $params[$paramKey] = $val[$operator];
                            break;

                        case 'IS NOT':
                        case '!=':
                            $conditions[] = "$col != $paramKey";
                            $params[$paramKey] = $val[$operator];
                            break;

                        case '>':
                        case '<':
                        case '>=':
                        case '<=':
                            $conditions[] = "$col $operator $paramKey";
                            $params[$paramKey] = $val[$operator];
                            break;

                        case 'BETWEEN':
                            if (is_array($val[$operator]) && count($val[$operator]) === 2) {
                                $paramKey1 = "{$paramKey}_1";
                                $paramKey2 = "{$paramKey}_2";
                                $conditions[] = "$col BETWEEN $paramKey1 AND $paramKey2";
                                $params["$paramKey1"] = $val[$operator][0];
                                $params["$paramKey2"] = $val[$operator][1];
                            }
                            break;

                        case 'CONTAINS':
                            $conditions[] = "$col LIKE $paramKey";
                            $params[$paramKey] = '%' . $val[$operator] . '%';
                            break;

                        case 'NOT CONTAINS':
                            $conditions[] = "$col NOT LIKE $paramKey";
                            $params[$paramKey] = '%' . $val[$operator] . '%';
                            break;

                        case 'BEGINS WITH':
                            $conditions[] = "$col LIKE $paramKey";
                            $params[$paramKey] = $val[$operator] . '%';
                            break;

                        case 'ENDS WITH':
                            $conditions[] = "$col LIKE $paramKey";
                            $params[$paramKey] = '%' . $val[$operator];
                            break;

                        default:
                            // Opcional: lanzar excepción si operador no es reconocido
                            throw new Exception("Operador no soportado: $operator");
                    }
                } elseif (is_scalar($val)) {
                    $paramKey = ":$col";
                    $conditions[] = "$col = $paramKey";
                    $params[$paramKey] = $val;
                }
            }

            $whereClause = $conditions ? 'WHERE ' . implode(" AND ", $conditions) : '';
            $sql = "SELECT * FROM " . static::$table . " $whereClause" . self::getPager();
            echo $sql;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $data ? array_map(fn($row) => static::mapToObject($row), $data) : null;
        } catch (Exception $e) {
            throw new Exception("No se pudo aplicar el filtro.");
        } finally {
            self::setPager();
        }
    }

    public function save(): bool|string
    {
        try {
            $pdo = Database::getConnection();
            $fields = $this->getFillableFields();
            $columns = array_keys($fields);

            if (isset($this->id) && !empty($this->id)) {
                // Actualizar
                $fields['id'] = $this->id;
                $sets = implode(", ", array_map(fn($col) => "$col = :$col", $columns));
                $sql = "UPDATE " . static::$table . " SET $sets WHERE id = :id";
            } else {
                // Insertar
                $placeholders = implode(", ", array_map(fn($col) => ":$col", $columns));
                $sql = "INSERT INTO " . static::$table . " (" . implode(", ", $columns) . ") VALUES ($placeholders)";
            }

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($fields)) {
                if (!isset($this->id) || empty($this->id))
                    $this->id = $pdo->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            //Database::logError("Save error in " . static::$table . ": " . $e->getMessage());
            return Database::getFriendlyMessage($e);
        }
    }

    public function delete(): bool
    {
        try {
            if (!isset($this->id))
                return false;
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM " . static::$table . " WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public static function exists($id): bool
    {
        try {
            if (!isset($id))
                return false;
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public static function executeProcedure($procedureName, $params = []): ?array
    {
        try {
            $pdo = Database::getConnection();

            $params = array_filter($params, fn($val) => is_string($val));
            $params_name = implode(", ", array_map(fn($param) => ":" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $param)), array_keys($params)));

            $stmt = $pdo->prepare("CALL $procedureName($params_name)");
            foreach ($params as $param => $value) {
                $stmt->bindParam(":" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $param)), $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    protected function getFillableFields(): array
    {
        $vars = get_object_vars($this);
        return array_intersect_key($vars, array_flip(static::$fillable));
    }
    public static function getFillableFieldsStatic(array $fillable): array
    {
        return array_intersect_key($fillable, array_flip(static::$fillable));
    }

    public static function setOrderBy(?array $orderBy = null)
    {
        if ($orderBy !== null || !empty($orderBy)) {
            self::$orderby = "ORDER BY " . implode(", ", array_filter($orderBy, fn($val) => is_string($val)));
        }
        self::$orderby = "ORDER BY id ASC";
    }

    public static function getCountTotalItems()
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . static::$table);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public static function setPager(?int $limit = null, ?int $offset = null)
    {
        self::$limit = $limit;
        self::$offset = $offset;
    }

    private static function getPager()
    {
        if (isset(static::$limit)) {
            if (isset(static::$offset)) {
                return " LIMIT " . self::$limit . " OFFSET " . self::$offset;
            } else {
                return " LIMIT " . self::$limit;
            }
        }
        return "";
    }

    protected static function mapToObject(array $data): static
    {
        $object = new static();
        foreach ($data as $key => $value) {
            if (property_exists($object, $key)) {
                $type = gettype($object->$key);
                $object->$key = $value ?? ($type === 'integer' ? 0 : ($type === 'string' ? "" : null));
            }
        }
        return $object;
    }
}

abstract class QueryBuilder
{
    protected static string $table;
    private $fields = []; 
    private $where;
    private $order = [];
    private $limit;
    private $offset;

    public function select(array $fields): static
    {
        if (!empty($fields) && $fields !== null) {
            $fields = array_filter($fields, fn($val) => is_string($val));
            $this->fields = array_merge($this->fields, $fields);
        }
        return $this;
    }

    private function getSelect(): string
    {
        return !empty($this->fields) ? implode(", ", $this->fields) : "*";
    }

    public function where(array $conditions)
    {
        if (!empty($conditions)) {
            $this->where = $conditions;

        }
        return $this;
    }

    public function getWhere()
    {
        return !empty($this->where) ? "WHERE " . implode(" AND ", $this->where) : "";
    }

    public function orderBy($field, $direction = null)
    {
        $direction = $direction || !is_string($direction) ?? 'ASC';
        $direction = in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'ASC';
        $this->order[] = array($field, $direction);
        return $this;
    }

    public function getOrderBy()
    {
        $_order = "";
        foreach ($this->order as $order) {
            $_order = $order[0] . " " . $order[1];
        }

        return $_order;
    }

    public function skip($offset)
    {
        $this->offset = is_int($offset) ? $offset : null;
        return $this;
    }

    private function getSkip()
    {
        if ($this->offset !== null && $this->limit !== null) {
            return " OFFSET " . $this->offset;
        }
        return "";
    }

    public function take($limit)
    {
        $this->limit = is_int($limit) ? $limit : null;
        return $this;
    }

    private function getTake()
    {
        if ($this->limit !== null) {
            return " LIMIT " . $this->limit;
        }
        return "";
    }

    public function getResult(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($this->buildQuery());
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buildQuery()
    {
        $query = sprintf(
            "SELECT %s FROM %s%s%s%s",
            $this->getSelect(),
            static::$table,
            $this->getWhere(),
            $this->getOrderBy(),
            $this->getTake(),
            $this->getSkip()
        );

        echo $query;

        return $query;
    }
}

class ORM
{
    private $table;
    private $resultSet;

    public function for_table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function find_result_set()
    {
        $this->resultSet = new ResultSet($this->table);
        return $this->resultSet;
    }
}

class ResultSet
{
    private $table;
    private $data;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function set($column, $value)
    {
        $this->data[$column] = $value;
        return $this;
    }

    public function save()
    {
        // Aquí puedes implementar la lógica para guardar los datos en la base de datos
        // Por ejemplo, podrías utilizar una sentencia SQL para actualizar la tabla
        $sql = "UPDATE $this->table SET age = :age";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':age', $this->data['age']);
        $stmt->execute();
    }
}

class User extends Core
{
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password', 'role_id'];

    public int $id;
    public string $name;
    public string $email;
    public string $password;
    public int $role_id;
    public ?Role $role;

    function __construct()
    {
        $this->id = 0;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role_id = 0;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function getRole(): ?Role
    {
        return Role::find($this->role_id);
    }
}

class Role extends Core
{
    protected static string $table = 'roles';
    protected static array $fillable = ['role'];

    public int $id;
    public string $role;
    public ?array $users;

    function __construct()
    {
        $this->id = 0;
        $this->role = '';
    }

    public function getUsers(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $this->id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($user) {
            return User::mapToObject($user);
        }, $users);
    }
}

class Cache
{
    private $cache = [];

    function __construct()
    {
    }

    public function store($key, $data, $ttl = 3600)
    {
        $this->cache[$key] = [
            'data' => $data,
            'ttl' => time() + $ttl,
        ];
    }

    public function retrieve($key)
    {
        if (isset($this->cache[$key])) {
            $cacheItem = $this->cache[$key];
            if ($cacheItem['ttl'] > time()) {
                return $cacheItem['data'];
            } else {
                unset($this->cache[$key]);
            }
        }
        return null;
    }

    public function invalidate($key)
    {
        unset($this->cache[$key]);
    }
}


// ===================
// USO
// ===================

Database::connect('mysql:host=localhost;dbname=test', 'root', 'ZeroCorp@12');


$user = new User();
$user->select(['id', 'name', 'email']);
$user->where([])->take(1)->skip(0)->getResult();
echo json_encode($user) . "\n\n";

/*echo json_encode(Role::findAll()) . "\n\n";

foreach (User::findAll() as $user) {
    $user->role = $user->getRole();
    echo json_encode($user) . "\n\n";
}

$user = User::find(3);

echo json_encode($user->getRole()) . "\n\n";

echo "ROLES \n\n";

foreach (Role::findAll() as $role) {
    $role->users = $role->getUsers();
    echo json_encode($role) . "\n\n";
}*/


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


/** NOTA
 * Caché: Implementa un sistema de caché para almacenar los resultados de las consultas y reducir la carga en la base de datos.
 Soporte para relaciones: Agrega la capacidad de definir relaciones entre tables, como relaciones de uno a uno, uno a muchos y muchos a muchos.
 Soporte para consultas complejas: Agrega la capacidad de realizar consultas más complejas, como consultas con subconsultas, joins y agregaciones.
 Soporte para tipos de datos personalizados: Agrega la capacidad de definir tipos de datos personalizados para almacenar datos específicos.
 Soporte para migraciones: Agrega la capacidad de realizar migraciones de la base de datos, lo que te permitirá cambiar la estructura de la base de datos de manera controlada.
 * 
 */