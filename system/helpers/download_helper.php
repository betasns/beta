<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 下载辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 强制下载
 *
 * 生成强制下载的头部
 * 注: 如果想在服务器上下载一个存在文件, 需要将它读到一个字符串中
 * 例如: $data = file_get_contents("/path/to/photo.jpg"); // 读文件内容
 *
 * @access	public
 * @param	string	下载文件的文件名
 * @param	mixed	文件数据
 * @return	void
 */
if ( ! function_exists('force_download')){
	function force_download($filename = '', $data = ''){
		if ($filename == '' OR $data == ''){
			return FALSE;
		}

		// 尝试确定文件名中是否包含文件扩展名
		// 需要文件扩展名以便设置 MIME 类型
		if (FALSE === strpos($filename, '.')){
			return FALSE;
		}

		// 获取文件扩展名
		$x = explode('.', $filename);
		$extension = end($x);

		// 加载 mime 类型
		if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/mimes.php')){
			include(APPPATH.'config/'.ENVIRONMENT.'/mimes.php');
		}elseif (is_file(APPPATH.'config/mimes.php')){
			include(APPPATH.'config/mimes.php');
		}

		// 如果无法找到则设置默认 mime
		if ( ! isset($mimes[$extension])){
			$mime = 'application/octet-stream';
		}else {
			$mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
		}

		// 生成服务器题头
		if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== FALSE){
			header('Content-Type: "'.$mime.'"');
			// 简体中文系统应该转成 GB2312 或 GBK
			// 即将 $filename 改为 iconv('utf-8', 'gb2312', $filename)
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header("Content-Transfer-Encoding: binary");
			header('Pragma: public');
			header("Content-Length: ".strlen($data));
		}else {
			header('Content-Type: "'.$mime.'"');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header("Content-Transfer-Encoding: binary");
			header('Expires: 0');
			header('Pragma: no-cache');
			header("Content-Length: ".strlen($data));
		}

		exit($data);
	}
}