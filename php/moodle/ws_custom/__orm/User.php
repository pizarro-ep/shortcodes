<?php

require_once __DIR__ . '/Core.php';
require_once __DIR__ . '/Role.php';
require_once __DIR__ . '/Validator.php';

class User extends Core
{
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password', 'role_id'];

    protected $validator;

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
        $this->validator = Validator::getInstance();
    }

    protected function validate(): void
    {
        try {
            $data = ['nombre' => $this->name, 'email' => $this->email, 'contraseña' => $this->password, 'rol' => $this->role_id];
            $rules = [
                'nombre' => ['required'],
                'email' => ['required', 'email'],
                'contraseña' => ['required', 'min:6'],
                'rol' => ['required', 'int']
            ];
            $errors = $this->validator->validate($data, $rules);

            if ($errors) {
                throw new InvalidArgumentException($this->validator->getFirstError());
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
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