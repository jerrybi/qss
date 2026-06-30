<?php

namespace app\api\controller;

use app\common\controller\ApiBase;
use app\common\lib\FtpServer;
use think\Request;

class Upload
{

    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:token,Origin,X-Requested-With,Content-Type,Accept,Authorization');
        header('Access-Control-Allow-Methods:POST,GET,PUT,DELETE');
    }
    /**
     * 图片上传
     * @param Request $request
     * @return \think\response\Json
     */
    public function img_file(Request $request){
        $opRes = [];
        if ($request->Method()== 'POST') {
            //判断是哪种上传方式 七牛云
            if (config('qiniu.QN_USE') == 'OPEN'){
                $opRes = \app\common\lib\Upload::qiNiuSingleFile();
            }else{
                $opRes = \app\common\lib\Upload::singleFile($request);
            }
        }else{
            $opRes['message'] = "Sorry,invalid request";
        }
        return showMsg($opRes['status'], $opRes['message'],$opRes['data']);
    }
}
