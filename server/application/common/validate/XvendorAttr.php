<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class XvendorAttr extends Validate
{
    protected $rule = [
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  [],
        'token'    =>  ['__token__'],
    ];
}