<?php

include('vendor/autoload.php');;
include('classes/Constants.php');
include('classes/TelegramBot.php');
include('classes/Manage.php');

// 1. По команде добавляем запись в базу issend = 0 (Можно записывать сообщение)
// 2. Скрипт, который берет 100 записей из базы, делает им рассылку и ставит issend = 1
// 3. Каждые 5 минут запускаем скрипт

$telegramApi = new TelegramBot();
$db = Manage::set_db_connect();

$nonsended = $db->query("SELECT * FROM ezcash_messagetask WHERE issend = ?i LIMIT 100", 0);
$userslist = $nonsended->fetch_assoc_array();
$countsend = 0;

foreach ($userslist as $item) {
    usleep(150000);
    $telegramApi->sendMessage($item['userid'], json_decode($item['message']));
    $db->query("UPDATE ezcash_messagetask SET issend = ?i  WHERE id = ?i", 1, $item['id']);
    $countsend++;
}

if ($countsend != 0) {
    foreach (Constants::ADMINS as $admin) {
        $telegramApi->sendMessage($admin, "Сообщения успешно отправлены " . $countsend . " пользователю(-лям).");
    }
}
