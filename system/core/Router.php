<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 路由类
 *
 */
class CI_Router {

	/**
	 * 配置类
	 *
	 * @var object
	 * @access public
	 */
	var $config;
	/**
	 * 路由列表
	 *
	 * @var array
	 * @access public
	 */
	var $routes			= array();
	/**
	 * 错误路由列表
	 *
	 * @var array
	 * @access public
	 */
	var $error_routes	= array();
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
	var $method			= 'index';
	/**
	 * 包含请求的控制器类的子目录
	 *
	 * @var string
	 * @access public
	 */
	var $directory		= '';
	/**
	 * 默认控制器 (和方法)
	 *
	 * @var string
	 * @access public
	 */
	var $default_controller;

	/**
	 * 构造函数
	 *
	 */
	function __construct(){
		$this->config =& load_class('Config', 'core');
		$this->uri =& load_class('URI', 'core');
		log_message('debug', "初始化路由类");
	}

	// --------------------------------------------------------------------

	/**
	 * 设置路由定向
	 *
	 * 这个函数决定根据 URI 请求以及路由配置文件中已经设置的任何 "routes" 所提供的服务
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_routing(){
		// 查询字符串是否在配置文件中启用
		// 通常情况下不使用查询字符串, 因为 URI 段对搜索引擎更友好, 但查询字符串形式的 URL 是可选的
		// 如果启用此功能, 将用不同的方式收集目录/类/方法
		$segments = array();
		if ($this->config->item('enable_query_strings') === TRUE AND isset($_GET[$this->config->item('controller_trigger')])){
			if (isset($_GET[$this->config->item('directory_trigger')])){
				$this->set_directory(trim($this->uri->_filter_uri($_GET[$this->config->item('directory_trigger')])));
				$segments[] = $this->fetch_directory();
			}

			if (isset($_GET[$this->config->item('controller_trigger')])){
				$this->set_class(trim($this->uri->_filter_uri($_GET[$this->config->item('controller_trigger')])));
				$segments[] = $this->fetch_class();
			}

			if (isset($_GET[$this->config->item('function_trigger')])){
				$this->set_method(trim($this->uri->_filter_uri($_GET[$this->config->item('function_trigger')])));
				$segments[] = $this->fetch_method();
			}
		}

		// 加载 routes.php 文件
		if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/routes.php')){
			include(APPPATH.'config/'.ENVIRONMENT.'/routes.php');
		} elseif (is_file(APPPATH.'config/routes.php')){
			include(APPPATH.'config/routes.php');
		}

		// 设置 $this->routes 并释放内存
		$this->routes = ( ! isset($route) OR ! is_array($route)) ? array() : $route;
		unset($route);

		// 设置 $this->default_controller, 当不匹配时调用
		$this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

		// 是否存在任何查询字符串? 如果是, 对其验证
		if (count($segments) > 0){
			// 验证路由是否正确, 正确的话使用查询字符串路由方式, 失败的话会进入 404 页面
			return $this->_validate_request($segments);
		}

		// 获取完整 URI 字符串
		$this->uri->_fetch_uri_string();

		// URI 为空的话, 调用默认控制器
		if ($this->uri->uri_string == ''){
			return $this->_set_default_controller();
		}

		// 是否需要删除 URL 后缀
		$this->uri->_remove_url_suffix();

		// 编译段到一个数组
		$this->uri->_explode_segments();

		// 解析可能存在的任何自定义路由
		$this->_parse_routes();

		// 重建段数组索引以便它从 1 开始而不是 0
		$this->uri->_reindex_segments();
	}

	// --------------------------------------------------------------------

	/**
	 * 设置默认控制器
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_default_controller(){
		if ($this->default_controller === FALSE){
			show_error("无法确定显示内容. 在 routing 文件中没有指定默认路由.");
		}
		// 是否有指定方法
		if (strpos($this->default_controller, '/') !== FALSE){
			$x = explode('/', $this->default_controller);

			$this->set_class($x[0]);
			$this->set_method($x[1]);
			$this->_set_request($x);
		}else {
			$this->set_class($this->default_controller);
			$this->set_method('index');
			$this->_set_request(array($this->default_controller, 'index'));
		}

		// 重建段数组索引以便它从 1 开始而不是 0
		$this->uri->_reindex_segments();

		log_message('debug', "没有当前 URI. 设置为默认控制器.");
	}

	// --------------------------------------------------------------------

	/**
	 * 设置路由
	 *
	 * 这个函数需要一个 URI 段数组作为输入, 并设置当前类/方法
	 *
	 * @access	private
	 * @param	array
	 * @param	bool
	 * @return	void
	 */
	function _set_request($segments = array()){
		$segments = $this->_validate_request($segments);

		if (count($segments) == 0){
			return $this->_set_default_controller();
		}

		$this->set_class($segments[0]);

		if (isset($segments[1])){
			// 标准方法请求
			$this->set_method($segments[1]);
		}else {
			// 使用默认的索引方法
			$segments[1] = 'index';
		}

		// 更新 "routed" 段数组
		// 注意: 如果没有自定义路由, 此数组将和 $this->uri->segments 相同
		$this->uri->rsegments = $segments;
	}

	// --------------------------------------------------------------------

	/**
	 * 验证提供的 URI 段, 尝试确定到控制器的路径
	 * 验证成功的话, 会完成设置成员变量, 失败的话直接返回 404 错误页面
	 *
	 * @access	private
	 * @param	array
	 * @return	array
	 */
	function _validate_request($segments){
		if (count($segments) == 0){
			return $segments;
		}

		// 当目录, 控制器, 方法参数都有值的时候, 三个值是: 0目录 1控制器 2方法
		// 请求的控制器是否在应用程序的 controllers 根目录中存在? 此时, 假设 $segments[0] 为控制器名子目录
		// 注: $this->directory 结尾存在斜线, 所以可以与同名 $this->class 区分
		if (file_exists(APPPATH.'controllers/'.$segments[0].'.php')){
			return $segments;
		}

		// 如果根目录中不存在, 那么判断在子目录中是否存在, 将$segments[0]当做是目录
		if (is_dir(APPPATH.'controllers/'.$segments[0])){
			// 设置目录并从 segment 数组中删除它
			$this->set_directory($segments[0]);
			$segments = array_slice($segments, 1);

			if (count($segments) > 0){
				// 请求的控制器是否在子文件夹中存在?
				if ( ! file_exists(APPPATH.'controllers/'.$this->fetch_directory().$segments[0].'.php')){
					if ( ! empty($this->routes['404_override'])){
						$x = explode('/', $this->routes['404_override']);

						$this->set_directory('');
						$this->set_class($x[0]);
						$this->set_method(isset($x[1]) ? $x[1] : 'index');

						return $x;
					}else {
						show_404($this->fetch_directory().$segments[0]);
					}
				}
			}else {
				// 路由中是否指定了默认的控制器
				if (strpos($this->default_controller, '/') !== FALSE){
					$x = explode('/', $this->default_controller);

					$this->set_class($x[0]);
					$this->set_method($x[1]);
				}else {
					$this->set_class($this->default_controller);
					$this->set_method('index');
				}

				// 默认控制器是否在子文件夹中存在
				if ( ! file_exists(APPPATH.'controllers/'.$this->fetch_directory().$this->default_controller.'.php')){
					$this->directory = '';
					return array();
				}
			}

			return $segments;
		}


		// 根目录不存在子目录也不存在, 报错
		// 下面判断是否有自定义404页面, 有的话显示, 没有的话使用默认的
		if ( ! empty($this->routes['404_override'])){
			$x = explode('/', $this->routes['404_override']);

			$this->set_directory('');
			$this->set_class($x[0]);
			$this->set_method(isset($x[1]) ? $x[1] : 'index');

			return $x;
		}

		show_404($segments[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * 解析路由
	 *
	 * 这个函数匹配在 config/routes.php 文件中存在的任何路由, 以确定类/方法是否需要重新映射
	 *
	 * @access	private
	 * @return	void
	 */
	function _parse_routes(){
		// 转换段数组到 URI 字符串
		$uri = implode('/', $this->uri->segments);

		// 是否有字面值匹配
		if (isset($this->routes[$uri])){
			return $this->_set_request(explode('/', $this->routes[$uri]));
		}

		// 循环路由数组寻找通配符
		foreach ($this->routes as $key => $val){
			// 转换通配符到正则表达式
			$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

			// 正则表达式是否匹配
			if (preg_match('#^'.$key.'$#', $uri)){
				// 是否有反向引用
				if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE){
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}

				return $this->_set_request(explode('/', $val));
			}
		}

		// 如果没有匹配路由就设置站点的默认路由
		$this->_set_request($this->uri->segments);
	}

	// --------------------------------------------------------------------

	/**
	 * 设置类名
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_class($class){
		$this->class = str_replace(array('/', '.'), '', $class);
	}

	// --------------------------------------------------------------------

	/**
	 * 获取当前类
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_class(){
		return $this->class;
	}

	// --------------------------------------------------------------------

	/**
	 *  设置方法名
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_method($method){
		$this->method = $method;
	}

	// --------------------------------------------------------------------

	/**
	 *  获取当前方法
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_method(){
		if ($this->method == $this->fetch_class()){
			return 'index';
		}

		return $this->method;
	}

	// --------------------------------------------------------------------

	/**
	 *  设置目录名
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_directory($dir){
		$this->directory = str_replace(array('/', '.'), '', $dir).'/';
	}

	// --------------------------------------------------------------------

	/**
	 *  获取包含请求的控制器类的子目录(如果有的话)
	 *
	 * @access	public
	 * @return	string
	 */
	function fetch_directory(){
		return $this->directory;
	}

	// --------------------------------------------------------------------

	/**
	 *  在 index.php 中配置了 $routing 的话, 将覆写系统默认的路由
	 *
	 * @access	public
	 * @param	array
	 * @return	null
	 */
	function _set_overrides($routing){
		if ( ! is_array($routing)){
			return;
		}

		if (isset($routing['directory'])){
			$this->set_directory($routing['directory']);
		}

		if (isset($routing['controller']) AND $routing['controller'] != ''){
			$this->set_class($routing['controller']);
		}

		if (isset($routing['function'])){
			$routing['function'] = ($routing['function'] == '') ? 'index' : $routing['function'];
			$this->set_method($routing['function']);
		}
	}


}