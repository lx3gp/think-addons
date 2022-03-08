<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\Request;
use think\Exception\ValidateException;

class Addons
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
        $result = get_addons_instance($name)->install();
        if($result === false){
            return 'install failure!';
        }

        //  处理静态资源文件-挪出去
        if(is_dir(dirname(__FILE__) . config('route.pathinfo_depr') . "static")) {
            @rename(root_path() . "addons" . config('route.pathinfo_depr') . $name . config('route.pathinfo_depr') . "static" . $name, public_path() . config('route.pathinfo_depr') . "static/addons/" . $name);
        }

        return 'install successed!';
    }


    public function uninstall($name){
        $result = get_addons_instance($name)->uninstall();

        //  处理静态资源文件-挪回来
        if(is_dir(dirname(__FILE__) . config('route.pathinfo_depr') . "static")) {
            @rename(public_path() . config('route.pathinfo_depr') . "static/addons/" . $name, root_path() . "addons" . config('route.pathinfo_depr') . $name . config('route.pathinfo_depr') . "static" . $name);
        }
        if($result === false){
            return 'uninstall failure!';
        }

        return 'uninstall successed!';
    }

    //  离线安装插件包
    public function offline_install() {
        //  上传插件


        //  解压插件


        //  安装插件
    }

    //  上传文件
    public function uploadFile(){
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
                $fileName = str_replace('.'.$file->getOriginalExtension(),'',$file->getOriginalName());
                $result = $this->unzip($originalPathname);
                if(!$result) {
                    return false;   //  解压失败
                }
                //  开始执行安装
                $result = $this->install($fileName);
                if(!$result) {
                    return false;   //  安装失败
                }

                return true;
            }
        }catch(ValidateException $e){
            return json(['data'=>[], 'msg'=>$e->getError(), 'code'=>400]);
        }
    }

    protected function upload(){

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
        $unzipPath = root_path() . "addons" .  config('route.pathinfo_depr');

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
                    @mkdir($unzipPath . config('route.pathinfo_depr') . substr($statInfo['name'], 0, -1));
                } else {
                    @copy('zip://'. $originalPathname . '#' . $statInfo['name'], $unzipPath . config('route.pathinfo_depr') . $statInfo['name']);
                }
            }

            //$zip->extractTo($unzipPath);
            //关闭zip文档
            $zip->close();
            return 'ok';
        }
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
