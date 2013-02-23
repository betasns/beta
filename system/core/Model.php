<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 模型类
 *
 */
class CI_Model {

	/**
	 * 构造函数
	 *
	 * @access public
	 */
	function __construct(){
		log_message('debug', "初始化模型类");
	}

	/**
	 * __get
	 *
	 * 允许模型使用和控制器一样的语法访问已加载类
	 *
	 * @param	string
	 * @access private
	 */
	function __get($key){
		$CI =& get_instance();
		return $CI->$key;
	}
}