<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 日志类
 */
class CI_Log {

	protected $_log_path;
	protected $_threshold	= 1;
	protected $_date_fmt	= 'Y-m-d H:i:s';
	protected $_enabled	= TRUE;
	protected $_levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

	/**
	 * 构造函数
	 */
	public function __construct(){
		$config =& get_config();

		//获取日志文件目录并判断是否可写
		$this->_log_path = ($config['log_path'] != '') ? $config['log_path'] : APPPATH.'logs/';

		if ( ! is_dir($this->_log_path) OR ! is_really_writable($this->_log_path)){
			$this->_enabled = FALSE;
		}

		//获取错误阈值
		if (is_numeric($config['log_threshold'])){
			$this->_threshold = $config['log_threshold'];
		}

		//获取日志日期格式
		if ($config['log_date_format'] != ''){
			$this->_date_fmt = $config['log_date_format'];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 写日志文件
	 *
	 * 一般情况下, 这个函数使用全局函数 log_message() 调用
	 *
	 * @param	string	错误级别
	 * @param	string	错误信息
	 * @param	bool	错误是否是原生的 PHP 错误
	 * @return	bool
	 */
	public function write_log($level = 'error', $msg, $php_error = FALSE){
		//判断日志文件目录是否可写
		if ($this->_enabled === FALSE){
			return FALSE;
		}

		//判断错误阈值是否存在以及是否符合配置
		$level = strtoupper($level);
		if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold)){
			return FALSE;
		}

		//文件名
		$filepath = $this->_log_path.'log-'.date('Y-m-d').'.php';
		$message  = '';

		//文件不存在则给出题头
		if ( ! file_exists($filepath)){
			$message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}

		//判断日志文件是否可写
		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE)){
			return FALSE;
		}

		//错误信息
		$message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n";

		//以独占锁定方式写入并关闭文件
		flock($fp, LOCK_EX);	//取得独占锁定
		fwrite($fp, $message);
		flock($fp, LOCK_UN);	//释放锁定
		fclose($fp);

		//设置文件权限
		@chmod($filepath, FILE_WRITE_MODE);
		return TRUE;
	}

}