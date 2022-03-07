<?php
namespace addons\advert;	// 注意命名空间规范

use think\Addons;

/**
 * 广告插件
 * @author byron sampson
 */
class Plugin extends Addons	// 需继承think\Addons类
{
    // 该插件的基础信息
    public $info = [
        'name' => 'advert',	// 插件标识
        'title' => '广告插件',	// 插件名称
        'description' => 'thinkph6广告插件测试',	// 插件简介
        'status' => 0,	// 状态
        'author' => 'dreamlee',
        'version' => '0.1'
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        //  重构配置文件
        $config = $this->info;
        $config['menu'] = [];
        return create_config($config);
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        //  组装参数
        $addons_path = root_path() . config('route.pathinfo_depr') . "addons" . config('route.pathinfo_depr');
        $config_file = $addons_path . $this->getName() . config('route.pathinfo_depr') . config('addons.conf_flag') . config('addons.conf_suffix');
        if(!file_exists($config_file)){
            return true;
        }
        if(!@unlink($config_file)){
            return false;
        }
        return true;
    }

    /**
     * 实现的adverthook钩子方法
     * @param $param 钩子被调用的前端传递给钩子的参数
     * @return mixed
     */
    public function adverthook($param)
    {
		// 调用钩子时候的参数信息
        print_r($param);
		// 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        print_r($this->getConfig());
		// 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('list');
    }

}