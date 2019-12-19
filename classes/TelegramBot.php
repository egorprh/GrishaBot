<?php

class TelegramBot
{

    protected $token = '443210917:AAEgqEA_MdIXxXWylu7EX4IEJLbUHo8inME';

    public function query($method, $params = [])
    {

        $url = "https://api.telegram.org/bot";

        $url .= $this->token;

        $url .= '/' . $method;

        if (!empty($params)) {

            $url .= '?' . http_build_query($params);
        }

        $client = new \GuzzleHttp\Client(['base_uri' => $url]);

        $result = $client->request('GET');

        return json_decode($result->getBody());

    }


    public function sendMessage($chat_id, $text)
    {

        $this->query('sendMessage', [
            'text' => $text,
            'chat_id' => $chat_id
        ]);

    }

    public function getMessage() {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        return $data;
    }


}