<?php
interface IUpdate {
    public function fillable(array $fields): IUpdate;
    public function set(array $data): IUpdate;
    public function where(string|array $field, mixed $value = null): IUpdate;
    public function execute(): bool|string;
}