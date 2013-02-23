<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Email 辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 验证 Email 地址
 * 
 * 注意: 这实际上并不表示这个地址能接收邮件, 只是简单地说明这是一个有效的地址格式
 *
 * @access	public
 * @return	bool
 */
if ( ! function_exists('valid_email')){
	function valid_email($address){
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}
}

// ------------------------------------------------------------------------

/**
 * 发送 Email
 *
 * @access	public
 * @return	bool
 */
if ( ! function_exists('send_email')){
	function send_email($recipient, $subject = 'Test email', $message = 'Hello World'){
		return mail($recipient, $subject, $message);
	}
}