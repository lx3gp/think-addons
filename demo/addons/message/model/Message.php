<?php
namespace addons\message\model;	// 注意命名空间规范

use think\Addons;
use think\Model;

class Message extends Model
{
    public static function store(array $data = null){
        if(!is_array($data)){
            return false;
        }
        $result = self::create($data);
        if($result) {
            return true;
        }
        return false;
    }
}