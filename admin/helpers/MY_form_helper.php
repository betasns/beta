<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 表单辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 表单数据处理
 *
 * 格式化文本, 以便可以在表单元素中安全地使用 HTML 标签而不必担心破坏表单
 * 如果使用的是表单辅助函数中的任何一个, 数据都会自动的进行预处理, 所以没有必要调用本函数
 * 只有当手动创建表单元素时, 才需要本函数
 * 修改: 原函数静态数组未存储已处理数据
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('form_prep')){
	function form_prep($str = '', $field_name = ''){
		static $prepped_fields = array();

		// 如果参数是一个数组就递归处理
		if (is_array($str)){
			foreach ($str as $key => $val){
				$str[$key] = form_prep($val);
			}

			return $str;
		}

		if ($str === ''){
			return '';
		}

		// 是否已经处理过此字段
		if (isset($prepped_fields[$field_name])){
			return $prepped_fields[$field_name];
		}

		$str = htmlspecialchars($str);

		// 万一 htmlspecialchars 遗漏这些
		$str = str_replace(array("'", '"'), array("&#39;", "&quot;"), $str);

		if ($field_name != ''){
			$prepped_fields[$field_name] = $str;
		}

		return $str;
	}
}