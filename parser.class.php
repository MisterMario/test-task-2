<?php 

class Parser
{
	private const CACHE_FILENAME = "chache.json";
	private $_cache = null; // Хранит кэш в виде ассоциативного массива
	private static $instance = null;

	public static function getInstance()
	{
		if (static::$instance === null)
		{
			static::$instance = new self();
			$cache_string = @file_get_contents(static::CACHE_FILENAME);

			if ($cache_string != false)
				static::$instance->_cache = json_decode($cache_string, true);
		}

		return static::$instance;
	}

	private function __construct() { }
	private function __clone() { }
	private function __wakeup() { }

	/**
	 * [Проверяет есть ли какие-либо данные в кэше]
	 * @return [bool] [true - в кэше есть данные, false - кэш пуст.]
	 */
	private function noEmptyCache()
	{
		return $this->_cache != null ? true : false;
	}

	/**
	 * [Проверяет есть ли данные за данный диапазон дат в кэше.
	 * Причем данные должны быть ЗА ВЕСЬ диапазон дат. ]
	 * @param  [int] $from_month [Месяц начальной даты]
	 * @param  [int] $from_year  [Год начальной даты]
	 * @param  [int] $to_month   [Месяц конечной даты]
	 * @param  [int] $to_year    [Год конечной даты]
	 * @return [bool]            [true - если данные для даты есть в кэше, false - нет]
	 */
	private function existsDates($from_month, $from_year, $to_month, $to_year)
	{
		if ($this->noEmptyCache())
		{
			$start_month = $from_month;
			for ($y=$from_year; $y <= $to_year; $y++)
				for ($m=$start_month; $m <= ($y != $to_year ? 12 : $to_month); $m++)
					if (!isset($this->_cache[$y][$m]))
						return false;
		}
		else return false;

		return true;
	}

	/**
	 * [Возвращает коллецию данных: дата-значение
	 *  Значение - стоимость $ в русских рублях.
	 *  Если в кэше есть нужные даты - берет данные от туда, нет - с сервера Цетрального банка России]
	 * @param  [string] $fromDate [Дата начала периода]
	 * @param  [string] $toDate   [Дата конца периода]
	 * @return [array]            [Ассоциативный массив с данными. Хранит пары: дата => значение]
	 */
	public function getData($fromDate, $toDate)
	{
		$output_arr = null;
		$from["m"] = (int)substr($fromDate, 0, 2);
		$from["y"] = (int)substr($fromDate, -4);
		$to["m"] = (int)substr($toDate, 0, 2);
		$to["y"] = (int)substr($toDate, -4);

		if ($this->existsDates($from["m"], $from["y"], $to["m"], $to["y"]))
			$output_arr = getDataFromCache($fromDate, $toDate);
		else
			$output_arr = getDataFromRemote($fromDate, $toDate);

		return $output_arr;
	}

	/**
	 * [Возвращает коллецию данных: дата-значение
	 *  Значение - стоимость $ в русских рублях.
	 *  Все данные извлекаются из кэша.]
	 * @param  [string] $fromDate [Дата начала периода]
	 * @param  [string] $toDate   [Дата конца периода]
	 * @return [array]            [Ассоциативный массив с данными. Хранит пары: дата => значение]
	 */
	public function getDataFromCache($fromDate, $toDate)
	{
		$output_arr = array();
		$start_month = $from_month;

		// Получение всех пар [дата - стоимость] из кэша
		for ($y=$from["y"]; $y < $to["y"]; $y++)
			for ($m=$start_month; $m < ($y != $to_year ? 12 : $to_month); $m++)
				for ($d=1; $d <= 31; $d++)
					if (isset($this->_cache[$y][$m][$d]))
							$output_arr["${d}.${m}.${y}"] = $this->_cache[$y][$m][$d];

		return $output_arr;
	}

	/**
	 * [Возвращает коллецию данных: дата-значение
	 *  Значение - стоимость $ в русских рублях.
	 *  Все данные извлекаются с сервера Центрального банка России.]
	 * @param  [string] $fromDate [Дата начала периода]
	 * @param  [string] $toDate   [Дата конца периода]
	 * @return [array]            [Ассоциативный массив с данными. Хранит пары: дата => значение]
	 */
	public function getDataFromRemote($fromDate, $toDate)
	{
		$td_list = array();
		$output_arr = array();

		$tempDates = array(
			"from" => "01".substr($fromDate, 2),
			"to" => (new DateTime(substr($toDate, -4)."-".substr($toDate, 3, 2)."-01"))->format("t.m.Y"),
		);

		$html = @file_get_contents("http://www.cbr.ru/currency_base/dynamics/?UniDbQuery.Posted=True&UniDbQuery.mode=1&UniDbQuery.date_req1=&UniDbQuery.date_req2=&UniDbQuery.VAL_NM_RQ=R01235&UniDbQuery.FromDate=${tempDates["from"]}&UniDbQuery.ToDate=${tempDates["to"]}");
		
		if ($html)
		{
			$start_pos = strpos($html, "<table class=\"data\" xmlns:XsltBlock=\"urn:XsltBlock\">\r\n");
			$end_pos = strpos($html, "</table>\r\n<div class=\"history_of_valute\" xmlns:XsltBlock=\"urn:XsltBlock\">");
			$table = substr($html, $start_pos, $end_pos - $start_pos);

			// Извлечение значения доллара к рублю и даты
			preg_match_all("/<td>(([0-9]{1,}\.[0-9]{1,}\.[0-9]{1,})|([0-9]{1,},[0-9]{1,}))<\/td>/", $table, $td_list);
			$td_list = preg_replace("/,/", ".", $td_list[0]);

			$cur_date = null; $cur_val = null;
			for ($i=0; $i < count($td_list); $i++) { 
				$td = substr($td_list[$i], 4, -5);

				if ($i % 2 == 0)
					$cur_date = $td;
				else
				{
					$cur_val = floatval($td);
					$output_arr[$cur_date] = $cur_val;

					$day = substr($cur_date, 0, 2);
					$month = substr($cur_date, 3, 2);
					$year = substr($cur_date, -4);

					if (!isset($this->_cache[$year][$month][$day]))
						$this->_cache[$year][$month][$day] = $cur_val;
				}
			}
		}

		return $output_arr;
	}
}

// $singleton = Cache::getInstance();
// echo $singleton->get();


?>