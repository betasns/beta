<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| 钩子
| -------------------------------------------------------------------------
| 这个文件定义钩子, 使得在不修改系统核心文件的情况下扩展系统
|
*/

/*
 * 系统执行的早期调用
 * 仅仅在 benchmark 和 hooks 类加载完毕的时候
 * 没有执行路由或者其它的过程
 */
$hook['pre_system'] = array(
		'class'    => 'Hook_default',
		'function' => 'pre_system',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);

/*
 * 此函数可以取代 output 类中的 _display_cache() 函数
 * 这可以让你使用自己的缓存显示方法
 */
$hook['cache_override'] = array(
		'class'    => 'Hook_default',
		'function' => 'cache_override',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);

/*
 * 在调用任何控制器之前调用
 * 此时所用的基础类, 路由选择和安全性检查都已完成
 */
$hook['pre_controller'] = array(
		'class'    => 'Hook_default',
		'function' => 'pre_controller',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);

/*
 * 在控制器实例化之后, 任何方法调用之前调用
 */
$hook['post_controller_constructor'] = array(
		'class'    => 'Hook_default',
		'function' => 'post_controller_constructor',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);

/*
 * 在控制器完全运行之后调用
 */
$hook['post_controller'] = array(
		'class'    => 'Hook_default',
		'function' => 'post_controller',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);

/*
 * 覆盖 _display() 函数, 用来在系统执行末尾向 web 浏览器发送最终页面
 * 这允许你用自己的方法来显示
 * 注意: 需要通过 $this->CI =& get_instance() 引用 CI 超级对象, 然后这样的最终数据可以通过调用 $this->CI->output->get_output() 来获得
 */
$hook['display_override'] = array(
		'class'    => 'Hook_default',
		'function' => 'display_override',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);

/*
 * 在最终着色页面发送到浏览器之后, 浏览器接收完最终数据的系统执行末尾调用
 */
$hook['post_system'] = array(
		'class'    => 'Hook_default',
		'function' => 'post_system',
		'filename' => 'hook_default.php',
		'filepath' => 'hooks',
);