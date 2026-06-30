<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xcompany extends Validate
{
    protected $rule = [
        'name'       =>    'require',
        'type'       =>    'require',
        'email'       =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.require'       =>  'name can not be empty',
        'type.require'       =>  'type can not be empty',
        'email.require'       =>  'email can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['name','type','email'],
        'token'    =>  ['__token__'],
    ];
}