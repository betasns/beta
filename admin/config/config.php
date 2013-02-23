<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| 网站根 URL
|--------------------------------------------------------------------------
|
| 通常情况下, 这是带斜线的基础 URL
| 如果没有设置, 系统将猜测出协议, 域名和路径
|
*/
$config['base_url']	= '';

/*
|--------------------------------------------------------------------------
| Index 文件
|--------------------------------------------------------------------------
|
| 通常情况下, 这是 index.php file, 除非已经改名
| 如果使用 mod_rewrite to remove the page set this
| variable so that it is blank.
|
*/
$config['index_page'] = 'admin.php';

/*
|--------------------------------------------------------------------------
| URI 协议
|--------------------------------------------------------------------------
|
| 这个配置项定义了用来检索 URL 字符串的服务器全局变量
| 对于大多数服务器的默认设置为 'AUTO'
| 当链接不能工作的时候, 试着用其它的选项
|
| 'AUTO'			默认 - 自动检测
| 'PATH_INFO'		使用 PATH_INFO
| 'QUERY_STRING'	使用 QUERY_STRING
| 'REQUEST_URI'		使用 REQUEST_URI
| 'ORIG_PATH_INFO'	使用 ORIG_PATH_INFO
|
*/
$config['uri_protocol']	= 'AUTO';

/*
|--------------------------------------------------------------------------
| URL 后缀
|--------------------------------------------------------------------------
|
| 此选项允许对所有的 URL 添加一个后缀
| 
*/

$config['url_suffix'] = '.html';

/*
|--------------------------------------------------------------------------
| 默认语言
|--------------------------------------------------------------------------
|
| 这决定了应使用的语言文件
| 如果打算使用英语以外的语言, 确保有可用的翻译
|
*/
$config['language']	= 'english';

/*
|--------------------------------------------------------------------------
| 缺省字符集
|--------------------------------------------------------------------------
|
| This determines which character set is used by default in various methods
| that require a character set to be provided.
|
*/
$config['charset'] = 'UTF-8';

/*
|--------------------------------------------------------------------------
| 启用/禁用系统钩子
|--------------------------------------------------------------------------
|
| 通过将此变量设置为 TRUE (boolean) 启用钩子功能
|
*/
$config['enable_hooks'] = TRUE;


/*
|--------------------------------------------------------------------------
| 类扩展前缀
|--------------------------------------------------------------------------
|
| 此项设置当扩展本地 libraries 时文件名/类名的前缀
|
*/
$config['subclass_prefix'] = 'MY_';


/*
|--------------------------------------------------------------------------
| 允许的 URL 字符
|--------------------------------------------------------------------------
|
| 指定一个字符可以在 URL 中使用的正则表达式
| 当试图提交包含禁用字符的 URL 时会得到警告信息
|
| 作为一种安全措施, 强烈建议尽可能用尽可能少的字符限制 URL
| 默认情况下, 只有这些是允许的: a-z 0-9~%.:_-
|
| 允许任何字符留为空白 -- 除非你疯了
|
| 不要改变, 除非你完全明白其影响!!!
|
*/
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

/*
|--------------------------------------------------------------------------
| 启用查询字符串
|--------------------------------------------------------------------------
|
| 默认情况下使用对搜索引擎友好的 URI 段
|
| 默认情况下可以访问 $_GET 数组
| 如果由于某种原因需要禁用它, 设置 'allow_get_array' 为 FALSE.
|
| 可以选择是否启用标准查询字符串的URL
| 选项是: TRUE 或 FALSE (boolean)
|
| 其它项是设置调用控制器及其函数的查询字符串词
|
| 请注意启用此功能是将使一些辅助函数不会如预期般运作, 如 URL 辅助函数(或是其他生成 URL 的辅助函数, 例如表单辅助函数)
| 这是因为系统设计主要是为了使用 URI 段
|
*/
$config['allow_get_array']		= TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger']	= 'c';
$config['function_trigger']		= 'm';
$config['directory_trigger']	= 'd'; // 若控制器在子目录下, URL中需带有这个参数, 指定控制器所在子目录名称

/*
|--------------------------------------------------------------------------
| 错误记录阈值
|--------------------------------------------------------------------------
|
| 如果启用了错误日志, 可以设置一个错误阈值来决定记录内容
| 可以通过设置阈值超过 0 来启用错误日志
| 阈值确定记录的内容, 阈值选项是:
|
|	0 = 禁用日志, 错误日志关闭
|	1 = 错误信息 (包括 PHP 错误)
|	2 = 调试信息
|	3 = 参考信息
|	4 = 全部信息
|
| 对于一个网站通常只启用记录 Errors (1), 否则日志文件将很快填满
|
*/
$config['log_threshold'] = 1;

/*
|--------------------------------------------------------------------------
| 错误日志目录路径
|--------------------------------------------------------------------------
|
| 除非希望设置 application/logs/ 文件夹以外的路径, 否则保留为空
| 使用一个带结尾斜线的完整的服务器路径
|
*/
$config['log_path'] = '';

/*
|--------------------------------------------------------------------------
| 日志日期格式
|--------------------------------------------------------------------------
|
| 每个被记录项有一个关联日期
| 可以使用 PHP date 代码来设置日期格式
|
*/
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------
| 缓存目录路径
|--------------------------------------------------------------------------
|
| 除非希望设置默认的系统缓存文件夹以外的路径, 否则保留为空
| 使用一个带结尾斜线的完整的服务器路径
|
*/
$config['cache_path'] = '';

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
|
| If you use the Encryption class or the Session class you
| MUST set an encryption key.  See the user guide for info.
|
*/
$config['encryption_key'] = '';

/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
|
| 'sess_cookie_name'		= the name you want for the cookie
| 'sess_expiration'			= the number of SECONDS you want the session to last.
|   by default sessions last 7200 seconds (two hours).  Set to zero for no expiration.
| 'sess_expire_on_close'	= Whether to cause the session to expire automatically
|   when the browser window is closed
| 'sess_encrypt_cookie'		= Whether to encrypt the cookie
| 'sess_use_database'		= Whether to save the session data to a database
| 'sess_table_name'			= The name of the session database table
| 'sess_match_ip'			= Whether to match the user's IP address when reading the session data
| 'sess_match_useragent'	= Whether to match the User Agent when reading the session data
| 'sess_time_to_update'		= how many seconds between CI refreshing Session Information
|
*/
$config['sess_cookie_name']		= 'ci_session';
$config['sess_expiration']		= 7200;
$config['sess_expire_on_close']	= FALSE;
$config['sess_encrypt_cookie']	= FALSE;
$config['sess_use_database']	= FALSE;
$config['sess_table_name']		= 'ci_sessions';
$config['sess_match_ip']		= FALSE;
$config['sess_match_useragent']	= TRUE;
$config['sess_time_to_update']	= 300;

/*
|--------------------------------------------------------------------------
| Cookie 相关变量
|--------------------------------------------------------------------------
|
| 'cookie_prefix' = 设置前缀以避免冲突
| 'cookie_domain' = 为了全站范围内使用设置为网站域名
| 'cookie_path'   = 通常是 '/'
| 'cookie_secure' = 是否通过安全的 HTTPS 连接来传输 cookie
|
*/
$config['cookie_prefix']	= "";
$config['cookie_domain']	= "";
$config['cookie_path']		= "/";
$config['cookie_secure']	= FALSE;

/*
|--------------------------------------------------------------------------
| 全局跨站脚本(XSS)过滤
|--------------------------------------------------------------------------
|
| 确定是否总是在 GET, POST 或 COOKIE 数据中进行 XSS 过滤
|
*/
$config['global_xss_filtering'] = TRUE;

/*
|--------------------------------------------------------------------------
| 跨站请求伪造(CSRF)
|--------------------------------------------------------------------------
| 是否启用 CSRF cookie 令牌
| 当设置为 TRUE 时, 令牌将在提交的表单中选中
| 如果接受用户数据, 强烈推荐启用 CSRF 保护
|
| 'csrf_token_name' = 令牌名
| 'csrf_cookie_name' = cookie 名
| 'csrf_expire' = 以秒为单位的令牌过期时间
*/
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_test_name';
$config['csrf_cookie_name'] = 'csrf_cookie_name';
$config['csrf_expire'] = 7200;

/*
|--------------------------------------------------------------------------
| 输出压缩
|--------------------------------------------------------------------------
|
| 为了更快的加载页面启用 Gzip 输出压缩
| 当启用时, 输出类将测试服务器是否支持 Gzip
| 即使这样做, 但是, 并不是所有的浏览器都支持压缩
| 所以仅仅当有理由相信访问者可以处理它时启用
|
| 非常重要:  如果对空白页启用了压缩, 就意味着过早地输出一些东西到浏览器
| It could even be a line of whitespace at the end of one of your scripts.  For
| compression to work, nothing can be sent before the output buffer is called
| by the output class.  Do not 'echo' any values with compression enabled.
|
*/
$config['compress_output'] = FALSE;

/*
|--------------------------------------------------------------------------
| 主参考时间
|--------------------------------------------------------------------------
|
| 选项是 'local' 或 'gmt'
| 此项决定函数 'now' 使用服务器本地时间还是将其转换为 GMT
|
*/
$config['time_reference'] = 'local';


/*
|--------------------------------------------------------------------------
| 重写 PHP 短标记
|--------------------------------------------------------------------------
|
| If your PHP installation does not have short tag support enabled CI
| can rewrite the tags on-the-fly, enabling you to utilize that syntax in your view files.  
| 选项是 TRUE 或 FALSE (boolean)
|
*/
$config['rewrite_short_tags'] = FALSE;


/*
|--------------------------------------------------------------------------
| Reverse Proxy IPs
|--------------------------------------------------------------------------
|
| If your server is behind a reverse proxy, you must whitelist the proxy IP
| addresses from which CodeIgniter should trust the HTTP_X_FORWARDED_FOR
| header in order to properly identify the visitor's IP address.
| Comma-delimited, e.g. '10.0.1.200,10.0.1.201'
|
*/
$config['proxy_ips'] = '';


/* End of file config.php */
/* Location: ./application/config/config.php */
