<?php

/*
 * TODO:
 * 1) Создать таблицу
 * 2) Написать алгоритм при приветствии
 * 3) Написать алгоритм проверки подписок
 * 4) Написать алгоритм запуска конкурса
 * */

echo 'This is Bot page.';
//443210917:AAEgqEA_MdIXxXWylu7EX4IEJLbUHo8inME

include('vendor/autoload.php'); //Подключаем библиотеку
include('classes/TelegramBot.php');

// Алиас для краткости
use Krugozor\Database\Mysql\Mysql as Mysql;

// Соединение с СУБД и получение объекта-"обертки" над "родным" mysqli
$db = Mysql::create("localhost", "u0905931_default", "6PTP_j!b")
    // Выбор базы данных
    ->setDatabaseName("u0905931_default")
    // Выбор кодировки
    ->setCharset("utf8");

$telegramApi = new TelegramBot();

///$telegramApi->query('getChat', ['chat_id' => '@egorprh']) получение ИД канала
// ид канала -1001008709248
// мой ид 342799025
//$telegramApi->query('getChatMember', [-1001008709248, 342799025]) проверка подписан ли юзер на бота

$message = $telegramApi->getMessage();

$text = $message["message"]["text"]; //Текст сообщения
$chat_id = $message["message"]["chat"]["id"]; //Уникальный идентификатор пользователя
$name = $message["message"]["from"]["username"]; //Юзернейм пользователя
$date = $message["message"]["date"];

$textarr = explode(' ', $text);
$isstart = in_array('/start', $textarr);
$iamsubcribe = in_array('Я подписался', $textarr);

if ($isstart) {
    switch (count($textarr)) {
        case 2:
            $referertoken = $textarr[1];
            $referer = $db->query("SELECT * FROM userdata WHERE selftoken = '?s'", $referertoken);
            $referer = $referer->fetch_assoc_array()[0];
            $referallmessage = "По вашей ссылке пришел пользователь" . $name;
            $countsubscribes = $referer['countsubscribes'] + 1;
            $db->query("UPDATE userdata SET countsubscribes = ?i  WHERE id = ?i", $countsubscribes, $referer['id']);
            $telegramApi->sendMessage($referer['chatid'], $referallmessage);
            if ($countsubscribes == 3 && $referer['countsubscribes'] >= 3 && !$referer['conditionscomplete']) {
                $telegramApi->sendMessage($referer['chatid'], 'Поздравляем вы выполнили все условия и учавсвтуете в конкурсе');
            }
            break;
        case 1:
            $referertoken = 0;
            $referer = 'admin';
            break;
    }

    $usertoken = substr(md5(microtime()), rand(0, 26), 9);
    $params = [
        'username' => $name,
        'chatid' => $chat_id,
        'referertoken' => $referertoken,
        'refererid' => !empty($referer['id']) ? $referer['id'] : 0,
        'selftoken' => $usertoken,
        'date' => time(),
        'countreferal' => 0,
        'countsubscribes' => 0,
        'conditionscomplete' => false,
        'konkursid' => 1
    ];
    $db->query('INSERT INTO `userdata` SET ?A["?s", ?i, "?s", "?s", "?s", ?i, ?i, ?i, ?i, ?i]', $params);

    $me = $telegramApi->query('getMe');
    $botname = $me->result->username;

    $referallurl = 'https://telegram.me/' . $botname . '?start=' . $usertoken;
    $welcomemessage = "Привет! Подпишись на этот канал и пригласи 3 друзей по этой ссылке:" . $referallurl . ", тогда ты сможешь учавствовать в розыграше!";

    $telegramApi->sendMessage($chat_id, $welcomemessage);
} elseif ($iamsubcribe) {
    $ourchannels = []; //тут указаны chat_id каналов на которые нужно подписаться
    $notsubscribes = [];
    $userdata = $db->query("SELECT * FROM userdata WHERE chatid = ?i", $chat_id);
    $userdata = $userdata->fetch_assoc_array()[0];
    $countsubscribes = $userdata['countsubscribes'];
    foreach ($ourchannels as $ourchannel) {
        $issubscribe = $telegramApi->query('getChatMember', [$ourchannel, $chat_id]);
        // TODO Полюбому надо как то разобрать
        if ($ourchannels) {
            $countsubscribes++;
        } else {
            $notsubscribes[] = $ourchannel;
        }
    }

    if ($countsubscribes == count($ourchannels)) {
        $telegramApi->sendMessage($chat_id, 'Красава! Ты подписался на все каналы!');
        if ($userdata['countreferal'] >= 3 && !$userdata['conditionscomplete']) {
            $telegramApi->sendMessage($chat_id, 'Поздравляем вы выполнили все условия и учавсвтуете в конкурсе');
        }
    } else {
        $telegramApi->sendMessage($chat_id, 'Ты ещё не всё. Подпишись на каналы:' . $notsubscribes);
    }
    $db->query("UPDATE userdata SET countsubscribes = ?i  WHERE id = ?i", $countsubscribes, $userdata['id']);
} else {
    $randommessages = [
        'Ничто не дается так дешево как хочется',
        'Господи, сколько уже не сделано, а сколько еще предстоит не сделать!',
        'Умными мы называем людей, которые с нами соглашаются.',
        'Каждый человек стоит столько, сколько он сделал, минус тщеславие.',
        'Когда женщине нечего сказать, это не значит, что она будет молчать',
        'Если Вы взглянули в зеркало, но никого там не обнаружили – Вы неотразимы!',
        'Лучше сделать и жалеть, чем жалеть, что не сделал',
        'Спи быстрей – подушка нужна!',
        'Оптимист верит, что мы живем в лучшем из миров. Пессимист боится, что так и есть.',
        'Разговор с женщиной есть потеря времени. Вопрос только в том, насколько это приятно',
        'В жизни всегда есть место поводу!',
        'Счастье – это когда утром очень хочется на работу, а вечером очень хочется домой',
        'Каждый имеет фотографическую память. Не у каждого есть пленка',
        'Картина Репина «Приплыли!» - всю ночь гребли, а лодку отвязать забыли',
        'Обьективная реальность есть бред, вызванный недостатком алкоголя в крови.',
        'Если Вам нечего делать, то не надо делать это здесь!',
        'Жизнь такова, какова она есть, и больше никакова. Каково?',
        'Человека охотнее всего съедают те, кто его не переваривает.',
        'Фарш невозможно провернуть назад. Второе начало термодинамики.',
        'Дегенератор мыслей',
        'Любопытство не порок, а способ образования'
    ];
    if (!empty($chat_id)) {
        $telegramApi->sendMessage($chat_id, $randommessages[rand(0, 19)]);
    }
}




