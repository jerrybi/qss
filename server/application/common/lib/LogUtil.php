<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\lib;
use think\facade\Log;
use think\facade\Env;

/**
 * Description of LogUtil
 *
 * @author 冬明
 */
class LogUtil {
    public static function info($data){
        Log::init([
            'type'=>'File',
            'path'=>Env::get('app_path').'/logs/'
        ]);
        Log::write($data,'info');
    }
}
