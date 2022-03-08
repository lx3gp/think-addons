<?php
declare(strict_types=1);

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
        $info_check_keys = ['name', 'title', 'description', 'status', 'author', 'version'];
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
            if (is_dir($addonsPath . config('route.pathinfo_depr') . $name)) {
                //  判断插件的有效性
                //  获取单例插件的插件详情, 当结果为null时，表示不是插件
                if(!get_addons_instance($name)) {
                    continue;
                }
                // $class = get_addons_class($name);
                // if (!class_exists($class)) {
                //     continue;
                // }
                $addon_info = get_addons_instance($name)->getInfo();
                //  判断单例插件的参数是否完整
                if (!check_addons_Info($addon_info)) {
                    continue;
                }
                //  判断配置文件是否存在，及判断是否安装了插件
                $info_file = $addonsPath . config('route.pathinfo_depr') . $name . config('route.pathinfo_depr') . config('addons.conf_flag') . config('addons.conf_suffix');
                if (!is_file($info_file)){
                    $addon_info['isInstall']=0;
                }else{
                    $addon_info['isInstall']=1;
                }
                $_addons_list[] = $addon_info;
            }
        }
        return $_addons_list;
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

if (!function_exists('create_config')) {
    /**
     * 创建插件配置文件
     *
     * @param array|null $config    需要创建的参数
     * @param string $name  需要创建的配置文件名
     * @return void
     */
    function create_config(array $config = null) {
        if (!$config) {
            return false;
        }
        $_config_file = config('addons.conf_flag') . config('addons.conf_suffix');
        $config_file = root_path() . "addons" . config('route.pathinfo_depr') . $config['name'] . config('route.pathinfo_depr') . $_config_file;
        if(is_file($config_file) && file_exists($config_file)) {
            return false;
        }
        $config = var_export($config, true);
        $content = <<<EOF
        <?php

        return {$config};
EOF;
        $result = file_put_contents($config_file, $content);
        if($result ===false) {
            return false;
        }
        return true;
    }
}
