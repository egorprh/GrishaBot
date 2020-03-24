<?php

/*
 * Скрипт для первого конкурса, только проверка подписок
 * */

include('vendor/autoload.php');
include('classes/TelegramBot.php');
include('classes/Constants.php');
include('classes/Manage.php');
include('classes/madelineManage.php');
include('classes/BotFunctions.php');

$telegramApi = new TelegramBot();
$db = Manage::set_db_connect();

$ourchannels = Constants::CHANNELS;
$ourchannelsurl = Constants::CHANNELS_URL;
$ourchannelsname = Constants::CHANNELS_NAME;

$message = $telegramApi->getMessage();

$text = $message["message"]["text"]; //Текст сообщения
$userid = $message["message"]["from"]["id"]; //Уникальный идентификатор пользователя
$username = $message["message"]["from"]["username"] ?: ''; //Юзернейм пользователя
$langcode = $message["message"]["from"]["language_code"] ?: 0;
$firstname = $message["message"]["from"]["first_name"] ?: '';

$textarr = explode(' ', $text);
$isstart = in_array('/start', $textarr);

$pressweekrules = strstr($text, '📃УСЛОВИЯ НЕДЕЛИ');
$pressrecalls = strstr($text, '👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ');
$iamsubcribe = strstr($text, '✅Я ПОДПИСАЛСЯ');
$feedback = strstr($text, '📪ОБРАТНАЯ СВЯЗЬ');
$userquestion = strstr($text, 'опрос');
$mailing = strstr($text, 'Рассылка');
$testmod = strstr($text, 'Тест1');

$getcompresults = strstr($text, 'даймнесписокучастников-пароль');
$viewcountmembers = strstr($text, 'скольконародавботе-пароль');

if ($isstart) {

    $welcomemessage = Constants::WELCOME_MESSAGE;
    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

    $telegramApi->sendMessage($userid, $welcomemessage, $reply_markup, 'HTML');

    $issubscribe = $db->query("SELECT EXISTS(SELECT * FROM ezcash_userdata WHERE userid = ?i)", $userid);
    if (current($issubscribe->fetch_row()) == 0) {
        $params = [
            'userid' => $userid,
            'firstname' => $firstname,
            'username' => $username,
            'langcode' => $langcode,
            'timecreated' => time()
        ];

        $db->query('INSERT INTO ezcash_userdata SET ?A[?i, "?s", "?s", "?s", ?i]', $params);
    }

    //TODO Запись в конкурсную таблицу.
    // Вытаскивать реферальный токен из ссылки
    // Добавить поля: selftoken, referertoken, refererid, dudescount
    // Проверять набралось ли у рефферера нужное количество рефералов и если да отправлять ему смс

}  else if ($iamsubcribe) {

    $telegramApi->sendMessage($userid, '⌛ Ща проверим, одну минуту...');

    $issubscribe = $db->query("SELECT EXISTS(SELECT * FROM " . Constants::COMP_TABLE . " WHERE userid = ?i)", $userid);

    if (current($issubscribe->fetch_row()) == 0) {
        $params = [
            'userid' => $userid,
            'username' => $username,
            'countsubscribes' => 0,
            'conditionscomplete' => 0,
        ];

        $db->query('INSERT INTO ' . Constants::COMP_TABLE . ' SET ?A[?i, "?s", ?i, ?i]', $params);
    }

    $notsubscribes = [];
    $countsubscribes = 0;

    foreach ($ourchannels as $key => $ourchannel) {
        //Сюда надо передавать название канала из ссылки t.me/channelname или channel id, и нужны права админа иначе ничего не вернет
        $ispartisipant = madelineManage::get_participant($ourchannel, $userid);
        if (!empty($ispartisipant)) {
            $countsubscribes++;
            unset($ourchannelsurl[$key]);//убираем чтобы сообщение показать с неподписанными каналами
        }
    }

    if ($countsubscribes == count(Constants::CHANNELS)) {
        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
        $telegramApi->sendMessage($userid, '🙏🏻 Дай пять. Ты теперь полноценный участник конкурса.

Итоги будут подведены уже в эти выходные. Мы тебя оповестим и скинем трансляцию розыгрыша.

Удачи!)', $reply_markup);
        $db->query("UPDATE " . Constants::COMP_TABLE . " SET countsubscribes = ?i, conditionscomplete = ?i WHERE userid = ?i", $countsubscribes, 1, $userid);
    } else {
        foreach ($ourchannelsurl as $key => $channel) {
            $channelslinks[] = '➡ <a href="' . $channel . '">' . $ourchannelsname[$key] . '</a>';
        }
        $links = implode("\n\n", $channelslinks);

        $db->query("UPDATE " . Constants::COMP_TABLE . " SET countsubscribes = ?i, conditionscomplete = ?i WHERE userid = ?i", $countsubscribes, 0, $userid);

        $keyboard = [["✅Я ПОДПИСАЛСЯ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

        $message = "😱Ты не доделал. Тебе еще нужно подписаться на: \n\n" . $links . "\n\n Как сделаешь, жми «Я ПОДПИСАЛСЯ» ещё разок.";

        $telegramApi->sendMessage($userid, $message, $reply_markup, 'HTML');
    }

}  else if ($feedback) {

    BotFunctions::feedback($telegramApi, $userid);

} else if ($userquestion) {

    BotFunctions::user_question($telegramApi, $userid, $username, $text);

} else if ($pressweekrules) {

    BotFunctions::press_week_rules($telegramApi, $userid);

} else if ($viewcountmembers) {

    BotFunctions::view_count_members($telegramApi, $userid, $db);

} else if ($pressrecalls) {

    BotFunctions::press_recalls($telegramApi, $userid);

} else if ($getcompresults) {

    BotFunctions::get_comp_results($telegramApi, $userid, $db);

} else if ($mailing) {

    BotFunctions::mailing($db, $userid, $text);

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
    $telegramApi->sendMessage($userid, 'Сообщения будут разосланы всем пользователям в течении 10-15 минут', $reply_markup);

} else if ($testmod) {
    if (BotFunctions::is_admin($userid)) {
        //Здесь место для быстрого тестирования
        $sendresult = $telegramApi->sendMessage(1100510190, $text);
        if ($sendresult == false) {
            $telegramApi->sendMessage($userid, 'Не отправлено');
        }
        $telegramApi->sendMessage($userid, json_encode($sendresult));
    }
} else {

    if (!empty($userid)) {
        $telegramApi->sendMessage($userid, "🤖 Дружище, я не понимаю о чём ты.
        
👉🏻 Если хочешь участвовать в конкурсе - жми\n\"📃УСЛОВИЯ НЕДЕЛИ\".

👉🏻 Если хочешь почитать отзывы о наших бомбических конкурсах - жми\n\"👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ\".
 
👉🏻 Если у тебя есть вопрос или ты что-то хочешь нам сказать - жми\n\"📪ОБРАТНАЯ СВЯЗЬ\"");
    }

}




