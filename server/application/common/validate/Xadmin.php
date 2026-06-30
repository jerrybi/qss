<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/11/20
 * Time: 17:18
 */

namespace app\common\validate;


use think\Validate;

class Xadmin extends Validate
{

    protected $rule = [
        'user_name'    =>  'require|max:20',
        'picture'      =>  'require',
//        'password'     =>  'require|max:200|confirm:re_password',
        'role_id'      =>  'number',
        'email'       =>  'require|email',
        'public_key'   =>  'require',
        '__token__'    =>  'require|token',

    ];
    protected $message  =   [
        'user_name.require'  =>  'User name can not be empty',
        'user_name.max'      =>  'User name can not surpass 20 words',
        'picture'            =>  'picture can not be empty',
//        'password.require'  =>  'password can not be empty',
//        'password.max'      =>  'password is too long',
        'role_id'            =>  'role can not be empty',
        'email.require'      => 'Email cannot be empty',
        'email.email'        =>   'Invalid email format',
        'public_key.require' =>  'public key can not be empty',
        '__token__'          =>  'Token is invalid or expired',
    ];
    /**
     * 定义情景
     * @var array
     */
    protected $scene = [
        'default'  =>  ['user_name','picture','password','role_id','email'],
        'edit_admin_no_pwd'   =>  ['user_name','picture','role_id','email'],
        'token'    =>  ['__token__'],
        'cms_admin'=>  ['user_name','picture','email']
    ];
}