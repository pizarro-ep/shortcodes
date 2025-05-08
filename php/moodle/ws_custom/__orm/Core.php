<?php
require_once __DIR__ . '/QueryBuilder.php';
abstract class Core
{
    protected static string $table;
    protected static array $fillable = [];
    private static ?int $limit = null;
    private static ?int $offset = null;
    private static ?string $orderby;
    protected int $id;

    protected function getTable(): string
    {
        return static::$table;
    }

    public static function select(array $fields = []): ISelect
    {
        return (new QueryBuilder())->forSelect(static::$table)->fillable(static::$fillable)->select($fields);
    }

    public static function insert(array $fields = []): IInsert
    {
        return (new QueryBuilder())->forInsert(static::$table)->fillable(static::$fillable)->values($fields);
    }

    public static function update(array $fields = []): IUpdate
    {
        return (new QueryBuilder())->forUpdate(static::$table)->fillable(static::$fillable)->set($fields);
    }

    public static function delete(): IDelete
    {
        return (new QueryBuilder())->forDelete(static::$table);
    }

    public static function find(int $id): ?static
    {
        try {
            $query = self::select()->where(['id' => $id])->take(1)->skip(0);
            $data = $query->getResult();
            return $data ? self::mapToObject($data) : null;
        } catch (PDOException $th) {
            throw $th;
        }
    }

    public static function findAll(): ?array
    {
        try {
            $query = self::select()->take(self::$limit)->skip(self::$offset);
            $data = $query->getResults();
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
            $this -> validate();
            $values = $this->getFillableFields();

            if (isset($this->id) && !empty($this->id)) { // Actualizar
                return self::update()->set($values)->where(['id' => $this->id])->execute();

            } else { // Insertar
                $result = self::insert()->values($values)->execute();
                $this->id = $result ?? 0;
                return $result;
            }
        } catch (PDOException $e) {
            //Database::logError("Save error in " . static::$table . ": " . $e->getMessage());
            return Database::getFriendlyMessage($e); 
        } catch(InvalidArgumentException $e) {
            echo "Datos inválidos: " . $e->getMessage();
            return false;
        }
    }

    public function remove(): bool
    {
        try {
            if (!isset($this->id))
                return false;
            return self::delete()->where(['id' => $this->id])->execute();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public static function exists($id): bool
    {
        try {
            if (!isset($id))
                return false;
            return self::select()->where(['id' => $id])->count() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    protected function validate(): void
    {
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