<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class XlocationGroup extends Validate
{
    protected $rule = [
        'name'         =>  'require|max:100',
        'list_order'    =>  'require|number',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.max'     =>  'name can not be more than 100 characters',
        'name.require' =>   'name can not be empty',
        'list_order'    =>  'sort must be integer',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['name','list_order'],
        'token'    =>  ['__token__'],
    ];
}