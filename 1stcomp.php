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

//Отладочный режим
//if (!BotFunctions::is_admin($userid)) {
//    $telegramApi->sendMessage($userid, 'Ведутся технические работы, приходите позже');
//} else {
if ($isstart) {

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

    //Проверяем подписан ли чувак
    $issubscribe = $db->query("SELECT EXISTS(SELECT * FROM ezcash_userdata WHERE userid = ?i)", $userid);

    if (current($issubscribe->fetch_row()) == 0) {
        $welcomemessage = Constants::WELCOME_MESSAGE;
        $telegramApi->sendMessage($userid, $welcomemessage, $reply_markup, 'HTML');

        switch (count($textarr)) {
            case 2:
                //Получаем токен того, кто пригласил
                $referrertoken = $textarr[1];
                //По токену получаем самого реферрера
                $referrer = $db->query("SELECT * FROM ezcash_userdata WHERE refcode = '?s'", $referrertoken);
                $referrer = $referrer->fetch_assoc_array()[0];
                // Отправляем ему смс, что по его ссылке перешёл пользователь
                $referallmessage = "По вашей ссылке пришел пользователь @" . $username;
                $telegramApi->sendMessage($referrer['userid'], $referallmessage, $reply_markup, 'HTML');

                //Обновляем запись в КОНКУРСНОЙ таблице, что +1 реферал
                $countsubscribers = $referrer['countsubscribers'] + 1;
                $referrercomprecord = BotFunctions::update_comp_record($db, ['username' => $referrer['username'], 'countsubscribers' => $countsubscribers], $referrer['userid']);
                //Проверяем выполнил ли реферер все условия конкурса и если да, то говорим ему что он красавчик
                //Т.е. если он набрал нужное количество рефераллов, подписок и нет отметки о том что он всё выполнил
                if ($countsubscribers == Constants::COUNT_SUBSCRIBERS &&
                    $referrercomprecord['countsubscriptions'] >= Constants::COUNT_SUBSCRIPTIONS &&
                    $referrercomprecord['conditionscomplete'] == 0
                ) {
                    $telegramApi->sendMessage($referrer['userid'], Constants::SUCCESS_MESSAGE, $reply_markup);
                    $referrercomprecord = BotFunctions::update_comp_record($db, ['conditionscomplete' => 1], $referrer['userid']);
                }

                break;
            case 1:
                //Если токена в ссылке не было, то значит пригласил админ
                $referertoken = 0;
                $referrer = 0;
                break;
        }

        // Генерируем чуваку собственный рефков
        $refcode = substr(md5(microtime()), rand(0, 26), 10);

        //Записываем чувака в основную базу
        $params = [
            'userid' => $userid,
            'firstname' => $firstname,
            'username' => $username,
            'langcode' => $langcode,
            'timecreated' => time(),
            'refcode' => $refcode,
            'referrerid' => !empty($referrer['userid']) ? $referrer['userid'] : 0
        ];
        $db->query('INSERT INTO ezcash_userdata SET ?A[?i, "?s", "?s", "?s", ?i, "?s", ?i]', $params);
    } else {
        $telegramApi->sendMessage($userid, 'Ты уже стартовал, хитрец.)');
    }

} else if ($iamsubcribe) {

    //Получаем конкурсную запись
    $comprecord = BotFunctions::update_comp_record($db, ['username' => $username], $userid);

    $telegramApi->sendMessage($userid, '⌛ Ща проверим, одну минуту...');

    $notsubscribes = [];
    $countsubscriptions = 0;

    //Проверяем подписался ли он на все каналы
    foreach ($ourchannels as $key => $ourchannel) {
        //Сюда надо передавать название канала из ссылки t.me/channelname или channel id, и нужны права админа иначе ничего не вернет
        $ispartisipant = madelineManage::get_participant($ourchannel, $userid);
        if (!empty($ispartisipant)) {
            $countsubscriptions++;
            unset($ourchannelsurl[$key]);//убираем чтобы сообщение показать с неподписанными каналами
        }
    }
    $params['countsubscriptions'] = $countsubscriptions;

    $allsubscribe = ($countsubscriptions == Constants::COUNT_SUBSCRIPTIONS); // На все ли каналы подписался
    $allinvite = ($comprecord['countsubscribers'] == Constants::COUNT_SUBSCRIBERS); // Пригласил ли необходимое количество человек

    $params['conditionscomplete'] = 0;
    $keyboard = [["✅Я ПОДПИСАЛСЯ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    switch (true) {
        // Если всё сделал: формируем сообщение об успехе и обновляем данные в таблице
        case ($allsubscribe && $allinvite):
            $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
            $message = Constants::SUCCESS_MESSAGE;
            $params['conditionscomplete'] = 1;
            break;
        // Если подписался, но не всех пригласил
        case ($allsubscribe && !$allinvite):
            $message = '🙏🏻 Дай пять! Ты подписался на все каналы.
             
👉🏼 Теперь тебе осталось пригласить ' . (Constants::COUNT_SUBSCRIBERS - $comprecord['countsubscribers']) . ' друга.

Реферральную ссылку ты можешь найти в условиях конкурса.';
            $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
            break;
        // Если всех пригласил, но не на всё подписался
        case ($allinvite && !$allsubscribe):
            foreach ($ourchannelsurl as $key => $channel) {
                $channelslinks[] = '➡ <a href="' . $channel . '">' . $ourchannelsname[$key] . '</a>';
            }
            $links = implode("\n\n", $channelslinks);
            $params['countsubscriptions'] = $countsubscriptions;
            $message = "😱Ты не доделал. Тебе еще нужно подписаться на: \n\n" . $links . "\n\n Как сделаешь, жми «Я ПОДПИСАЛСЯ» ещё разок.";
            break;
        // Если не пригласил и не подписался
        case (!$allsubscribe && !$allinvite):
            foreach ($ourchannelsurl as $key => $channel) {
                $channelslinks[] = '➡ <a href="' . $channel . '">' . $ourchannelsname[$key] . '</a>';
            }
            $links = implode("\n\n", $channelslinks);
            $params['countsubscriptions'] = $countsubscriptions;
            $message = 'Тебе надо ещё пригласить <b>' . (Constants::COUNT_SUBSCRIBERS - $comprecord['countsubscribers']) . "</b> друга и подписаться на: \n\n" . $links . "\n\n Как сделаешь, жми «Я ПОДПИСАЛСЯ» ещё разок.";
            break;
        default:
            $message = 'Что-то пошло не так... Нажми "Обратная связь" и напиши нам. Во всём разберёмся.';
            break;
    }

    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
    BotFunctions::update_comp_record($db, $params, $userid);
    $telegramApi->sendMessage($userid, $message, $reply_markup, 'HTML');

} else if ($feedback) {

    BotFunctions::feedback($telegramApi, $userid);

} else if ($userquestion) {

    BotFunctions::user_question($telegramApi, $userid, $username, $text);

} else if ($pressweekrules) {

    BotFunctions::press_week_rules($db, $telegramApi, $userid);

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

//Если нет ника у пользователя, то надо ему об этом сказать
if (empty($username)) {
    $usernamemessage = Constants::NONICKNAME_MESSAGE;
    $telegramApi->sendMessage($userid, $usernamemessage);
}
//}




