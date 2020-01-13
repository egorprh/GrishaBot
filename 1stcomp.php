<?php

/*
 * Скрипт для первого конкурса, только проверка подписок
 * */

echo 'BotPag';

include('vendor/autoload.php');
include('classes/TelegramBot.php');
include('classes/Constants.php');
include('classes/madelineManage.php');

use Krugozor\Database\Mysql\Mysql as Mysql;

$ourchannels = Constants::CHANNELS;

$telegramApi = new TelegramBot();

// Соединение с СУБД и получение объекта-"обертки" над "родным" mysqli
$db = Mysql::create(Constants::DB_SERVER, Constants::DB_USERNAME, Constants::DB_PASSWORD)
    // Выбор базы данных
    ->setDatabaseName(Constants::DB_NAME)
    // Выбор кодировки
    ->setCharset("utf8");

$ourchannels = Constants::CHANNELS;

$message = $telegramApi->getMessage();

$text = $message["message"]["text"]; //Текст сообщения
$chat_id = $message["message"]["chat"]["id"]; //Уникальный идентификатор пользователя
$name = $message["message"]["from"]["username"]; //Юзернейм пользователя

$textarr = explode(' ', $text);
$isstart = in_array('/start', $textarr);
$iamsubcribe = in_array('подписался', $textarr);

if ($isstart) {

    $issubscribe = $db->query("SELECT EXISTS(SELECT * FROM firstcomp WHERE userid = ?i)", $chat_id);
    if (current($issubscribe->fetch_row()) == 0) {
        $params = [
            'username' => $name,
            'userid' => $chat_id,
            'countsubscribes' => 0,
            'conditionscomplete' => 0,
        ];

        $db->query('INSERT INTO `firstcomp` SET ?A["?s", ?i, ?i, ?i]', $params);
    }

    foreach ($ourchannels as $channel) {
        $channelslinks[] = 't.me/' . $channel;
    }
    $links = implode(', ', $channelslinks);

    $welcomemessage = "Привет! Подпишись на каналы " . $links . " тогда ты сможешь учавствовать в розыграше! После того как подпишешься, обязательно приди сюда и напиши что 'Я подписался', чтобы мы проверили и ты смог учавcтовать в розыгрыше!";

    $telegramApi->sendMessage($chat_id, $welcomemessage);

} else if ($iamsubcribe) {

    $telegramApi->sendMessage($chat_id, 'Ща проверим, одну минуту...');

    $notsubscribes = [];
    $countsubscribes = 0;

    foreach ($ourchannels as $key => $ourchannel) {
        //Сюда надо передавать название канала из ссылки t.me/channelname или channel id, и нужны права админа иначе ничего не вернет
        $partisipants = madelineManage::get_participants($ourchannel);
        foreach ($partisipants as $partisipant) {
            if ($partisipant['user']['id'] == $chat_id) {
                $countsubscribes++;
                unset($ourchannels[$key]);
            }
        }
    }

    if ($countsubscribes == count($ourchannels)) {
        $telegramApi->sendMessage($chat_id, 'Красава! Ты подписался на все каналы!');
        $db->query("UPDATE `firstcomp` SET countsubscribes = ?i, conditionscomplete = ?i  WHERE userid = ?i",count($ourchannels), 1, $chat_id);
    } else {
        foreach ($ourchannels as $channel) {
            $channelslinks[] = 't.me/' . $channel;
        }
        $links = implode(', ', $channelslinks);

        $db->query("UPDATE `firstcomp` SET countsubscribes = ?i  WHERE userid = ?i", $countsubscribes, $chat_id);

        $telegramApi->sendMessage($chat_id, 'Ты ещё не всё. Подпишись на каналы:' . $links . ' Затем снова напиши сюда "Я подписался"');
    }


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




