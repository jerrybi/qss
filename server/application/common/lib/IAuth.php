<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2020/5/20
 * Time: 10:59
 */

namespace app\common\lib;
use think\facade\Session;
use think\Request;
use think\Db;
use Firebase\JWT\JWT;

/**
 * 相关授权操作类
 * Class IAuth
 * @package app\api\controller
 */
class IAuth
{
    /**
     * 获取系统配置项
     * @param string $confTag
     * @return mixed
     */
    public static function AUTH_CONF($confTag = ''){
        $res = config("sys_auth.".$confTag);
        return $res;
    }

    /**
     * 设置后台管理员登录密码加密
     * @param string $password
     * @param null $pws_pre_halt
     * @return string
     */
    public static function setAdminUsrPassword($password = '',$pws_pre_halt = null){
        if (!$pws_pre_halt){
            $pws_pre_halt = self::AUTH_CONF('PWD_PRE_HALT');
        }
        $res = strrev(md5(base64_encode($password).$pws_pre_halt));
        return $res;
    }

    public static function setAdminUsrAuthKey($publicKey,$privateKey){
        $res = self::aesEncrypt($publicKey,$privateKey, self::AUTH_CONF('AES_IV'));
        return $res;
    }

    public static function setUserAuthKey($publicKey,$privateKey){
        $res = self::aesEncrypt($publicKey,$privateKey, self::AUTH_CONF('AES_IV'));
        return $res;
    }

     public static function setUsrPassword($password = '',$pws_pre_halt = null){
        if (!$pws_pre_halt){
            $pws_pre_halt = self::AUTH_CONF('PWD_PRE_HALT');
        }
        $res = strrev(md5(base64_encode($password).$pws_pre_halt));
        return $res;
    }
    
    /**
     * 管理员登录成功后的信息 加密保存
     * @param int $op_id
     */
    public static function setSessionAdminCurrLogged($op_id = 0)
    {
        if ($op_id){
            $cmsRes = [
                'op_id' => $op_id,
                'time_stamp' => time(),
                'op_ip' => (new Request())->ip()];
            $jsonRes = json_encode($cmsRes);
            //进行加密 并保存到Session中
            $cms_encrypt = self::encrypt($jsonRes);
            Session::set(self::AUTH_CONF('SESSION_CMS_TAG'), $cms_encrypt,self::AUTH_CONF('SESSION_CMS_SCOPE'));
        }
    }
    
    public static function setSessionUserCurrLogged($op_id = 0)
    {
        if ($op_id){
            $apiRes = [
                'op_id' => $op_id,
                'time_stamp' => time(),
                'op_ip' => (new Request())->ip()];
            $jsonRes = json_encode($apiRes);
            //进行加密 并保存到Session中
            $api_encrypt = self::encrypt($jsonRes);
            Session::set(self::AUTH_CONF('SESSION_API_TAG'), $api_encrypt,self::AUTH_CONF('SESSION_API_SCOPE'));
        }
    }

    public static function logoutUserCurrLogged($op_id = 0){
        if (Session::has(self::AUTH_CONF('SESSION_API_TAG'),self::AUTH_CONF('SESSION_API_SCOPE'))) {
            Session::delete(self::AUTH_CONF('SESSION_API_TAG'),self::AUTH_CONF('SESSION_API_SCOPE'));
        }
    }

    public static function setSessionVendorUserCurrLogged($op_id = 0)
    {
        if ($op_id){
            $apiRes = [
                'op_id' => $op_id,
                'time_stamp' => time(),
                'op_ip' => (new Request())->ip()];
            $jsonRes = json_encode($apiRes);
            //进行加密 并保存到Session中
            $api_encrypt = self::encrypt($jsonRes);
            Session::set(self::AUTH_CONF('SESSION_VENDOR_TAG'), $api_encrypt,self::AUTH_CONF('SESSION_VENDOR_SCOPE'));
        }
    }

    public static function logoutVendorUserCurrLogged($op_id = 0){
        if (Session::has(self::AUTH_CONF('SESSION_VENDOR_TAG'),self::AUTH_CONF('SESSION_VENDOR_SCOPE'))) {
            Session::delete(self::AUTH_CONF('SESSION_VENDOR_TAG'),self::AUTH_CONF('SESSION_VENDOR_SCOPE'));
        }
    }

    /**
     * 获取当前登录状态下的管理员 ID信息
     * @return int
     */
    public static function getAdminIDCurrLogged(){
        $cmsRes = self::getDecryCmsRes();

        $time_stamp = isset($cmsRes['time_stamp'])?$cmsRes['time_stamp']:0;
        //检查 登录Session 的有效时间
        if ($time_stamp + config('session.expire') > time()){
            $cmsAID = isset($cmsRes['op_id'])?$cmsRes['op_id']:0;
        }
        return isset($cmsAID)?intval($cmsAID):0;
    }

    public static function getAdminInfo(){
        $id = self::getAdminIDCurrLogged();
        $res = Db::name('xadmins')->where(['id'=>$id])->find();
        return $res;
    }
    
     public static function getUserIDCurrLogged(){
         $apiRes = self::getDecryApiRes();

         $time_stamp = isset($apiRes['time_stamp'])?$apiRes['time_stamp']:0;
         //检查 登录Session 的有效时间
         if ($time_stamp + config('session.expire') > time()){
             $userId = isset($apiRes['op_id'])?$apiRes['op_id']:0;
         }
         return isset($userId)?intval($userId):0;
    }

    public static function getVendorUserIDCurrLogged(){
        $apiRes = self::getDecryVendorRes();

        $time_stamp = isset($apiRes['time_stamp'])?$apiRes['time_stamp']:0;
        //检查 登录Session 的有效时间
        if ($time_stamp + config('session.expire') > time()){
            $userId = isset($apiRes['op_id'])?$apiRes['op_id']:0;
        }
        return isset($userId)?intval($userId):0;
    }

    /**
     * 获取 加密数据的 原始数组形式
     * @return array|mixed
     */
    public static function getDecryCmsRes(){
        if (Session::has(self::AUTH_CONF('SESSION_CMS_TAG'),self::AUTH_CONF('SESSION_CMS_SCOPE'))
            && Session::get(self::AUTH_CONF('SESSION_CMS_TAG'),self::AUTH_CONF('SESSION_CMS_SCOPE'))){
            $cms_encrypt = Session::get(self::AUTH_CONF('SESSION_CMS_TAG'),self::AUTH_CONF('SESSION_CMS_SCOPE'));
            $cms_decrypt = self::decrypt($cms_encrypt);
            $cmsRes = json_decode($cms_decrypt,1);
        }
        return isset($cmsRes)?$cmsRes:[];
    }
    /**
     * 管理员账号退出操作
     */
    public static function logoutAdminCurrLogged(){
        if (Session::has(self::AUTH_CONF('SESSION_CMS_TAG'),self::AUTH_CONF('SESSION_CMS_SCOPE'))) {
            Session::delete(self::AUTH_CONF('SESSION_CMS_TAG'),self::AUTH_CONF('SESSION_CMS_SCOPE'));
        }
    }

    public static function getDecryApiRes($op_id = 0){
        if (Session::has(self::AUTH_CONF('SESSION_API_TAG'),self::AUTH_CONF('SESSION_API_SCOPE'))
            && Session::get(self::AUTH_CONF('SESSION_API_TAG'),self::AUTH_CONF('SESSION_API_SCOPE'))){
            $api_encrypt = Session::get(self::AUTH_CONF('SESSION_API_TAG'),self::AUTH_CONF('SESSION_API_SCOPE'));
            $api_decrypt = self::decrypt($api_encrypt);
            $apiRes = json_decode($api_decrypt,1);
        }
        return isset($apiRes)?$apiRes:[];
    }

    public static function getDecryVendorRes($op_id = 0){
        if (Session::has(self::AUTH_CONF('SESSION_VENDOR_TAG'),self::AUTH_CONF('SESSION_VENDOR_SCOPE'))
            && Session::get(self::AUTH_CONF('SESSION_VENDOR_TAG'),self::AUTH_CONF('SESSION_VENDOR_SCOPE'))){
            $api_encrypt = Session::get(self::AUTH_CONF('SESSION_VENDOR_TAG'),self::AUTH_CONF('SESSION_VENDOR_SCOPE'));
            $api_decrypt = self::decrypt($api_encrypt);
            $apiRes = json_decode($api_decrypt,1);
        }
        return isset($apiRes)?$apiRes:[];
    }

    /**
     * 创建 token
     * @param array $data 必填 自定义参数数组
     * @param integer $exp_time 必填 token过期时间 单位:秒 例子：7200=2小时
     * @param string $scopes 选填 token标识，请求接口的token
     * @return string
     */
    public static function createToken($data = "", $exp_time = 0, $scopes = "")
    {

        //JWT标准规定的声明，但不是必须填写的；
        //iss: jwt签发者
        //sub: jwt所面向的用户
        //aud: 接收jwt的一方
        //exp: jwt的过期时间，过期时间必须要大于签发时间
        //nbf: 定义在什么时间之前，某个时间点后才能访问
        //iat: jwt的签发时间
        //jti: jwt的唯一身份标识，主要用来作为一次性token。
        //公用信息
        try {
            $key = self::AUTH_CONF('API_TOKEN_KEY');
            $time = time(); //当前时间
            $token['iss'] = 'Jerry'; //签发者 可选
            $token['aud'] = ''; //接收该JWT的一方，可选
            $token['iat'] = $time; //签发时间
            $token['nbf'] = $time+3; //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            if ($scopes) {
                $token['scopes'] = $scopes; //token标识，请求接口的token
            }
            if (!$exp_time) {
                $exp_time = self::AUTH_CONF('API_TOKEN_EXPIRE');//默认=2小时过期
            }
            $token['exp'] = $time + $exp_time; //token过期时间,这里设置2个小时
            if ($data) {
                $token['data'] = $data; //自定义参数
            }

            $json = JWT::encode($token, $key);
            $returndata['code'] = "200";//200=成功
            $returndata['msg'] = '';
            $returndata['data'] = $json;//返回的数据
            return $returndata; //返回给客户端token信息

        } catch (\Firebase\JWT\ExpiredException $e) {  //签名不正确
            $returndata['code'] = "104";//101=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (Exception $e) {  //其他错误
            $returndata['code'] = "199";//199=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        }
    }
    
    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $jwt 需要验证的token
     * @return string $msg 返回消息
     */
    public static function checkToken($jwt)
    {
        $key = self::AUTH_CONF('API_TOKEN_KEY');
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['HS256']); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;

            $returndata['code'] = "200";//200=成功
            $returndata['msg'] = "成功";//
            $returndata['data'] = $arr;//返回的数据
            return $returndata; //返回信息

        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            //echo "2,";
            //echo $e->getMessage();
            $returndata['code'] = "101";//101=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            //echo "3,";
            //echo $e->getMessage();
            $returndata['code'] = "102";//102=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
            //echo "4,";
            //echo $e->getMessage();
            $returndata['code'] = "103";//103=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (Exception $e) {  //其他错误
            //echo "5,";
            //echo $e->getMessage();
            $returndata['code'] = "199";//199=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        }
        //Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token
    }
    
    /*-------------------------分界线------------------下面是核心处理方法-------------------*/
    /**
     * 加密
     * @param String input 加密的字符串
     * @param String key   解密的key
     * @return HexString
     */
    public static  function encrypt($input = '') {
        $data = openssl_encrypt($input, 'AES-256-CBC', self::AUTH_CONF('AES_KEY'), OPENSSL_RAW_DATA,self::AUTH_CONF('AES_IV'));
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 解密
     * @param String input 解密的字符串
     * @param String key   解密的key
     * @return String
     */
    public static function decrypt($sStr) {
        $decrypted = openssl_decrypt(base64_decode($sStr), 'AES-256-CBC', self::AUTH_CONF('AES_KEY'), OPENSSL_RAW_DATA,self::AUTH_CONF('AES_IV'));
        return $decrypted;
    }

    public static  function encrypt2($input = '') {
        //JWT标准规定的声明，但不是必须填写的；
        //iss: jwt签发者
        //sub: jwt所面向的用户
        //aud: 接收jwt的一方
        //exp: jwt的过期时间，过期时间必须要大于签发时间
        //nbf: 定义在什么时间之前，某个时间点后才能访问
        //iat: jwt的签发时间
        //jti: jwt的唯一身份标识，主要用来作为一次性token。
        //公用信息
        try {
            $key = "-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIOyb1z5MJ/zG+LQfLXlFKIHYT1ajf+aOIudce5I0KJtioAoGCCqGSM49
AwEHoUQDQgAEX9zjc3I8Gbjn08VUGVWTtKRgguXmhc28uommTetd6zwo4FdwDJ3Q
qLE+5RE/wjYz0iI50SYejBQh7m+MZe3X8g==
-----END EC PRIVATE KEY-----";
            $json = JWT::encode($input, $key,'ES256');
            return $json; //返回给客户端token信息
        } catch (\Firebase\JWT\ExpiredException $e) {  //签名不正确
            return ""; //返回信息
        } catch (Exception $e) {  //其他错误
            return ""; //返回信息
        }
    }

    public static function decrypt2($jwt) {
        $key = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEX9zjc3I8Gbjn08VUGVWTtKRgguXm
hc28uommTetd6zwo4FdwDJ3QqLE+5RE/wjYz0iI50SYejBQh7m+MZe3X8g==
-----END PUBLIC KEY-----";
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['ES256']); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;

            $returndata['code'] = "200";//200=成功
            $returndata['msg'] = "成功";//
            $returndata['data'] = $arr;//返回的数据
            return $returndata; //返回信息

        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            //echo "2,";
            //echo $e->getMessage();
            $returndata['code'] = "101";//101=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            //echo "3,";
            //echo $e->getMessage();
            $returndata['code'] = "102";//102=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
            //echo "4,";
            //echo $e->getMessage();
            $returndata['code'] = "103";//103=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (Exception $e) {  //其他错误
            //echo "5,";
            //echo $e->getMessage();
            $returndata['code'] = "199";//199=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        }
    }

    /**
     * 加密
     * @param String input 加密的字符串
     * @param String key   解密的key
     * @return HexString
     */
    public static  function encrypt3($input = '') {
        $data = openssl_encrypt($input, 'AES-256-CBC', self::AUTH_CONF('AES_KEY3'), OPENSSL_RAW_DATA,self::AUTH_CONF('AES_IV3'));
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 解密
     * @param String input 解密的字符串
     * @param String key   解密的key
     * @return String
     */
    public static function decrypt3($sStr) {
        $decrypted = openssl_decrypt(base64_decode($sStr), 'AES-256-CBC', self::AUTH_CONF('AES_KEY3'), OPENSSL_RAW_DATA,self::AUTH_CONF('AES_IV3'));
        return $decrypted;
    }

     public static function qr_decrypt($sStr,$key) {
        $decrypted = openssl_decrypt($sStr, 'AES-256-CBC', $key, OPENSSL_RAW_DATA);
        return $decrypted;
    }

    public static  function aesEncrypt($input,$key,$iv) {
        $data = openssl_encrypt($input, 'AES-256-CBC', $key, OPENSSL_RAW_DATA,$iv);
        $data = base64_encode($data);
        return $data;
    }

    public static function decodeEs256($jwt){
        $key = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEnOOKdyZq18knp4muSaWFTlESuxk/
Lb/NVc+N+eJso1ZlM0kasAgL1KE6lErJSUZoE0HgZR9ZJoptvISuo3JyXg==
-----END PUBLIC KEY-----";
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($jwt, $key, ['ES256']); //HS256方式，这里要和签发的时候对应
            $arr = (array)$decoded;

            $returndata['code'] = "200";//200=成功
            $returndata['msg'] = "成功";//
            $returndata['data'] = $arr;//返回的数据
            return $returndata; //返回信息

        } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            //echo "2,";
            //echo $e->getMessage();
            $returndata['code'] = "101";//101=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            //echo "3,";
            //echo $e->getMessage();
            $returndata['code'] = "102";//102=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
            //echo "4,";
            //echo $e->getMessage();
            $returndata['code'] = "103";//103=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        } catch (Exception $e) {  //其他错误
            //echo "5,";
            //echo $e->getMessage();
            $returndata['code'] = "199";//199=签名不正确
            $returndata['msg'] = $e->getMessage();
            $returndata['data'] = "";//返回的数据
            return $returndata; //返回信息
        }
    }

}