<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\BaseController;
use think\Request;
use think\facade\Db;
use think\Exception\ValidateException;

class Addons extends BaseController
{
    /**
     * 显示资源列表 -   插件列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return view('', ['addons'=>get_addons_list()]);
    }

    /**
     * 安装插件
     *
     * @return void
     */
    public function install($name){
        //  安装数据库      +   配置文件
        $result = get_addons_instance($name)->install($name);

        if($result['code'] == 400){
            return install_and_uninstall_progress_rollback($name, "install", $result['data']);
        }
        //  处理静态资源文件-挪出去
        try{
            if (move_dirs($name)['code'] == 400) {
                return install_and_uninstall_progress_rollback($name, "install", $result['data']);
            }
        }catch(\ErrorException $e){ //  发生错误    -   回滚
            return install_and_uninstall_progress_rollback($name, "install", $result['data']);      
        }
        return [
            'code'  =>  200,
            'msg'   =>  '插件'.$name.'安装成功',
            'data'  =>  [],
        ];
    }

    public function uninstall($name){
        //  获取配置信息
        $info = get_addons_instance($name)->getInfo();
        if($info['status']){
            return [
                "code"  =>  400,
                "msg"   =>  "请先暂停插件{$name}再进行插件卸载",
                "data"  =>  [],
            ];
        }

        //  备份数据表文件  +   配置文件
        if(!rollback_database_backup($name) || !rollback_backup_config_file($name)){
            return [
                "code"  =>  400,
                "msg"   =>  "插件{$name}数据/配置文件备份失败",
                "data"  =>  [],
            ];
        }
        //  卸载数据库  +   配置文件
        $result = get_addons_instance($name)->uninstall($name);
        if($result['code'] == 400) {
            return install_and_uninstall_progress_rollback($name, "uninstall", $result['data']);
        }
        //  处理静态资源文件-挪回来
        try{
            if(!move_dirs($name, 0)){
                return install_and_uninstall_progress_rollback($name, "uninstall", $result['data']);
            }
        }catch(\ErrorException $e){
            return install_and_uninstall_progress_rollback($name, "uninstall", $result['data']);
        }
        //  删除备份的配置文件
        if(!clear_backup_files($name)){
            return [
                'code'  =>  400,
                'msg'   =>  '插件'.$name.'的卸载备份文件清理失败，请手动清除~',
                'data'  =>  [],
            ];
        }
        return [
            'code'  =>  200,
            'msg'   =>  '插件'.$name.'卸载成功',
            'data'  =>  [],
        ];
    }

    //  离线安装插件包
    public function offlineInstall() {
        $file = request()->file('addons');
        try{
            // 验证文件格式
            validate(['file'=>['fileExt' => 'zip','fileMime' => 'application/zip']])->check(['file' => $file]);
            if($file){
                //$fileInfo = $file->getInfo();
                //halt($file);
                //halt($file->getPathname());     //  获取文件路径          C:\Users\DreamLee\AppData\Local\Temp\phpD4D.tmp
                //halt($file->getOriginalName());     //  获取文件原始名      下载地址.zip
                //halt($file->getOriginalExtension());     //  获取文件扩展名      zip
                $originalPathname = $file->getPathname();
                $name = str_replace('.'.$file->getOriginalExtension(),'',$file->getOriginalName());
                $result = $this->unzip($originalPathname);
                if(!$result) {
                    return [
                        'code'  =>  400,
                        'msg'   =>  '插件'.$name.'解压失败~',
                        'data'  =>  [],
                    ];
                }
                //  开始执行安装
                $result = $this->install($name);
                if($result['code'] == 400) {
                    return [
                        'code'  =>  400,
                        'msg'   =>  '插件'.$name.'安装失败~',
                        'data'  =>  [],
                    ];
                }
            }
        }catch(ValidateException $e){
            return [
                'code'  =>  400,
                'msg'   =>  '插件'.$name.'安装失败~',
                'data'  =>  [],
            ];
        }
        return [
            'code'  =>  200,
            'msg'   =>  '插件'.$name.'安装成功',
            'data'  =>  [],
        ];
    }

    //  解压文件
    /**
     * unzip function    解压文件
     *
     * @param [type] $originalPathname  解压路径，这里选择的是文件上传的临时路径
     * @param [type] $fileName  文件名称，这里是不带任何后缀的文件名称
     * @return void
     */
    protected function unzip($originalPathname){
        // 实例化对象
        $zip = new \ZipArchive() ;
        // 定义解压路径
        $unzipPath = root_path() . "addons" .  DIRECTORY_SEPARATOR;
        //打开zip文档，如果打开失败返回提示信息
        if ($zip->open($originalPathname, \ZipArchive::CREATE) !== TRUE) {
            die ("Could not open archive");
            return false;
        }else{
            //将压缩文件解压到指定的目录下
            if(!file_exists($unzipPath)) {
                @mkdir($unzipPath, 0755, true);
            }
            //  获取压缩包中的文件数（含目录）
            $totalNums = $zip->numFiles;
            for ($i=0; $i<$totalNums; $i++) {
                $statInfo = $zip->statIndex($i);
                if ($statInfo['crc'] == 0) {
                    //  新建目录
                    @mkdir($unzipPath . DIRECTORY_SEPARATOR . substr($statInfo['name'], 0, -1));
                } else {
                    @copy('zip://'. $originalPathname . '#' . $statInfo['name'], $unzipPath . DIRECTORY_SEPARATOR . $statInfo['name']);
                }
            }
            //$zip->extractTo($unzipPath);
            //关闭zip文档
            $zip->close();
            return true;
        }
    }

    //  切换插件状态
    public function switchPluginStatus(string $name = null, int $status = 0){
        //  判断插件名称是否存在
        if(!$name) {
            return [
                'code'  =>  400,
                'msg'   =>  '请指定操作出错的插件名称~',
                'data'  =>  [],
            ];
        }
        //  获取配置信息
        $infoFilePath = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "addon.ini";
        if(!file_exists($infoFilePath)){
            return [
                'code'  =>  400,
                'msg'   =>  '插件配置文件丢失~',
                'data'  =>  [],
            ];
        }
        //  修改配置信息
        $info = get_addons_info($name);
        $info['status'] = $status;

        //  保存配置信息并返回结果
        if(set_addons_info($name, $info)){
            return [
                'code'  =>  200,
                'msg'   =>  '插件状态调整成功~',
                'data'  =>  [],
            ];
        }
        return [
            'code'  =>  400,
            'msg'   =>  '插件状态调整失败~',
            'data'  =>  [],
        ];
    }

    //  获取配置信息
    public function configList(string $name=null) {
        $configList = get_addons_instance($name)->getConfig();
        if (!empty($configList)) {
            foreach ($configList as $key => &$val) {
                if(!empty($val)) {
                    foreach ($val as $k => &$v) {
                        if(in_array($k, ['content', 'extend']) && !empty($v) && is_array($v)) {
                            $str = "";                            
                            foreach($v as $_k => $_v) {
                                $str .= "{$_k}:{$_v},";
                            }
                            $v = substr($str, 0, -1);
                        }
                    }
                }
            }
        }
        return view('', ['name'=>$name, 'configList'=>$configList]);
    }

    //  添加菜单
    public function addConfig(string $name = null) {
        if (request()->isPost()) {
            $data = array_filter(input('post.'), function ($item){
                if($item === '' || $item === null){
                    return false;
                }
                return true;
            });

            if(empty($data)){
                return [
                    'code'  =>  400,
                    'msg'   =>  '配置有效信息为空',
                    'data'  =>  [],
                ];
            }
            //  判断菜单配置是否存在
            $configAll = get_addons_config(input('get.name'));
            $config = [];
            if(isset($configAll['config'])){  //  已经有菜单
                $config = $configAll['config'];
                $config = array_merge($config, [$data]);
            }  else {
                $config[] = $data;
            }
            //  写入配置文件
            $result = set_addons_config(input('get.name'), $config, 'config');
            if($result){
                return [
                    'code'  =>  200,
                    'msg'   =>  '菜单信息写入成功',
                    'data'  =>  [],
                ];
            }
            return [
                'code'  =>  400,
                'msg'   =>  '菜单信息写入错误',
                'data'  =>  [],
            ];
        }
        $config_groups = Db::name('auth_rule')->select();
        return view('', ['configGroup'=>$config_groups, 'name'=>$name]);
    }

    //  配置插件
    public function setConfig() {
        //  替换数组
        list($name, $configKey, $configName) = array_values(input('get.'));
        $originConfig = get_addons_config($name)['config'];
        //  写入配置
        if (request()->isPost()) {
            $data = array_filter(input('post.'), function ($item){
                if($item === '' || $item === null){
                    return false;
                }
                return true;
            });

            //  将内容还原为数组
            foreach ($data as $d => &$v) {
                if (in_array($d, ['content', 'extend']) && $v && (stripos($v, ",")>=0)) {
                    $_tmpV = [];                    
                    $_v = explode(',', $v);
                    foreach ($_v as $_vv) {
                        if ($_vv && (stripos($_vv, ":")>=0)) {
                            $_vv = explode(':', $_vv);
                            $_tmpV[$_vv[0]] = $_vv[1];
                        }
                    }
                    $v = $_tmpV;                    
                }
            }
            //  需要转成数组的
            if(!empty($originConfig)) {
                foreach ($originConfig as $key => &$val) {
                    //  替换数据
                    if($configKey == $key){
                        $val = $data;
                    }
                }
            }else{
                $originConfig = $data;
            }
            //  写入配置文件
            $result = set_addons_config($name, $originConfig);
            if($result){
                return [
                    'code'  =>  200,
                    'msg'   =>  '配置信息写入成功',
                    'data'  =>  [],
                ];
            }
            return [
                'code'  =>  400,
                'msg'   =>  '配置信息写入错误',
                'data'  =>  [],
            ];
        }
        //  获取配置
        if(isset($originConfig[$configKey])){
            foreach ($originConfig[$configKey] as $key => &$val) {
                //  这里扩展content|extend属性     
                if(in_array($key, ['content', 'extend']) && is_array($val)) {
                    $str = "";
                    foreach ($val as $k => $v) {
                        $str .= "{$k}:{$v},";
                    }
                    $val = substr($str, 0, -1);
                }
            }
        }
        return view('', ['config'=>$originConfig[$configKey], 'configKey'=>$configKey, 'name'=>$name, 'configName'=>$configName]);
    }

    //  删除配置
    public function deleteConfig () {
        //  替换数组
        list($name, $configKey, $configName) = array_values(input('get.'));
        $originConfig = get_addons_config($name)['config'];
        if(!empty($originConfig) && isset($originConfig[$configKey])){
            unset($originConfig[$configKey]);
            $originConfig = array_values($originConfig);
        }
        //  写入配置文件
        $result = set_addons_config($name, $originConfig, 'config');
        if($result){
            return [
                'code'  =>  200,
                'msg'   =>  '配置信息写入成功',
                'data'  =>  [],
            ];
        }
        return [
            'code'  =>  400,
            'msg'   =>  '配置信息写入错误',
            'data'  =>  [],
        ];
    }


    //  获取菜单列表
    public function menuList(string $name=null){
        $menuList = get_addons_instance($name)->getMenu();
        return view('', ['name'=>$name, 'menuList'=>$menuList]);
    }

    //  添加菜单
    public function addMenu (string $name = null) {
        if (request()->isPost()) {
            $data = array_filter(input('post.'), function ($item){
                if($item === '' || $item === null){
                    return false;
                }
                return true;
            });

            if(empty($data)){
                return [
                    'code'  =>  400,
                    'msg'   =>  '菜单有效信息为空',
                    'data'  =>  [],
                ];
            }
            //  判断菜单配置是否存在
            $configAll = get_addons_menu(input('get.name'));
            $menus = [];
            if(!empty($configAll)){  //  已经有菜单
                $menus = array_merge($configAll, [$data]);
            }  else {
                $menus[] = $data;
            }
            //  写入配置文件
            $result = set_addons_menu(input('get.name'), $menus);
            if($result){
                return [
                    'code'  =>  200,
                    'msg'   =>  '菜单信息写入成功',
                    'data'  =>  [],
                ];
            }
            return [
                'code'  =>  400,
                'msg'   =>  '菜单信息写入错误',
                'data'  =>  [],
            ];
        }
        $authNodes = Db::name('auth_rule')->select();
        return view('', ['authNodes'=>$authNodes, 'name'=>$name]);
    }

    //  设置菜单
    public function setMenu() {
        //  替换数组
        list($name, $menuKey, $menuName) = array_values(input('get.'));
        $originConfig = get_addons_menu($name);
        //  写入配置
        if (request()->isPost()) {
            $data = array_filter(input('post.'), function ($item){
                if($item === '' || $item === null){
                    return false;
                }
                return true;
            });
            //  将内容还原为数组
            foreach ($data as $d => &$v) {
                if (in_array($d, ['content', 'extend']) && $v && (stripos($v, ",")>=0)) {
                    $_tmpV = [];                    
                    $_v = explode(',', $v);
                    foreach ($_v as $_vv) {
                        if ($_vv && (stripos($_vv, ":")>=0)) {
                            $_vv = explode(':', $_vv);
                            $_tmpV[$_vv[0]] = $_vv[1];
                        }
                    }
                    $v = $_tmpV;                    
                }
            }
            //  需要转成数组的
            if(!empty($originConfig)) {
                foreach ($originConfig as $key => &$val) {
                    //  替换数据
                    if($menuKey == $key){
                        $val = $data;
                    }
                }
            }else{
                $originConfig = $data;
            }
            //  写入配置文件
            $result = set_addons_menu($name, $originConfig);
            if($result){
                return [
                    'code'  =>  200,
                    'msg'   =>  '配置信息写入成功',
                    'data'  =>  [],
                ];
            }
            return [
                'code'  =>  400,
                'msg'   =>  '配置信息写入错误',
                'data'  =>  [],
            ];
        }
        //  获取配置
        if(isset($originConfig[$menuKey])){
            foreach ($originConfig[$menuKey] as $key => &$val) {
                //  这里扩展content|extend属性     
                if(in_array($key, ['content', 'extend']) && is_array($val)) {
                    $str = "";
                    foreach ($val as $k => $v) {
                        $str .= "{$k}:{$v},";
                    }
                    $val = substr($str, 0, -1);
                }
            }
        }
        $authNodes = Db::name('auth_rule')->select();
        return view('', ['menu'=>$originConfig[$menuKey], 'authNodes'=>$authNodes, 'menuKey'=>$menuKey, 'name'=>$name, 'menuName'=>$menuName]);
    }

    //  删除菜单
    public function deleteMenu () {
        //  替换数组
        list($name, $configKey, $configName) = array_values(input('get.'));
        $originMenu = get_addons_config($name)['menu'];
        if(!empty($originMenu) && isset($originMenu[$configKey])){
            unset($originMenu[$configKey]);
            $originMenu = array_values($originMenu);
        }
        //  写入配置文件
        $result = set_addons_config($name, $originMenu, 'menu');
        if($result){
            return [
                'code'  =>  200,
                'msg'   =>  '菜单信息更新成功',
                'data'  =>  [],
            ];
        }
        return [
            'code'  =>  400,
            'msg'   =>  '菜单信息更新错误',
            'data'  =>  [],
        ];
    }


    //  获取应用信息
    public function getInfo(string $name=null){
        $info = get_addons_instance($name)->getInfo();
        return view('', ['info'=>$info]);
    }

    //  配置应用信息
    public function setInfo(string $name=null){
        //  设置应用信息
        if (request()->isPost()) {
            $data = array_filter(input('post.'), function ($item){
                if($item === '' || $item === null){
                    return false;
                }
                return true;
            });
            $result = get_addons_instance($name)->setInfo($data);
            if($result){
                return [
                    'code'  =>  200,
                    'msg'   =>  '应用信息写入成功',
                    'data'  =>  [],
                ];
            }
            return [
                'code'  =>  400,
                'msg'   =>  '应用信息写入错误',
                'data'  =>  [],
            ];
        }
        //  获取应用信息
        $info = get_addons_instance($name)->getInfo();
        return view('', ['info'=>$info]);
    }


    //  获取提示列表信息
    public function tipsList(string $name=null) {
        $tipsList = get_addons_instance($name)->getTips();
        return view('', ['name'=>$name, 'tipsList'=>$tipsList]);
    }

    //  获取提示信息
    public function getTips(string $name=null) {





    }

}
