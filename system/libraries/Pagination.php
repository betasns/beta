<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 分页类
 *
 */
class CI_Pagination {

	var $base_url			= ''; // 链接页面
	var $prefix				= ''; // 添加到路径的自定义前缀
	var $suffix				= ''; // 添加到路径的自定义后缀

	var $total_rows			=  0; // 项目总数 (数据库结果)
	var $per_page			= 10; // 每页显示的最大项目数
	var $num_links			=  2; // 放在当前页码的前面和后面的"数字"链接的数量
	var $cur_page			=  0; // 当前页
	var $use_page_numbers	= FALSE; // 默认分页 URL 中是显示当前偏移量, 启用后显示的是当前页码
	var $first_link			= '&lsaquo; First';
	var $next_link			= '&gt;';
	var $prev_link			= '&lt;';
	var $last_link			= 'Last &rsaquo;';
	var $uri_segment		= 3;
	var $full_tag_open		= '';
	var $full_tag_close		= '';
	var $first_tag_open		= '';
	var $first_tag_close	= '&nbsp;';
	var $last_tag_open		= '&nbsp;';
	var $last_tag_close		= '';
	var $first_url			= ''; // 第一页的替代 URL
	var $cur_tag_open		= '&nbsp;<strong>';
	var $cur_tag_close		= '</strong>';
	var $next_tag_open		= '&nbsp;';
	var $next_tag_close		= '&nbsp;';
	var $prev_tag_open		= '&nbsp;';
	var $prev_tag_close		= '';
	var $num_tag_open		= '&nbsp;';
	var $num_tag_close		= '';
	var $page_query_string	= FALSE; // 默认情况下, 分页类库假定使用 URI 段
	var $query_string_segment = 'per_page'; // 传递的查询字符串, 默认是 'per_page'
	var $display_pages		= TRUE;
	var $anchor_class		= '';

	/**
	 * 构造函数
	 *
	 * @access	public
	 * @param	array	初始化参数
	 */
	public function __construct($params = array()){
		if (count($params) > 0){
			$this->initialize($params);
		}

		if ($this->anchor_class != ''){
			$this->anchor_class = 'class="'.$this->anchor_class.'" ';
		}

		log_message('debug', "实例化分页类");
	}

	// --------------------------------------------------------------------

	/**
	 * 初始化分页类
	 *
	 * @access	public
	 * @param	array	初始化参数
	 * @return	void
	 */
	function initialize($params = array()){
		if (count($params) > 0){
			foreach ($params as $key => $val){
				if (isset($this->$key)){
					$this->$key = $val;
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * 生成分页链接
	 *
	 * @access	public
	 * @return	string
	 */
	function create_links(){
		// 如果项目总数或每个页面的项目数是零, 就没有必要继续
		if ($this->total_rows == 0 OR $this->per_page == 0){
			return '';
		}

		// 计算总页数
		$num_pages = ceil($this->total_rows / $this->per_page);

		// 是否仅有一页
		if ($num_pages == 1){
			return '';
		}

		// 设置基本页面索引为起始页码(1)或偏移量(0)
		if ($this->use_page_numbers){
			$base_page = 1;
		}else {
			$base_page = 0;
		}

		// 确定当前页码
		$CI =& get_instance();

		if ($CI->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE){
			if ($CI->input->get($this->query_string_segment) != $base_page){
				$this->cur_page = $CI->input->get($this->query_string_segment);

				$this->cur_page = (int) $this->cur_page;
			}
		}else {
			if ($CI->uri->segment($this->uri_segment) != $base_page){
				$this->cur_page = $CI->uri->segment($this->uri_segment);

				$this->cur_page = (int) $this->cur_page;
			}
		}
		
		// 如果使用页码而不是偏移, 将当前页设置为 1
		if ($this->use_page_numbers AND $this->cur_page == 0){
			$this->cur_page = $base_page;
		}

		$this->num_links = (int)$this->num_links;

		if ($this->num_links < 1){
			show_error('相邻链接数量必须是正数.');
		}

		if ( ! is_numeric($this->cur_page)){
			$this->cur_page = $base_page;
		}

		// 页码是否超出结果范围, 如果是, 显示最后一页
		if ($this->use_page_numbers){
			if ($this->cur_page > $num_pages){
				$this->cur_page = $num_pages;
			}
		}else {
			if ($this->cur_page > $this->total_rows){
				$this->cur_page = ($num_pages - 1) * $this->per_page;
			}
		}

		$uri_page_number = $this->cur_page;
		
		if ( ! $this->use_page_numbers){
			$this->cur_page = floor(($this->cur_page/$this->per_page) + 1);
		}

		// 计算开始和结束的数字
		$start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
		$end   = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

		// 分页是使用 GET 还是 POST?  如果是 get, 添加一个 per_page 查询字符串. 如果是 post, 如果必要对基 URL 添加结尾斜线
		if ($CI->config->item('enable_query_strings') === TRUE OR $this->page_query_string === TRUE){
			$this->base_url = rtrim($this->base_url).'&amp;'.$this->query_string_segment.'=';
		}else {
			$this->base_url = rtrim($this->base_url, '/') .'/';
		}

		// 输出
		$output = '';

		// 渲染 "First" 链接
		if  ($this->first_link !== FALSE AND $this->cur_page > ($this->num_links + 1)){
			$first_url = ($this->first_url == '') ? $this->base_url : $this->first_url;
			$output .= $this->first_tag_open.'<a '.$this->anchor_class.'href="'.$first_url.'">'.$this->first_link.'</a>'.$this->first_tag_close;
		}

		// 渲染 "previous" 链接
		if  ($this->prev_link !== FALSE AND $this->cur_page != 1){
			if ($this->use_page_numbers){
				$i = $uri_page_number - 1;
			}else {
				$i = $uri_page_number - $this->per_page;
			}

			if ($i == 0 && $this->first_url != ''){
				$output .= $this->prev_tag_open.'<a '.$this->anchor_class.'href="'.$this->first_url.'">'.$this->prev_link.'</a>'.$this->prev_tag_close;
			}else {
				$i = ($i == 0) ? '' : $this->prefix.$i.$this->suffix;
				$output .= $this->prev_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$i.'">'.$this->prev_link.'</a>'.$this->prev_tag_close;
			}
		}

		// 渲染页面
		if ($this->display_pages !== FALSE){
			// 写数字链接
			for ($loop = $start -1; $loop <= $end; $loop++){
				if ($this->use_page_numbers){
					$i = $loop;
				}else {
					$i = ($loop * $this->per_page) - $this->per_page;
				}

				if ($i >= $base_page){
					if ($this->cur_page == $loop){
						$output .= $this->cur_tag_open.$loop.$this->cur_tag_close; // Current page
					}else {
						$n = ($i == $base_page) ? '' : $i;

						if ($n == '' && $this->first_url != ''){
							$output .= $this->num_tag_open.'<a '.$this->anchor_class.'href="'.$this->first_url.'">'.$loop.'</a>'.$this->num_tag_close;
						}else {
							$n = ($n == '') ? '' : $this->prefix.$n.$this->suffix;

							$output .= $this->num_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$n.'">'.$loop.'</a>'.$this->num_tag_close;
						}
					}
				}
			}
		}

		// 渲染 "next" 链接
		if ($this->next_link !== FALSE AND $this->cur_page < $num_pages){
			if ($this->use_page_numbers){
				$i = $this->cur_page + 1;
			}else {
				$i = ($this->cur_page * $this->per_page);
			}

			$output .= $this->next_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$this->prefix.$i.$this->suffix.'">'.$this->next_link.'</a>'.$this->next_tag_close;
		}

		// 渲染 "Last" 链接
		if ($this->last_link !== FALSE AND ($this->cur_page + $this->num_links) < $num_pages){
			if ($this->use_page_numbers){
				$i = $num_pages;
			}else {
				$i = (($num_pages * $this->per_page) - $this->per_page);
			}
			$output .= $this->last_tag_open.'<a '.$this->anchor_class.'href="'.$this->base_url.$this->prefix.$i.$this->suffix.'">'.$this->last_link.'</a>'.$this->last_tag_close;
		}

		// Kill double slashes.  Note: Sometimes we can end up with a double slash
		// in the penultimate link so we'll kill all double slashes.
		$output = preg_replace("#([^:])//+#", "\\1/", $output);

		// 如果存在添加封装标签
		$output = $this->full_tag_open.$output.$this->full_tag_close;

		return $output;
	}
}