<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;
use app\common\controller\ApiBase;
use app\common\lib\Email;
use app\common\lib\IAuth;
use app\common\lib\MyRedis;
use app\common\lib\QRCode;
use app\common\model\XdataFields;
use app\common\model\Xevents;
use app\common\model\Xconfigs;
use app\common\model\Xexhibitors;
use app\common\model\Xorders;
use app\common\model\Xtracks;
use app\common\model\XuserDatas;
use app\common\model\XuserStatus;
use app\common\model\XuserTables;
use app\common\model\Xusers;
use app\common\model\Xzones;
use think\Db;
use think\Request;
use think\facade\Cache;
use think\facade\Env;
use app\common\model\Xdevices;
use app\common\lib\Tools;

/**
 * Description of Login
 *
 * @author 冬明
 */
class User{
    protected $deviceModel;
    protected $userModel;
    protected $orderModel;
    protected $userDataModel;
    protected $userTableModel;
    protected $eventModel;
    protected $trackModel;
    protected $zoneModel;
    protected $configModel;
    protected $dataFieldModel;
    protected $userStatusModel;
    protected $exhibitorModel;

    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:token,Origin,X-Requested-With,Content-Type,Accept,Authorization');
        header('Access-Control-Allow-Methods:POST,GET,PUT,DELETE');
        $this->userModel = new Xusers();
        $this->deviceModel = new Xdevices();
        $this->orderModel = new Xorders();
        $this->userDataModel = new XuserDatas();
        $this->userTableModel = new XuserTables();
        $this->eventModel = new Xevents();
        $this->trackModel = new Xtracks();
        $this->zoneModel = new Xzones();
        $this->configModel = new Xconfigs();
        $this->dataFieldModel = new XdataFields();
        $this->userStatusModel = new XuserStatus();
        $this->exhibitorModel = new Xexhibitors();
    }

    public function regist(Request $request){
        if ($request->isPost()) {
            $email = $request->param('email');
            $company = $request->param('company');
            $publicKey = $request->param('public_key');
            $firstName = $request->param('first_name');
            $lastName = $request->param('last_name');
            $mobile = $request->param('mobile');
            $type = $request->param('type');
            $code = $request->param('code');
            $userName = $request->param('username');
            $user = $this->userModel->getUserByEmail($email,$type);
            if (!empty($user)) {
                return showMsg(0, 'Email exist!');
            }
            $user = $this->userModel->getUserByUserName($userName,$type);
            if (!empty($user)) {
                return showMsg(0, 'User name exist!');
            }
            if (Cache::get('email_code_' . $email) != $code) {
                return showMsg(0, 'verification code error or expired');
            }
            Cache::rm('email_code_' . $email);
            $privateKey = Tools::randCode();
            $authenticateKey = IAuth::aesEncrypt($publicKey, $privateKey, config('sys_auth.AES_IV'));
            $uniqueId = Tools::create_guid();
            //生成默认二维码
            $qrContent = config('app.web_url').'?m=1&p='.$uniqueId;
            $filename = QRCode::create_qrcode($qrContent,null);
            $url = $request->domain().'/qrcode/'.$filename;
            $user = $this->userModel->newUser($userName, $email, $privateKey,$authenticateKey,$firstName,$lastName,$mobile,$company,$type,$uniqueId,$url);
            $result = IAuth::createToken(['uid'=>$user['unique_id']]);
            $data['token'] = $result['data'];
            $userInfo = $this->userModel->getUserByUid($user['unique_id']);
            $data['user'] = $userInfo;
            return showMsg(1, 'success',$data);
        }else{
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function logout() {
        $userId = IAuth::getUserIDCurrLogged();
        IAuth::logoutUserCurrLogged($userId);
        return showMsg(1);
    }

    public function sendEmailCode(Request $request) {
        if($request->isPost()){
            //接收手机号
            $email = $request->param('email');
            //验证手机号
            $res = Tools::emailValidate($email);
            if (!$res['status']) {
                return showMsg(0, $res['msg']);
            }
            //生成验证码
            $code = Tools::makeCode();
            //发送验证码
            $emailClient = new Email();
            $name = Tools::getNameByEmail($email);
            $content = '<html>Hello <b>'.$name.'</b></br>Welcome to $company </br>Your email verification code is <b>'.$code.'</b></html>';
            $rs = $emailClient->sendemailex($name,$email,'Digital Card','verification code',$content);
            if (!$rs['status']) {
                return showMsg(0,$rs['msg']);
            }
            //将验证码保存到redis中
            Cache::set('email_code_' . $email, $code, config('email.expire_in'));
            return showMsg(1, $rs['msg']);
        }else{
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    //put your code here
    public function login(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $input['ip'] = Tools::get_ip();
            $tagRes = $this->checkUserLogin($input);
            return showMsg($tagRes['tag'], $tagRes['message'],$tagRes['data']);
        } else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function updateCompanyLogo(Request $request)
    {
        if ($request->isPost()) {
            $uid = $request->param('uid');
            $url = $request->param('url');
            //生成二维码
            $user = $this->userModel->getUserByUid($uid);
            $qrContent = config('app.web_url').'?m=1&p='.$user['unique_id'];
            $qrUrl = QRCode::create_qrcode($qrContent,Env::get('root_path').'/public/'.$url);
            $companyLogo = $request->domain().'/'.$url;
            $companyQr = $request->domain().'/qrcode/'.$qrUrl;
            Db::name('xusers')->where('unique_id',$uid)->update(['company_logo'=>$companyLogo,'company_qr'=>$companyQr,'step'=>2]);
            $user['company_logo'] = $companyLogo;
            $user['company_qr'] = $companyQr;
            return showMsg(1, 'success',$user);
        } else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    private function checkUserLogin($input)
    {
        $flag = 0;
        $message = "Login Success";
        $data = [];
        $userName = isset($input['username']) ? $input['username'] : '';
        $publicKey = isset($input['public_key']) ? $input['public_key'] : '';
        $res = Db::name('xexhibitors')
                ->field('private_key,authenticate_key,id,unique_id')
                ->where('login_name', $userName)
                ->where('status', 1)
                ->find();
        if ($res) {
//            if ($res['password'] == IAuth::setUsrPassword($pwd)) {
            $authenticateKey = IAuth::aesEncrypt($publicKey, $res['private_key'], config('sys_auth.AES_IV'));
            if($authenticateKey == $res['authenticate_key']){
                $flag = 1;
                IAuth::setSessionUserCurrLogged($res['id']);
                $result = IAuth::createToken(['id'=>$res['id']]);
                $data['token'] = $result['data'];
                MyRedis::getInstance()->setEx('token_'.$res['id'],$data['token'],config('redis.one_day'));
                $user = $this->exhibitorModel->getCmsDataByID($res['id']);
                $data['user'] = $user;
                //save device info
//                $input['user_id'] = $res['id'];
//                $deviceRes = $this->deviceModel->addData($input);
//                if($deviceRes['tag']){
//                    $message = "success";
//                }else{
//                    $flag = 200;
//                    $message = $deviceRes['message'];
//                }
            } else {
                $message = "Login Failed,Please check your password";
            }
        } else {
            $message = "username is not exist or invalid";
        }

        return [
            'tag' => $flag,
            'message' => $message,
            'data' => $data
        ];
    }

    public function getUserInfo(Request $request) {
        if($request->isPost()){
            $uid = $request->param('uid');
            $user = $this->userModel->getUserByUid($uid);
            $order = $this->orderModel->getOrderByUserId($uid);
            $data = ['user'=>$user,'order'=>$order];
            return showMsg(1, 'ok',$data);
        }else{
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function changeFn(Request $request){
        echo '';
    }

    public function checkin(Request $request){
        $code = $request->param('code');
        $v = $request->param('v');
        $zoneName = $request->param('zoneName');
        $eventCode = $request->param('eventCode');
        $fnData = ['eventCode'=>$eventCode,'zoneName'=>$zoneName];
        if(md5($code."QSR") != $v){
            return redirect("/changeFn?fn=".json_encode($fnData)."&timeout=3&result=2&userLevel=0");
        }
        if(empty($eventCode)){
            return redirect("/changeFn?fn=".json_encode($fnData)."&timeout=3&result=2&userLevel=0");
        }
        $event = $this->eventModel->getCmsEventByCode($eventCode);
        if(empty($event)){
            return redirect("/changeFn?fn=".json_encode($fnData)."&timeout=3&result=2&userLevel=0");
        }
        $id = $this->userDataModel->getUserIdBySerialNumber($event['id'],$code);
        if(empty($id)){
            // 判断是否输入的是 Badge Name
            $zoneID = $this->zoneModel->getIdByName($event['id'],$zoneName);
            $res = $this->userDataModel->getUserIdByBadgeName($event['id'],$zoneID,$code);
            if(empty($res)){
                return redirect("/changeFn?fn=".json_encode($fnData)."&timeout=3&result=2&userLevel=0");
            }else if(count($res) == 1){
                $id = $res[0]['user_id'];
            }else{
                return $this->getHtmlUserList($eventCode,$zoneName,$res);
            }
        }
        $res = $this->userModel->getCmsDataByID($id);
        if(empty($res)){
            return redirect("/changeFn?fn=".json_encode($fnData)."&timeout=3&result=2&userLevel=0");
        }
        $userStatus = $this->userStatusModel->getDataByDay($id,date('Y-m-d',time()));
        $checkinStatus = !empty($userStatus) ? $userStatus['checkin_status'] : 0;
        $data = $this->userTableModel->getUserTables($res['event_id'],$id);
        $tables = [];
        $zoneTables = [];
        $userZoneExist = false;
        if($data){
            foreach($data as $v){
                $tables[] = $v['zone_name'].' - '.$v['table_name'];
                if(!empty($zoneName) && $zoneName == $v['zone_name']){
                    $userZoneExist = true;
                    $zoneTables[] = $v['table_name'];
                }
            }
        }
        $table = implode("|",$tables);
        $zoneTable = implode("|",$zoneTables);
        $config = $this->configModel->getCmsData($res['event_id']);
        if(!empty($config)){
            $text = $config['app_text'];
            if($event['enable_track'] == '1' && $userZoneExist){
                $text = $config['app_track_text'];
            }
            $matches = [];
            preg_match_all('/\[\%(.*)\%\]/U',$text,$matches);
            $keywords = $matches[1];
            foreach($keywords as $keyword){
                if($keyword == 'table'){
                    if($event['enable_track'] == '1' && $userZoneExist){
                        $text = str_replace('[%'.$keyword.'%]',$zoneTable,$text);
                    }else{
                        $text = str_replace('[%'.$keyword.'%]',$table,$text);
                    }
                }else{
                    $key = $this->dataFieldModel->getKeyByName($keyword);
                    $userData = $this->userDataModel->getCmsData($id, $key);
                    $value = isset($userData['value'])?$userData['value']:'';
                    $text = str_replace('[%'.$keyword.'%]',$value,$text);
                }
            }
            $fnData['text'] = $text;
        }
        $res = $this->userDataModel->getCmsData($id,'user_level');
        $userLevel = isset($res['value'])?$res['value']:0;
        $fn = json_encode($fnData);
        $timeout = !empty($config)&&!empty($config['show_time'])?$config['show_time']:6;
        if($event['enable_track'] == '1' && !empty($zoneName)){
            if($userZoneExist){
                $zoneID = $this->zoneModel->getIdByName($event['id'],$zoneName);
                $track = $this->trackModel->getCmsDataByDay($id,$zoneID,date('Y-m-d',time()));
                if(empty($track) && time() >= strtotime($event['start_time']) && time() <= strtotime($event['end_time'])){
                    $this->trackModel->attendUser($id,$zoneID,-1,
                        date('Y-m-d H:i:s',time()),1,
                        date('Y-m-d',time()),$event['id']);
                    // generate digital card
                    Db::name('xtasks')->insert([
                        'id'=>Tools::create_guid(),
                        'name'=>'generate_digital_card',
                        'data'=>json_encode(['user_id'=>$id,'event_id'=>$event['id']]),
                        'status'=>0,
                        'create_time'=>Date('Y-m-d H:i:s',time())
                    ]);
                    return redirect("/changeFn?fn=".$fn."&timeout=".$timeout."&result=1&userLevel=".$userLevel);
                }else{
                    return redirect("/changeFn?fn=".$fn."&timeout=".$timeout."&result=3&userLevel=".$userLevel);
                }
            }else{
                return redirect("/changeFn?fn=".$fn."&timeout=3&result=2&userLevel=0");
            }
        }else{
            if($checkinStatus==1){
                return redirect("/changeFn?fn=".$fn."&timeout=".$timeout."&result=3&userLevel=".$userLevel);
            }else{
//                $this->userModel->updateUserAttend($id,-1);
                $this->userStatusModel->addData([
                    'user_id'=>$id,
                    'day'=>date('Y-m-d'),
                    'event_id'=>$event['id'],
                    'checkin_status'=>1,
                    'op_user_id'=>-1,
                    'checkin_time'=>date('Y-m-d H:i:s',time())
                ]);
                // generate digital card
                Db::name('xtasks')->insert([
                    'id'=>Tools::create_guid(),
                    'name'=>'generate_digital_card',
                    'data'=>json_encode(['user_id'=>$id,'event_id'=>$event['id']]),
                    'status'=>0,
                    'create_time'=>Date('Y-m-d H:i:s',time())
                ]);
                return redirect("/changeFn?fn=".$fn."&timeout=".$timeout."&result=1&userLevel=".$userLevel);
            }
        }
    }

    private function getHtmlUserList($eventCode,$zoneName,$list){
        $bgUrl = "static/images/bg.jpg";
        $textAttr = [];
        if(!empty($eventCode)){
            $event = $this->eventModel->getCmsEventByCode($eventCode);
            if(!empty($event)){
                $config = $this->configModel->getCmsData($event['id']);
                if(!empty($config)){
                    if(!empty($config['app_bg_url'])){
                        $bgUrl = $config['app_bg_url'];
                    }
                    if(!empty($zoneName)){
                        if(!empty($config['text_track_attr'])){
                            $textAttr = json_decode($config['text_track_attr'],true);
                        }
                    }else{
                        if(!empty($config['text_attr'])){
                            $textAttr = json_decode($config['text_attr'],true);
                        }
                    }
                }
            }
        }
        $html = "<html><head>";
        $html .= "<style>@font-face{font-family: 'Frutiger';src: url('static/fonts/Frutiger-LT-45-Light.ttf');font-weight: normal;font-style: normal}";
        if($textAttr){
            foreach($textAttr as $v){
                if(!empty($v['font_family']) && !empty($v['font_file'])){
                    $html .= "@font-face{font-family: '".$v['font_family']."';src: url('".$v['font_file']."');font-weight: normal;font-style: normal}";
                }
            }
        }
        $html .= "body{width:100%;height: 100%;background:url('".$bgUrl."') no-repeat;background-size: contain;margin:0}";
        $html .= "table{width:80%;margin:0 auto;} table tr{text-align:center;height:60px;";
        $html .= "color: ".($textAttr && count($textAttr) > 2 && !empty($textAttr[2]['text_color'])?$textAttr[2]['text_color']:'#000').";";
        $html .= "font-size: ".($textAttr && count($textAttr) > 2 && !empty($textAttr[2]['text_size'])?$textAttr[2]['text_size']:'25')."px;";
        $html .= "font-family: \"".($textAttr && count($textAttr) > 2 && !empty($textAttr[2]['font_family'])?$textAttr[2]['font_family']:'Frutiger')."\";";
        $html .= "} table tr:active{background:gold;color:black;}</style>";
        $html .= "</head><body>";
        $html .= "<div style='width:100%;height: 100%;display: flex;flex-direction: column;justify-content: center;align-items: center;'>";
        $html .= "<table border='1'><thead><tr><th>ID</th><th>Badge Name</th></tr></thead><tbody>";
        foreach($list as $v){
            $html .= "<tr ontouchstart='()=>{}' onclick='window.location.href=\"/getCode?code=".$v['badge_name']."\"'><td>".$v['user_id']."</td><td>".$v['badge_name']."</td></tr>";
        }
        $html .= "</tbody></table>";
        $fnData = ['eventCode'=>$eventCode,'zoneName'=>$zoneName];
        $html .= "<div style='margin: 100px auto 20px;font-size: 40px;text-align:center;width: 200px;height: 80px;line-height: 80px;border-radius: 10px;background-color: black;color: gold;border: 1px solid #ccc;' onclick='window.location.href = \"/changeFn?fn=".urlencode(json_encode($fnData))."&timeout=0&result=&userLevel=1\"'>Back</div>";
        $html .= "</div>";
        $html .= "</body></html>";
        echo $html;
    }

    public function logo(Request $request){
        $fn = $request->param('fn');
        $result = $request->param('result');
        $fnData = json_decode($fn,true);
        $eventCode = $fnData['eventCode'];
        $bgUrl = "static/images/bg.jpg";
        $textAttr = [];
        $failText = '';
        $tipPosition = 10;
        if(!empty($eventCode)){
            $event = $this->eventModel->getCmsEventByCode($eventCode);
            if(!empty($event)){
                $config = $this->configModel->getCmsData($event['id']);
                if(!empty($config)){
                    if(!empty($config['app_bg_url'])){
                        $bgUrl = $config['app_bg_url'];
                    }
                    if(isset($fnData['zoneName']) && !empty($fnData['zoneName'])){
                        if(!empty($config['text_track_attr'])){
                            $textAttr = json_decode($config['text_track_attr'],true);
                        }
                    }else{
                        if(!empty($config['text_attr'])){
                            $textAttr = json_decode($config['text_attr'],true);
                        }
                    }
                    if(!empty($config['fail_text'])){
                        $failText = $config['fail_text'];
                    }
                    if(!empty($config['tip_position'])){
                        $tipPosition = $config['tip_position'];
                    }
                }
            }
        }
        $html = "<html><head>";
        $html .= "<style>@font-face{font-family: 'Frutiger';src: url('static/fonts/Frutiger-LT-45-Light.ttf');font-weight: normal;font-style: normal}";
        if($textAttr){
            foreach($textAttr as $v){
                if(!empty($v['font_family']) && !empty($v['font_file'])){
                    $html .= "@font-face{font-family: '".$v['font_family']."';src: url('".$v['font_file']."');font-weight: normal;font-style: normal}";
                }
            }
        }
        $html .= "body{width:100%;height: 100%;background:url('".$bgUrl."') no-repeat;background-size: contain;margin:0}";
        $html .= ".container{width:100%;height:50%;display: flex;justify-content: center;align-items: center;position: relative}img{position: absolute;right: 10px;top: 10px}.table{position: absolute;right: 120px;top: 10px;font-size: 35px;font-weight:bold;height: 100px;line-height: 100px}</style>";
        $html .= "</head><body>";
        $html .= "<div style='";
        $html .= "color: ".($textAttr && count($textAttr) > 0 && !empty($textAttr[0]['text_color'])?$textAttr[0]['text_color']:'#000').";";
        $html .= "font-size: ".($textAttr && count($textAttr) > 0 && !empty($textAttr[0]['text_size'])?$textAttr[0]['text_size']:'25')."px;";
        $html .= "font-family: \"".($textAttr && count($textAttr) > 0 && !empty($textAttr[0]['font_family'])?$textAttr[0]['font_family']:'Frutiger')."\";";
        $html .= "display: flex;align-items: center;justify-content: center;width: 100%;height: 100%;margin-top: ".$tipPosition."%'>Please scan your QR code below</div>";
        $html .= "</body></html>";
        echo $html;
    }

    public function result(Request $request){
        $fn = $request->param('fn');
        $result = $request->param('result');
        $fnData = json_decode($fn,true);
        $eventCode = $fnData['eventCode'];
        $bgUrl = "static/images/bg.jpg";
        $textAttr = [];
        $failText = '';
        $tipPosition = 10;
        $appPosition = 10;
        $failTextPosition = 10;
        if(!empty($eventCode)){
            $event = $this->eventModel->getCmsEventByCode($eventCode);
            if(!empty($event)){
                $config = $this->configModel->getCmsData($event['id']);
                if(!empty($config)){
                    if(!empty($config['app_bg_url'])){
                        $bgUrl = $config['app_bg_url'];
                    }
                    if(isset($fnData['zoneName']) && !empty($fnData['zoneName'])){
                        if(!empty($config['text_track_attr'])){
                            $textAttr = json_decode($config['text_track_attr'],true);
                        }
                        if(!empty($config['app_track_position'])){
                            $appPosition = $config['app_track_position'];
                        }
                    }else{
                        if(!empty($config['text_attr'])){
                            $textAttr = json_decode($config['text_attr'],true);
                        }
                        if(!empty($config['app_reg_position'])){
                            $appPosition = $config['app_reg_position'];
                        }
                    }
                    if(!empty($config['fail_text'])){
                        $failText = $config['fail_text'];
                    }
                    if(!empty($config['tip_position'])){
                        $tipPosition = $config['tip_position'];
                    }
                    if(!empty($config['fail_text_position'])){
                        $failTextPosition = $config['fail_text_position'];
                    }
                }
            }
        }
        $html = "<html><head>";
        $html .= "<style>@font-face{font-family: 'Frutiger';src: url('static/fonts/Frutiger-LT-45-Light.ttf');font-weight: normal;font-style: normal}";
        if($textAttr){
            foreach($textAttr as $v){
                if(!empty($v['font_family']) && !empty($v['font_file'])){
                    $html .= "@font-face{font-family: '".$v['font_family']."';src: url('".$v['font_file']."');font-weight: normal;font-style: normal}";
                }
            }
        }
        $html .= "body{width:100%;height: 100%;background:url('".$bgUrl."') no-repeat;background-size: contain;margin:0}";
        $html .= ".container{width:100%;height:50%;display: flex;justify-content: center;align-items: center;position: relative}img{position: absolute;right: 10px;top: 10px}.table{position: absolute;right: 120px;top: 10px;font-size: 35px;font-weight:bold;height: 100px;line-height: 100px}</style>";
        $html .= "</head><body>";
        if(empty($result)){
            $html .= "<div style='";
            $html .= "color: ".($textAttr && count($textAttr) > 0 && !empty($textAttr[0]['text_color'])?$textAttr[0]['text_color']:'#000').";";
            $html .= "font-size: ".($textAttr && count($textAttr) > 0 && !empty($textAttr[0]['text_size'])?$textAttr[0]['text_size']:'25')."px;";
            $html .= "font-family: \"".($textAttr && count($textAttr) > 0 && !empty($textAttr[0]['font_family'])?$textAttr[0]['font_family']:'Frutiger')."\";";
            $html .= "display: flex;align-items: center;justify-content: center;width: 100%;height: 100%;margin-top: ".$tipPosition."%'>Please scan your QR code below</div>";
        }else{
            if($result == '1' || $result == '3'){
                $html .= "<div style='display: flex;flex-direction: column;align-items: center;justify-content: center;position: absolute;top: 0;bottom: 0;left: 0;right: 0;";
                if($result == '1'){
                    $html .= "border:20px solid green";
                }else{
                    $html .= "border:20px solid blue";
                }
                $html .= "'>";
                if(!empty($fnData['text'])){
                    $arr = explode('|',$fnData['text']);
                    foreach($arr as $k => $v){
                        $html .= "<div style='";
                        $html .= "color: ".($textAttr && count($textAttr) > (3+$k) && !empty($textAttr[3+$k]['text_color'])?$textAttr[3+$k]['text_color']:'#000').";";
                        $html .= "font-size: ".($textAttr && count($textAttr) > (3+$k) && !empty($textAttr[3+$k]['text_size'])?$textAttr[3+$k]['text_size']:'25')."px;";
                        $html .= "font-family: \"".($textAttr && count($textAttr) > (3+$k) && !empty($textAttr[3+$k]['font_family'])?$textAttr[3+$k]['font_family']:'Frutiger')."\";";
                        $v = !empty($v)?$v:'&nbsp;';
                        if($k == 0){
                            $html .= "font-weight: bold;text-align:center;margin-top: ".$appPosition."%'>".$v."</div>";
                        }else{
                            $html .= "font-weight: bold;text-align:center;margin-top: 10px'>".$v."</div>";
                        }
                    }
                }
                $html .= "</div>";
//                $html .= "<div class='container'>";
//                if($result == '1'){
//                    $html .= "<img src='static/images/success.png' width='100' height='100'/></div>";
//                }else{
//                    $html .= "<img src='static/images/dot.png' width='100' height='100'/></div>";
//                }
            }else{
                $html .= "<div style='display: flex;flex-direction: column;align-items: center;justify-content: center;position: absolute;top: 0;bottom: 0;left: 0;right: 0;border:20px solid red'>";
                $html .= "<div style='";
                $html .= "color: ".($textAttr && count($textAttr) > 1 && !empty($textAttr[1]['text_color'])?$textAttr[1]['text_color']:'#000').";";
                $html .= "font-size: ".($textAttr && count($textAttr) > 1 && !empty($textAttr[1]['text_size'])?$textAttr[1]['text_size']:'25')."px;";
                $html .= "font-family: \"".($textAttr && count($textAttr) > 1 && !empty($textAttr[1]['font_family'])?$textAttr[1]['font_family']:'Frutiger')."\";";
                $html .= "font-weight: bold;text-align:center;margin-top: ".$failTextPosition."%'>".$failText."</div>";
                $html .= "</div>";
//                $html .= "<div class='container'><img src='static/images/fail.png' width='100' height='100'/></div>";
            }
        }
        $html .= "</body></html>";
        echo $html;
    }

    public function uploadContact(Request $request) {
        $userId = IAuth::getUserIDCurrLogged();
        if($request->isPost()){
            $contacts = $request->param('contacts');

            return showMsg(1, 'ok',['lastUpdate'=>date('Y-m-d H:i:s',time())]);
        }else{
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }
}
