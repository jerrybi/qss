<?php


namespace app\common\lib;


use Phinx\Util\Util;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    public function  sendemailex($to,$email,$from,$title,$content,$senderMail='',$senderPwd='',$mailServer=''
        ,$serverPort='',$cc='',$attachment=[]){
        $addr = [
            'to'=>$to,
            'email'=>$email,
            'from'=>$from,
            'title'=>$title,
            'content'=>$content,
            'senderEmail'=>$senderMail,
            'senderPwd'=>$senderPwd,
            'mailServer'=>$mailServer,
            'serverPort'=>$serverPort,
            'cc'=>$cc,
            'attachment'=>$attachment
        ];
        return $this->sendemail($addr);
    }

    public function sendemail($addr)
    {
        try{
            $mail = new PHPMailer(true); //建立邮件发送类
            $mail->CharSet = "UTF-8";//设置信息的编码类型
            $mail->IsSMTP(); // 使用SMTP方式发送
            if(!empty($addr['mailServer'])){
                $mail->Host = $addr['mailServer']; //使用163邮箱服务器
            }else{
                $mail->Host = config('email.host'); //使用163邮箱服务器
            }
            $mail->SMTPAuth = true; // 启用SMTP验证功能
            if(!empty($addr['senderEmail'])) {
                $mail->Username = $addr['senderEmail']; //你的163服务器邮箱账号
                $mail->Password = $addr['senderPwd'];               // 163邮箱密码
            }else{
                $mail->Username = config('email.username'); //你的163服务器邮箱账号
                $mail->Password = config('email.password');               // 163邮箱密码
            }
            if (config("email.MAIL_TLS") == '1') {
                $mail->SMTPSecure = "tls";
                if(!empty($addr['serverPort'])){
                    $mail->Port = $addr['serverPort'];//邮箱服务器端口号
                }else{
                    $mail->Port = 587;//邮箱服务器端口号
                }
            }else if (config("email.MAIL_SSL") == '1') {
                $mail->SMTPSecure = "ssl";
                if(!empty($addr['serverPort'])){
                    $mail->Port = $addr['serverPort'];//邮箱服务器端口号
                }else{
                    $mail->Port = 465;//邮箱服务器端口号
                }
            } else {
                $mail->Port = 25;//邮箱服务器端口号
            }
            if(!empty($addr['senderEmail'])){
                $mail->From = $addr['senderEmail']; //邮件发送者email地址
            }else{
                $mail->From = config('email.username'); //邮件发送者email地址
            }
            $emails = explode(';',$addr['email']);
            $tos = explode(';',$addr['to']);
            foreach($emails as $k=>$v){
                $mail->AddAddress($v, $tos[$k]);
            }
            if(!empty($addr['cc'])){
                $ccs = $addr['cc'];
                foreach($ccs as $k=>$v){
                    $mail->addCC($v['mail'],$v['name']);
                }
            }
            if($addr['attachment']){
                foreach($addr['attachment'] as $v){
                    if(file_exists("public/upload/".$v)){

                        $mail->addAttachment("public/upload/".$v);
                    }
                }
            }
            //循环发送给接受者
            $mail->FromName = $addr['from'];//发件人名称
            $mail->IsHTML(true);//是否使用HTML格式
            $mail->Subject = $addr['title']; //邮件标题
            $mail->Body = $addr['content']; //邮件内容，上面设置HTML，则可以是HTML

            if (!$mail->Send()) {
                LogUtil::info("Send email fail: " . $mail->ErrorInfo);
                return ['status'=>0,'msg'=>"Send email fail: " . $mail->ErrorInfo];
            } else {
                return ['status'=>1,'msg'=>'Send email success'];
            }
        } catch (Exception $e){
            LogUtil::info("Send email exception: " . $e->getMessage());
            return ['status'=>0,'msg'=>"Send email exception: " . $e->getMessage()];
        }
    }
}