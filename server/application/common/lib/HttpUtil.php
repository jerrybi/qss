<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\lib;

/**
 * Description of HttpUtil
 *
 * @author 冬明
 */
class HttpUtil {
    public static function http_post_data($url,$data_string){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);// 跳过证书检查，为true则从证书中检查SSL加密算法是否存在
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array("Content-Type:application/x-www-form-urlencoded","Content-Length:".strlen($data_string)));
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($return_code,$return_content);
    }
}
