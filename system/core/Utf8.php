<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Utf8 类
 *
 * 提供支持 UTF-8 的环境
 *
 */
class CI_Utf8 {

	/**
	 * 构造函数
	 *
	 * 确定是否启用 UTF-8 支持
	 *
	 */
	function __construct(){
		log_message('debug', "初始化 Utf8 类");

		global $CFG;

		if (
			// 注: /u 表示按 unicode(utf-8) 匹配(主要针对多字节比如汉字)
			preg_match('/./u', 'é') === 1					// PCRE 必须支持 UTF-8
			AND function_exists('iconv')					// 必须安装 iconv
			AND ini_get('mbstring.func_overload') != 1		// 不能启用多字节字符串函数重载
			AND $CFG->item('charset') == 'UTF-8'			// 应用程序字符集必须是 UTF-8
			){
			log_message('debug', "支持 UTF-8");

			define('UTF8_ENABLED', TRUE);

			// set internal encoding for multibyte string functions if necessary
			// and set a flag so we don't have to repeatedly use extension_loaded()
			// or function_exists()
			if (extension_loaded('mbstring')){
				define('MB_ENABLED', TRUE);
				mb_internal_encoding('UTF-8');
			}else {
				define('MB_ENABLED', FALSE);
			}
		}else {
			log_message('debug', "不支持 UTF-8");
			define('UTF8_ENABLED', FALSE);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 清洁 UTF-8 字符串
	 *
	 * 确保字符串是 UTF-8
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function clean_string($str){
		if ($this->_is_ascii($str) === FALSE){
			$str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * 删除 ASCII 控制字符
	 *
	 * 删除除了水平制表符, 换行符, 回车等所有其他可能导致 XML 问题的 ASCII 控制字符
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function safe_ascii_for_xml($str){
		return remove_invisible_characters($str, FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * 转换为 UTF-8
	 *
	 * 尝试将字符串转换为 UTF-8
	 *
	 * @access	public
	 * @param	string
	 * @param	string	- 输入编码
	 * @return	string
	 */
	function convert_to_utf8($str, $encoding){
		if (function_exists('iconv')){
			$str = @iconv($encoding, 'UTF-8', $str);
		}elseif (function_exists('mb_convert_encoding')){
			$str = @mb_convert_encoding($str, 'UTF-8', $encoding);
		}else {
			return FALSE;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * 是否是 ASCII
	 *
	 * 测试一个字符串是否是标准的 7 位 ASCII
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function _is_ascii($str){
		return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
	}

}