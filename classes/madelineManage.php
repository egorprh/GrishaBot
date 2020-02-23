<?php

//Подключение Madeline
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
include 'Constants.php';

class madelineManage
{

    public static function connect()
    {
        $madelineSettings = [];
        $madelineSettings['app_info']['api_id'] = Constants::TG_API_ID;
        $madelineSettings['app_info']['api_hash'] = Constants::TG_API_HASH;

        $MadelineProto = new \danog\MadelineProto\API('session.madeline', $madelineSettings);

        return $MadelineProto;
    }

    public static function get_participants($channel)
    {
        $MadelineProto = self::connect();
        $MadelineProto->start();
        $userschatinfo = $MadelineProto->get_pwr_chat($channel, true);
        return $userschatinfo["participants"];
    }

    public static function get_participant($channel, $userid)
    {
        $MadelineProto = self::connect();
        $MadelineProto->start();

        try {
            $data = $MadelineProto->channels->getParticipant(['channel' => $channel, 'user_id' => $userid]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        }

        return !empty($data) ? $data : [];
    }

    public static function sendMessage ($chat_id, $text, $reply_markup = '') {

        $MadelineProto = self::connect();
        $MadelineProto->start();

        try {
        $Updates = $MadelineProto->messages->sendMessage([
            //'no_webpage' => Bool,
            //'silent' => Bool,
            //'background' => Bool,
            //'clear_draft' => Bool,
            'peer' => $chat_id,
            //'reply_to_msg_id' => int,
            'message' => $text,
            //'reply_markup' => ReplyMarkup,
            //'entities' => [MessageEntity, MessageEntity],
            //'parse_mode' => 'string',
            //'schedule_date' => int,
            ]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
        }

        return $Updates;
    }

    public static function getFullInfo($id) {

        $MadelineProto = self::connect();
        $MadelineProto->start();

        try {
            $fullinfo = $MadelineProto->getFullInfo($id);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            return false;
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
            return false;
        }

        return !empty($fullinfo) ? $fullinfo : false;
    }
}
