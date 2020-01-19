<?php

include('classes/Constants.php');

class TelegramBot
{

    protected $token = Constants::BOT_TOKEN_PROD;

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

    public function sendMessage($chat_id, $text, $reply_markup = '', $parsemode = '', $disablepreview = true)
    {

        $this->query('sendMessage', [
            'text' => $text,
            'chat_id' => $chat_id,
            'parse_mode' => $parsemode,
            'reply_markup' => $reply_markup,
            'disable_web_page_preview' => $disablepreview
        ]);

    }

    public function getMessage() {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        return $data;
    }

    public function replyKeyboardMarkup($keyboardarr, $resize_keyboard = true, $one_time_keyboard = false, $selective = false) {

        $keyboardobject = (object) [
            'keyboard' => $keyboardarr,
            'resize_keyboard' => $resize_keyboard,
            'one_time_keyboard' => $one_time_keyboard,
            'selective' => $selective
        ];

        return json_encode($keyboardobject);
    }

}