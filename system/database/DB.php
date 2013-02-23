<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 初始化数据库
 *
 */
function &DB($params = '', $active_record_override = NULL){
	// 如果传递的不是 DSN 字符串, 加载 DB 配置文件
	if (is_string($params) AND strpos($params, '://') === FALSE){
		// 加载配置文件
		if ( ! defined('ENVIRONMENT') OR ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php')){
			if ( ! file_exists($file_path = APPPATH.'config/database.php')){
				show_error('配置文件 database.php 不存在.');
			}
		}

		include($file_path);

		if ( ! isset($db) OR count($db) == 0){
			show_error('没有在数据库配置文件中发现数据库连接设置.');
		}

		if ($params != ''){
			$active_group = $params;
		}

		if ( ! isset($active_group) OR ! isset($db[$active_group])){
			show_error('指定了一个无效的数据库连接组.');
		}

		$params = $db[$active_group];
	}elseif (is_string($params)){

		/**
		 * 从 DSN 字符串解析 URL
		 * DSN 中必须有这个原型: $dsn = 'driver://username:password@hostname/database';
		 */

		if (($dns = @parse_url($params)) === FALSE){
			show_error('无效的 DB 连接字符串');
		}

		$params = array(
							'dbdriver'	=> $dns['scheme'],
							'hostname'	=> (isset($dns['host'])) ? rawurldecode($dns['host']) : '',
							'username'	=> (isset($dns['user'])) ? rawurldecode($dns['user']) : '',
							'password'	=> (isset($dns['pass'])) ? rawurldecode($dns['pass']) : '',
							'database'	=> (isset($dns['path'])) ? rawurldecode(substr($dns['path'], 1)) : ''
						);

		// 是否进行额外地配置项设置
		if (isset($dns['query'])){
			parse_str($dns['query'], $extra);

			foreach ($extra as $key => $val){
				if (strtoupper($val) == "TRUE"){
					$val = TRUE;
				}elseif (strtoupper($val) == "FALSE"){
					$val = FALSE;
				}

				$params[$key] = $val;
			}
		}
	}

	// 是否有指定的 DB
	if ( ! isset($params['dbdriver']) OR $params['dbdriver'] == ''){
		show_error('没有选择连接的数据库类型.');
	}

	// 加载 DB 类
	// 注意: Since the active record class is optional
	// we need to dynamically create a class that extends proper parent class
	// based on whether we're using the active record class or not.
	// Kudos to Paul for discovering this clever use of eval()

	if ($active_record_override !== NULL){
		$active_record = $active_record_override;
	}

	require_once(BASEPATH.'database/DB_driver.php');

	if ( ! isset($active_record) OR $active_record == TRUE){
		require_once(BASEPATH.'database/DB_active_rec.php');

		if ( ! class_exists('CI_DB')){
			eval('class CI_DB extends CI_DB_active_record { }');
		}
	}else {
		if ( ! class_exists('CI_DB')){
			eval('class CI_DB extends CI_DB_driver { }');
		}
	}

	require_once(BASEPATH.'database/drivers/'.$params['dbdriver'].'/'.$params['dbdriver'].'_driver.php');

	// 实例化 DB 适配器
	$driver = 'CI_DB_'.$params['dbdriver'].'_driver';
	$DB = new $driver($params);

	if ($DB->autoinit == TRUE){
		$DB->initialize();
	}

	if (isset($params['stricton']) && $params['stricton'] == TRUE){
		$DB->query('SET SESSION sql_mode="STRICT_ALL_TABLES"');
	}

	return $DB;
}