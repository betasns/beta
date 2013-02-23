<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| 数据库连接设置
| -------------------------------------------------------------------
| 此文件将包含访问数据库所需的设置
|
| -------------------------------------------------------------------
| 变量解释
| -------------------------------------------------------------------
|
|	['hostname'] 数据库的主机名, 通常位于本机, 可以表示为 "localhost"
|	['username'] 需要连接到数据库的用户名
|	['password'] 登陆数据库的密码
|	['database'] 需要连接的数据库名
|	['dbdriver'] 数据库类型. 目前支持: mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] 当运行 Active Record 类查询时数据表的前缀
|	['pconnect'] TRUE/FALSE - 是否使用持续连接
|	['db_debug'] TRUE/FALSE - 是否显示数据库错误信息
|	['cache_on'] TRUE/FALSE - 数据库查询缓存是否开启
|	['cachedir'] 数据库查询缓存目录所在的服务器绝对路径
|	['char_set'] 与数据库通信时所使用的字符集
|	['dbcollat'] 与数据库通信时所使用的字符规则
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'zgz';
$db['default']['password'] = '810612';
$db['default']['database'] = 'zgz_beta';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;


/* End of file database.php */
/* Location: ./application/config/database.php */