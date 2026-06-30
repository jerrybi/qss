<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xzone extends Validate
{
    protected $rule = [
        'name'         =>  'require|max:150',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.max'     =>  'name can not be more than 150 characters',
        'name.require' =>   'name can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['name'],
        'token'    =>  ['__token__'],
    ];
}