<?php 

ob_start();


$date = json_decode(file_get_contents("php://input"), true);
$answer = array("success" => false, "message" => "");
$date_pattern = "/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/";


if (!isset($date["from"]) || !isset($date["to"]))
	$answer["message"] = "Ошибка! Переданы не все параметры!";

elseif (!preg_match($date_pattern, $date["from"]) || !preg_match($date_pattern, $date["to"]))
	$answer["message"] = "Ошибка! Некорректная дата!";

else {

	$fromDate = array();
	$toDate = array();
	preg_match_all("/[0-9]{1,}/", $date["from"], $fromDate);
	preg_match_all("/[0-9]{1,}/", $date["to"], $toDate);
	$fromDate = $fromDate[0];
	$toDate = $toDate[0];
	$now = getdate();

	if (!checkdate($fromDate[1], $fromDate[0], $fromDate[2]) || 
		!checkdate($toDate[1], $toDate[0], $toDate[2]))
		$answer["message"] = "Ошибка! Одна из-за введенных дат не существует!";

	elseif ($fromDate[0] > $toDate[0] || $fromDate[1] > $toDate[1] || $fromDate[2] > $toDate[2])
		$answer["message"] = "Ошибка! Дата начала периода не быть позднее даты конца периода!";

	elseif ($fromDate[0] > $now["mday"] || $fromDate[1] > $now["mon"] || $fromDate[2] > $now["year"] ||
			 $toDate[0] > $now["mday"] || $toDate[1] > $now["mon"] || $toDate[2] > $now["year"])
		$answer["message"] = "Ошибка! Одна из введенных дат еще не наступила!";

	else {

		$td_list = array();
		$date_arr = array();
		$val_arr = array();

		$html = @file_get_contents("http://www.cbr.ru/currency_base/dynamics/?UniDbQuery.Posted=True&UniDbQuery.mode=1&UniDbQuery.date_req1=&UniDbQuery.date_req2=&UniDbQuery.VAL_NM_RQ=R01235&UniDbQuery.FromDate=${date["from"]}&UniDbQuery.ToDate=${date["to"]}");
		
		if ($html) {

			$start_pos = strpos($html, "<table class=\"data\" xmlns:XsltBlock=\"urn:XsltBlock\">\r\n");
			$end_pos = strpos($html, "</table>\r\n<div class=\"history_of_valute\" xmlns:XsltBlock=\"urn:XsltBlock\">");
			$table = substr($html, $start_pos, $end_pos - $start_pos);

			// Извлечение значения доллара к рублю и даты
			preg_match_all("/<td>(([0-9]{1,}\.[0-9]{1,}\.[0-9]{1,})|([0-9]{1,},[0-9]{1,}))<\/td>/", $table, $td_list);
			$td_list = preg_replace("/,/", ".", $td_list[0]);


			for ($i=0; $i < count($td_list); $i++) { 
				$item = substr($td_list[$i], 4, -5);

				if ($i % 2 == 0)
					$date_arr[] = "'${item}'";
				else
					$val_arr[] = floatval($item);

			}

			$answer["success"] = true;
			$answer["message"] = array("date_arr" => $date_arr, "val_arr" => $val_arr);

		} else $answer["message"] = "Ошибка! Не удалось получить данные от удаленного сервера!";

	}

}


file_put_contents("output.txt", ob_get_clean());


echo json_encode($answer);

?>