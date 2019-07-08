<?php 

ob_start();


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

		// Тут будет вызов парсера

	}

}

file_put_contents("output.txt", ob_get_clean());


echo json_encode($answer);

?>