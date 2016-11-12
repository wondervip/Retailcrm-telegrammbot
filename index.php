<?php
// RetailCRM lib
require 'vendor/autoload.php';

// Telegram URL, token, Access PASS
define('TOKEN', 'telegram_token');
define('URL', 'https://api.telegram.org/bot'.TOKEN.'/');
define('PASS', 'qwerty');

// New CRM client
$client = new RetailCrm\ApiClient(
	'https://testcrm.retailcrm.ru/',
	'retailcrm_api',
	'testcrm_url'
);

// Bot input
$bot = json_decode(file_get_contents("php://input"), true);
$chat = $bot["message"]["chat"]["id"];
$user = $bot["message"]["chat"]["last_name"].' '.$bot["message"]["chat"]["first_name"];
$text = explode(" ", $bot["message"]["text"]);

// Massage to user
$startMsg = "Привет, ".$user."!";
$accessMsg = urlencode("\nЧтобы получить полный доступ, введите пароль в формате:\n /pass 12345678 ");
$stopMsg = urlencode("Хорошего дня, ".$user."!");
$helpMsg = "Страница помощи в разработке";
$orderMsg = urlencode("\nДля просмотра деталей по заказу, введите:\n /order номер_заказа");
$regMsg = "Пароль верный! Теперь у Вас полный доступ".$orderMsg;
$wPass = "Неверный пароль!";
$woNum = "Неверный номер заказа!";
$woComm = urlencode("Неправильная комманда! Попробуйте:\n <b>/order номер_заказа close</b>");

// Check Input
if ($text[0] == "/start"){
  send($chat, $startMsg.$accessMsg);
}
elseif ($text[0] == "/help"){
	send($chat, $helpMsg);
}
elseif ($text[0] == "/stop"){
	send($chat, $stopMsg);
}
elseif ($text[0] == "/pass"){
  if ($text[1] == PASS) {
		$mysqli = connectDB();
		$query = "SELECT * FROM users WHERE id = '$chat'";
		$record = $mysqli->query($query);
		if ($record->num_rows == 0) {
		  $param = array(
	      $id = $chat,
	      $name = $user,
	      $passwd = PASS,
	      $auth = "true"
		  	);
		  $ins = implode("', '", $param);
		  $pr_query = "INSERT INTO users VALUES ('$ins')";
      if ($req = $mysqli->query($pr_query)) {
        $mysqli->close;
        send($chat, $regMsg);
      } else {
      	send($chat, json_encode($mysqli->error));
      }
		} 
		else {
		  $mysqli->close;
		  send($chat, $orderMsg);
	  }
	} 
  elseif (!isset($text[1])) {
  	send($chat, $accessMsg);
  }
	else {
		send($chat, $wPass);
	}
}
elseif ($text[0] == "/order") {
	$mysqli = connectDB();
	$query = "SELECT * FROM users WHERE id = '$chat'";
	$record = $mysqli->query($query);
	if ($record->num_rows == 0) {
	  $mysqli->close;
	  send($chat, $accessMsg);
	} 
	elseif (isset($text[1]) && !isset($text[2])) {
		try {
		  $response = $client->ordersGet($text[1], 'id');
		}
		catch (\RetailCrm\Exception\CurlException $e) {
		  send($chat, json_encode($e->getMessage()));
		}
		if ($response->isSuccessful()) {
			$str_order = urlencode("<b>Заказ:\t</b>".$response->order['id']."<b>\nФИО:\t</b>".$response->order['firstName']."\t".$response->order['lastName']."<b>\nТелефон:\t</b>".$response->order['phone']."<b>\nСумма заказа:\t</b>".$response->order['summ']."\tруб"."<b>\nСумма доставки:\t</b>".$response->order['delivery']['cost']."\tруб"."\n<b>Время\tдоставки:\t</b>c:\t".$response->order['delivery']['time']['from']."\tдо:\t".$response->order['delivery']['time']['to']."\n<b>Адрес:</b>\t".$response->order['delivery']['address']['city'].",\t".$response->order['delivery']['address']['text']."\n<b>Статус:</b>\t".$response->order['status']."\n<b>Статус оплаты:</b>\t".$response->order['paymentStatus']."\n\nДля завершения заказа отправьте: <b>/order\t".$response->order['id']."\tclose</b>");
			send($chat, $str_order);
		} 
		else {
			send($chat, $woNum);
		}
	}
	elseif (isset($text[2])) {
		if ($text[2] == "close") {
			try {
		    $resOne = $client->ordersEdit(array('id' => $text[1], 'paymentStatus' => 'paid', 'status' => 'complete'), 'id');
			}
			catch (\RetailCrm\Exception\CurlException $e) {
			  send($chat, json_encode($e->getMessage()));
			}
			if ($resOne->isSuccessful()) {
			  send($chat, "Статус заказа <b>".$text[1]."</b> успешно изменен на Выполнен и Оплачен!");
			} 
			else {
			  send($chat, "Ошибка! Статус заказа <b>".$text[1]." изменен небыл");
			}
		}
		else {
			send($chat, $woComm);
		}
	}
	else {
		send($chat, $orderMsg);
	}
}
else {
	$mysqli = connectDB();
	$query = "SELECT * FROM users WHERE id = '$chat'";
	$record = $mysqli->query($query);
	if ($record->num_rows == 0) {
	  send($chat, $accessMsg);
	  $mysqli->close;
	}
	else {
		$mysqli->close;
		send($chat, $orderMsg);
	}
}
// send message to user
function send($chat, $msg){
	file_get_contents(URL."sendmessage?parse_mode=HTML&chat_id=".$chat."&text=".$msg);
}
// DB
function connectDB() {
  $mysqli = new mysqli("localhost", "dblogin", "dbpass", "db");
  if ($mysqli->connect_error) {
    die("Connect Error : " . $mysqli->connect_error);
  }
  return $mysqli;
}
?>
