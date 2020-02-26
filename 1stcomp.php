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

$pressweekrules = strstr($text, 'УСЛОВИЯ');
$pressrecalls = strstr($text, 'ОТЗЫВЫ');
$iamsubcribe = strstr($text, 'ПОДПИСАЛСЯ');
$feedback = strstr($text, 'ОБРАТНАЯ СВЯЗЬ');
$userquestion = strstr($text, 'опрос');

$getcompresults = strstr($text, 'даймнесписокучастников-пароль');
$newcomp = strstr($text, 'отправьуведомленияоновомконкурсе-пароль');
$compresults = strstr($text, 'отправьуведомленияоготовностирезультатов-пароль');
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

}  else if ($iamsubcribe) {

    $telegramApi->sendMessage($userid, '⌛ Ща проверим, одну минуту...');

    $issubscribe = $db->query("SELECT EXISTS(SELECT * FROM " . Constants::COMP_TABLE . " WHERE userid = ?i)", $userid);

    if (current($issubscribe->fetch_row()) == 0) {
        $params = [
            'userid' => $userid,
            'countsubscribes' => 0,
            'conditionscomplete' => 0,
        ];

        $db->query('INSERT INTO ' . Constants::COMP_TABLE . ' SET ?A[?i, ?i, ?i]', $params);
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

}  else if ($newcomp || $compresults) {

    if ($newcomp) {
        $messagetext =  "🤟🏻⏰ТЫ ТОЧНО НИЧЕГО НЕ УПУСКАЕШЬ?

У нас новый розыгрыш подъехал. Жми кнопку «УСЛОВИЯ НЕДЕЛИ» и выигрывай ценные призы.

Удачи и ещё раз удачи!
💣Мы запустили новый конкурс!
🎁Жми 'УСЛОВИЯ НЕДЕЛИ', чтобы забрать свой выигрыш!";

    } else if ($compresults) {
        $messagetext = "🎉Мы подвели итоги конкурса, результат смотри здесь: <a href=\"t.me/EZCashOtzivi\">Отзывы EZCash</a>";
    }

    $params['issend'] = 0;
    $params['message'] = json_encode($messagetext);

    $sql = "SELECT userid FROM ezcash_userdata";
    $competitors = $db->query($sql);
    $competitorslist = $competitors->fetch_assoc_array();

    foreach ($competitorslist as $competitor) {
        $params['userid'] = $competitor['userid'];
        $db->query('INSERT INTO ezcash_messagetask SET ?A[?i, "?s", ?i]', $params);
    }

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
    $telegramApi->sendMessage($userid, 'Сообщения будут разосланы всем пользователям в течении 10-15 минут', $reply_markup);

} else if ($feedback) {

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

} else {

    if (!empty($userid)) {
        $telegramApi->sendMessage($userid, "🤖 Дружище, я не понимаю о чём ты.
        
👉🏻 Если хочешь участвовать в конкурсе - жми\n\"📃УСЛОВИЯ НЕДЕЛИ\".

👉🏻 Если хочешь почитать отзывы о наших бомбических конкурсах - жми\n\"👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ\".
 
👉🏻 Если у тебя есть вопрос или ты что-то хочешь нам сказать - жми\n\"📪ОБРАТНАЯ СВЯЗЬ\"");
    }

}




