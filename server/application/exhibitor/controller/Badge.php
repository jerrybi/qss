<?php


namespace app\exhibitor\controller;


use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\model\Xannouncements;
use app\common\model\XboothAttrs;
use app\common\model\XdataFields;
use app\common\model\Xcompanies;
use app\common\model\XcompanyAttrs;
use app\common\model\XexhibitorForms;
use app\common\model\Xconfigs;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\XBadge;
use app\common\model\Xnotices;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use app\common\model\XmailSettings;
use app\common\model\Xevents;
use think\facade\Env;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use app\common\lib\Email;
use think\Db;

class Badge extends UserBase
{
    private $model;
    private $exhibitorModel;
    private $announcementModel;
    private $noticeModel;
    private $formModel;
    private $exhibitorFormModel;
    private $formDataModel;
    private $companyModel;
    private $boothModel;
    private $boothAttrModel;
    private $companyAttrModel;
    private $vendorModel;
    private $mailSettingModel;
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
    private $badgeModel;
    private $eventModel;
    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->exhibitorModel = new Xconfigs();
        $this->announcementModel = new Xannouncements();
        $this->noticeModel = new Xnotices();
        $this->formModel = new Xzones();
        $this->exhibitorFormModel = new XexhibitorForms();
        $this->formDataModel = new XformDatas();
        $this->companyModel = new Xcompanies();
        $this->boothModel = new XdataFields();
        $this->boothAttrModel = new XboothAttrs();
        $this->companyAttrModel = new XcompanyAttrs();
        $this->badgeModel = new XBadge();
        $this->eventModel = new Xevents();
        $this->vendorModel = new Xvendors();
        $this->mailSettingModel = new XmailSettings();
        $this->userId = IAuth::getUserIDCurrLogged();
        if(!$this->userId){
            return redirect('exhibitor/login/index');
        }
    }

    private function loadSharedData(){
        $this->userInfo = $this->model->getCmsDataByID($this->userId);
        $this->navHome = $this->exhibitorModel->getCmsData($this->userInfo['event_id']);
        $this->navAnnouncements = $this->announcementModel->getTopAnnouncements($this->userInfo['event_id']);
        $this->navMarketing = $this->formModel->getSimpleList($this->userInfo['event_id'],'Marketing');
        $this->navAmenities = $this->formModel->getSimpleList($this->userInfo['event_id'],'Operations');
        $this->navNotices = $this->noticeModel->getCmsList($this->userInfo['event_id']);
        $this->navBookings = $this->formModel->getSimpleList($this->userInfo['event_id'],'Booking');
        $this->navBadges = $this->formModel->getSimpleList($this->userInfo['event_id'],'Badge');
        $this->navManpowers = $this->formModel->getSimpleList($this->userInfo['event_id'],'Manpower');
    }

    public function index(Request $request){
        if($request->isPost()){
            if(!$this->userId){
                return showMsg(401,'you need to login');
            }
            $this->loadSharedData();
            $formId = $request->param('form_id');
            $res = $this->badgeModel->addCmsData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId,$request->param());
            if($res['tag']){
                $isDraft = $request->param("is_draft",0);
                if(!$isDraft){
//                    $this->sendConfirmationMail($this->userInfo,$formId);
                    $task = [
                        'id'=>Tools::create_guid(),
                        'name'=>'exhibitor_send_mail',
                        'data'=>json_encode(['type'=>'Badge','user_id'=>$this->userInfo['unique_id'],'form_id'=>$formId]),
                        'status'=>0,
                        'create_time'=>Date('Y-m-d H:i:s',time())
                    ];
                    Db::name('xtasks')->insert($task);
                }
                return showMsg(1,$res['message']);
            }else{
                return showMsg(0,$res['message']);
            }
        }else{
            if(!$this->userId) return redirect('exhibitor/login/index');
            $this->loadSharedData();
            $formId = $request->param('form_id');
            $form = $this->formModel->getData($formId);
            $formData = $this->badgeModel->getLastData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId);
            $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $vendor = $this->vendorModel->getCmsDataByID($form['vendor_id']);
            $vendorAccounts = $this->model->getVendorAccounts($form['vendor_accounts']);
	        if(count($formData) > 0){
                $formData1 = $formData[0];
                if(empty($formData1['salutation'])){
                    $formData = [];
                }
            }else{
	            $submitter = $this->userInfo['first_name'].' '.$this->userInfo['last_name'];
                $formData1 = ['badge_submit_name'=>'','badge_submit_designation'=>'','form_id'=>$formId,
                'form_submit_name'=>$submitter,'form_submit_designation'=>$submitter,'form_submit_email'=>$this->userInfo['email'],
                    'form_submit_instruction'=>''];
            }
            $blockStatus = $this->formModel->getFormBlockStatus($form);
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
                'form' => $form,
                'formData'=>$formData,
                'formData1'=>$formData1,
                'vendor'=>$vendor,
                'vendorAccounts'=>$vendorAccounts,
                'blockStatus'=>$blockStatus,
                'current_id'=>0,
                'current_marketing_id'=>0,
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>$form['id'],
                'current_manpower_id'=>0,
                'record_num'=>count($formData),
                'page_limit'=>1000,
                'company'=>$company
            ];
            return view('index', $data);
        }
    }

    public function template(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        $formId = $request->param('id');
        $articles = $this->badgeModel->getLastData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId);
        $form = $this->formModel->getCmsDataByID($formId);
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'BadgeTemplate';
        $fileName = $xlsTitle;
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $cellName = array();
        $cellName[] = 'Booth No';
        $cellName[] = 'Exhibiting Company';
        $cellName[] = 'SQM';
        $cellName[] = 'Rank';
        $cellName[] = 'Salutation';
        $cellName[] = 'First Name';
        $cellName[] = 'Last Name';
        $cellName[] = 'Name On Badge';
        $cellName[] = 'Position/Job Title';
        $cellName[] = 'Company Name';
        $cellName[] = 'Email';
        $cellName[] = 'Mobile No';
        $cellName[] = 'Country/Region of Residence';
        $cellName[] = 'Vaccination Type';
        $cellName[] = 'Vaccination Effective Date(2 weeks after second dose)';
        $cellName[] = 'Date of Entry';
        $cellName[] = 'Date of Exit';
        $cellName[] = 'Last City/Port of Embarkation Before Singapore';
//        $cellName[] = 'Authorizer Person  (Pick up badge for the group)';
//        $cellName[] = 'Authorizer\'s Designation (Pick up badge for the group)';
        if($form['show_dob'] == 1){
            $cellName[] = 'Date of Birth';
            $cellName[] = 'NRIC (last 4 Digits) / FIN NO';
        }
//        $cellName[] = 'Organisation/Requesting Agency';
        $cellName[] = 'Submitted By';
        $cellName[] = 'Submittor\'s Designation';
        $cellName[] = 'Submitor\'s Email';
        $cellName[] = 'Further Instruction';
//        $cellName[] = 'Timestamp';

        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane('A'.($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        //处理数据
        if(count($articles) > 0){
            foreach ($articles as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$v['booth_no']);
                $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['exhibiting_company']);
                $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),$v['sqm']);
                $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),$v['rank']);
                $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['salutation']);
                $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['first_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['last_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['badge_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),$v['job_title']);
                $objPHPExcel->getActiveSheet()->setCellValue('J'.($k+1+$topNumber),$v['company']);
                $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),$v['email']);
                $objPHPExcel->getActiveSheet()->setCellValue('L'.($k+1+$topNumber),$v['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('M'.($k+1+$topNumber),$v['country']);
                $objPHPExcel->getActiveSheet()->setCellValue('N'.($k+1+$topNumber),$v['vaccination_type']);
                $objPHPExcel->getActiveSheet()->setCellValue('O'.($k+1+$topNumber),$v['vaccination_effective_date']);
                $objPHPExcel->getActiveSheet()->setCellValue('P'.($k+1+$topNumber),$v['vaccination_entry_date']);
                $objPHPExcel->getActiveSheet()->setCellValue('Q'.($k+1+$topNumber),$v['vaccination_exit_date']);
                $objPHPExcel->getActiveSheet()->setCellValue('R'.($k+1+$topNumber),$v['vaccination_last_city']);
//                $objPHPExcel->getActiveSheet()->setCellValue('S'.($k+1+$topNumber),$v['vaccination_authorizer_person']);
//                $objPHPExcel->getActiveSheet()->setCellValue('T'.($k+1+$topNumber),$v['vaccination_authorizer_designation']);
                if($form['show_dob'] == 1){
                    $objPHPExcel->getActiveSheet()->setCellValue('S'.($k+1+$topNumber),$v['dob']);
                    $objPHPExcel->getActiveSheet()->setCellValue('T'.($k+1+$topNumber),$v['nric']);
                    $objPHPExcel->getActiveSheet()->setCellValue('U'.($k+1+$topNumber),$v['badge_submit_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('V'.($k+1+$topNumber),$v['badge_submit_designation']);
                    $objPHPExcel->getActiveSheet()->setCellValue('W'.($k+1+$topNumber),$v['badge_submit_email']);
                    $objPHPExcel->getActiveSheet()->setCellValue('X'.($k+1+$topNumber),$v['badge_submit_instruction']);
                }else{
                    $objPHPExcel->getActiveSheet()->setCellValue('S'.($k+1+$topNumber),$v['badge_submit_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('T'.($k+1+$topNumber),$v['badge_submit_designation']);
                    $objPHPExcel->getActiveSheet()->setCellValue('U'.($k+1+$topNumber),$v['badge_submit_email']);
                    $objPHPExcel->getActiveSheet()->setCellValue('V'.($k+1+$topNumber),$v['badge_submit_instruction']);
                }
//                $objPHPExcel->getActiveSheet()->setCellValue('Y'.($k+1+$topNumber),$v['badge_submit_timestamp']);
            }
        }else{
            $k = 0;
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),'e.g. MAP10');
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),'e.g. Company name Pte Ltd');
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),'12');
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),'Colonel');
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),'Mr');
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),'e.g Joseph');
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),'e.g. Maptip');
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),'e.g. Joseph Map');
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),'e.g. Operations Director');
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($k+1+$topNumber),'e.g. Company name Pte Ltd');
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),'e.g. josmap@company.com');
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($k+1+$topNumber),'e.g. 6590987654');
            $objPHPExcel->getActiveSheet()->setCellValue('M'.($k+1+$topNumber),'e.g. Singapore');
            $objPHPExcel->getActiveSheet()->setCellValue('N'.($k+1+$topNumber),'e.g. Pfizer');
            $objPHPExcel->getActiveSheet()->setCellValue('O'.($k+1+$topNumber),'e.g. 2021-08-01');
            $objPHPExcel->getActiveSheet()->setCellValue('P'.($k+1+$topNumber),'e.g. 2021-08-03');
            $objPHPExcel->getActiveSheet()->setCellValue('Q'.($k+1+$topNumber),'e.g.2021-08-18');
            $objPHPExcel->getActiveSheet()->setCellValue('R'.($k+1+$topNumber),'e.g. UAE');
            if($form['show_dob'] == 1){
                $objPHPExcel->getActiveSheet()->setCellValue('S'.($k+1+$topNumber),'e.g. 1977-08-01');
                $objPHPExcel->getActiveSheet()->setCellValue('T'.($k+1+$topNumber),'e.g. Optional');
                $objPHPExcel->getActiveSheet()->setCellValue('U'.($k+1+$topNumber),'e.g. Mary');
                $objPHPExcel->getActiveSheet()->setCellValue('V'.($k+1+$topNumber),'e.g. Ong');
                $objPHPExcel->getActiveSheet()->setCellValue('W'.($k+1+$topNumber),'e.g. maryong@company.com');
                $objPHPExcel->getActiveSheet()->setCellValue('X'.($k+1+$topNumber),'e.g. Mary will come to collect the badges');
            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('S'.($k+1+$topNumber),'e.g. Mary');
                $objPHPExcel->getActiveSheet()->setCellValue('T'.($k+1+$topNumber),'e.g. Ong');
                $objPHPExcel->getActiveSheet()->setCellValue('U'.($k+1+$topNumber),'e.g. maryong@company.com');
                $objPHPExcel->getActiveSheet()->setCellValue('V'.($k+1+$topNumber),'e.g. Mary will come to collect the badges');
            }
//            $objPHPExcel->getActiveSheet()->setCellValue('Y'.($k+1+$topNumber),'2021-08-18');
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function uploadBadge(Request $request){
        $fileUrl = $request->param('file_url');
        $showDob = $request->param('show_dob');
        $fileUrl = urldecode($fileUrl);
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
            if($showDob == 1){
                $data[] = [
                    'id'=>time().''.$rowIndex,
                    'booth_no'=>(string)($currentSheet->getCell('A'.$rowIndex)->getValue()),
                    'exhibiting_company'=>(string)($currentSheet->getCell('B'.$rowIndex)->getValue()),
                    'sqm'=>(string)($currentSheet->getCell('C'.$rowIndex)->getValue()),
                    'rank'=>(string)($currentSheet->getCell('D'.$rowIndex)->getValue()),
                    'salutation'=>(string)($currentSheet->getCell('E'.$rowIndex)->getValue()),
                    'first_name'=>(string)($currentSheet->getCell('F'.$rowIndex)->getValue()),
                    'last_name'=>(string)($currentSheet->getCell('G'.$rowIndex)->getValue()),
                    'badge_name'=>(string)($currentSheet->getCell('H'.$rowIndex)->getValue()),
                    'job_title'=>(string)($currentSheet->getCell('I'.$rowIndex)->getValue()),
                    'company'=>(string)($currentSheet->getCell('J'.$rowIndex)->getValue()),
                    'email'=>(string)($currentSheet->getCell('K'.$rowIndex)->getValue()),
                    'mobile'=>(string)($currentSheet->getCell('L'.$rowIndex)->getValue()),
                    'country'=>(string)($currentSheet->getCell('M'.$rowIndex)->getValue()),
                    'vaccination_type'=>(string)($currentSheet->getCell('N'.$rowIndex)->getValue()),
                    'vaccination_effective_date'=>(string)($currentSheet->getCell('O'.$rowIndex)->getFormattedValue()),
                    'vaccination_entry_date'=>(string)($currentSheet->getCell('P'.$rowIndex)->getFormattedValue()),
                    'vaccination_exit_date'=>(string)($currentSheet->getCell('Q'.$rowIndex)->getFormattedValue()),
                    'vaccination_last_city'=>(string)($currentSheet->getCell('R'.$rowIndex)->getValue()),
                    'dob'=>(string)($currentSheet->getCell('S'.$rowIndex)->getValue()),
                    'nric'=>(string)($currentSheet->getCell('T'.$rowIndex)->getValue()),
                    'badge_submit_name'=>(string)($currentSheet->getCell('U'.$rowIndex)->getValue()),
                    'badge_submit_designation'=>(string)($currentSheet->getCell('V'.$rowIndex)->getValue()),
                    'badge_submit_email'=>(string)($currentSheet->getCell('W'.$rowIndex)->getValue()),
                    'badge_submit_instruction'=>(string)($currentSheet->getCell('X'.$rowIndex)->getValue())
                ];
            }else{
                $data[] = [
                    'id'=>time().''.$rowIndex,
                    'booth_no'=>(string)($currentSheet->getCell('A'.$rowIndex)->getValue()),
                    'exhibiting_company'=>(string)($currentSheet->getCell('B'.$rowIndex)->getValue()),
                    'sqm'=>(string)($currentSheet->getCell('C'.$rowIndex)->getValue()),
                    'rank'=>(string)($currentSheet->getCell('D'.$rowIndex)->getValue()),
                    'salutation'=>(string)($currentSheet->getCell('E'.$rowIndex)->getValue()),
                    'first_name'=>(string)($currentSheet->getCell('F'.$rowIndex)->getValue()),
                    'last_name'=>(string)($currentSheet->getCell('G'.$rowIndex)->getValue()),
                    'badge_name'=>(string)($currentSheet->getCell('H'.$rowIndex)->getValue()),
                    'job_title'=>(string)($currentSheet->getCell('I'.$rowIndex)->getValue()),
                    'company'=>(string)($currentSheet->getCell('J'.$rowIndex)->getValue()),
                    'email'=>(string)($currentSheet->getCell('K'.$rowIndex)->getValue()),
                    'mobile'=>(string)($currentSheet->getCell('L'.$rowIndex)->getValue()),
                    'country'=>(string)($currentSheet->getCell('M'.$rowIndex)->getValue()),
                    'vaccination_type'=>(string)($currentSheet->getCell('N'.$rowIndex)->getValue()),
                    'vaccination_effective_date'=>(string)($currentSheet->getCell('O'.$rowIndex)->getFormattedValue()),
                    'vaccination_entry_date'=>(string)($currentSheet->getCell('P'.$rowIndex)->getFormattedValue()),
                    'vaccination_exit_date'=>(string)($currentSheet->getCell('Q'.$rowIndex)->getFormattedValue()),
                    'vaccination_last_city'=>(string)($currentSheet->getCell('R'.$rowIndex)->getValue()),
                    'badge_submit_name'=>(string)($currentSheet->getCell('S'.$rowIndex)->getValue()),
                    'badge_submit_designation'=>(string)($currentSheet->getCell('T'.$rowIndex)->getValue()),
                    'badge_submit_email'=>(string)($currentSheet->getCell('U'.$rowIndex)->getValue()),
                    'badge_submit_instruction'=>(string)($currentSheet->getCell('V'.$rowIndex)->getValue())
                ];
            }
        }
        return showMsg(1,'ok',$data);
    }
}