<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/1/17
 * Time: 19:02
 */
namespace app\common\controller;

use think\facade\Session;
use think\Request;

class Base
{
    /**
     * 初始化处理数据
     * Base constructor.
     */
    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:token,Origin,X-Requested-With,Content-Type,Accept,Authorization');
        header('Access-Control-Allow-Methods:POST,GET,PUT,DELETE');
    }

}