<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;
use app\common\lib\Email;
use app\common\lib\LogUtil;
use app\common\model\Xbrochures;
use app\common\model\XdownloadRecords;
use app\common\model\Xedms;
use app\common\model\Xproducts;
use app\common\model\Xusers;
use app\common\model\XscanRecords;
use app\common\model\Xvisitors;
use think\Db;
use think\Request;
use app\common\controller\ApiBase;
use app\common\model\XadminRoles;

use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\lib\MyRedis;

/**
 * Description of Config
 *
 * @author 冬明
 */
class Index extends ApiBase{
    protected $whitelist = ['company','date','recipient'];
    protected $productModel;
    protected $brochureModel;
    protected $userModel;
    protected $scanRecordModel;
    protected $downloadRecordModel;
    protected $edmModel;
    protected $visitorModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Xproducts();
        $this->brochureModel = new Xbrochures();
        $this->userModel = new Xusers();
        $this->scanRecordModel = new XscanRecords();
        $this->downloadRecordModel = new XdownloadRecords();
        $this->edmModel = new Xedms();
        $this->visitorModel = new Xvisitors();
    }

    public function getProductList(Request $request){
        if ($request->isPost()) {
            $products = $this->productModel->select();
            return showMsg(1,'success',$products);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function getProduct(Request $request){
        if ($request->isPost()) {
            $code = $request->param('code');
            $product = $this->productModel->getProduct($code);
            return showMsg(1,'success',$product);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function updateBrochure(Request $request){
        if($request->isPost()){
            $uid = $request->param('uid');
            $index = $request->param('index');
            $name = $request->param('name');
            $url = $request->param('url');
            if(empty($index)){
                return showMsg(0,'parameter index is missed!');
            }
            if(!empty($name)){
                $this->brochureModel->updateName($uid,$index,$name);
            }
            if(!empty($url)){
                $url = $request->domain().'/'.$url;
                $this->brochureModel->updateUrl($uid,$index,$url);
            }
            $brochures = $this->brochureModel->getBrochureList($uid);
            return showMsg(1,'success',$brochures);
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function getBrochureList(Request $request){
        if($request->isPost()){
            $uid = $request->param('uid');
            $uniqueId = $request->param('uniqueId');
            if(!empty($uid)){
                $brochures = $this->brochureModel->getBrochureList($uid);
                return showMsg(1,'success',$brochures);
            }else if(!empty($uniqueId)){
                $brochures = $this->brochureModel->getBrochureListByUniqueId($uniqueId);
                return showMsg(1,'success',$brochures);
            }else{
                return showMsg(1,'success',[]);
            }
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function saveScanRecord(Request $request){
        if($request->isPost()){
            $merchantId = $request->param('merchantId');
            $visitorId = $request->param('visitorId');
            $merchant = $this->userModel->getUserByUid($merchantId);
            if(empty($merchant)){
                return showMsg(0,'merchant not exist!');
            }
            $visitor = $this->userModel->getUserByUid($visitorId);
            if(empty($visitor)){
                return showMsg(0,'visitor not exist!');
            }
            $date = date('Y-m-d',time());
            $record = $this->scanRecordModel->where(['visitor_id'=>$visitorId,'merchant_id'=>$merchantId,'date'=>$date])->find();
            if(empty($record)){
                $this->scanRecordModel->save(['visitor_id'=>$visitorId,'merchant_id'=>$merchantId,'date'=>$date]);
            }
            return showMsg(1,'ok');
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function getVisitors(Request $request){
        if($request->isPost()){
            $merchantId = $request->param('merchantId');
            $res = $this->scanRecordModel->where('merchant_id',$merchantId)->select();
            $visitors = [];
            foreach($res as $item){
                $visitors[] = $item['visitor_id'];
            }
            $list = $this->userModel->where('unique_id','in',$visitors)
                ->field('id,unique_id,first_name,last_name,organisation,email,mobile,remarks')->select();
            foreach($list as $key=>$value){
                $value['brochure_download_count'] = $this->downloadRecordModel
                    ->where('merchant_id',$merchantId)->where('visitor_id',$value['unique_id'])->count();
                $list[$key] = $value;
            }
            return showMsg(1,'success',$list);
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function getEdms(Request $request){
        if($request->isPost()){
            $uid = $request->param('uid');
            $ids = $request->param('ids');
            $data = $this->edmModel->where('status','1')->select();
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    private function getCustomizeKeywords($arr){
        $out = [];
        foreach($arr as $item){
            if(!in_array($item,$this->whitelist)){
                $out[] = $item;
            }
        }
        return $out;
    }

    private function getBuiltinKeywords($arr){
        $out = [];
        foreach($arr as $item){
            if(in_array($item,$this->whitelist)){
                $out[] = $item;
            }
        }
        return $out;
    }

    private function replaceEdmContent(Request $request,$mode){
        $matches = [];
        $merchantId = $request->param('merchantId');
        $ids = $request->param('ids');
        $idArr = explode(',',$ids);
        $templateId = $request->param('templateId');
        $template = $this->edmModel->where('id',$templateId)->find();
        $merchant = $this->userModel->where('unique_id',$merchantId)->find();
        $visitors = $this->userModel->where('id','in',$idArr)->select();
        $count = preg_match_all('/\[\%(.*)\%\]/U',$template['content'],$matches);
        $customizeKeywords = $this->getCustomizeKeywords($matches[1]);
        if($mode == 1 && count($customizeKeywords) > 0){
            return ['type'=>'1','data'=>$customizeKeywords];
        }
        $builtinKeywords = $this->getBuiltinKeywords($matches[1]);
        $content = $template['content'];
        foreach($customizeKeywords as $v){
            $data = $request->param($v);
            $content = str_replace('[%'.$v.'%]',$data,$content);
        }
        foreach($builtinKeywords as $v){
            if($v == 'company'){
                $content = str_replace('[%'.$v.'%]',$merchant['organisation'],$content);
            }
            if($v == 'date'){
                $content = str_replace('[%'.$v.'%]',date("Y-m-d",time()),$content);
            }
            if($v == 'recipient'){
                $count = 0;
                $names = [];
                foreach($visitors as $visitor){
                    if($count > 3) break;
                    $names[] = $visitor['first_name'].$visitor['last_name'];
                    $count++;
                }
                $name = join(',',$names);
                if($count > 3)$name = 'all';
                $content = str_replace('[%'.$v.'%]',$name,$content);
            }
        }
        $emails = [];
        $tos = [];
        foreach($visitors as $visitor){
            $tos[] = $visitor['first_name'].$visitor['last_name'];
            $emails[] = $visitor['email'];
        }
        $data = [
            'title'=>$template['name'],
            'content'=>$content,
            'to'=>join(';',$tos),
            'email'=>join(':',$emails)
        ];
        return ['type'=>'2','data'=>$data];
    }

    public function parseEdm(Request $request){
        if($request->isPost()){
            $data = $this->replaceEdmContent($request,1);
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function previewEdm(Request $request){
        if($request->isPost()){
            $data = $this->replaceEdmContent($request,2);
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function sendEmail(Request $request){
        if($request->isPost()){
            $title = $request->param('title');
            $content = $request->param('content');
            $to = $request->param('to');
            $email = $request->param('email');
            $emailClient = new Email();
            $rs = $emailClient->sendemailex($to,$email,'Digital Card',$title,$content);
            if (!$rs['status']) {
                return showMsg(0,$rs['msg']);
            }
            return showMsg(1,'Email is sent successfully!');
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function getDailyScanReport(Request $request){
        //取最近5天的该商家被扫码记录
        $merchantId = $request->param('merchantId');
        $startDate = date('Y-m-d',strtotime("-5 day"));
        $res = $this->scanRecordModel->field('merchant_id,date,count(*) as count')
            ->where('merchant_id',$merchantId)
            ->where('date','>=',$startDate)
            ->group('merchant_id,date')->order('date','asc')->select();
        return showMsg(1,'ok',$res);
    }

    public function getDailyDownloadReport(Request $request){
        //取最近5天的该商家被扫码记录
        $merchantId = $request->param('merchantId');
        $startDate = date('Y-m-d',strtotime("-5 day"));
        $res = $this->downloadRecordModel->field('merchant_id,date,count(*) as count')
            ->where('merchant_id',$merchantId)
            ->where('date','>=',$startDate)
            ->group('merchant_id,date')->order('date','asc')->select();
        return showMsg(1,'ok',$res);
    }

    public function uploadContact(Request $request){
        if($request->isPost()){
            $contacts = $request->param('contacts');
            LogUtil::info('[uploadContact]'.json_encode($contacts));
            if($contacts){
                foreach($contacts as $v){
                    $serialNumber = $v['serialNumber'];
                    $exhibitorID = $v['exhibitorID'];
                    $eventID = $this->user['event_id'];
                    $visitTime = $v['visitTime'];
                    $res = $this->visitorModel->where('serial_number',$serialNumber)
                        ->where('exhibitor_id',$exhibitorID)
                        ->where('event_id',$eventID)
                        ->where('visit_time',$visitTime)
                        ->find();
                    if(!empty($res)){
                        $this->visitorModel->where('id',$res['id'])
                            ->update([
                                'first_name'=>$v['firstName'],
                                'last_name'=>$v['lastName'],
                                'full_name'=>$v['fullName'],
                                'organization'=>$v['organization'],
                                'title'=>$v['title'],
                                'phone'=>$v['telephone'],
                                'email'=>$v['email'],
                                'flag'=>$v['flag'],
                                'remark'=>$v['remark'],
                                'img_card'=>$v['imgCard'],
                                'update_time'=>date('Y-m-d H:i:s',time())
                            ]);
                    } else {
                        $this->visitorModel->insert([
                            'id'=>Tools::create_guid(),
                            'first_name'=>$v['firstName'],
                            'last_name'=>$v['lastName'],
                            'full_name'=>$v['fullName'],
                            'organization'=>$v['organization'],
                            'title'=>$v['title'],
                            'phone'=>$v['telephone'],
                            'email'=>$v['email'],
                            'flag'=>$v['flag'],
                            'remark'=>$v['remark'],
                            'serial_number'=>$serialNumber,
                            'exhibitor_id'=>$exhibitorID,
                            'event_id'=>$eventID,
                            'visit_time'=>$visitTime,
                            'visit_date'=>$v['visitDate'],
                            'img_card'=>$v['imgCard'],
                            'create_time'=>date('Y-m-d H:i:s',time())
                        ]);
                    }
                }
            }
            return showMsg(1,'ok',['lastUpdate'=>date('Y-m-d H:i:s',time())]);
        }else{
            return showMsg(0,'sorry,your request is invalid!');
        }
    }

    public function checkLogin(Request $request){
        return showMsg(1,'ok!');
    }
}
