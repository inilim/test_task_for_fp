<?php

namespace FpDbTest;

use Exception;
use mysqli;
use FpDbTest\Skip;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;
    protected readonly Skip $skip;
    protected const ALLOWED_NULL = ['?', '?d', '?f'];

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->skip   = new Skip;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $args = \array_values($args);
        $results = [];
        // ------------------------------------------------------------------
        // ___
        // ------------------------------------------------------------------
        if ($this->hasBlock($query)) {
            $this->checkNestedBlock($query);
            $this->checkIntegrityBlock($query);
            $this->findBlocks($query, $results);
        }
        // ------------------------------------------------------------------
        // ___
        // ------------------------------------------------------------------
        if ($this->hasMark($query)) {
            $this->findMarks($query, $results);
        }
        // ------------------------------------------------------------------
        // ___
        // ------------------------------------------------------------------
        // if (\sizeof($results) !== \sizeof($args)) throw new Exception('');
        // ------------------------------------------------------------------
        // ___
        // ------------------------------------------------------------------
        if ($results) {
            usort($results, function ($a, $b) {
                if ($a['pos'] == $b['pos']) return 0;
                return $a['pos'] < $b['pos'] ? -1 : 1;
            });
            $this->process($query, $results, $args);
        }
        // ------------------------------------------------------------------
        // ___
        // ------------------------------------------------------------------
        return $query;
    }

    public function skip()
    {
        return $this->skip;
    }

    // ------------------------------------------------------------------
    // protected
    // ------------------------------------------------------------------

    protected function process(string &$query, array $results, array $args): void
    {
        /**
         * @var array{type:string,tmp_str:string,sub_query:string,pos:int} $result
         */
        foreach ($results as $idx => $result) {
            $arg = $args[$idx];
            match ($result['type']) {
                'mark'  => $this->mark($query, $result, $arg),
                'block' => $this->block($query, $result, $arg),
            };
        }
        d($query);
        de($result);
    }

    protected function mark(string &$query, array $result, $arg): void
    {
        self::ALLOWED_NULL;
        /**
         * @var array{type:string,tmp_str:string,sub_query:string,pos:int} $result
         */
        $is_array = \is_array($arg);
        match ($result['sub_query']) {
            '?' => '',
        };
    }

    protected function setArg(string &$query, string $mark, $arg): void
    {
    }

    protected function prepareArg(string $mark, $arg)
    {
        return match ($mark) {
            '?d' => $arg === null ? 'NULL' : \intval($arg),
            '?f' => $arg === null ? 'NULL' : \floatval($arg),
            '?a' => $this->prepareArrayArg(\is_array($arg) ? $arg : [$arg]),
            '?#' => $this->prepareArrayArg(\is_array($arg) ? $arg : [$arg]),
        };
    }

    protected function prepareArrayArg(array $arg): string
    {
        $check_value = function ($v) {
            if (!$this->isScalarTypeOrNull($v)) throw new Exception('Недопустимый тип');
            if (\is_string($v)) return $this->escape($v);
            if ($v === null) return 'NULL';
            return $v;
        };
        // Массив (параметр ?a) преобразуется либо в список значений через запятую (список)
        if (\array_is_list($arg)) {
            $arg = \array_map($check_value, $arg);
            return \implode(',', $arg);
        }
        // либо в пары идентификатор и значение через запятую (ассоциативный массив).
        $arg = \array_map(
            fn ($v, $k) => $check_value($k) . ',' . $check_value($v),
            $arg,
            \array_keys($arg),
        );
        return \implode(',', $arg);
    }

    protected function block(string &$query, array $result, $arg): void
    {
        /**
         * @var array{type:string,tmp_str:string,sub_query:string,pos:int} $result
         */
        if ($arg instanceof Skip) {
            $query = \str_replace($result['tmp_str'], '', $query);
        } else {
            $result['sub_query'] = \trim($result['sub_query'], '{}');
            if ($this->hasMark($result['sub_query']) && $this->isScalarTypeOrNull($arg)) {
            }
            $query = \str_replace($result['tmp_str'], $result['sub_query'], $query);
        }
    }

    protected function escape(string $value): string
    {
        return sprintf('"%s"', $this->mysqli->real_escape_string($value));
    }

    protected function genStr(int $length = 10): string
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[\random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    protected function genTmpStr(int $length = 10): string
    {
        return '(' . $this->genStr($length) . ')';
    }

    protected function findMarks(string &$query, array &$results): void
    {
        $this->findBy(
            $query,
            $results,
            '#\?(d|f|a|\#)?#',
            'mark'
        );
    }

    protected function findBy(string &$query, array &$results, string $pattern, string $type): void
    {
        $query = \preg_replace_callback(
            pattern: $pattern,
            callback: function ($m) use (&$results, $type) {
                $tmp = $this->genTmpStr();
                $results[] = [
                    'type'      => $type,
                    'tmp_str'   => $tmp,
                    'sub_query' => $m[0][0],
                    'pos'       => $m[0][1],
                ];
                return $tmp;
            },
            subject: $query,
            flags: PREG_OFFSET_CAPTURE
        );
    }

    protected function findBlocks(string &$query, array &$results): void
    {
        $this->findBy(
            $query,
            $results,
            '#\{[^\{\}]+\}#',
            'block'
        );
    }

    protected function hasMark(string $query): bool
    {
        return \str_contains($query, '?');
    }

    protected function hasBlock(string $query): bool
    {
        // $t = \strpos($query, '{');
        // return $t !== false && \strpos($query, '}', $t) !== false;
        return \str_contains($query, '{') && \str_contains($query, '}');
    }

    protected function checkNestedBlock(string $query): void
    {
        if (\preg_match('#\{[^\}]+?\{#', $query)) throw new Exception('Вложенные блоки не допустимы.');
    }

    protected function checkIntegrityBlock(string $query): void
    {
        $start = \substr_count($query, '{');
        $end   = \substr_count($query, '}');
        if ($start !== $end) throw new Exception('Целостность блоков нарушена.');
    }

    /**
     * string, int, float, bool null
     */
    protected function isScalarTypeOrNull($value): bool
    {
        // Скалярные переменные — это переменные, содержащие int, float, string и bool
        return $value === null || \is_scalar($value);
    }
}
