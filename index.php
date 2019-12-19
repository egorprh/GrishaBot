<?php

include('vendor/autoload.php');

include('TelegramBot.php');


// получаем сообщение
$telegramApi = new TelegramBot();

$weatherApi = new Weather();

while (true) {
  sleep(1);
  
  $updates = $telegramApi->getUpdates();

//по каждому сообщению пробегаемся
  foreach ($updates as $update) {
  
    if(isset($update->message->location)) {
      //получаем погоду
      $result = $weatherApi->getWeather($update->message->location->latitude, $update->message->location->longitude); 
      switch ($result->weather[0]->main) {
        case 'Clear':
          $response = 'На улице ни облачка. Кайфуй!';
          break;
        case 'Clouds':
          $response = 'Ну там облачно, хз, может и дождь пойти..';
          break;
        case 'Rain':
          $response = 'Там дождь. Может дома останешься?)';
          break;
        default:
          $response = 'В окно посмотри и поймешь чё за погода!';
          break;
      }    
      $telegramApi->sendMessage($update->message->chat->id, $response);
    } else {
    // на каждое сообщение отвечаем
      $telegramApi->sendMessage($update->message->chat->id, "Мне нужна локация. Отправь мне локацию и я скажу что за погода.");
    }
  }
}




  

//https://api.telegram.org/bot<token>/НАЗВАНИЕ_МЕТОДА

//443210917:AAEB-fYxxCwL7WoUgkQJrAsylhm9uhT9iHw

//913fc7774d875954063a8e83d74f2c2b