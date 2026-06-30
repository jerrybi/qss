<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class XdataField extends Validate
{
    protected $rule = [
        'name'    =>  'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.require'  =>  'Name can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['name'],
        'token'    =>  ['__token__'],
    ];
}