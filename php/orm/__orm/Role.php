<?php

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
            return $this->mapToObject($user);
        }, $users);
    }
}