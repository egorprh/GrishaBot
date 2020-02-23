<?php

include('classes/Constants.php');

use Krugozor\Database\Mysql\Mysql as Mysql;

class Manage
{

    public static function set_db_connect() {
        return Mysql::create(Constants::DB_SERVER, Constants::DB_USERNAME, Constants::DB_PASSWORD)
            // Выбор базы данных
            ->setDatabaseName(Constants::DB_NAME)
            // Выбор кодировки
            ->setCharset("utf8");
    }

    public static function get_users_for_sends() {

        $db = self::set_db_connect();

        $sql = "SELECT userid FROM ezcash_send1 WHERE issend = 0";
        $botusers = $db->query($sql);
        $userslist = $botusers->fetch_row_array();

        $usersarr = [];
        foreach ($userslist as $item) {
            $usersarr[] = current($item);
        }

        return $usersarr;
    }
}