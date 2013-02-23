<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 输入类
 *
 * 为了安全, 预处理输入数据
 * 提供 helper 的一些方法, 取得输入数据, 并预处理输入数据
 *
 */
class CI_Input {

	/**
	 * 当前用户的 IP 地址
	 *
	 * @var string
	 */
	var $ip_address				= FALSE;
	/**
	 * 当前用户所使用的用户代理 (Web浏览器)
	 *
	 * @var string
	 */
	var $user_agent				= FALSE;
	/**
	 * 是否允许访问 $_GET 数组
	 *
	 * @var bool
	 */
	var $_allow_get_array		= TRUE;
	/**
	 * 如果是 TRUE, 标准化换行
	 *
	 * @var bool
	 */
	var $_standardize_newlines	= TRUE;
	/**
	 * 确定是否总是在 GET, POST 或 COOKIE 数据中进行 XSS 过滤
	 * 在配置选项里面配置是否自动开启
	 *
	 * @var bool
	 */
	var $_enable_xss			= FALSE;
	/**
	 * 是否启用 CSRF cookie 令牌
	 * 在配置选项里面配置是否自动开启
	 *
	 * @var bool
	 */
	var $_enable_csrf			= FALSE;
	/**
	 * HTTP 请求头部列表
	 *
	 * @var array
	 */
	protected $headers			= array();

	/**
	 * 构造函数
	 *
	 * 设置是否全局允许 XSS 处理和是否允许使用 $_GET 数组
	 *
	 * @return	void
	 */
	public function __construct(){
		log_message('debug', "初始化输入类");

		$this->_allow_get_array	= (config_item('allow_get_array') === TRUE);
		$this->_enable_xss		= (config_item('global_xss_filtering') === TRUE);
		$this->_enable_csrf		= (config_item('csrf_protection') === TRUE);

		global $SEC;
		$this->security =& $SEC;

		// 是否需要 UTF-8 类?
		if (UTF8_ENABLED === TRUE){
			global $UNI;
			$this->uni =& $UNI;
		}

		// 清理全局数组
		$this->_sanitize_globals();
	}

	// --------------------------------------------------------------------

	/**
	 * 从数组中检索
	 *
	 * 检索全局数组中的值
	 *
	 * @access	private
	 * @param	array
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function _fetch_from_array(&$array, $index = '', $xss_clean = FALSE){
		if ( ! isset($array[$index])){
			return FALSE;
		}

		if ($xss_clean === TRUE){
			return $this->security->xss_clean($array[$index]);
		}

		return $array[$index];
	}

	// --------------------------------------------------------------------

	/**
	* 从 GET 数组中获取数据
	*
	* @access	public
	* @param	string
	* @param	bool
	* @return	string
	*/
	function get($index = NULL, $xss_clean = FALSE){
		// 检查是否提供了字段
		if ($index === NULL AND ! empty($_GET)){
			$get = array();

			// 遍历整个 _GET 数组
			foreach (array_keys($_GET) as $key){
				$get[$key] = $this->_fetch_from_array($_GET, $key, $xss_clean);
			}
			return $get;
		}

		return $this->_fetch_from_array($_GET, $index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* 从 POST 数组中获取数据
	*
	* @access	public
	* @param	string
	* @param	bool
	* @return	string
	*/
	function post($index = NULL, $xss_clean = FALSE){
		// 检查是否提供了字段
		if ($index === NULL AND ! empty($_POST)){
			$post = array();

			// 遍历整个 _POST 数组
			foreach (array_keys($_POST) as $key){
				$post[$key] = $this->_fetch_from_array($_POST, $key, $xss_clean);
			}
			return $post;
		}

		return $this->_fetch_from_array($_POST, $index, $xss_clean);
	}


	// --------------------------------------------------------------------

	/**
	* 从 GET 数组或 POST 数组中获取数据, POST 数组优先
	*
	* @access	public
	* @param	string	The index key
	* @param	bool	XSS cleaning
	* @return	string
	*/
	function get_post($index = '', $xss_clean = FALSE){
		if ( ! isset($_POST[$index]) ){
			return $this->get($index, $xss_clean);
		}else {
			return $this->post($index, $xss_clean);
		}
	}

	// --------------------------------------------------------------------

	/**
	* 从 COOKIE 数组中获取数据
	*
	* @access	public
	* @param	string
	* @param	bool
	* @return	string
	*/
	function cookie($index = '', $xss_clean = FALSE){
		return $this->_fetch_from_array($_COOKIE, $index, $xss_clean);
	}

	// ------------------------------------------------------------------------

	/**
	* 设置 cookie
	*
	* 接受 6 个参数, 也可以在第一个参数中传递一个包含所有值的关联数组
	* 只有 name 和 value 是必须的
	* 可以通过将 expire 设置成空来实现删除 Cookie 的操作
	* 如果将 expire 设置成零, 那么 Cookie 仅在浏览器关闭的时候失效
	* 如果需要设置全站范围内使用的 cookie, 那么需要把网站域名赋给 $domain 变量, 并且需要以英文的句号 "." 开头
	* path 通常是不需要设置的, 该方法设置 path 为网站的根目录
	* prefix(前缀)只有在为了避免和其它服务器上的相同命名的 cookies 冲突时才需要使用
	*
	* @access	public
	* @param	mixed
	* @param	string	cookie 值
	* @param	string	以秒为单位的过期时间
	* @param	string	cookie 域名
	* @param	string	cookie 路径
	* @param	string	cookie 前缀
	* @param	bool	是否通过安全的 HTTPS 连接来传输 cookie
	* @return	void
	*/
	function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = FALSE){
		if (is_array($name)){
			// always leave 'name' in last place, as the loop will break otherwise, due to $$item
			foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'name') as $item){
				if (isset($name[$item])){
					$$item = $name[$item];
				}
			}
		}

		if ($prefix == '' AND config_item('cookie_prefix') != ''){
			$prefix = config_item('cookie_prefix');
		}
		if ($domain == '' AND config_item('cookie_domain') != ''){
			$domain = config_item('cookie_domain');
		}
		if ($path == '/' AND config_item('cookie_path') != '/'){
			$path = config_item('cookie_path');
		}
		if ($secure == FALSE AND config_item('cookie_secure') != FALSE){
			$secure = config_item('cookie_secure');
		}

		if ( ! is_numeric($expire)){
			$expire = time() - 86500;
		}else {
			$expire = ($expire > 0) ? time() + $expire : 0;
		}

		setcookie($prefix.$name, $value, $expire, $path, $domain, $secure);
	}

	// --------------------------------------------------------------------

	/**
	* 从 SERVER 数组中获取数据
	*
	* @access	public
	* @param	string
	* @param	bool
	* @return	string
	*/
	function server($index = '', $xss_clean = FALSE){
		return $this->_fetch_from_array($_SERVER, $index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* 获取 IP 地址
	*
	* @return	string
	*/
	public function ip_address(){
		if ($this->ip_address !== FALSE){
			return $this->ip_address;
		}

		$proxy_ips = config_item('proxy_ips');
		if ( ! empty($proxy_ips)){
			$proxy_ips = explode(',', str_replace(' ', '', $proxy_ips));
			foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP') as $header){
				if (($spoof = $this->server($header)) !== FALSE){
					// Some proxies typically list the whole chain of IP
					// addresses through which the client has reached us.
					// e.g. client_ip, proxy_ip1, proxy_ip2, etc.
					if (strpos($spoof, ',') !== FALSE){
						$spoof = explode(',', $spoof, 2);
						$spoof = $spoof[0];
					}

					if ( ! $this->valid_ip($spoof)){
						$spoof = FALSE;
					}else {
						break;
					}
				}
			}

			$this->ip_address = ($spoof !== FALSE && in_array($_SERVER['REMOTE_ADDR'], $proxy_ips, TRUE))
				? $spoof : $_SERVER['REMOTE_ADDR'];
		}else {
			$this->ip_address = $_SERVER['REMOTE_ADDR'];
		}

		if ( ! $this->valid_ip($this->ip_address)){
			$this->ip_address = '0.0.0.0';
		}

		return $this->ip_address;
	}

	// --------------------------------------------------------------------

	/**
	* 验证 IP 地址
	*
	* @access	public
	* @param	string
	* @param	string	ipv4 或 ipv6
	* @return	bool
	*/
	public function valid_ip($ip, $which = ''){
		$which = strtolower($which);

		// First check if filter_var is available
		if (is_callable('filter_var'))
		{
			switch ($which) {
				case 'ipv4':
					$flag = FILTER_FLAG_IPV4;
					break;
				case 'ipv6':
					$flag = FILTER_FLAG_IPV6;
					break;
				default:
					$flag = '';
					break;
			}

			return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flag);
		}

		if ($which !== 'ipv6' && $which !== 'ipv4')
		{
			if (strpos($ip, ':') !== FALSE)
			{
				$which = 'ipv6';
			}
			elseif (strpos($ip, '.') !== FALSE)
			{
				$which = 'ipv4';
			}
			else
			{
				return FALSE;
			}
		}

		$func = '_valid_'.$which;
		return $this->$func($ip);
	}

	// --------------------------------------------------------------------

	/**
	* Validate IPv4 Address
	*
	* Updated version suggested by Geert De Deckere
	*
	* @access	protected
	* @param	string
	* @return	bool
	*/
	protected function _valid_ipv4($ip)
	{
		$ip_segments = explode('.', $ip);

		// Always 4 segments needed
		if (count($ip_segments) !== 4)
		{
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
		{
			return FALSE;
		}

		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* Validate IPv6 Address
	*
	* @access	protected
	* @param	string
	* @return	bool
	*/
	protected function _valid_ipv6($str)
	{
		// 8 groups, separated by :
		// 0-ffff per group
		// one set of consecutive 0 groups can be collapsed to ::

		$groups = 8;
		$collapsed = FALSE;

		$chunks = array_filter(
			preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE)
		);

		// Rule out easy nonsense
		if (current($chunks) == ':' OR end($chunks) == ':')
		{
			return FALSE;
		}

		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
		if (strpos(end($chunks), '.') !== FALSE)
		{
			$ipv4 = array_pop($chunks);

			if ( ! $this->_valid_ipv4($ipv4))
			{
				return FALSE;
			}

			$groups--;
		}

		while ($seg = array_pop($chunks))
		{
			if ($seg[0] == ':')
			{
				if (--$groups == 0)
				{
					return FALSE;	// too many groups
				}

				if (strlen($seg) > 2)
				{
					return FALSE;	// long separator
				}

				if ($seg == '::')
				{
					if ($collapsed)
					{
						return FALSE;	// multiple collapsed
					}

					$collapsed = TRUE;
				}
			}
			elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4)
			{
				return FALSE; // invalid segment
			}
		}

		return $collapsed OR $groups == 1;
	}

	// --------------------------------------------------------------------

	/**
	* 用户代理
	* 
	* 返回当前用户正在使用的浏览器的用户代理信息
	*
	* @access	public
	* @return	string
	*/
	function user_agent(){
		if ($this->user_agent !== FALSE){
			return $this->user_agent;
		}

		$this->user_agent = ( ! isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];

		return $this->user_agent;
	}

	// --------------------------------------------------------------------

	/**
	* 清理全局数组
	*
	* 此函数执行以下操作:
	*
	* 取消设置 $ _GET 数据 (如果未启用查询字符串)
	*
	* 取消所有的全局变量 (如果启用了 register_globals)
	*
	* 标准化换行符为 \n
	*
	* @access	private
	* @return	void
	*/
	function _sanitize_globals(){
		// 下面这些是需要进行保护的全局变量
		$protected = array('_SERVER', '_GET', '_POST', '_FILES', '_REQUEST',
							'_SESSION', '_ENV', 'GLOBALS', 'HTTP_RAW_POST_DATA',
							'system_folder', 'application_folder', 'BM', 'EXT',
							'CFG', 'URI', 'RTR', 'OUT', 'IN');

		// 安全起见, 删除全局变量
		// 这样的效果和 register_globals = off 是相同的
		// 经过下面处理后, 所有的非保护的全局变量将被删除掉
		foreach (array($_GET, $_POST, $_COOKIE) as $global){
			if ( ! is_array($global)){
				if ( ! in_array($global, $protected)){
					global $$global;
					$$global = NULL;
				}
			}else {
				foreach ($global as $key => $val){
					if ( ! in_array($key, $protected)){
						global $$key;
						$$key = NULL;
					}
				}
			}
		}

		// 是否允许 $_GET 数据? 如果不允许的话, 设置 $_GET 为空数组
		if ($this->_allow_get_array == FALSE){
			$_GET = array();
		}else {
			if (is_array($_GET) AND count($_GET) > 0){
				foreach ($_GET as $key => $val){
					$_GET[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
				}
			}
		}

		// 清理 $_POST 数据
		if (is_array($_POST) AND count($_POST) > 0){
			foreach ($_POST as $key => $val){
				$_POST[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
		}

		// 清理 $_COOKIE 数据
		if (is_array($_COOKIE) AND count($_COOKIE) > 0){
			// Also get rid of specially treated cookies that might be set by a server
			// or silly application, that are of no use to a CI application anyway
			// but that when present will trip our 'Disallowed Key Characters' alarm
			// http://www.ietf.org/rfc/rfc2109.txt
			// 注意, 下面的键名是单引号字符串的, 而不是 PHP 变量
			unset($_COOKIE['$Version']);
			unset($_COOKIE['$Path']);
			unset($_COOKIE['$Domain']);

			foreach ($_COOKIE as $key => $val){
				$_COOKIE[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
		}

		// 清理 PHP_SELF
		$_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);

		// 对 HTTP 请求进行 CSRF 保护检查
		if ($this->_enable_csrf == TRUE && ! $this->is_cli_request()){
			$this->security->csrf_verify();
		}

		log_message('debug', "全局 POST 和 COOKIE 数据已清理");
	}

	// --------------------------------------------------------------------

	/**
	* 清理输入数据
	*
	* 这个函数转义数据并标准化换行符为 \n
	*
	* @access	private
	* @param	string
	* @return	string
	*/
	function _clean_input_data($str){
		if (is_array($str)){
			$new_array = array();
			foreach ($str as $key => $val){
				$new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
			return $new_array;
		}

		/**
		 * 如果小于 PHP5.4 版本, 并且 get_magic_quotes_gpc() 开启了, 则去掉斜线
		 * 注意: 在 PHP5.4 及之后版本, get_magic_quotes_gpc() 将总是返回 0, 在后续版本中可能会移除该特性
		*/
		if ( ! is_php('5.4') && get_magic_quotes_gpc()){
			$str = stripslashes($str);
		}

		// 如果支持的话, 清理 UTF-8
		if (UTF8_ENABLED === TRUE){
			$str = $this->uni->clean_string($str);
		}

		// 移除控制字符串
		$str = remove_invisible_characters($str);

		// 是否过滤输入数据
		if ($this->_enable_xss === TRUE){
			$str = $this->security->xss_clean($str);
		}

		// 如果需要的话, 标准化换行符
		if ($this->_standardize_newlines == TRUE){
			if (strpos($str, "\r") !== FALSE){
				$str = str_replace(array("\r\n", "\r", "\r\n\n"), PHP_EOL, $str);
			}
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	* 清理键值
	*
	* 为了防止恶意用户试图利用键, 确认键仅仅用字母数字文本和其它一些项命名
	*
	* @access	private
	* @param	string
	* @return	string
	*/
	function _clean_input_keys($str){
		if ( ! preg_match("/^[a-z0-9:_\/-]+$/i", $str)){
			exit('键值中有不允许字符.');
		}

		// 如果支持的话, 清理 UTF-8
		if (UTF8_ENABLED === TRUE){
			$str = $this->uni->clean_string($str);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * 请求头部
	 *
	 * 在 Apache 上, 可以简单地调用 apache_request_headers()
	 * 在不支持 apache_request_headers() 的非 Apache 环境非常有用
	 *
	 * @param	bool XSS 清理
	 *
	 * @return array
	 */
	public function request_headers($xss_clean = FALSE){
		if (function_exists('apache_request_headers')){
			$headers = apache_request_headers();
		}else {
			$headers['Content-Type'] = (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE');

			foreach ($_SERVER as $key => $val){
				if (strncmp($key, 'HTTP_', 5) === 0){
					$headers[substr($key, 5)] = $this->_fetch_from_array($_SERVER, $key, $xss_clean);
				}
			}
		}

		// take SOME_HEADER and turn it into Some-Header
		foreach ($headers as $key => $val){
			$key = str_replace('_', ' ', strtolower($key));
			$key = str_replace(' ', '-', ucwords($key));

			$this->headers[$key] = $val;
		}

		return $this->headers;
	}

	// --------------------------------------------------------------------

	/**
	 * 获取请求头部
	 *
	 * Returns the value of a single member of the headers class member
	 *
	 * @param 	string		array key for $this->headers
	 * @param	boolean		XSS Clean or not
	 * @return 	mixed		FALSE on failure, string on success
	 */
	public function get_request_header($index, $xss_clean = FALSE){
		if (empty($this->headers)){
			$this->request_headers();
		}

		if ( ! isset($this->headers[$index])){
			return FALSE;
		}

		if ($xss_clean === TRUE){
			return $this->security->xss_clean($this->headers[$index]);
		}

		return $this->headers[$index];
	}

	// --------------------------------------------------------------------

	/**
	 * 是否是 ajax 请求
	 *
	 * Test to see if a request contains the HTTP_X_REQUESTED_WITH header
	 *
	 * @return 	boolean
	 */
	public function is_ajax_request(){
		return ($this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest');
	}

	// --------------------------------------------------------------------

	/**
	 * 是否是 cli 请求
	 *
	 * 测试是否是来自命令行的请求
	 *
	 * @return 	bool
	 */
	public function is_cli_request(){
		return (php_sapi_name() === 'cli' OR defined('STDIN'));
	}

}