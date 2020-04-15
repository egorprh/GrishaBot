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

        $keyboard = [["📃УСЛОВИЯ НЕДЕЛИ"], ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
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

    static function mailing($db, $userid, $text)
    {
        if (self::is_admin($userid)) {
            $messagetext = str_replace('Рассылка: ', '', $text);

            $params['issend'] = 0;
            $params['message'] = json_encode($messagetext);

            $sql = "SELECT userid FROM ezcash_userdata";
            $competitors = $db->query($sql);
            $competitorslist = $competitors->fetch_assoc_array();

            foreach ($competitorslist as $competitor) {
                $params['userid'] = $competitor['userid'];
                $db->query('INSERT INTO ezcash_messagetask SET ?A[?i, "?s", ?i]', $params);
            }
        }
    }

    static function get_comp_results($telegramApi, $userid, $db)
    {
        $telegramApi->sendMessage($userid, "Ща, соберу всех в кучу");

        $sql = "SELECT DISTINCT comp.username FROM " . Constants::COMP_TABLE . " comp
                WHERE comp.conditionscomplete = 1";
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
        //$inline_button1 = ["text" => "👍🏻ОТЗЫВЫ", "url" => 't.me/telesig'];
        //$inline_keyboard = [[$inline_button1]];
        //$keyboard = ["inline_keyboard"=>$inline_keyboard];
        //$replyMarkup = json_encode($keyboard);
        //$telegramApi->sendMessage($userid, '👇🏻👇🏻👇🏻', $replyMarkup);
    }

    static function press_week_rules($db, $telegramApi, $userid)
    {
        //foreach ($ourchannels as $channel) {
        //$channelslinks[] = 't.me/' . $channel;
        //}
        //$links = implode(', ', $channelslinks);

        //Формируем реферальную ссылку для пользователя
        $me = $telegramApi->query('getMe');
        $botname = $me->result->username;
        $record = $db->query("SELECT * FROM ezcash_userdata WHERE userid = ?i", $userid);
        $record = $record->fetch_assoc_array()[0];

        $referallurl = 'https://telegram.me/' . $botname . '?start=' . $record['refcode'];

        $text = Constants::CONDITIONS_TEXT;
        $default = '{reflink}';
        $replace = $referallurl;
        $messagetext = str_replace($default, $replace, $text);

        //$messagetext = Constants::WAIT_RESULT_TEXT;

        $keyboard = [["✅Я ПОДПИСАЛСЯ"], /*["📃УСЛОВИЯ НЕДЕЛИ"],*/ ["👍🏻ОТЗЫВЫ И РЕЗУЛЬТАТЫ"], ["📪ОБРАТНАЯ СВЯЗЬ"]];
        $reply_markup = $telegramApi->replyKeyboardMarkup($keyboard);
        $telegramApi->sendMessage($userid, $messagetext, $reply_markup, 'HTML');
    }

    static function is_admin($userid)
    {
        return in_array($userid, Constants::ADMINS);
    }
    
    static function update_comp_record($db, $params, $userid)
    {
        $params['userid'] = $userid;

        //Делаем строку SET для запроса
        $setstrarr = [];
        foreach ($params as $key => $param) {
            switch (gettype($param)) {
                case 'integer':
                    $setstrarr[] = '?i';
                    break;
                case 'string':
                    $setstrarr[] = '"?s"';
                    break;
                default:
                    $param[$key] = json_encode($param);
                    $setstrarr[] = '"?s"';
                    break;
            }
        }
        $setstr = implode(', ', $setstrarr);

        //Проверяем есть ли запись для этого юзера
        $record = $db->query("SELECT * FROM " . Constants::COMP_TABLE . " WHERE userid = ?i", $userid);
        $record = $record->fetch_assoc_array()[0];

        //Если нет, то добавляем
        if (empty($record)) {
            $db->query('INSERT INTO ' . Constants::COMP_TABLE . ' SET ?A[' . $setstr . ']', $params);
        }
        //Если есть, то обновляем
        else {
            $db->query('UPDATE ' . Constants::COMP_TABLE . ' SET ?A[' . $setstr . '] WHERE id = ?i', $params, $record['id']);
        }

        $record2 = $db->query("SELECT * FROM " . Constants::COMP_TABLE . " WHERE userid = ?i", $userid);
        $record2 = $record2->fetch_assoc_array()[0];

        return $record2;
    }

    static function is_referrals_complete($db, $referrerid)
    {
        //1. Получаем рефералов чувака
        $referrals = $db->query("SELECT * FROM " . Constants::COMP_TABLE . " comp 
                                 LEFT JOIN ezcash_userdata usdata ON usdata.userid = comp.userid
                                 WHERE usdata.referrerid = ?i", $referrerid);
        $referrals = $referrals->fetch_assoc_array();

        if (empty($referrals)) {
            return [false, []];
        }

        //2. Форычом проверяем выполнили условия
        // - невыполнивших заносим в массив
        $noncompletenames = [];
        $countcomplete = 0;
        foreach ($referrals as $referral) {
            if ($referral['conditionscomplete'] == 0) {
                $noncompletenames[] = !empty($referral['username']) ? $referral['username'] : 0;
            }
            if ($referral['conditionscomplete'] == 1) {
                $countcomplete ++;
            }
        }

        //3. Если массив пустой, то все выполнил
        $complete = ($countcomplete >= Constants::COUNT_SUBSCRIBERS);

        return [$complete, $noncompletenames];
    }

    static function get_referrerid($db, $userid)
    {
        $referreid = $db->query("SELECT referrerid FROM ezcash_userdata WHERE userid = ?i", $userid);

        return $referreid->fetch_assoc_array()[0]['referrerid'];
    }

    static function is_conditions_complete($db, $userid)
    {
        list($referralscomplete, $uncompletenames) = self::is_referrals_complete($db, $userid);

        $countsubscriptions = $db->query('SELECT countsubscriptions FROM ' . Constants::COMP_TABLE . ' WHERE userid = ?i', $userid);
        $countsubscriptions = $countsubscriptions->fetch_assoc_array()[0]['countsubscriptions'];

        return ($referralscomplete && $countsubscriptions >= Constants::COUNT_SUBSCRIPTIONS);
    }
}
