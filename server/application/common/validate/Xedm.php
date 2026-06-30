<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xedm extends Validate
{
    protected $rule = [
        'name'         =>  'require|max:255',
        'content'    =>  'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.max'     =>  'name can not be more than 255 characters',
        'name.require' =>   'name can not be empty',
        'content.require'    =>  'content can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['name','content'],
        'token'    =>  ['__token__'],
    ];
}