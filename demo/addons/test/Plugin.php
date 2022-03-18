<?php
namespace addons\test;	// 注意命名空间规范

use think\Addons;

/**
 * 插件测试
 * @author byron sampson
 */
class Plugin extends Addons	// 需继承think\Addons类
{
    // 该插件的基础信息
    public $info = [
        'name'  =>  'test', //  插件标识 
        'title' =>  '插件测试', //  插件名称
        'description'   =>  'ThinkPHP6插件测试',    //  插件简介
        'status'    =>  0,  //  插件运行状态，0 禁用，1 启用
        'source'    =>  0,  //  插件来源，0 本地插件，  1 在线插件
        'author'    =>  'DreamLee',     //  插件作者
        'version'   =>  '0.1'           //  插件版本
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        //  return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        //  return true;
    }

    /**
     * 实现的testhook钩子方法
     * @param $param 钩子被调用的前端传递给钩子的参数
     * @return mixed
     */
    public function testhook($param)
    {
		// 调用钩子时候的参数信息
        print_r($param);
		// 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        print_r($this->getConfig());
		// 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('info');
    }

}