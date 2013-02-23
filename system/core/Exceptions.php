<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 异常处理类
 * 
 */
class CI_Exceptions {
	var $action;
	var $severity;
	var $message;
	var $filename;
	var $line;

	/**
	 * 输出缓冲机制的嵌套级别
	 *
	 * @var int
	 * @access public
	 */
	var $ob_level;

	/**
	 * 错误等级列表
	 *
	 * @var array
	 * @access public
	 */
	var $levels = array(
						E_ERROR				=>	'Error',				// 致命的运行时错误(它会阻止脚本的执行)
						E_WARNING			=>	'Warning',				// 运行时警告(非致命的错误)
						E_PARSE				=>	'Parsing Error',		// 编译时语法解析错误
						E_NOTICE			=>	'Notice',				// 运行时注意消息(可能是或者可能不是一个问题)
						E_CORE_ERROR		=>	'Core Error',			// 类似 E_ERROR, 但不包括 PHP 核心造成的错误
						E_CORE_WARNING		=>	'Core Warning',			// 类似 E_WARNING, 但不包括 PHP 核心错误警告
						E_COMPILE_ERROR		=>	'Compile Error',		// 致命的编译时错误
						E_COMPILE_WARNING	=>	'Compile Warning',		// 致命的编译时警告
						E_USER_ERROR		=>	'User Error',			// 用户导致的错误消息
						E_USER_WARNING		=>	'User Warning',			// 用户导致的警告
						E_USER_NOTICE		=>	'User Notice',			// 用户导致的注意消息
						E_STRICT			=>	'Runtime Notice'		// 关于 PHP 版本移植的兼容性和互操作性建议
					);


	/**
	 * 构造函数
	 */
	public function __construct(){
		$this->ob_level = ob_get_level();
		// 注意:  不要从此构造函数记录信息
	}

	// --------------------------------------------------------------------

	/**
	 * 异常记录
	 *
	 * 此函数记录 PHP 生成的错误信息
	 *
	 * @access	private
	 * @param	string	错误严重程度
	 * @param	string	错误字符串
	 * @param	string	错误文件路径
	 * @param	string	错误行号
	 * @return	string
	 */
	function log_exception($severity, $message, $filepath, $line){
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		log_message('error', 'Severity: '.$severity.'  --> '.$message. ' '.$filepath.' '.$line, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 页 没找到处理程序
	 *
	 * @access	private
	 * @param	string	the page
	 * @param 	bool	log error yes/no
	 * @return	string
	 */
	function show_404($page = '', $log_error = TRUE){
		$heading = "404 Page Not Found";
		$message = "请求的页面未找到.";

		// 默认情况下将在日志中记录, 但允许设置跳过
		if ($log_error){
			log_message('error', '404 Page Not Found --> '.$page);
		}

		echo $this->show_error($heading, $message, 'error_404', 404);
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * 通用错误页
	 *
	 * 这个函数需要一个错误信息作为输入(无论是字符串还是数组), 并且使用指定模板显示
	 *
	 * @access	private
	 * @param	string	标题
	 * @param	string	信息
	 * @param	string	模板名
	 * @param 	int		状态码
	 * @return	string
	 */
	function show_error($heading, $message, $template = 'error_general', $status_code = 500){
		set_status_header($status_code);

		$message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

		if (ob_get_level() > $this->ob_level + 1){
			ob_end_flush();
		}
		ob_start();
		include(APPPATH.'errors/'.$template.'.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * 原生的 PHP 错误处理程序
	 *
	 * @access	private
	 * @param	string	错误严重程度
	 * @param	string	错误字符串
	 * @param	string	错误文件路径
	 * @param	string	错误行数
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line){
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		$filepath = str_replace("\\", "/", $filepath);

		//出于安全考虑, 我们不显示完整的文件路径
		if (FALSE !== strpos($filepath, '/')){
			$x = explode('/', $filepath);
			$filepath = $x[count($x)-2].'/'.end($x);
		}

		if (ob_get_level() > $this->ob_level + 1){
			ob_end_flush();
		}
		ob_start();
		include(APPPATH.'errors/error_php.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}


}