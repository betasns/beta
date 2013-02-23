<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 装载类
 *
 */
class CI_Loader {

	// 所有这些都是自动设置, 不要修改它们
	/**
	 * 输出缓冲机制的嵌套级别
	 *
	 * @var int
	 * @access protected
	 */
	protected $_ci_ob_level;
	/**
	 * 载入视图的路径列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_view_paths		= array();
	/**
	 * 载入类库的路径列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_library_paths	= array();
	/**
	 * 载入模型的路径列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_model_paths		= array();
	/**
	 * 载入辅助函数的路径列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_helper_paths		= array();
	/**
	 * 已加载基础类列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_base_classes		= array();
	/**
	 * 缓存变量的列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_cached_vars		= array();
	/**
	 * 已加载类列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_classes			= array();
	/**
	 * 已加载文件列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_loaded_files		= array();
	/**
	 * 已加载模型列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_models			= array();
	/**
	 * 已加载辅助函数列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_helpers			= array();
	/**
	 * 类名映射列表
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_varmap			= array('unit_test' => 'unit',
											'user_agent' => 'agent');

	/**
	 * 构造函数
	 *
	 * 设置视图文件的路径, 获取初始化输出缓冲区的嵌套级别
	 */
	public function __construct(){
		$this->_ci_ob_level  = ob_get_level();
		$this->_ci_library_paths = array(APPPATH, BASEPATH);
		$this->_ci_helper_paths = array(APPPATH, BASEPATH);
		$this->_ci_model_paths = array(APPPATH);
		$this->_ci_view_paths = array(APPPATH.'views/'	=> TRUE);

		log_message('debug', "初始化装载类");
	}

	// --------------------------------------------------------------------

	/**
	 * 初始化类加载器
	 *
	 * 这个方法只在 CI_Controller 中被调用一次
	 *
	 * @param 	array
	 * @return 	object
	 */
	public function initialize(){
		$this->_ci_classes = array();
		$this->_ci_loaded_files = array();
		$this->_ci_models = array();
		$this->_base_classes =& is_loaded();

		$this->_ci_autoloader();

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 已加载类
	 *
	 * 用于测试一个类是否已经在 self::$_ci_classes 数组中
	 * 如果类已经被加载了, 这个函数返回这个对象的名字; 否则, 返回 FALSE
	 *
	 * 通常用在 form_helper -> _get_validation_object()
	 *
	 * @param 	string	检查的类
	 * @return 	mixed	在 CI 超级对象中的类对象名或 FALSE
	 */
	public function is_loaded($class){
		if (isset($this->_ci_classes[$class])){
			return $this->_ci_classes[$class];
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * 加载类
	 *
	 * 加载和实例化类
	 * 这个函数设计用来在应用程序控制器中调用
	 * 在控制器中这样调用: $this->load->library(类库名);
	 * 类库可以是个数组, 用于同时载入多个类
	 *
	 * @param	string	类名
	 * @param	mixed	可选参数
	 * @param	string	可选对象名
	 * @return	void
	 */
	public function library($library = '', $params = NULL, $object_name = NULL){
		if (is_array($library)){
			foreach ($library as $class){
				$this->library($class, $params);
			}

			return;
		}

		if ($library == '' OR isset($this->_base_classes[$library])){
			return FALSE;
		}

		if ( ! is_null($params) && ! is_array($params)){
			$params = NULL;
		}

		$this->_ci_load_class($library, $params, $object_name);
	}

	// --------------------------------------------------------------------

	/**
	 * 加载模型
	 *
	 * 加载模型并实例化
	 *
	 * @param	string	类名
	 * @param	string	模型名
	 * @param	bool	数据库连接
	 * @return	void
	 */
	public function model($model, $name = '', $db_conn = FALSE){
		if (is_array($model)){
			foreach ($model as $babe){
				$this->model($babe);
			}
			return;
		}

		if ($model == ''){
			return;
		}

		$path = '';

		// 模型是否在子文件夹中? 如果是, 解析出文件名和路径
		if (($last_slash = strrpos($model, '/')) !== FALSE){
			// 路径在最后一个斜线的前面
			$path = substr($model, 0, $last_slash + 1);

			// 模型名在它后面
			$model = substr($model, $last_slash + 1);
		}

		if ($name == ''){
			$name = $model;
		}

		if (in_array($name, $this->_ci_models, TRUE)){
			return;
		}

		$CI =& get_instance();
		if (isset($CI->$name)){
			show_error('要载入的模型名是已经被使用的资源名: '.$name);
		}

		$model = strtolower($model);

		foreach ($this->_ci_model_paths as $mod_path){
			if ( ! file_exists($mod_path.'models/'.$path.$model.'.php')){
				continue;
			}

			if ($db_conn !== FALSE AND ! class_exists('CI_DB')){
				if ($db_conn === TRUE){
					$db_conn = '';
				}

				$CI->load->database($db_conn, FALSE, TRUE);
			}

			if ( ! class_exists('CI_Model')){
				load_class('Model', 'core');
			}

			require_once($mod_path.'models/'.$path.$model.'.php');

			$model = ucfirst($model);

			$CI->$name = new $model();

			$this->_ci_models[] = $name;
			return;
		}

		// 不能找到模型
		show_error('找不到模型: '.$model);
	}

	// --------------------------------------------------------------------

	/**
	 * 加载数据库
	 *
	 * @param	string	DB 凭证 (二维数组的第一维选项)
	 * @param	bool	是否返回 DB 对象
	 * @param	bool	是否启用活动记录 (这可以覆盖配置设置)
	 * @return	object
	 */
	public function database($params = '', $return = FALSE, $active_record = NULL){
		// 获取超级对象
		$CI =& get_instance();

		// 是否需要加载数据库类
		if (class_exists('CI_DB') AND $return == FALSE AND $active_record == NULL AND isset($CI->db) AND is_object($CI->db)){
			return FALSE;
		}

		require_once(BASEPATH.'database/DB.php');

		if ($return === TRUE){
			return DB($params, $active_record);
		}

		// 初始化 db 变量
		// 需要防止一些配置的引用错误
		$CI->db = '';

		// 加载 DB 类
		$CI->db =& DB($params, $active_record);
	}

	// --------------------------------------------------------------------

	/**
	 * 加载数据库工具类
	 *
	 * @return	string
	 */
	public function dbutil(){
		if ( ! class_exists('CI_DB')){
			$this->database();
		}

		$CI =& get_instance();

		// 为了向后兼容, 加载 dbforge 以便可以扩展 dbutils
		// 这已过时, 强烈建议不要使用
		$CI->load->dbforge();

		require_once(BASEPATH.'database/DB_utility.php');
		require_once(BASEPATH.'database/drivers/'.$CI->db->dbdriver.'/'.$CI->db->dbdriver.'_utility.php');
		$class = 'CI_DB_'.$CI->db->dbdriver.'_utility';

		$CI->dbutil = new $class();
	}

	// --------------------------------------------------------------------

	/**
	 * 加载数据库维护类
	 *
	 * @return	string
	 */
	public function dbforge(){
		if ( ! class_exists('CI_DB')){
			$this->database();
		}

		$CI =& get_instance();

		require_once(BASEPATH.'database/DB_forge.php');
		require_once(BASEPATH.'database/drivers/'.$CI->db->dbdriver.'/'.$CI->db->dbdriver.'_forge.php');
		$class = 'CI_DB_'.$CI->db->dbdriver.'_forge';

		$CI->dbforge = new $class();
	}

	// --------------------------------------------------------------------

	/**
	 * 加载视图
	 *
	 * 它有三个参数:
	 * 1. 视图文件名
	 * 2. 在视图中需要使用的数据的关联数组
	 * 3. TRUE/FALSE - 第三个可选参数可以改变函数的行为, 让数据作为字符串返回而不是发送到浏览器
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	void
	 */
	public function view($view, $vars = array(), $return = FALSE){
		return $this->_ci_load(array('_ci_view' => $view, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return));
	}

	// --------------------------------------------------------------------

	/**
	 * 加载文件
	 *
	 * 这是一个通用的文件装载器
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	public function file($path, $return = FALSE){
		return $this->_ci_load(array('_ci_path' => $path, '_ci_return' => $return));
	}

	// --------------------------------------------------------------------

	/**
	 * 设置变量
	 *
	 * 一旦变量被设置了, 它在控制器和视图文件中都是可用的
	 *
	 * @param	array
	 * @param 	string
	 * @return	void
	 */
	public function vars($vars = array(), $val = ''){
		if ($val != '' AND is_string($vars)){
			$vars = array($vars => $val);
		}

		$vars = $this->_ci_object_to_array($vars);

		if (is_array($vars) AND count($vars) > 0){
			foreach ($vars as $key => $val){
				$this->_ci_cached_vars[$key] = $val;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 获取变量
	 *
	 * 检查变量是否设置并获取
	 *
	 * @param	array
	 * @return	void
	 */
	public function get_var($key){
		return isset($this->_ci_cached_vars[$key]) ? $this->_ci_cached_vars[$key] : NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * 加载辅助函数
	 *
	 * 该函数用于加载指定的辅助函数
	 *
	 * @param	mixed
	 * @return	void
	 */
	public function helper($helpers = array()){
		foreach ($this->_ci_prep_filename($helpers, '_helper') as $helper){
			if (isset($this->_ci_helpers[$helper])){
				continue;
			}

			$ext_helper = APPPATH.'helpers/'.config_item('subclass_prefix').$helper.'.php';

			// 是否是辅助函数扩展请求
			if (file_exists($ext_helper)){
				$base_helper = BASEPATH.'helpers/'.$helper.'.php';

				if ( ! file_exists($base_helper)){
					show_error('无法加载请求文件: helpers/'.$helper.'.php');
				}

				include_once($ext_helper);
				include_once($base_helper);
				
				$this->_ci_helpers[$helper] = TRUE;
				log_message('debug', '加载辅助函数: '.$helper);
				continue;
			}

			// 加载辅助函数
			foreach ($this->_ci_helper_paths as $path){
				if (file_exists($path.'helpers/'.$helper.'.php')){
					include_once($path.'helpers/'.$helper.'.php');

					$this->_ci_helpers[$helper] = TRUE;
					log_message('debug', '加载辅助函数: '.$helper);
					break;
				}
			}

			// 不能加载辅助函数
			if ( ! isset($this->_ci_helpers[$helper])){
				show_error('无法加载请求文件: helpers/'.$helper.'.php');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 加载辅助函数
	 *
	 * 这是上面函数的一个别名
	 *
	 * @param	array
	 * @return	void
	 */
	public function helpers($helpers = array()){
		$this->helper($helpers);
	}

	// --------------------------------------------------------------------

	/**
	 * 加载语言文件
	 *
	 * @param	array
	 * @param	string
	 * @return	void
	 */
	public function language($file = array(), $lang = ''){
		$CI =& get_instance();

		if ( ! is_array($file)){
			$file = array($file);
		}

		foreach ($file as $langfile){
			$CI->lang->load($langfile, $lang);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 加载配置文件
	 *
	 * @param	string
	 * @param	bool
	 * @param 	bool
	 * @return	void
	 */
	public function config($file = '', $use_sections = FALSE, $fail_gracefully = FALSE){
		$CI =& get_instance();
		$CI->config->load($file, $use_sections, $fail_gracefully);
	}

	// --------------------------------------------------------------------

	/**
	 * 驱动
	 *
	 * 加载驱动类
	 *
	 * @param	string	类名
	 * @param	mixed	可选参数
	 * @param	string	可选对象名
	 * @return	void
	 */
	public function driver($library = '', $params = NULL, $object_name = NULL){
		if ( ! class_exists('CI_Driver_Library')){
			// 在此并不实例化对象, 它将不类本身执行
			require BASEPATH.'libraries/Driver.php';
		}

		if ($library == ''){
			return FALSE;
		}

		// 可以节省加载时间因为驱动总是在一个子文件夹并且通常相同地命名类
		if ( ! strpos($library, '/')){
			$library = ucfirst($library).'/'.$library;
		}

		return $this->library($library, $params, $object_name);
	}

	// --------------------------------------------------------------------

	/**
	 * 添加包路径
	 *
	 * 对类库, 模型, 辅助函数和配置路径数组前置一个父路径
	 *
	 * @param	string
	 * @param 	boolean
	 * @return	void
	 */
	public function add_package_path($path, $view_cascade=TRUE){
		$path = rtrim($path, '/').'/';

		array_unshift($this->_ci_library_paths, $path);
		array_unshift($this->_ci_model_paths, $path);
		array_unshift($this->_ci_helper_paths, $path);

		$this->_ci_view_paths = array($path.'views/' => $view_cascade) + $this->_ci_view_paths;

		// 添加配置文件路径
		$config =& $this->_ci_get_component('config');
		array_unshift($config->_config_paths, $path);
	}

	// --------------------------------------------------------------------

	/**
	 * 获取包路径
	 *
	 * 返回所有包路径列表, 默认情况下忽略 BASEPATH
	 *
	 * @param	string
	 * @return	void
	 */
	public function get_package_paths($include_base = FALSE){
		return $include_base === TRUE ? $this->_ci_library_paths : $this->_ci_model_paths;
	}

	// --------------------------------------------------------------------

	/**
	 * 删除包路径
	 *
	 * 如果存在从类, 模型, 辅助函数路径数组中删除路径
	 * 如果没有提供路径, 最近添加的路径被移除
	 *
	 * @param	type
	 * @param 	bool
	 * @return	type
	 */
	public function remove_package_path($path = '', $remove_config_path = TRUE){
		$config =& $this->_ci_get_component('config');

		if ($path == ''){
			$void = array_shift($this->_ci_library_paths);
			$void = array_shift($this->_ci_model_paths);
			$void = array_shift($this->_ci_helper_paths);
			$void = array_shift($this->_ci_view_paths);
			$void = array_shift($config->_config_paths);
		}else {
			$path = rtrim($path, '/').'/';
			foreach (array('_ci_library_paths', '_ci_model_paths', '_ci_helper_paths') as $var){
				if (($key = array_search($path, $this->{$var})) !== FALSE){
					unset($this->{$var}[$key]);
				}
			}

			if (isset($this->_ci_view_paths[$path.'views/'])){
				unset($this->_ci_view_paths[$path.'views/']);
			}

			if (($key = array_search($path, $config->_config_paths)) !== FALSE){
				unset($config->_config_paths[$key]);
			}
		}

		// 确保默认路径仍在数组中
		$this->_ci_library_paths = array_unique(array_merge($this->_ci_library_paths, array(APPPATH, BASEPATH)));
		$this->_ci_helper_paths = array_unique(array_merge($this->_ci_helper_paths, array(APPPATH, BASEPATH)));
		$this->_ci_model_paths = array_unique(array_merge($this->_ci_model_paths, array(APPPATH)));
		$this->_ci_view_paths = array_merge($this->_ci_view_paths, array(APPPATH.'views/' => TRUE));
		$config->_config_paths = array_unique(array_merge($config->_config_paths, array(APPPATH)));
	}

	// --------------------------------------------------------------------

	/**
	 * 加载
	 *
	 * 这个函数被用来载入视图和文件
	 * 变量名以前缀 _ci_ 开始以避免与视图文件的变量名冲突
	 *
	 * @param	array
	 * @return	void
	 */
	protected function _ci_load($_ci_data){
		// 设置默认数据变量
		foreach (array('_ci_view', '_ci_vars', '_ci_path', '_ci_return') as $_ci_val){
			$$_ci_val = ( ! isset($_ci_data[$_ci_val])) ? FALSE : $_ci_data[$_ci_val];
		}

		$file_exists = FALSE;

		// 设置请求文件路径
		if ($_ci_path != ''){
			$_ci_x = explode('/', $_ci_path);
			$_ci_file = end($_ci_x);
		}else {
			$_ci_ext = pathinfo($_ci_view, PATHINFO_EXTENSION);
			$_ci_file = ($_ci_ext == '') ? $_ci_view.'.php' : $_ci_view;

			foreach ($this->_ci_view_paths as $view_file => $cascade){
				if (file_exists($view_file.$_ci_file)){
					$_ci_path = $view_file.$_ci_file;
					$file_exists = TRUE;
					break;
				}

				if ( ! $cascade){
					break;
				}
			}
		}

		if ( ! $file_exists && ! file_exists($_ci_path)){
			show_error('不能加载请求文件: '.$_ci_file);
		}

		// 这使得在控制器和模型中可以通过使用 $this->load 载入任何东西(视图, 文件, 等等)
		$_ci_CI =& get_instance();
		foreach (get_object_vars($_ci_CI) as $_ci_key => $_ci_var){
			if ( ! isset($this->$_ci_key)){
				$this->$_ci_key =& $_ci_CI->$_ci_key;
			}
		}

		/*
		 * 提取和缓存变量
		 * 
		 * 可以使用专用的 $this->load_vars() 函数或者是通过这个函数的第二个参数设置变量
		 * 合并这两种类型并缓存, 以便嵌入其它视图的视图可以访问到这些变量
		 */
		if (is_array($_ci_vars)){
			$this->_ci_cached_vars = array_merge($this->_ci_cached_vars, $_ci_vars);
		}
		// 通过 extract 可以提取出数组中的内容, 方便直接使用, 大多数的视图使用控制器的变量都是通过这样实现的
		extract($this->_ci_cached_vars);

		/*
		 * 缓冲输出
		 *
		 * 缓冲输出有两个原因:
		 * 1. 速度. 可以获得重大的速度提升
		 * 2. 最终渲染的模板可以通过 output 类进行后置处理
		 * 为什么需要后置处理?  一个原因是为了显示页面的载入时间
		 * 除非我们可以在发送到浏览器之前与页面内容进行交互, 然后终止计时器, 否则时间统计将不准确
		 */
		ob_start();

		// 下面的处理使得使用短标记成为可能, 不管是否是在 PHP 配置文件中禁止了使用短标记, 我们都可以使用, 极大地提高了模板开发效率

		if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE){
			echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($_ci_path))));
		}else {
			include($_ci_path); // include() vs include_once() 允许多个视图具有相同名称
		}

		log_message('debug', '加载文件: '.$_ci_path);

		// 如果请求的话, 返回渲染后的模板文件数据
		if ($_ci_return === TRUE){
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}

		/*
		 * 刷新缓冲区
		 *
		 * 为了允许视图嵌套, 需要在第一层缓冲级别上刷新缓冲, 输出内容
		 * 如果不是第一层缓冲的话, 将会被包含到第一层模板中
		 *
		 */
		if (ob_get_level() > $this->_ci_ob_level + 1){
			ob_end_flush();// 刷新缓冲区, 关闭当前输出缓冲
		}else {
			$_ci_CI->output->append_output(ob_get_contents());// 将缓冲区内容追加到输出缓冲流中
			@ob_end_clean();// 清空当前缓冲区, 关闭当前输出缓冲
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 加载类
	 *
	 * 此函数加载所请求类
	 *
	 * @param	string	加载项
	 * @param	mixed	初始化类参数
	 * @param	string	可选对象名
	 * @return	void
	 */
	protected function _ci_load_class($class, $params = NULL, $object_name = NULL){
		// 获取类名, 并从两端删除任何斜线
		// 类名可以包含目录路径, 但不希望包含前导斜线
		$class = str_replace('.php', '', trim($class, '/'));

		// 路径是否包含类名, 查找斜线判定
		$subdir = '';
		if (($last_slash = strrpos($class, '/')) !== FALSE){
			// 提取路径
			$subdir = substr($class, 0, $last_slash + 1);

			// 获取文件名
			$class = substr($class, $last_slash + 1);
		}

		// 处理文件名的大写与小写版本
		foreach (array(ucfirst($class), strtolower($class)) as $class){
			$subclass = APPPATH.'libraries/'.$subdir.config_item('subclass_prefix').$class.'.php';

			// 是否是类的扩展请求
			if (file_exists($subclass)){
				$baseclass = BASEPATH.'libraries/'.ucfirst($class).'.php';

				if ( ! file_exists($baseclass)){
					log_message('error', "不能加载请求类: ".$class);
					show_error("不能加载请求类: ".$class);
				}

				// 安全性: 这个类是否已经被以前的调用加载
				if (in_array($subclass, $this->_ci_loaded_files)){
					// 在认为这是重复请求之前, 看看是否是自定义对象名
					// 如果是的话, 返回一个新的对象实例
					if ( ! is_null($object_name)){
						$CI =& get_instance();
						if ( ! isset($CI->$object_name)){
							return $this->_ci_init_class($class, config_item('subclass_prefix'), $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." 类已经加载. 忽略第二次尝试.");
					return;
				}

				include_once($baseclass);
				include_once($subclass);
				$this->_ci_loaded_files[] = $subclass;

				return $this->_ci_init_class($class, config_item('subclass_prefix'), $params, $object_name);
			}

			// 查询请求类文件并加载
			$is_duplicate = FALSE;
			foreach ($this->_ci_library_paths as $path){
				$filepath = $path.'libraries/'.$subdir.$class.'.php';

				// 文件是否存在
				if ( ! file_exists($filepath)){
					continue;
				}

				// 安全性: 这个类是否已经被以前的调用加载
				if (in_array($filepath, $this->_ci_loaded_files)){
					// 在认为这是重复请求之前, 看看是否是自定义对象名
					// 如果是的话, 返回一个新的对象实例
					if ( ! is_null($object_name)){
						$CI =& get_instance();
						if ( ! isset($CI->$object_name)){
							return $this->_ci_init_class($class, '', $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." 类已经加载. 忽略第二次尝试.");
					return;
				}

				include_once($filepath);
				$this->_ci_loaded_files[] = $filepath;
				return $this->_ci_init_class($class, '', $params, $object_name);
			}

		}

		// 最后一次尝试.  也许库是在一个子目录中, 但它没有规定呢?
		if ($subdir == ''){
			$path = strtolower($class).'/'.$class;
			return $this->_ci_load_class($path, $params);
		}

		// 如果加载失败于重复请求就不发出错误
		if ($is_duplicate == FALSE){
			log_message('error', "不能加载请求类: ".$class);
			show_error("不能加载请求类: ".$class);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 实例化类
	 *
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	string	可选对象名
	 * @return	null
	 */
	protected function _ci_init_class($class, $prefix = '', $config = FALSE, $object_name = NULL){
		// 这个类是否有关联的配置文件?  注意: 应该始终小写
		if ($config === NULL){
			// 获取包含任何包路径的配置路径
			$config_component = $this->_ci_get_component('config');

			if (is_array($config_component->_config_paths)){
				// 在第一次找到文件后退出, 从而包文件不被默认路径覆盖
				foreach ($config_component->_config_paths as $path){
					// 为了区分文件名大小写的服务器, 对大小写都进行测试
					if (defined('ENVIRONMENT') AND file_exists($path .'config/'.ENVIRONMENT.'/'.strtolower($class).'.php')){
						include($path .'config/'.ENVIRONMENT.'/'.strtolower($class).'.php');
						break;
					}elseif (defined('ENVIRONMENT') AND file_exists($path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php')){
						include($path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php');
						break;
					}elseif (file_exists($path .'config/'.strtolower($class).'.php')){
						include($path .'config/'.strtolower($class).'.php');
						break;
					}elseif (file_exists($path .'config/'.ucfirst(strtolower($class)).'.php')){
						include($path .'config/'.ucfirst(strtolower($class)).'.php');
						break;
					}
				}
			}
		}

		if ($prefix == ''){
			if (class_exists('CI_'.$class)){
				$name = 'CI_'.$class;
			}elseif (class_exists(config_item('subclass_prefix').$class)){
				$name = config_item('subclass_prefix').$class;
			}else {
				$name = $class;
			}
		}else {
			$name = $prefix.$class;
		}

		// 类名是否有效
		if ( ! class_exists($name)){
			log_message('error', "不存在类: ".$name);
			show_error("不存在类: ".$class);
		}

		// 设置变量名
		$class = strtolower($class);

		if (is_null($object_name)){
			$classvar = ( ! isset($this->_ci_varmap[$class])) ? $class : $this->_ci_varmap[$class];
		}else {
			$classvar = $object_name;
		}

		// 保存类名和对象名
		$this->_ci_classes[$class] = $classvar;
		
		// 实例化类
		$CI =& get_instance();
		if ($config !== NULL){
			$CI->$classvar = new $name($config);
		}else {
			$CI->$classvar = new $name;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 自动加载
	 *
	 * 配置文件 config/autoload.php 包含了一个允许自动加载的子系统, 类库和辅助函数的数组
	 *
	 * @param	array
	 * @return	void
	 */
	private function _ci_autoloader(){
		if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/autoload.php')){
			include(APPPATH.'config/'.ENVIRONMENT.'/autoload.php');
		}else {
			include(APPPATH.'config/autoload.php');
		}

		if ( ! isset($autoload)){
			return FALSE;
		}

		// 自动加载包
		if (isset($autoload['packages'])){
			foreach ($autoload['packages'] as $package_path){
				$this->add_package_path($package_path);
			}
		}

		// 加载自定义配置文件
		if (count($autoload['config']) > 0){
			$CI =& get_instance();
			foreach ($autoload['config'] as $key => $val){
				$CI->config->load($val);
			}
		}

		// 自动加载辅助函数及语言
		foreach (array('helper', 'language') as $type){
			if (isset($autoload[$type]) AND count($autoload[$type]) > 0){
				$this->$type($autoload[$type]);
			}
		}

		// 一个小调整, 以保持向后兼容
		// $autoload['core'] 已被废弃
		if ( ! isset($autoload['libraries']) AND isset($autoload['core'])){
			$autoload['libraries'] = $autoload['core'];
		}

		// 加载类
		if (isset($autoload['libraries']) AND count($autoload['libraries']) > 0){
			// 加载数据库驱动程序
			if (in_array('database', $autoload['libraries'])){
				$this->database();
				$autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
			}

			// 加载其余类
			foreach ($autoload['libraries'] as $item){
				$this->library($item);
			}
		}

		// 自动加载模型
		if (isset($autoload['model'])){
			$this->model($autoload['model']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 对象转换为数组
	 *
	 * 接受一个对象作为输入并将其转换为由对象属性组成的关联数组
	 *
	 * @param	object
	 * @return	array
	 */
	protected function _ci_object_to_array($object){
		return (is_object($object)) ? get_object_vars($object) : $object;
	}

	// --------------------------------------------------------------------

	/**
	 * 获取指定类库或者是模型的引用
	 *
	 * @param 	string
	 * @return	bool
	 */
	protected function &_ci_get_component($component){
		$CI =& get_instance();
		return $CI->$component;
	}

	// --------------------------------------------------------------------

	/**
	 * 预处理文件名
	 *
	 * 这个函数处理传入的文件名, 确保其是指定的扩展名的文件, 返回替换后的文件名
	 *
	 * @param	mixed
	 * @param 	string
	 * @return	array
	 */
	protected function _ci_prep_filename($filename, $extension){
		if ( ! is_array($filename)){
			return array(strtolower(str_replace('.php', '', str_replace($extension, '', $filename)).$extension));
		}else {
			foreach ($filename as $key => $val){
				$filename[$key] = strtolower(str_replace('.php', '', str_replace($extension, '', $val)).$extension);
			}

			return $filename;
		}
	}
	
}