<?php

class Validator
{
    private static ?Validator $instance = null;
    private array $errors = [];

    // Constructor privado para evitar instanciación directa
    private function __construct() {}

    // Método para obtener la única instancia
    public static function getInstance(): Validator
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Método para resetear los errores
    public function reset(): void
    {
        $this->errors = [];
    }

    public function validate(array $data, array $rules, array $fieldsToValidate = []): array
    {
        $this->reset();

        // Si no se especifican campos a validar, validamos todos
        if (empty($fieldsToValidate)) {
            $fieldsToValidate = array_keys($rules);
        }

        foreach ($rules as $field => $constraints) {
            // Solo validar si el campo está en la lista de campos a validar
            if (in_array($field, $fieldsToValidate)) {
                foreach ($constraints as $constraint) {
                    [$rule, $params] = $this->parseRule($constraint);

                    $method = "validate" . ucfirst($rule);
                    if (method_exists($this, $method)) {
                        $this->$method($data, $field, $params);
                    } else {
                        throw new \Exception("Regla de validación '$rule' no implementada.");
                    }
                }
            }
        }
        return $this->errors;
    }

    private function parseRule($constraint): array
    {
        if (strpos($constraint, ':') !== false) {
            [$rule, $params] = explode(':', $constraint, 2);
            $params = explode(',', $params);
        } else {
            $rule = $constraint;
            $params = [];
        }
        return [$rule, $params];
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // Validaciones
    private function validateRequired(array $data, string $field): void
    {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $this->addError($field, "El campo $field es obligatorio.");
        }
    }

    private function validateLength(array $data, string $field, array $params): void
    {
        $length = intval($params[0]);
        if (isset($data[$field]) && strlen($data[$field]) !== $length) {
            $this->addError($field, "El campo $field debe tener $length caracteres.");
        }
    }

    private function validateMin(array $data, string $field, array $params): void
    {
        $min = intval($params[0]);
        if (isset($data[$field]) && strlen($data[$field]) < $min) {
            $this->addError($field, "El campo $field debe tener al menos $min caracteres.");
        }
    }
    private function validateMax(array $data, string $field, array $params): void
    {
        $max = intval($params[0]);
        if (isset($data[$field]) && strlen($data[$field]) > $max) {
            $this->addError($field, "El campo $field no puede superar $max caracteres.");
        }
    }

    private function validateIn(array $data, string $field, array $params): void
    {
        if (isset($data[$field])) {
            if (!in_array($data[$field], $params)) {
                $this->errors[$field][] = "El campo $field debe ser uno de: " . implode(', ', $params);
            }
        }
    }

    private function validateString(array $data, string $field): void
    {
        if (isset($data[$field]) && !is_string($data[$field])) {
            $this->addError($field, "El campo $field debe ser una cadena.");
        }
    }
    
    private function validateInt(array $data, string $field): void
    {
        if (isset($data[$field]) && !is_int($data[$field])) {
            $this->addError($field, "El campo $field debe ser un número entero.");
        }
    }

    private function validateScalar(array $data, string $field): void
    {
        if (isset($data[$field]) && !is_scalar($data[$field])) {
            $this->addError($field, "El campo $field debe ser un escalar.");
        }
    }

    private function validateFloat(array $data, string $field): void
    {
        if (isset($data[$field]) && !is_float($data[$field])) {
            $this->addError($field, "El campo $field debe ser un número decimal.");
        }
    }

    private function validateImg(array $data, string $field){
        if (!isset($data[$field]) || !is_numeric($data[$field]) || $data[$field] > (500 * 1024)){
            
        }
    }

    private function validateEmail(array $data, string $field): void
    {
        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "El campo $field debe ser un correo electrónico válido.");
        }
    }

    private function validateAlpahanumeric(array $data, string $field): void
    {        
        if (isset($data[$field]) && !preg_match('/^[a-zA-ZñÑáéíóúüéÁÉÍÓÚÜ0-9\s]$/', $data[$field])) {
            $this->addError($field, "El campo $field debe contener solo letras y números.");
        }
    }

    private function validatePassword(array $data, string $field): void
    {
        $password = $data[$field] ?? '';
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->addError($field, "El campo $field debe tener al menos 8 caracteres, incluyendo una letra y un número.");
        }
    }

    private function validateDate(array $data, string $field): void
    {
        if (isset($data[$field]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$field])) {
            $this->addError($field, "El campo $field debe ser una fecha en formato YYYY-MM-DD.");
        }
    }

    private function validateNullable(array $data, string $field, array $params): void
    {
        // Si el campo es null, no validamos las demás reglas
        if (isset($data[$field]) && $data[$field] === null) {
            return; // Si es null, simplemente no se validan las otras reglas
        }
    }


    /** Obtiene el primer error  */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0]; // Devuelve el primer error encontrado
            }
        }
        return ''; // Si no hay errores
    }

    /** Obtiene todos los errores */
    public function getAllErrors(): array
    {
        return $this->errors;
    }
}
