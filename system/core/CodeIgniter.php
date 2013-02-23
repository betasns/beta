<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 执行文件
 *
 */

/**
 * CodeIgniter 版本
 *
 * @var string
 *
 */
	define('CI_VERSION', '2.1.3');

/**
 * 版本 2.0.1 添加了常量 CI_CORE 以用于区分核心代码: TRUE 和社区贡献代码: FALSE
 * 版本 2.1.0 移除了常量 CI_CORE
 * 
 * CodeIgniter 分支 (Core = TRUE, Reactor = FALSE)
 *
 * @var boolean
 *
 */
	define('CI_CORE', FALSE);

/*
 * ------------------------------------------------------
 *  加载全局函数
 * ------------------------------------------------------
 */
	require(BASEPATH.'core/Common.php');

/*
 * ------------------------------------------------------
 *  加载框架常量
 * ------------------------------------------------------
 * 
 * 主要是与文件操作有关的一些常量
 * 
 */
	if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/constants.php')){
		require(APPPATH.'config/'.ENVIRONMENT.'/constants.php');
	}else {
		require(APPPATH.'config/constants.php');
	}

/*
 * ------------------------------------------------------
 *  设置用户自定义的错误处理函数
 * ------------------------------------------------------
 */
	set_error_handler('_exception_handler');

	// 如果当前PHP的版本低于 5.3, 执行 set_magic_quotes_runtime, 关闭 magic_quotes_runtime(对引号进行转义)
	// 在 PHP5.3 中已经废弃了此函数，因此不要使用了
	if ( ! is_php('5.3')){
		@set_magic_quotes_runtime(0); // 关闭魔术引号
	}

/*
 * ------------------------------------------------------
 *  设置 subclass_prefix
 *  该段程序在当 index.php 中设置了 $assign_to_config['subclass_prefix'] 覆写配置时执行
 * ------------------------------------------------------
 *
 * 通常情况下, 在 config 中设置 "subclass_prefix"
 * 子类前缀允许 CI 获知一个核心类是否被位于本地应用程序的 libraries 目录下的类扩展了
 * 因为 CI 允许在 index.php 文件中覆写配置项的值, 在处理之前我们需要知道 subclass_prefix 覆盖是否存在
 * 如果存在, 在任何类被载入之前设置这个值
 * 注意: 由于配置文件数据缓存, 它不会影响加载
 */
	if (isset($assign_to_config['subclass_prefix']) AND $assign_to_config['subclass_prefix'] != ''){
		// 如果该段得以执行, 则缓存覆写后的配置项, 一旦配置项被加载了, 以后就不能再更改了
		get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
	}

/*
 * ------------------------------------------------------
 *  设置一个宽松的脚本执行时间限制
 * ------------------------------------------------------
 * 
 * set_time_limit($seconds) 设置脚本最大执行时间, 默认是 30s, 如果设置为0, 则没有时间限制
 * 当在安全模式下运行时, 该功能无效, 除了关闭安全模式或者改变 php.ini 的时间限制, 没有别的办法
 * 
 */
	if (function_exists("set_time_limit") == TRUE AND @ini_get("safe_mode") == 0){
		@set_time_limit(300);
	}

/*
 * ------------------------------------------------------
 *  启动计时器(基准测试)
 * ------------------------------------------------------
 */
	$BM =& load_class('Benchmark', 'core');
	$BM->mark('total_execution_time_start');
	$BM->mark('loading_time:_base_classes_start');// 设置加载时间开始标识点

/*
 * ------------------------------------------------------
 *  实例化钩子类
 * ------------------------------------------------------
 */
	$EXT =& load_class('Hooks', 'core');

/*
 * ------------------------------------------------------
 *  调用 "pre_system" 钩子
 * ------------------------------------------------------
 */
	$EXT->_call_hook('pre_system');

/*
 * ------------------------------------------------------
 *  实例化配置类
 * ------------------------------------------------------
 */
	$CFG =& load_class('Config', 'core');

	// 是否在 index.php 中手动设置了配置项
	if (isset($assign_to_config)){
		$CFG->_assign_to_config($assign_to_config);
	}

/*
 * ------------------------------------------------------
 *  实例化 UTF-8 类
 * ------------------------------------------------------
 *
 * 注意: 因为UTF-8 类在初期就被使用, 所以在此处加载是很重要的
 * 不过它不能正确判断 UTf-8 是否被支持直到实例化配置类
 *
 */

	$UNI =& load_class('Utf8', 'core');

/*
 * ------------------------------------------------------
 *  实例化 URI 类
 * ------------------------------------------------------
 */
	$URI =& load_class('URI', 'core');

/*
 * ------------------------------------------------------
 *  实例化路由类并设置路由
 * ------------------------------------------------------
 */
	$RTR =& load_class('Router', 'core');
	$RTR->_set_routing();

	// 在 index.php 中配置了 $routing 的话, 将覆写系统默认的路由
	if (isset($routing)){
		$RTR->_set_overrides($routing);
	}

/*
 * ------------------------------------------------------
 *  实例化输出类
 * ------------------------------------------------------
 */
	$OUT =& load_class('Output', 'core');

/*
 * ------------------------------------------------------
 *	是否存在有效的缓存文件
 * ------------------------------------------------------
 */
	if ($EXT->_call_hook('cache_override') === FALSE){
		if ($OUT->_display_cache($CFG, $URI) == TRUE){
			exit;
		}
	}

/*
 * -----------------------------------------------------
 * Load the security class for xss and csrf support
 * -----------------------------------------------------
 */
	$SEC =& load_class('Security', 'core');

/*
 * ------------------------------------------------------
 *  实例化输入类并清理全局数组
 * ------------------------------------------------------
 */
	$IN	=& load_class('Input', 'core');

/*
 * ------------------------------------------------------
 *  实例化语言类
 * ------------------------------------------------------
 */
	$LANG =& load_class('Lang', 'core');

/*
 * ------------------------------------------------------
 *  加载应用程序控制器和本地控制器
 * ------------------------------------------------------
 *
 */
	// 加载控制器基类
	require BASEPATH.'core/Controller.php';

	function &get_instance(){
		return CI_Controller::get_instance();
	}

	if (file_exists(APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller.php')){
		require APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller.php';
	}

	// 加载本地应用程序控制器
	// 注意: 路由类将使用 router->_validate_request() 自动验证控制器的路径
	// 如果失败意味着在 Routes.php 中指定的默认控制器无效
	if ( ! file_exists(APPPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().'.php')){
		show_error('无法加载默认的控制器. 请确保在 Routes.php 文件中指定的控制器是有效的.');
	}

	include(APPPATH.'controllers/'.$RTR->fetch_directory().$RTR->fetch_class().'.php');

	// 设置加载时间结束标识点
	$BM->mark('loading_time:_base_classes_end');

/*
 * ------------------------------------------------------
 *  安全检查
 * ------------------------------------------------------
 *
 *  None of the functions in the app controller or the
 *  loader class can be called via the URI, nor 控制器函数不以下划线开头
 */
	$class  = $RTR->fetch_class();
	$method = $RTR->fetch_method();

	if ( ! class_exists($class)
		OR strncmp($method, '_', 1) == 0
		OR in_array(strtolower($method), array_map('strtolower', get_class_methods('CI_Controller')))
		){
		if ( ! empty($RTR->routes['404_override'])){
			$x = explode('/', $RTR->routes['404_override']);
			$class = $x[0];
			$method = (isset($x[1]) ? $x[1] : 'index');
			if ( ! class_exists($class)){
				if ( ! file_exists(APPPATH.'controllers/'.$class.'.php')){
					show_404("{$class}/{$method}");
				}

				include_once(APPPATH.'controllers/'.$class.'.php');
			}
		}else {
			show_404("{$class}/{$method}");
		}
	}

/*
 * ------------------------------------------------------
 *  调用 "pre_controller" 钩子
 * ------------------------------------------------------
 */
	$EXT->_call_hook('pre_controller');

/*
 * ------------------------------------------------------
 *  实例化控制器
 * ------------------------------------------------------
 */
	// 设置控制器开始标识点
	$BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_start');

	$CI = new $class();

/*
 * ------------------------------------------------------
 *  调用 "post_controller_constructor" 钩子
 * ------------------------------------------------------
 */
	$EXT->_call_hook('post_controller_constructor');

/*
 * ------------------------------------------------------
 *  调用请求的方法
 * ------------------------------------------------------
 */
	// 是否存在 "remap" 函数? 如果是, 调用它
	if (method_exists($CI, '_remap')){
		$CI->_remap($method, array_slice($URI->rsegments, 2));
	}else {
		// is_callable() returns TRUE on some versions of PHP 5 for private and protected
		// methods, so we'll use this workaround for consistent behavior
		if ( ! in_array(strtolower($method), array_map('strtolower', get_class_methods($CI)))){
			// Check and see if we are using a 404 override and use it.
			if ( ! empty($RTR->routes['404_override'])){
				$x = explode('/', $RTR->routes['404_override']);
				$class = $x[0];
				$method = (isset($x[1]) ? $x[1] : 'index');
				if ( ! class_exists($class)){
					if ( ! file_exists(APPPATH.'controllers/'.$class.'.php')){
						show_404("{$class}/{$method}");
					}

					include_once(APPPATH.'controllers/'.$class.'.php');
					unset($CI);
					$CI = new $class();
				}
			}else {
				show_404("{$class}/{$method}");
			}
		}

		// 调用请求的方法
		// Any URI segments present (besides the class/function) will be passed to the method for convenience
		call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
	}

	// 设置控制器结束标识点
	$BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_end');

/*
 * ------------------------------------------------------
 *  调用 "post_controller" 钩子
 * ------------------------------------------------------
 */
	$EXT->_call_hook('post_controller');

/*
 * ------------------------------------------------------
 *  发送最终呈现的输出到浏览器
 * ------------------------------------------------------
 */
	if ($EXT->_call_hook('display_override') === FALSE){
		$OUT->_display();
	}

/*
 * ------------------------------------------------------
 *  调用 "post_system" 钩子
 * ------------------------------------------------------
 */
	$EXT->_call_hook('post_system');

/*
 * ------------------------------------------------------
 *  关闭数据库连接(如果存在)
 * ------------------------------------------------------
 */
	if (class_exists('CI_DB') AND isset($CI->db)){
		$CI->db->close();
	}