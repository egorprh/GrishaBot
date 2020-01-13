<?php

///$telegramApi->query('getChat', ['chat_id' => '@egorprh']) получение ИД канала
// ид канала -1001008709248
// мой ид 342799025

echo 'This is test page.';

//Подключение Madeline
if (!file_exists(__DIR__ . '/madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', __DIR__ . '/madeline.php');
}
include __DIR__ . '/madeline.php';
include('vendor/autoload.php');
include('classes/TelegramBot.php');
include('classes/Constants.php');

use Krugozor\Database\Mysql\Mysql as Mysql;

$db = Mysql::create(Constants::DB_SERVER, Constants::DB_USERNAME, Constants::DB_PASSWORD)
    // Выбор базы данных
    ->setDatabaseName(Constants::DB_NAME)
    // Выбор кодировки
    ->setCharset("utf8");

$usertoken = substr(md5(microtime()), rand(0, 26), 9);
$params = [
    'username' => 'test',
    'userid' => '12345',
    'referertoken' => 'sdfs34r',
    'refererid' => 0,
    'selftoken' => 'sdfsd3r442',
    'date' => time(),
    'countreferal' => 0,
    'countsubscribes' => 0,
    'conditionscomplete' => false,
    'konkursid' => 1
];
$db->query('INSERT INTO `userdata` SET ?A["?s", ?i, "?s", "?s", "?s", ?i, ?i, ?i, ?i, ?i]', $params);

echo '<pre>';
echo 'Path: ' . __FILE__ . '<br>';
echo 'Line: ' . __LINE__ . '<br>';
var_dump(
    $db->getQueryString()
);
echo '</pre>';
die;



