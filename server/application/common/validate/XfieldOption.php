<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class XfieldOption extends Validate
{
    protected $rule = [
        'field'    =>  'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'field.require'  =>  'Field can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['field'],
        'token'    =>  ['__token__'],
    ];
}