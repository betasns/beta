<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 语言辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 获取一个语言变量和可选输出表单标签
 *
 * @access	public
 * @param	string	语言行
 * @param	string	表单元素的 id
 * @return	string
 */
if ( ! function_exists('lang')){
	function lang($line, $id = ''){
		$CI =& get_instance();
		$line = $CI->lang->line($line);

		if ($id != ''){
			$line = '<label for="'.$id.'">'.$line."</label>";
		}

		return $line;
	}
}