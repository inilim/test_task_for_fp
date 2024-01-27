<?php

require_once __DIR__ . '/dev.php';

use FpDbTest\Database;
use FpDbTest\DatabaseTest;

spl_autoload_register(function ($class) {
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});


$mysqli = @new \mysqli('localhost', 'root', '', 'database_test', 3306);
if ($mysqli->connect_errno) {
    throw new Exception($mysqli->connect_error);
}

$db = new Database($mysqli);
$db->buildQuery('SELECT ?# FROM users WHERE user_id = ?d {AND block = ?d}', [
    ['name1', 'name2'],
    111,
    500
]);
// $test = new DatabaseTest($db);
// $test->testBuildQuery();

exit('OK');
