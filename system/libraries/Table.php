<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * HTML 表格类
 *
 * 从数据库结果集或数组中自动生成 HTML 表格
 *
 */
class CI_Table {

	var $rows				= array();		//表格行, 用 add_row() 设置 注: 用 $this->_prep_args($args) 处理参数问题
	var $heading			= array();		//表格表头, 用 set_heading() 设置 注: 用 $this->_prep_args($args) 处理参数问题
	var $auto_heading		= TRUE;
	var $caption			= NULL;			//表格标题, 用 set_caption($caption) 设置
	var $template			= NULL;			//模板数组, 用 set_template($template) 设置
	var $newline			= "\n";
	var $empty_cells		= "";			//空单元格形式, 用 set_empty($value) 设置
	var $function			= FALSE;

	public function __construct(){
		log_message('debug', "初始化 HTML 表格类");
	}

	// --------------------------------------------------------------------

	/**
	 * 设置模板
	 * 
	 * 可以提交整个模板或局部模板
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function set_template($template){
		if ( ! is_array($template)){
			return FALSE;
		}

		$this->template = $template;
	}

	// --------------------------------------------------------------------
	
	/**
	 * 添加表格标题
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_caption($caption){
		$this->caption = $caption;
	}
	
	// --------------------------------------------------------------------

	/**
	 * 设置表格表头
	 *
	 * 可以提交一个数组或分开的参数
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function set_heading(){
		$args = func_get_args();

		$this->heading = $this->_prep_args($args);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 设置空单元格
	 *
	 * 设置一个默认值, 用来显示在表格中内容为空的单元格
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function set_empty($value){
		$this->empty_cells = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * 设置二维数组
	 * 这个函数以一个一维数组为输入, 创建一个二维数组, 它的深度和列数一样
	 * 这个函数可以把一个带有多个元素的单一数组根据表格的列数进行整理并显示
	 *
	 * @access	public
	 * @param	array
	 * @param	int
	 * @return	void
	 */
	function make_columns($array = array(), $col_limit = 0){
		if ( ! is_array($array) OR count($array) == 0){
			return FALSE;
		}

		// 关闭自动标题的功能, 因为想从一个一维数组获取标题的可能性很小
		$this->auto_heading = FALSE;

		if ($col_limit == 0){
			return $array;
		}

		$new = array();
		while (count($array) > 0){
			$temp = array_splice($array, 0, $col_limit);

			if (count($temp) < $col_limit){
				for ($i = count($temp); $i < $col_limit; $i++){
					$temp[] = '&nbsp;';
				}
			}

			$new[] = $temp;
		}

		return $new;
	}

	// --------------------------------------------------------------------

	/**
	 * 添加表格行
	 *
	 * 可以提交一个数组或分开的参数
	 *
	 * @access	public
	 * @param	mixed
	 * @return	void
	 */
	function add_row()
	{
		$args = func_get_args();
		$this->rows[] = $this->_prep_args($args);
	}

	// --------------------------------------------------------------------

	/**
	 * 预处理参数
	 *
	 * 对所有单元格数据确保标准的关联数组格式
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
						$args[$key] = $val;
					}else {
						$args[$key] = array('data' => $val);
					}
				}
			}
		}else {
			foreach ($args as $key => $val){
				if ( ! is_array($val)){
					$args[$key] = array('data' => $val);
				}
			}
		}
		return $args;
	}

	// --------------------------------------------------------------------

	/**
	 * 生成表
	 * 
	 * 返回一个包含生成的表格的字符串
	 *
	 * @access	public
	 * @param	mixed	可选参数, 该参数可以是一个数组或是从数据库获取的结果对象
	 * @return	string
	 */
	function generate($table_data = NULL){
		// 可以任意地把表数据传递给该函数, 无论是作为一个数据库结果对象或一个数组
		if ( ! is_null($table_data)){
			if (is_object($table_data)){
				$this->_set_from_object($table_data);
			}elseif (is_array($table_data)){
				$set_heading = (count($this->heading) == 0 AND $this->auto_heading == FALSE) ? FALSE : TRUE;
				$this->_set_from_array($table_data, $set_heading);
			}
		}

		// 是否有数据显示
		if (count($this->heading) == 0 AND count($this->rows) == 0){
			return '未定义表格数据';
		}

		// 编译模板
		$this->_compile_template();

		// 设置自定义单元格操作函数
		$function = $this->function;

		// 建立表格

		$out = $this->template['table_open'];												//默认表格标签开始<table border="0" cellpadding="4" cellspacing="0">
		$out .= $this->newline;

		// 添加标题
		if ($this->caption){
			$out .= $this->newline;
			$out .= '<caption>' . $this->caption . '</caption>';							//设置标题<caption></caption>
			$out .= $this->newline;
		}

		// 是否有表头显示
		if (count($this->heading) > 0){
			$out .= $this->template['thead_open'];											//默认表头开始<thead>
			$out .= $this->newline;
			$out .= $this->template['heading_row_start'];									//默认表格行开始<tr>
			$out .= $this->newline;

			foreach ($this->heading as $heading){
				$temp = $this->template['heading_cell_start'];								//默认表头单元格开始<th>

				foreach ($heading as $key => $val){
					if ($key != 'data'){
						$temp = str_replace('<th', "<th $key='$val'", $temp);
					}
				}

				$out .= $temp;
				$out .= isset($heading['data']) ? $heading['data'] : '';
				$out .= $this->template['heading_cell_end'];								//默认表头单元格结束</th>
			}

			$out .= $this->template['heading_row_end'];										//默认表格行结束</tr>
			$out .= $this->newline;
			$out .= $this->template['thead_close'];											//默认表头结束</thead>
			$out .= $this->newline;
		}

		// 建立表格行
		if (count($this->rows) > 0){
			$out .= $this->template['tbody_open'];											//默认表格主体开始<tbody>
			$out .= $this->newline;

			$i = 1;
			foreach ($this->rows as $row){
				if ( ! is_array($row)){
					break;
				}

				// 使用模来使行颜色交替
				$name = (fmod($i++, 2)) ? '' : 'alt_';

				$out .= $this->template['row_'.$name.'start'];								//默认表格行开始<tr>
				$out .= $this->newline;

				foreach ($row as $cell){
					$temp = $this->template['cell_'.$name.'start'];							//默认表格单元格开始<td>

					foreach ($cell as $key => $val){
						if ($key != 'data'){
							$temp = str_replace('<td', "<td $key='$val'", $temp);
						}
					}

					$cell = isset($cell['data']) ? $cell['data'] : '';
					$out .= $temp;

					if ($cell === "" OR $cell === NULL){
						$out .= $this->empty_cells;
					}else {
						if ($function !== FALSE && is_callable($function)){
							$out .= call_user_func($function, $cell);						//使用函数处理单元格内容
						}else {
							$out .= $cell;
						}
					}

					$out .= $this->template['cell_'.$name.'end'];							//默认表格单元格结束</td>
				}

				$out .= $this->template['row_'.$name.'end'];								//默认表格行结束</tr>
				$out .= $this->newline;
			}

			$out .= $this->template['tbody_close'];											//默认表格主体结束</tbody>
			$out .= $this->newline;
		}

		$out .= $this->template['table_close'];												//默认表格标签关闭</table>

		// 在生成表之前清除表格类的属性
		$this->clear();

		return $out;
	}

	// --------------------------------------------------------------------

	/**
	 * 清除表格数组
	 * 用于生成多个表的情况
	 *
	 * @access	public
	 * @return	void
	 */
	function clear(){
		$this->rows				= array();
		$this->heading			= array();
		$this->auto_heading		= TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * 从数据库结果对象中设置表格数据
	 *
	 * @access	public
	 * @param	object
	 * @return	void
	 */
	function _set_from_object($query){
		if ( ! is_object($query)){
			return FALSE;
		}

		// 从表格列名中生成标题
		if (count($this->heading) == 0){
			if ( ! method_exists($query, 'list_fields')){
				return FALSE;
			}

			$this->heading = $this->_prep_args($query->list_fields());
		}

		// Next blast through the result array and build out the rows

		if ($query->num_rows() > 0){
			foreach ($query->result_array() as $row){
				$this->rows[] = $this->_prep_args($row);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 从数组中设置表格数据
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function _set_from_array($data, $set_heading = TRUE){
		if ( ! is_array($data) OR count($data) == 0){
			return FALSE;
		}

		$i = 0;
		foreach ($data as $row){
			// 如果尚未设置题头, 将使用数组第一行作为题头
			if ($i == 0 AND count($data) > 1 AND count($this->heading) == 0 AND $set_heading == TRUE){
				$this->heading = $this->_prep_args($row);
			}else {
				$this->rows[] = $this->_prep_args($row);
			}

			$i++;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 编译模板
	 *
	 * @access	private
	 * @return	void
	 */
	function _compile_template(){
		if ($this->template == NULL){
			$this->template = $this->_default_template();
			return;
		}

		$this->temp = $this->_default_template();
		foreach (array('table_open', 'thead_open', 'thead_close', 'heading_row_start', 'heading_row_end', 'heading_cell_start', 'heading_cell_end', 'tbody_open', 'tbody_close', 'row_start', 'row_end', 'cell_start', 'cell_end', 'row_alt_start', 'row_alt_end', 'cell_alt_start', 'cell_alt_end', 'table_close') as $val){
			if ( ! isset($this->template[$val])){
				$this->template[$val] = $this->temp[$val];
			}
		}
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

						'row_alt_start'		=> '<tr>',
						'row_alt_end'			=> '</tr>',
						'cell_alt_start'		=> '<td>',
						'cell_alt_end'			=> '</td>',

						'table_close'			=> '</table>'
					);
	}

}