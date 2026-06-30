<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xuser extends Validate
{
    protected $rule = [
        'first_name'    =>  'require',
        'last_name'       =>    'require',
        'email'       =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'first_name.require'       =>  'first name can not be empty',
        'last_name.require'       =>  'last name can not be empty',
        'email.require'       =>  'email can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['first_name','last_name','email'],
        'token'    =>  ['__token__'],
    ];
}