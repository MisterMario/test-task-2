<?php 

class Parser
{
	private const CACHE_FILENAME = "cache.json";
	public $_cache = null; // Хранит кэш в виде ассоциативного массива
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
	 * [Возвращает ассоциативный массив с информацией о дате: день, месяц, год.]
	 * @param  [string] $date [Дата в формате dd.mm.YYYY]
	 * @return [array]        [Информация о дате: d - день, m - месяц, y - год. Все значения - целочисленные.]
	 */
	public function getDateInfo($date)
	{
		return array(
			"d" => (int)substr($date, 0, 2),
			"m" => (int)substr($date, 3, 2),
			"y" => (int)substr($date, -4),
		);
	}

	/**
	 * [Проверяет есть ли какие-либо данные в кэше]
	 * @return [bool] [true - в кэше есть данные, false - кэш пуст.]
	 */
	private function noEmptyCache()
	{
		return $this->_cache != null ? true : false;
	}

	/**
	 * [Перезаписывает файл кэша]
	 * @return [bool] [true - успешная перезапись, false - произошла ошибка при записи.]
	 */
	public function saveCache()
	{
		return (bool)@file_put_contents(static::CACHE_FILENAME, json_encode($this->_cache));
	}

	/**
	 * [Проверяет есть ли данные за данный диапазон дат в кэше.
	 * Причем данные должны быть ЗА ВЕСЬ диапазон дат. ]
	 * @param  [array] $from [Дата начала периода (d - день, m - месяц, y - год)]
	 * @param  [array] $to   [Дата конца периода (d - день, m - месяц, y - год)]
	 * @return [bool]        [true - если данные для даты есть в кэше, false - нет]
	 */
	private function existsDates($from, $to)
	{
		if ($this->noEmptyCache())
		{
			$start_month = $from["m"];

			for ($y = $from["y"]; $y <= $to["y"]; $y++)

				for ($m = $start_month; $m <= ($y != $to["y"] ? 12 : $to["m"]); $m++)

					if (!isset($this->_cache["${y}"][$m < 10 ? "0${m}" : "${m}"]))
					{
						echo "В кэше нет данных за ${m}.${y}\r\n\r\n\r\n";
						return false;
					}
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
		$from = $this->getDateInfo($fromDate);
		$to = $this->getDateInfo($toDate);

		if ($this->existsDates($from, $to))
		{
			echo "Данные взяты из кэша!\r\n\r\n";
			$output_arr = $this->getDataFromCache($from, $to);
		}
		else
		{
			echo "Данные взяты с сайта Центрального банка Росии\r\n\r\n";
			$output_arr = $this->getDataFromRemote($from, $to);
		}

		return $output_arr;
	}

	/**
	 * [Возвращает коллецию данных: дата-значение
	 *  Значение - стоимость $ в русских рублях.
	 *  Все данные извлекаются из кэша.]
	 * @param  [array] $fromDate [Дата начала периода (d - день, m - месяц, y - год)]
	 * @param  [array] $toDate   [Дата конца периода (d - день, m - месяц, y - год)]
	 * @return [array]           [Ассоциативный массив с данными. Хранит пары: дата => значение]
	 */
	public function getDataFromCache($fromDate, $toDate)
	{
		$output_arr = array();
		$start_month = $fromDate["m"];

		// Получение всех пар [дата - стоимость] из кэша
		for ($y = $fromDate["y"]; $y <= $toDate["y"]; $y++)

			for ($m = $start_month; $m <= ($y != $toDate["y"] ? 12 : $toDate["m"]); $m++)
			{
				$start_day = 1;
				$days_num = (int)(new DateTime("01.${m}.${y}"))->format("t");;

				if ($y == $fromDate["y"] && $m == $fromDate["m"])
					$start_day = $fromDate["d"];

				elseif ($y == $toDate["y"] && $m == $toDate["m"])
					$days_num = $toDate["d"];

				$str_y = "${y}";
				$str_m = $m < 10 ? "0${m}" : "${m}";

				for ($d = $start_day; $d <= $days_num; $d++)
				{
					$str_d = $d < 10 ? "0${d}" : "${d}";
					if (isset($this->_cache[$str_y][$str_m][$str_d]))
							$output_arr["${str_d}.${str_m}.${str_y}"] = $this->_cache[$str_y][$str_m][$str_d];
				}
			}

		return $output_arr;
	}

	/**
	 * [Возвращает коллецию данных: дата-значение
	 *  Значение - стоимость $ в русских рублях.
	 *  Все данные извлекаются с сервера Центрального банка России.]
	 * @param  [array] $fromDate [Дата начала периода (d - день, m - месяц, y - год)]
	 * @param  [array] $toDate   [Дата конца периода (d - день, m - месяц, y - год)]
	 * @return [array]           [Ассоциативный массив с данными. Хранит пары: дата => значение]
	 */
	public function getDataFromRemote($fromDate, $toDate)
	{
		$td_list = array();
		$output_arr = array();

		$tempDates = array(
			"from" => "01.${fromDate["m"]}.${fromDate["y"]}",
			"to" => (new DateTime("01.${toDate["m"]}.${toDate["y"]}"))->format("t.m.Y"),
		);
		$timeFromDate = strtotime("${fromDate["d"]}.${fromDate["m"]}.${fromDate["y"]}");
		$timeToDate = strtotime("${toDate["d"]}.${toDate["m"]}.${toDate["y"]}");

		$html = @file_get_contents("http://www.cbr.ru/currency_base/dynamics/?UniDbQuery.Posted=True&UniDbQuery.mode=1&UniDbQuery.date_req1=&UniDbQuery.date_req2=&UniDbQuery.VAL_NM_RQ=R01235&UniDbQuery.FromDate=${tempDates["from"]}&UniDbQuery.ToDate=${tempDates["to"]}");
		
		if ($html)
		{
			$start_pos = strpos($html, "<table class=\"data\" xmlns:XsltBlock=\"urn:XsltBlock\">\r\n");
			$end_pos = strpos($html, "</table>\r\n<div class=\"history_of_valute\" xmlns:XsltBlock=\"urn:XsltBlock\">");
			$table = substr($html, $start_pos, $end_pos - $start_pos);

			// Извлечение <td> содержащих дату и стоимость
			preg_match_all("/<td>(([0-9]{1,}\.[0-9]{1,}\.[0-9]{1,})|([0-9]{1,},[0-9]{1,}))<\/td>/", $table, $td_list);
			$td_list = preg_replace("/,/", ".", $td_list[0]);

			$cur_date = null; $cur_val = null;
			for ($i=0; $i < count($td_list); $i++)
			{ 
				$td = substr($td_list[$i], 4, -5);

				if ($i % 2 == 0)
					$cur_date = $td;
				else
				{
					$cur_val = floatval($td);

					if ($timeFromDate <= strtotime($cur_date) && strtotime($cur_date) <= $timeToDate)
						$output_arr[$cur_date] = $cur_val;

					$day = substr($cur_date, 0, 2);
					$month = substr($cur_date, 3, 2);
					$year = substr($cur_date, -4);

					if (!isset($this->_cache[$year][$month][$day]))
						$this->_cache[$year][$month][$day] = $cur_val;
				}
			}
		}

		$this->saveCache();

		return $output_arr;
	}
}

?>