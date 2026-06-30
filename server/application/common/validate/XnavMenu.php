<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/11/20
 * Time: 17:18
 */

namespace app\common\validate;


use think\Validate;

class XnavMenu extends Validate
{

    protected $rule = [
        'name'         =>  'require|max:100',
        'icon'         =>  'require',
        '__token__'    =>  'require|token',
    ];
    protected $message  =   [
        'name.require'  =>  'Name cannot be empty',
        'name.max'      =>  'Name cannot be more than 255 characters',
        'icon'          =>  'Icon is not added',
        '__token__'     =>  'Token invalid or expired',
    ];

    /**
     * 定义情景
     * @var array
     */
    protected $scene = [
        'default'  =>  ['name','icon'],
        'token'    =>  ['__token__'],
    ];
}