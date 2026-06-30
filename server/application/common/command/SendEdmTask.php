<?php


namespace app\common\command;


use app\common\lib\Email;
use app\common\lib\IAuth;
use app\common\lib\LogUtil;
use app\common\lib\QRCode;
use app\common\lib\Tools;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\Db;
use think\Request;

class SendEdmTask extends Command
{
    protected $templateCache;
    protected $mailConfigCache;
    protected function configure()
    {
        $this->setName('think:qss_send_edm_task')->setDescription('this is qss_send_edm_task');
    }

    //定义任务的几种状态 9：待发送 1：发送成功 2：发送失败 3：邮件地址错误 4：已收件
    protected function execute(Input $input, Output $output)
    {
        while (true){
            $this->templateCache = [];
            $this->mailConfigCache = [];
            //策略是执行失败的任务每天最多只能执行三次
            $item = Db::name('xedm_tasks')->where('status','in',[2,9])
                ->order('update_time asc')
                ->find();
            if (!empty($item)){
                if(!empty($item['update_time'])
                    &&($item['total_count'] >= 10 ||
                        (Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())
                            && $item['count'] >= 3)
                    )){
                    //同时满足这三个条件说明今天已执行过三次，不能执行了，把状态改成失败
                    Db::name('xedm_tasks')->where('id', $item['id'])
                        ->update(['status' => 2, 'update_time' => Date('Y-m-d H:i:s', time())]);
                }else {
                    $userId = $item['user_id'];
                    $templateId = $item['template_id'];
                    $eventId = $item['event_id'];
                    $type = $item['type'];
                    if (isset($this->templateCache[$templateId])) {
                        $template = $this->templateCache[$templateId];
                    } else {
                        $template = Db::name('xedm_templates')->where('id', $templateId)
                            ->where('status', 1)
                            ->find();
                        $this->templateCache[$templateId] = $template;
                    }
                    if (isset($this->mailConfigCache[$eventId])) {
                        $mailSettings = $this->mailConfigCache[$eventId];
                    } else {
                        $mailSettings = Db::name('xmail_settings')->where('event_id', $eventId)->find();
                        $this->mailConfigCache[$eventId] = $mailSettings;
                    }
                    if($type == 'exhibitor'){
                        $rs = $this->sendExhibitorMail($userId, $eventId, $template, $mailSettings,$item['id'],$item['data']);
                    }else{
                        $rs = $this->sendGuestMail($userId, $eventId, $template, $mailSettings,$item['id']);
                    }
                    if (isset($rs) && $rs['status']) {
                        Db::name('xedm_tasks')->where('id', $item['id'])
                            ->update(['status' => 1, 'update_time' => Date('Y-m-d H:i:s', time())]);
                        $this->updateJoinStatus($userId, $eventId, $template);
                    } else {
                        if(isset($rs) && strpos($rs['msg'],'Invalid address') !== false){
                            Db::name('xedm_tasks')->where('id', $item['id'])
                                ->update(['status' => 3, 'update_time' => Date('Y-m-d H:i:s', time())]);
                        }else if (empty($item['update_time'])) {
                            Db::name('xedm_tasks')->where('id', $item['id'])
                                ->update(['update_time' => Date('Y-m-d H:i:s', time()), 'count' => 1, 'total_count' => 1]);
                        } else if (!Tools::isSameDay(getdate(strtotime($item['update_time'])), getdate())) {
                            Db::name('xedm_tasks')->where('id', $item['id'])
                                ->inc('total_count')
                                ->update(['update_time' => Date('Y-m-d H:i:s', time()), 'count' => 1]);
                        } else {
                            Db::name('xedm_tasks')->where('id', $item['id'])
                                ->inc('count')
                                ->inc('total_count')
                                ->update(['update_time' => Date('Y-m-d H:i:s', time())]);
                        }
                    }
                    sleep(5);
                }
            }else{
                sleep(60);
            }
        }
    }

    private function sendGuestMail($userId,$eventId,$template,$mailSettings,$taskId){
        $userDatas = Db::name('xuser_datas')->where('event_id',$eventId)
            ->where('status',1)
            ->where('user_id',$userId)
            ->select();
        $firstName = '';
        $lastName = '';
        $lastNameEdm = '';
        $fullName = '';
        $email = '';
        $serialNumber = '';
        $salutation = '';
        $reception = '0';
        $f1 = '0';
        if($userDatas){
            foreach($userDatas as $v){
                if($v['key'] == 'first_name'){
                    $firstName = $v['value'];
                }
                if($v['key'] == 'last_name'){
                    $lastName = $v['value'];
                }
                if($v['key'] == 'full_name'){
                    $fullName = $v['value'];
                }
                if($v['key'] == 'last_name_edm'){
                    $lastNameEdm = $v['value'];
                }
                if($v['key'] == 'email'){
                    $email = $v['value'];
                }
                if($v['key'] == 'serial_number'){
                    $serialNumber = $v['value'];
                }
                if($v['key'] == 'salutation'){
                    $salutation = $v['value'];
                }
                if($v['key'] == 'reception'){
                    $reception = $v['value'];
                }
                if($v['key'] == 'f1'){
                    $f1 = $v['value'];
                }
            }
        }
        $to = $firstName." ".$lastName;
        $tos[] = $to;
        $emails = config('email.default_receiver');
        $tos = array_merge($tos,$emails);
        $to = implode(";",$tos);
        array_unshift($emails,$email);
        $email = implode(";",$emails);
        $content = $template['content'];
        $content = str_replace('[%First Name%]',$firstName,$content);
        $content = str_replace('[%Last Name%]',$lastName,$content);
        $content = str_replace('[%Full Name%]',$fullName,$content);
        $content = str_replace('[%Last Name EDM%]',$lastNameEdm,$content);
        $content = str_replace('[%serial number%]',urlencode(IAuth::encrypt($serialNumber)),$content);
//        $content = str_replace('[%serial number%]',$serialNumber,$content);
        $content = str_replace('[%Salutation%]',$salutation,$content);
        $content = str_replace('[%task id%]',urlencode(IAuth::encrypt($taskId)),$content);
        if(strpos($content,'[%QR Code%]') !== false){
            $filename = QRCode::create_qrcode($serialNumber,null);
            $url = config('app.web_url').'/qrcode/'.$filename;
            $content = str_replace('[%QR Code%]',$url,$content);
        }
        $templateName = $template['name'];
        $confirmContent = "";
        $attachments = [];
        if($templateName == 'confirmation6'){
            if($reception == '1' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September, and the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Friday, 15 September, to Sunday, 17 September.";
            }else if($reception == '1' && $f1 == '2'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September.";
            }else if($reception == '2' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Friday, 15 September, to Sunday, 17 September.";
            }
        }else if($templateName == 'confirmation7'){
            if($reception == '1' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September, and the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Friday, 15 September.";
            }else if($reception == '1' && $f1 == '2'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September.";
            }else if($reception == '2' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Friday, 15 September.";
            }
        }else if($templateName == 'confirmation8'){
            if($reception == '1' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September, and the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Saturday, 16 September.";
            }else if($reception == '1' && $f1 == '2'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September.";
            }else if($reception == '2' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Saturday, 16 September.";
            }
        }else if($templateName == 'confirmation9'){
            if($reception == '1' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September, and the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Sunday, 17 September.";
            }else if($reception == '1' && $f1 == '2'){
                $confirmContent = "Thank you for your interest in participating in the Neom Global Reception 2023 on Thursday, 14 September.";
            }else if($reception == '2' && $f1 == '1'){
                $confirmContent = "Thank you for your interest in participating in the Neom Sky Suite at the Formula 1 Singapore Grand Prix on Sunday, 17 September.";
            }
        }
//        if($templateName == 'v11'){
//            $attachments = ['Neom Global Reception 2023 - Directional Map.pdf'];
//        }else if($templateName == 'v12' || $templateName == 'v13' || $templateName == 'v14'
//            || $templateName == 'v15'){
//            $attachments = ['Neom F1 Sky Suite - Directional Map.pdf'];
//        }else if($templateName == 'v16' || $templateName == 'v17' || $templateName == 'v18'
//            || $templateName == 'v19' || $templateName == 'v20'){
//            $attachments = ['Neom Global Reception 2023 - Directional Map.pdf','Neom F1 Sky Suite - Directional Map.pdf'];
//        }
//        if($templateName == 'reminder_to_attend' || $templateName == 'reminder_to_register'){
//            $attachments = ['DNC_Factsheet_HK_v1.0.pdf'];
//        }
        $textAttr = $template['text_attr'];
        if(!empty($textAttr)){
            $attr = json_decode($textAttr,true);
            if(!empty($attr)){
                foreach($attr as $v){
                    if(!empty($v['attachment'])){
                        $attachments[] = Tools::get_filename($v['attachment']);
                    }
                }
            }
        }
        $content = str_replace('[%Confirmation Content%]',$confirmContent,$content);
        $subject = $template['subject'];
        $subject = str_replace('[%First Name%]',$firstName,$subject);
        $subject = str_replace('[%Last Name%]',$lastName,$subject);
        $subject = str_replace('[%Full Name%]',$fullName,$subject);
        $subject = str_replace('[%Last Name EDM%]',$lastNameEdm,$subject);
        $subject = str_replace('[%serial number%]',urlencode(IAuth::encrypt($serialNumber)),$subject);
        $subject = str_replace('[%Salutation%]',$salutation,$subject);
        $subject = str_replace('[%task id%]',urlencode(IAuth::encrypt($taskId)),$subject);
        try{
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,$mailSettings['sender_name'],$subject,$content,$mailSettings['sender_email'],
                $mailSettings['sender_pwd'],$mailSettings['mail_server'],$mailSettings['mail_port'],[],$attachments);
            return $rs;
        }catch (Exception $e){
            LogUtil::info("mail error:".$e->getMessage());
            return ['status'=>0,'msg'=>"Send email fail: " . $e->getMessage()];
        }
    }

    private function sendExhibitorMail($userId,$eventId,$template,$mailSettings,$taskId,$data){
        $exhibitor = Db::name('xexhibitors')->where('event_id',$eventId)
            ->where('status',1)
            ->where('id',$userId)
            ->find();
        $firstName = $exhibitor['first_name'];
        $lastName = $exhibitor['last_name'];
        $fullName = $firstName.' '.$lastName;
        $serialNumber = $exhibitor['unique_id'];
        $loginName = $exhibitor['login_name'];
        $email = $exhibitor['email'];
        $to = $firstName." ".$lastName;
        $tos[] = $to;
        $emails = config('email.default_receiver');
        $tos = array_merge($tos,$emails);
        $to = implode(";",$tos);
        array_unshift($emails,$email);
        $email = implode(";",$emails);
        $content = $template['content'];
        $content = str_replace('[%Mail Header%]',$mailSettings['top_banner'],$content);
        $content = str_replace('[%First Name%]',$firstName,$content);
        $content = str_replace('[%Last Name%]',$lastName,$content);
        $content = str_replace('[%Full Name%]',$fullName,$content);
        $content = str_replace('[%Login Name%]',$loginName,$content);
        if(!empty($data)){
            $json = json_decode($data,true);
            $content = str_replace('[%Password%]',$json['pwd'],$content);
        }else{
            $content = str_replace('[%Password%]',config('sys_auth.DEFAULT_PWD'),$content);
        }
        $content = str_replace('[%task id%]',urlencode(IAuth::encrypt($taskId)),$content);
        if(strpos($content,'[%QR Code%]') !== false){
            $filename = QRCode::create_qrcode($serialNumber,null);
            $url = config('app.web_url').'/qrcode/'.$filename;
            $content = str_replace('[%QR Code%]',$url,$content);
        }
        $templateName = $template['name'];
        $confirmContent = "";
        $attachments = [];
        $textAttr = $template['text_attr'];
        if(!empty($textAttr)){
            $attr = json_decode($textAttr,true);
            if(!empty($attr)){
                foreach($attr as $v){
                    if(!empty($v['attachment'])){
                        $attachments[] = Tools::get_filename($v['attachment']);
                    }
                }
            }
        }
        $content = str_replace('[%Confirmation Content%]',$confirmContent,$content);
        $subject = $template['subject'];
        $subject = str_replace('[%First Name%]',$firstName,$subject);
        $subject = str_replace('[%Login Name%]',$loginName,$subject);
        $subject = str_replace('[%Last Name%]',$lastName,$subject);
        $subject = str_replace('[%Full Name%]',$fullName,$subject);
        $subject = str_replace('[%serial number%]',urlencode(IAuth::encrypt($serialNumber)),$subject);
        $subject = str_replace('[%task id%]',urlencode(IAuth::encrypt($taskId)),$subject);
        try{
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,$mailSettings['sender_name'],$subject,$content,$mailSettings['sender_email'],
                $mailSettings['sender_pwd'],$mailSettings['mail_server'],$mailSettings['mail_port'],[],$attachments);
            return $rs;
        }catch (Exception $e){
            LogUtil::info("mail error:".$e->getMessage());
            return ['status'=>0,'msg'=>"Send email fail: " . $e->getMessage()];
        }
    }

    private function updateJoinStatus($userId,$eventId,$template){
        $templateName = $template['name'];
        if($templateName == 'confirmation'){
            Db::name('xuser_datas')->where('event_id',$eventId)
                ->where('status',1)
                ->where('user_id',$userId)
                ->where('key','join')
                ->update([
                    'value'=>5,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
        }else if($templateName == 'reminder_to_register'){
            Db::name('xuser_datas')->where('event_id',$eventId)
                ->where('status',1)
                ->where('user_id',$userId)
                ->where('key','join')
                ->update([
                    'value'=>6,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
        }else if($templateName == 'reminder_to_attend'){
            Db::name('xuser_datas')->where('event_id',$eventId)
                ->where('status',1)
                ->where('user_id',$userId)
                ->where('key','join')
                ->update([
                    'value'=>7,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
        }
    }
}