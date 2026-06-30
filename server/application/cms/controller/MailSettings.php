<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Email;
use app\common\lib\Tools;
use app\common\model\XmailSettings;
use app\common\model\Xevents;
use think\Request;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class MailSettings extends CmsBase
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XmailSettings();
    }

    /**
     * 获取文章列表数据
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $res = $this->model->updateCmsData($request->post());
            return showMsg($res['tag'],$res['message']);
        } else {
            $event_id = $request->param('event_id');
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $article = $this->model->getCmsData($eventId);
            $data = [
                'article' => $article,
                'events'=>$events,
                'event_id'=>$eventId
            ];
            return view('index', $data);
        }
    }

    public function getList(Request $request){
        $event_id = $request->param('event_id');
        $list = $this->model->getList($event_id);
        return showMsg(1,'success',$list);
    }

    public function testSendEmail(Request $request){
        if($request->isPost()){
            $senderName = $request->param('sender_name');
            $senderMail = $request->param('sender_email');
            $senderPwd = $request->param('sender_pwd');
            $mailServer = $request->param('mail_server');
            $serverPort = $request->param('mail_port');
            $sendType = $request->param('send_type');
            $recevierMail = $request->param('receiver_mail');
            $supervisorEmail = '';
            $emailClient = new Email();
            $content = '<html>Hello,this is a test email</html>';
            if($sendType == '1'){
                $fromName = $senderName;
            }else{
                $fromName = 'Digital Card';
                $senderMail = '';
            }
            $name = Tools::getNameByEmail($recevierMail);
            $cc = [];
            if(!empty($supervisorEmail)){
                $ccs = explode("\r\n",$supervisorEmail);
                foreach($ccs as $v){
                    if(!empty($v)){
                        $cc[] = ['name'=>Tools::getNameByEmail($v),'mail'=>$v];
                    }
                }
            }
            $rs = $emailClient->sendemailex($name,$recevierMail,$fromName,'Test Email',$content,$senderMail,$senderPwd,$mailServer,$serverPort,$cc);
            if (!$rs['status']) {
                return showMsg(0,$rs['msg']);
            }
            return showMsg(1,'Send test email successfully!');
        }else{
            return showMsg(0,'sorry,your request is invalid！');
        }
    }
}
