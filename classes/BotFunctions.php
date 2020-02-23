<?php

include('classes/Constants.php');

class BotFunctions
{

    static function feedback($telegramApi, $userid)
    {
        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

        $message = '🤟🏻Салют, дружище.

Если ты здесь, то у тебя есть вопрос или проблема. Давай ее обсудим.

🚨Напиши в точности, как я прошу - Вопрос: текст твоего вопроса или проблемы.

Если не напишешь слово Вопрос, то бот не сможет отправить нам твою проблему, а мы не сможем ее решить.

Спасибо за внимание. Обнял.';

        $telegramApi->sendMessage($userid, $message, $reply_markup);
    }

    static function view_count_members($telegramApi, $userid, $db)
    {
        $sql = "SELECT COUNT(userid) FROM ezcash_userdata";
        $countmembers = $db->query($sql);
        $countmembers = $countmembers->fetch_row();

        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);

        $telegramApi->sendMessage($userid, current($countmembers), $reply_markup);
    }

    static function user_question($telegramApi, $userid, $username, $text)
    {
        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
        $telegramApi->sendMessage($userid, 'Вопрос приняли, друг. Ожидай ответа.', $reply_markup);

        foreach (Constants::ADMINS as $admin) {
            $telegramApi->sendMessage($admin, 'От пользователя @' . $username . ' поступил ' . $text, $reply_markup);
        }
    }

    static function get_comp_results($telegramApi, $userid, $db)
    {
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
    }

    static function press_recalls($telegramApi, $userid)
    {
        $messagetext = 'Все отзывы и результаты предыдущих розыгрышей смотри на канале: <a href="t.me/EZCashOtzivi">Отзывы EZCash</a>';

        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
        $telegramApi->sendMessage($userid, $messagetext, $reply_markup, 'HTML');

        //Это просто пример инлайновой клавиатуры
//    $inline_button1 = ["text" => "👍🏻ОТЗЫВЫ", "url" => 't.me/telesig'];
//    $inline_keyboard = [[$inline_button1]];
//    $keyboard = ["inline_keyboard"=>$inline_keyboard];
//    $replyMarkup = json_encode($keyboard);
//
//    $telegramApi->sendMessage($userid, '👇🏻👇🏻👇🏻', $replyMarkup);
    }

    static function press_week_rules($telegramApi, $userid)
    {
        //    foreach ($ourchannels as $channel) {
//        $channelslinks[] = 't.me/' . $channel;
//    }
//    $links = implode(', ', $channelslinks);

        $messagetext = Constants::WAIT_RESULT_TEXT;

        $keyboard = [["✅Я ПОДПИСАЛСЯ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
        $telegramApi->sendMessage($userid, $messagetext, $reply_markup, 'HTML');
    }

}
