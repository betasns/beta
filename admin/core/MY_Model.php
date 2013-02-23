<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 模型基类
 * 
 * @author zgz
 * 
 */
class MY_Model extends CI_Model{
	
	/**
	 * 数据库名称
	 *
	 * @var string
	 * @access protected
	 */
	protected $tableName = '';
	
	/**
	 * 构造函数
	 *
	 */
	public function __construct(){
		parent::__construct();
	}
	
}