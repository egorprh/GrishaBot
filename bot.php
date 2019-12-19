<?php

echo 'This is Bot page.';
//443210917:AAEgqEA_MdIXxXWylu7EX4IEJLbUHo8inME

include('vendor/autoload.php'); //Подключаем библиотеку
include('classes/TelegramBot.php');
include('classes/dbmanage.php');

$dbManager = new dbmanage();
$telegramApi = new TelegramBot();

$message = $telegramApi->getMessage();
$tablename = 'usersdata';

$text = $message["message"]["text"]; //Текст сообщения
$chat_id = $message["message"]["chat"]["id"]; //Уникальный идентификатор пользователя
$name = $message["message"]["from"]["username"]; //Юзернейм пользователя
$date = $message["message"]["date"];

$textarr = explode(' ', $text);
$isstart = in_array('/start', $textarr);

if ($isstart) {
    switch (count($textarr)) {
        case 2:
            $referertoken = $textarr[1];
            $referer = 'neadmin';
            break;
        case 1:
            $referertoken = 0;
            $referer = 'admin';
            break;
    }

    $usertoken = substr(md5(microtime()), rand(0, 26), 9);
    $params = [
        'username' => $name,
        'referertoken' => $referertoken,
        'referer' => $referer,
        'selftoken' => $usertoken,
        'date' => time()
    ];
    $result = $dbManager->insert_record($tablename, $params);

    $me = $telegramApi->query('getMe');
    $botname = $me->result->username;

    $referallurl = 'https://telegram.me/' . $botname . '?start=' . $usertoken;
    $welcomemessage = "Привет! Подпишись на этот канал и пригласи 3 друзей по этой ссылке:" . $referallurl . ", тогда ты сможешь учавствовать в розыграше!";

    $telegramApi->sendMessage($chat_id, $welcomemessage);
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
    $telegramApi->sendMessage($chat_id, $randommessages[rand(0, 19)]);
}




