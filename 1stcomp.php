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
    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
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

} else if ($pressweekrules) {

//    foreach ($ourchannels as $channel) {
//        $channelslinks[] = 't.me/' . $channel;
//    }
//    $links = implode(', ', $channelslinks);

    $messagetext = Constants::CONDITIONS_TEXT;

    $keyboard = [["✅Я ПОДПИСАЛСЯ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
    $telegramApi->sendMessage($userid, $messagetext, $reply_markup, 'HTML');

} else if ($iamsubcribe) {

    $telegramApi->sendMessage($userid, '⌛ Ща проверим, одну минуту...');

    $issubscribe = $db->query("SELECT EXISTS(SELECT * FROM ezcash_comp2 WHERE userid = ?i)", $userid);
    if (current($issubscribe->fetch_row()) == 0) {
        $params = [
            'userid' => $userid,
            'countsubscribes' => 0,
            'conditionscomplete' => 0,
        ];

        $db->query('INSERT INTO ezcash_comp2 SET ?A[?i, ?i, ?i]', $params);
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
        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
        $telegramApi->sendMessage($userid, '🙏🏻 Дай пять. Ты теперь полноценный участник конкурса.

Итоги будут подведены уже в эти выходные. Мы тебя оповестим и скинем трансляцию розыгрыша.

Удачи!)', $reply_markup);
        $db->query("UPDATE ezcash_comp2 SET countsubscribes = ?i, conditionscomplete = ?i  WHERE userid = ?i", $countsubscribes, 1, $userid);
    } else {
        foreach ($ourchannelsurl as $key => $channel) {
            $channelslinks[] = '➡ <a href="' . $channel . '">' . $ourchannelsname[$key] . '</a>';
        }
        $links = implode("\n\n", $channelslinks);

        $db->query("UPDATE ezcash_comp2 SET countsubscribes = ?i  WHERE userid = ?i", $countsubscribes, $userid);

        $keyboard = [["✅Я ПОДПИСАЛСЯ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

        $message = "😱Ты не доделал. Тебе еще нужно подписаться на: \n\n" . $links . "\n\n Как сделаешь, жми «Я ПОДПИСАЛСЯ» ещё разок.";

        $telegramApi->sendMessage($userid, $message, $reply_markup, 'HTML');
    }

} else if ($pressrecalls) {

    $messagetext = 'Все отзывы и результаты предыдущих розыгрышей смотри на канале: <a href="t.me/EZCashOtzivi">Отзывы EZCash</a>';

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
    $telegramApi->sendMessage($userid, $messagetext, $reply_markup, 'HTML');

//    $inline_button1 = ["text" => "👍🏻ОТЗЫВЫ", "url" => 't.me/telesig'];
//    $inline_keyboard = [[$inline_button1]];
//    $keyboard = ["inline_keyboard"=>$inline_keyboard];
//    $replyMarkup = json_encode($keyboard);
//
//    $telegramApi->sendMessage($userid, '👇🏻👇🏻👇🏻', $replyMarkup);

} else if ($getcompresults) {

    $telegramApi->sendMessage($userid, "Ща, соберу всех в кучу");

    $sql = "SELECT DISTINCT u.username FROM ezcash_userdata u 
            LEFT JOIN ezcash_comp2 comp1 ON comp1.userid = u.userid
            WHERE comp1.conditionscomplete = 1";
    $competitors = $db->query($sql);
    $competitorslist = $competitors->fetch_row_array();

    $outArray = [];
    foreach ($competitorslist as $item) {
        foreach ($item as $item2) {
            $outArray[] = $item2;
        }
    }

    $competitorsliststr = implode("\n", $outArray);

    $file = '../competitors.csv';
    $bom = "\xEF\xBB\xBF";
    $bytesCount = file_put_contents($file, $bom . $competitorsliststr);
    if ($bytesCount === false) {
        $telegramApi->sendMessage($userid, "При сохранении данных произошла ошибка!");
    }

    $telegramApi->sendMessage($userid, "Ссылка на скачивание: https://yaga.space/ezcashbot/competitors.csv Если сразу не скачается, клацни правой кнопкой мыши и нажми 'Сохранить как'");

} else if ($newcomp || $compresults) {

    $sql = "SELECT userid FROM ezcash_userdata";
    $competitors = $db->query($sql);
    $competitorslist = $competitors->fetch_row_array();

    $outArray = [];
    foreach ($competitorslist as $item) {
        foreach ($item as $item2) {
            $outArray[] = $item2;
        }
    }

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

    foreach ($outArray as $memberid) {
        usleep(100000);
        if ($newcomp) {
            $telegramApi->sendMessage($memberid, "⏰ТЫ ТОЧНО НИЧЕГО НЕ УПУСКАЕШЬ?

У нас новый розыгрыш подъехал. Жми кнопку «УСЛОВИЯ НЕДЕЛИ» и выигрывай ценные призы.

Удачи и ещё раз удачи!", $reply_markup);
        } else if ($compresults) {
            $telegramApi->sendMessage($memberid, "🎉Мы подвели итоги конкурса, результат смотри здесь: <a href=\"t.me/EZCashOtzivi\">Отзывы EZCash</a>", $reply_markup, 'HTML');
        }
    }

    $telegramApi->sendMessage($userid, "Сообщения успешно отправлены.");

} else if ($feedback) {

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

    $message = '🤟🏻Салют, дружище.

Если ты здесь, то у тебя есть вопрос или проблема. Давай ее обсудим.

🚨Напиши в точности, как я прошу - Вопрос: текст твоего вопроса или проблемы.

Если не напишешь слово Вопрос, то бот не сможет отправить нам твою проблему, а мы не сможем ее решить.

Спасибо за внимание. Обнял.';

    $telegramApi->sendMessage($userid, $message, $reply_markup);

} else if ($userquestion) {

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
    $telegramApi->sendMessage($userid, 'Вопрос приняли, друг. Ожидай ответа.', $reply_markup);

    foreach (Constants::ADMINS as $admin) {
        $telegramApi->sendMessage($admin, 'От пользователя @' . $username . ' поступил ' . $text, $reply_markup);
    }

} else if ($viewcountmembers) {
    $sql = "SELECT COUNT(userid) FROM ezcash_userdata";
    $countmembers = $db->query($sql);
    $countmembers = $countmembers->fetch_row();

    $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
    $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

    $telegramApi->sendMessage($userid, current($countmembers), $reply_markup);
} else {
//    $randommessages = [
//        'Ничто не дается так дешево как хочется',
//        'Господи, сколько уже не сделано, а сколько еще предстоит не сделать!',
//        'Умными мы называем людей, которые с нами соглашаются.',
//        'Каждый человек стоит столько, сколько он сделал, минус тщеславие.',
//        'Когда женщине нечего сказать, это не значит, что она будет молчать',
//        'Если Вы взглянули в зеркало, но никого там не обнаружили – Вы неотразимы!',
//        'Лучше сделать и жалеть, чем жалеть, что не сделал',
//        'Спи быстрей – подушка нужна!',
//        'Оптимист верит, что мы живем в лучшем из миров. Пессимист боится, что так и есть.',
//        'Разговор с женщиной есть потеря времени. Вопрос только в том, насколько это приятно',
//        'В жизни всегда есть место поводу!',
//        'Счастье – это когда утром очень хочется на работу, а вечером очень хочется домой',
//        'Каждый имеет фотографическую память. Не у каждого есть пленка',
//        'Картина Репина «Приплыли!» - всю ночь гребли, а лодку отвязать забыли',
//        'Обьективная реальность есть бред, вызванный недостатком алкоголя в крови.',
//        'Если Вам нечего делать, то не надо делать это здесь!',
//        'Жизнь такова, какова она есть, и больше никакова. Каково?',
//        'Человека охотнее всего съедают те, кто его не переваривает.',
//        'Фарш невозможно провернуть назад. Второе начало термодинамики.',
//        'Дегенератор мыслей',
//        'Любопытство не порок, а способ образования'
//    ];
    if (!empty($userid)) {
        $telegramApi->sendMessage($userid, "🤖 Дружище, я не понимаю о чём ты.
        
👉🏻 Если хочешь участвовать в конкурсе - жми\n\"📃УСЛОВИЯ НЕДЕЛИ\".

👉🏻 Если хочешь почитать отзывы о наших бомбических конкурсах - жми\n\"👍🏻ОТЗЫВЫ\".
 
👉🏻 Если у тебя есть вопрос или ты что-то хочешь нам сказать - жми\n\"📪ОБРАТНАЯ СВЯЗЬ\"");
    }
}




