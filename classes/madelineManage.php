<?php

//Подключение Madeline
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
include 'Constants.php';

class madelineManage {

    public static function get_participants($channel) {

        $madelineSettings = [];
        $madelineSettings['app_info']['api_id'] = Constants::TG_API_ID;
        $madelineSettings['app_info']['api_hash'] = Constants::TG_API_HASH;

        $MadelineProto = new \danog\MadelineProto\API('session.madeline', $madelineSettings);
        $MadelineProto->start();

        $userschatinfo = $MadelineProto->get_pwr_chat($channel, true);
        return $userschatinfo["participants"];
    }
}
