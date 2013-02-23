<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 目录辅助函数
 *
 */

// ------------------------------------------------------------------------

/**
 * 创建目录的文件数组
 *
 * 读取指定的目录并建立它的数组表示, 如果目录含有子文件夹也将被列出
 * 可以使用第二个参数(整数)来控制递归的深度
 * 如果深度为 1, 则只列出根目录
 * 默认情况下, 返回的数组中不会包括那些隐藏文件
 * 为了覆盖此行为, 可以设置第三个参数为 true (boolean)
 * 每一个文件夹的名字都将作为数组的索引, 文件夹所包含的文件将以数字作为索引
 *
 * @access	public
 * @param	string
 * @param	int		遍历目录的深度 (0 = 完全递归, 1 = 当前目录, 等)
 * @return	array
 */
if ( ! function_exists('directory_map')){
	function directory_map($source_dir, $directory_depth = 0, $hidden = FALSE){
		if ($fp = @opendir($source_dir)){
			$filedata	= array();
			$new_depth	= $directory_depth - 1;
			$source_dir	= rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

			while (FALSE !== ($file = readdir($fp))){
				// 删除 '.', '..' 和隐藏文件 [可选]
				if ( ! trim($file, '.') OR ($hidden == FALSE && $file[0] == '.')){
					continue;
				}

				if (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir.$file)){
					$filedata[$file] = directory_map($source_dir.$file.DIRECTORY_SEPARATOR, $new_depth, $hidden);
				}else {
					$filedata[] = $file;
				}
			}

			closedir($fp);
			return $filedata;
		}

		return FALSE;
	}
}