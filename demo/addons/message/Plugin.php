<?php
namespace addons\message;	// 注意命名空间规范

use think\Addons;
use think\facade\Db;

/**
 * 留言插件
 * @author by Dreamlee
 */
class Plugin extends Addons	// 需继承think\Addons类
{
    // 该插件的基础信息
    public $info = [
        'name' => 'message',	// 插件标识
        'title' => '留言插件',	// 插件名称
        'description' => 'thinkph6留言插件',	// 插件简介
        'status' => 1,	//插件运行状态，0 禁用，1 启用
        'author' => 'by Dreamlee',
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
        $config['menu'] = [ //  建议结合auth权限中的role来设置项，同时的记得增加一项：position，用来表示显示位置
            [
                'name'  =>  '留言管理',
                'url'   =>  addons_url('message://Index/index'),
                'type'  =>  0,  //  0 菜单， 1 按钮
                'position'  =>  0,  //  0 用户后台，1 管理员后台
            ],
        ];
        //  创建数据表  当前文件所在目录的上级目录dirname(dirname(__FILE__))
        try{
            $sql_content = file_get_contents(dirname(__FILE__) . config('route.pathinfo_depr') . 'data/install.sql');
            $sql_content = array_values(array_filter(explode(';', $sql_content)));
            $sql_status = true;
            foreach ($sql_content as $sql) {
                $_step_status = true;
                if(Db::execute($sql) !== false) {
                    $_step_status = true;
                }
                $sql_status = $_step_status && $sql_status;
            }
            //  判断是否安装失败
            if( $sql_status === false ) {
                return false;
            }

            //  返回配置文件写入的状态
            return create_config($config);
        }catch(\Exception $e){
            return false;
        }
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        //  删除数据表
        $sql = "DROP TABLE IF EXISTS `tp_message`";
        $sql_status = Db::execute($sql);
        if($sql_status === false) {
            return false;
        }

        //  删除标识文件
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
     * 实现的messagehook钩子方法
     * @param $param 钩子被调用的前端传递给钩子的参数
     * @return mixed
     */
    public function messagehook($param)
    {
		// 调用钩子时候的参数信息
        print_r($param);
		// 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        print_r($this->getConfig());
		// 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('info');
    }

    /**
     * 实现的messagelisthook钩子方法
     * @param $param 钩子被调用的前端传递给钩子的参数
     * @return mixed
     */
    public function messagelisthook($param)
    {
		// 调用钩子时候的参数信息
        print_r($param);
		// 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        print_r($this->getConfig());
		// 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('info');
    }


    /**
     * 数据库信息
     */

    // id,name,phone,content,create_time,update_time,touid,
}