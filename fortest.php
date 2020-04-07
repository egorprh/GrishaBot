<?php

///$telegramApi->query('getChat', ['chat_id' => '@egorprh']) получение ИД канала
// ид канала -1001008709248
// мой ид 342799025

echo 'This is test page.';

include('vendor/autoload.php');
include('classes/TelegramBot.php');
include('classes/Constants.php');
include('classes/Manage.php');
include('classes/BotFunctions.php');
include('classes/madelineManage.php');

//use Krugozor\Database\Mysql\Mysql as Mysql;
//
//$db = Mysql::create(Constants::DB_SERVER, Constants::DB_USERNAME, Constants::DB_PASSWORD)
//    // Выбор базы данных
//    ->setDatabaseName(Constants::DB_NAME)
//    // Выбор кодировки
//    ->setCharset("utf8");
//
//$sql = "SELECT userid FROM ezcash_send1 WHERE issend = 0";
//$competitors = $db->query($sql);
//$competitorslist = $competitors->fetch_row_array();
//$usersarr = [];
//foreach ($competitorslist as $item) {
//    $usersarr[] = current($item);
//}

$db = Manage::set_db_connect();

$records = $db->query("SELECT id FROM ezcash_userdata");

//foreach ($records->fetch_assoc_array() as $item) {
//    $refcode = substr(md5(microtime()), rand(0, 26), 10);
//    $db->query('UPDATE ezcash_userdata SET refcode = "?s" WHERE id = ?i', $refcode, current($item));
//}


    echo '<pre>';
    echo 'Path: ' . __FILE__ . '<br>';
    echo 'Line: ' . __LINE__ . '<br>';
    var_dump(
        madelineManage::get_participant(-1001488600170, 342799025)

    );
    echo '</pre>';
    die;

    //$MadelineProto->channels->getParticipant(['channel' => -1001492513386, 'user_id' => 342799025])

//$params = ['username' => 'egorprh2', 'countsubscribers' => 1, 'countsubscriptions' => 3];
//
//echo '<pre>';
//echo 'Path: ' . __FILE__ . '<br>';
//echo 'Line: ' . __LINE__ . '<br>';
//var_dump(
//    BotFunctions::update_comp_record($db, $params,3427990255)
//);
//echo '</pre>';
//die;

////$usersarr = Manage::get_users_for_sends();
//
//$sql = "SELECT * FROM ezcash_send1 WHERE issend = ?i";
//$botusers = $db->query($sql, 0);
//$userslist = $botusers->fetch_assoc_array();
//
//$usersarr = [];
//foreach ($userslist as $item) {
//    $usersarr[] = $item['userid'];
//}
//
////$usertoken = substr(md5(microtime()), rand(0, 26), 9);
////$params = [
////    'username' => 'test',
////    'userid' => '12345',
////    'referertoken' => 'sdfs34r',
////    'refererid' => 0,
////    'selftoken' => 'sdfsd3r442',
////    'date' => time(),
////    'countreferal' => 0,
////    'countsubscribes' => 0,
////    'conditionscomplete' => false,
////    'konkursid' => 1
////];
////$db->query('INSERT INTO `userdata` SET ?A["?s", ?i, "?s", "?s", "?s", ?i, ?i, ?i, ?i, ?i]', $params);
//
//echo '<pre>';
//echo 'Path: ' . __FILE__ . '<br>';
//echo 'Line: ' . __LINE__ . '<br>';
//var_dump(
//    $userslist, $usersarr
//);
//echo '</pre>';
//die;
//
//// 1. По команде добавляем запись в базу issend = 0 (Можно записывать сообщение)
//// 2. Скрипт, который берет 100 записей из базы, делает им рассылку и ставит issend = 1
//// 3. Каждые 5 минут запускаем скрипт
//
//$sql = "SELECT userid FROM ezcash_userdata";
//$competitors = $db->query($sql);
//$competitorslist = $competitors->fetch_assoc_array();
//
//echo '<pre>';
//echo 'Path: ' . __FILE__ . '<br>';
//echo 'Line: ' . __LINE__ . '<br>';
//var_dump(
//    $competitorslist
//);
//echo '</pre>';
//die;
//
//echo 'Pages';
//
//$telegramApi = new TelegramBot();
//
//$message = $telegramApi->getMessage();
//
//$text = $message["message"]["text"];
//
//if ($text == 'addrecord') {
//    $params = [
//        'userid' => 342799025,
//        'issend' => 0
//    ];
//    for ($i = 1; $i < 26; $i++) {
//        $db->query('INSERT INTO ezcash_messagetask SET ?A[?i, ?i]', $params);
//    }
//    $telegramApi->sendMessage(342799025, 'Done');
//}
//
//if ($text == 'script') {
//    $records = $db->query("SELECT * FROM ezcash_messagetask WHERE issend = ?i LIMIT 100", 0);
//    $userslist = $records->fetch_assoc_array();
//    $i = 1;
//    foreach ($userslist as $item) {
//        usleep(300000);
//        $telegramApi->sendMessage($item['userid'], $i);
//        $db->query("UPDATE ezcash_messagetask SET issend = ?i  WHERE id = ?i", 1, $item['id']);
//        $i++;
//    }
//}



