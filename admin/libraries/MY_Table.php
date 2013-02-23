<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * HTML 表格类
 *
 * 从数据库结果集或数组中自动生成HTML表格
 *
 */
class MY_Table extends CI_Table {

	var $empty_cells		= "&nbsp;";		//空单元格形式, 用 set_empty($value) 设置
	
	public function __construct(){
		parent::__construct();
	}

	// --------------------------------------------------------------------
	
	/**
	 * 预处理参数
	 *
	 * 对所有单元格数据确保标准的关联数组格式
	 * 注: 原函数对参数为数组的情况可能保留 $args[0] 在返回数组
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	function _prep_args($args){
		if (isset($args[0]) AND (count($args) == 1 && is_array($args[0]))){
			if ( ! isset($args[0]['data'])){
				foreach ($args[0] as $key => $val){
					if (is_array($val) && isset($val['data'])){
						$arg[$key] = $val;
					}else {
						$arg[$key] = array('data' => $val);
					}
				}
			}
		}else {
			foreach ($args as $key => $val){
				if ( ! is_array($val)){
					$arg[$key] = array('data' => $val);
				}
			}
		}
		return $arg;
	}
	
	// --------------------------------------------------------------------

	/**
	 * 默认模板
	 *
	 * @access	private
	 * @return	void
	 */
	function _default_template(){
		return  array (
						'table_open'			=> '<table border="0" cellpadding="4" cellspacing="0">',

						'thead_open'			=> '<thead>',
						'thead_close'			=> '</thead>',

						'heading_row_start'		=> '<tr>',
						'heading_row_end'		=> '</tr>',
						'heading_cell_start'	=> '<th>',
						'heading_cell_end'		=> '</th>',

						'tbody_open'			=> '<tbody>',
						'tbody_close'			=> '</tbody>',

						'row_start'				=> '<tr>',
						'row_end'				=> '</tr>',
						'cell_start'			=> '<td>',
						'cell_end'				=> '</td>',

						'row_alt_start'			=> '<tr>',
						'row_alt_end'			=> '</tr>',
						'cell_alt_start'		=> '<td>',
						'cell_alt_end'			=> '</td>',

						'table_close'			=> '</table>'
					);
	}

}