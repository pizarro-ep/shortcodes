<?php
interface ISelect {
    public function fillable(array $fields): ISelect;
    public function select(array $fields): ISelect;
    public function where(string|array $field, mixed $value = null): ISelect;
    public function is(string|array $field, mixed $value = null): ISelect;
    public function isNot(string|array $field, mixed $value = null): ISelect;
    public function isNull(string|array $field): ISelect;
    public function isNotNull(string|array $field): ISelect;
    public function greaterThan(string|array $field, mixed $value = null): ISelect;
    public function greaterThanOrEqual(string $field, mixed $value): ISelect;
    public function lessThan(string $field, mixed $value): ISelect;
    public function lessThanOrEqual(string $field, mixed $value): ISelect;
    public function isBetween(string $field, string ...$conditions): ISelect;
    public function contains(string|array $field, mixed $value = null): ISelect;
    public function notContains(string|array $field, mixed $value = null): ISelect;
    public function beginsWith(string|array $field, mixed $value = null): ISelect;
    public function endsWith(string|array $field, mixed $value = null): ISelect;
    public function orderBy(string $field, ?string $direction = null): ISelect;
    public function take(int $limit): ISelect;
    public function skip(int $offset): ISelect;
    public function getResult(): ?array;
    public function getResults(): ?array;
    public function count(): int;
}