<?php

interface IInsert {
    public function fillable(array $fields): IInsert;
    public function values(array $values): IInsert;
    public function execute(): bool|string;
}