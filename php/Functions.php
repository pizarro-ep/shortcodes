<?php

namespace app\Utils;
// Declaramos una clase abstracta con un método estático
abstract class Functions
{
    /** 
     * Construir una consulta SQL SELECT.
     *
     * @param string $table Nombre de la tabla.
     * @param array $columns Columnas a seleccionar.
     * @param array $conditions Condiciones para la cláusula WHERE.
     * @param array $options Opciones adicionales como ORDER BY y LIMIT.
     * @param array $joins Tablas para realizar JOIN.
     * @return array Consulta SQL construida, parámetros y tipos.
     * */
    public static function buildSelectSQLQuery($table, $columns = [], $conditions = [], $options = [], $joins = [])
    {
        // Construir SELECT
        $cols = (!empty($columns)) ? implode(", ", $columns) : "*";
        $select = "SELECT $cols FROM " . self::buildNameAlias($table);
        // Construir JOIN
        $joinClauses = self::buildJoinClauses($joins);

        // Construir WHERE
        [$where, $params, $types] = self::buildWhereClause($conditions);

        // Construir opciones adicionales
        $options = self::buildOptions($options);

        // Construir consulta completa
        $query = "$select $joinClauses $where $options";

        return ['query' => $query, 'params' => $params, 'types' => $types];
    }

    /**
     * Construye una consulta SQL de inserción para la tabla y los datos proporcionados.
     * 
     * @param mixed $table El nombre de la tabla en la que se insertarán los datos.
     * @param mixed $data Un array asociativo que contiene los datos a insertar.
     * @throws \InvalidArgumentException Si los datos proporcionados no son válidos.
     * @return array Un array que contiene la consulta SQL y los valores correspondientes.
     */
    public static function buildInsertSQLQuery($table, $data)
    {
        // Construir INSERT
        $insert = "INSERT INTO " . self::buildNameAlias($table);

        $params = []; // Array para valores
        $types = '';  // Cadena de tipos para bind_param

        // Construir VALUES
        if (!empty($data)) {
            $values = [];
            $cols = [];
            foreach ($data as $key => $value) {
                // Escapar nombres de columnas
                $key = explode(".", $key);
                $key = (count($key) == 1) ? "`$key[0]`" : "`$key[0]`.`$key[1]`";

                // Agregar columna y placeholder
                $values[] = $key;
                $cols[] = "?";

                // Construir parámetros y tipos
                $types .= self::getParamType($value);
                $params[] = $value;
            }

            // Construir sección INTO y VALUES
            $into = "(" . implode(", ", $values) . ")";
            $vals = "VALUES (" . implode(", ", $cols) . ");";
        } else {
            logError("Error en " . __METHOD__ . ": Es necesario los datos para insertar.");
            throw new \InvalidArgumentException("Error: Es necesario los datos para insertar.");
        }

        // Construir consulta completa
        $query = "$insert $into $vals";

        return ['query' => $query, 'params' => $params, 'types' => $types];
    }
    /**
     * Construir la consulta sql para actualizar datos.
     * 
     * @param mixed $table La tabla en la que se realizará la actualización.
     * @param mixed $data Los datos que se actualizarán en la tabla.
     * @param mixed $conditions Las condiciones que deben cumplirse para la actualización.
     * @param mixed $joins Las uniones que se deben realizar en la consulta.
     * 
     * @throws \InvalidArgumentException Si alguno de los parámetros no es válido.
     * 
     * @return array Un array con la consulta SQL generada y los parámetros correspondientes.
     */
    public static function buildUpdateSQLQuery($table, $data, $conditions = [], $joins = [])
    {
        // Construir INSERT
        $update = "UPDATE " . self::buildNameAlias($table) . " SET";

        $params = []; // Array para valores
        $types = '';  // Cadena de tipos para bind_param

        // Construir VALUES
        if (!empty($data)) {
            $sets = [];
            foreach ($data as $key => $value) {
                // Escapar nombres de columnas
                $key = explode(".", $key);
                $key = (count($key) == 1) ? "`$key[0]`" : "`$key[0]`.`$key[1]`";
                // Agregar columna y placeholder
                $sets[] = "$key = ?";

                // Construir parámetros y tipos
                $types .= self::getParamType($value);
                $params[] = $value;
            }
            // Construir sección INTO y VALUES
            $set = implode(", ", $sets);
        } else {
            throw new \InvalidArgumentException("Son necesarios los datos para actualizar.");
        }

        // Construir JOIN
        $joinClauses = self::buildJoinClauses($joins);

        // Construir WHERE
        [$where, $_params, $_types] = self::buildWhereClause($conditions);
        $params = array_merge($params, $_params);
        $types .= $_types;

        // Construir consulta completa
        $query = "$update $set $joinClauses $where";

        return ['query' => $query, 'params' => $params, 'types' => $types];
    }

    /** 
     * Construir una consulta SQL DELETE.
     *
     * @param string $table Nombre de la tabla.
     * @param array $conditions Condiciones para la cláusula WHERE.
     * @return array Consulta SQL construida, parámetros y tipos.
     * */
    public static function buildDeleteQuery($table, $conditions = [])
    {
        // Construir DELETE
        $delete = "DELETE FROM " . self::buildNameAlias($table);

        // Construir WHERE
        [$where, $params, $types] = self::buildWhereClause($conditions);

        // Construir consulta completa
        $query = "$delete $where";

        return ['query' => $query, 'params' => $params, 'types' => $types];
    }

    public static function buildWithSelectQuery($withArgs, $selectArgs)
    {
        if (empty($withArgs) || empty($selectArgs)) {
            throw new \Exception("No se ha proporcionado argumentos completos para realizar la consulta");
        }
        $withQuery = "WITH ";
        $params = [];
        $types = '';
        foreach ($withArgs as $with) {
            $sqlQuery = self::buildSelectSQLQuery($with['table'], $with['columns'] ?? [], $with['conditions'] ?? [], $with['options'] ?? [], $with['joins'] ?? []);
            $whits[] = $with['name'] . " AS (" . $sqlQuery['query'] . ")";
            $params = array_merge($params, $sqlQuery['params']);
            $types .= $sqlQuery['types'];
        }
        $withQuery .= implode(", ", $whits);
        $selectQuery = self::buildSelectSQLQuery($selectArgs['table'], $selectArgs['columns'] ?? [], $selectArgs['conditions'] ?? [], $selectArgs['options'] ?? [], $selectArgs['joins'] ?? []);
        $query = $withQuery . " " . $selectQuery['query'];
        $params = array_merge($params, $selectQuery['params']);
        $types .= $selectQuery['types'];
        return ['query' => $query, 'params' => $params, 'types' => $types];
    }

    /**
     * Generar el statement
     * @param \mysqli $connection Conexión a la base de datos
     * @param array $queryData Datos de la consulta
     * @return bool|\mysqli_stmt Devuelve false en caso de error o un objeto mysqli_stmt en caso de éxito
     */
    public static function prepareStatement(\mysqli $connection, array $queryData)
    {
        $statement = $connection->prepare($queryData['query']);
        if (!empty($queryData['params'])) {
            $statement->bind_param($queryData['types'], ...$queryData['params']);
        }
        return $statement;
    }

    /**
     * Obtener el tipo de parámetro para un valor dado.
     *
     * Esta función determina el tipo de un valor dado y devuelve un carácter correspondiente
     * que representa el tipo. El carácter devuelto se utiliza para enlazar
     * parámetros en declaraciones preparadas.
     *
     * @param mixed $value El valor cuyo tipo necesita ser determinado.
     * 
     * @return string Devuelve un carácter que representa el tipo del valor:
     *                - "i" para entero
     *                - "d" para doble
     *                - "s" para cadena
     *                - "s" para NULL (tratado como cadena)
     *                - "s" para cualquier otro tipo (por defecto cadena)
     */
    public static function getParamType($value)
    {
        if (is_int($value)) {
            return "i";  // Entero
        } elseif (is_double($value)) {
            return "d";  // Doble
        } elseif (is_string($value)) {
            return "s";  // Cadena
        } elseif (is_null($value)) {
            return "s";  // NULL tratado como cadena
        }
        return "s";  // Por defecto, tratamos todo como cadena
    }

    /**
     * Constriur el sql para la clausula where.
     *
     * @param array $conditions Condiciones para construir la cláusula WHERE
     * @throws \Exception Lanza una excepción si ocurre un error
     * @return array Devuelve un array con la cláusula WHERE construida
     */
    private static function buildWhereClause(array $conditions): array
    {
        $params = [];
        $types = '';
        $where = '';

        if (!empty($conditions)) {
            $clauses = [];
            foreach ($conditions as $key => $value) {
                $key = (strpos($key, '.') === false) ? "`$key`" : '`' . str_replace('.', '`.`', $key) . '`';

                if (is_array($value)) {
                    // Si el valor es un array, asume que contiene [valor, operador]
                    [$val, $operator] = $value;
                    $valid_operators = ["=", "<", ">", ">=", "<=", "!=", "IS NULL", "LIKE", "IS NOT NULL", "BETWEEN", "SELECT"];
                    if (!in_array($operator, $valid_operators)) {
                        logError("Error en " . __METHOD__ . ": Operador inválido en la condición SQL: $operator");
                        throw new \Exception("Operador inválido en la condición SQL: $operator");
                    }
                    if (strtoupper($operator) == "LIKE") {
                        $params[] = $val;
                        $clauses[] = "$key LIKE ?";
                        $types .= "s";
                        continue;
                    }
                    if (strtoupper($operator) == "BETWEEN") {
                        [$params[], $params[]] = explode(" | ", $val, 2);
                        $clauses[] = "$key BETWEEN ? AND ?";
                        $types .= "ss";
                        continue;
                    }
                    if (strtoupper($operator) == "IS NULL" || strtoupper($operator) == "IS NOT NULL") {
                        $clauses[] = "$key $operator";
                        continue;
                    }
                    if (strtoupper($operator) == "SELECT") {
                        $clauses[] = "$key = ($val)";
                        continue;
                    }
                    $clauses[] = "$key $operator ?";
                    $types .= self::getParamType($val);
                    $params[] = $val;
                } else {
                    // Por defecto, usa '=' como operador
                    $clauses[] = "$key = ?";
                    $types .= self::getParamType($value);
                    $params[] = $value;
                }
            }
            $where = "WHERE " . implode(" AND ", $clauses);
        }
        return [$where, $params, $types];
    }

    /**
     * Construye las cláusulas JOIN para una consulta SQL.
     *
     * @param array $joins Un arreglo asociativo donde la clave es el nombre de la tabla a unirse y el valor es la condición ON para la unión.
     * @return string Las cláusulas JOIN construidas.
     */
    private static function buildJoinClauses(array $joins): string
    {
        $joinClauses = [];

        foreach ($joins as $joinType => $joinData) {
            // Determinar si es un tipo de JOIN o una tabla
            $isCustomJoinType = in_array(strtoupper($joinType), ['INNER', 'LEFT', 'RIGHT', 'FULL'], true);

            // Si no es un tipo de JOIN, asumimos INNER JOIN
            if (!$isCustomJoinType) {
                $joinData = [$joinType => $joinData];
                $joinType = 'INNER';
            }

            foreach ($joinData as $joinTable => $onCondition) {
                // Formatear la condición
                $condition = self::buildJoinCondition($onCondition);
                // Formatear la tabla y alias
                $tableAlias = self::buildNameAlias($joinTable);
                // Construir el join completo
                $joinClauses[] = sprintf(" %s JOIN %s ON %s", strtoupper($joinType), $tableAlias, $condition);
            }
        }
        return implode(' ', $joinClauses);
    }
    private static function buildJoinCondition($onCondition): string
    {
        if (is_array($onCondition)) {
            if (count($onCondition) === 2) {
                return implode(' = ', $onCondition);
            }
            // Si es un array pero tiene más de dos elementos, usar directamente el primer elemento
            return $onCondition[0];
        }

        return $onCondition;
    }
    private static function escapeIdentifier(string $identifier): string
    {
        // Escapar identificadores básicos para evitar problemas de inyección
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
    /**
     * Construye las opciones adicionales para una consulta SQL a partir de un array de opciones.
     *
     * @param array $options Array asociativo de opciones. Las claves se normalizan a mayúsculas.
     *                       Claves posibles:
     *                       - 'ORDER_BY': Columna(s) por las que se ordenará la consulta.
     *                       - 'LIMIT': Número máximo de registros a devolver.
     *                       - 'OFFSET': Número de registros a saltar antes de comenzar a devolver resultados.
     *
     * @return array Array con las opciones construidas en el siguiente orden:
     *               - 0: Cadena 'ORDER BY' si se especificó, de lo contrario cadena vacía.
     *               - 1: Cadena 'LIMIT' si se especificó, de lo contrario cadena vacía.
     *               - 2: Cadena 'OFFSET' si se especificó, de lo contrario cadena vacía.
     */
    private static function buildOptions(array $options): string
    {
        // Normalizar las claves del array a mayúsculas para evitar problemas de caso
        $options = array_change_key_case($options, CASE_UPPER);

        // Construir las opciones adicionales como ORDER BY y LIMIT...
        $groupBy = isset($options['GROUP_BY']) ? " GROUP BY " . $options['GROUP_BY'] : '';
        $orderBy = isset($options['ORDER_BY']) ? " ORDER BY " . $options['ORDER_BY'] : '';
        $limit = isset($options['LIMIT']) ? " LIMIT " . (int) $options['LIMIT'] : '';
        $offset = isset($options['OFFSET']) ? " OFFSET " . (int) $options['OFFSET'] : '';

        return "$groupBy $orderBy $limit $offset";
    }

    /**
     * Sanea las opciones de los parámetros de consulta.
     *
     * Esta función toma un array de parámetros de consulta, convierte todas las claves a mayúsculas,
     * y extrae las opciones 'ORDER_BY', 'LIMIT' y 'OFFSET' si están presentes.
     *
     * @param array $queryParams Array de parámetros de consulta.
     * @return array Array de opciones saneadas con las claves 'ORDER_BY', 'LIMIT' y 'OFFSET' si están presentes.
     */
    public static function sanitizeOptions($queryParams)
    {
        $options = [];
        $queryParams = array_change_key_case($queryParams, CASE_UPPER);
        if (isset($queryParams['GROUP_BY'])) {
            $options['GROUP_BY'] = $queryParams['GROUP_BY'];
        }
        if (isset($queryParams['ORDER_BY'])) {
            $options['ORDER_BY'] = $queryParams['ORDER_BY'];
        }
        if (isset($queryParams['LIMIT'])) {
            $options['LIMIT'] = $queryParams['LIMIT'];
        }
        if (isset($queryParams['OFFSET'])) {
            $options['OFFSET'] = $queryParams['OFFSET'];
        }
        return $options;
    }

    /**
     * Construye un alias de nombre para una tabla.
     *
     * Esta función toma un nombre de tabla y lo divide en partes usando un espacio como delimitador.
     * Si el nombre de la tabla contiene una sola palabra, lo envuelve en comillas invertidas.
     * Si el nombre de la tabla contiene dos palabras, la primera palabra se considera el nombre de la tabla
     * y la segunda palabra se considera el alias, y ambos se envuelven en comillas invertidas.
     *
     * @param string $tableName El nombre de la tabla, que puede incluir un alias separado por un espacio.
     * @return string El nombre de la tabla con el alias, formateado con comillas invertidas.
     */
    private static function buildNameAlias(string $table): string
    {
        if (strpos($table, ' ') !== false) {
            [$name, $alias] = explode(' ', $table, 2);
            return sprintf('%s AS %s', self::escapeIdentifier($name), self::escapeIdentifier($alias));
        }

        return self::escapeIdentifier($table);
    }
    /**
     * Encripta los datos proporcionados utilizando el algoritmo AES-256-CBC.
     *
     * @param string $data Los datos que se desean encriptar.
     * @param string $key La clave de encriptación.
     * @return string Los datos encriptados en formato base64.
     */
    public static function encryptData($data, $key)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencripta los datos proporcionados utilizando el algoritmo AES-256-CBC.
     *
     * @param string $encryptedData Los datos encriptados en base64 que se van a desencriptar.
     * @param string $key La clave utilizada para la desencriptación.
     * @return string|false Los datos desencriptados o false en caso de fallo.
     */
    public static function decryptData($encryptedData, $key)
    {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Formatea una fecha y hora inicial al formato de MySQL.
     *
     * @param string $dateTimeInitial La fecha y hora inicial en formato 'Y-m-d\TH:i'.
     * @return string La fecha y hora formateada en el formato de MySQL 'Y-m-d H:i:s'.
     */
    public static function formatMySQLDataTime($dateTimeInitial)
    {
        $formatted_date = \DateTime::createFromFormat('Y-m-d\TH:i', $dateTimeInitial); 
        return $formatted_date ? $formatted_date->format('Y-m-d H:i:s') : '';
    }

    public static function addLikeParameter(array &$params, string $field, ?string $value): void
    {
        if (!empty($value)) {
            $params[$field] = ["%$value%", 'LIKE'];
        }
    }

    public static function addDateRangeParameters(array &$params, string $field, ?string $from, ?string $to): void
    {
        if (empty($from) && empty($to)) {
            return;
        }

        if (!empty($from)) {
            $dateFrom = self::formatMySQLDataTime($from);
            if (!empty($to)) {
                $dateTo = self::formatMySQLDataTime($to);
                $params[$field] = ["$dateFrom | $dateTo", 'BETWEEN'];
                return;
            }
            $params[$field] = [$dateFrom, '>='];
            return;
        }

        $dateTo = self::formatMySQLDataTime($to);
        $params[$field] = [$dateTo, '<='];
    }

    /**
     * Sube un archivo al directorio especificado.
     *
     * @param array $file Arreglo que contiene la información del archivo subido ($_FILES['nombre_del_archivo']).
     * @param string $uploadDir Directorio donde se subirá el archivo.
     * @param string $fileName Nombre con el que se guardará el archivo.
     * @return array Arreglo asociativo con el código de estado y el mensaje correspondiente.
     */
    public static function uploadFile($file, $uploadDir, $fileName)
    {
        $uploadFile = "{$uploadDir}{$fileName}";
        // Crear el directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Verificar permisos del directorio
        if (!is_writable($uploadDir)) {
            logError('Ocurrió un error en ' . __METHOD__ . ': El directorio no tiene permisos de escritura');
            return ['status_code' => 500, 'message' => 'El directorio no tiene permisos de escritura.'];
        }

        // Mover el archivo subido al directorio
        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            return ["status_code" => 200, "message" => "Archivo subido exitosamente: " . $uploadFile . PHP_EOL];
        } else {
            return ['status_code' => 500, 'message' => 'No se pudo guardar el archivo.'];
        }
    }

    /**
     * Elimina un archivo en la ruta especificada.
     *
     * @param string $filePath La ruta del archivo que se desea eliminar.
     * @return array Un arreglo asociativo que contiene el código de estado y el mensaje:
     *               - 'status_code' => 200 si el archivo se eliminó exitosamente.
     *               - 'status_code' => 500 si ocurrió un error al intentar eliminar el archivo.
     *               - 'status_code' => 404 si el archivo no existe.
     */
    public static function deleteFile($filePath)
    {
        // Verificar si el archivo existe
        if (file_exists($filePath)) {
            // Intentar eliminar el archivo
            if (unlink($filePath)) {
                return ["status_code" => 200, "message" => "Archivo eliminado exitosamente."];
            } else {
                logError('Ocurrió un error al intentar eliminar el archivo ' . __METHOD__);
                return ['status_code' => 500, 'message' => 'No se pudo eliminar el archivo.'];
            }
        } else {
            return ['status_code' => 404, 'message' => 'El archivo no existe.'];
        }
    }


    /**
     * Genera un nombre de archivo único basado en el nombre original y la fecha y hora actuales.
     *
     * @param string $fileName El nombre original del archivo.
     * @return string El nombre de archivo generado con el formato: nombre_original_YYYYMMDDHHMMSS.extensión
     */
    public static function buildFileName($fileName)
    {
        $originalName = strtolower(pathinfo($fileName, PATHINFO_FILENAME));
        return $originalName . '_' . date('YmdHis') . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
    }


    /**
     * Genera un título de rango de fechas basado en los parámetros de consulta.
     *
     * @param array $queryParams
     * @return string
     */
    public static function generateDateTitle($date)
    {
        $current_date = date("Y-m-d H:i:s");
        $date_from = isset($date['from']) && $date['from'] !== ''
            ? self::formatMySQLDataTime($date['from'])
            : null;

        $date_to = isset($date['to']) && $date['to'] !== ''
            ? self::formatMySQLDataTime($date['to'])
            : $current_date;

        if ($date_from) {
            return " desde $date_from hasta $date_to";
        }

        return " hasta $date_to";
    }
}
