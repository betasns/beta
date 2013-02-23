<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Cookie 辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 设置 cookie
 *
 * 接受 6 个参数, 也可以在第一个参数中传递一个包含所有值的关联数组
 *
 * @access	public
 * @param	mixed
 * @param	string	cookie 值
 * @param	string	以秒为单位的过期时间
 * @param	string	cookie 域名
 * @param	string	cookie 路径
 * @param	string	cookie 前缀
 * @return	void
 */
if ( ! function_exists('set_cookie')){
	function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = FALSE){
		$CI =& get_instance();
		$CI->input->set_cookie($name, $value, $expire, $domain, $path, $prefix, $secure);
	}
}

// --------------------------------------------------------------------

/**
 * 从 COOKIE 数组中获取项
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	mixed
 */
if ( ! function_exists('get_cookie')){
	function get_cookie($index = '', $xss_clean = FALSE){
		$CI =& get_instance();

		$prefix = '';

		if ( ! isset($_COOKIE[$index]) && config_item('cookie_prefix') != ''){
			$prefix = config_item('cookie_prefix');
		}

		return $CI->input->cookie($prefix.$index, $xss_clean);
	}
}

// --------------------------------------------------------------------

/**
 * 删除 COOKIE
 *
 * @param	mixed
 * @param	string	cookie 域名
 * @param	string	cookie 路径
 * @param	string	cookie 前缀
 * @return	void
 */
if ( ! function_exists('delete_cookie')){
	function delete_cookie($name = '', $domain = '', $path = '/', $prefix = ''){
		set_cookie($name, '', '', $domain, $path, $prefix);
	}
}