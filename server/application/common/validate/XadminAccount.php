<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/11/20
 * Time: 17:18
 */

namespace app\common\validate;


use think\Validate;

class XadminAccount extends Validate
{

    protected $rule = [
        'account_name'    =>  'require|max:100',
        'max_event' =>  'require|number',
        'max_user' =>  'require|number',
        'status'       =>  'integer',
        '__token__'    =>  'require|token',
    ];
    protected $message  =   [
        'account_name.require'  =>  'account name cannot be empty',
        'account_name.max'      =>  'account name cannot be more than 100 characters',
        'max_event.require'       =>  'max event cannot be empty',
        'max_event.number'       =>  'max event must be integer',
        'max_user.require'       =>  'max user cannot be empty',
        'max_user.number'       =>  'max user must be integer',
        'status'             =>  'status invalid',
        '__token__'          =>  'Token invalid or expired',
    ];

    /**
     * 定义情景
     * @var array
     */
    protected $scene = [
        'default'  =>  ['account_name','max_event','max_user','status'],
        'token'    =>  ['__token__'],
    ];
}