<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 语言类
 *
 */
class CI_Lang {

	/**
	 * 翻译列表
	 *
	 * @var array
	 */
	var $language	= array();
	/**
	 * 已加载的语言文件列表
	 *
	 * @var array
	 */
	var $is_loaded	= array();

	/**
	 * 构造函数
	 *
	 * @access	public
	 */
	function __construct(){
		log_message('debug', "初始化语言类");
	}

	// --------------------------------------------------------------------

	/**
	 * 加载语言文件
	 *
	 * @access	public
	 * @param	mixed	加载的语言文件名
	 * @param	string	语言
	 * @param	bool	返回加载的翻译数组
	 * @param 	bool	为加载的语言文件名添加后缀
	 * @param 	string	寻找语言文件的替代路径
	 * @return	mixed
	 */
	function load($langfile = '', $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = ''){
		$langfile = str_replace('.php', '', $langfile);

		if ($add_suffix == TRUE){
			$langfile = str_replace('_lang.', '', $langfile).'_lang';
		}

		$langfile .= '.php';

		if (in_array($langfile, $this->is_loaded, TRUE)){
			return;
		}

		$config =& get_config();

		if ($idiom == ''){
			$deft_lang = ( ! isset($config['language'])) ? 'english' : $config['language'];
			$idiom = ($deft_lang == '') ? 'english' : $deft_lang;
		}

		// 确定语言文件并加载
		if ($alt_path != '' && file_exists($alt_path.'language/'.$idiom.'/'.$langfile)){
			include($alt_path.'language/'.$idiom.'/'.$langfile);
		}else {
			$found = FALSE;

			foreach (get_instance()->load->get_package_paths(TRUE) as $package_path){
				if (file_exists($package_path.'language/'.$idiom.'/'.$langfile)){
					include($package_path.'language/'.$idiom.'/'.$langfile);
					$found = TRUE;
					break;
				}
			}

			if ($found !== TRUE){
				show_error('不能加载请求的语言文件: language/'.$idiom.'/'.$langfile);
			}
		}


		if ( ! isset($lang)){
			log_message('error', '语言文件不包含任何数据: language/'.$idiom.'/'.$langfile);
			return;
		}

		if ($return == TRUE){
			return $lang;
		}

		$this->is_loaded[] = $langfile;
		$this->language = array_merge($this->language, $lang);
		unset($lang);

		log_message('debug', '已加载语言文件: language/'.$idiom.'/'.$langfile);
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 获取一个语言变量
	 *
	 * @access	public
	 * @param	string	$line
	 * @return	string
	 */
	function line($line = ''){
		$value = ($line == '' OR ! isset($this->language[$line])) ? FALSE : $this->language[$line];
		
		if ($value === FALSE){
			log_message('error', '没有找到语言行 "'.$line.'"');
		}

		return $value;
	}

}