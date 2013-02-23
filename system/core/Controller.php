<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 应用程序控制器类
 *
 * 这个类对象是包含所有类的超级类
 *
 */
class CI_Controller {

	private static $instance;

	/**
	 * 构造函数
	 */
	public function __construct(){
		self::$instance =& $this;
		
		// 将所有已经被引导文件 (CodeIgniter.php) 实例化的类对象赋给类变量
		// 因此 CI 可以作为超级对象运行
		foreach (is_loaded() as $var => $class){
			$this->$var =& load_class($class);
		}

		$this->load =& load_class('Loader', 'core');

		$this->load->initialize();
		
		log_message('debug', "初始化控制器类");
	}

	public static function &get_instance(){
		return self::$instance;
	}
}