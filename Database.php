<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        throw new Exception();
    }

    public function skip()
    {
        throw new Exception();
    }

    // ------------------------------------------------------------------
    // protected
    // ------------------------------------------------------------------

    protected function parseMark(string $query): array
    {
        $match = [];
        preg_match('#\?(d|f|a|\#)?#', $query, $match);
        return $match;
    }

    protected function hasMark(string $query): bool
    {
        return \str_contains($query, '?');
    }

    protected function hasBlock(string $query): bool
    {
        $t = \strpos($query, '{');
        return $t !== false && \strpos($query, '}', $t) !== false;
    }

    protected function checkNestedBlock(string $query): void
    {
        if (preg_match('#\{[^\}]+?\{#', $query)) throw new Exception('Вложенные блоки не допустимы.');
    }

    // protected function checkIntegrityBlock(string $query): void
    // {
    //     $start = \strpos($query, '{');
    //     $end   = \strpos($query, '}');
    //     if (
    //         ($start === false && $end === false)
    //         ||
    //         ($start !== false && $end !== false)
    //     ) throw new Exception('Не найден закрывающий или открывающий символ блока.');
    // }

    /**
     * string, int, float, bool null
     */
    protected function isScalarTypeOrNull($value): bool
    {
        // Скалярные переменные — это переменные, содержащие int, float, string и bool
        return $value === null || is_scalar($value);
    }
}
