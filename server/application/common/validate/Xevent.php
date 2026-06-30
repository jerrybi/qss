<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class Xevent extends Validate
{
    protected $rule = [
        'name'         =>  'require|max:100',
        'list_order'    =>  'require|number',
        'venue'       =>    'require|max:512',
        'country'     =>    'require',
        'timezone'    =>    'require',
        'start_time'  =>    'require',
        'end_time'    =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'name.max'     =>  'name can not be more than 100 characters',
        'name.require' =>   'name can not be empty',
        'list_order'    =>  'sort must be integer',
        'venue'       =>  'venue can not be empty',
        'country'       =>  'country can not be empty',
        'timezone'       =>  'timezone can not be empty',
        'start_time'       =>  'start time can not be empty',
        'end_time'       =>  'end time can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['title','list_order','content'],
        'token'    =>  ['__token__'],
    ];
}