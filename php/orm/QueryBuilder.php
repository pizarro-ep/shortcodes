<?php
require_once __DIR__ . '/ISelect.php';
require_once __DIR__ . '/IInsert.php';
require_once __DIR__ . '/IUpdate.php';
require_once __DIR__ . '/IDelete.php';

class QueryBuilder implements ISelect, IInsert, IUpdate, IDelete
{
    private string $table;
    private array $fillable;
    private $fields = [];
    private $values = [];
    private $valueParams = [];
    private $where = [];
    private $whereParams = [];
    private $set = [];
    private $setParams = [];
    private $order = [];
    private $limit = null;
    private $offset = null;
    private $join = [];
    private $mode = "";

    const SELECT = "SELECT";
    const INSERT = "INSERT";
    const UPDATE = "UPDATE";
    const DELETE = "DELETE";

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }
    private function mode(string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    public static function forInsert(string $table): IInsert
    {
        $instance = new self();
        $instance->table($table);
        $instance->mode(self::INSERT);
        /** @var IInsert $instance */
        return $instance;
    }

    public static function forSelect(string $table): ISelect
    {
        $instance = new self();
        $instance->table($table);
        $instance->mode(self::SELECT);
        /** @var ISelect $instance */
        return $instance;
    }

    public static function forUpdate(string $table): IUpdate
    {
        $instance = new self();
        $instance->table($table);
        $instance->mode(self::UPDATE);
        /** @var IUpdate $instance */
        return $instance;
    }

    public static function forDelete(string $table): IDelete
    {
        $instance = new self();
        $instance->table($table);
        $instance->mode(self::DELETE);
        /** @var IDelete $instance */
        return $instance;
    }


    public function fillable(?array $fillable = []): static
    {
        $this->fillable = $fillable;
        return $this;
    }

    public function values(array $values): IInsert
    {
        $values = $this->filterArrayScalar($values);
        $this->values = array_merge($this->values, $values);
        $this->fields = array_keys($values);
        $this->mode = self::INSERT;
        return $this;
    }

    private function getValues(): string
    {
        $values = [];
        foreach ($this->values as $key => $value) {
            $values[] = ":{$key}";
            $this->valueParams[":{$key}"] = $value;
        }
        return !empty($values) ? implode(",", $values) : "";
    }

    private function getFields(): string
    {
        return !empty($this->fields) ? implode(",", $this->fields) : "";
    }

    /**
     * Summary of select
     * @param array $conditions
     * @return QueryBuilder
     */
    public function select(array $fields): ISelect
    {
        if (!empty($fields) && $fields !== null) {
            $fields = array_filter($fields, fn($val) => is_string($val));
            $this->fields = array_merge($this->fields, $fields);
        }
        return $this;
    }
    private function getSelect(): string
    {
        return !empty($this->fields) ? implode(",", $this->fields) : "*";
    }
    public function is(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key = :is_$key";
                $this->whereParams[":is_$key"] = $val;
            }

        } else {
            $this->where[] = "$field = :is_$field";
            $this->whereParams[":is_$field"] = $value;
        }
        return $this;
    }
    public function isNot(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key != :isnot_$key";
                $this->whereParams[":isnot_$key"] = $val;
            }
        } else {
            $this->where[] = "$field != :isnot_$field";
            $this->whereParams[":isnot_$field"] = $value;
        }
        return $this;
    }
    public function isNull(string|array $field): ISelect
    {
        $fields = is_array($field) ? $field : [$field];
        foreach ($fields as $f) {
            $this->where[] = "$f IS NULL";
        }
        return $this;
    }
    public function isNotNull(string|array $field): ISelect
    {
        $fields = is_array($field) ? $field : [$field];
        foreach ($fields as $f) {
            $this->where[] = "$f IS NOT NULL";
        }
        return $this;
    }
    public function greaterThan(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key > :gt_$key";
                $this->whereParams[":gt_$key"] = $val;
            }
        } else {
            $this->where[] = "$field > :gt_$field";
            $this->whereParams[":gt_$field"] = $value;
        }
        return $this;
    }
    public function greaterThanOrEqual(string $field, mixed $value): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key >= :gte_$key";
                $this->whereParams[":gte_$key"] = $val;
            }
        } else {
            $this->where[] = "$field >= :gte_$field";
            $this->whereParams[":gte_$field"] = $value;
        }
        return $this;
    }
    public function lessThan(string $field, mixed $value): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key < :lt_$key";
                $this->whereParams[":lt_$key"] = $val;
            }
        } else {
            $this->where[] = "$field < :lt_$field";
            $this->whereParams[":lt_$field"] = $value;
        }
        return $this;
    }
    public function lessThanOrEqual(string $field, mixed $value): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key <= :lte_$key";
                $this->whereParams[":lte_$key"] = $val;
            }
        } else {
            $this->where[] = "$field <= :lte_$field";
            $this->whereParams[":lte_$field"] = $value;
        }
        return $this;
    }
    public function isBetween(string $field, string ...$conditions): ISelect
    {
        if (count($conditions) === 2) {
            [$from, $to] = $conditions;

            $this->where[] = "{$field} BETWEEN :isbetween_{$field}_from AND :isbetween_{$field}_to";
            $this->whereParams[":isbetween_{$field}_from"] = $from;
            $this->whereParams[":isbetween_{$field}_to"] = $to;
        }
        return $this;
    }
    public function contains(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key LIKE :cs_$key";
                $this->whereParams[":cs_$key"] = "%$val%";
            }
        } else {
            $this->where[] = "$field LIKE %:cs_$field%";
            $this->whereParams[":cs_$field"] = "%$value%";
        }
        return $this;
    }
    public function notContains(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key NOT LIKE :ncs_$key";
                $this->whereParams[":ncs_$key"] = "%$val%";
            }
        } else {
            $this->where[] = "$field NOT LIKE :ncs_$field%";
            $this->whereParams[":ncs_$field"] = "%$value%";
        }
        return $this;
    }
    public function beginsWith(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key LIKE :bw_$key";
                $this->whereParams[":bw_$key"] = "$val%";
            }
        } else {
            $this->where[] = "$field LIKE :bw_$field";
            $this->whereParams[":bw_$field"] = "$value%";
        }
        return $this;
    }
    public function endsWith(string|array $field, mixed $value = null): ISelect
    {
        if (is_array($field)) {
            $conditions = $this->filterArrayScalar($field);
            foreach ($conditions as $key => $val) {
                $this->where[] = "$key LIKE :ew_$key";
                $this->whereParams[":ew_$key"] = "%$val";
            }
        } else {
            $this->where[] = "$field LIKE :ew_$field";
            $this->whereParams[":ew_$field"] = "%$value";
        }
        return $this;
    }

    public function where(string|array $field, mixed $value = null): static
    {
        $this->is($field, $value);
        return $this;
    }
    private function getWhere()
    {
        return !empty($this->where) ? " WHERE " . implode(" AND ", $this->where) : "";
    }

    /**
     * Summary of set
     * @param array $set
     * @return QueryBuilder
     */
    public function set(array $set): IUpdate
    {
        $set = $this->filterArrayScalar($set);
        $this->set = array_merge($this->set, $set);
        return $this;
    }

    private function getSets(): string
    {
        $set = [];
        foreach ($this->set as $key => $value) {
            $set[] = "$key = :set_{$key}";
            $this->setParams[":set_{$key}"] = $value;
        }
        return !empty($set) ? implode(", ", $set) : "";
    }

    public function orderBy(string $field, ?string $direction = null): static
    {
        $direction = $direction || !is_string($direction) ?? 'ASC';
        $direction = strtoupper($direction);
        $direction = in_array($direction, ['ASC', 'DESC']) ? $direction : 'ASC';
        $this->order[] = array($field, $direction);
        return $this;
    }
    public function getOrderBy(): string
    {
        if (empty($this->order))
            return "";
        $orders = array_map(fn($o) => "$o[0] $o[1]", $this->order);
        return " ORDER BY " . implode(", ", $orders);
    }

    public function skip($offset): ISelect
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

    public function take($limit): ISelect
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

    public function getResult(): array|null
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare($this->buildQuery()); 
            $stmt->execute($this->whereParams);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getResults(): array|null
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare($this->buildQuery());
            $stmt->execute($this->whereParams);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function execute(): bool|string
    {
        try {
            $pdo = Database::getConnection();
            if ($this->mode === self::INSERT) {
                $query = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->table, $this->getFields(), $this->getValues());
                $stmt = $pdo->prepare($query);
                return $stmt->execute($this->valueParams) ? $pdo->lastInsertId() : false;
            } else if ($this->mode === self::UPDATE && !empty($this->where)) {
                $query = sprintf("UPDATE %s SET %s%s", $this->table, $this->getSets(), $this->getWhere());
                $stmt = $pdo->prepare($query);
                return $stmt->execute(array_merge($this->setParams, $this->whereParams));
            } else if ($this->mode === self::DELETE && !empty($this->where)) {
                $query = sprintf("DELETE FROM %s%s", $this->table, $this->getWhere());
                $stmt = $pdo->prepare($query); 
                return $stmt->execute($this->whereParams);
            }
            return false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function count(): int
    {
        try {
            $query = sprintf("SELECT COUNT(*) FROM %s%s", $this->table, $this->getWhere());
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($this->whereParams);
            return $stmt->fetchColumn() ?? 0;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function procedure(string $procedureName, ?array $params = []): ?array
    {
        try {
            $pdo = Database::getConnection();

            $params = array_filter($params, fn($val) => is_scalar($val));
            $params_name = implode(",", array_map(fn($param) => ":" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $param)), array_keys($params)));

            $stmt = $pdo->prepare("CALL $procedureName($params_name)");
            foreach ($params as $param => $value) {
                $stmt->bindParam(":" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $param)), $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function join(string $t1, string $table, array $on){
        $on = array_filter($on, fn($val) => is_array($val) && count($val) == 2);
        $this->join["{$t1}_{$table}"] = [];
    }

    private function filterArrayScalar(array $array): array
    {
        return (!empty($array) && $array !== null) ? array_filter($array, fn($val) => is_scalar($val)) : [];
    }

    public function buildQuery()
    {
        $query = sprintf(
            "SELECT %s FROM %s%s%s%s",
            $this->getSelect(),
            $this->table,
            $this->getWhere(),
            $this->getOrderBy(),
            $this->getTake(),
            $this->getSkip()
        );
        return $query;
    }
}