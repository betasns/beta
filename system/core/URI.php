<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * URI 类
 *
 */
class CI_URI {

	/**
	 * List of cached uri segments
	 *
	 * @var array
	 * @access public
	 */
	var	$keyval			= array();
	/**
	 * 当前 uri 字符串
	 *
	 * @var string
	 * @access public
	 */
	var $uri_string;
	/**
	 * uri 段列表
	 *
	 * @var array
	 * @access public
	 */
	var $segments		= array();
	/**
	 * Re-indexed list of uri segments
	 * Starts at 1 instead of 0
	 *
	 * @var array
	 * @access public
	 */
	var $rsegments		= array();

	/**
	 * 构造函数
	 *
	 * @access	public
	 */
	function __construct(){
		$this->config =& load_class('Config', 'core');
		log_message('debug', "初始化 URI 类");
	}


	// --------------------------------------------------------------------

	/**
	 * 获取 URI 字符串
	 *
	 * @access	private
	 * @return	string
	 */
	function _fetch_uri_string(){
		if (strtoupper($this->config->item('uri_protocol')) == 'AUTO'){
			// 请求是否来自命令行
			// 命令行形式为: php index.php welcome index 或 php index.php welcome/index
			if (php_sapi_name() == 'cli' or defined('STDIN')){
				$this->_set_uri_string($this->_parse_cli_args());
				return;
			}

			// 首先尝试 REQUEST_URI , 它用于大多数情况
			if ($uri = $this->_detect_uri()){
				$this->_set_uri_string($uri);
				return;
			}

			// 如果上面的两种方式都不能获取到 URI, 那么会采用 $_SERVER['PATH_INFO'] 来获取
			// 注意: 一些服务器 使用 getenv(), 所以我们将测试两种方式
			$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
			if (trim($path, '/') != '' && $path != "/".SELF){
				$this->_set_uri_string($path);
				return;
			}

			// 如果上面三种方式都不能获取到, 那么就使用 $_SERVER['QUERY_STRING'] 或者 getenv['QUERY_STRING']
			$path =  (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
			if (trim($path, '/') != ''){
				$this->_set_uri_string($path);
				return;
			}

			// 作为一个最后的努力, 让我们尝试使用 $ _GET 数组
			if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != ''){
				$this->_set_uri_string(key($_GET));
				return;
			}

			// 已经用尽了所有的选择
			$this->uri_string = '';
			return;
		}

		// 在 config.php 中设定了 $config['uri_protocol'], 那么程序会自动执行相应的操作来获取 URI
		$uri = strtoupper($this->config->item('uri_protocol'));

		if ($uri == 'REQUEST_URI'){
			$this->_set_uri_string($this->_detect_uri());
			return;
		}elseif ($uri == 'CLI'){
			$this->_set_uri_string($this->_parse_cli_args());
			return;
		}

		$path = (isset($_SERVER[$uri])) ? $_SERVER[$uri] : @getenv($uri);
		$this->_set_uri_string($path);
	}

	// --------------------------------------------------------------------

	/**
	 * 设置 URI 字符串
	 *
	 * @access	public
	 * @param 	string
	 * @return	string
	 */
	function _set_uri_string($str){
		// 过滤控制字符
		$str = remove_invisible_characters($str, FALSE);

		// 如果 URI 只包含一个斜线, 重置为空
		$this->uri_string = ($str == '/') ? '' : $str;
	}

	// --------------------------------------------------------------------

	/**
	 * 检测 URI
	 *
	 * 此函数将自动检测 URI 并修正查询字符串(如果必要)
	 *
	 * @access	private
	 * @return	string
	 */
	private function _detect_uri(){
		if ( ! isset($_SERVER['REQUEST_URI']) OR ! isset($_SERVER['SCRIPT_NAME'])){
			return '';
		}

		$uri = $_SERVER['REQUEST_URI'];
		if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0){
			$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		}elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0){
			$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		}

		// 这部分确保即使在需要 URI 查询字符串的服务器 (Nginx) 能找到正确的 URI
		// 修正 QUERY_STRING 服务器变量和 $_GET 数组
		// 去掉可能存在的 '?/'
		if (strncmp($uri, '?/', 2) === 0){
			$uri = substr($uri, 2);
		}
		// 以 '?' 分割 $uri
		$parts = preg_split('#\?#i', $uri, 2);
		$uri = $parts[0];
		if (isset($parts[1])){
			$_SERVER['QUERY_STRING'] = $parts[1];
			// 把查询字符串解析到变量中
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		}else {
			$_SERVER['QUERY_STRING'] = '';
			$_GET = array();
		}

		if ($uri == '/' || empty($uri)){
			return '/';
		}

		$uri = parse_url($uri, PHP_URL_PATH);

		return str_replace(array('//', '../'), '/', trim($uri, '/'));
	}

	// --------------------------------------------------------------------

	/**
	 * 解析 cli 参数
	 *
	 * 获取每个命令行参数并假定它是一个 URI 段
	 *
	 * @access	private
	 * @return	string
	 */
	private function _parse_cli_args(){
		$args = array_slice($_SERVER['argv'], 1);

		return $args ? '/' . implode('/', $args) : '';
	}

	// --------------------------------------------------------------------

	/**
	 * 过滤字符串, 对特殊字符进行转义
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _filter_uri($str){
		if ($str != '' && $this->config->item('permitted_uri_chars') != '' && $this->config->item('enable_query_strings') == FALSE){
			// preg_quote() 在 PHP 5.3 中转义 -, 所以 str_replace() 处理 preg_quote() 是为了保持向后兼容
			if ( ! preg_match("|^[".str_replace(array('\\-', '\-'), '-', preg_quote($this->config->item('permitted_uri_chars'), '-'))."]+$|i", $str)){
				show_error('提交的 URI有不允许的字符.', 400);
			}
		}

		// 转换字符为实体
		$bad	= array('$',		'(',		')',		'%28',		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');

		return str_replace($bad, $good, $str);
	}

	// --------------------------------------------------------------------

	/**
	 * 删除 URL 后缀
	 *
	 * @access	private
	 * @return	void
	 */
	function _remove_url_suffix(){
		if  ($this->config->item('url_suffix') != ""){
			$this->uri_string = preg_replace("|".preg_quote($this->config->item('url_suffix'))."$|", "", $this->uri_string);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 分解 URI 段
	 * 各个部分将被存储到 $this->segments 数组
	 *
	 * @access	private
	 * @return	void
	 */
	function _explode_segments(){
		foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val){
			// 为安全起见过滤段
			$val = trim($this->_filter_uri($val));

			if ($val != ''){
				$this->segments[] = $val;
			}
		}
	}

	// --------------------------------------------------------------------
	/**
	 * 重建段数组索引
	 *
	 * 此函数重建 $this->segment 数组索引以便它从 1 开始而不是 0
	 * 这样做可以使像 $this->uri->segment(n) 这样的函数更容易使用因为在段数组和实际段之间有一个 1:1 对应的关系
	 *
	 * @access	private
	 * @return	void
	 */
	function _reindex_segments(){
		array_unshift($this->segments, NULL);
		array_unshift($this->rsegments, NULL);
		unset($this->segments[0]);
		unset($this->rsegments[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * 获取 URI 段
	 *
	 * 这个函数基于提供的数字返回 URI 段
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	function segment($n, $no_result = FALSE){
		return ( ! isset($this->segments[$n])) ? $no_result : $this->segments[$n];
	}

	// --------------------------------------------------------------------

	/**
	 * 获取 URI "routed" Segment
	 *
	 * 此函数基于提供的数字返回重新路由的 URI 段 (假设使用路由规则)
	 * 如果没有路由, 则该函数返回和 $this->segment() 相同的结果
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	function rsegment($n, $no_result = FALSE){
		return ( ! isset($this->rsegments[$n])) ? $no_result : $this->rsegments[$n];
	}

	// --------------------------------------------------------------------

	/**
	 * Generate a key value pair from the URI string
	 *
	 * This function generates and associative array of URI data starting
	 * at the supplied segment. For example, if this is your URI:
	 *
	 *	example.com/user/search/name/joe/location/UK/gender/male
	 *
	 * You can use this function to generate an array with this prototype:
	 *
	 * array (
	 *			name => joe
	 *			location => UK
	 *			gender => male
	 *		 )
	 *
	 * @access	public
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @return	array
	 */
	function uri_to_assoc($n = 3, $default = array()){
		return $this->_uri_to_assoc($n, $default, 'segment');
	}
	/**
	 * Identical to above only it uses the re-routed segment array
	 *
	 * @access 	public
	 * @param 	integer	the starting segment number
	 * @param 	array	an array of default values
	 * @return 	array
	 *
	 */
	function ruri_to_assoc($n = 3, $default = array())
	{
		return $this->_uri_to_assoc($n, $default, 'rsegment');
	}

	// --------------------------------------------------------------------

	/**
	 * Generate a key value pair from the URI string or Re-routed URI string
	 *
	 * @access	private
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @param	string	which array we should use
	 * @return	array
	 */
	function _uri_to_assoc($n = 3, $default = array(), $which = 'segment')
	{
		if ($which == 'segment')
		{
			$total_segments = 'total_segments';
			$segment_array = 'segment_array';
		}
		else
		{
			$total_segments = 'total_rsegments';
			$segment_array = 'rsegment_array';
		}

		if ( ! is_numeric($n))
		{
			return $default;
		}

		if (isset($this->keyval[$n]))
		{
			return $this->keyval[$n];
		}

		if ($this->$total_segments() < $n)
		{
			if (count($default) == 0)
			{
				return array();
			}

			$retval = array();
			foreach ($default as $val)
			{
				$retval[$val] = FALSE;
			}
			return $retval;
		}

		$segments = array_slice($this->$segment_array(), ($n - 1));

		$i = 0;
		$lastval = '';
		$retval  = array();
		foreach ($segments as $seg)
		{
			if ($i % 2)
			{
				$retval[$lastval] = $seg;
			}
			else
			{
				$retval[$seg] = FALSE;
				$lastval = $seg;
			}

			$i++;
		}

		if (count($default) > 0)
		{
			foreach ($default as $val)
			{
				if ( ! array_key_exists($val, $retval))
				{
					$retval[$val] = FALSE;
				}
			}
		}

		// Cache the array for reuse
		$this->keyval[$n] = $retval;
		return $retval;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate a URI string from an associative array
	 *
	 *
	 * @access	public
	 * @param	array	an associative array of key/values
	 * @return	array
	 */
	function assoc_to_uri($array)
	{
		$temp = array();
		foreach ((array)$array as $key => $val)
		{
			$temp[] = $key;
			$temp[] = $val;
		}

		return implode('/', $temp);
	}

	// --------------------------------------------------------------------

	/**
	 * 获取 URI 段并添加斜线
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	function slash_segment($n, $where = 'trailing'){
		return $this->_slash_segment($n, $where, 'segment');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch a URI Segment and add a trailing slash
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	function slash_rsegment($n, $where = 'trailing')
	{
		return $this->_slash_segment($n, $where, 'rsegment');
	}

	// --------------------------------------------------------------------

	/**
	 * 获取 URI 段并添加斜线 - 辅助函数
	 *
	 * @access	private
	 * @param	integer
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function _slash_segment($n, $where = 'trailing', $which = 'segment'){
		$leading	= '/';
		$trailing	= '/';

		if ($where == 'trailing'){
			$leading	= '';
		}elseif ($where == 'leading'){
			$trailing	= '';
		}

		return $leading.$this->$which($n).$trailing;
	}

	// --------------------------------------------------------------------

	/**
	 * URI 分段数组
	 *
	 * @access	public
	 * @return	array
	 */
	function segment_array(){
		return $this->segments;
	}

	// --------------------------------------------------------------------

	/**
	 * 重新路由的 URI 分段数组
	 *
	 * @access	public
	 * @return	array
	 */
	function rsegment_array(){
		return $this->rsegments;
	}

	// --------------------------------------------------------------------

	/**
	 * 获取总的 URI 的段数
	 *
	 * @access	public
	 * @return	integer
	 */
	function total_segments(){
		return count($this->segments);
	}

	// --------------------------------------------------------------------

	/**
	 * 获取总的重新路由的 URI 的段数
	 *
	 * @access	public
	 * @return	integer
	 */
	function total_rsegments(){
		return count($this->rsegments);
	}

	// --------------------------------------------------------------------

	/**
	 * 获取完整的 URI 字符串
	 *
	 * @access	public
	 * @return	string
	 */
	function uri_string(){
		return $this->uri_string;
	}


	// --------------------------------------------------------------------

	/**
	 * 获取完整的重新路由的 URI 字符串
	 *
	 * @access	public
	 * @return	string
	 */
	function ruri_string(){
		return '/'.implode('/', $this->rsegment_array());
	}

}