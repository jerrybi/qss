<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xannouncement extends Validate
{
    protected $rule = [
        'content'       =>    'require|max:300',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'content.require'       =>  'content can not be empty',
        'content.max'     =>  'content can not be more than 300 characters',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['content'],
        'token'    =>  ['__token__'],
    ];
}