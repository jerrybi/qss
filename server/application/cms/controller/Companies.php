<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XdataFields;
use app\common\model\XcompanyAttrs;
use app\common\model\Xcompanies;
use app\common\model\Xevents;
use app\common\model\XexhibitorForms;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\Xlocations;
use app\common\model\Xusers;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class Companies extends CmsBase
{
    protected $model;
    protected $companyAttrModel;
    protected $locationModel;
    protected $boothModel;
    protected $userModel;
    protected $exhibitorFormModel;
    protected $formModel;
    protected $formDataModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xcompanies();
        $this->companyAttrModel = new XcompanyAttrs();
        $this->locationModel = new Xlocations();
        $this->boothModel = new XdataFields();
        $this->userModel = new Xusers();
        $this->exhibitorFormModel = new XexhibitorForms();
        $this->formModel = new Xzones();
        $this->formDataModel = new XformDatas();
    }

    /**
     * 获取文章列表数据
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search');
        $event_id = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search);
            return showMsg(1, 'success', $list);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            $record_num = $this->model->getCmsDatasCount($search);
            $companyAttrs = $this->companyAttrModel->getCmsList($eventId);
            $titles = Xcompanies::getTtitles($companyAttrs);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'companyAttrs'=>$companyAttrs,
                'titles'=>$titles
            ];
            return view('index', $data);
        }
    }
    
    /**
     * 添加文章
     * @param Request $request
     * @return \think\response\View|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->addData($input);
            //自动创建exhibitor管理员账号
            $accounts = isset($input['admin_account'])?$input['admin_account']:'';
            $email = $request->param('email');
            $companyId = $opRes['id'];
            if($companyId){
                $eventId = isset($input['event_id'])?$input['event_id']:'';
                $arr = json_decode($accounts,true);
                foreach($arr as $item){
                    $addData = [
                        'unique_id' => Tools::create_guid(),
                        'login_name' => $item['login_name'],
                        'first_name' => $item['first_name'],
                        'last_name' => $item['last_name'],
                        'phone_country_code' => isset($item['phone_country_code'])?$item['phone_country_code']:'',
                        'phone_area_code' => isset($item['phone_area_code'])?$item['phone_area_code']:'',
                        'phone_number' => isset($item['phone_number'])?$item['phone_number']:'',
                        'email' => $item['email'],
                        'company_id' => $companyId,
                        'event_id' => $eventId,
                        'status'=>1,
                        'type'=>0
                    ];
                    $res = $this->userModel->addData($addData);
                    //设置用户名密码
                    if($res['tag']){
                        $res1 = $this->userModel->updatePassword($res['id'],config('email.update_password_subject'),'Please use following account and password to login',$addData['email'],$request->domain());
                        if(!$res1['status']){
                            return showMsg($res1['status'],$res1['msg']);
                        }
                    }else{
                        return showMsg($res['tag'],$res['message']);
                    }
                }
            }
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            return view('add',['events'=>$events]);
        }
    }

    private function getRemovedList($oldList,$newList){
        $list = [];
        $logins = array_column($newList,'login_name');
        foreach($oldList as $key=>$item){
            if(!in_array($item['login_name'],$logins)){
                $list[] = $item['id'];
            }
        }
        return $list;
    }

    private function getAddList($oldList,$newList){
        $list = [];
        $items = array_column($oldList,'login_name');
        foreach($newList as $item){
            $flag = in_array($item['login_name'],$items);
            if(!$flag){
                $list[] = $item;
            }
        }
        return $list;
    }

    private function getRemainList($oldList,$newList){
        $list = [];
        $items = array_column($oldList,'login_name');
        foreach($newList as $item){
            $flag = in_array($item['login_name'],$items);
            if($flag){
                $list[] = $item;
            }
        }
        return $list;
    }

    /**
     * 更新文章数据
     * @param Request $request
     * @param $id 文章ID
     * @return \think\response\View|void
     */
    public function edit(Request $request, $id='')
    {
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsData($request->post(),$id);
            $tag = $request->param('tag');
            if($tag == 'del'){
                $this->userModel->where(['company_id'=>$id,'type'=>0])->delete();
            }else{
                $companyId = $id;
                $eventId = $request->param('event_id');
                $curUsers = $this->userModel->getUserByCompanyId($companyId,0);
                //检查管理员账号的增减情况
                $accounts = $request->param('admin_account');
                if(!empty($accounts)){
                    $arr = json_decode($accounts,true);
                    //找出被移除的管理员账号
                    $removedList = $this->getRemovedList($curUsers,$arr);
                    //删除这些账号
                    $this->userModel->removeUser($removedList);
                    //找出新增的管理员账号
                    $addList = $this->getAddList($curUsers,$arr);
                    //添加新的管理员账号
                    foreach($addList as $item){
                        $addData = [
                            'unique_id' => Tools::create_guid(),
                            'login_name' => $item['login_name'],
                            'first_name' => $item['first_name'],
                            'last_name' => $item['last_name'],
                            'phone_country_code' => isset($item['phone_country_code'])?$item['phone_country_code']:'',
                            'phone_area_code' => isset($item['phone_area_code'])?$item['phone_area_code']:'',
                            'phone_number' => isset($item['phone_number'])?$item['phone_number']:'',
                            'email' => $item['email'],
                            'company_id' => $companyId,
                            'event_id' => $eventId,
                            'status'=>1,
                            'type'=>0
                        ];
                        $res = $this->userModel->addData($addData);
                        //设置用户名密码
                        if($res['tag']){
                            $company = $this->model->getCmsDataByID($id);
                            $res1 = $this->userModel->updatePassword($res['id'],config('email.update_password_subject'),'Please use following account and password to login',$addData['email'],$request->domain());
                            if(!$res1['status']){
                                return showMsg($res1['status'],$res1['msg']);
                            }
                        }else{
                            return showMsg($res['tag'],$res['message'].$companyId);
                        }
                    }
                    //找出仍旧保留的管理员账号,可能会有字段更新
                    $remainList = $this->getRemainList($curUsers,$arr);
                    foreach($remainList as $item){
                        $this->userModel
                            ->where(['login_name'=>$item['login_name'],'company_id'=>$companyId,'event_id'=>$eventId,'type'=>0])
                            ->update([
                                'first_name'=>$item['first_name'],
                                'last_name'=>$item['last_name'],
                                'phone_country_code'=>isset($item['phone_country_code'])?$item['phone_country_code']:'',
                                'phone_area_code'=>isset($item['phone_area_code'])?$item['phone_area_code']:'',
                                'phone_number'=>isset($item['phone_number'])?$item['phone_number']:'',
                                'email'=>$item['email']]);
                    }
                }
            }
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $users = $this->userModel->getUsersByCompanyId($article['id']);
            $arr = [];
            foreach ($users as $item){
                $arr[] = ['login_name'=>$item['login_name']
                    ,'first_name'=>$item['first_name']
                    ,'last_name'=>$item['last_name']
                    ,'phone_country_code'=>$item['phone_country_code']
                    ,'phone_area_code'=>$item['phone_area_code']
                    ,'phone_number'=>$item['phone_number']
                    ,'email'=>$item['email']
                ];
            }
            $article['admin_account'] = json_encode($arr);
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $data =
                [
                    'article' => $article,
                    'events'=>$events
                ];
            return view('edit', $data);
        }
    }

    public function getCompanyAttrs(Request $request){
        if($request->isPost()){
            $eventId = $request->param('event_id');
            $companyAttrs = $this->companyAttrModel->getCmsList($eventId);
            $titles = [];
            if(!empty($companyAttrs)){
                $titles = Xcompanies::getTtitles1($companyAttrs);
            }
            $locations = $this->locationModel->getList($eventId);
            $booths = $this->boothModel->getCmsList($eventId);
            $data = [
                'companyAttrs'=>$companyAttrs,
                'titles'=>$titles,
                'locations'=>$locations,
                'booths'=>$booths
            ];
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0,'sorry,your request is invalid！');
        }
    }

    public function fieldList(Request $request)
    {
        $curr_page = 1;
        $search = '';
        $event_id = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, 1000, $search);
            return showMsg(1, 'success', $list);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $record_num = 5;
            $companyAttrs = $this->companyAttrModel->getCmsList($eventId);
            $articles = [];
            $articles[] = ['id'=>1,'key'=>'name','name'=>'Company','label'=>Xcompanies::getLabelByKey($companyAttrs,'name','')];
            $articles[] = ['id'=>2,'key'=>'type','name'=>'Exhibitor Type','label'=>Xcompanies::getLabelByKey($companyAttrs,'type','')];
            $articles[] = ['id'=>3,'key'=>'booth_id','name'=>'Booth ID','label'=>Xcompanies::getLabelByKey($companyAttrs,'booth_id','')];
            $articles[] = ['id'=>4,'key'=>'admin_account','name'=>'Exhibitor Admin','label'=>Xcompanies::getLabelByKey($companyAttrs,'admin_account','')];
            $articles[] = ['id'=>5,'key'=>'origin_country','name'=>'Country/Region of Origin','label'=>Xcompanies::getLabelByKey($companyAttrs,'origin_country','')];
            $articles[] = ['id'=>6,'key'=>'profile','name'=>'Profile','label'=>Xcompanies::getLabelByKey($companyAttrs,'profile','')];
            $articles[] = ['id'=>7,'key'=>'logo','name'=>'Logo','label'=>Xcompanies::getLabelByKey($companyAttrs,'logo','')];
            $articles[] = ['id'=>8,'key'=>'email','name'=>'Contact Email','label'=>Xcompanies::getLabelByKey($companyAttrs,'email','')];
            $articles[] = ['id'=>9,'key'=>'address_line1','name'=>'Address Line 1','label'=>Xcompanies::getLabelByKey($companyAttrs,'address_line1','')];
            $articles[] = ['id'=>10,'key'=>'address_line2','name'=>'Address Line 2','label'=>Xcompanies::getLabelByKey($companyAttrs,'address_line2','')];
            $articles[] = ['id'=>11,'key'=>'postal','name'=>'Postal/Zip Code','label'=>Xcompanies::getLabelByKey($companyAttrs,'postal','')];
            $articles[] = ['id'=>12,'key'=>'country','name'=>'Country/Region','label'=>Xcompanies::getLabelByKey($companyAttrs,'country','')];
            $articles[] = ['id'=>13,'key'=>'phone','name'=>'Business Phone','label'=>Xcompanies::getLabelByKey($companyAttrs,'phone','')];
            $articles[] = ['id'=>14,'key'=>'fax','name'=>'Fax','label'=>Xcompanies::getLabelByKey($companyAttrs,'fax','')];
            $articles[] = ['id'=>15,'key'=>'website','name'=>'Website','label'=>Xcompanies::getLabelByKey($companyAttrs,'website','')];
            $articles[] = ['id'=>16,'key'=>'industry','name'=>'Industries and Sectors','label'=>Xcompanies::getLabelByKey($companyAttrs,'industry','')];
            $articles[] = ['id'=>17,'key'=>'product','name'=>'Products and Services','label'=>Xcompanies::getLabelByKey($companyAttrs,'product','')];
            $articles[] = ['id'=>18,'key'=>'billing_address_line1','name'=>'Billing Address Line 1','label'=>Xcompanies::getLabelByKey($companyAttrs,'billing_address_line1','')];
            $articles[] = ['id'=>19,'key'=>'billing_address_line2','name'=>'Billing Address Line 2','label'=>Xcompanies::getLabelByKey($companyAttrs,'billing_address_line2','')];
            $articles[] = ['id'=>20,'key'=>'billing_postal','name'=>'Billing Postal/Zip Code','label'=>Xcompanies::getLabelByKey($companyAttrs,'billing_postal','')];
            $articles[] = ['id'=>21,'key'=>'billing_country','name'=>'Billing Country/Region','label'=>Xcompanies::getLabelByKey($companyAttrs,'billing_country','')];
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => 1000,
                'events'=>$events,
                'event_id'=>$eventId,
                'companyAttrs'=>$companyAttrs
            ];
            return view('fieldList', $data);
        }
    }

    public function editField(Request $request, $id='',$event_id='')
    {
        if ($request->isPost()) {
            $event_id = $request->param('event_id');
            $opRes = $this->companyAttrModel->updateCmsData($request->post(),$id,$event_id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->companyAttrModel->getDataByKey($id,$event_id);
            if(empty($article)){
                $name = '';
                if($id == 'name') $name = 'Company Name';
                if($id == 'type') $name = 'Type';
                if($id == 'booth_id') $name = 'Booth ID';
                if($id == 'admin_account') $name = 'Exhibitor Admin';
                if($id == 'origin_country') $name = 'Country/Region of Origin';
                if($id == 'profile') $name = 'Company Profile';
                if($id == 'logo') $name = 'Company Logo';
                if($id == 'email') $name = 'Contact Email';
                if($id == 'address_line1') $name = 'Address Line 1';
                if($id == 'address_line2') $name = 'Address Line 2';
                if($id == 'postal') $name = 'Postal/Zip Code';
                if($id == 'country') $name = 'Country/Region';
                if($id == 'phone') $name = 'Business Phone';
                if($id == 'fax') $name = 'Fax';
                if($id == 'website') $name = 'Website';
                if($id == 'industry') $name = 'Industries and Sectors';
                if($id == 'product') $name = 'Products and Services';
                if($id == 'billing_address_line1') $name = 'Billing Address Line 1';
                if($id == 'billing_address_line2') $name = 'Billing Address Line 2';
                if($id == 'billing_postal') $name = 'Billing Postal/Zip Code';
                if($id == 'billing_country') $name = 'Billing Country/Region';
                $article = ['key'=>$id,'name'=>$name,'label'=>'','default'=>'','min'=>null,'max'=>null,
                    'options'=>'','description'=>''];
            }else{
                if(!empty($article['options'])){
                    $article['options'] = trim($article['options']);
                }
            }
            $locations = $this->locationModel->getList($event_id);
            $booths = $this->boothModel->getCmsList($event_id);
            $data =
                [
                    'article' => $article,
                    'event_id'=>$event_id,
                    'key'=>$id,
                    'locations'=>$locations,
                    'booths'=>$booths
                ];
            return view('editField', $data);
        }
    }

    public function download(Request $request){
        $eventId = $request->param('event_id');
        $record_num = $this->model->getCmsDatasCount('');
        $articles = $this->model->getCmsDatasForPage(1, $record_num, '',$eventId);
        $objPHPExcel = new PHPExcel();
        $topNumber = 3;
        $xlsTitle = 'companies';
        $fileName = $xlsTitle.date('_YmdHis');
        $companyAttrs = $this->companyAttrModel->getCmsList($eventId);
        $cellName = array();
        $cellName[] = ['','','No'];
        $cellName[] = ['','','Company Name'];
        $cellName[] = ['','','Type'];
        $cellName[] = ['','','Booth No'];
        $cellName[] = ['','','Location'];
        $cellName[] = ['','','Badge'];
        $cellName[] = ['','','Area'];
        $cellName[] = ['','','Stand Type'];
        $cellName[] = ['','','Country of Origin'];
        $cellName[] = ['','','Profile'];
        $cellName[] = ['','','Logo'];
        $cellName[] = ['','','Email'];
        $cellName[] = ['','','Address Line1'];
        $cellName[] = ['','','Address Line2'];
        $cellName[] = ['','','Postal Code'];
        $cellName[] = ['','','Country'];
        $cellName[] = ['','','Phone'];
        $cellName[] = ['','','Fax'];
        $cellName[] = ['','','Website'];
        $cellName[] = ['','','Billing Address Line1'];
        $cellName[] = ['','','Billing Address Line2'];
        $cellName[] = ['','','Billing Postal Code'];
        $cellName[] = ['','','Billing Country'];
        if(!empty($value['sub_company_name'])) {
            $industry = $this->getOptionsByKey($companyAttrs, 'sub_industry');
        }else{
            $industry = $this->getOptionsByKey($companyAttrs, 'industry');
        }
        $industries = explode("\r\n",$industry);
        foreach ($industries as $v){
            $cellName[] = ['','',$v];
        }
        if(!empty($value['sub_company_name'])) {
            $product = $this->getOptionsByKey($companyAttrs, 'sub_product');
        }else{
            $product = $this->getOptionsByKey($companyAttrs, 'product');
        }
        $productJson = json_decode($product,true);
        $products = [];
        if(!empty($productJson) && !empty($productJson[0]) && !empty($productJson[0]['children'])){
            foreach($productJson[0]['children'] as $k=>$v){
                //first level
                $title0 = $v['title'];
                if(!empty($v['children'])){
                    foreach($v['children'] as $k1=>$v1){
                        //2nd level
                        $title1 = $v1['title'];
                        if(empty($v1['children'])){
                            $cellName[] = [$title0,'',$title1];
                            $products[] = $title1;
                        }else{
                            foreach($v1['children'] as $k2=>$v2){
                                //3rd level
                                $cellName[] = [$title0,$title1,$v2['title']];
                                $products[] = $v2['title'];
                            }
                        }
                    }
                }else{
                    $cellName[] = [$title0,'',''];
                    $products[] = $title0;
                }
            }
        }
        $cellName[] = ['','','Submission Date'];
        $cellName[] = ['','','Modified Date'];
        $cellKey1 = Tools::getExcelColumnTitles(count($cellName));
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle("Companies");
        foreach($cellName as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'1',$v[0]);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'2',$v[1]);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'2')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'3',$v[2]);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'3')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'3')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        foreach ($cellKey1 as $v){
            $objPHPExcel->getActiveSheet()->getColumnDimension($v)->setWidth(30);
        }
        foreach ($articles as $key=>$value){
            $index = 0;
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$key+1);
            $companyName = !empty($value['sub_company_name'])?$value['sub_company_name']:$value['name'];
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['type']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['booth']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['location']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['badge']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['area']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['stand_type']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['origin_country']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['profile']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['logo']);
            if(!empty($value['sub_company_name'])) {
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['sub_email']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_address_line1']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_address_line2']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_postal']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['sub_country']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['sub_phone_country_code'] . $value['sub_phone_area_code'] . $value['sub_phone_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_fax_country_code'].$value['sub_fax_area_code'].$value['sub_fax_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_website']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_billing_address_line1']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_billing_address_line2']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_billing_postal']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_billing_country']);
            }else{
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['email']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['address_line1']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['address_line2']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['postal']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['country']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['phone_country_code'] . $value['phone_area_code'] . $value['phone_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['fax_country_code'].$value['fax_area_code'].$value['fax_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['website']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['billing_address_line1']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['billing_address_line2']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['billing_postal']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['billing_country']);
            }
            //industry
            foreach($industries as $k=>$v){
                if(strstr($value['industry'],$v)){
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
                }else{
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),'');
                }
            }
            //product
            foreach($products as $k=>$v){
                if(strstr($value['product'],$v)){
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
                }else{
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),'');
                }
            }
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['create_time']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['update_time']);
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    private function getOptionsByKey($arr,$key){
        if(empty($arr) || count($arr) == 0) return '';
        foreach($arr as $value){
            if($value['key'] == $key){
                return $value['options'];
            }
        }
        return '';
    }

    public function upload(Request $request){
        $eventId = $request->param('event_id');
        $fileUrl = $request->param('file_url');
        $filePath = Env::get('root_path').'/public/'.$fileUrl;
        //读取excel内容
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($filePath)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filePath)){
                showMsg(0,'can not read file!');
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();
        $data = array();
        for($rowIndex = 2;$rowIndex<=$allRow;$rowIndex++){
            $data[] = ['name'=>(string)($currentSheet->getCell('B'.$rowIndex)->getValue()),
                'type'=>(string)($currentSheet->getCell('C'.$rowIndex)->getValue()),
                'booth_id'=>(string)($currentSheet->getCell('D'.$rowIndex)->getValue()),
                'admin_account'=>(string)($currentSheet->getCell('E'.$rowIndex)->getValue()),
                'origin_country'=>(string)($currentSheet->getCell('F'.$rowIndex)->getValue()),
                'profile'=>(string)($currentSheet->getCell('G'.$rowIndex)->getValue()),
                'logo'=>(string)($currentSheet->getCell('H'.$rowIndex)->getValue()),
                'email'=>(string)($currentSheet->getCell('I'.$rowIndex)->getValue()),
                'address_line1'=>(string)($currentSheet->getCell('J'.$rowIndex)->getValue()),
                'address_line2'=>(string)($currentSheet->getCell('K'.$rowIndex)->getValue()),
                'postal'=>(string)($currentSheet->getCell('L'.$rowIndex)->getValue()),
                'country'=>(string)($currentSheet->getCell('M'.$rowIndex)->getValue()),
                'phone_country_code'=>(string)($currentSheet->getCell('N'.$rowIndex)->getValue()),
                'phone_area_code'=>(string)($currentSheet->getCell('O'.$rowIndex)->getValue()),
                'phone_number'=>(string)($currentSheet->getCell('P'.$rowIndex)->getValue()),
                'fax_country_code'=>(string)($currentSheet->getCell('Q'.$rowIndex)->getValue()),
                'fax_area_code'=>(string)($currentSheet->getCell('R'.$rowIndex)->getValue()),
                'fax_number'=>(string)($currentSheet->getCell('S'.$rowIndex)->getValue()),
                'website'=>(string)($currentSheet->getCell('T'.$rowIndex)->getValue()),
                'billing_address_line1'=>(string)($currentSheet->getCell('U'.$rowIndex)->getValue()),
                'billing_address_line2'=>(string)($currentSheet->getCell('V'.$rowIndex)->getValue()),
                'billing_postal'=>(string)($currentSheet->getCell('W'.$rowIndex)->getValue()),
                'billing_country'=>(string)($currentSheet->getCell('X'.$rowIndex)->getValue()),
                'event_id'=>$eventId,
                'status'=>1,
                'create_time'=>date('Y-m-d H:i:s',time()),
                'update_time'=>date('Y-m-d H:i:s',time())];
        }
        if(count($data) > 0){
            $res = Db::name('Xcompanies')->insertAll($data);
            if($res){
                return showMsg(1,'upload companys successfully!');
            }else{
                return showMsg(0,'upload companys failed!');
            }
        }
        return showMsg(0,'file is empty!');
    }

    public function viewForms(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $id = $request->param('company_id');
        if ($request->isPost()) {
            $list = $this->exhibitorFormModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$event_id,$id);
            foreach($list as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $list[$key] = $item;
            }
            return showMsg(1, 'success', $list);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $list = $this->exhibitorFormModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,$id);
            foreach($list as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $list[$key] = $item;
            }
            $record_num = $this->exhibitorFormModel->getCmsDatasCount($search,$event_id,$id);
            $data = [
                'articles' => $list,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'company_id'=>$id
            ];
            return view('view_forms', $data);
        }
    }

    public function viewForm(Request $request){
        $formId = $request->param('form_id');
        $companyId = $request->param('company_id');
        $article = $this->formModel->getCmsDataByID($formId);
        $formData = $this->formDataModel->getCmsData($article['event_id'],$companyId,$formId);
        $data = [
            'article' => $article,
            'formData'=>$formData
        ];
        return view('view_form', $data);
    }

    public function industryView(Request $request,$id=''){
        if ($request->isPost()) {
            $industry = $request->param('industry');
            $opRes = $this->model->where('id',$id)->update(['industry'=>$industry]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            $article = $this->model->getCmsDataByID($id);
            $res = $this->companyAttrModel->getDataByKey('industry',$article['event_id']);
            $options = explode("\r\n",$res['options']);
            $data =
                [
                    'article' => $article,
                    'options'=>$options,
                    'industry_attr'=>$res
                ];
            return view('industry_view', $data);
        }
    }

    public function productView(Request $request,$id=''){
        if ($request->isPost()) {
            $product = $request->param('product');
            $opRes = $this->model->where('id',$id)->update(['product'=>$product]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            $article = $this->model->getCmsDataByID($id);
            $res = $this->companyAttrModel->getDataByKey('product',$article['event_id']);
            $data =
                [
                    'article' => $article,
                    'product_attr'=>$res
                ];
            return view('product_view', $data);
        }
    }
}
