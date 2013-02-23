<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * HTML 辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 标题
 *
 * 生成一个HTML的标题标签
 * 第一个参数是数据, 第二个参数是标题标签的大小
 * 此外, 为了给标题标签增加像类, id, 内联样式等属性, 可以使用第三个参数
 *
 * @access	public
 * @param	string
 * @param	integer
 * @return	string
 */
if ( ! function_exists('heading')){
	function heading($data = '', $h = '1', $attributes = ''){
		$attributes = ($attributes != '') ? ' '.$attributes : $attributes;
		return "<h".$h.$attributes.">".$data."</h".$h.">";
	}
}

// ------------------------------------------------------------------------

/**
 * 无序列表
 *
 * 从单维或多维数组生成一个 HTML 无序列表
 *
 * @access	public
 * @param	array
 * @param	mixed
 * @return	string
 */
if ( ! function_exists('ul')){
	function ul($list, $attributes = ''){
		return _list('ul', $list, $attributes);
	}
}

// ------------------------------------------------------------------------

/**
 * 有序列表
 *
 * 从单维或多维数组生成一个 HTML 有序列表
 *
 * @access	public
 * @param	array
 * @param	mixed
 * @return	string
 */
if ( ! function_exists('ol')){
	function ol($list, $attributes = ''){
		return _list('ol', $list, $attributes);
	}
}

// ------------------------------------------------------------------------

/**
 * 生成列表
 *
 * 从单维或多维数组生成一个 HTML 列表
 *
 * @access	private
 * @param	string
 * @param	mixed
 * @param	mixed
 * @param	integer
 * @return	string
 */
if ( ! function_exists('_list')){
	function _list($type = 'ul', $list, $attributes = '', $depth = 0){
		if ( ! is_array($list)){
			return $list;
		}

		// 设置基于深度的缩进
		$out = str_repeat(" ", $depth);

		// 是否存在属性? 如果是, 生成一个字符串
		if (is_array($attributes)){
			$atts = '';
			foreach ($attributes as $key => $val){
				$atts .= ' ' . $key . '="' . $val . '"';
			}
			$attributes = $atts;
		}elseif (is_string($attributes) AND strlen($attributes) > 0){
			$attributes = ' '. $attributes;
		}

		// 列表开始标签
		$out .= "<".$type.$attributes.">\n";

		// 循环列表元素
		// 如果遇到一个数组, 将递归地调用 _list()
		static $_last_list_item = '';
		foreach ($list as $key => $val){
			$_last_list_item = $key;

			$out .= str_repeat(" ", $depth + 2);
			$out .= "<li>";

			if ( ! is_array($val)){
				$out .= $val;
			}else {
				$out .= $_last_list_item."\n";
				$out .= _list($type, $val, '', $depth + 4);
				$out .= str_repeat(" ", $depth + 2);
			}

			$out .= "</li>\n";
		}

		// 设置结束标签缩进
		$out .= str_repeat(" ", $depth);

		// 列表关闭标签
		$out .= "</".$type.">\n";

		return $out;
	}
}

// ------------------------------------------------------------------------

/**
 * 图像
 *
 * 生成一个 <img /> 元素
 *
 * @access	public
 * @param	mixed
 * @return	string
 */
if ( ! function_exists('img')){
	function img($src = '', $index_page = FALSE){
		if ( ! is_array($src) ){
			$src = array('src' => $src);
		}

		// 如果没有定义 alt 属性, 将其设置为空字符串
		if ( ! isset($src['alt'])){
			$src['alt'] = '';
		}

		$img = '<img';

		foreach ($src as $k=>$v){
			if ($k == 'src' AND strpos($v, '://') === FALSE){
				$CI =& get_instance();

				if ($index_page === TRUE){
					$img .= ' src="'.$CI->config->site_url($v).'"';
				}else {
					$img .= ' src="'.$CI->config->slash_item('base_url').$v.'"';
				}
			} else{
				$img .= " $k=\"$v\"";
			}
		}

		$img .= '/>';

		return $img;
	}
}

// ------------------------------------------------------------------------

/**
 * Doctype
 *
 * 生成页面文档类型声明
 *
 * 有效的选项是: xhtml-11, xhtml-strict, xhtml-trans, xhtml-frame, html4-strict, html4-trans, and html4-frame
 * 值被保存在 doctypes 配置文件
 *
 * @access	public
 * @param	string	type	The doctype to be generated
 * @return	string
 */
if ( ! function_exists('doctype')){
	function doctype($type = 'xhtml1-strict'){
		global $_doctypes;

		if ( ! is_array($_doctypes)){
			if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/doctypes.php')){
				include(APPPATH.'config/'.ENVIRONMENT.'/doctypes.php');
			}elseif (is_file(APPPATH.'config/doctypes.php')){
				include(APPPATH.'config/doctypes.php');
			}

			if ( ! is_array($_doctypes)){
				return FALSE;
			}
		}

		if (isset($_doctypes[$type])){
			return $_doctypes[$type];
		}else {
			return FALSE;
		}
	}
}

// ------------------------------------------------------------------------

/**
 * 链接
 *
 * 生成到 CSS 文件的链接
 *
 * @access	public
 * @param	mixed	样式表的 href 或数组
 * @param	string	rel
 * @param	string	type
 * @param	string	title
 * @param	string	media
 * @param	boolean	index_page 是否应该添加到 css 路径
 * @return	string
 */
if ( ! function_exists('link_tag')){
	function link_tag($href = '', $rel = 'stylesheet', $type = 'text/css', $title = '', $media = '', $index_page = FALSE){
		$CI =& get_instance();

		$link = '<link ';

		if (is_array($href)){
			foreach ($href as $k=>$v){
				if ($k == 'href' AND strpos($v, '://') === FALSE){
					if ($index_page === TRUE){
						$link .= 'href="'.$CI->config->site_url($v).'" ';
					}else {
						$link .= 'href="'.$CI->config->slash_item('base_url').$v.'" ';
					}
				}else {
					$link .= "$k=\"$v\" ";
				}
			}

			$link .= "/>";
		}else {
			if ( strpos($href, '://') !== FALSE){
				$link .= 'href="'.$href.'" ';
			}elseif ($index_page === TRUE){
				$link .= 'href="'.$CI->config->site_url($href).'" ';
			}else {
				$link .= 'href="'.$CI->config->slash_item('base_url').$href.'" ';
			}

			$link .= 'rel="'.$rel.'" type="'.$type.'" ';

			if ($media	!= ''){
				$link .= 'media="'.$media.'" ';
			}

			if ($title	!= ''){
				$link .= 'title="'.$title.'" ';
			}

			$link .= '/>';
		}

		return $link;
	}
}

// ------------------------------------------------------------------------

/**
 * 创建 meta 标签
 *
 * @access	public
 * @param	array
 * @return	string
 */
if ( ! function_exists('meta')){
	function meta($name = '', $content = '', $type = 'name', $newline = "\n"){
		// 由于允许字符串, 简单数组或多维数组作为参数, 因此需要预处理
		if ( ! is_array($name)){
			$name = array(array('name' => $name, 'content' => $content, 'type' => $type, 'newline' => $newline));
		}else {
			// 处理简单数组
			if (isset($name['name'])){
				$name = array($name);
			}
		}

		$str = '';
		foreach ($name as $meta){
			$type		= ( ! isset($meta['type']) OR $meta['type'] == 'name') ? 'name' : 'http-equiv';
			$name		= ( ! isset($meta['name']))		? ''	: $meta['name'];
			$content	= ( ! isset($meta['content']))	? ''	: $meta['content'];
			$newline	= ( ! isset($meta['newline']))	? "\n"	: $meta['newline'];

			$str .= '<meta '.$type.'="'.$name.'" content="'.$content.'" />'.$newline;
		}

		return $str;
	}
}

// ------------------------------------------------------------------------

/**
 * 生成不换行的指定个数的空格标签
 *
 * @access	public
 * @param	integer
 * @return	string
 */
if ( ! function_exists('nbs')){
	function nbs($num = 1){
		return str_repeat("&nbsp;", $num);
	}
}

// ------------------------------------------------------------------------

/**
 * 根据提供的数字生成 HTML BR 标签
 *
 * @access	public
 * @param	integer
 * @return	string
 */
if ( ! function_exists('br')){
	function br($num = 1){
		return str_repeat("<br />", $num);
	}
}