<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\lib;

/**
 * Description of Tools
 *
 * @author 冬明
 */
class Tools {
    //put your code here
    public static function create_guid(){
        $charid = strtoupper(md5(uniqid(mt_rand(),TRUE)));
        $hyphen = chr(45);// "-" 
        $uuid =  
        substr($charid, 0, 8).$hyphen 
        .substr($charid, 8, 4).$hyphen 
        .substr($charid,12, 4).$hyphen 
        .substr($charid,16, 4).$hyphen 
        .substr($charid,20,12) ; 
        return $uuid; 
    }

    public static function create_number_unique(){
        //假设一个机器id
        $machineId = 89;

        //41bit timestamp(毫秒)
        $time = floor(microtime(true) * 1000);

        //0bit 未使用
        $suffix = 0;

        //datacenterId  添加数据的时间
        $base = decbin(pow(2,40) - 1 + $time);

        //workerId  机器ID
        $machineid = decbin(pow(2,9) - 1 + $machineId);

        //毫秒类的计数
        $random = mt_rand(1, pow(2,9)-1);

        $random = decbin(pow(2,9)-1 + $random);
        //拼装所有数据
//        $base64 = $suffix.$base.$machineid.$random;
        $base64 = $suffix.$base.$random;
        //将二进制转换int
        $base64 = bindec($base64);

        $id = sprintf('%.0f', $base64);

        return $id;
    }

    /* 生成随机字符串
    * @param int       $length  要生成的随机字符串长度
    * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
    * @return string
    */
    public static function randCode($length = 32, $type = -1) {
        $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
        if ($type == 0) {
          array_pop($arr);
          $string = implode("", $arr);
        } elseif ($type == "-1") {
           $string = implode("", $arr);
        } else {
          $string = $arr[$type];
        }
         $count = strlen($string) - 1;
         $code = '';
        for ($i = 0; $i < $length; $i++) {
          $code .= $string[rand(0, $count)];
        }
        return $code;
    }
    
    public static function parse_vcard($content){
        //Vcard数据格式行为：类型[;参数]:值
        $arr = explode("|||", str_replace("\r\n", "|||", $content));
        $data = [];
        foreach ($arr as $value){
            $itemArr = explode(':',$value);
            if($itemArr[0] == 'N'){
                $paras = explode(';', $itemArr[1]);
                $data['surname'] = $paras[0];
                $data['given_name'] = $paras[1];
                $data['salutaion'] = $paras[3];
            }else if($itemArr[0] == 'FN'){
                $data['first_name'] = $itemArr[1];
            }else if($itemArr[0] == 'ORG'){
                $data['organisation'] = $itemArr[1];
            }else if($itemArr[0] == 'TITLE'){
                $data['title'] = $itemArr[1];
            }else if($itemArr[0] == 'TEL;TYPE=WORK,voice'){
                $data['work_phone'] = $itemArr[1];
            }else if($itemArr[0] == 'TEL;TYPE=CELL,voice'){
                $data['mobile_phone'] = $itemArr[1];
            }else if($itemArr[0] == 'ADR;TYPE=WORK'){
                $paras = explode(';', $itemArr[1]);
                $data['address_line1'] = $paras[2];
                $data['address_line2'] = $paras[3];
                $data['city'] = $paras[4];
                $data['state'] = $paras[5];
                $data['postal_code'] = $paras[6];
                $data['country'] = $paras[7];
            }else if($itemArr[0] == 'EMAIL'){
                $data['email'] = $itemArr[1];
            }else if($itemArr[0] == 'SOURCE'){
                $data['source_url'] = substr($value, strlen($itemArr[0])+1);
            }else if($itemArr[0] == 'X-QS-SOURCE-NAME'){
                $data['source_name'] = $itemArr[1];
            }else if($itemArr[0] == 'X-QS-SOURCE-FROM'){
                $data['source_from'] = $itemArr[1];
            }else if($itemArr[0] == 'X-QS-CONTACT-CATEGORY'){
                $data['visitor_category'] = $itemArr[1];
            }else if($itemArr[0] == 'X-QS-UNIQUE-ID'){
                $data['visitor_id'] = $itemArr[1];
            }else if(strpos($itemArr[0],'PHOTO') === 0){
                $data['photo_url'] = substr($value, strlen($itemArr[0])+1);
            }
        }
        return $data;
    }
    
    public static function get_ip()
    {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            $cip = $_SERVER['HTTP_CLIENT_IP'];
        }
        else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        else if(!empty($_SERVER["REMOTE_ADDR"])){
            $cip = $_SERVER["REMOTE_ADDR"];
        }else{
            $cip = '';
        }
        preg_match("/[\d\.]{7,15}/", $cip, $cips);
        $cip = isset($cips[0]) ? $cips[0] : 'unknown';
        unset($cips);
        return $cip;
    }
    
    public static function startWith($str,$needle){
        return strpos($str, $needle) === 0;
    }
    
    public static function endWith($haystack,$needle){
        $length = strlen($needle);  
        if($length == 0)
        {    
            return true;  
        }  
        return (substr($haystack, -$length) === $needle);
    }

    public static function getDirectoryPath($path){
        $arr = explode(DIRECTORY_SEPARATOR,$path);
        $count = count($arr);
        if($count > 1){
            $arr[$count-1] = '';
            $out = implode(DIRECTORY_SEPARATOR,$arr);
            return $out;
        }else{
            return $path;
        }
    }

    public static function makeCode($len = 6) {
        $str = '0123456789';
        $code = '';
        for ($i=0; $i < $len; $i++) {
            $code .= substr($str, rand(0, strlen($str) - 1), 1);
        }
        return $code;
    }

    public static function phoneValidate($phone) {
        $pattern = "/^1[34578]{1}\d{9}$/";
        if (preg_match($pattern, $phone)) {
            return ['status' => 1, 'msg' => 'ok'];
        }else {
            return ['status' => 0, 'msg' => 'Please input valid phone number'];
        }
    }

    public static function emailValidate($email) {
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        if (preg_match($pattern, $email)) {
            return ['status' => 1, 'msg' => 'ok'];
        }else {
            return ['status' => 0, 'msg' => 'Please input valid email address'];
        }
    }

    public static function generateAccessToken() {
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';
        for ($i = 0; $i < 64; $i++) {
            $token .= substr($str, rand(0, strlen($str) - 1), 1);
        }
        return $token;
    }

    public static function getNameByEmail($email){
        $index = strpos($email,'@');
        $name = substr($email,0,$index);
        return $name;
    }

    public static function getNameByCompany($company){
        //取公司名的前7位作为用户名
        $len = strlen($company);
        $len = $len<=7?$len:7;
        $name = substr($company,0,$len);
        return $name;
    }

    public static function encryptPassword($password) {
        return md5(md5($password) . 'qls2021');
    }

    public static function generateOrderNo($uid) {
        return date('YmdHis') . '_' . rand(1000, 9999) . '_' . $uid;
    }

    public static function generatePayNo($uid) {
        return date('YmdHis') . '_' . rand(1000, 9999) . '_' . $uid;
    }

    public static function getUri($url){
        $res = parse_url($url);
        $uri = $res['path'];
        if(!empty($res['query'])){
            $uri .= '?'.$res['query'];
        }
        return $uri;
    }

    public static function getExcelColumnTitles($count){
        $titles = [];
        for($i=0;$i<$count;$i++){
            if($i >= 0 && $i < 26){
                $titles[] = chr(ord('A')+$i);
            }else if($i >= 26 && $i < 27*26){
                $titles[] = chr(ord('A')+(($i-26)/26)).chr(ord('A')+(($i-26)%26));
            }else if($i >= 27*26 && $i < (27*26+1)*26){
                $titles[] = chr(ord('A')+(($i-27*26)/(26*26))).chr(ord('A')+((($i-27*26)%(26*26))/26)).chr(ord('A')+((($i-27*26)%(26*26))%26));
            }else{
                //normally can not reach this condition
                $titles[] = $i;
            }
        }
        return $titles;
    }

    public static function isSameDay($last_date,$this_date){

        if(($last_date['year']===$this_date['year'])&&($this_date['yday']===$last_date['yday'])){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    public static function renameFile($oldName,$newName){
        $pos = strrpos($newName,".");
        if($pos){
            $filename = substr($newName,0,$pos);
            $suffix = substr($newName,$pos);
        }else{
            $filename = $newName;
            $suffix = "";
        }
        if(file_exists($oldName)){
            $index = 1;
            while(!rename($oldName,$newName)){
                $newName = $filename."(".$index.")".$suffix;
                $index++;
            }
        }
    }

    public static function autolink($content)
    {
        $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
        $content = preg_replace($regex, '<a href="$1">$1</a>', $content);
        return $content;
    }

    public static function getHtmlMultiLine($content){
        return str_replace("\r\n","<br/>",$content);
    }

    public static function getSortIndustry($industry){
        $data = explode("\r\n",$industry);
        sort($data);
        $industry = implode("|",$data);
        return $industry;
    }

    public static function getSortHtmlIndustry($industry){
        $data = explode("\r\n",$industry);
        sort($data);
        $industry = implode("<br/>",$data);
        return $industry;
    }

    /**
     * @param $arr 要查找的数组
     * @param $arrKey 要查找的数组键值
     * @param $value 要查找的值
     * @return array|null
     */
    public static function find_array_item($arr, $arrKey, $value)
    {
        foreach($arr as $v){
            if($v[$arrKey] == $value){
                return $v;
            }
        }
        return null;
    }

    public static function find_array_value($arr, $arrKey){
        foreach($arr as $v){
            if($v['key'] == $arrKey){
                return $v['value'];
            }
        }
        return '';
    }

    public static function array_filter($arr, $arrKey){
        $res = [];
        if($arr){
            foreach($arr as $v){
                if(isset($v[$arrKey])){
                    $res[] = $v[$arrKey];
                }
            }
        }
        return $res;
    }

    public static function url_decode($url){
        if(strpos($url,'%') !== false){
            return urldecode($url);
        }else{
            return $url;
        }
    }

    public static function get_filename($url){
        $arr = explode('/',$url);
        return $arr[count($arr)-1];
    }

    public static function removeInvisibleCharacters($str) {
        // 正则表达式匹配所有不可见的特殊字符
        $regex = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F\x{200B}-\x{200F}]/u';

        // 使用正则表达式替换掉这些字符
        $cleanString = preg_replace($regex, '', $str);

        return $cleanString;
    }
}
