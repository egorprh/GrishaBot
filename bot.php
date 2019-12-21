<?php

/* Алгоритм
 * 1) Пользователь переходит по реферальной ссылке
 * 2) Смотри от кого он пришел и записываем данные в базу
 * 3) Показываем сообщение в котором канала на которые надо подписаться и сформированная для него ссылка с реферальным кодом
 * 4) Рефереру сообщаем что подписался по его ссылке реферал и записываем количество в базу число рефералов
 *
 * 1) Пользователь подписывается на каналы
 * 2) Идет к нам и говорит "Я подписался"
 * 3) Проверяем так ли это
 * 4) Если да - то записываем в базу что он выполнил подписки
 * 5) Если нет - то показываем каналы на которые он не подписался
 *
 * Когда пользователь выполняет все результаты отправляем ему сообщение что он молодец.
 * Проверяем это, когда пользователь делает все подписки, и когда приглашает нужное количество пользователей
 *
 * Проверка результатов:
 * 1) Делаем запрос в базу чтобы выбрало тех, кто выполнил условия конкурса
 * 2) Собираем их аёдишники
 * 3) Рандомным алгоритмом выбираем победителей
 *
 * Каналы для конкурса хранятся в отдельной таблице?
 *
 *  Для каждого конкурса новая таблица.
 *  Поля таблицы: id, username, chatid, referer, referertoken, selftoken, date, countreferal, countsubscribes, conditionscomplete
 *
 * TODO:
 * 1) Создать таблицу
 * 2) Написать алгоритм при приветствии
 * 3) Написать алгоритм проверки подписок
 *
 *
 * */

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
$userid = $message["message"]["from"]["id"]; //ИД пользователя
$date = $message["message"]["date"];

$textarr = explode(' ', $text);
$isstart = in_array('/start', $textarr);
$iamready = in_array('/icompleted', $textarr);

if ($isstart) {
    switch (count($textarr)) {
        case 2:
            $referertoken = $textarr[1];
            $referer = "SELECT * FROM userdata WHERE selftoken = $referertoken";
            $referallmessage = "По вашей ссылке пришел пользователь" . $name;
            $countsubscribes = $referer->countsubscribes + 1;
            //"UPDATE userdata SET countsubscribes = $countsubscribes WHERE id = $referer->id"
            $telegramApi->sendMessage($referer->chatid, $referallmessage);
            if ($countsubscribes == 3 && $referer->countsubscribes >= 3 && !$referer->conditionscomplete) {
                $telegramApi->sendMessage($referer->chatid, 'Поздравляем вы выполнили все условия и учавсвтуете в конкурсе');
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
        'refererid' => $referer->id,
        'selftoken' => $usertoken,
        'date' => time(),
        'countreferal' => 0,
        'countsubscribes' => 0,
        'conditionscomplete' => false
    ];
    $result = $dbManager->insert_record($tablename, $params);

    $me = $telegramApi->query('getMe');
    $botname = $me->result->username;

    $referallurl = 'https://telegram.me/' . $botname . '?start=' . $usertoken;
    $welcomemessage = "Привет! Подпишись на этот канал и пригласи 3 друзей по этой ссылке:" . $referallurl . ", тогда ты сможешь учавствовать в розыграше!";

    $telegramApi->sendMessage($chat_id, $welcomemessage);
}
elseif ($iamready) {
    $ourchannels = []; //тут указаны chat_id каналов на которые нужно подписаться
    $notsubscribes = [];
    $userdata = "SELECT * FROM userdata WHERE chatid = $chat_id";
    $countsubscribes = $userdata->countsubscribes;
    foreach ($ourchannels as $ourchannel) {
        if (getChatMember($ourchannel, $userid)) {
            $countsubscribes++;
        } else {
            $notsubscribes[] = $ourchannel;
        }
    }

    if ($countsubscribes == count($ourchannels)) {
        $telegramApi->sendMessage($chat_id, 'Красава! Ты подписался на все каналы!');
        if ($userdata->countreferal >= 3 && !$userdata->conditionscomplete) {
            $telegramApi->sendMessage($chat_id, 'Поздравляем вы выполнили все условия и учавсвтуете в конкурсе');
        }
    } else {
        $telegramApi->sendMessage($chat_id, 'Ты ещё не всё подпишись на каналы:' . $notsubscribes);
    }
    //"UPDATE userdata SET countsubscribes = $countsubscribes WHERE id = $userdata->id"
}
else {
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




