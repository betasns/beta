<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 基准测试类
 *
 * 这个类可以标记点和计算它们之间的时间差, 也可以显示内存消耗
 *
 */
class CI_Benchmark {

	/**
	 * 所有基准标记和它们被添加时间的列表
	 *
	 * @var array
	 */
	var $marker = array();

	// --------------------------------------------------------------------

	/**
	 * 设置基准测试标记
	 *
	 * 多次调用这个函数可以使多个执行点被计时
	 *
	 * @access	public
	 * @param	string	$name	标记名称
	 * @return	void
	 */
	function mark($name){
		$this->marker[$name] = microtime();
	}

	// --------------------------------------------------------------------

	/**
	 * 计算两个标记点之间的时间差
	 *
	 * 如果第一个参数是空, 返回 {elapsed_time} 伪变量
	 * 这使得完整的系统执行时间在模板中显示
	 * 输出类将会替换这个变量的真实值
	 *
	 * @access	public
	 * @param	string	一个特定标记点
	 * @param	string	一个特定标记点
	 * @param	integer	小数位数
	 * @return	mixed
	 */
	function elapsed_time($point1 = '', $point2 = '', $decimals = 4){
		if ($point1 == ''){
			return '{elapsed_time}';
		}

		// 如果第一个标记点不存在, 返回空字符
		if ( ! isset($this->marker[$point1])){
			return '';
		}

		// //第二个标记点不存在则以当前时间设置第二个标记点
		if ( ! isset($this->marker[$point2])){
			$this->marker[$point2] = microtime();
		}

		list($sm, $ss) = explode(' ', $this->marker[$point1]);
		list($em, $es) = explode(' ', $this->marker[$point2]);

		return number_format(($em + $es) - ($sm + $ss), $decimals);
	}

	// --------------------------------------------------------------------

	/**
	 * 内存使用情况
	 *
	 * 这个函数返回 {memory_usage} 伪变量
	 * 在模板中最后会替换成系统执行完成的内存占用
	 * 输出类将会替换这个变量的真实值
	 *
	 * @access	public
	 * @return	string
	 */
	function memory_usage(){
		return '{memory_usage}';
	}

}