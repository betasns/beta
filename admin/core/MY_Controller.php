<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 控制器基类
 * 
 * @author zgz
 * 
 */
class MY_Controller extends CI_Controller{
	
	protected $directory;
	protected $class;
	protected $method;

	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		
		//编码格式
		header("content-type:text/html; charset=utf-8");
		
		global $RTR, $URI;
		$this->directory = $RTR->fetch_directory();
		$this->class = $RTR->fetch_class();
		$this->method = $RTR->fetch_method();
		$this->current_uri = $URI->uri_string();
		
		log_message('debug', "初始化控制器基类");
		
		// 初始化数据库类
		$this->load->database();
	}

}