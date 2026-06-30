<?php
namespace app\exhibitor\controller;

use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\model\Xarticles;
use app\common\model\Xcompanies;
use app\common\model\Xconfigs;
use app\common\model\XdataFields;
use app\common\model\Xevents;
use app\common\model\Xexhibitors;
use app\common\model\XuserDatas;
use app\common\model\Xvisitors;
use PHPExcel;
use PHPExcel_IOFactory;
use think\Db;
use think\Request;

class Index extends UserBase
{
    private $model;
    private $configModel;
    private $announcementModel;
    private $noticeModel;
    private $formModel;
    private $exhibitorFormModel;
    private $formDataModel;
    private $companyModel;
    private $boothModel;
    private $boothAttrModel;
    private $companyAttrModel;
    private $eventModel;
    private $freightModel;
    private $visitorModel;
    private $userDataModel;
    private $dataFieldModel;
    protected $userId;
    protected $userInfo;
    protected $navHome;
    protected $navAnnouncements;
    protected $navMarketing;
    protected $navAmenities;
    protected $navNotices;
    protected $navBookings;
    protected $navBadges;
    protected $navManpowers;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xexhibitors();
        $this->configModel = new Xconfigs();
        $this->eventModel = new Xevents();
        $this->visitorModel = new Xvisitors();
        $this->userDataModel = new XuserDatas();
        $this->dataFieldModel = new XdataFields();
        $this->userId = IAuth::getUserIDCurrLogged();
        if(!$this->userId){
            return redirect('exhibitor/login/index');
        }
    }

    private function loadSharedData(){
        $this->userInfo = $this->model->getCmsDataByID($this->userId);
        $this->navHome = $this->configModel->getCmsData($this->userInfo['event_id']);
    }

    /**
     * PC 端首页
     * @return \think\response\View
     */
    public function index()
    {
        if(!$this->userId){
            return redirect('exhibitor/login/index');
        }else{
            $this->loadSharedData();
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'current_id'=>0
            ];
            return view('index',$data);
        }
    }


    private function existFormId($datas,$formId){
        foreach($datas as $key=>$item){
            if($item['form_id'] == $formId){
                return true;
            }
        }
        return false;
    }

    public function marketings(Request $request,$id=0){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $curr_page = $request->param('curr_page', 1);
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        if(empty($id)){
            //检查exhibitorforms表是否有对应的表单数据
            $forms = $this->formModel->getDataList($eventId,'Marketing');
            $marketings = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Marketing');
            $saveDatas = [];
            foreach($forms as $key=>$item){
                if(!$this->existFormId($marketings,$item['id'])){
                    $saveDatas[] = ['company_id'=>$company['id'],'form_id'=>$item['id'],'status'=>0,'main_type'=>'Marketing','type'=>$item['type'],'event_id'=>$eventId];
                }
            }
            if(count($saveDatas) > 0){
                $this->exhibitorFormModel->saveAll($saveDatas);
            }
            //重新取出所有的数据
            $marketings = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Marketing');
            foreach($marketings as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $form = $this->formModel->getFormFromList($forms,$item['form_id']);
                $item['block_status'] = $this->formModel->getFormBlockStatus($form);
                $marketings[$key] = $item;
            }
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'topAnnouncements'=>$this->navAnnouncements,
                'notices'=>$this->navNotices,
                'navMarketing'=>$this->navMarketing,
                'navAmenities'=>$this->navAmenities,
                'navBookings'=>$this->navBookings,
                'navBadges'=>$this->navBadges,
                'navManpowers'=>$this->navManpowers,
                'marketings'=>$marketings,
                'current_id'=>0,
                'current_marketing_id'=>$id,
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>0,
                'current_manpower_id'=>0
            ];
            return view('marketings', $data);
        }else{
//            $marketing = $this->formModel->getData($id);
            //取出用户在该表单提交过的数据
//            $marketingData = $this->formDataModel->getCmsData($eventId,$company['id'],$id);
//            $data = [
//                'user'=>$this->userInfo,
//                'exhibitor'=>$this->navHome,
//                'topAnnouncements'=>$this->navAnnouncements,
//                'notices'=>$this->navNotices,
//                'navMarketing'=>$this->navMarketing,
//                'navAmenities'=>$this->navAmenities,
//                'marketing' => $marketing,
//                'formData'=>$marketingData,
//                'current_id'=>0,
//                'current_marketing_id'=>$id,
//                'current_amenity_id'=>0
//            ];
//            return view($template, $data);
            $form = $this->formModel->getData($id);
            if($form['type'] == 'Booking'){
                return redirect('/exhibitor/Booking/index',['form_id'=>$id]);
            }else if($form['type'] == 'Badge'){
                return redirect('/exhibitor/Badge/index',['form_id'=>$id]);
            }else if($form['type'] == 'Manpower'){
                return redirect('/exhibitor/Manpower/index',['form_id'=>$id]);
            }else if($form['type'] == 'Amenity'){
                return redirect('/exhibitor/Amenity/index',['form_id'=>$id]);
            }else{
                return redirect('/exhibitor/Marketing/index',['form_id'=>$id]);
            }
        }
    }

    public function records(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $str_search = $request->param('str_search', "");
        $curr_page = $request->param('curr_page', 1);
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $extraFields = [];
        $extraKeys = [];
        $userFields = $this->dataFieldModel->getCmsList($eventId);
        if($userFields){
            foreach($userFields as $k => $v){
                if(!in_array($v['key'],['serial_number','first_name','last_name','full_name'])
                && $v['exhibitor_visible'] == '1'){
                    $extraFields[] = $v;
                    $extraKeys[] = $v['key'];
                }
            }
        }
        if($request->isPost()){
            $onsiteNumbers = $this->userDataModel->getMatchedOnsiteNumber($eventId,$str_search);
            $list = $this->visitorModel->getCmsDatasForPage($curr_page,$this->page_limit,
                $this->userId,$str_search,$onsiteNumbers,$eventId);
            foreach($list as $k => $v){
                if(empty($v['first_name'])){
                    $userId = $this->userDataModel->getUserIdByOnSiteNumber($eventId,$v['serial_number']);
                    if(!empty($userId)){
                        $userDatas = $this->userDataModel->getDataList($userId);
                        $v['first_name'] = Tools::find_array_value($userDatas,'first_name');
                        $v['last_name'] = Tools::find_array_value($userDatas,'last_name');
                        $v['full_name'] = $v['first_name'].' '.$v['last_name'];
                        $v['organization'] = Tools::find_array_value($userDatas,'company');
                        $v['title'] = Tools::find_array_value($userDatas,'job_title');
                        $v['phone'] = Tools::find_array_value($userDatas,'phone_number');
                        foreach($extraKeys as $key){
                            if(!in_array($key,['serial_number','first_name','last_name','full_name','company'
                                ,'job_title','phone_number'])){
                                $v[$key] = Tools::find_array_value($userDatas,$key);
                            }
                        }
                        $list[$k] = $v;
                    }
                }
            }
            $count = $this->visitorModel->getCmsDatasCount($this->userId,$str_search,$onsiteNumbers,$eventId);
            $data = [
                'data'=>$list,
                'total'=>$count
            ];
            return showMsg(200,'ok',$data);
        }else{
            $data = [
                'user'=>$this->userInfo,
                'user_fields'=>$extraFields,
                'exhibitor'=>$this->navHome
            ];
            return view('records', $data);
        }
    }

    public function contact(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $id = $request->param('id', "");
        $this->loadSharedData();
        $visitor = Db::name('xvisitors')
            ->where('id',$id)
            ->find();
        $visitorData = [];
        if(!empty($visitor)){
            $userFields = $this->dataFieldModel->getCmsList($visitor['event_id']);
            if(empty($visitor['first_name'])){
                $userId = $this->userDataModel->getUserIdByOnSiteNumber($visitor['event_id'],$visitor['serial_number']);
                if(!empty($userId)){
                    $userDatas = $this->userDataModel->getDataList($userId);
                    $visitorData[] = ['name'=>'Serial Number','value'=>$visitor['serial_number']];
                    $firstName = Tools::find_array_value($userDatas,'first_name');
                    $lastName = Tools::find_array_value($userDatas,'last_name');
                    $visitorData[] = ['name'=>'First Name','value'=>$firstName];
                    $visitorData[] = ['name'=>'Last Name','value'=>$lastName];
                    $visitorData[] = ['name'=>'Full Name','value'=>$firstName.' '.$lastName];
                    $visitorData[] = ['name'=>'Organization','value'=>Tools::find_array_value($userDatas,'company')];
                    $visitorData[] = ['name'=>'Title','value'=>Tools::find_array_value($userDatas,'job_title')];
                    $visitorData[] = ['name'=>'Phone','value'=>Tools::find_array_value($userDatas,'phone_number')];
                    if($userFields){
                        foreach($userFields as $k => $v){
                            if(!in_array($v['key'],['serial_number','first_name','last_name','full_name','company'
                                ,'job_title','phone_number']) && $v['exhibitor_visible'] == '1'){
                                $visitorData[] = ['name'=>$v['table_name'],'value'=>Tools::find_array_value($userDatas,$v['key'])];
                            }
                        }
                    }
                    $visitorData[] = ['name'=>'Flag','value'=>$visitor['flag']];
                    $visitorData[] = ['name'=>'Remark','value'=>$visitor['remark']];
                    $visitorData[] = ['name'=>'Image Card','value'=>$visitor['img_card']];
                }else{
                    $visitorData[] = ['name'=>'Serial Number','value'=>$visitor['serial_number']];
                    $visitorData[] = ['name'=>'First Name','value'=>$visitor['first_name']];
                    $visitorData[] = ['name'=>'Last Name','value'=>$visitor['last_name']];
                    $visitorData[] = ['name'=>'Full Name','value'=>$visitor['first_name'].' '.$visitor['last_name']];
                    $visitorData[] = ['name'=>'Organization','value'=>$visitor['organization']];
                    $visitorData[] = ['name'=>'Title','value'=>$visitor['title']];
                    $visitorData[] = ['name'=>'Phone','value'=>$visitor['phone']];
                    $visitorData[] = ['name'=>'Email','value'=>$visitor['email']];
                    $visitorData[] = ['name'=>'Flag','value'=>$visitor['flag']];
                    $visitorData[] = ['name'=>'Remark','value'=>$visitor['remark']];
                    $visitorData[] = ['name'=>'Image Card','value'=>$visitor['img_card']];
                }
            }else{
                $visitorData[] = ['name'=>'Serial Number','value'=>$visitor['serial_number']];
                $visitorData[] = ['name'=>'First Name','value'=>$visitor['first_name']];
                $visitorData[] = ['name'=>'Last Name','value'=>$visitor['last_name']];
                $visitorData[] = ['name'=>'Full Name','value'=>$visitor['first_name'].' '.$visitor['last_name']];
                $visitorData[] = ['name'=>'Organization','value'=>$visitor['organization']];
                $visitorData[] = ['name'=>'Title','value'=>$visitor['title']];
                $visitorData[] = ['name'=>'Phone','value'=>$visitor['phone']];
                $visitorData[] = ['name'=>'Email','value'=>$visitor['email']];
                $visitorData[] = ['name'=>'Flag','value'=>$visitor['flag']];
                $visitorData[] = ['name'=>'Remark','value'=>$visitor['remark']];
                $visitorData[] = ['name'=>'Image Card','value'=>$visitor['img_card']];
            }
        }
        $data = [
            'user'=>$this->userInfo,
            'exhibitor'=>$this->navHome,
            'visitor'=>$visitorData
        ];
        return view('contact', $data);
    }

    public function download(Request $request){
        ini_set('max_execution_time', '600');
        ini_set('memory_limit',-1); //没有内存限制
        if(!$this->userId) return redirect('exhibitor/login/index');
        $str_search = $request->param('str_search', '');
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $onsiteNumbers = $this->userDataModel->getMatchedOnsiteNumber($eventId,$str_search);
        $record_num1 = $this->visitorModel->getCmsDatasCount($this->userId,$str_search,$onsiteNumbers,$eventId);
        $articles1 = $this->visitorModel->getCmsDatasForPage2(1,$record_num1,$this->userId,$str_search,
            $onsiteNumbers,$eventId);
        $userFields = $this->dataFieldModel->getCmsList($eventId);
        foreach($articles1 as $k => $v){
            if(empty($v['first_name'])){
                $userId = $this->userDataModel->getUserIdByOnSiteNumber($eventId,$v['serial_number']);
                if(!empty($userId)){
                    $userDatas = $this->userDataModel->getDataList($userId);
                    $v['first_name'] = Tools::find_array_value($userDatas,'first_name');
                    $v['last_name'] = Tools::find_array_value($userDatas,'last_name');
                    $v['full_name'] = $v['first_name'].' '.$v['last_name'];
                    $v['organization'] = Tools::find_array_value($userDatas,'company');
                    $v['title'] = Tools::find_array_value($userDatas,'job_title');
                    $v['phone'] = Tools::find_array_value($userDatas,'phone_number');
                    if($userFields){
                        foreach($userFields as $userField){
                            if(!in_array($userField['key'],['serial_number','first_name','last_name','full_name','company'
                                ,'job_title','phone_number']) && $userField['exhibitor_visible'] == '1'){
                                $v[$userField['key']] = Tools::find_array_value($userDatas,$userField['key']);
                            }
                        }
                    }
                    $articles1[$k] = $v;
                }
            }
        }
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'visitors';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $cellName1 = array();
        $cellName1[] = 'Serial Number';
        $cellName1[] = 'First Name';
        $cellName1[] = 'Last Name';
        $cellName1[] = 'Full Name';
        if($userFields){
            foreach($userFields as $k => $v){
                if(!in_array($v['key'],['serial_number','first_name','last_name','full_name'])
                &&$v['exhibitor_visible'] == '1'){
                    $cellName1[] = $v['table_name'];
                }
            }
        }
//        $cellName1[] = 'Organization';
//        $cellName1[] = 'Title';
//        $cellName1[] = 'Phone';
//        $cellName1[] = 'Email';
        $cellName1[] = 'Flag';
        $cellName1[] = 'Remark';
        $cellName1[] = 'Visit Time';
        $cellName1[] = 'Image Card';
        $objPHPExcel->setActiveSheetIndex(0);
        foreach($cellName1 as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
//            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        foreach($articles1 as $k=>$v){
            $index = 0;
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['serial_number']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['first_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['last_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['full_name']);
//            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['organization']);
//            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['title']);
//            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['phone']);
//            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['email']);
            if($userFields){
                foreach($userFields as $field){
                    if(!in_array($field['key'],['serial_number','first_name','last_name','full_name'])
                        &&$field['exhibitor_visible'] == '1'){
                        $fieldKey = $field['key'];
                        if($field['key'] == 'company') $fieldKey = 'organization';
                        if($field['key'] == 'job_title') $fieldKey = 'title';
                        if($field['key'] == 'phone_number') $fieldKey = 'phone';
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),isset($v[$fieldKey])?$v[$fieldKey] : '');
                    }
                }
            }
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['flag']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['remark']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['visit_time']);
            if(!empty($v['img_card'])){
                $gdImage = imagecreatefromstring(base64_decode($v['img_card']));
                $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
                $objDrawing->setName($v['serial_number']);
                $objDrawing->setDescription($v['serial_number']);
                $objDrawing->setImageResource($gdImage);
                $objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_PNG);
                $objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
                $objDrawing->setHeight(150);
                $objDrawing->setCoordinates($cellKey[$index++].($k+1+$topNumber));
                $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                $objPHPExcel->getActiveSheet()->getRowDimension($k+1+$topNumber)->setRowHeight(150);
            }
            for($i=0;$i<$index;$i++){
                $objPHPExcel->getActiveSheet()->getStyle($cellKey[$i].($k+1+$topNumber))->getAlignment()
                    ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            }
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.urlencode($xlsTitle).'.xls"');
        header("Content-Disposition:attachment;filename=".urlencode($fileName).".xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function customForm(Request $request){
        if($request->isPost()){
            $eventId = $request->param('event_id');
            $companyId = $request->param('company_id');
            $userId = $request->param('user_id');
            $formId = $request->param('form_id');
            $params = $request->post();
            $saveDatas = [];
            foreach($params as $key=>$item){
                if($key != 'event_id' && $key != 'user_id' && $key != 'form_id' && $key != 'company_id'){
                    $saveDatas[] = ['event_id'=>$eventId,'last_update_user'=>$userId,'company_id'=>$companyId,'form_id'=>$formId,
                        'name'=>$key,'value'=>$item,
                        'create_time'=>date('Y-m-d H:i:s',time()),'update_time'=>date('Y-m-d H:i:s',time())];
                }
            }
            Db::transaction(function () use($eventId,$companyId,$formId,$saveDatas){
                Db::name('xform_datas')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])->delete();
                Db::name('xform_datas')->insertAll($saveDatas);
            });
            //更新exhibitorform表单状态为已提交
            Db::name('xexhibitor_forms')->where(['company_id'=>$companyId,'form_id'=>$formId])->update(['status'=>1]);
            return showMsg(1,'Submitted successfully!');
        }else{
            return showMsg(0,'sorry,your request is invalid！');
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

    private function getDefaultValueByKey($arr,$key){
        if(empty($arr) || count($arr) == 0) return '';
        foreach($arr as $value){
            if($value['key'] == $key){
                return $value['default'];
            }
        }
        return '';
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

    private function getMaxByKey($arr,$key){
        if(empty($arr) || count($arr) == 0) return 0;
        foreach($arr as $value){
            if($value['key'] == $key){
                return intval($value['max']);
            }
        }
        return 0;
    }

    public function booth(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        $booth = $this->boothModel->getBooth($company['event_id'],$company['booth_id']);
        $boothAttr = $this->boothAttrModel->getCmsList($company['event_id']);
        $res = [
            'name'=>['label'=>$this->getLabelByKey($boothAttr,'name','Booth Name'),'value'=>$booth['name']],
            'size'=>['label'=>$this->getLabelByKey($boothAttr,'size','Booth Size'),'value'=>$booth['size']],
            'type'=>['label'=>$this->getLabelByKey($boothAttr,'type','Booth Type'),'value'=>$booth['type']],
            'location'=>['label'=>$this->getLabelByKey($boothAttr,'location','Booth Location'),'value'=>$booth['location']],
            'badge'=>['label'=>$this->getLabelByKey($boothAttr,'badge','Booth Badge'),'value'=>$booth['badge']]
        ];
        $data = [
            'user'=>$this->userInfo,
            'exhibitor'=>$this->navHome,
            'topAnnouncements'=>$this->navAnnouncements,
            'notices'=>$this->navNotices,
            'navMarketing'=>$this->navMarketing,
            'navAmenities'=>$this->navAmenities,
            'navBookings'=>$this->navBookings,
            'navBadges'=>$this->navBadges,
            'navManpowers'=>$this->navManpowers,
            'booth' => $res,
            'current_id'=>0,
            'current_marketing_id'=>0,
            'current_amenity_id'=>0,
            'current_booking_id'=>0,
            'current_badge_id'=>0,
            'current_manpower_id'=>0
        ];
        return view('booth', $data);
    }

    public function profile(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $data = [
            'user'=>$this->userInfo,
            'exhibitor'=>$this->navHome,
            'current_id'=>0
        ];
        return view('profile', $data);
    }

    public function showDirectory(Request $request){
        if($request->isPost()){
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $event = $this->eventModel->getCmsEventByID($this->userInfo['event_id']);
            if(strtotime($event['show_directory_start_time']) > time()){
                return showMsg(0,'Sorry,show directory not start yet!');
            }
            if(strtotime($event['show_directory_end_time']) < time()){
                return showMsg(0,'Sorry,show directory has ended!');
            }
            $this->companyModel->updateSubProfile($this->userInfo['company_id'],$request->param());
            return showMsg(1, 'ok');
        }else {
            if (!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $eventId = $this->userInfo['event_id'];
            $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $companyAttr = $this->companyAttrModel->getCmsList($company['event_id']);
            if(empty($company['sub_company_name'])){
                $subParams['sub_company_name'] = $company['name'];
                $subParams['sub_email'] = $company['email'];
                $subParams['sub_country'] = $company['country'];
                $subParams['sub_postal'] = $company['postal'];
                $subParams['sub_address_line1'] = $company['address_line1'];
                $subParams['sub_address_line2'] = $company['address_line2'];
                $subParams['sub_phone_country_code'] = $company['phone_country_code'];
                $subParams['sub_phone_area_code'] = $company['phone_area_code'];
                $subParams['sub_phone_number'] = $company['phone_number'];
                $subParams['sub_fax_country_code'] = $company['fax_country_code'];
                $subParams['sub_fax_area_code'] = $company['fax_area_code'];
                $subParams['sub_fax_number'] = $company['fax_number'];
                $subParams['sub_profile'] = Tools::getHtmlMultiLine($company['profile']);
                $subParams['sub_logo'] = $company['logo'];
                $subParams['sub_industry'] = $company['industry'];
                $subParams['sub_product'] = $company['product'];
                $subParams['sub_website'] = $company['website'];
                $subParams['sub_billing_address_line1'] = $company['billing_address_line1'];
                $subParams['sub_billing_address_line2'] = $company['billing_address_line2'];
                $subParams['sub_billing_country'] = $company['billing_country'];
                $subParams['sub_billing_postal'] = $company['billing_postal'];
                $this->companyModel->updateSubProfile($this->userInfo['company_id'],$subParams);
                $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            }
            $res = [
                'sub_company_name' => ['label' => $this->getLabelByKey($companyAttr, 'name', 'Company Name'), 'value' => $company['sub_company_name']],
                'type'=>['label'=>$this->getLabelByKey($companyAttr,'type','Type'),'value'=>$company['type']],
                'booth' => ['label' => $this->getLabelByKey($companyAttr, 'size', 'Booth'), 'value' => $company['booth']],
                'location'=>['label'=>$this->getLabelByKey($companyAttr,'location','Location'),'value'=>$company['location']],
                'badge'=>['label'=>$this->getLabelByKey($companyAttr,'badge','Badge'),'value'=>$company['badge']],
                'origin_country'=>['label'=>$this->getLabelByKey($companyAttr,'origin_country','Country/Region of Origin'),'value'=>$company['origin_country']],
                'sub_profile'=>['label'=>$this->getLabelByKey($companyAttr,'profile','Profile'),'value'=>Tools::getHtmlMultiLine($company['sub_profile'])],
                'sub_logo'=>['label'=>$this->getLabelByKey($companyAttr,'logo','Logo'),'value'=>$company['sub_logo']],
                'sub_email' => ['label' => $this->getLabelByKey($companyAttr, 'email', 'Email'), 'value' => $company['sub_email']],
                'sub_postal'=>['label'=>$this->getLabelByKey($companyAttr,'postal','Postal/Zip Code'),'value'=>$company['sub_postal']],
                'sub_country' => ['label' => $this->getLabelByKey($companyAttr, 'country', 'Country'), 'value' => $company['sub_country']],
                'sub_address_line1' => ['label' => $this->getLabelByKey($companyAttr, 'address_line1', 'Address Line 1'), 'value' => $company['sub_address_line1']],
                'sub_address_line2' => ['label' => $this->getLabelByKey($companyAttr, 'address_line2', 'Address Line 2'), 'value' => $company['sub_address_line2']],
                'sub_phone'=>['label'=>$this->getLabelByKey($companyAttr,'phone','Business Phone'),'value'=>$company['sub_phone_country_code'].$company['sub_phone_area_code'].$company['sub_phone_number']],
                'sub_fax'=>['label'=>$this->getLabelByKey($companyAttr,'fax','Fax'),'value'=>$company['sub_fax_country_code'].$company['sub_fax_area_code'].$company['sub_fax_number']],
                'sub_website'=>['label'=>$this->getLabelByKey($companyAttr,'website','Website'),'value'=>$company['sub_website']],
                'sub_billing_address_line1'=>['label'=>$this->getLabelByKey($companyAttr,'billing_address_line1','Billing Address Line 1'),'value'=>$company['sub_billing_address_line1']],
                'sub_billing_address_line2'=>['label'=>$this->getLabelByKey($companyAttr,'billing_address_line2','Billing Address Line 2'),'value'=>$company['sub_billing_address_line2']],
                'sub_billing_postal'=>['label'=>$this->getLabelByKey($companyAttr,'billing_postal','Billing Postal/Zip Code'),'value'=>$company['sub_billing_postal']],
                'sub_billing_country'=>['label'=>$this->getLabelByKey($companyAttr,'billing_country','Billing Country/Region'),'value'=>$company['sub_billing_country']],
                'sub_industry'=>['label'=>$this->getLabelByKey($companyAttr,'industry','Industries and Sectors'),'value'=>Tools::getSortHtmlIndustry($company['sub_industry'])],
                'sub_product'=>['label'=>$this->getLabelByKey($companyAttr,'product','Products and Services'),'value'=>$company['sub_product']]
            ];
            $countries = $this->getOptionsByKey($companyAttr,'country');
            $industryMax = $this->getMaxByKey($companyAttr,'industry');
            $productMax = $this->getMaxByKey($companyAttr,'product');
            $data = [
                'user' => $this->userInfo,
                'exhibitor' => $this->navHome,
                'topAnnouncements' => $this->navAnnouncements,
                'notices' => $this->navNotices,
                'navMarketing' => $this->navMarketing,
                'navAmenities' => $this->navAmenities,
                'navBookings' => $this->navBookings,
                'navBadges' => $this->navBadges,
                'navManpowers' => $this->navManpowers,
                'company' => $res,
                'countries'=>$countries,
                'industryMax'=>$industryMax,
                'productMax'=>$productMax,
                'current_id' => 0,
                'current_marketing_id' => 0,
                'current_amenity_id' => 0,
                'current_booking_id' => 0,
                'current_badge_id' => 0,
                'current_manpower_id' => 0
            ];
            return view('show_directory', $data);
        }
    }

    public function bookings(Request $request,$id=0){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $curr_page = $request->param('curr_page', 1);
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        if(empty($id)){
            //检查exhibitorforms表是否有对应的表单数据
            $forms = $this->formModel->getDataList($eventId,'Booking');
            $bookings = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Booking');
            $saveDatas = [];
            foreach($forms as $key=>$item){
                if(!$this->existFormId($bookings,$item['id'])){
                    $saveDatas[] = ['company_id'=>$company['id'],'form_id'=>$item['id'],'status'=>0,'type'=>'Booking','event_id'=>$eventId];
                }
            }
            if(count($saveDatas) > 0){
                $this->exhibitorFormModel->saveAll($saveDatas);
            }
            //重新取出所有的数据
            $bookings = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Booking');
            foreach($bookings as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $form = $this->formModel->getFormFromList($forms,$item['form_id']);
                $item['block_status'] = $this->formModel->getFormBlockStatus($form);
                $bookings[$key] = $item;
            }
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'topAnnouncements'=>$this->navAnnouncements,
                'notices'=>$this->navNotices,
                'navMarketing'=>$this->navMarketing,
                'navAmenities'=>$this->navAmenities,
                'navBookings'=>$this->navBookings,
                'navBadges'=>$this->navBadges,
                'navManpowers'=>$this->navManpowers,
                'bookings'=>$bookings,
                'current_id'=>0,
                'current_marketing_id'=>0,
                'current_amenity_id'=>0,
                'current_booking_id'=>$id,
                'current_badge_id'=>0,
                'current_manpower_id'=>0
            ];
            return view('bookings', $data);
        }else{
            return redirect('/exhibitor/Booking/index',['form_id'=>$id]);
        }
    }

    public function badges(Request $request,$id=0){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $curr_page = $request->param('curr_page', 1);
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        if(empty($id)){
            //检查exhibitorforms表是否有对应的表单数据
            $forms = $this->formModel->getDataList($eventId,'Badge');
            $badges = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Badge');
            $saveDatas = [];
            foreach($forms as $key=>$item){
                if(!$this->existFormId($badges,$item['id'])){
                    $saveDatas[] = ['company_id'=>$company['id'],'form_id'=>$item['id'],'status'=>0,'type'=>'Badge','event_id'=>$eventId];
                }
            }
            if(count($saveDatas) > 0){
                $this->exhibitorFormModel->saveAll($saveDatas);
            }
            //重新取出所有的数据
            $badges = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Badge');
            foreach($badges as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $form = $this->formModel->getFormFromList($forms,$item['form_id']);
                $item['block_status'] = $this->formModel->getFormBlockStatus($form);
                $badges[$key] = $item;
            }
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'topAnnouncements'=>$this->navAnnouncements,
                'notices'=>$this->navNotices,
                'navMarketing'=>$this->navMarketing,
                'navAmenities'=>$this->navAmenities,
                'navBookings'=>$this->navBookings,
                'navBadges'=>$this->navBadges,
                'navManpowers'=>$this->navManpowers,
                'badges'=>$badges,
                'current_id'=>0,
                'current_marketing_id'=>0,
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>$id,
                'current_manpower_id'=>0
            ];
            return view('badges', $data);
        }else{
            return redirect('/exhibitor/Badge/index',['form_id'=>$id]);
        }
    }

    public function manpowers(Request $request,$id=0){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $curr_page = $request->param('curr_page', 1);
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        if(empty($id)){
            //检查exhibitorforms表是否有对应的表单数据
            $forms = $this->formModel->getDataList($eventId,'Manpower');
            $badges = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Manpower');
            $saveDatas = [];
            foreach($forms as $key=>$item){
                if(!$this->existFormId($badges,$item['id'])){
                    $saveDatas[] = ['company_id'=>$company['id'],'form_id'=>$item['id'],'status'=>0,'type'=>'Manpower','event_id'=>$eventId];
                }
            }
            if(count($saveDatas) > 0){
                $this->exhibitorFormModel->saveAll($saveDatas);
            }
            //重新取出所有的数据
            $badges = $this->exhibitorFormModel->getDataList($company['id'],$eventId,'Manpower');
            foreach($badges as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $form = $this->formModel->getFormFromList($forms,$item['form_id']);
                $item['block_status'] = $this->formModel->getFormBlockStatus($form);
                $badges[$key] = $item;
            }
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'topAnnouncements'=>$this->navAnnouncements,
                'notices'=>$this->navNotices,
                'navMarketing'=>$this->navMarketing,
                'navAmenities'=>$this->navAmenities,
                'navBookings'=>$this->navBookings,
                'navBadges'=>$this->navBadges,
                'navManpowers'=>$this->navManpowers,
                'badges'=>$badges,
                'current_id'=>0,
                'current_marketing_id'=>0,
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>0,
                'current_manpower_id'=>$id
            ];
            return view('manpowers', $data);
        }else{
            return redirect('/exhibitor/manpower/index',['form_id'=>$id]);
        }
    }

    public function updatePwd(Request $request)
    {
        if($request->isPost()){
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $input = $request->param();
            $opRes = $this->model->updatePasswordByUser($this->userId,$input);
            return showMsg($opRes['tag']?200:500, $opRes['message']);
        }else{
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'topAnnouncements'=>$this->navAnnouncements,
                'notices'=>$this->navNotices,
                'navMarketing'=>$this->navMarketing,
                'navAmenities'=>$this->navAmenities,
                'navBookings'=>$this->navBookings,
                'navBadges'=>$this->navBadges,
                'navManpowers'=>$this->navManpowers,
                'current_id'=>0,
                'current_marketing_id'=>0,
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>0,
                'current_manpower_id'=>0
            ];
            return view('update_password', $data);
        }
    }

    public function industryView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $industry = $request->param('sub_industry');
            $id = $request->param('id');
            $companyId = isset($id)?$id:$this->userInfo['company_id'];
            $company = $this->companyModel->getCmsDataByID($companyId);
            if($company['type'] != 'Main'){
                $this->companyModel->where('id',$companyId)->update(['industry'=>$industry,'update_time'=>date('Y-m-d H:i:s',time())]);
            }else{
                $this->companyModel->where('id',$companyId)->update(['sub_industry'=>$industry,'update_time'=>date('Y-m-d H:i:s',time())]);
            }
            return showMsg(1, 'update success');
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $id = $request->param('id');
            $companyId = isset($id)?$id:$this->userInfo['company_id'];
            $article = $this->companyModel->getCmsDataByID($companyId);
            $res = $this->companyAttrModel->getDataByKey('industry',$article['event_id']);
            $options = explode("\r\n",$res['options']);
            if($article['type'] != 'Main'){
                $article['sub_industry'] = $article['industry'];
            }
            $data =
                [
                    'article' => $article,
                    'options'=>$options,
                    'industry_attr'=>$res
                ];
            return view('industry_view', $data);
        }
    }

    public function productView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $product = $request->param('sub_product');
            $id = $request->param('id');
            $companyId = isset($id)?$id:$this->userInfo['company_id'];
            $company = $this->companyModel->getCmsDataByID($companyId);
            if($company['type'] != 'Main'){
                $this->companyModel->where('id',$companyId)->update(['product'=>$product,'update_time'=>date('Y-m-d H:i:s',time())]);
            }else{
                $this->companyModel->where('id',$companyId)->update(['sub_product'=>$product,'update_time'=>date('Y-m-d H:i:s',time())]);
            }
            return showMsg(1, 'update success');
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $id = $request->param('id');
            $companyId = isset($id)?$id:$this->userInfo['company_id'];
            $article = $this->companyModel->getCmsDataByID($companyId);
            $res = $this->companyAttrModel->getDataByKey('product',$article['event_id']);
            if($article['type'] != 'Main'){
                $article['sub_product'] = $article['product'];
            }
            $data =
                [
                    'article' => $article,
                    'product_attr'=>$res
                ];
            return view('product_view', $data);
        }
    }

    public function profileView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $profile = $request->param('sub_profile');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_profile'=>$profile,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('profile_view', $data);
        }
    }

    public function logoView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $logo = $request->param('sub_logo');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_logo'=>$logo,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('logo_view', $data);
        }
    }

    public function addressLine1View(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param = $request->param('sub_address_line1');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_address_line1'=>$param,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('line1_view', $data);
        }
    }

    public function addressLine2View(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param = $request->param('sub_address_line2');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_address_line2'=>$param,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('line2_view', $data);
        }
    }

    public function postalView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param = $request->param('sub_postal');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_postal'=>$param,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('postal_view', $data);
        }
    }

    public function countryView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param = $request->param('sub_country');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_country'=>$param,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $companyAttr = $this->companyAttrModel->getCmsList($article['event_id']);
            $countries = $this->getOptionsByKey($companyAttr,"country");
            $data =
                [
                    'article' => $article,
                    'countries' => $countries
                ];
            return view('country_view', $data);
        }
    }

    public function phoneView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param1 = $request->param('sub_phone_country_code');
            $param2 = $request->param('sub_phone_area_code');
            $param3 = $request->param('sub_phone_number');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])
                ->update(['sub_phone_country_code'=>$param1,'sub_phone_area_code'=>$param2,'sub_phone_number'=>$param3,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('phone_view', $data);
        }
    }

    public function faxView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param1 = $request->param('sub_fax_country_code');
            $param2 = $request->param('sub_fax_area_code');
            $param3 = $request->param('sub_fax_number');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])
                ->update(['sub_fax_country_code'=>$param1,'sub_fax_area_code'=>$param2,'sub_fax_number'=>$param3,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('fax_view', $data);
        }
    }

    public function websiteView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $param = $request->param('sub_website');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_website'=>$param,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('website_view', $data);
        }
    }

    public function sharerList(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        $total = $this->companyModel->getCmsDatasCount('',$this->userInfo['event_id'],$this->userInfo['company_id']);
        $articles = $this->companyModel->getCmsDatasForPage(1,$total,'',
            $this->userInfo['event_id'],$this->userInfo['company_id']);
        $data =
            [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'topAnnouncements'=>$this->navAnnouncements,
                'notices'=>$this->navNotices,
                'navMarketing'=>$this->navMarketing,
                'navAmenities'=>$this->navAmenities,
                'navBookings'=>$this->navBookings,
                'navBadges'=>$this->navBadges,
                'navManpowers'=>$this->navManpowers,
                'current_id'=>0,
                'current_marketing_id'=>0,
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>0,
                'current_manpower_id'=>0,
                'articles' => $articles
            ];
        return view('sharer_list', $data);
    }

    public function addSharer(Request $request)
    {
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->companyModel->addData($input);
            return showMsg($opRes['tag']?200:500, $opRes['message']);
        } else {
            $parentId = $request->param('parent_id');
            $parentCompany = $this->companyModel->getCmsDataByID($parentId);
            $data =
                [
                    'user'=>$this->userInfo,
                    'parent' => $parentCompany
                ];
            return view('add_sharer',$data);
        }
    }

    public function editSharer(Request $request, $id='')
    {
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        if ($request->isPost()) {
            $opRes = $this->companyModel->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag']?200:500, $opRes['message']);
        } else {
            $article = $this->companyModel->getCmsDataByID($id);
            $parentId = $request->param('parent_id');
            $parentCompany = $this->companyModel->getCmsDataByID($parentId);
            $data =
                [
                    'user'=>$this->userInfo,
                    'parent' => $parentCompany,
                    'article' => $article
                ];
            return view('edit_sharer', $data);
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
            $booths = $this->boothModel->getCmsList($eventId);
            $data = [
                'companyAttrs'=>$companyAttrs,
                'titles'=>$titles,
                'booths'=>$booths
            ];
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0,'sorry,your request is invalid！');
        }
    }

    public function freights(Request $request){
        if (!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $freight = $this->freightModel->getCmsData($eventId);
        $data = [
            'user' => $this->userInfo,
            'exhibitor' => $this->navHome,
            'topAnnouncements' => $this->navAnnouncements,
            'notices' => $this->navNotices,
            'navMarketing' => $this->navMarketing,
            'navAmenities' => $this->navAmenities,
            'navBookings' => $this->navBookings,
            'navBadges' => $this->navBadges,
            'navManpowers' => $this->navManpowers,
            'freight' => $freight,
            'current_id' => 0,
            'current_marketing_id' => 0,
            'current_amenity_id' => 0,
            'current_booking_id' => 0,
            'current_badge_id' => 0,
            'current_manpower_id' => 0
        ];
        return view('freights', $data);
    }

    public function companyView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $company = $request->param('sub_company_name');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_company_name'=>$company,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('company_view', $data);
        }
    }

    public function emailView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $email = $request->param('sub_email');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_email'=>$email,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('email_view', $data);
        }
    }

    public function billingAddressLine1View(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $billingAddressLine1 = $request->param('sub_billing_address_line1');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])->update(['sub_billing_address_line1'=>$billingAddressLine1,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('billing_line1_view', $data);
        }
    }

    public function billingAddressLine2View(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $billingAddressLine2 = $request->param('sub_billing_address_line2');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])
                ->update(['sub_billing_address_line2'=>$billingAddressLine2,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('billing_line2_view', $data);
        }
    }

    public function billingCountryView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $billingCountry = $request->param('sub_billing_country');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])
                ->update(['sub_billing_country'=>$billingCountry,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $companyAttr = $this->companyAttrModel->getCmsList($article['event_id']);
            $countries = $this->getOptionsByKey($companyAttr,"billing_country");
            $data =
                [
                    'article' => $article,
                    'countries' => $countries
                ];
            return view('billing_country_view', $data);
        }
    }

    public function billingPostalView(Request $request){
        if ($request->isPost()) {
            if(!$this->userId){
                return showMsg(401,'login');
            }
            $this->loadSharedData();
            $billingPostal = $request->param('sub_billing_postal');
            $opRes = $this->companyModel->where('id',$this->userInfo['company_id'])
                ->update(['sub_billing_postal'=>$billingPostal,'update_time'=>date('Y-m-d H:i:s',time())]);
            if($opRes){
                return showMsg(1, 'update success');
            }else{
                return showMsg(0, 'update failed');
            }
        } else {
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $article = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $data =
                [
                    'article' => $article
                ];
            return view('billing_postal_view', $data);
        }
    }
}
