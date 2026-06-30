<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xexhibitor extends Validate
{
    protected $rule = [
        'login_name'    =>  'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'login_name.require'       =>  'login name can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['login_name'],
        'token'    =>  ['__token__'],
    ];
}