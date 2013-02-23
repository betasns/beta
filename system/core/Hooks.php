<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 钩子类
 *
 * 提供一个不修改系统核心文件而扩展基本系统的机制
 * 首先从配置文件中读取配置的钩子列表, 然后在系统中需要运行钩子函数的地方调用 _call_hook($which)
 */
class CI_Hooks {

	/**
	 * 是否启用钩子
	 *
	 * @var bool
	 */
	var $enabled		= FALSE;
	/**
	 * 在 config/hooks.php 中的钩子列表
	 *
	 * @var array
	 */
	var $hooks			= array();
	/**
	 * 标识一个钩子函数在运行中, 用来防止无限循环
	 * 如果有一个钩子正在运行中, 则 in_progress 为 true, 运行完设置为 false(类似于锁定/解锁)
	 *
	 * @var bool
	 */
	var $in_progress	= FALSE;

	/**
	 * 构造函数
	 *
	 */
	function __construct(){
		$this->_initialize();
		log_message('debug', "初始化钩子类");
	}

	// --------------------------------------------------------------------

	/**
	 * 初始化钩子属性
	 *
	 * @access	private
	 * @return	void
	 */
	function _initialize(){
		$CFG =& load_class('Config', 'core');

		// 如果在配置文件中不允许使用钩子, 返回
		if ($CFG->item('enable_hooks') == FALSE){
			return;
		}

		// 获取钩子定义文件
		if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/hooks.php')){
		    include(APPPATH.'config/'.ENVIRONMENT.'/hooks.php');
		}elseif (is_file(APPPATH.'config/hooks.php')){
			include(APPPATH.'config/hooks.php');
		}
		
		// 如果文件中没有定义钩子或钩子非数组, 返回
		if ( ! isset($hook) OR ! is_array($hook)){
			return;
		}

		// 获取配置中的钩子
		$this->hooks =& $hook;
		// 设置允许使用钩子
		$this->enabled = TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 调用钩子
	 *
	 * @access	private
	 * @param	string	钩子名
	 * @return	mixed
	 */
	function _call_hook($which = ''){
		// 如果禁用钩子, 或者是钩子没有定义则返回 false
		if ( ! $this->enabled OR ! isset($this->hooks[$which])){
			return FALSE;
		}

		// 判断调用的钩子是否是个钩子数组
		if (isset($this->hooks[$which][0]) AND is_array($this->hooks[$which][0])){
			// 调用的是个钩子列表, 遍历, 依次调用
			foreach ($this->hooks[$which] as $val){
				$this->_run_hook($val);
			}
		}else {
			// 调用的是单个钩子
			$this->_run_hook($this->hooks[$which]);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 运行指定钩子
	 *
	 * @access	private
	 * @param	array	钩子详情
	 * @return	bool
	 */
	function _run_hook($data){
		// 参数非数组, 返回 false
		if ( ! is_array($data)){
			return FALSE;
		}

		// -----------------------------------
		// 安全 - 防止产生调用循环
		// -----------------------------------

		// 如果被调用的脚本调用了同一个钩子, 可能产生一个循环
		// 如果运行特征值为 true, 返回
		if ($this->in_progress == TRUE){
			return;
		}

		// -----------------------------------
		// 设置文件路径
		// -----------------------------------

		if ( ! isset($data['filepath']) OR ! isset($data['filename'])){
			return FALSE;
		}

		$filepath = APPPATH.$data['filepath'].'/'.$data['filename'];

		if ( ! file_exists($filepath)){
			return FALSE;
		}

		// -----------------------------------
		// 设置类/函数名
		// -----------------------------------

		$class		= FALSE;
		$function	= FALSE;
		$params		= '';

		if (isset($data['class']) AND $data['class'] != ''){
			$class = $data['class'];
		}

		if (isset($data['function'])){
			$function = $data['function'];
		}

		if (isset($data['params'])){
			$params = $data['params'];
		}

		if ($class === FALSE AND $function === FALSE){
			return FALSE;
		}

		// -----------------------------------
		// 设置 in_progress 特征值
		// -----------------------------------

		$this->in_progress = TRUE;

		// -----------------------------------
		// 调用请求的类和方法或函数
		// -----------------------------------

		if ($class !== FALSE){
			if ( ! class_exists($class)){
				require($filepath);
			}

			$HOOK = new $class;
			$HOOK->$function($params);
		}else {
			if ( ! function_exists($function)){
				require($filepath);
			}

			$function($params);
		}

		$this->in_progress = FALSE;
		return TRUE;
	}

}