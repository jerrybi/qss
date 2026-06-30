<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/11/20
 * Time: 17:18
 */

namespace app\common\validate;


use think\Validate;

class Xserver extends Validate
{

    protected $rule = [
        'server_name'         =>  'require|max:100',
        '__token__'    =>  'require|token',
    ];
    protected $message  =   [
        'server_name.require'  =>  'server name can not be empty',
        'server_name.max'      =>  'server name can not surpass 100 words',
        '__token__.require'     =>  'Token can not be empty',
        '__token__.token'     =>  'Token is invalid or expired',
    ];

    /**
     * 定义情景
     * @var array
     */
    protected $scene = [
        'default'  =>  ['title','tag','value','input_type','tip'],
        'token'    =>  ['__token__'],
    ];
}