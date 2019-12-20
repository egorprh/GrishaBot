<?php

include('classes/dbmanage.php');

// Предположим, что установили библиотеку через composer
require  './vendor/autoload.php';
// Алиас для краткости
use Krugozor\Database\Mysql\Mysql as Mysql;

// Соединение с СУБД и получение объекта-"обертки" над "родным" mysqli
$db = Mysql::create("localhost", "root", "smCTj1P5FXsYK")
    // Выбор базы данных
    ->setDatabaseName("konkurs_bot")
    // Выбор кодировки
    ->setCharset("utf8");

// Получение объекта результата Statement
// Statement - "обертка" над "родным" объектом mysqli_result
$result = $db->query("SELECT * FROM `usersdata`");

// Получаем данные (в виде ассоциативного массива, например)
$data = $result->fetch_assoc();

echo '<pre>';
echo 'Path: ' . __FILE__ . '<br>';
echo 'Line: ' . __LINE__ . '<br>';
var_dump(
    $result->fetch_assoc_array()
);
echo '</pre>';

// Не работает запрос? Не проблема - выведите его на печать:
echo $db->getQueryString();

$dbManager = new dbmanage();

$tablename = 'usersdata';

$dbManager->get_records($tablename);
