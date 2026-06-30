<?php
/**
 * 系统+认证 配置文件
 * 初始化配置信息
 * 根据注释，进行配置，提高系统安全性
 *
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2020/5/27
 * Time: 14:45
 */
return [
    'IP_WHITE'=>'CLOSE',//是否启用IP白名单 ...

    'AES_KEY'=>'$MEWQ+D_()*#($#@O>?',//自定义AES秘钥
    'AES_IV'=>'$EH890^38LOIgf()',//自定义16位 AES偏移量
	  'AES_KEY3'=>'$MEWQ+D_()*#($#@O>?CBSsuei334@@2',//自定义AES秘钥
    'AES_IV3'=>'$EH890^38LOIgf()',//自定义16位 AES偏移量
    'CLIENT_AES_IV'=>'qsxxqsxxqsxxqsxx',
    'PWD_PRE_HALT'=>'szxx#23E()&lJHD#$F3',//密码加密前缀
    'SESSION_CMS_TAG' => 'cmsMoTzxxAID',//后台登录信息存储标记
    'SESSION_CMS_SCOPE' => 'tp51Pro',// 后台登录信息存储 作用域
    'TEST_AI'=>'nihjiao', //哈哈如果
    'SESSION_API_TAG' => 'apiQlsxxAID',//前台登录信息存储标记
    'SESSION_API_SCOPE' => 'qlsPro',// 前台登录信息存储 作用域
    'SESSION_VENDOR_TAG' => 'vendorQlsxxAID',//前台登录信息存储标记
    'SESSION_VENDOR_SCOPE' => 'qlsVendorPro',// 前台登录信息存储 作用域
    'API_TOKEN_EXPIRE' => 432000,
    'API_TOKEN_KEY' => 'qlsscan',
    'GOOGLE_RECAPTCHA_SECRET'=>'6Lcl1r0ZAAAAAEwij5_L9_1Nw97RP0JD2Zbl-a2e',
    'DEFAULT_PWD' => 'Qss@6785'
];