<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/11/20
 * Time: 17:18
 */

namespace app\common\validate;


use think\Validate;

class XadminRole extends Validate
{

    protected $rule = [
        'user_name'    =>  'require|max:100',
        'nav_menu_ids' =>  'require',
        'status'       =>  'integer',
        '__token__'    =>  'require|token',
    ];
    protected $message  =   [
        'user_name.require'  =>  'role name cannot be empty',
        'user_name.max'      =>  'role name cannot be more than 100 characters',
        'nav_menu_ids'       =>  'menus cannot be empty',
        'status'             =>  'status invalid',
        '__token__'          =>  'Token invalid or expired',
    ];

    /**
     * 定义情景
     * @var array
     */
    protected $scene = [
        'default'  =>  ['user_name','nav_menu_ids','status'],
        'token'    =>  ['__token__'],
    ];
}