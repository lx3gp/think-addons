<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
	// 模板路径
	'view_path'     =>	root_path() . "view" . DIRECTORY_SEPARATOR . \think\facade\App::initialize()->http->getName() . DIRECTORY_SEPARATOR,

    // 模板后缀
	'view_suffix'   => 'html',
	
	//	自定义标签导入
	//'taglib_pre_load'		=>	'Cx,app\common\taglib\Ex', 

	// 模板资源路径替换
	'tpl_replace_string'	=>	[
		//'__STATIC__'=>'/static/'. \think\facade\App::initialize()->http->getName(),
		'__STATIC__'=> DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR,
	],
];