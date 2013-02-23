<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 钩子类
 *
 * 提供一个不修改系统核心文件的扩展基本系统的机制
 */
class Hook_default {
	
	/**
	 * 当前类名
	 *
	 * @var string
	 * @access public
	 */
	var $class			= '';
	/**
	 * 当前方法名
	 *
	 * @var string
	 * @access public
	 */
	var $method			= '';
	
	/**
	 * 构造函数
	 *
	 */
	function __construct(){
		$this->_initialize();
		log_message('debug', "初始化应用程序钩子类");
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 初始化钩子属性
	 *
	 * @access	private
	 * @return	void
	 */
	function _initialize(){
		
	}
	
	/*
	 * 系统执行的早期调用
	 * 仅仅在 benchmark 和 hooks 类加载完毕的时候
	 * 没有执行路由或者其它的过程
	 */
	public function pre_system() {
		
	}
	
	/*
	 * 此函数可以取代 output 类中的 _display_cache() 函数
	 * 这可以让你使用自己的缓存显示方法
	 */
	public function cache_override() {
	
	}
	
	/*
	 * 在调用任何控制器之前调用
	 * 此时所用的基础类, 路由选择和安全性检查都已完成
	 */
	public function pre_controller() {
		
	}

	/*
	 * 在控制器实例化之后, 任何方法调用之前调用
	 */
	public function post_controller_constructor() {
		
	}

	/*
	 * 在控制器完全运行之后调用
	 */
	public function post_controller() {
		
	}

	/*
	 * 覆盖 _display() 函数, 用来在系统执行末尾向 web 浏览器发送最终页面
	 * 这允许你用自己的方法来显示
	 * 注意: 需要通过 $this->CI =& get_instance() 引用 CI 超级对象, 然后这样的最终数据可以通过调用 $this->CI->output->get_output() 来获得
	 */
	public function display_override() {
		$this->CI = & get_instance();
		//$this->CI->output->get_output();
		$this->CI->output->_display();
	}

	/*
	 * 在最终着色页面发送到浏览器之后, 浏览器接收完最终数据的系统执行末尾调用
	 */
	public function post_system() {
		global $RTR;
		$this->class  = $RTR->fetch_class();
		$this->method = $RTR->fetch_method();
		global $BM;
		//foreach ($BM->marker as $key => $value){echo '$' . $key . ' = ' . $value; echo '<br />';}
		echo '总执行时间: ' . $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');
		echo '<br />';
		echo '加载基类时间: ' . $BM->elapsed_time('loading_time:_base_classes_start', 'loading_time:_base_classes_end');
		echo '<br />';
		echo '控制器执行时间: ' . $BM->elapsed_time('controller_execution_time_( '.$this->class.' / '.$this->method.' )_start', 'controller_execution_time_( '.$this->class.' / '.$this->method.' )_end');
	}
	
}