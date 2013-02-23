<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 配置类
 *
 * 这个类包含了管理配置文件的方法
 *
 */
class CI_Config {

	/**
	 * 所有载入的配置值列表
	 *
	 * @var array
	 */
	var $config = array();
	/**
	 * 已经载入的配置文件列表
	 *
	 * @var array
	 */
	var $is_loaded = array();
	/**
	 * 载入配置文件时要搜索的路径列表
	 *
	 * @var array
	 */
	var $_config_paths = array(APPPATH);

	/**
	 * 构造函数
	 *
	 * 从主 config.php 文件读取设置 $config 数据
	 *
	 */
	function __construct(){
		$this->config =& get_config();
		log_message('debug', "初始化配置类");

		// 如果没有配置则自动设置 base_url
		if ($this->config['base_url'] == ''){
			if (isset($_SERVER['HTTP_HOST'])){
				$base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
				$base_url .= '://'. $_SERVER['HTTP_HOST'];
				$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
			}else {
				$base_url = 'http://localhost/';
			}
			
			$this->set_item('base_url', $base_url);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 加载配置文件
	 *
	 * @access	public
	 * @param	string		配置文件名
	 * @param   boolean		是否配置值应该被加载到它们自己的部分
	 * @param   boolean		true 如果发生错误 return false, false 显示错误信息
	 * @return	boolean		文件是否加载成功
	 */
	function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE){
		// 处理文件名
		$file = ($file == '') ? 'config' : str_replace('.php', '', $file);
		$found = FALSE;		// 文件是否存在
		$loaded = FALSE;	// 文件是否已经加载

		$check_locations = defined('ENVIRONMENT') ? array(ENVIRONMENT.'/'.$file, $file) : array($file);

		// 遍历 $this->_config_paths 路径数组
		foreach ($this->_config_paths as $path){
			// 遍历 $check_locations 路径数组
			foreach ($check_locations as $location){
				$file_path = $path.'config/'.$location.'.php';

				// 如果文件已加载, 继续第一层循环; 如果文件存在, 改变 $found 特征量并跳出循环
				if (in_array($file_path, $this->is_loaded, TRUE)){
					$loaded = TRUE;
					continue 2;
				}

				if (file_exists($file_path)){
					$found = TRUE;
					break;
				}
			}

			if ($found === FALSE){
				continue;
			}

			// 加载文件并判断文件格式是否正确
			include($file_path);

			if ( ! isset($config) OR ! is_array($config)){
				if ($fail_gracefully === TRUE){
					return FALSE;
				}
				show_error($file_path.' 文件没有包含有效的配置数组.');
			}

			// 如果在不同的配置文件中存在同名的索引, 那么会发生冲突
			// 为了避免这个问题, 可以把第二个参数设置为 TRUE
			// 这可以使每个配置文件的内容存储在一个单独的数组中, 数组的索引就是配置文件的文件名
			if ($use_sections === TRUE){
				if (isset($this->config[$file])){
					$this->config[$file] = array_merge($this->config[$file], $config);
				}else {
					$this->config[$file] = $config;
				}
			}else {
				$this->config = array_merge($this->config, $config);
			}

			$this->is_loaded[] = $file_path;
			unset($config);

			$loaded = TRUE;
			log_message('debug', '加载配置文件: '.$file_path);
			break;
		}

		if ($loaded === FALSE){
			if ($fail_gracefully === TRUE){
				return FALSE;
			}
			show_error('配置文件 '.$file.'.php 不存在.');
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 获取配置文件项
	 *
	 * @access	public
	 * @param	string	配置项名字
	 * @param	string	索引名字
	 * @param	bool
	 * @return	string
	 */
	function item($item, $index = ''){
		if ($index == ''){
			if ( ! isset($this->config[$item])){
				return FALSE;
			}

			$pref = $this->config[$item];
		}else {
			if ( ! isset($this->config[$index])){
				return FALSE;
			}

			if ( ! isset($this->config[$index][$item])){
				return FALSE;
			}

			$pref = $this->config[$index][$item];
		}

		return $pref;
	}

	// --------------------------------------------------------------------

	/**
	 * 获取配置文件项 - 确保项后面有斜线(如果项非空)
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function slash_item($item){
		// 没有要获取的项
		if ( ! isset($this->config[$item])){
			return FALSE;
		}
		// 如果获取的项的值是空的, 返回空字符
		if( trim($this->config[$item]) == ''){
			return '';
		}

		// //返回项, 去掉右侧的 '/', 重新添加, 以确保右边总是只有一个 '/'
		return rtrim($this->config[$item], '/').'/';
	}

	// --------------------------------------------------------------------

	/**
	 * 网站 URL
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function site_url($uri = ''){
		if ($uri == ''){
			return $this->slash_item('base_url').$this->item('index_page');
		}

		if ($this->item('enable_query_strings') == FALSE){
			$suffix = ($this->item('url_suffix') == FALSE) ? '' : $this->item('url_suffix');
			return $this->slash_item('base_url').$this->slash_item('index_page').$this->_uri_string($uri).$suffix;
		}else {
			return $this->slash_item('base_url').$this->item('index_page').'?'.$this->_uri_string($uri);
		}
	}

	// -------------------------------------------------------------

	/**
	 * 基础 URL
	 *
	 * @access public
	 * @param string $uri
	 * @return string
	 */
	function base_url($uri = ''){
		return $this->slash_item('base_url').ltrim($this->_uri_string($uri), '/');
	}

	// -------------------------------------------------------------

	/**
	 * 建立 URI 字符串用于 Config::site_url() 和 Config::base_url()
	 *
	 * @access protected
	 * @param  $uri
	 * @return string
	 */
	protected function _uri_string($uri){
		if ($this->item('enable_query_strings') == FALSE){
			if (is_array($uri)){
				$uri = implode('/', $uri);
			}
			$uri = trim($uri, '/');
		}else {
			if (is_array($uri)){
				$i = 0;
				$str = '';
				foreach ($uri as $key => $val){
					$prefix = ($i == 0) ? '' : '&';
					$str .= $prefix.$key.'='.$val;
					$i++;
				}
				$uri = $str;
			}
		}
	    return $uri;
	}

	// --------------------------------------------------------------------

	/**
	 * 系统 URL
	 *
	 * @access	public
	 * @return	string
	 */
	function system_url(){
		$x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", BASEPATH));
		return $this->slash_item('base_url').end($x).'/';
	}

	// --------------------------------------------------------------------

	/**
	 * 设置配置文件项
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	function set_item($item, $value){
		$this->config[$item] = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * 指定配置
	 *
	 * 这个函数在配置类被实例化之后被前端控制器调用 (CodeIgniter.php)
	 * 它允许在 index.php 中赋值和覆写配置项
	 *
	 * @access	private
	 * @param	array
	 * @return	void
	 */
	function _assign_to_config($items = array()){
		if (is_array($items)){
			foreach ($items as $key => $val){
				$this->set_item($key, $val);
			}
		}
	}
}