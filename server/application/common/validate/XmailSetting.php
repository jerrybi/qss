<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 9:55
 */
namespace app\common\validate;
use \think\Validate;

class XmailSetting extends Validate
{
    protected $rule = [
        'sender_name'         =>  'require',
        'sender_email'    =>  'require',
        'mail_server'       =>    'require',
        'mail_port'       =>    'require',
        '__token__'     =>  'require|token',
    ];
    protected $message  =   [
        'sender_name.require' =>   'sender name can not be empty',
        'sender_email.require'       =>  'sender email can not be empty',
        'mail_server.require'       =>  'mail server can not be empty',
        'mail_port.require'       =>  'mail port can not be empty',
        '__token__'     =>  'Token is invalid or out of date',
    ];

    protected $scene = [
        'default'  =>  ['sender_name','sender_email','mail_server','mail_port'],
        'token'    =>  ['__token__'],
    ];
}