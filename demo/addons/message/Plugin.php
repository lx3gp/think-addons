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
        'name'  =>  'message', //  插件标识 
        'title' =>  '留言插件', //  插件名称
        'description'   =>  'ThinkPHP6留言插件',    //  插件简介
        'status'    =>  0,  //  插件运行状态，0 禁用，1 启用
        'source'    =>  0,  //  插件来源，0 本地插件，  1 在线插件
        'author'    =>  'DreamLee',     //  插件作者
        'version'   =>  '0.1'           //  插件版本
    ];

    /**
     * 插件安装方法
     * @return array
     */
    public function install(string $name= null)
    {
        if (!$name) {
            return [
                'code'  =>  400,
                'msg'   =>  '请指定插件名称~',
                'data'  =>  'database',
            ];
        }
        //  创建数据表  当前文件所在目录的上级目录dirname(dirname(__FILE__))
        try{
            if(!create_table($name)){
                return [
                    'code'  =>  400,
                    'msg'   =>  '数据库安装失败~',
                    'data'  =>  'database',
                ];
            }
        }catch(\Exception $e){
            return [
                'code'  =>  400,
                'msg'   =>  '数据库安装失败~',
                'data'  =>  'database',
            ];
        }

        //  安装标识文件    -   配置文件
        try{
            //  重构插件文件
            $info = get_addons_info($name);
            $info['isInstall'] = 1;
            //  返回配置文件写入的状态
            if(!set_addons_info($name, $info)){
                return [
                    'code'  =>  400,
                    'msg'   =>  '标识文件写入失败~',
                    'data'  =>  'ini',
                ];
            }
        }catch(\Exception $e){
            return [
                'code'  =>  400,
                'msg'   =>  $e->getMessage(),
                'data'  =>  'ini',
            ];
        }
        return [
            'code'  =>  200,
            'msg'   =>  '标识文件安装成功~',
            'data'  =>  [],
        ];
    }

    /**
     * 插件卸载方法
     * @return array
     */
    public function uninstall(string $name= null)
    {
        if (!$name) {
            return [
                'code'  =>  400,
                'msg'   =>  '请指定插件名称~',
                'data'  =>  'database',
            ];
        }
        if (!drop_table($name)) {   //  获取message插件所有的数据表，主要是根据安装文件来获取的
            return [
                'code'  =>  400,
                'msg'   =>  '数据库删除失败~',
                'data'  =>  'database',
            ];
        }
        //  删除数据表
        try{
            if (!drop_table($name)) {   //  获取message插件所有的数据表，主要是根据安装文件来获取的
                return [
                    'code'  =>  400,
                    'msg'   =>  '数据库删除失败~',
                    'data'  =>  'database',
                ];
            }
        }catch(\Exception $e){
            return [
                'code'  =>  400,
                'msg'   =>  '数据库删除失败~',
                'data'  =>  'database',
            ];
        }
        //  删除配置文件    -   配置文件
        try{
            if (!remove_config($name)) {
                return [
                    'code'  =>  400,
                    'msg'   =>  '标识文件删除失败~',
                    'data'  =>  'config',
                ];
            }
            //  调整插件信息
            $info = get_addons_info($name);
            $info['isInstall'] = 0;
            //  返回配置文件写入的状态
            if(!set_addons_info($name, $info)){
                return [
                    'code'  =>  400,
                    'msg'   =>  '插件信息写入失败~',
                    'data'  =>  'ini',
                ];
            }
        }catch(\Exception $e){
            return [
                'code'  =>  400,
                'msg'   =>  '配置文件删除失败~',
                'data'  =>  'config',
            ];
        }
        return [
            'code'  =>  200,
            'msg'   =>  '数据库、配置文件、基础信息删除成功~',
            'data'  =>  [],
        ];
    }

    /**
     * 实现的messageHook钩子方法
     * @param $param 钩子被调用的前端传递给钩子的参数
     * @return mixed
     */
    public function messageHook($param)
    {
		// 调用钩子时候的参数信息
        print_r($param);
		// 当前插件的配置信息，配置信息存在当前目录的config.php文件中，见下方
        print_r($this->getConfig());
		// 可以返回模板，模板文件默认读取的为插件目录中的文件。模板名不能为空！
        return $this->fetch('info');
    }

    /**
     * 实现的messageListHook钩子方法
     * @param $param 钩子被调用的前端传递给钩子的参数
     * @return mixed
     */
    public function messageListHook($param)
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


}