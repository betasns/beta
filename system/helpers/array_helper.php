<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 数组辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 获取数组中的元素
 * 
 * 本函数测试数组的索引是否已设定并且非空
 * 如果已设有值则返回该值, 否则返回 FALSE, 或任何设定的默认值(函数第三个参数)
 *
 * @access	public
 * @param	string
 * @param	array
 * @param	mixed
 * @return	mixed
 */
if ( ! function_exists('element')){
	function element($item, $array, $default = FALSE){
		if ( ! isset($array[$item]) OR $array[$item] == ""){
			return $default;
		}

		return $array[$item];
	}
}

// ------------------------------------------------------------------------

/**
 * 随机获取数组中的元素
 * 
 * 根据提供的数组, 随机返回该数组内的一个元素
 *
 * @access	public
 * @param	array
 * @return	mixed
 */
if ( ! function_exists('random_element')){
	function random_element($array){
		if ( ! is_array($array)){
			return $array;
		}

		return $array[array_rand($array)];
	}
}

// --------------------------------------------------------------------

/**
 * 获取数组中的若干元素
 *
 * 该函数从一个数组中取得若干元素
 * 该函数测试(传入)数组的每个键值是否在(目标)数组中已定义
 * 如果一个键值不存在, 该键值所对应的值将被置为 FALSE, 或者你可以通过传入的第3个参数来指定默认的值
 * 
 * 这个函数在将 $_POST 数组传入模型时非常有用
 * 通过这种方式可以防止用户发送的额外的 POST 数据进入你的数据表
 * 例如:
 * $this->load->model('post_model');
 * $this->post_model->update(elements(array('id', 'title', 'content'), $_POST));
 * 这样就保证了只有 id, title 和 content 字段被发送以进行更新
 *
 * @access	public
 * @param	array
 * @param	array
 * @param	mixed
 * @return	mixed
 */
if ( ! function_exists('elements')){
	function elements($items, $array, $default = FALSE){
		$return = array();
		
		if ( ! is_array($items)){
			$items = array($items);
		}
		
		foreach ($items as $item){
			if (isset($array[$item])){
				$return[$item] = $array[$item];
			}else {
				$return[$item] = $default;
			}
		}

		return $return;
	}
}