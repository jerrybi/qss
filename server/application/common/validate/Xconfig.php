<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xconfig extends Validate
{
    protected $rule = [
        'top_banner_url'         =>  'require',
        'bottom_banner_url'    =>  'require',
        'content'       =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'top_banner_url.require' =>   'top banner url can not be empty',
        'bottom_banner_url.require'       =>  'foot banner url can not be empty',
        'content.require'       =>  'content can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['top_banner_url','foot_banner_url','content'],
        'token'    =>  ['__token__'],
    ];
}