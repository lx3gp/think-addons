<?php

function get_addons_menus($type=0, $positon=0){
    $addons = get_addons_list();
    $menu_arr = [];
    foreach($addons as $name){
        //  判断插件是否安装
        if(is_file($info_file = root_path() . "addons" . config('route.pathinfo_depr') . $name . config('route.pathinfo_depr') . config('addons.conf_flag') . config('addons.conf_suffix'))){
            if(isset($v['menu']) && !empty($v['menu'])) {
                $_menu = [];
                foreach($v['menu'] as $menu) {
                    if ($menu['type'] == $type && $menu['positon'] == $positon) {
                        $_menu[] = $v['menu'];
                    }
                }
                $menu_arr[] = $_menu;
            }
        }
    }
    return $menu_arr;
}