<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use think\facade\Env;

return [
    'host' => Env::get('redis.host', '127.0.0.1'),
    'port' => Env::get('redis.port', 6379),
    'password' => Env::get('redis.password', ''),
    'time_out' => Env::get('time_out', 5),
    'auth' => '',
    'admin_token_expire_in' => 86400,
    'admin_token_key_prefix' => 'admin_token:',
    'api_token_expire_in' => 86400,
    'api_token_key_prefix' => 'api_token:',

    'thirty_minutes' => 1800,
    'one_day' => 86400,
    'two_day' => 172800,
    'three_day' => 259200,
    'four_day' => 345600,
    'five_day' => 432000,
    'seven_day' => 604800,
    'fifteen_day' => 1296000,
    'one_month' => 2590000
];

