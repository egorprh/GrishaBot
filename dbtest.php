<?php

echo 'DB tetst';

include('classes/dbmanage.php');

// Предположим, что установили библиотеку через composer
require './vendor/autoload.php';

// Алиас для краткости
use Krugozor\Database\Mysql\Mysql as Mysql;

// Соединение с СУБД и получение объекта-"обертки" над "родным" mysqli
$db = Mysql::create("localhost", "g995994o_konkurs", "kS2qiPsj")
    // Выбор базы данных
    ->setDatabaseName("g995994o_konkurs")
    // Выбор кодировки
    ->setCharset("utf8");


//Поля таблицы: id, username, chatid, referer, referertoken, selftoken, date, countreferal, countsubscribes, conditionscomplete
// Создаем таблицу пользователей с полями:
// Первичный ключ, имя пользователя, возраст, адрес
$db->query('
    CREATE TABLE IF NOT EXISTS userdata(
        id int unsigned not null primary key auto_increment,
        username varchar(255),
        chatid int(11),
        referer varchar(255),
        referertoken varchar(255),
        selftoken varchar(255)
        timecreated int(11),
        countreferal int(11),
        countsubscribes int(11),
        conditionscomplete int(11),
        konkursid int(11)
    )
');

$params = [
    'username' => 'testuser2',
    'chatid' => '545687',
    'referer' => 'admin',
    'referertoken' => 'jsbdjbf4',
    'selftoken' => '8732jkmm',
    'timecreated' => time(),
    'countreferal' => 0,
    'countsubscribes' => 0,
    'conditionscomplete' => false,
    'konkursid' => 1
];

$strkeys = implode(", ", array_keys($params));

$countsubscribes = 17;
$userdataid = 1;
$referertoken = '8732jkmm';

$db->query('INSERT INTO `userdata` SET ?A["?s", ?i, "?s", "?s", "?s", ?i, ?i, ?i, ?i, ?i]', $params);
$updateres = $db->query("UPDATE userdata SET countsubscribes = ?i  WHERE id = ?i", $countsubscribes, $userdataid);
$result = $db->query("SELECT * FROM userdata WHERE selftoken = '?s'", $referertoken);

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
