<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 字符串辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 去除斜线
 *
 * 除去字符串开头和末尾的斜线
 * 例如: 将 /this/that/theother/ 变成 this/that/theother
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('trim_slashes')){
	function trim_slashes($str){
		return trim($str, '/');
	}
}

// ------------------------------------------------------------------------

/**
 * 去除转义反斜线
 *
 * 去除字符串或数组中的转义反斜线
 *
 * @access	public
 * @param	mixed
 * @return	mixed
 */
if ( ! function_exists('strip_slashes')){
	function strip_slashes($str){
		if (is_array($str)){
			foreach ($str as $key => $val){
				$str[$key] = strip_slashes($val);
			}
		}else {
			$str = stripslashes($str);
		}

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
 * 去除引号
 *
 * 去除字符串中的单引号和双引号
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('strip_quotes')){
	function strip_quotes($str){
		return str_replace(array('"', "'"), '', $str);
	}
}

// ------------------------------------------------------------------------

/**
 * 引号转换为实体
 * 
 * 将字符串中的单引号和双引号转换为相应的 HTML 字符表示
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('quotes_to_entities')){
	function quotes_to_entities($str){
		return str_replace(array("\'","\"","'",'"'), array("&#39;","&quot;","&#39;","&quot;"), $str);
	}
}

// ------------------------------------------------------------------------

/**
 * 降阶双斜线
 *
 * 将字符串中的双斜线(//)转换为单斜线(/), 但不转换形如(http://)的双斜线
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('reduce_double_slashes')){
	function reduce_double_slashes($str){
		return preg_replace("#(^|[^:])//+#", "\\1/", $str);
	}
}

// ------------------------------------------------------------------------

/**
 * Reduce Multiples
 *
 * Reduces multiple instances of a particular character.  Example:
 *
 * Fred, Bill,, Joe, Jimmy
 *
 * becomes:
 *
 * Fred, Bill, Joe, Jimmy
 *
 * @access	public
 * @param	string
 * @param	string	the character you wish to reduce
 * @param	bool	TRUE/FALSE - whether to trim the character from the beginning/end
 * @return	string
 */
if ( ! function_exists('reduce_multiples'))
{
	function reduce_multiples($str, $character = ',', $trim = FALSE)
	{
		$str = preg_replace('#'.preg_quote($character, '#').'{2,}#', $character, $str);

		if ($trim === TRUE)
		{
			$str = trim($str, $character);
		}

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
 * 生成随机字符串
 *
 * 根据所指定的类型和长度产生一个随机字符串
 * 可用于生成密码串或哈希值
 *
 * @access	public
 * @param	string	随机字符串类型.  以下为可选字符串类型: basic, alpha, alnum, numeric, nozero, unique, md5, encrypt 和 sha1
 * @param	integer	字符数
 * @return	string
 */
if ( ! function_exists('random_string')){
	function random_string($type = 'alnum', $len = 8){
		switch($type){
			case 'basic'	: return mt_rand();
				break;
			case 'alnum'	:
			case 'numeric'	:
			case 'nozero'	:
			case 'alpha'	:

					switch ($type){
						case 'alpha'	:	$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
							break;
						case 'alnum'	:	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
							break;
						case 'numeric'	:	$pool = '0123456789';
							break;
						case 'nozero'	:	$pool = '123456789';
							break;
					}

					$str = '';
					for ($i=0; $i < $len; $i++){
						$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
					}
					return $str;
				break;
			// 注意: 第二个长度参数在这种类型无效
			case 'unique'	:
			case 'md5'		:

						return md5(uniqid(mt_rand()));
				break;
			case 'encrypt'	:
			case 'sha1'	:

						$CI =& get_instance();
						$CI->load->helper('security');

						return do_hash(uniqid(mt_rand(), TRUE), 'sha1');
				break;
		}
	}
}

// ------------------------------------------------------------------------

/**
 * Add's _1 to a string or increment the ending number to allow _2, _3, etc
 *
 * @param   string  $str  required
 * @param   string  $separator  What should the duplicate number be appended with
 * @param   string  $first  Which number should be used for the first dupe increment
 * @return  string
 */
function increment_string($str, $separator = '_', $first = 1)
{
	preg_match('/(.+)'.$separator.'([0-9]+)$/', $str, $match);

	return isset($match[2]) ? $match[1].$separator.($match[2] + 1) : $str.$separator.$first;
}

// ------------------------------------------------------------------------

/**
 * 交替
 *
 * 让两个或两个以上的条目轮换使用, 可以任意添加条目的数量, 每一次循环后下一个条目将成为返回值
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('alternator')){
	function alternator(){
		static $i;

		if (func_num_args() == 0){
			$i = 0;
			return '';
		}echo $i;
		$args = func_get_args();
		return $args[($i++ % count($args))];
	}
}

// ------------------------------------------------------------------------

/**
 * 重复生成提交的数据
 *
 * @access	public
 * @param	string
 * @param	integer	重复次数
 * @return	string
 */
if ( ! function_exists('repeater')){
	function repeater($data, $num = 1){
		return (($num > 0) ? str_repeat($data, $num) : '');
	}
}