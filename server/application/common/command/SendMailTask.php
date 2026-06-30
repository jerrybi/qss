<?php


namespace app\common\command;


use app\common\lib\Email;
use app\common\lib\FabricJs;
use app\common\lib\LogUtil;
use app\common\lib\Tools;
use app\common\model\XBadge;
use app\common\model\XBooking;
use app\common\model\XcardTemplates;
use app\common\model\Xcompanies;
use app\common\model\XDynamicForm;
use app\common\model\XedmTemplates;
use app\common\model\Xevents;
use app\common\model\Xconfigs;
use app\common\model\Xlocations;
use app\common\model\XmailSettings;
use app\common\model\XmanPower;
use app\common\model\XuserDatas;
use app\common\model\Xusers;
use app\common\model\Xzones;
use app\common\model\Xvendors;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\Db;
use think\facade\Env;
use think\Request;

class SendMailTask extends Command
{
    protected function configure()
    {
        $this->setName('think:fpg50_send_mail_task')->setDescription('this is fpg50_send_mail_task');
    }

    protected function execute(Input $input, Output $output)
    {
        while (true){
            //策略是执行失败的任务每天最多只能执行三次
            $list = Db::name('xtasks')->where('status',0)->select();
            foreach ($list as $item){
                switch($item['name']){
                    case 'generate_digital_card':
                        {
                            if(!empty($item['update_time'])
                                &&($item['total_count'] >= 10 ||
                                    (Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())
                                        && $item['count'] >= 3)
                                )){
                                //同时满足这三个条件说明今天已执行过三次，不能执行了
                            }else {
                                $data = json_decode($item['data'], true);
                                $rs = $this->sendDigitalCardEmail($data['user_id'], $data['event_id']);
                                if (isset($rs) && $rs['status']) {
                                    Db::name('xtasks')->where('id', $item['id'])
                                        ->update(['status' => 1, 'update_time' => Date('Y-m-d H:i:s', time())]);
                                }else{
                                    if(empty($item['update_time'])){
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time()),'count'=>1,'total_count'=>1]);
                                    }else if(!Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())){
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->inc('total_count')
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time()),'count'=>1]);
                                    } else{
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->inc('count')
                                            ->inc('total_count')
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time())]);
                                    }
                                }
                            }
                        }
                        break;
                    case 'exhibitor_send_mail':
                        {
                            if(!empty($item['update_time'])
                                &&($item['total_count'] >= 10 ||
                                    (Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())
                                        && $item['count'] >= 3)
                                )){
                                //同时满足这三个条件说明今天已执行过三次，不能执行了
                            }else{
                                $data = json_decode($item['data'],true);
                                $type = $data['type'];
                                $userId = $data['user_id'];
                                $formId = $data['form_id'];
                                if($type == 'Amenity' || $type == 'Marketing'){
                                    $rs = $this->sendExhibitorConfirmationMail($type,$userId,$formId);
                                }else if($type == 'Badge'){
                                    $rs = $this->sendExhibitorConfirmationMailBadge($userId,$formId);
                                }else if($type == 'Booking'){
                                    $rs = $this->sendExhibitorConfirmationMailBooking($userId,$formId);
                                }else if($type == 'Manpower'){
                                    $rs = $this->sendExhibitorConfirmationMailManpower($userId,$formId);
                                }
                                if (isset($rs) && $rs['status']) {
                                    Db::name('xtasks')->where('id',$item['id'])
                                        ->update(['status'=>1,'update_time'=>Date('Y-m-d H:i:s',time())]);
                                }else{
                                    if(empty($item['update_time'])){
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time()),'count'=>1,'total_count'=>1]);
                                    }else if(!Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())){
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->inc('total_count')
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time()),'count'=>1]);
                                    } else{
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->inc('count')
                                            ->inc('total_count')
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time())]);
                                    }
                                }
                            }
                        }
                        break;
                    case 'exhibitor_update_password':
                        {
                            if(!empty($item['update_time'])
                                &&($item['total_count'] >= 10 ||
                                    (Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())
                                        && $item['count'] >= 3)
                                )){
                                //同时满足这三个条件说明今天已执行过三次，不能执行了
                            }else {
                                $data = json_decode($item['data'], true);
                                $rs = $this->sendExhibitorUpdatePasswordEmail($data['user_id'], $data['title'], $data['email'], $data['password'], $data['domain']);
                                if (isset($rs) && $rs['status']) {
                                    Db::name('xtasks')->where('id', $item['id'])
                                        ->update(['status' => 1, 'update_time' => Date('Y-m-d H:i:s', time())]);
                                }else{
                                    if(empty($item['update_time'])){
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time()),'count'=>1,'total_count'=>1]);
                                    }else if(!Tools::isSameDay(getdate(strtotime($item['update_time'])),getdate())){
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->inc('total_count')
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time()),'count'=>1]);
                                    } else{
                                        Db::name('xtasks')->where('id',$item['id'])
                                            ->inc('count')
                                            ->inc('total_count')
                                            ->update(['update_time'=>Date('Y-m-d H:i:s',time())]);
                                    }
                                }
                            }
                        }
                        break;
                }
            }
            sleep(60);
        }
    }

    private function sendExhibitorConfirmationMail($type,$userId,$formId){
        $userModel = new Xusers();
        $formModel = new Xzones();
        $companyModel =new Xcompanies();
        $dynamicFormModel = new XDynamicForm();
        $user = $userModel->getUserByUid($userId);
        $form = $formModel->getCmsDataByID($formId);
        $formData = $dynamicFormModel->getLastDataWithCatalog($form['event_id'],$user['company_id'],$formId);
        $dynamicData = $dynamicFormModel->getLastData($form['event_id'],$user['company_id'],$formId,1);
        $company = $companyModel->getCmsDataByID($user['company_id']);
        $orderId = 0;
        $to = $user['first_name'].' '.$user['last_name'];
        if(!empty($formData)){
            $orderId = $formData[0]['id'];
            $submitName = $formData[0]['form_submit_name'];
            $submitDesignation = $formData[0]['form_submit_designation'];
            $submitEmail = $formData[0]['form_submit_email'];
        }else if(!empty($dynamicData)){
            $orderId = $dynamicData[0]['id'];
            $submitName = $dynamicData[0]['form_submit_name'];
            $submitDesignation = $dynamicData[0]['form_submit_designation'];
            $submitEmail = $dynamicData[0]['form_submit_email'];
        }else{
            $submitName = $to;
            $submitDesignation = $to;
            $submitEmail = $user['email'];
        }
        $title = "Notification for form ".$form['name'];
        $content = "<div style=\"text-align:center;\">";
        $content .= "<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:0 auto;\"><tbody><tr><td>";
        $content .= "<div style=\"width:1000px;text-align:left;font-size:16px;color:#000;background:#fff;\">";
        $content .= $form['header_content'];
        $content .= "<div style='height: 25px;line-height: 25px'>Dear ".$to.",</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Thank you for submitting the below order.</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Your order transaction reference: TF-".date("Ymd",time())."-ORD".sprintf("%04d",$orderId)."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Date: ".date("d/M/Y",time())."</div><br/>";
        $content .= "<div style='width: 100%;height: 40px;line-height: 40px;background-color: #283560;color: white;padding: 10px;'>Form Name: ".$form['name']."</div>";
        $content .= "<div style='margin-top: 10px;font-weight: bold;height: 25px;line-height: 25px'>Submitter:</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Submitted by: ".$submitName."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Company: ".$company['name']."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Designation: ".$submitDesignation."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Email: ".$submitEmail."</div>";
        $content .= $form['instruction'];
        if($form['have_item'] == '1'){
            $content .= "<table style='border: 1px solid #ddd;margin-bottom: 10px'>";
            $content .= "<colgroup>";
            $content .= "<col width=\"10%\">";
//            $content .= "<col width=\"10%\">";
//            $content .= "<col width=\"10%\">";
//            $content .= "<col width=\"10%\">";
            $content .= "<col width=\"15%\">";
            $content .= "<col width=\"15%\">";
            $content .= "<col width=\"15%\">";
            $content .= "<col width=\"15%\">";
            $content .= "<col width=\"10%\">";
            $content .= "<col width=\"10%\">";
            $content .= "<col width=\"10%\">";
            $content .= "</colgroup>";
            $content .= "<thead style='height: 25px;line-height: 25px;background-color: #283560;color: white'><tr>";
            $content .= "<th>Item Code</th>";
//            $content .= "<th>Type</th>";
//            $content .= "<th>Category</th>";
//            $content .= "<th>Sub-Category</th>";
            $content .= "<th>Description Services</th>";
            $content .= "<th>Advanced Rate(SGD)</th>";
            $content .= "<th>Standard Rate(SGD)</th>";
            $content .= "<th>Onsite Rate(SGD)</th>";
            $content .= "<th>Image</th>";
            $content .= "<th>Qty</th>";
            $content .= "<th>Cost</th>";
            $content .= "</tr></thead>";
            $content .= "<tbody>";
            $total = 0.0;
            foreach($formData as $item){
                if($item['item_quantity'] > 0){
                    $content .= "<tr>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['name']."</td>";
//                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['type']."</td>";
//                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['category']."</td>";
//                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['sub_category']."</td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['description']."</td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['advanced_rate']."</td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['standard_rate']."</td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".($item['have_onsite_rate']?$item['onsite_rate']:'-')."</td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'><img src='".$item['logo']."' width='50'></td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_quantity']."</td>";
                    $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".($item['item_quantity']*$item['item_price'])."</td>";
                    $content .= "</tr>";
                    $total += $item['item_quantity']*$item['item_price'];
                }
            }
            $content .= "<tr>";
            $content .= "<td colspan=\"7\" style=\"text-align: right;font-weight: bold\">Sub Total:</td>";
            $content .= "<td style='text-align: center;'>".$total."</td>";
            $content .= "</tr>";
            $content .= "</tbody></table>";
        }
        $content .= $form['cost_notes'];
        $content .= $form['footer_content'];
        if($form['have_dynamic'] == '1'){
            foreach($dynamicData as $item){
                $content .= "<div style='margin-top: 10px;font-weight: bold'>".$item['dynamic_title']."</div>";
                $content .= "<div>".$item['dynamic_value']."</div>";
            }
        }
        $eventModel = new Xevents();
        $event = $eventModel->getCmsEventByID($user['event_id']);
        $content .= "<div style='margin-top: 20px;height: 25px;line-height: 25px'>We wish you every success and look forward to welcoming you at ".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>The Organiser</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Note: This is a system generated letter. Please do not reply this acknowledgement.</div>";
        $content .= "</div>";
        $content .= "</td></tr></tbody></table>";
        $content .= "</div>";
        $email = $user['email'];
        $exhibitors = $userModel->getUsersByCompanyId($user['company_id']);
        $cc = [];
        if(!empty($exhibitors)){
            foreach($exhibitors as $k=>$v){
                if($v['email'] != $email){
                    $cc[] = ['mail'=>$v['email'],'name'=>$v['first_name'].$v['last_name']];
                }
            }
        }
        $mailSettingModel = new XmailSettings();
        $mailSettings = $mailSettingModel->getCmsData($form['event_id']);
        if(!empty($form['supervisor_email'])){
            $ccs = explode("\r\n",$form['supervisor_email']);
            foreach($ccs as $v){
                if(!empty($v)){
                    $cc[] = ['name'=>Tools::getNameByEmail($v),'mail'=>$v];
                }
            }
        }
        try{
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,$mailSettings['sender_name'],$title,$content,$mailSettings['sender_email'],
                $mailSettings['sender_pwd'],$mailSettings['mail_server'],$mailSettings['mail_port'],$cc);
            return $rs;
        }catch (Exception $e){
            LogUtil::info($type." mail error:".$e->getMessage());
            return ['status'=>0,'msg'=>"Send email fail: " . $e->getMessage()];
        }
    }

    private function sendExhibitorConfirmationMailBadge($userId,$formId){
        $userModel = new Xusers();
        $formModel = new Xzones();
        $badgeModel = new XBadge();
        $companyModel =new Xcompanies();
        $user = $userModel->getUserByUid($userId);
        $form = $formModel->getCmsDataByID($formId);
        $formData = $badgeModel->getLastData($form['event_id'],$user['company_id'],$formId);
        $company = $companyModel->getCmsDataByID($user['company_id']);
        $orderId = $formData[0]['id'];
        $title = "Notification for form ".$form['name'];
        $to = $user['first_name'].' '.$user['last_name'];
        if(!empty($formData)){
            $submitName = $formData[0]['form_submit_name'];
            $submitDesignation = $formData[0]['form_submit_designation'];
            $submitEmail = $formData[0]['form_submit_email'];
        }else{
            $submitName = $to;
            $submitDesignation = $to;
            $submitEmail = $user['email'];
        }
        $content = "<div style=\"text-align:center;\">";
        $content .= "<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:0 auto;\"><tbody><tr><td>";
        $content .= "<div style=\"width:1000px;text-align:left;font-size:16px;color:#000;background:#fff;\">";
        $content .= $form['header_content'];
        $content .= "<div style='height: 25px;line-height: 25px'>Dear ".$to.",</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Thank you for submitting the below order.</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Your order transaction reference: TF-".date("Ymd",time())."-ORD".sprintf("%04d",$orderId)."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Date: ".date("d/M/Y",time())."</div><br/>";
        $content .= "<div style='width: 100%;height: 40px;line-height: 40px;background-color: #283560;color: white;padding: 10px;'>Form Name: ".$form['name']."</div>";
        $content .= "<div style='margin-top: 10px;font-weight: bold;height: 25px;line-height: 25px'>Submitter:</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Submitted by: ".$submitName."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Company: ".$company['name']."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Designation: ".$submitDesignation."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Email: ".$submitEmail."</div>";
        $content .= $form['instruction'];
        $content .= "<table style='border: 1px solid #ddd;border-bottom: none'>";
        $content .= "<colgroup>";
        $content .= "<col width=\"20%\">";
        $content .= "<col width=\"20%\">";
        $content .= "<col width=\"20%\">";
        $content .= "<col width=\"10%\">";
        $content .= "<col width=\"10%\">";
        $content .= "<col width=\"20%\">";
        $content .= "</colgroup>";
        $content .= "<thead style='height: 25px;line-height: 25px;'><tr>";
        $content .= "<th style='background-color: #283560;color: white'>Salutation/Rank</th>";
        $content .= "<th style='background-color: #283560;color: white'>Name on Badge</th>";
        $content .= "<th style='background-color: #283560;color: white'>Position/Job Title</th>";
        $content .= "<th style='background-color: #283560;color: white'>Company</th>";
        $content .= "<th style='background-color: #283560;color: white'>Country/Region</th>";
        $content .= "<th style='background-color: #283560;color: white'>Email</th>";
        $content .= "</tr></thead>";
        $content .= "<tbody>";
        foreach($formData as $item){
            $content .= "<tr>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['salutation']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['badge_name']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['job_title']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['company']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['country']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['email']."</td>";
            $content .= "</tr>";
        }
        $content .= "</tbody></table>";
        $content .= $form['cost_notes'];
        $content .= $form['footer_content'];
        $eventModel = new Xevents();
        $event = $eventModel->getCmsEventByID($user['event_id']);
        $content .= "<div style='margin-top: 20px;height: 25px;line-height: 25px'>We wish you every success and look forward to welcoming you at ".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>The Organiser</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Note: This is a system generated letter. Please do not reply this acknowledgement.</div>";
        $content .= "</div>";
        $content .= "</td></tr></tbody></table>";
        $content .= "</div>";
        $email = $user['email'];
        $exhibitors = $userModel->getUsersByCompanyId($user['company_id']);
        $cc = [];
        if(!empty($exhibitors)){
            foreach($exhibitors as $k=>$v){
                if($v['email'] != $email){
                    $cc[] = ['mail'=>$v['email'],'name'=>$v['first_name'].$v['last_name']];
                }
            }
        }
        $mailSettingModel = new XmailSettings();
        $mailSettings = $mailSettingModel->getCmsData($form['event_id']);
        if(!empty($form['supervisor_email'])){
            $ccs = explode("\r\n",$form['supervisor_email']);
            foreach($ccs as $v){
                if(!empty($v)){
                    $cc[] = ['name'=>Tools::getNameByEmail($v),'mail'=>$v];
                }
            }
        }
        try{
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,$mailSettings['sender_name'],$title,$content,
                $mailSettings['sender_email'], $mailSettings['sender_pwd'],$mailSettings['mail_server'],$mailSettings['mail_port'],$cc);
            return $rs;
        }catch (Exception $e){
            LogUtil::info("Badge mail error:".$e->getMessage());
            return ['status'=>0,'msg'=>"Send email fail: " . $e->getMessage()];
        }
    }

    private function sendExhibitorConfirmationMailBooking($userId,$formId){
        $userModel = new Xusers();
        $formModel = new Xzones();
        $bookingModel = new XBooking();
        $locationModel = new Xlocations();
        $companyModel =new Xcompanies();
        $user = $userModel->getUserByUid($userId);
        $form = $formModel->getCmsDataByID($formId);
        $formData = $bookingModel->getLastData($form['event_id'],$user['company_id'],$formId);
        $company = $companyModel->getCmsDataByID($user['company_id']);
        $orderId = $formData[0]['id'];
        $title = "Notification for form ".$form['name'];
        $to = $user['first_name'].' '.$user['last_name'];
        if(!empty($formData)){
            $submitName = $formData[0]['form_submit_name'];
            $submitDesignation = $formData[0]['form_submit_designation'];
            $submitEmail = $formData[0]['form_submit_email'];
        }else{
            $submitName = $to;
            $submitDesignation = $to;
            $submitEmail = $user['email'];
        }
        $content = "<div style=\"text-align:center;\">";
        $content .= "<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:0 auto;\"><tbody><tr><td>";
        $content .= "<div style=\"width:1000px;text-align:left;font-size:16px;color:#000;background:#fff;\">";
        $content .= $form['header_content'];
        $content .= "<div style='height: 25px;line-height: 25px'>Dear ".$to.",</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Thank you for submitting the below order.</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Your order transaction reference: TF-".date("Ymd",time())."-ORD".sprintf("%04d",$orderId)."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Date: ".date("d/M/Y",time())."</div><br/>";
        $content .= "<div style='width: 100%;height: 40px;line-height: 40px;background-color: #283560;color: white;padding: 10px;'>Form Name: ".$form['name']."</div>";
        $content .= "<div style='margin-top: 10px;font-weight: bold;height: 25px;line-height: 25px'>Submitter:</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Submitted by: ".$submitName."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Company: ".$company['name']."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Designation: ".$submitDesignation."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Email: ".$submitEmail."</div>";
        $content .= $form['instruction'];
        $content .= $form['cost_notes'];
        $content .= $form['footer_content'];

        $content .= "<div style='margin-top: 10px;font-weight: bold'>Presentation Date</div>";
        $content .= "<div>".$formData[0]['presentation_date']."</div>";
        $content .= "<div style='margin-top: 10px;font-weight: bold'>Presentation Time</div>";
        $content .= "<div>".$formData[0]['presentation_time']."</div>";
        if($form['have_location'] == '1'){
            $location = $locationModel->getCmsDataByID($formData[0]['location_id']);
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Location</div>";
            $content .= "<div>".$location['name']."</div>";
        }else{
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Is New Product</div>";
            $content .= "<div>".($formData[0]['is_new_product']=='1'?'Yes':'No')."</div>";
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Title</div>";
            $content .= "<div>".$formData[0]['title']."</div>";
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Synopsis</div>";
            $content .= "<div>".$formData[0]['synopsis']."</div>";
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Product Image</div>";
            $content .= "<div><img src='".$formData[0]['product_img_url']."' width='100'></div>";
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Speaker CV</div>";
            $content .= "<div>".$formData[0]['speaker_cv']."</div>";
            $content .= "<div style='margin-top: 10px;font-weight: bold'>Speaker Image</div>";
            $content .= "<div><img src='".$formData[0]['speaker_img_url']."' width='100'></div>";
        }
        $eventModel = new Xevents();
        $event = $eventModel->getCmsEventByID($user['event_id']);
        $content .= "<div style='margin-top: 20px;height: 25px;line-height: 25px'>We wish you every success and look forward to welcoming you at ".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>The Organiser</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Note: This is a system generated letter. Please do not reply this acknowledgement.</div>";
        $content .= "</div>";
        $content .= "</td></tr></tbody></table>";
        $content .= "</div>";
        $email = $user['email'];
        $exhibitors = $userModel->getUsersByCompanyId($user['company_id']);
        $cc = [];
        if(!empty($exhibitors)){
            foreach($exhibitors as $k=>$v){
                if($v['email'] != $email){
                    $cc[] = ['mail'=>$v['email'],'name'=>$v['first_name'].$v['last_name']];
                }
            }
        }
        $mailSettingModel = new XmailSettings();
        $mailSettings = $mailSettingModel->getCmsData($form['event_id']);
        if(!empty($form['supervisor_email'])){
            $ccs = explode("\r\n",$form['supervisor_email']);
            foreach($ccs as $v){
                if(!empty($v)){
                    $cc[] = ['name'=>Tools::getNameByEmail($v),'mail'=>$v];
                }
            }
        }
        try{
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,$mailSettings['sender_name'],$title,$content,
                $mailSettings['sender_email'], $mailSettings['sender_pwd'],$mailSettings['mail_server'],$mailSettings['mail_port'],$cc);
            return $rs;
        }catch (Exception $e){
            LogUtil::info("Booking mail error:".$e->getMessage());
            return ['status'=>0,'msg'=>"Send email fail: " . $e->getMessage()];
        }
    }

    private function sendExhibitorConfirmationMailManpower($userId,$formId){
        $userModel = new Xusers();
        $formModel = new Xzones();
        $manpowerModel = new XmanPower();
        $companyModel =new Xcompanies();
        $user = $userModel->getUserByUid($userId);
        $form = $formModel->getCmsDataByID($formId);
        $formData = $manpowerModel->getLastData($form['event_id'],$user['company_id'],$formId);
        $company = $companyModel->getCmsDataByID($user['company_id']);
        $orderId = $formData[0]['id'];
        $title = "Notification for form ".$form['name'];
        $to = $user['first_name'].' '.$user['last_name'];
        if(!empty($formData)){
            $submitName = $formData[0]['form_submit_name'];
            $submitDesignation = $formData[0]['form_submit_designation'];
            $submitEmail = $formData[0]['form_submit_email'];
        }else{
            $submitName = $to;
            $submitDesignation = $to;
            $submitEmail = $user['email'];
        }
        $content = "<div style=\"text-align:center;\">";
        $content .= "<table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"margin:0 auto;\"><tbody><tr><td>";
        $content .= "<div style=\"width:1000px;text-align:left;font-size:16px;color:#000;background:#fff;\">";
        $content .= $form['header_content'];
        $content .= "<div style='height: 25px;line-height: 25px'>Dear ".$to.",</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Thank you for submitting the below order.</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Your order transaction reference: TF-".date("Ymd",time())."-ORD".sprintf("%04d",$orderId)."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Date: ".date("d/M/Y",time())."</div><br/>";
        $content .= "<div style='width: 100%;height: 40px;line-height: 40px;background-color: #283560;color: white;padding: 10px;'>Form Name: ".$form['name']."</div>";
        $content .= "<div style='margin-top: 10px;font-weight: bold;height: 25px;line-height: 25px'>Submitter:</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Submitted by: ".$submitName."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Company: ".$company['name']."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Designation: ".$submitDesignation."</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>Email: ".$submitEmail."</div>";
        $content .= $form['instruction'];
        $content .= "<table style='border: 1px solid #ddd;border-bottom: none'>";
        $content .= "<colgroup>";
        $content .= "<col width=\"40%\">";
        $content .= "<col width=\"20%\">";
        $content .= "<col width=\"10%\">";
        $content .= "<col width=\"10%\">";
        $content .= "<col width=\"10%\">";
        $content .= "<col width=\"10%\">";
        $content .= "</colgroup>";
        $content .= "<thead style='height: 25px;line-height: 25px;'><tr style='background-color: #283560;color: white'>";
        $content .= "<th rowspan=\"2\">Item</th>";
        $content .= "<th rowspan=\"2\">Unit Cost Per Hour</th>";
        $content .= "<th colspan=\"2\">Date</th>";
        $content .= "<th rowspan=\"2\">Duration(Hours per day)</th>";
        $content .= "<th rowspan=\"2\">No. of Staff Required</th>";
        $content .= "</tr>";
        $content .= "<tr style='background-color: #283560;color: white'>";
        $content .= "<th>From</th>";
        $content .= "<th>To</th>";
        $content .= "</tr>";
        $content .= "</thead>";
        $content .= "<tbody>";
        foreach($formData as $item){
            $content .= "<tr>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_name']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_price']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_from_date']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_to_date']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_duration']."</td>";
            $content .= "<td style='text-align: center;border-bottom: 1px solid #ddd'>".$item['item_staff_num']."</td>";
            $content .= "</tr>";
        }
        $content .= "</tbody></table>";
        $content .= $form['cost_notes'];
        $content .= $form['footer_content'];
        $content .= "<div style='margin-top: 10px;font-weight: bold'>Language</div>";
        $content .= "<div>".$formData[0]['language']."</div>";
        $eventModel = new Xevents();
        $event = $eventModel->getCmsEventByID($user['event_id']);
        $content .= "<div style='margin-top: 20px;height: 25px;line-height: 25px'>We wish you every success and look forward to welcoming you at ".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>The Organiser</div>";
        $content .= "<div style='height: 25px;line-height: 25px'>".$event['name']."</div><br/>";
        $content .= "<div style='height: 25px;line-height: 25px'>Note: This is a system generated letter. Please do not reply this acknowledgement.</div>";
        $content .= "</div>";
        $content .= "</td></tr></tbody></table>";
        $content .= "</div>";
        $email = $user['email'];
        $exhibitors = $userModel->getUsersByCompanyId($user['company_id']);
        $cc = [];
        if(!empty($exhibitors)){
            foreach($exhibitors as $k=>$v){
                if($v['email'] != $email){
                    $cc[] = ['mail'=>$v['email'],'name'=>$v['first_name'].$v['last_name']];
                }
            }
        }
        $mailSettingModel = new XmailSettings();
        $mailSettings = $mailSettingModel->getCmsData($form['event_id']);
        if(!empty($form['supervisor_email'])){
            $ccs = explode("\r\n",$form['supervisor_email']);
            foreach($ccs as $v){
                if(!empty($v)){
                    $cc[] = ['name'=>Tools::getNameByEmail($v),'mail'=>$v];
                }
            }
        }
        try{
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,$mailSettings['sender_name'],$title,$content,
                $mailSettings['sender_email'], $mailSettings['sender_pwd'],$mailSettings['mail_server'],$mailSettings['mail_port'],$cc);
            return $rs;
        }catch (Exception $e){
            LogUtil::info("Manpower mail error:".$e->getMessage());
            return ['status'=>0,'msg'=>"Send email fail: " . $e->getMessage()];
        }
    }

    private function sendExhibitorUpdatePasswordEmail($userId,$title,$email,$password,$domain){
        $userModel = new Xusers();
        $user = $userModel->getUserByUid($userId);
        $mailSettingModel = new XmailSettings();
        $mailSetting = $mailSettingModel->getCmsData($user['event_id']);
        if(empty($mailSetting)){
            return ['status'=>0,'msg'=>'Incorrect mail setting!'];
        }
        $homeModel = new Xconfigs();
        $home = $homeModel->getCmsData($user['event_id']);
        if($user['type'] == 0){
            $companyModel = new Xcompanies();
            $company = $companyModel->getCmsDataByID($user['company_id']);
        }else{
            $companyModel = new Xvendors();
            $company = $companyModel->getCmsDataByID($user['company_id']);
        }
        $emailClient = new Email();
        $name = $user['first_name'].' '.$user['last_name'];
//        $htmlContent = '<html><header></header><body>';
//        $htmlContent .= '<img src="'.($mailSetting?$mailSetting['top_banner']:'').'" width="100%">';
//        $htmlContent .= '<p style="margin-top: 20px">Dear '.$name.',</p>';
//        $htmlContent .= '<div style="margin-top:20px;margin-bottom: 20px">'.$home['content'].'</div>';
//        $htmlContent .= '<div>You can access your exhibitor account at: '.$domain.'/exhibitor </div>';
//        $htmlContent .= '<div>Username: '.$user['login_name'].'</div>';
//        $htmlContent .= '<div>Password: '.$password.'</div><br/><br/><br/>';
//        $htmlContent .= '<img src="'.($mailSetting?$mailSetting['bottom_banner']:'').'" width="100%">';
//        $htmlContent .= '</body></html>';
        $htmlContent = $home['content_email'];
        $htmlContent = str_replace("[%company%]",$company['name'],$htmlContent);
        $htmlContent = str_replace("[%recipient%]",$name,$htmlContent);
        $htmlContent = str_replace("[%username%]",$user['login_name'],$htmlContent);
        $htmlContent = str_replace("[%password%]",$password,$htmlContent);
        if($user['type'] == 0){
            $htmlContent = str_replace("[%clienttype%]","exhibitor",$htmlContent);
        }else{
            $htmlContent = str_replace("[%clienttype%]","vendor",$htmlContent);
        }
        $cc = [];
        $rs = $emailClient->sendemailex($name,$email,$mailSetting['sender_name'],$title,$htmlContent,
            $mailSetting['sender_email'],$mailSetting['sender_pwd'],$mailSetting['mail_server'],$mailSetting['mail_port'],$cc);
        return $rs;
    }

    private function getKeyByName($name) {
        $key = strtolower($name);
        $key = str_replace(" ","_",$key);
        return $key;
    }

    private function sendDigitalCardEmail($userId,$eventId){
        $mailSettingModel = new XmailSettings();
        $mailSetting = $mailSettingModel->getCmsData($eventId);
        if(empty($mailSetting)){
            return ['status'=>0,'msg'=>'Incorrect mail setting!'];
        }
        $userDataModel = new XuserDatas();
        $userDatas = $userDataModel->getDataList($userId);
        $userData = [];
        if($userDatas){
            foreach($userDatas as $v){
                $userData[$v['key']] = $v['value'];
            }
        }
        $visitorCategory = isset($userData['visitor_category'])?$userData['visitor_category']:'';
        if(empty($visitorCategory)){
            return ['status'=>0,'msg'=>'Visitor category is empty!'];
        }
        $cardTemplateModel = new XcardTemplates();
        $cardTemplate = $cardTemplateModel->getDataByType($eventId,$visitorCategory);
        if(empty($cardTemplate)){
            return ['status'=>0,'msg'=>'Card template is empty!'];
        }
        $edmTemplateModel = new XedmTemplates();
        if($cardTemplate['double_side'] == '1'){
            $edmTemplate = $edmTemplateModel->getDataByName($eventId,'Digital Card Double');
        }else{
            $edmTemplate = $edmTemplateModel->getDataByName($eventId,'Digital Card Single');
        }
        if(empty($edmTemplate)){
            return ['status'=>0,'msg'=>'EDM template is empty!'];
        }

        $emailClient = new Email();
        $htmlContent = $edmTemplate['content'];
        $fabricJs = new FabricJs();
        $content1 = !empty($cardTemplate['content1'])?$cardTemplate['content1']:'';
        if(!empty($content1)){
            $matches = [];
            preg_match_all('/\[\%(.*)\%\]/U',$content1,$matches);
            $customizeKeywords = $matches[1];
            foreach($customizeKeywords as $v){
                $key = $this->getKeyByName($v);
                $value = isset($userData[$key])?$userData[$key]:'';
                $content1 = str_replace('[%'.$v.'%]',$value,$content1);
            }
            $path = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$userId."_print_front.png";
            $res = $fabricJs->toPNG($content1,$cardTemplate['bg_width']*28.346,$cardTemplate['bg_height']*28.346,$path);
            if($res){
                $webUrl = config('app.web_url').'/images/'.$userId."_print_front.png";
                $htmlContent = str_replace("[%card front%]",$webUrl,$htmlContent);
            }else{
                $htmlContent = str_replace("[%card front%]","",$htmlContent);
            }
        }
        $content2 = !empty($cardTemplate['content2'])?$cardTemplate['content2']:'';
        if(!empty($content2)){
            $matches = [];
            preg_match_all('/\[\%(.*)\%\]/U',$content2,$matches);
            $customizeKeywords = $matches[1];
            foreach($customizeKeywords as $v){
                $key = $this->getKeyByName($v);
                $value = isset($userData[$key])?$userData[$key]:'';
                $content2 = str_replace('[%'.$v.'%]',$value,$content2);
            }
            $path = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$userId."_print_back.png";
            $res = $fabricJs->toPNG($content2,$cardTemplate['bg_width']*28.346,$cardTemplate['bg_height']*28.346,$path);
            if($res){
                $webUrl = config('app.web_url').'/images/'.$userId."_print_back.png";
                $htmlContent = str_replace("[%card back%]",$webUrl,$htmlContent);
            }else{
                $htmlContent = str_replace("[%card front%]","",$htmlContent);
            }
        }
        $htmlContent = str_replace("[%First Name%]",isset($userData['first_name'])?$userData['first_name']:'',$htmlContent);
        $htmlContent = str_replace("[%Last Name%]",isset($userData['last_name'])?$userData['last_name']:'',$htmlContent);
        $cc = [];
        $firstName = isset($userData['first_name'])?$userData['first_name']:'';
        $lastName = isset($userData['last_name'])?$userData['last_name']:'';
        $email = isset($userData['email'])?$userData['email']:'';
        $name = $firstName." ".$lastName;
        $rs = $emailClient->sendemailex($name,$email,$mailSetting['sender_name'],"FairPrice Group Turns 50! ",$htmlContent,
            $mailSetting['sender_email'],$mailSetting['sender_pwd'],$mailSetting['mail_server'],$mailSetting['mail_port'],$cc);
        return $rs;
    }
}