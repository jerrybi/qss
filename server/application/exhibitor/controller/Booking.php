<?php


namespace app\exhibitor\controller;


use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\model\Xannouncements;
use app\common\model\XboothAttrs;
use app\common\model\XdataFields;
use app\common\model\XcatalogAttrs;
use app\common\model\Xcatalogs;
use app\common\model\Xcompanies;
use app\common\model\XcompanyAttrs;
use app\common\model\Xevents;
use app\common\model\XexhibitorForms;
use app\common\model\Xconfigs;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\XBooking;
use app\common\model\XlocationGroups;
use app\common\model\Xlocations;
use app\common\model\XmailSettings;
use app\common\model\Xnotices;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use think\facade\Env;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use app\common\lib\Email;
use think\Db;

class Booking extends UserBase
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
    private $bookingModel;
    private $catalogModel;
    private $catalogAttrModel;
    private $locationGroupModel;
    private $locationModel;
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
        $this->bookingModel = new XBooking();
        $this->catalogModel = new Xcatalogs();
        $this->catalogAttrModel = new XcatalogAttrs();
        $this->eventModel = new Xevents();
        $this->vendorModel = new Xvendors();
        $this->locationGroupModel = new XlocationGroups();
        $this->locationModel = new Xlocations();
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
            $locationId = $request->param('location_id');
            $res = $this->bookingModel->addCmsData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId,$locationId,$request->param());
            if($res['tag']){
                $isDraft = $request->param("is_draft",0);
                if(!$isDraft){
//                    $this->sendConfirmationMail($this->userInfo,$formId);
                    $task = [
                        'id'=>Tools::create_guid(),
                        'name'=>'exhibitor_send_mail',
                        'data'=>json_encode(['type'=>'Booking','user_id'=>$this->userInfo['unique_id'],'form_id'=>$formId]),
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
            $formData = $this->bookingModel->getLastData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId);
            $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $count = ((strtotime($form['due_date'])-strtotime($form['early_submission_date']))/86400)+1;
            $days = [];
            for($i=0;$i<$count;$i++){
                $days[] = strtotime('+'.$i.' day',strtotime($form['early_submission_date']));
            }
            $vendor = $this->vendorModel->getCmsDataByID($form['vendor_id']);
            $vendorAccounts = $this->model->getVendorAccounts($form['vendor_accounts']);
            $locationGroups = $this->locationGroupModel->getList($this->userInfo['event_id']);
            $locations = [];
            foreach($locationGroups as $value){
                $resLocations = $this->locationModel->getListByGroup($value['id']);
                if(count($resLocations) > 0){
                    $items = [];
                    foreach($resLocations as $v){
                        $items[] = ['title'=>$v['name'],'id'=>$v['id']];
                    }
                    $locations[] = ['title'=>$value['name'],'id'=>$value['id'],'child'=>$items];
                }
            }
            if(count($formData) > 0){
                $formData1 = $formData[0];
            }else{
                $submitter = $this->userInfo['first_name'].' '.$this->userInfo['last_name'];
                $formData1 = ['is_new_product'=>1,'title'=>'','synopsis'=>'','product_img_url'=>'','speaker_cv'=>'',
                    'speaker_img_url'=>'','form_id'=>$formId,'location_id'=>'','location_group_id'=>'',
		            'form_submit_name'=>$submitter,'form_submit_designation'=>$submitter,
                    'form_submit_email'=>$this->userInfo['email'], 'form_submit_instruction'=>''];
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
                'formData'=>$formData,//whole data
                'formData1'=>$formData1,//extra parameters
                'days'=>$days,
                'vendor'=>$vendor,
                'vendorAccounts'=>$vendorAccounts,
                'blockStatus'=>$blockStatus,
                'current_id'=>0,
                'current_marketing_id'=>$form['id'],
                'current_amenity_id'=>0,
                'current_booking_id'=>0,
                'current_badge_id'=>0,
                'current_manpower_id'=>0,
                'record_num'=>count($formData),
                'page_limit'=>1000,
                'company'=>$company,
                'location'=>$locations
            ];
            return view('index', $data);
        }
    }

    private function isSlotUsed($time,$times){
        if($time == 'LUNCH') return 2;
        foreach($times as $k=>$v){
            if($time == $v['presentation_time']){
                return 1;
            }
        }
        return 0;
    }

    public function slot(Request $request){
        if(!$this->userId) return redirect('exhibitor/login/index');
        $this->loadSharedData();
        $formId = $request->param('form_id');
        $locationId = $request->param('location_id');
        $form = $this->formModel->getData($formId);
        $formData = $this->bookingModel->getLastData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId);
        $count = ((strtotime($form['due_date'])-strtotime($form['early_submission_date']))/86400)+1;
        $days = [];
        $articles = [];
        $times = ['1100 hrs','1130 hrs','1200 hrs','LUNCH','1400 hrs','1430 hrs','1500 hrs','1530 hrs','1600 hrs'];
        for($i=0;$i<$count;$i++){
            $day = strtotime('+'.$i.' day',strtotime($form['early_submission_date']));
            $days[] = $day;
            $res = $this->bookingModel->getUsedDateTime($this->userInfo['event_id'],Date('Y-m-d',$day),$times,$formId,$locationId);
            $timeItem = [];
            foreach($times as $k=>$v){
                $status = $this->isSlotUsed($v,$res);
                $timeItem[$v] = $status;
            }
            $articles[$day] = $timeItem;
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
            'form' => $form,
            'days'=>$days,
            'times'=>$times,
            'articles'=>$articles,
            'current_id'=>0,
            'current_marketing_id'=>$form['id'],
            'current_amenity_id'=>0,
            'current_booking_id'=>0,
            'current_badge_id'=>0,
            'current_manpower_id'=>0,
            'record_num'=>count($formData),
            'page_limit'=>1000
        ];
        return view('slot', $data);
    }
}