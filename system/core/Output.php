<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 输出类
 *
 * 发送最终的 Web 页面到所请求的浏览器
 *
 */
class CI_Output {

	/**
	 * 当前输出字符串
	 *
	 * @var string
	 * @access 	protected
	 */
	protected $final_output;
	/**
	 * Cache expiration time
	 *
	 * @var int
	 * @access 	protected
	 */
	protected $cache_expiration	= 0;
	/**
	 * List of server headers
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $headers			= array();
	/**
	 * mime 类型列表
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $mime_types		= array();
	/**
	 * Determines wether profiler is enabled
	 *
	 * @var book
	 * @access 	protected
	 */
	protected $enable_profiler	= FALSE;
	/**
	 * 确定是否启用输出压缩
	 *
	 * @var bool
	 * @access 	protected
	 */
	protected $_zlib_oc			= FALSE;
	/**
	 * List of profiler sections
	 *
	 * @var array
	 * @access 	protected
	 */
	protected $_profiler_sections = array();
	/**
	 * Whether or not to parse variables like {elapsed_time} and {memory_usage}
	 *
	 * @var bool
	 * @access 	protected
	 */
	protected $parse_exec_vars	= TRUE;

	/**
	 * 构造函数
	 *
	 */
	function __construct(){
		// 通过 php.ini 的配置获取是否启用输出压缩
		$this->_zlib_oc = @ini_get('zlib.output_compression');

		// 获取 mime 类型
		if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php')){
		    include APPPATH.'config/'.ENVIRONMENT.'/mimes.php';
		}else {
			include APPPATH.'config/mimes.php';
		}


		$this->mime_types = $mimes;

		log_message('debug', "初始化输出类");
	}

	// --------------------------------------------------------------------

	/**
	 * 获取输出
	 *
	 * 返回当前输出字符串
	 *
	 * @access	public
	 * @return	string
	 */
	function get_output(){
		return $this->final_output;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置输出
	 *
	 * 设置输出字符串, 返回当前对象
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_output($output){
		$this->final_output = $output;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 追加输出
	 *
	 * 追加数据到输出, 返回当前对象
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function append_output($output){
		if ($this->final_output == ''){
			$this->final_output = $output;
		}else {
			$this->final_output .= $output;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置头部
	 *
	 * 设置将会作为最终显示输出的服务器头部
	 *
	 * 注意:  如果一个文件已经缓存, 头部将不会被发送
	 * 同时, 需要指出怎样去允许头部数据保存到缓存数据中
	 *
	 * @access	public
	 * @param	string
	 * @param 	bool
	 * @return	void
	 */
	function set_header($header, $replace = TRUE){
		// 如果启用了 zlib.output_compression, 将会压缩输出
		// 但是这样不会修改 content-length 头部去弥补减少的数据, 这样就会造成浏览器一直挂起等待更多的数据, 此时通过跳过 content-length 字段解决这个问题

		if ($this->_zlib_oc && strncasecmp($header, 'content-length', 14) == 0){
			return;
		}

		$this->headers[] = array($header, $replace);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置内容类型头部
	 * 
	 * 设置页面的 mime 类型以便于输出 JSON, JPEG, XML 等类型的数据
	 *
	 * @access	public
	 * @param	string	输出文件的扩展名
	 * @return	void
	 */
	function set_content_type($mime_type){
		if (strpos($mime_type, '/') === FALSE){
			$extension = ltrim($mime_type, '.');

			// 是否支持扩展
			if (isset($this->mime_types[$extension])){
				$mime_type =& $this->mime_types[$extension];

				if (is_array($mime_type)){
					$mime_type = current($mime_type);
				}
			}
		}

		$header = 'Content-Type: '.$mime_type;

		$this->headers[] = array($header, TRUE);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置 HTTP 状态头
	 * 在1.7.2版本之后移动到了 Common 函数库中
	 *
	 * @access	public
	 * @param	int		状态头
	 * @param	string
	 * @return	void
	 */
	function set_status_header($code = 200, $text = ''){
		set_status_header($code, $text);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 启用/禁用分析器
	 *
	 * @access	public
	 * @param	bool
	 * @return	void
	 */
	function enable_profiler($val = TRUE){
		$this->enable_profiler = (is_bool($val)) ? $val : TRUE;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Profiler Sections设置分析部分
	 *
	 * Allows override of default / config settings for Profiler section display允许覆写默认配置
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function set_profiler_sections($sections){
		foreach ($sections as $section => $enable){
			$this->_profiler_sections[$section] = ($enable !== FALSE) ? TRUE : FALSE;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置缓存时间
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	function cache($time){
		$this->cache_expiration = ( ! is_numeric($time)) ? 0 : $time;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * 显示输出
	 *
	 * 所有的 "view" 数据在控制器类中被自动的放入变量: $this->final_output 中
	 * 这个函数发送最终的输出数据到浏览器(包含服务器头部和分析器数据)
	 * 它也停止了基准测试时间计时器, 所以页面渲染速度和内存消耗也可以显示
	 *
	 * @access	public
	 * @param 	string
	 * @return	mixed
	 */
	function _display($output = '')
	{
		// 注意:  使用全局变量是因为不能使用 $CI =& get_instance() 来获取控制器实例对象
		// 因为缓存机制的使用, 有时控制器实例对象还没有被实例化, 所以这里就没有 CI 超级对象了
		// 具体是在 CodeIgniter.php 中, 载入输出类后, 会判断是否是存在缓存的文件,
		// 如果存在的话, 会直接调用 _display_cache() 方法, 在 _display_cache() 方法中又调用了该方法, 此时, 控制器类并没有被实例化
        // 但是基准测试类和配置类已经实例化了, 这里就只能通过全局对象获取了
		global $BM, $CFG;

		// 如果存在的话, 获取 CI 超级对象
		if (class_exists('CI_Controller')){
			$CI =& get_instance();
		}

		// --------------------------------------------------------------------

		// 设置输出数据, 这只有在没有提供 "output" 参数的时候调用最终渲染的页面数据
		if ($output == ''){
			$output =& $this->final_output;
		}

		// --------------------------------------------------------------------

		// 是否需要写入一个缓存文件?
		// 只有在控制器类没有自己实现 _output() 方法, 同时我们已经获取到了CI超级对象(从而面对的不是一个缓存文件)的情况下才缓存
		if ($this->cache_expiration > 0 && isset($CI) && ! method_exists($CI, '_output')){
			$this->_write_cache($output);
		}

		// --------------------------------------------------------------------

		// 解析出运行时间和内存消耗
		// 然后把 {} 之间的伪变量替换成获取的值

		$elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');

		if ($this->parse_exec_vars === TRUE){
			$memory	 = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';

			$output = str_replace('{elapsed_time}', $elapsed, $output);
			$output = str_replace('{memory_usage}', $memory, $output);
		}

		// --------------------------------------------------------------------

		// 是否请求压缩
		// 在 php.ini 中关闭, 同时配置中要求使用的时候才进行压缩
        // 在 php.ini 中如果是开启压缩的, 那么在这里就没必要再压缩了
		if ($CFG->item('compress_output') === TRUE && $this->_zlib_oc == FALSE){
			if (extension_loaded('zlib')){
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE){
					ob_start('ob_gzhandler');
				}
			}
		}

		// --------------------------------------------------------------------

		// 是否有服务器头部需要发送
		if (count($this->headers) > 0){
			foreach ($this->headers as $header){
				@header($header[0], $header[1]);
			}
		}

		// --------------------------------------------------------------------

		// $CI 超级对象是否存在
		// 如果没有的话我们知道我们正在处理的是一个缓存文件, 因此我们将简单的输出它并退出
		if ( ! isset($CI)){
			echo $output;
			log_message('debug', "最终输出已经发送到浏览器");
			log_message('debug', "总执行时间: ".$elapsed);
			return TRUE;
		}

		// --------------------------------------------------------------------

		// 是否启用分析器
		// 如果是, l加载 Profile 类并运行
		if ($this->enable_profiler == TRUE){
			$CI->load->library('profiler');

			if ( ! empty($this->_profiler_sections)){
				$CI->profiler->set_sections($this->_profiler_sections);
			}

			// 如果输出数据中包含了 HTML 结束标签 </body> 和 </html> 的话, 移除他们并在插入 profile 数据之后再添加上
			// 注意: 在 </body> 和 </html> 之间一定不要放任何的内容, 特别是 js 以及统计代码等, 如果放到这里, 在开启 profiler 的情况下, 这之间的数据会被清除
			if (preg_match("|</body>.*?</html>|is", $output)){
				$output  = preg_replace("|</body>.*?</html>|is", '', $output);
				$output .= $CI->profiler->run();
				$output .= '</body></html>';
			}else {
				$output .= $CI->profiler->run();
			}
		}

		// --------------------------------------------------------------------

		// 控制器是否包含方法 _output()
		// 如果存在的话, 调用控制器的 _output() 方法输出, 否则直接输出
		if (method_exists($CI, '_output')){
			$CI->_output($output);
		}else {
			echo $output;  // 发送至浏览器
		}

		log_message('debug', "最终输出已经发送到浏览器");
		log_message('debug', "总执行时间: ".$elapsed);
	}

	// --------------------------------------------------------------------

	/**
	 * 写缓存文件
	 *
	 * @access	public
	 * @param 	string
	 * @return	void
	 */
	function _write_cache($output){
		$CI =& get_instance();
		$path = $CI->config->item('cache_path');

		$cache_path = ($path == '') ? APPPATH.'cache/' : $path;

		if ( ! is_dir($cache_path) OR ! is_really_writable($cache_path)){
			log_message('error', "无法写入缓存文件: ".$cache_path);
			return;
		}

		$uri =	$CI->config->item('base_url').
				$CI->config->item('index_page').
				$CI->uri->uri_string();

		$cache_path .= md5($uri);

		if ( ! $fp = @fopen($cache_path, FOPEN_WRITE_CREATE_DESTRUCTIVE)){
			log_message('error', "无法写入缓存文件: ".$cache_path);
			return;
		}

		// 缓存文件的有效时间
		$expire = time() + ($this->cache_expiration * 60);

		// 对文件写入之前, 首先取得对文件的写锁, 这样, 可以确保不是两个操作同时写入文件
		// 文件的有效时间在文件内容的开头写入, 格式是 "过期时间TS--->" + 缓存内容
		if (flock($fp, LOCK_EX)){
			fwrite($fp, $expire.'TS--->'.$output);
			flock($fp, LOCK_UN);
		}else {
			log_message('error', "不能安全锁定文件: ".$cache_path);
			return;
		}
		fclose($fp);
		@chmod($cache_path, FILE_WRITE_MODE);

		log_message('debug', "已写入缓存文件: ".$cache_path);
	}

	// --------------------------------------------------------------------

	/**
	 * 更新/提供缓存文件
	 *
	 * @access	public
	 * @param 	object	配置类
	 * @param 	object	uri 类
	 * @return	void
	 */
	function _display_cache(&$CFG, &$URI){
		$cache_path = ($CFG->item('cache_path') == '') ? APPPATH.'cache/' : $CFG->item('cache_path');

		// 建立文件路径, 文件名是 MD5 哈希后的完整 URI
		$uri =	$CFG->item('base_url').
				$CFG->item('index_page').
				$URI->uri_string;

		$filepath = $cache_path.md5($uri);

		if ( ! @file_exists($filepath)){
			return FALSE;
		}

		if ( ! $fp = @fopen($filepath, FOPEN_READ)){
			return FALSE;
		}

		// 取得共享锁(读锁)
		flock($fp, LOCK_SH);

		$cache = '';
		if (filesize($filepath) > 0)
		{
			$cache = fread($fp, filesize($filepath));
		}

		// 解除锁定
		flock($fp, LOCK_UN);
		fclose($fp);

		// 去除嵌入的时间戳
		if ( ! preg_match("/(\d+TS--->)/", $cache, $match)){
			return FALSE;
		}

		// 文件是否过期, 如果过期了, 就删除文件
		if (time() >= trim(str_replace('TS--->', '', $match['1']))){
			if (is_really_writable($cache_path)){
				@unlink($filepath);
				log_message('debug', "缓存文件已过期, 已经删除");
				return FALSE;
			}
		}

		// 显示缓存
		$this->_display(str_replace($match['0'], '', $cache));
		log_message('debug', "当前缓存文件内容已经发送到客户浏览器");
		return TRUE;
	}

}