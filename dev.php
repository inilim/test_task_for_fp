<?php

function dde(...$v): void
{
    var_dump(...$v);
    exit();
}

/**
 * var_dump
 */
function dd(...$v): void
{
    var_dump(...$v);
    echo PHP_EOL;
}

function de(...$v): void
{
    array_map(function ($i) {
        print_r($i);
        echo PHP_EOL;
    }, $v);
    exit();
}

function d(...$v): void
{
    array_map(function ($i) {
        print_r($i);
        echo PHP_EOL;
    }, $v);
    echo PHP_EOL;
}
