<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [ThinkPHP6]
 * +----------------------------------------------------------------------
 * | FILE: helper.php
 * | AUTHOR: DreamLee
 * | EMAIL: 1755773846@qq.com
 * | QQ: 1755773846
 * | DATETIME: 2022/03/07 14:47
 * |-----------------------------------------
 * | 不积跬步,无以至千里；不积小流，无以成江海！
 * +----------------------------------------------------------------------
 * | Copyright (c) 2022 DreamLee All rights reserved.
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);


use think\facade\Db;
use think\facade\Event;
use think\facade\Route;
use think\helper\{
    Str, Arr
};

\think\Console::starting(function (\think\Console $console) {
    $console->addCommands([
        'addons:config' => '\\think\\addons\\command\\SendConfig'
    ]);
});

// 插件类库自动载入
spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');
    $dir = app()->getRootPath();
    $namespace = 'addons';
    if (strpos($class, $namespace) === 0) {
        $class = substr($class, strlen($namespace));
        $path = '';
        if (($pos = strripos($class, '\\')) !== false) {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }
        $path .= str_replace('_', '/', $class) . '.php';
        $dir .= $namespace . $path;
        if (file_exists($dir)) {
            include $dir;
            return true;
        }
        return false;
    }
    return false;
});

if (!function_exists('hook')) {
    /**
     * 处理插件钩子
     * @param string $event 钩子名称
     * @param array|null $params 传入参数
     * @param bool $once 是否只返回一个结果
     * @return mixed
     */
    function hook($event, $params = null, bool $once = false)
    {
        $result = Event::trigger($event, $params, $once);

        return join('', $result);
    }
}

if (!function_exists('check_addons_info')) {
    /**
     * 检查配置信息是否完整
     * @return bool
     */
    function check_addons_info(array $addon_info=null)
    {
        $info_check_keys = ['name', 'title', 'description', 'status', 'source', 'isInstall', 'author',  'version'];
        foreach ($info_check_keys as $value) {
            if (!$addon_info || !array_key_exists($value, $addon_info)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('get_addons_info')) {
    /**
     * 读取插件的基础信息
     * @param string $name 插件名
     * @return array
     */
    function get_addons_info($name)
    {
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }
        return $addon->getInfo();
    }
}

if (!function_exists('get_addons_instance')) {
    /**
     * 获取插件的单例
     * @param string $name 插件名
     * @return mixed|null
     */
    function get_addons_instance($name)
    {
        static $_addons = [];
        if (isset($_addons[$name])) {
            return $_addons[$name];
        }
        $class = get_addons_class($name);
        if (class_exists($class)) {
            $_addons[$name] = new $class(app());
            return $_addons[$name];
        } else {
            return null;
        }
    }
}

if (!function_exists('get_addons_class')) {
    /**
     * 获取插件类的类名
     * @param string $name 插件名
     * @param string $type 返回命名空间类型
     * @param string $class 当前类名
     * @return string
     */
    function get_addons_class($name, $type = 'hook', $class = null)
    {
        $name = trim($name);
        // 处理多级控制器情况
        if (!is_null($class) && strpos($class, '.')) {
            $class = explode('.', $class);

            $class[count($class) - 1] = Str::studly(end($class));
            $class = implode('\\', $class);
        } else {
            $class = Str::studly(is_null($class) ? $name : $class);
        }
        switch ($type) {
            case 'controller':
                $namespace = '\\addons\\' . $name . '\\controller\\' . $class;
                break;
            default:
                $namespace = '\\addons\\' . $name . '\\Plugin';
        }
        return class_exists($namespace) ? $namespace : '';
    }
}

if (!function_exists('get_addons_list')) {
    /**
     * 获取所有插件列表
     * @return array|null
     */
    function get_addons_list() {
        $addonsPath = root_path() . "addons";
        $addons = scandir($addonsPath);
        $_addons_list= [];
        foreach($addons as $name){
            if (is_dir($addonsPath . DIRECTORY_SEPARATOR . $name)) {
                //  判断插件的有效性
                //  获取单例插件的插件详情, 当结果为null时，表示不是插件
                if(!get_addons_instance($name)) {
                    continue;
                }
                //  获取插件信息以及配置信息
                $addon_info = get_addons_instance($name)->getInfo();
                //  判断单例插件的参数是否完整
                if (empty($addon_info) || !check_addons_info($addon_info)) {
                    continue;
                }
                $_addons_list[] = $addon_info;
            }
        }
        return array_values($_addons_list);
    }
}

if (!function_exists('addons_url')) {
    /**
     * 插件显示内容里生成访问插件的url
     * @param $url
     * @param array $param
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return bool|string
     */
    function addons_url($url = '', $param = [], $suffix = true, $domain = false)
    {
        $request = app('request');
        if (empty($url)) {
            // 生成 url 模板变量
            $addons = $request->addon;
            $controller = $request->controller();
            $controller = str_replace('/', '.', $controller);
            $action = $request->action();
        } else {
            $url = Str::studly($url);
            $url = parse_url($url);
            if (isset($url['scheme'])) {
                $addons = strtolower($url['scheme']);
                $controller = $url['host'];
                $action = trim($url['path'], '/');
            } else {
                $route = explode('/', $url['path']);
                $addons = $request->addon;
                $action = array_pop($route);
                $controller = array_pop($route) ?: $request->controller();
            }
            $controller = Str::snake((string)$controller);

            /* 解析URL带的参数 */
            if (isset($url['query'])) {
                parse_str($url['query'], $query);
                $param = array_merge($query, $param);
            }
        }
        return Route::buildUrl("@addons/{$addons}/{$controller}/{$action}", $param)->suffix($suffix)->domain($domain);
    }
}


if(!function_exists('get_addons_config')){
    /**
     * 获取插件类的配置值值
     * @param string $name 插件名
     * @return array
     */
    function get_addons_config($name)
    {
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }
        return $addon->getConfig($name);
    }
}



if (!function_exists('is_really_writable')) {
    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5((string) mt_rand(1000, 9999));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('install_and_uninstall_progress_rollback')) {
    function install_and_uninstall_progress_rollback(string $name=null, string $type="install", string $position = "database") {
        //  安装流程
        /**
        *   1、安装数据表
        *   2、卸载配置文件
        *   3、调整插件文件
        *   4、配置资源
        **/
        //  判定是否指定插件名称
        if( !$name ) { return false; }
        switch ($position)
        {
            case "database":    //  出错的位置节点，操作对象为数据表
                if($type=="install") {
                    //  获取已经安装的数据表，并删除数据表
                   return drop_table($name);
                }else{
                    //  获取已经安装的数据表文件，并恢复数据表
                    return rollback_database_restore($name);
                }
            break;
            case "config":  //  出错的位置节点，操作对象为数据表、配置文件
                if($type=="install") {
                    //  获取已经安装的数据表，并删除数据表
                    if (!drop_table($name) || !remove_config($name)) {
                        return false;
                    }
                }else{
                    //  获取已经安装的数据表文件，并恢复数据表、回复配置文件
                    if (!rollback_database_restore($name) || !rollback_restore_config_file($name)) {
                        return false;
                    }
                }
            break;
            case "ini":  //  出错的位置节点，操作对象为数据表、配置文件
                if($type=="install") {
                    //  获取已经安装的数据表，并删除数据表
                    $ini = get_addons_info($name);
                    $ini['isInstall'] = 0;
                    if (!drop_table($name) || !set_addons_info($name, $ini)) {
                        return false;
                    }
                }else{
                    //  获取已经安装的数据表文件，并恢复数据表、回复配置文件
                    //  获取已经安装的数据表，并删除数据表
                    $ini = get_addons_info($name);
                    $ini['isInstall'] = 0;
                    if (!rollback_database_restore($name) || !rollback_restore_config_file($name) || !set_addons_info($name, $ini)) {
                        return false;
                    }
                }
            break;
            case "source":    //  出错的位置节点，操作对象为数据表、配置文件、资源
                //  获取已经安装的数据表，并删除数据表
                $ini = get_addons_info($name);
                if($type=="install") {
                    //  获取已经安装的数据表，并删除数据表，调整插件基础信息
                    $ini['isInstall'] = 0;
                    if (!drop_table($name) || !remove_config($name) || !remove_source($name) || !set_addons_info($name, $ini)) {
                        return false;
                    }
                }else{
                    //  获取已经安装的数据表文件，并恢复数据表、回复配置文件、恢复资源文件
                    $ini['isInstall'] = 1;
                    if (!rollback_database_restore($name) || !rollback_restore_config_file($name) || !set_addons_info($name, $ini) || !move_dirs($name)) {
                        return false;
                    }
                }
            break;
            default:
        }
        return true;
    }
}

//  删除资源
if (!function_exists("remove_source")) {
    function remove_source (string $name = null){
        //  判定是否指定插件名称
        if(!$name) { return false; }
        //  先定义需要删除的文件夹位置
        $dir = public_path() . "static" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $name;
        if(is_dir($dir) && !file_exists($dir)) {
            return true;
        }
        //先删除目录下的文件：
        $dh=opendir($dir);
        while ($file=readdir($dh)) {
            if($file != "." && $file != "..") {
                $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
                if(!is_dir($fullPath)) {
                    unlink($fullPath);
                } else {
                    remove_source($fullPath);
                }
            }
        }
        closedir($dh);
        //删除当前文件夹：
        if(!rmdir($dir)) {
            return false;
        }
        return true;
    }
}


if(!function_exists('move_dirs')){
    /**
     * 移动文件/目录
     * @param string $name   源地址
     * @param int $type 类型：1 为安装移动到公共资源目录， 0 为卸载移动到插件原始目录
     * @return array   
     */
    function move_dirs(string $name = null, int $type = 1){
        //  判断插件名称是否存在
        if(!$name) { return false; }
        if($type){
            $srcFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "static" . DIRECTORY_SEPARATOR . $name;
            $targetFileName = public_path() . "static" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $name;
        }else{
            $srcFileName = public_path() . "static" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $name;
            $targetFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "static" . DIRECTORY_SEPARATOR . $name;
        }
        if(!file_exists($srcFileName)){
            return false;
        }
        if(file_exists($targetFileName)){
            return false;            
        }
        if(!rename($srcFileName, $targetFileName)){
            return false;   
        }
        return true;   
    }
}

//  备份配置文件
if (!function_exists('rollback_backup_config_file')) {
    //  【当插件卸载错误时，需要还原配置文件】
    function rollback_backup_config_file (string $name=null) {
        //  判断插件名称是否存在
        if(!$name) { return false; }
        //  获取配置文件
        $srcFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . config('addons.conf_flag') . config('addons.conf_suffix');
        $targetFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "backup_" . config('addons.conf_flag') . config('addons.conf_suffix');
        if(!file_exists($srcFileName)){
            return true;
        }
        if(is_file($targetFileName) && file_exists($targetFileName)){
            @unlink($targetFileName);
        }
        if(!rename($srcFileName, $targetFileName)){
            return false;   
        }
        return true;
    }
}

//  还原配置备份文件
if (!function_exists('rollback_restore_config_file')) {
    //  【当插件卸载错误时，需要还原配置文件】
    function rollback_restore_config_file (string $name=null) {
        //  判断插件名称是否存在
        if(!$name) { return false; }
        //  获取配置文件
        $srcFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "backup_" . config('addons.conf_flag') . config('addons.conf_suffix');
        $targetFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . config('addons.conf_flag') . config('addons.conf_suffix');
        if(!file_exists($srcFileName)){
            return false;
        }
        if(is_file($targetFileName) && file_exists($targetFileName)){
            @unlink($targetFileName);
        }
        if(!rename($srcFileName, $targetFileName)){
            return false;   
        }
        return true;
    }
}



//  设置配置文件

//  设置配置文件


//  写入配置文件
if (!function_exists('create_config')) {
    /**
     * 创建插件配置文件
     * @method create_info
     * @param array|string|null $info    需要创建的参数
     * @param string $name  需要创建的配置文件名
     * @return void
     */
    function create_config(string $name=null, $config = null) {
        //  判断插件名称是否存在
        if(!$name || !$config) { return false; }
        $info_file = root_path() . "addons" . DIRECTORY_SEPARATOR . $config['name'] . DIRECTORY_SEPARATOR ."addon.ini";
        
        if(is_file($info_file) && file_exists($info_file)) {
            return false;
        }
        $config = var_export($config, true);
        $content = <<<EOF
        <?php

            return {$config};

EOF;
        $result = file_put_contents($info_file, $content);
        if($result ===false) {
            return false;
        }
        return true;
    }
}



//  删除配置文件
if (!function_exists("remove_config")) {
    /**
     * 删除插件配置文件
     * @method remove_config
     * @param string|null $name  需要创建的配置文件名
     * @param string|null $fileName  需要指定创建的配置文件名
     * @return void
     */
    function remove_config(string $name=null, string $fileName=null){
        //  判断插件名称是否存在
        if(!$name) { return false; }
        //  定义配置文件
        $fileName = $fileName ?: config('addons.conf_flag');
        $configFullFileName = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $fileName . config('addons.conf_suffix');
        if (!file_exists($configFullFileName)) {
            return true;
        }
        if(!unlink($configFullFileName)){
            return false;
        }
        return true;
    }
}

//  安装数据表
if (!function_exists("create_table")) {
    function create_table (string $name=null) {
        //  判断插件名称是否存在
        if(!$name) { return false; }
        //  创建数据表  当前文件所在目录的上级目录dirname(dirname(__FILE__))
        try{
            $sqlPath = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "install.sql";
            if(file_exists($sqlPath)){
                $sql_content = file_get_contents($sqlPath);
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
            }
        }catch(\Exception $e){
            return false;
        }
        return true;
    }
}

//  删除数据表
if (!function_exists("drop_table")) {
    function drop_table (string $name=null){
        //  判断插件名称是否存在
        if(!$name) { return false; }
        //  删除数据表
        try{
            $dataTableList = get_addons_tables($name);  //  获取message插件所有的数据表，主要是根据安装文件来获取的
            if(!empty($dataTableList)) {
                $sql = "DROP TABLE IF EXISTS ";
                foreach ($dataTableList as $dtl) {
                    $sql .= "`{$dtl}`,";
                }
                $sql = substr($sql, 0, -1) . ";";
                $sql_status = Db::execute($sql);
                if($sql_status === false) {
                    return false;
                }
            }
        }catch(\Exception $e){
            return false;
        }
        return true;
    }
}

//  备份指定数据表  -   私有方法    卸载插件时，先备份数据表，以备卸载出现错误时，还原数据表
if (!function_exists("rollback_database_backup")) {
    /**
     * @method rollback_database_backup   卸载前的数据备份
     * @param string $name  当前需要处理的插件名称
     * @param string $sqlFileName   需要保存的文件名（不要带后缀）
     * @return array    返回标准数据
     */
    function rollback_database_backup(string $name = null, string $sqlFileName = 'backup_install')
    {
        if (!$name) {
            return false;
        }
        $tablesList =  get_addons_tables($name);
        if (empty($tablesList)) {
            return true;
        }
        #  开始备份数据表   +   创建数据表信息
        $sqlStr = "";
		$sqlStr = "/*************************\r\n";
 		$sqlStr.= " * " . date("Y-m-d H:i:s" ,time()) . " \r\n";
		$sqlStr.= " ************************/\r\n";
		$sqlStr.= "SET FOREIGN_KEY_CHECKS=0;\r\n";


        #  开始备份数据表   +   连接数据库
        foreach ($tablesList as $table) {
            $sqlTableStr = "";
            try{
                if(!empty(Db::query("SHOW TABLES LIKE '$table'"))){
                    //  创建表信息  +   生成表安装语句
                    $ctable = array_values(Db::query("SHOW CREATE TABLE `$table`")[0]);
                    $sqlTableStr .= "-- ----------------------------\r\n";
                    $sqlTableStr .= "-- Table structure for `{$ctable[0]}`\r\n";
                    $sqlTableStr .= "-- ----------------------------\r\n";
                    $sqlTableStr .= "DROP TABLE IF EXISTS `{$ctable[0]}`;\r\n".$ctable[1].";\r\n\r\n";
                    #  开始备份数据表   +   生成表数据插入语句
                    if( $data = Db::query("SELECT * FROM `{$table}`") ) {
                        $sqlTableStr .= "-- ----------------------------\r\n";
                        $sqlTableStr.= "-- Records of $table \r\n";
                        $sqlTableStr.= "-- ----------------------------\r\n";
                        foreach ( $data as $val ) {
                            if( $val ) {
                                $valArr = array();
                                foreach ( $val as $k => $v ) {
                                    //对单引号和换行符进行一下转义
                                    $valArr[] = "'".str_replace( array("'","\r\n"), array("\'","\\r\\n"), $v )."'";
                                }
                                $values = implode(', ', $valArr);
                                $sqlTableStr .= "INSERT INTO `{$table}` VALUES ($values);\r\n";//省略了字段名称
                            }
                        }
                    }
                }
            } catch(\Exception $e) {
                return false;
            }
            #  结束数据表创建信息
        }
        if(!$sqlTableStr){
            return true;
        }
        #  写入数据表文件
        try {
            #   定义数据备份内容
            $sqlStr .= $sqlTableStr;
            #   定义备份数据表文件
            $filename = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $sqlFileName . ".sql";
            if ( !is_dir(dirname($filename)) ) {
                mkdir(dirname($filename) ,0755 ,true);
            }
            if ( file_exists($filename) ) {
                unlink($filename);
            }
            if (file_put_contents($filename, $sqlStr) === false) {
                return false;
            }
        }catch(\Exception $e){
            return false;
        }
        return true;
    }
}

//还原指定数据表  -   私有方法  【当插件卸载失败时，需要还原数据表】    拆分sql语句,  因为之前保存到文件中的语句都是以 ;\r\n 结尾的, 所以... 
if (!function_exists("rollback_database_restore")) {
	/**
     * @method rollback_database_restore 回滚备份文件
     * @param string|null $name  插件名称
     * @return array   返回标准数据
     */
    function rollback_database_restore (string $name = null)
	{
        //  判断插件名称是否存在
        if(!$name) {
            return false;
        }
        $filename = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . 'backup_install.sql';;
		if( !file_exists($filename) ) {
            return false;
		}
		$str = fread( $hd = fopen($filename, "rb") , filesize($filename) );
        $sqlStr = array_values(array_filter(explode(";\r\n", $str)));//所以... 这里拆分sql
        $sql_status = true;
		if(!empty($sqlStr)) {
            foreach ($sqlStr as $sql) {//   逐条执行
                $_step_status = true;
                if($sql != "\r\n" && Db::execute($sql) !== false) {
                    $_step_status = true;
                }
                $sql_status = $_step_status && $sql_status;
            }
		}
		fclose($hd);
        //  判断是否安装失败
        if( $sql_status === false ) {
            return false;
        }
        //  删除配置文件
        @unlink($filename);
        return true;
	}
}


//  清理因卸载遗留下的配置备份文件、数据库备份文件
if(!function_exists('clear_backup_files')){
    function clear_backup_files(string $name = null){
        //  判断插件名称是否存在
        if(!$name) {
            return false;
        }
        try{
            $configBackupFilePath = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "backup_" . config('addons.conf_flag') . config('addons.conf_suffix');
            if(file_exists($configBackupFilePath)){
                @unlink($configBackupFilePath);
            }            
        }catch(\Exception $e){
            return false;
        }
        try{
            $sqlBackupFilePath = root_path() . "addons" . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "backup_install.sql";
            if(file_exists($sqlBackupFilePath)){
                @unlink($sqlBackupFilePath);
            }        
        }catch(\Exception $e){
            return false;
        }
        return true;
    }
}


//  获取插件配置信息
if(!function_exists('get_addons_config')){
    /**
     * 写入配置文件
     * @param string  $name      插件名
     * @param array   $config    配置数据
     * @param boolean $writefile 是否写入配置文件
     * @return bool
     * @throws Exception
     */
    function get_addons_config(string $name = null, string $type = 'config')
    {
        if(!$name){ return false; }
        $addon = get_addons_instance($name);
        $config = $addon->getConfig($type);
        if ($config) {
            return $config; // 读取配置文件
        }
        return false;
    }
}

//  设置插件配置信息
if(!function_exists('set_addons_config')){
    /**
     * 写入配置文件
     * @param string  $name     插件名
     * @param array   $config   配置数据
     * @param boolean $type     是否写入配置文件
     * @return bool
     * @throws Exception
     */
    function set_addons_config(string $name = null, array $config = null, string $type = 'config')
    {
        if(!$name || !$config){
            return false;
        }
        $addon = get_addons_instance($name);
        if ($addon->setConfig($name, $config, $type)) {
            // 写入配置文件
            return true;
        }
        return false;
    }
}

//  获取插件菜单
if(!function_exists('get_addons_menu')){
    /**
     * 获取插件类的配置值值
     * @param string $name 插件名
     * @return array
     */
    function get_addons_menu($name)
    {
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }
        return $addon->getMenu($name);
    }
}

//  设置插件菜单
if(!function_exists('set_addons_menu')) {
    /**
     * 写入配置文件
     * @param string  $name     插件名
     * @param array   $config   配置数据
     * @param boolean $type     是否写入配置文件
     * @return bool
     * @throws Exception
     */
    function set_addons_menu(string $name = null, array $config = null)
    {
        if(!$name || !$config){
            return false;
        }
        $addon = get_addons_instance($name);
        if ($addon->setMenu($name, $config)) {
            // 写入配置文件
            return true;
        }
        return false;
    }
}








// /**
//  * 写入配置文件
//  * @param string  $name      插件名
//  * @param array   $config    配置数据
//  * @param boolean $writefile 是否写入配置文件
//  * @return bool
//  * @throws Exception
//  */
// function set_addon_config($name, $config, $writefile = true)
// {
//     $addon = get_addon_instance($name);
//     $addon->setConfig($name, $config);
//     $fullconfig = get_addon_fullconfig($name);
//     foreach ($fullconfig as $k => &$v) {
//         if (isset($config[$v['name']])) {
//             $value = $v['type'] !== 'array' && is_array($config[$v['name']]) ? implode(',', $config[$v['name']]) : $config[$v['name']];
//             $v['value'] = $value;
//         }
//     }
//     if ($writefile) {
//         // 写入配置文件
//         set_addon_fullconfig($name, $fullconfig);
//     }
//     return true;
// }

// /**
//  * 写入配置文件
//  *
//  * @param string $name  插件名
//  * @param array  $array 配置数据
//  * @return boolean
//  * @throws Exception
//  */
// function set_addon_fullconfig($name, $array)
// {
//     $file = ADDON_PATH . $name . DS . 'config.php';
//     $ret = file_put_contents($file, "<?php\n\n" . "return " . VarExporter::export($array) . ";\n", LOCK_EX);
//     if (!$ret) {
//         throw new Exception("配置写入失败");
//     }
//     return true;
// }
















/*
--------------------------------------------------------
|                                                      |
|                                                      |
|                以下暂时还未测试                        |
|                                                      |
|                                                      |
--------------------------------------------------------
**/


if(!function_exists('get_addons_tables')){      //  OK
    /**
     * 获取插件创建的表
     * @param string $name 插件名
     * @return array
     */
    function get_addons_tables($addonsName=null, $sqlFileName='install')
    {
        if (!$addonsName) {
            return [];
        }
        $addonInfo = get_addons_info($addonsName);
        if (!$addonInfo) {
            return [];
        }
        $regex = "/^CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z_]+)`?/mi";
        $sqlFile = root_path() . "addons" . DIRECTORY_SEPARATOR . $addonsName . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . $sqlFileName . '.sql';
        $tables = [];
        if (is_file($sqlFile)) {
            preg_match_all($regex, file_get_contents($sqlFile), $matches);
            if ($matches && isset($matches[2]) && $matches[2]) {
                $prefix = config('database.prefix');
                $tables = array_map(function ($item) use ($prefix) {
                    return str_replace("__PREFIX__", $prefix, $item);
                }, $matches[2]);
            }
        }
        return $tables;
    }
}

//  设置插件基础信息
if (!function_exists('set_addons_info')) {      //  OK
    /**
     * 设置基础配置信息
     * @param string $name  插件名
     * @param array  $array 配置数据
     * @return boolean
     * @throws Exception
     */
    function set_addons_info($name, $array)
    {
        $addon = get_addons_instance($name);
        if (!isset($array['name']) || !isset($array['title']) || !isset($array['version']) || !isset($array['isInstall'])) {
            return false;
        }
        return $addon->setInfo($name, $array);
        // $res = array();
        // foreach ($array as $key => $val) {
        //     if (is_array($val)) {
        //         $res[] = "[$key]";
        //         foreach ($val as $skey => $sval) {
        //             $res[] = "$skey = " . (is_numeric($sval) ? $sval : $sval);
        //         }
        //     } else {
        //         $res[] = "$key = " . (is_numeric($val) ? $val : $val);
        //     }
        // }
        // if (file_put_contents($file, implode("\n", $res) . "\n", LOCK_EX)) {
        //     //清空当前配置缓存
        //     config($name, null, 'addoninfo');
        // } else {
        //     //throw new Exception("文件没有写入权限");
        //     return false;
        // }
    }
}



if (!function_exists('is_really_writable')) {
    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5((string) mt_rand(1000, 9999));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}