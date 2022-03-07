<?php
namespace addons\message\controller;	// 注意命名空间规范

use app\BaseController;
use addons\message\model\Message as MessageModel;
use think\Addons;
use think\facade\Db;

class Index extends BaseController
{

    //  获取留言列表
    public function index($aid = 0, $user_id=0){
        $list = MessageModel::where('aid', '=', $aid)->where('touid', '=', $user_id)->paginate(10);
        return view('', ['meaages'=>$list]);
    }

    //  保存留言
    public function save() {
        $data = input('post.');
        $result = MessageModel::store($data);
        if($result){
            return true;
        }
        return false;
    }

    //  删除留言
    public function delete($id=0) {
        $result = MessageModel::destroy($id);
        if($result>0){
            return true;    //  可以使用echo '删除成功~'
        }
        return false;   //  可以使用echo '删除失败~'
    }
}