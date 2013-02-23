<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 全局函数
 *
 */

// ------------------------------------------------------------------------

/**
* 判断系统的 PHP 版本号是否高于所提供的 version_number
*
* 由于有一些地方需要测试条件 PHP版本 > 5, 因此设置一个静态变量
*
* @access	public
* @param	string
* @return	bool	TRUE 如果当前版本是 $version 或更高
*/
if ( ! function_exists('is_php')){
	function is_php($version = '5.0.0'){
		//设置静态变量 $_is_php 
		static $_is_php;
		
		//处理参数
		$version = (string)$version;

		//如果第一次运行此函数, 获取判断结果并赋值给静态变量
		if ( ! isset($_is_php[$version])){
			$_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
		}

		return $_is_php[$version];
	}
}

// ------------------------------------------------------------------------

/**
 * 测试文件可写性
 * 一个检测文件/文件夹可写的跨平台的可靠的方法
 *
 * PHP 中原生的 is_writable() 函数在 windows 系统中不能准确判断文件是否可写
 * 如果文件只是可读, is_writable() 也会返回 true, 无法达到真正的判断目的
 * 如果是在 Unix 内核的系统中, 在配置文件 safe_mode 参数被设置为 on 时 is_writable() 函数也不奏效
 *
 * @access	private
 * @return	void
 */
if ( ! function_exists('is_really_writable')){
	function is_really_writable($file){
		// 如果是 Unix 系统并且 safe_mode 关闭直接使用 is_writable
		// 目录分隔符是'/', 表名是在类 Unix 系统, 然后判断配置文件中是否开启 safe_mode
		if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE){
			return is_writable($file);
		}

		// 对于 windows 系统和 safe_mode 参数被设置为 on 的情况, 将写入文件并读取
		if (is_dir($file)){
			// 生成要写入的文件名, 通过 MD5 加密的随机数产生
			$file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));

			if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE){
				return FALSE;
			}

			fclose($fp);
			@chmod($file, DIR_WRITE_MODE);// 改变文件权限为0777
			@unlink($file);// 删除文件
			return TRUE;
		}elseif ( ! is_file($file) OR ($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE){
			// 如果 $file 不是文件或者是不能以写创建模式打开文件的话, 返回 FALSE
			return FALSE;
		}

		fclose($fp);
		return TRUE;
	}
}

// ------------------------------------------------------------------------

/**
* 类库载入
*
* 这个函数给出类的单例模式(singleton)
* 如果请求的类不存在, 该函数将会实例化它并加入到静态变量中; 如果已经被实例化过了, 直接返回已经实例化的静态变量
*
* 要覆写某个类库中的类的话, 可以通过新建一个类文件, 命名规则为配置项 subclass_prefix 加上要扩展的类名
* 这样就避免了直接修改原有类库, 提高了可移植性
* 
* @access	public
* @param	string	请求的类名
* @param	string	查找这个类的目录
* @param	string	类名前缀
* @return	object
*/
if ( ! function_exists('load_class')){
	function &load_class($class, $directory = 'libraries', $prefix = 'CI_'){
		// 设置静态变量 $_classes 用于存放已经实例化过的类对象
		static $_classes = array();

		// 判断请求的类对象是否存在, 如果存在的话, 返回它
		if (isset($_classes[$class])){
			return $_classes[$class];
		}

		$name = FALSE;// 初始化类名

		// 首先在本地(application)/libraries 文件夹查询类, 如果找不到, 在框架(system)/libraries 文件夹查询
		foreach (array(APPPATH, BASEPATH) as $path){
			// 判断类名对应的类库文件是否存在
			if (file_exists($path.$directory.'/'.$class.'.php')){
				$name = $prefix.$class;// 加前缀后的类名

				// 如果该类在当前环境中不存在, 则载入这个文件, 如果有的话, 就不再载入, 防止冲突
				if (class_exists($name) === FALSE){
					require($path.$directory.'/'.$class.'.php');
				}

				break;
			}
		}

		// 请求的类是否有扩展? 如果有的话也载入
		if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php')){
			$name = config_item('subclass_prefix').$class;

			if (class_exists($name) === FALSE){
				require(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php');
			}
		}

		// 如果没有找到要找的类
		if ($name === FALSE){
			// 注意: 为了避免在 Excptions 类可能产生的自引用循环, 使用 exit() 而不是 show_error()
			exit('无法找到指定类: '.$class.'.php');
		}

		// 跟踪刚才载入的类
		is_loaded($class);

		// 实例化要载入的类, 并加入到静态字段中存储
		// $name 或者是 $prefix.$class, 或者是 config_item('subclass_prefix').$class(要加载类的扩展类)
		$_classes[$class] = new $name();
		return $_classes[$class];
	}
}

// --------------------------------------------------------------------

/**
* 保持跟踪已载入类库
* 通过上面的 load_class() 函数调用此函数
* 将传入的类名加入到 $_is_loaded 中并返回 $_is_loaded
*
* @access	public
* @return	array
*/
if ( ! function_exists('is_loaded')){
	function &is_loaded($class = ''){
		static $_is_loaded = array();

		if ($class != ''){
			$_is_loaded[strtolower($class)] = $class;
		}

		return $_is_loaded;
	}
}

// ------------------------------------------------------------------------

/**
* 载入主要的 config.php 文件
*
* 这个函数让我们获取配置文件，即使是 Config 类没有被实例化
*
* @access	private
* @return	array
*/
if ( ! function_exists('get_config')){
	/*
	 * 这里使用 & 引用返回的原因是因为返回的是 config.php 文件中的所有内容, 内容比较多
	 * 如果使用普通的返回的话, 会产生大量的内存复制, 浪费资源, 使用引用返回可以降低内存的消耗
	 * 函数返回引用的使用, 获取返回值的时候也要加上 &, 例如: $config = &get_config();
	 */
	function &get_config($replace = array()){
		static $_config;

		if (isset($_config)){
			return $_config[0];
		}

		// 如果没有定义 ENVIRONMENT, 或虽然定义了 ENVIRONMENT, 但是 config/ENVIRONMENT/config.php 不存在, 调用默认位置的配置文件
		if ( ! defined('ENVIRONMENT') OR ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php')){
			$file_path = APPPATH.'config/config.php';
		}

		// 检查配置文件是否存在
		if ( ! file_exists($file_path)){
			exit('配置文件不存在.');
		}

		require($file_path);

		// 配置文件中不存在 $config 变量或者是 $config 不是数组, 报错
		if ( ! isset($config) OR ! is_array($config)){
			exit('配置文件没有被正确格式化.');
		}

		// 是否有值被动态的替换了, 传入参数为替换的值
		if (count($replace) > 0){
			foreach ($replace as $key => $val){
				if (isset($config[$key])){
					$config[$key] = $val;
				}
			}
		}

		return $_config[0] =& $config;
	}
}

// ------------------------------------------------------------------------

/**
* 返回指定配置项
* 只会获取一次, 并缓存
*
* @access	public
* @return	mixed
*/
if ( ! function_exists('config_item')){
	function config_item($item){
		static $_config_item = array();

		if ( ! isset($_config_item[$item])){
			$config =& get_config();

			if ( ! isset($config[$item])){
				return FALSE;
			}
			$_config_item[$item] = $config[$item];
		}

		return $_config_item[$item];
	}
}

// ------------------------------------------------------------------------

/**
* 错误处理
*
* 调用异常处理类和使用标准错误模板显示错误
* 此函数会直接发送错误到浏览器并退出
*
* @access	public
* @return	void
*/
if ( ! function_exists('show_error')){
	function show_error($message, $status_code = 500, $heading = '遇到错误'){
		$_error =& load_class('Exceptions', 'core');
		echo $_error->show_error($heading, $message, 'error_general', $status_code);
		exit;
	}
}

// ------------------------------------------------------------------------

/**
* 404 页面
*
* 这个函数与上面的 show_error() 函数类似, 不过, 代替标准错误输出模板的是 404 错误显示
*
* @access	public
* @return	void
*/
if ( ! function_exists('show_404')){
	function show_404($page = '', $log_error = TRUE){
		$_error =& load_class('Exceptions', 'core');
		$_error->show_404($page, $log_error);
		exit;
	}
}

// ------------------------------------------------------------------------

/**
* 错误日志接口
*
* 使用这个函数作为一个简单的机制去访问日志类并发送消息以记录在日志
*
* @access	public
* @return	void
*/
if ( ! function_exists('log_message')){
	function log_message($level = 'error', $message, $php_error = FALSE){
		static $_log;

		if (config_item('log_threshold') == 0){
			return;
		}

		$_log =& load_class('Log');
		$_log->write_log($level, $message, $php_error);
	}
}

// ------------------------------------------------------------------------

/**
 * 手动设置服务器状态头
 *
 * @access	public
 * @param	int		状态代码
 * @param	string
 * @return	void
 */
if ( ! function_exists('set_status_header')){
	function set_status_header($code = 200, $text = ''){
		$stati = array(
							200	=> 'OK',
							201	=> 'Created',
							202	=> 'Accepted',
							203	=> 'Non-Authoritative Information',
							204	=> 'No Content',
							205	=> 'Reset Content',
							206	=> 'Partial Content',

							300	=> 'Multiple Choices',
							301	=> 'Moved Permanently',
							302	=> 'Found',
							304	=> 'Not Modified',
							305	=> 'Use Proxy',
							307	=> 'Temporary Redirect',

							400	=> 'Bad Request',
							401	=> 'Unauthorized',
							403	=> 'Forbidden',
							404	=> 'Not Found',
							405	=> 'Method Not Allowed',
							406	=> 'Not Acceptable',
							407	=> 'Proxy Authentication Required',
							408	=> 'Request Timeout',
							409	=> 'Conflict',
							410	=> 'Gone',
							411	=> 'Length Required',
							412	=> 'Precondition Failed',
							413	=> 'Request Entity Too Large',
							414	=> 'Request-URI Too Long',
							415	=> 'Unsupported Media Type',
							416	=> 'Requested Range Not Satisfiable',
							417	=> 'Expectation Failed',

							500	=> 'Internal Server Error',
							501	=> 'Not Implemented',
							502	=> 'Bad Gateway',
							503	=> 'Service Unavailable',
							504	=> 'Gateway Timeout',
							505	=> 'HTTP Version Not Supported'
						);

		if ($code == '' OR ! is_numeric($code)){
			show_error('状态代码必须是数字', 500);
		}

		if (isset($stati[$code]) AND $text == ''){
			$text = $stati[$code];
		}

		if ($text == ''){
			show_error('没有可用的状态代码.  请检查状态代码数或提供信息文本.', 500);
		}

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

		// php_sapi_name() 返回 Web 服务器与 PHP 之间的接口类型
		if (substr(php_sapi_name(), 0, 3) == 'cgi'){
			header("Status: {$code} {$text}", TRUE);
		}elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0'){
			header($server_protocol." {$code} {$text}", TRUE, $code);
		}else {
			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
		}
	}
}

// --------------------------------------------------------------------

/**
* 异常处理
*
* 这是一个在 Codeigniter.php 的顶部(常量定义完后)声明的自定义异常处理器
* 使用它的主要原因是在用户可能没有访问服务器日志的权限时允许把 PHP 错误记录在日志文件中
* 由于这个函数有效地拦截了 PHP 错误, 所以也需要基于当前的 error_reporting 级别通过 PHP 的错误模板显示错误
*
* @access	private
* @return	void
*/
if ( ! function_exists('_exception_handler')){
	function _exception_handler($severity, $message, $filepath, $line){
		 // 不需要理会 "strict" 警告, 因为它们往往将额外的没什么帮助的信息填充到日志文件中
		 // 例如, 如果运行的是 PHP 5, 但是使用了版本 4 的类函数样式(没有前缀 "public", "private" 等), 将会产生已经不赞成使用的警告
		if ($severity == E_STRICT){
			return;
		}

		$_error =& load_class('Exceptions', 'core');

		// 是否显示错误? 将获得当前的 error_reporting 级别并且找出 severity 添加
		if (($severity & error_reporting()) == $severity){
			$_error->show_php_error($severity, $message, $filepath, $line);
		}

		// 判断是否记录错误
		if (config_item('log_threshold') == 0){
			return;
		}

		$_error->log_exception($severity, $message, $filepath, $line);
	}
}

// --------------------------------------------------------------------

/**
 * 移除不可见字符
 *
 * 防止在 ascii 字符串中夹杂着 null 字符 ， 比如：Java\0script
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('remove_invisible_characters')){
	function remove_invisible_characters($str, $url_encoded = TRUE){
		$non_displayables = array();
		
		// every control character except newline (dec 10)
		// carriage return (dec 13), and horizontal tab (dec 09)
		
		if ($url_encoded){
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
* HTML 转义
*
* @access	public
* @param	mixed
* @return	mixed
*/
if ( ! function_exists('html_escape')){
	function html_escape($var){
		if (is_array($var)){
			return array_map('html_escape', $var);
		}else {
			return htmlspecialchars($var, ENT_QUOTES, config_item('charset'));
		}
	}
}