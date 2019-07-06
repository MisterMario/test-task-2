<?php 

$fromDate = "01.06.2019";
$toDate = "06.07.2019";
$td_list = array();
$date_arr = array();
$val_arr = array();


$html = file_get_contents("http://www.cbr.ru/currency_base/dynamics/?UniDbQuery.Posted=True&UniDbQuery.mode=1&UniDbQuery.date_req1=&UniDbQuery.date_req2=&UniDbQuery.VAL_NM_RQ=R01235&UniDbQuery.FromDate=${fromDate}&UniDbQuery.ToDate=${toDate}");
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

ob_start(); include "view/index.html";
echo ob_get_clean();

?>