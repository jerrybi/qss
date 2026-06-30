<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/11/23
 * Time: 11:23
 */

namespace app\common\controller;

use app\common\lib\IAuth;
use app\common\lib\LogUtil;
use app\common\lib\MyRedis;
use app\common\model\Xadmins;
use app\common\model\XsysConf;
use think\facade\Request;
use think\Db;

/**
 * 此类主要用于后台控制类的初始化操作
 * Class ApiBase
 * @package app\common\controller
 */
class ApiBase extends Base
{
    protected $apiAID = 0;
    protected $user;
    /**
     * 初始化处理数据
     * Base constructor.
     */
    public function __construct()
    {
        parent::__construct();
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:token,Origin,X-Requested-With,Content-Type,Accept,Authorization');
        header('Access-Control-Allow-Methods:POST,GET,PUT,DELETE');
        $this->initAuth();
    }

    /**
     * 进行权限控制
     */
    public function initAuth()
    {
        $authFlag = 500;
        $this->apiAID = IAuth::getUserIDCurrLogged();
        if (!$this->apiAID) {
            $message = "You are offline,please logon again!";
            $authFlag = 401;
        } else {
            //检查id是否有效，因为存在在后台删除账号的情况
            $this->user = Db::name('xexhibitors')->where(['id'=>$this->apiAID,'status'=>1])->find();
            if(empty($this->user)){
                $authFlag = 401;
                $message = "User not exist!";
            }else{
                // 判断单点登录
                $token = MyRedis::getInstance()->get('token_'.$this->apiAID);
                $curToken = Request::header('token');
                if($curToken != $token){
                    $message = "You have login on another device!";
                    $authFlag = 401;
                } else {
                    $authFlag = 200;
                    $message = "Auth ok";
                }
            }
        }
        if ($authFlag != 200) {
            return showMsg($authFlag, $message);
        };
    }
    
    public function getUserId(){
        return $this->apiAID;
    }

    public function getUser(){
        return $this->user;
    }
}