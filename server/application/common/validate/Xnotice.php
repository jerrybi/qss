<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xnotice extends Validate
{
    protected $rule = [
        'title'       =>    'require',
        'content'       =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'title.require'       =>  'title can not be empty',
        'content.require'       =>  'content can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['title','content'],
        'token'    =>  ['__token__'],
    ];
}