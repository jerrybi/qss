<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xcatalog extends Validate
{
    protected $rule = [
        'name'       =>    'require',
        'type'       =>    'require',
        'category'       =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.require'       =>  'name can not be empty',
        'type.require'       =>  'type can not be empty',
        'category.require'       =>  'category can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['name','type','category'],
        'token'    =>  ['__token__'],
    ];
}