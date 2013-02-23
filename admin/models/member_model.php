<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 会员
 * 
 * @author zgz
 * 
 */
class Member_model extends MY_Model{
	
	public function __construct(){
		parent::__construct();
		
		$this->tableName = 'member';
	}
}