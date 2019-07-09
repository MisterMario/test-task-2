<?php 

require_once("parser.class.php");


$date = json_decode(file_get_contents("php://input"), true);
$answer = array("success" => false, "message" => "");
$date_pattern = "/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/";


if (!isset($date["from"]) || !isset($date["to"]))
	$answer["message"] = "Ошибка! Переданы не все параметры!";

elseif (!preg_match($date_pattern, $date["from"]) || !preg_match($date_pattern, $date["to"]))
	$answer["message"] = "Ошибка! Некорректная дата!";

else
{

	$fromDate = array();
	$toDate = array();
	preg_match_all("/[0-9]{1,}/", $date["from"], $fromDate);
	preg_match_all("/[0-9]{1,}/", $date["to"], $toDate);
	$fromDate = $fromDate[0];
	$toDate = $toDate[0];
	$intFromDate = (int)strtotime($fromDate[2]."-".$fromDate[1]."-".$fromDate[0]." 00:00:00");
	$intToDate = (int)strtotime($toDate[2]."-".$toDate[1]."-".$toDate[0]." 00:00:00");

	if (!checkdate($fromDate[1], $fromDate[0], $fromDate[2]) || 
		!checkdate($toDate[1], $toDate[0], $toDate[2]))
		$answer["message"] = "Ошибка! Одна из-за введенных дат не существует!";

	elseif ($intFromDate > $intToDate)
		$answer["message"] = "Ошибка! Дата начала периода не быть позднее даты конца периода!";

	elseif ($intFromDate > time() || $intToDate > time())
		$answer["message"] = "Ошибка! Одна из введенных дат еще не наступила!";

	else
	{
		ob_start();
		echo date("[H:i d:m:Y]:")."\r\n";
		echo "Запрошены данные с [${date["from"]}] по [${date["to"]}]\r\n";
		$parser = Parser::getInstance(true);
		$data_arr = $parser->getData($date["from"], $date["to"]);

		if (!$data_arr)
			echo "Произошла ошибка при получении данных! Источник сообщения: class Parser!\r\n";
		file_put_contents("log.txt", ob_get_clean()."\r\n", FILE_APPEND);

		if ($data_arr)
		{
			$answer["success"] = true;
			$answer["message"] = array(
				"date_arr" => array(),
				"val_arr" => array(),
			);

			foreach ($data_arr as $date => $value) {
				$answer["message"]["date_arr"][] = $date;
				$answer["message"]["val_arr"][] = $value;
			}
		}
		else $answer["message"] = "Ошибка! Не удается получить данные с удаленного сервера!";

	}

}

echo json_encode($answer);

?>