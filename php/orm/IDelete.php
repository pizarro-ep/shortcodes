<?php
interface IDelete {
    public function where(string|array $field, mixed $value = null): IDelete;
    public function execute(): bool|string;
}