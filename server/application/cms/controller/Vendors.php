<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XdataFields;
use app\common\model\XvendorAttrs;
use app\common\model\Xvendors;
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
class Vendors extends CmsBase
{
    protected $model;
    protected $vendorAttrModel;
    protected $locationModel;
    protected $boothModel;
    protected $userModel;
    protected $exhibitorFormModel;
    protected $formModel;
    protected $formDataModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xvendors();
        $this->vendorAttrModel = new XvendorAttrs();
        $this->locationModel = new Xlocations();
        $this->boothModel = new XdataFields();
        $this->userModel = new Xusers();
        $this->exhibitorFormModel = new XexhibitorForms();
        $this->formModel = new Xzones();
        $this->formDataModel = new XformDatas();
    }

    private function getTtitles($attrs){
        $titles = [];
        $titles['name'] = $this->getLabelByKey($attrs,'name','Vendor');
        $titles['origin_country'] = $this->getLabelByKey($attrs,'origin_country','Country/Region of Origin');
        $titles['email'] = $this->getLabelByKey($attrs,'email','Email');
        $titles['phone'] = $this->getLabelByKey($attrs,'phone','Phone');
        return $titles;
    }

    private function getTtitles1($attrs){
        $titles = [];
        $titles['name'] = $this->getLabelByKey($attrs,'name','vendor');
        $titles['admin_account'] = $this->getLabelByKey($attrs,'admin_account','Vendor Admin');
        $titles['origin_country'] = $this->getLabelByKey($attrs,'origin_country','Country/Region of Origin');
        $titles['profile'] = $this->getLabelByKey($attrs,'profile','Vendor Profile');
        $titles['logo'] = $this->getLabelByKey($attrs,'logo','Vendor Logo');
        $titles['email'] = $this->getLabelByKey($attrs,'email','Contact Email');
        $titles['address_line1'] = $this->getLabelByKey($attrs,'address_line1','Address Line 1');
        $titles['address_line2'] = $this->getLabelByKey($attrs,'address_line2','Address Line 2');
        $titles['postal'] = $this->getLabelByKey($attrs,'postal','Postal/Zip Code');
        $titles['country'] = $this->getLabelByKey($attrs,'country','Country/Region');
        $titles['phone'] = $this->getLabelByKey($attrs,'phone','Business Phone');
        $titles['fax'] = $this->getLabelByKey($attrs,'fax','Fax');
        $titles['website'] = $this->getLabelByKey($attrs,'website','Website');
        return $titles;
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
            $vendorAttrs = $this->vendorAttrModel->getCmsList($eventId);
            $titles = $this->getTtitles($vendorAttrs);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'vendorAttrs'=>$vendorAttrs,
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
            $vendorId = $opRes['id'];
            if($vendorId){
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
                        'company_id' => $vendorId,
                        'event_id' => $eventId,
                        'status'=>1,
                        'type'=>1
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
                $this->userModel->where(['company_id'=>$id,'type'=>1])->delete();
            }else{
                $vendorId = $id;
                $eventId = $request->param('event_id');
                $curUsers = $this->userModel->getUserByCompanyId($vendorId,1);
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
                            'company_id' => $vendorId,
                            'event_id' => $eventId,
                            'status'=>1,
                            'type'=>1
                        ];
                        $res = $this->userModel->addData($addData);
                        //设置用户名密码
                        if($res['tag']){
                            $vendor = $this->model->getCmsDataByID($id);
                            $res1 = $this->userModel->updatePassword($res['id'],config('email.update_password_subject'),'Please use following account and password to login',$addData['email'],$request->domain());
                            if(!$res1['status']){
                                return showMsg($res1['status'],$res1['msg']);
                            }
                        }else{
                            return showMsg($res['tag'],$res['message'].$vendorId);
                        }
                    }
                    //找出仍旧保留的管理员账号,可能会有字段更新
                    $remainList = $this->getRemainList($curUsers,$arr);
                    foreach($remainList as $item){
                        $this->userModel
                            ->where(['login_name'=>$item['login_name'],'company_id'=>$vendorId,'event_id'=>$eventId,'type'=>1])
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

    public function getvendorAttrs(Request $request){
        if($request->isPost()){
            $eventId = $request->param('event_id');
            $vendorAttrs = $this->vendorAttrModel->getCmsList($eventId);
            $titles = [];
            if(!empty($vendorAttrs)){
                $titles = $this->getTtitles1($vendorAttrs);
            }
            $data = [
                'vendorAttrs'=>$vendorAttrs,
                'titles'=>$titles
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
            $vendorAttrs = $this->vendorAttrModel->getCmsList($eventId);
            $articles = [];
            $articles[] = ['id'=>1,'key'=>'name','name'=>'Vendor','label'=>$this->getLabelByKey($vendorAttrs,'name','')];
            $articles[] = ['id'=>2,'key'=>'admin_account','name'=>'Vendor Admin','label'=>$this->getLabelByKey($vendorAttrs,'admin_account','')];
            $articles[] = ['id'=>3,'key'=>'origin_country','name'=>'Country/Region of Origin','label'=>$this->getLabelByKey($vendorAttrs,'origin_country','')];
            $articles[] = ['id'=>4,'key'=>'profile','name'=>'Profile','label'=>$this->getLabelByKey($vendorAttrs,'profile','')];
            $articles[] = ['id'=>5,'key'=>'logo','name'=>'Logo','label'=>$this->getLabelByKey($vendorAttrs,'logo','')];
            $articles[] = ['id'=>6,'key'=>'email','name'=>'Contact Email','label'=>$this->getLabelByKey($vendorAttrs,'email','')];
            $articles[] = ['id'=>7,'key'=>'address_line1','name'=>'Address Line 1','label'=>$this->getLabelByKey($vendorAttrs,'address_line1','')];
            $articles[] = ['id'=>8,'key'=>'address_line2','name'=>'Address Line 2','label'=>$this->getLabelByKey($vendorAttrs,'address_line2','')];
            $articles[] = ['id'=>9,'key'=>'postal','name'=>'Postal/Zip Code','label'=>$this->getLabelByKey($vendorAttrs,'postal','')];
            $articles[] = ['id'=>10,'key'=>'country','name'=>'Country/Region','label'=>$this->getLabelByKey($vendorAttrs,'country','')];
            $articles[] = ['id'=>11,'key'=>'phone','name'=>'Business Phone','label'=>$this->getLabelByKey($vendorAttrs,'phone','')];
            $articles[] = ['id'=>12,'key'=>'fax','name'=>'Fax','label'=>$this->getLabelByKey($vendorAttrs,'fax','')];
            $articles[] = ['id'=>13,'key'=>'website','name'=>'Website','label'=>$this->getLabelByKey($vendorAttrs,'website','')];
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => 1000,
                'events'=>$events,
                'event_id'=>$eventId,
                'vendorAttrs'=>$vendorAttrs
            ];
            return view('fieldList', $data);
        }
    }

    private function getLabelByKey($arr,$key,$default){
        if(empty($arr) || count($arr) == 0) return $default;
        foreach($arr as $value){
            if($value['key'] == $key){
                return !empty($value['label'])?$value['label']:$default;
            }
        }
        return $default;
    }

    public function editField(Request $request, $id='',$event_id='')
    {
        if ($request->isPost()) {
            $event_id = $request->param('event_id');
            $opRes = $this->vendorAttrModel->updateCmsData($request->post(),$id,$event_id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->vendorAttrModel->getDataByKey($id,$event_id);
            if(empty($article)){
                $name = '';
                if($id == 'name') $name = 'Vendor Name';
                if($id == 'admin_account') $name = 'Vendor Admin';
                if($id == 'origin_country') $name = 'Country/Region of Origin';
                if($id == 'profile') $name = 'Vendor Profile';
                if($id == 'logo') $name = 'Vendor Logo';
                if($id == 'email') $name = 'Contact Email';
                if($id == 'address_line1') $name = 'Address Line 1';
                if($id == 'address_line2') $name = 'Address Line 2';
                if($id == 'postal') $name = 'Postal/Zip Code';
                if($id == 'country') $name = 'Country/Region';
                if($id == 'phone') $name = 'Business Phone';
                if($id == 'fax') $name = 'Fax';
                if($id == 'website') $name = 'Website';
                $article = ['key'=>$id,'name'=>$name,'label'=>'','default'=>'','min'=>null,'max'=>null,
                    'options'=>'','description'=>''];
            }else{
                if(!empty($article['options'])){
                    $article['options'] = trim($article['options']);
                }
            }
            $data =
                [
                    'article' => $article,
                    'event_id'=>$event_id,
                    'key'=>$id
                ];
            return view('editField', $data);
        }
    }

    public function download(Request $request){
        $eventId = $request->param('event_id');
        $record_num = $this->model->getCmsDatasCount('');
        $articles = $this->model->getCmsDatasForPage(1, $record_num, '',$eventId);
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'vendors';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $vendorAttrs = $this->vendorAttrModel->getCmsList($eventId);
        $cellName = array('ID');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'name','Vendor Name');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'admin_account','Vendor Admin');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'origin_country','Country/Region of Origin');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'profile','Vendor Profile');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'logo','Vendor Logo');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'email','Contact Email');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'address_line1','Address Line 1');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'address_line2','Address Line 2');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'postal','Postal/Zip Code');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'country','Country/Region');
        $cellName[] = 'Phone Country Code';
        $cellName[] = 'Phone Area Code';
        $cellName[] = $this->getLabelByKey($vendorAttrs,'phone','Business Phone');
        $cellName[] = 'Fax Country Code';
        $cellName[] = 'Fax Area Code';
        $cellName[] = $this->getLabelByKey($vendorAttrs,'fax','Fax');
        $cellName[] = $this->getLabelByKey($vendorAttrs,'website','Website');
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        //处理数据
        foreach($articles as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$k+1);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),$v['admin_account']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),$v['origin_country']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['profile']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['logo']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['email']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['address_line1']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),$v['address_line2']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($k+1+$topNumber),$v['postal']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),$v['country']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($k+1+$topNumber),$v['phone_country_code']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.($k+1+$topNumber),$v['phone_area_code']);
            $objPHPExcel->getActiveSheet()->setCellValue('N'.($k+1+$topNumber),$v['phone_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('O'.($k+1+$topNumber),$v['fax_country_code']);
            $objPHPExcel->getActiveSheet()->setCellValue('P'.($k+1+$topNumber),$v['fax_area_code']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q'.($k+1+$topNumber),$v['fax_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('R'.($k+1+$topNumber),$v['website']);
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
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
                'admin_account'=>(string)($currentSheet->getCell('C'.$rowIndex)->getValue()),
                'origin_country'=>(string)($currentSheet->getCell('D'.$rowIndex)->getValue()),
                'profile'=>(string)($currentSheet->getCell('E'.$rowIndex)->getValue()),
                'logo'=>(string)($currentSheet->getCell('F'.$rowIndex)->getValue()),
                'email'=>(string)($currentSheet->getCell('G'.$rowIndex)->getValue()),
                'address_line1'=>(string)($currentSheet->getCell('H'.$rowIndex)->getValue()),
                'address_line2'=>(string)($currentSheet->getCell('I'.$rowIndex)->getValue()),
                'postal'=>(string)($currentSheet->getCell('J'.$rowIndex)->getValue()),
                'country'=>(string)($currentSheet->getCell('K'.$rowIndex)->getValue()),
                'phone_country_code'=>(string)($currentSheet->getCell('L'.$rowIndex)->getValue()),
                'phone_area_code'=>(string)($currentSheet->getCell('M'.$rowIndex)->getValue()),
                'phone_number'=>(string)($currentSheet->getCell('N'.$rowIndex)->getValue()),
                'fax_country_code'=>(string)($currentSheet->getCell('O'.$rowIndex)->getValue()),
                'fax_area_code'=>(string)($currentSheet->getCell('P'.$rowIndex)->getValue()),
                'fax_number'=>(string)($currentSheet->getCell('Q'.$rowIndex)->getValue()),
                'website'=>(string)($currentSheet->getCell('R'.$rowIndex)->getValue()),
                'event_id'=>$eventId,
                'status'=>1,
                'create_time'=>date('Y-m-d H:i:s',time()),
                'update_time'=>date('Y-m-d H:i:s',time())];
        }
        if(count($data) > 0){
            $res = Db::name('Xvendors')->insertAll($data);
            if($res){
                return showMsg(1,'upload vendors successfully!');
            }else{
                return showMsg(0,'upload vendors failed!');
            }
        }
        return showMsg(0,'file is empty!');
    }

    public function getVendors(Request $request){
        $eventId = $request->param('event_id');
        if($request->isPost()){
            $data = $this->model->getCmsList($eventId);
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }
}
