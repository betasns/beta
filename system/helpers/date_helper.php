<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 日期辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 获取当前的 Unix 时间戳
 *
 * 根据 config 文件中 "time reference" 设定返回服务器当前时间或者 GMT 时间
 *
 * @access	public
 * @return	integer
 */
if ( ! function_exists('now')){
	function now(){
		$CI =& get_instance();

		if (strtolower($CI->config->item('time_reference')) == 'gmt'){
			$now = time();
			$system_time = mktime(gmdate("H", $now), gmdate("i", $now), gmdate("s", $now), gmdate("m", $now), gmdate("d", $now), gmdate("Y", $now));

			if (strlen($system_time) < 10){
				$system_time = time();
				log_message('error', 'Date 类不能设置适当的 GMT 时间戳, 所以使用本地 time() 值');
			}

			return $system_time;
		}else {
			return time();
		}
	}
}

// ------------------------------------------------------------------------

/**
 * 转换 MySQL 格式日期代码
 *
 * 这个函数跟 PHP 的内部函数 date() 几乎是一样的
 * 区别在于, 这个函数允许使用在每个代码字母前带有百分号 "%" 的 MySQL 时间表达形式:  %Y %m %d 等
 *
 * 采用这种方式处理时间数据的优点在于, 避免了在使用 date() 函数时经常担心漏掉那些非时间的字符的问题
 *
 * @access	public
 * @param	string
 * @param	integer
 * @return	integer
 */
if ( ! function_exists('mdate')){
	function mdate($datestr = '', $time = ''){
		if ($datestr == '')
			return '';

		if ($time == '')
			$time = now();

		$datestr = str_replace('%\\', '', preg_replace("/([a-z]+?){1}/i", "\\\\\\1", $datestr));
		return date($datestr, $time);
	}
}

// ------------------------------------------------------------------------

/**
 * 标准化日期
 *
 * 根据提交的标准格式返回生成的日期字符串
 *
 * @access	public
 * @param	string	选择的格式
 * @param	integer	Unix 时间戳
 * @return	string
 */
if ( ! function_exists('standard_date')){
	function standard_date($fmt = 'DATE_RFC822', $time = ''){
		$formats = array(
						'DATE_ATOM'		=>	'%Y-%m-%dT%H:%i:%s%Q',
						'DATE_COOKIE'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
						'DATE_ISO8601'	=>	'%Y-%m-%dT%H:%i:%s%Q',
						'DATE_RFC822'	=>	'%D, %d %M %y %H:%i:%s %O',
						'DATE_RFC850'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
						'DATE_RFC1036'	=>	'%D, %d %M %y %H:%i:%s %O',
						'DATE_RFC1123'	=>	'%D, %d %M %Y %H:%i:%s %O',
						'DATE_RSS'		=>	'%D, %d %M %Y %H:%i:%s %O',
						'DATE_W3C'		=>	'%Y-%m-%dT%H:%i:%s%Q'
						);

		if ( ! isset($formats[$fmt])){
			return FALSE;
		}

		return mdate($formats[$fmt], $time);
	}
}

// ------------------------------------------------------------------------

/**
 * 时间跨度
 *
 * 这个函数的用途通常是计算过去某一个时间点到当前时间之间的时间差
 * 格式化 unix 时间戳使其看起来像这样: 10 days 14 hours 36 minutes 47 seconds
 * 注: 由本函数生成的文字可以在以下语言文件中找到： language/<your_lang>/date_lang.php
 *
 * @access	public
 * @param	integer	unix 时间戳
 * @param	integer	unix 时间戳
 * @return	integer
 */
if ( ! function_exists('timespan')){
	function timespan($seconds = 1, $time = ''){
		$CI =& get_instance();
		$CI->lang->load('date');

		if ( ! is_numeric($seconds)){
			$seconds = 1;
		}

		if ( ! is_numeric($time)){
			$time = time();
		}

		if ($time <= $seconds){
			$seconds = 1;
		}else {
			$seconds = $time - $seconds;
		}

		$str = '';
		$years = floor($seconds / 31536000);

		if ($years > 0){
			$str .= $years.' '.$CI->lang->line((($years	> 1) ? 'date_years' : 'date_year')).', ';
		}

		$seconds -= $years * 31536000;
		$months = floor($seconds / 2628000);

		if ($years > 0 OR $months > 0){
			if ($months > 0){
				$str .= $months.' '.$CI->lang->line((($months	> 1) ? 'date_months' : 'date_month')).', ';
			}

			$seconds -= $months * 2628000;
		}

		$weeks = floor($seconds / 604800);

		if ($years > 0 OR $months > 0 OR $weeks > 0){
			if ($weeks > 0){
				$str .= $weeks.' '.$CI->lang->line((($weeks	> 1) ? 'date_weeks' : 'date_week')).', ';
			}

			$seconds -= $weeks * 604800;
		}

		$days = floor($seconds / 86400);

		if ($months > 0 OR $weeks > 0 OR $days > 0){
			if ($days > 0){
				$str .= $days.' '.$CI->lang->line((($days	> 1) ? 'date_days' : 'date_day')).', ';
			}

			$seconds -= $days * 86400;
		}

		$hours = floor($seconds / 3600);

		if ($days > 0 OR $hours > 0){
			if ($hours > 0){
				$str .= $hours.' '.$CI->lang->line((($hours	> 1) ? 'date_hours' : 'date_hour')).', ';
			}

			$seconds -= $hours * 3600;
		}

		$minutes = floor($seconds / 60);

		if ($days > 0 OR $hours > 0 OR $minutes > 0){
			if ($minutes > 0){
				$str .= $minutes.' '.$CI->lang->line((($minutes	> 1) ? 'date_minutes' : 'date_minute')).', ';
			}

			$seconds -= $minutes * 60;
		}

		if ($str == ''){
			$str .= $seconds.' '.$CI->lang->line((($seconds	> 1) ? 'date_seconds' : 'date_second')).', ';
		}

		return substr(trim($str), 0, -1);
	}
}

// ------------------------------------------------------------------------

/**
 * 一月的天数
 *
 * 对给出的年月值返回天数
 * 这个函数的实现已经考虑了存在闰年和平年的情况
 *
 * @access	public
 * @param	integer a numeric month
 * @param	integer	a numeric year
 * @return	integer
 */
if ( ! function_exists('days_in_month')){
	function days_in_month($month = 0, $year = ''){
		if ($month < 1 OR $month > 12){
			return 0;
		}

		if ( ! is_numeric($year) OR strlen($year) != 4){
			$year = date('Y');
		}

		if ($month == 2){
			if ($year % 400 == 0 OR ($year % 4 == 0 AND $year % 100 != 0)){
				return 29;
			}
		}

		$days_in_month	= array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		return $days_in_month[$month - 1];
	}
}

// ------------------------------------------------------------------------

/**
 * 将 unix 时间戳转换为 GMT
 *
 * @access	public
 * @param	integer
 * @return	integer
 */
if ( ! function_exists('local_to_gmt')){
	function local_to_gmt($time = ''){
		if ($time == '')
			$time = time();

		return mktime( gmdate("H", $time), gmdate("i", $time), gmdate("s", $time), gmdate("m", $time), gmdate("d", $time), gmdate("Y", $time));
	}
}

// ------------------------------------------------------------------------

/**
 * 将 GMT 转换为 本地时间戳
 *
 * 根据时区与夏时制转换为本地时间戳
 *
 * @access	public
 * @param	integer
 * @param	string
 * @param	bool
 * @return	integer
 */
if ( ! function_exists('gmt_to_local')){
	function gmt_to_local($time = '', $timezone = 'UTC', $dst = FALSE){
		if ($time == ''){
			return now();
		}

		$time += timezones($timezone) * 3600;

		if ($dst == TRUE){
			$time += 3600;
		}

		return $time;
	}
}

// ------------------------------------------------------------------------

/**
 * 将 MySQL 时间戳转换为 unix 时间戳
 *
 * @access	public
 * @param	integer
 * @return	integer
 */
if ( ! function_exists('mysql_to_unix')){
	function mysql_to_unix($time = ''){
		// 为了向后兼容删除某些字符
		// MySQL 4.1 格式改为 YYYY-MM-DD HH:MM:SS

		$time = str_replace('-', '', $time);
		$time = str_replace(':', '', $time);
		$time = str_replace(' ', '', $time);

		// YYYYMMDDHHMMSS
		return  mktime(
						substr($time, 8, 2),
						substr($time, 10, 2),
						substr($time, 12, 2),
						substr($time, 4, 2),
						substr($time, 6, 2),
						substr($time, 0, 4)
						);
	}
}

// ------------------------------------------------------------------------

/**
 * 将 unix 的时间表示方式转换成"人类"的时间表示方式
 *
 * 将 unix 时间戳转为如下格式: 2006-08-21 11:35 PM
 *
 * @access	public
 * @param	integer
 * @param	bool	是否显示秒
 * @param	string	美国(us)或欧洲(euro)格式
 * @return	string
 */
if ( ! function_exists('unix_to_human')){
	function unix_to_human($time = '', $seconds = FALSE, $fmt = 'us'){
		$r  = date('Y', $time).'-'.date('m', $time).'-'.date('d', $time).' ';

		if ($fmt == 'us'){
			$r .= date('h', $time).':'.date('i', $time);
		}else {
			$r .= date('H', $time).':'.date('i', $time);
		}

		if ($seconds){
			$r .= ':'.date('s', $time);
		}

		if ($fmt == 'us'){
			$r .= ' '.date('A', $time);
		}

		return $r;
	}
}

// ------------------------------------------------------------------------

/**
 * 将"人类"的时间表示方式转换成 unix 的时间表示方式
 *
 * @access	public
 * @param	string	美国(us)或欧洲(euro)格式
 * @return	integer
 */
if ( ! function_exists('human_to_unix')){
	function human_to_unix($datestr = ''){
		if ($datestr == ''){
			return FALSE;
		}

		$datestr = trim($datestr);
		$datestr = preg_replace("/\040+/", ' ', $datestr);

		if ( ! preg_match('/^[0-9]{2,4}\-[0-9]{1,2}\-[0-9]{1,2}\s[0-9]{1,2}:[0-9]{1,2}(?::[0-9]{1,2})?(?:\s[AP]M)?$/i', $datestr)){
			return FALSE;
		}

		$split = explode(' ', $datestr);

		$ex = explode("-", $split['0']);

		$year  = (strlen($ex['0']) == 2) ? '20'.$ex['0'] : $ex['0'];
		$month = (strlen($ex['1']) == 1) ? '0'.$ex['1']  : $ex['1'];
		$day   = (strlen($ex['2']) == 1) ? '0'.$ex['2']  : $ex['2'];

		$ex = explode(":", $split['1']);

		$hour = (strlen($ex['0']) == 1) ? '0'.$ex['0'] : $ex['0'];
		$min  = (strlen($ex['1']) == 1) ? '0'.$ex['1'] : $ex['1'];

		if (isset($ex['2']) && preg_match('/[0-9]{1,2}/', $ex['2'])){
			$sec  = (strlen($ex['2']) == 1) ? '0'.$ex['2'] : $ex['2'];
		}else {
			// 除非另有规定, 秒设置为零
			$sec = '00';
		}

		if (isset($split['2'])){
			$ampm = strtolower($split['2']);

			if (substr($ampm, 0, 1) == 'p' AND $hour < 12)
				$hour = $hour + 12;

			if (substr($ampm, 0, 1) == 'a' AND $hour == 12)
				$hour =  '00';

			if (strlen($hour) == 1)
				$hour = '0'.$hour;
		}

		return mktime($hour, $min, $sec, $month, $day, $year);
	}
}

// ------------------------------------------------------------------------

/**
 * 时区表
 *
 * 生成一个时区下拉选单
 *
 * @access	public
 * @param	string	时区
 * @param	string	类名
 * @param	string	菜单名
 * @return	string
 */
if ( ! function_exists('timezone_menu')){
	function timezone_menu($default = 'UTC', $class = "", $name = 'timezones'){
		$CI =& get_instance();
		$CI->lang->load('date');

		if ($default == 'GMT')
			$default = 'UTC';

		$menu = '<select name="'.$name.'"';

		if ($class != ''){
			$menu .= ' class="'.$class.'"';
		}

		$menu .= ">\n";

		foreach (timezones() as $key => $val){
			$selected = ($default == $key) ? " selected='selected'" : '';
			$menu .= "<option value='{$key}'{$selected}>".$CI->lang->line($key)."</option>\n";
		}

		$menu .= "</select>";

		return $menu;
	}
}

// ------------------------------------------------------------------------

/**
 * 时区
 *
 * 返回时区数组
 *
 * @access	public
 * @param	string	时区
 * @return	string
 */
if ( ! function_exists('timezones')){
	function timezones($tz = ''){
		// 注意: 不要改变这些顺序, 即使一些项出现在错误的顺序

		$zones = array(
						'UM12'		=> -12,
						'UM11'		=> -11,
						'UM10'		=> -10,
						'UM95'		=> -9.5,
						'UM9'		=> -9,
						'UM8'		=> -8,
						'UM7'		=> -7,
						'UM6'		=> -6,
						'UM5'		=> -5,
						'UM45'		=> -4.5,
						'UM4'		=> -4,
						'UM35'		=> -3.5,
						'UM3'		=> -3,
						'UM2'		=> -2,
						'UM1'		=> -1,
						'UTC'		=> 0,
						'UP1'		=> +1,
						'UP2'		=> +2,
						'UP3'		=> +3,
						'UP35'		=> +3.5,
						'UP4'		=> +4,
						'UP45'		=> +4.5,
						'UP5'		=> +5,
						'UP55'		=> +5.5,
						'UP575'		=> +5.75,
						'UP6'		=> +6,
						'UP65'		=> +6.5,
						'UP7'		=> +7,
						'UP8'		=> +8,
						'UP875'		=> +8.75,
						'UP9'		=> +9,
						'UP95'		=> +9.5,
						'UP10'		=> +10,
						'UP105'		=> +10.5,
						'UP11'		=> +11,
						'UP115'		=> +11.5,
						'UP12'		=> +12,
						'UP1275'	=> +12.75,
						'UP13'		=> +13,
						'UP14'		=> +14
					);

		if ($tz == ''){
			return $zones;
		}

		if ($tz == 'GMT')
			$tz = 'UTC';

		return ( ! isset($zones[$tz])) ? 0 : $zones[$tz];
	}
}