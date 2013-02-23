<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件上传类
 *
 */
class CI_Upload {

	public $max_size				= 0;
	public $max_width				= 0;
	public $max_height				= 0;
	public $max_filename			= 0;
	public $allowed_types			= "";
	public $file_temp				= "";
	public $file_name				= "";
	public $orig_name				= "";
	public $file_type				= "";
	public $file_size				= "";
	public $file_ext				= "";
	public $upload_path			= "";
	public $overwrite				= FALSE;
	public $encrypt_name			= FALSE;
	public $is_image				= FALSE;
	public $image_width			= '';
	public $image_height			= '';
	public $image_type				= '';
	public $image_size_str			= '';
	public $error_msg				= array();
	public $mimes					= array();
	public $remove_spaces			= TRUE;
	public $xss_clean				= FALSE;
	public $temp_prefix			= "temp_file_";
	public $client_name			= '';

	protected $_file_name_override	= '';

	/**
	 * 构造函数
	 *
	 * @access	public
	 */
	public function __construct($props = array()){
		if (count($props) > 0){
			$this->initialize($props);
		}

		log_message('debug', "初始化文件上传类");
	}

	// --------------------------------------------------------------------

	/**
	 * 初始化文件上传类
	 *
	 * @param	array
	 * @return	void
	 */
	public function initialize($config = array()){
		$defaults = array(
							'max_size'			=> 0,
							'max_width'			=> 0,
							'max_height'		=> 0,
							'max_filename'		=> 0,
							'allowed_types'		=> "",
							'file_temp'			=> "",
							'file_name'			=> "",
							'orig_name'			=> "",
							'file_type'			=> "",
							'file_size'			=> "",
							'file_ext'			=> "",
							'upload_path'		=> "",
							'overwrite'			=> FALSE,
							'encrypt_name'		=> FALSE,
							'is_image'			=> FALSE,
							'image_width'		=> '',
							'image_height'		=> '',
							'image_type'		=> '',
							'image_size_str'	=> '',
							'error_msg'			=> array(),
							'mimes'				=> array(),
							'remove_spaces'		=> TRUE,
							'xss_clean'			=> FALSE,
							'temp_prefix'		=> "temp_file_",
							'client_name'		=> ''
						);

		foreach ($defaults as $key => $val){
			if (isset($config[$key])){
				$method = 'set_'.$key;
				if (method_exists($this, $method)){
					$this->$method($config[$key]);
				}else {
					$this->$key = $config[$key];
				}
			}else {
				$this->$key = $val;
			}
		}

		// if a file_name was provided in the config, use it instead of the user input
		// supplied file name for all uploads until initialized again
		$this->_file_name_override = $this->file_name;
	}

	// --------------------------------------------------------------------

	/**
	 * 执行文件上传
	 *
	 * @return	bool
	 */
	public function do_upload($field = 'userfile'){
		// $_FILES[$field] 是否设置
		if ( ! isset($_FILES[$field])){
			$this->set_error('upload_no_file_selected');
			return FALSE;
		}

		// 上传路径是否正确
		if ( ! $this->validate_upload_path()){
			// 错误已经被 validate_upload_path() 设置, 所以才返回 FALSE
			return FALSE;
		}

		// 判断指定的文件是否是通过 HTTP POST 上传的
		if ( ! is_uploaded_file($_FILES[$field]['tmp_name'])){
			$error = ( ! isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

			switch($error){
				case 1:	// 上传的文件超过PHP配置文件允许的最大尺寸
					$this->set_error('upload_file_exceeds_limit');
					break;
				case 2: // 上传的文件超过提交表单允许的最大尺寸
					$this->set_error('upload_file_exceeds_form_limit');
					break;
				case 3: // 该文件只有部分被上传
					$this->set_error('upload_file_partial');
					break;
				case 4: // 没有选择要上传的文件
					$this->set_error('upload_no_file_selected');
					break;
				case 6: // 临时文件夹丢失
					$this->set_error('upload_no_temp_directory');
					break;
				case 7: // 该文件不能被写入到磁盘
					$this->set_error('upload_unable_to_write_file');
					break;
				case 8: // 文件上传被扩展名停止
					$this->set_error('upload_stopped_by_extension');
					break;
				default :   $this->set_error('upload_no_file_selected');
					break;
			}

			return FALSE;
		}


		// 设置上传数据为类变量
		$this->file_temp = $_FILES[$field]['tmp_name'];
		$this->file_size = $_FILES[$field]['size'];
		$this->_file_mime_type($_FILES[$field]);
		$this->file_type = preg_replace("/^(.+?);.*$/", "\\1", $this->file_type);
		$this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
		$this->file_name = $this->_prep_filename($_FILES[$field]['name']);
		$this->file_ext	 = $this->get_extension($this->file_name);
		$this->client_name = $this->file_name;

		// 文件类型是否允许上传
		if ( ! $this->is_allowed_filetype()){
			$this->set_error('upload_invalid_filetype');
			return FALSE;
		}

		// if we're overriding, let's now make sure the new name and type is allowed
		if ($this->_file_name_override != ''){
			$this->file_name = $this->_prep_filename($this->_file_name_override);

			// If no extension was provided in the file_name config item, use the uploaded one
			if (strpos($this->_file_name_override, '.') === FALSE){
				$this->file_name .= $this->file_ext;
			}

			// An extension was provided, lets have it!
			else {
				$this->file_ext	 = $this->get_extension($this->_file_name_override);
			}

			if ( ! $this->is_allowed_filetype(TRUE)){
				$this->set_error('upload_invalid_filetype');
				return FALSE;
			}
		}

		// 转换文件的大小, 以千字节为单位
		if ($this->file_size > 0){
			$this->file_size = round($this->file_size/1024, 2);
		}

		// 大小是否在所允许的范围内
		if ( ! $this->is_allowed_filesize()){
			$this->set_error('upload_invalid_filesize');
			return FALSE;
		}

		// 图像尺寸是否在所允许的范围内
		// Note: This can fail if the server has an open_basdir restriction.
		if ( ! $this->is_allowed_dimensions()){
			$this->set_error('upload_invalid_dimensions');
			return FALSE;
		}

		// Sanitize the file name for security
		$this->file_name = $this->clean_file_name($this->file_name);

		// 如果文件名太长则截断
		if ($this->max_filename > 0){
			$this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
		}

		// 删除名称空格中的
		if ($this->remove_spaces == TRUE){
			$this->file_name = preg_replace("/\s+/", "_", $this->file_name);
		}

		/*
		 * 验证文件名
		 * 如果有同名文件存在这个函数在文件名后追加一个数字
		 * If it returns false there was a problem.
		 */
		$this->orig_name = $this->file_name;

		if ($this->overwrite == FALSE){
			$this->file_name = $this->set_filename($this->upload_path, $this->file_name);

			if ($this->file_name === FALSE){
				return FALSE;
			}
		}

		/*
		 * Run the file through the XSS hacking filter
		 * This helps prevent malicious code from being
		 * embedded within a file.  Scripts can easily
		 * be disguised as images or other file types.
		 */
		if ($this->xss_clean)
		{
			if ($this->do_xss_clean() === FALSE)
			{
				$this->set_error('upload_unable_to_write_file');
				return FALSE;
			}
		}

		/*
		 * 将文件移动到最终目的地
		 * 为了处理不同的服务器配置, 首先尝试 copy() first, 如果失败, 就使用 use move_uploaded_file()
		 * 在大多数环境中两个之一可以可靠地工作
		 */
		if ( ! @copy($this->file_temp, $this->upload_path.$this->file_name)){
			if ( ! @move_uploaded_file($this->file_temp, $this->upload_path.$this->file_name)){
				$this->set_error('upload_destination_error');
				return FALSE;
			}
		}

		/*
		 * 设置最终的图像尺寸
		 * 设置图像的宽度/高度 (假设文件是一个图像)
		 * We use this information in the "data" function.
		 */
		$this->set_image_properties($this->upload_path.$this->file_name);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 最终的数据数组
	 *
	 * 返回一个包含所有上传相关信息的关联数组, 允许开发人员方便地访问
	 *
	 * @return	array
	 */
	public function data(){
		return array (
						'file_name'			=> $this->file_name,
						'file_type'			=> $this->file_type,
						'file_path'			=> $this->upload_path,
						'full_path'			=> $this->upload_path.$this->file_name,
						'raw_name'			=> str_replace($this->file_ext, '', $this->file_name),
						'orig_name'			=> $this->orig_name,
						'client_name'		=> $this->client_name,
						'file_ext'			=> $this->file_ext,
						'file_size'			=> $this->file_size,
						'is_image'			=> $this->is_image(),
						'image_width'		=> $this->image_width,
						'image_height'		=> $this->image_height,
						'image_type'		=> $this->image_type,
						'image_size_str'	=> $this->image_size_str,
					);
	}

	// --------------------------------------------------------------------

	/**
	 * 设置上传路径
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_upload_path($path){
		// 确保有结尾斜线
		$this->upload_path = rtrim($path, '/').'/';
	}

	// --------------------------------------------------------------------

	/**
	 * 设置文件名
	 *
	 * 这个函数接受一个文件名/路径作为输入, 然后查找存在的文件是否具有相同的名称
	 * 如果找到, 它会在结束的文件名中添加一个数字, 以避免覆盖预先存在的文件
	 *
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function set_filename($path, $filename){
		if ($this->encrypt_name == TRUE){
			mt_srand();
			$filename = md5(uniqid(mt_rand())).$this->file_ext;
		}

		if ( ! file_exists($path.$filename)){
			return $filename;
		}

		$filename = str_replace($this->file_ext, '', $filename);

		$new_filename = '';
		for ($i = 1; $i < 100; $i++){
			if ( ! file_exists($path.$filename.$i.$this->file_ext)){
				$new_filename = $filename.$i.$this->file_ext;
				break;
			}
		}

		if ($new_filename == ''){
			$this->set_error('upload_bad_filename');
			return FALSE;
		}else {
			return $new_filename;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 设置最大文件大小
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_size($n){
		$this->max_size = ((int) $n < 0) ? 0: (int) $n;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置最大文件名长度
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_filename($n){
		$this->max_filename = ((int) $n < 0) ? 0: (int) $n;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置最大图片宽
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_width($n){
		$this->max_width = ((int) $n < 0) ? 0: (int) $n;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置最大图片高
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_height($n){
		$this->max_height = ((int) $n < 0) ? 0: (int) $n;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置允许的文件类型
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_allowed_types($types){
		if ( ! is_array($types) && $types == '*'){
			$this->allowed_types = '*';
			return;
		}
		$this->allowed_types = explode('|', $types);
	}

	// --------------------------------------------------------------------

	/**
	 * 设置图像属性
	 *
	 * Uses GD to determine the width/height/type of image
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_image_properties($path = '')
	{
		if ( ! $this->is_image())
		{
			return;
		}

		if (function_exists('getimagesize'))
		{
			if (FALSE !== ($D = @getimagesize($path)))
			{
				$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

				$this->image_width		= $D['0'];
				$this->image_height		= $D['1'];
				$this->image_type		= ( ! isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
				$this->image_size_str	= $D['3'];  // string containing height and width
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 设置 XSS 清理
	 *
	 * 启用 XSS 标志以便上载的文件将通过 XSS 过滤器运行
	 *
	 * @param	bool
	 * @return	void
	 */
	public function set_xss_clean($flag = FALSE){
		$this->xss_clean = ($flag == TRUE) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate the image
	 *
	 * @return	bool
	 */
	public function is_image()
	{
		// IE will sometimes return odd mime-types during upload, so here we just standardize all
		// jpegs or pngs to the same file type.

		$png_mimes  = array('image/x-png');
		$jpeg_mimes = array('image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg');

		if (in_array($this->file_type, $png_mimes))
		{
			$this->file_type = 'image/png';
		}

		if (in_array($this->file_type, $jpeg_mimes))
		{
			$this->file_type = 'image/jpeg';
		}

		$img_mimes = array(
							'image/gif',
							'image/jpeg',
							'image/png',
						);

		return (in_array($this->file_type, $img_mimes, TRUE)) ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * 验证允许的文件类型
	 *
	 * @return	bool
	 */
	public function is_allowed_filetype($ignore_mime = FALSE){
		if ($this->allowed_types == '*'){
			return TRUE;
		}

		if (count($this->allowed_types) == 0 OR ! is_array($this->allowed_types)){
			$this->set_error('upload_no_file_types');
			return FALSE;
		}

		$ext = strtolower(ltrim($this->file_ext, '.'));

		if ( ! in_array($ext, $this->allowed_types)){
			return FALSE;
		}

		// Images get some additional checks
		$image_types = array('gif', 'jpg', 'jpeg', 'png', 'jpe');

		if (in_array($ext, $image_types)){
			if (getimagesize($this->file_temp) === FALSE){
				return FALSE;
			}
		}

		if ($ignore_mime === TRUE){
			return TRUE;
		}

		$mime = $this->mimes_types($ext);

		if (is_array($mime)){
			if (in_array($this->file_type, $mime, TRUE)){
				return TRUE;
			}
		}elseif ($mime == $this->file_type){
				return TRUE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Verify that the file is within the allowed size
	 *
	 * @return	bool
	 */
	public function is_allowed_filesize()
	{
		if ($this->max_size != 0  AND  $this->file_size > $this->max_size)
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Verify that the image is within the allowed width/height
	 *
	 * @return	bool
	 */
	public function is_allowed_dimensions()
	{
		if ( ! $this->is_image())
		{
			return TRUE;
		}

		if (function_exists('getimagesize'))
		{
			$D = @getimagesize($this->file_temp);

			if ($this->max_width > 0 AND $D['0'] > $this->max_width)
			{
				return FALSE;
			}

			if ($this->max_height > 0 AND $D['1'] > $this->max_height)
			{
				return FALSE;
			}

			return TRUE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 验证上传路径
	 *
	 * 验证它是否是一个可写的有效上传路径
	 *
	 * @return	bool
	 */
	public function validate_upload_path(){
		if ($this->upload_path == ''){
			$this->set_error('upload_no_filepath');
			return FALSE;
		}

		if (function_exists('realpath') AND @realpath($this->upload_path) !== FALSE){
			$this->upload_path = str_replace("\\", "/", realpath($this->upload_path));
		}

		if ( ! @is_dir($this->upload_path)){
			$this->set_error('upload_no_filepath');
			return FALSE;
		}

		if ( ! is_really_writable($this->upload_path)){
			$this->set_error('upload_not_writable');
			return FALSE;
		}

		$this->upload_path = preg_replace("/(.+?)\/*$/", "\\1/",  $this->upload_path);
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Extract the file extension
	 *
	 * @param	string
	 * @return	string
	 */
	public function get_extension($filename)
	{
		$x = explode('.', $filename);
		return '.'.end($x);
	}

	// --------------------------------------------------------------------

	/**
	 * Clean the file name for security
	 *
	 * @param	string
	 * @return	string
	 */
	public function clean_file_name($filename)
	{
		$bad = array(
						"<!--",
						"-->",
						"'",
						"<",
						">",
						'"',
						'&',
						'$',
						'=',
						';',
						'?',
						'/',
						"%20",
						"%22",
						"%3c",		// <
						"%253c",	// <
						"%3e",		// >
						"%0e",		// >
						"%28",		// (
						"%29",		// )
						"%2528",	// (
						"%26",		// &
						"%24",		// $
						"%3f",		// ?
						"%3b",		// ;
						"%3d"		// =
					);

		$filename = str_replace($bad, '', $filename);

		return stripslashes($filename);
	}

	// --------------------------------------------------------------------

	/**
	 * Limit the File Name Length
	 *
	 * @param	string
	 * @return	string
	 */
	public function limit_filename_length($filename, $length)
	{
		if (strlen($filename) < $length)
		{
			return $filename;
		}

		$ext = '';
		if (strpos($filename, '.') !== FALSE)
		{
			$parts		= explode('.', $filename);
			$ext		= '.'.array_pop($parts);
			$filename	= implode('.', $parts);
		}

		return substr($filename, 0, ($length - strlen($ext))).$ext;
	}

	// --------------------------------------------------------------------

	/**
	 * Runs the file through the XSS clean function
	 *
	 * This prevents people from embedding malicious code in their files.
	 * I'm not sure that it won't negatively affect certain files in unexpected ways,
	 * but so far I haven't found that it causes trouble.
	 *
	 * @return	void
	 */
	public function do_xss_clean()
	{
		$file = $this->file_temp;

		if (filesize($file) == 0)
		{
			return FALSE;
		}

		if (function_exists('memory_get_usage') && memory_get_usage() && ini_get('memory_limit') != '')
		{
			$current = ini_get('memory_limit') * 1024 * 1024;

			// There was a bug/behavioural change in PHP 5.2, where numbers over one million get output
			// into scientific notation.  number_format() ensures this number is an integer
			// http://bugs.php.net/bug.php?id=43053

			$new_memory = number_format(ceil(filesize($file) + $current), 0, '.', '');

			ini_set('memory_limit', $new_memory); // When an integer is used, the value is measured in bytes. - PHP.net
		}

		// If the file being uploaded is an image, then we should have no problem with XSS attacks (in theory), but
		// IE can be fooled into mime-type detecting a malformed image as an html file, thus executing an XSS attack on anyone
		// using IE who looks at the image.  It does this by inspecting the first 255 bytes of an image.  To get around this
		// CI will itself look at the first 255 bytes of an image to determine its relative safety.  This can save a lot of
		// processor power and time if it is actually a clean image, as it will be in nearly all instances _except_ an
		// attempted XSS attack.

		if (function_exists('getimagesize') && @getimagesize($file) !== FALSE)
		{
			if (($file = @fopen($file, 'rb')) === FALSE) // "b" to force binary
			{
				return FALSE; // Couldn't open the file, return FALSE
			}

			$opening_bytes = fread($file, 256);
			fclose($file);

			// These are known to throw IE into mime-type detection chaos
			// <a, <body, <head, <html, <img, <plaintext, <pre, <script, <table, <title
			// title is basically just in SVG, but we filter it anyhow

			if ( ! preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes))
			{
				return TRUE; // its an image, no "triggers" detected in the first 256 bytes, we're good
			}
			else
			{
				return FALSE;
			}
		}

		if (($data = @file_get_contents($file)) === FALSE)
		{
			return FALSE;
		}

		$CI =& get_instance();
		return $CI->security->xss_clean($data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * 设置错误信息
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_error($msg){
		$CI =& get_instance();
		$CI->lang->load('upload');

		if (is_array($msg)){
			foreach ($msg as $val){
				$msg = ($CI->lang->line($val) == FALSE) ? $val : $CI->lang->line($val);
				$this->error_msg[] = $msg;
				log_message('error', $msg);
			}
		}else {
			$msg = ($CI->lang->line($msg) == FALSE) ? $msg : $CI->lang->line($msg);
			$this->error_msg[] = $msg;
			log_message('error', $msg);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display the error message
	 *
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function display_errors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach ($this->error_msg as $val)
		{
			$str .= $open.$val.$close;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * List of Mime Types
	 *
	 * This is a list of mime types.  We use it to validate
	 * the "allowed types" set by the developer
	 *
	 * @param	string
	 * @return	string
	 */
	public function mimes_types($mime)
	{
		global $mimes;

		if (count($this->mimes) == 0)
		{
			if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
			{
				include(APPPATH.'config/'.ENVIRONMENT.'/mimes.php');
			}
			elseif (is_file(APPPATH.'config/mimes.php'))
			{
				include(APPPATH.'config//mimes.php');
			}
			else
			{
				return FALSE;
			}

			$this->mimes = $mimes;
			unset($mimes);
		}

		return ( ! isset($this->mimes[$mime])) ? FALSE : $this->mimes[$mime];
	}

	// --------------------------------------------------------------------

	/**
	 * 预备文件名
	 *
	 * Prevents possible script execution from Apache's handling of files multiple extensions
	 * http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _prep_filename($filename)
	{
		if (strpos($filename, '.') === FALSE OR $this->allowed_types == '*')
		{
			return $filename;
		}

		$parts		= explode('.', $filename);
		$ext		= array_pop($parts);
		$filename	= array_shift($parts);

		foreach ($parts as $part)
		{
			if ( ! in_array(strtolower($part), $this->allowed_types) OR $this->mimes_types(strtolower($part)) === FALSE)
			{
				$filename .= '.'.$part.'_';
			}
			else
			{
				$filename .= '.'.$part;
			}
		}

		$filename .= '.'.$ext;

		return $filename;
	}

	// --------------------------------------------------------------------

	/**
	 * 文件 MIME 类型
	 *
	 * 如果可能的话, 检测上传文件的 (实际) MIME 类型
	 * 输入数组应该是 $_FILES[$field]
	 *
	 * @param	array
	 * @return	void
	 */
	protected function _file_mime_type($file){
		// 验证 MIME 信息字符串 (例如: text/plain; charset=us-ascii)
		$regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';

		/* Fileinfo extension - most reliable method
		 *
		 * Unfortunately, prior to PHP 5.3 - it's only available as a PECL extension and the
		 * more convenient FILEINFO_MIME_TYPE flag doesn't exist.
		 */
		if (function_exists('finfo_file')){
			$finfo = finfo_open(FILEINFO_MIME);
			if (is_resource($finfo)) // It is possible that a FALSE value is returned, if there is no magic MIME database file found on the system
			{
				$mime = @finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				/* According to the comments section of the PHP manual page,
				 * it is possible that this function returns an empty string
				 * for some files (e.g. if they don't exist in the magic MIME database)
				 */
				if (is_string($mime) && preg_match($regexp, $mime, $matches))
				{
					$this->file_type = $matches[1];
					return;
				}
			}
		}

		/* This is an ugly hack, but UNIX-type systems provide a "native" way to detect the file type,
		 * which is still more secure than depending on the value of $_FILES[$field]['type'], and as it
		 * was reported in issue #750 (https://github.com/EllisLab/CodeIgniter/issues/750) - it's better
		 * than mime_content_type() as well, hence the attempts to try calling the command line with
		 * three different functions.
		 *
		 * Notes:
		 *	- the DIRECTORY_SEPARATOR comparison ensures that we're not on a Windows system
		 *	- many system admins would disable the exec(), shell_exec(), popen() and similar functions
		 *	  due to security concerns, hence the function_exists() checks
		 */
		if (DIRECTORY_SEPARATOR !== '\\')
		{
			$cmd = 'file --brief --mime ' . escapeshellarg($file['tmp_name']) . ' 2>&1';

			if (function_exists('exec'))
			{
				/* This might look confusing, as $mime is being populated with all of the output when set in the second parameter.
				 * However, we only neeed the last line, which is the actual return value of exec(), and as such - it overwrites
				 * anything that could already be set for $mime previously. This effectively makes the second parameter a dummy
				 * value, which is only put to allow us to get the return status code.
				 */
				$mime = @exec($cmd, $mime, $return_status);
				if ($return_status === 0 && is_string($mime) && preg_match($regexp, $mime, $matches))
				{
					$this->file_type = $matches[1];
					return;
				}
			}

			if ( (bool) @ini_get('safe_mode') === FALSE && function_exists('shell_exec'))
			{
				$mime = @shell_exec($cmd);
				if (strlen($mime) > 0)
				{
					$mime = explode("\n", trim($mime));
					if (preg_match($regexp, $mime[(count($mime) - 1)], $matches))
					{
						$this->file_type = $matches[1];
						return;
					}
				}
			}

			if (function_exists('popen'))
			{
				$proc = @popen($cmd, 'r');
				if (is_resource($proc))
				{
					$mime = @fread($proc, 512);
					@pclose($proc);
					if ($mime !== FALSE)
					{
						$mime = explode("\n", trim($mime));
						if (preg_match($regexp, $mime[(count($mime) - 1)], $matches))
						{
							$this->file_type = $matches[1];
							return;
						}
					}
				}
			}
		}

		// Fall back to the deprecated mime_content_type(), if available (still better than $_FILES[$field]['type'])
		if (function_exists('mime_content_type'))
		{
			$this->file_type = @mime_content_type($file['tmp_name']);
			if (strlen($this->file_type) > 0) // It's possible that mime_content_type() returns FALSE or an empty string
			{
				return;
			}
		}

		$this->file_type = $file['type'];
	}

	// --------------------------------------------------------------------

}