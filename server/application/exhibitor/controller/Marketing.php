<?php


namespace app\exhibitor\controller;


use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\lib\LogUtil;
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
use app\common\model\XDynamicForm;
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

class Marketing extends UserBase
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
    private $dynamicFormModel;
    private $catalogModel;
    private $catalogAttrModel;
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
        $this->dynamicFormModel = new XDynamicForm();
        $this->catalogModel = new Xcatalogs();
        $this->catalogAttrModel = new XcatalogAttrs();
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
            $res = $this->dynamicFormModel->addCmsData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId,$request->param());
            if($res['tag']){
                $isDraft = $request->param("is_draft",0);
                if(!$isDraft){
//                    $this->sendConfirmationMail($this->userInfo,$formId);
                    $task = [
                        'id'=>Tools::create_guid(),
                        'name'=>'exhibitor_send_mail',
                        'data'=>json_encode(['type'=>'Marketing','user_id'=>$this->userInfo['unique_id'],'form_id'=>$formId]),
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
            $orderItems = explode("\r\n",$form['order_items']);
            $items = [];
            foreach($orderItems as $category){
                $options = $this->catalogAttrModel->getSubCategories($this->userInfo['event_id'],$category,'Marketing');
                foreach($options as $value){
                    $res = $this->catalogModel->getCmsListByCategory($this->userInfo['event_id'],$category,$value,'Marketing');
                    if(count($res) > 0){
                        if(time() <= strtotime($form['early_submission_date'])){
                            foreach($res as $k=>$v){
                                $v['item_price'] = $v['advanced_rate'];
                                $items[] = $v;
                            }
                        }else{
                            if(time() > strtotime($form['due_date'])){
                                foreach($res as $k=>$v){
                                    $v['item_price'] = $v['have_onsite_rate']?$v['onsite_rate']:$v['standard_rate'];
                                    $items[] = $v;
                                }
                            }else{
                                foreach($res as $k=>$v){
                                    $v['item_price'] = $v['standard_rate'];
                                    $items[] = $v;
                                }
                            }
                        }
                    }
                }
            }
            $formData = $this->dynamicFormModel->getLastData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId,false);
            $dynamicData = $this->dynamicFormModel->getLastData($this->userInfo['event_id'],$this->userInfo['company_id'],$formId,true);
            $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
            $vendor = $this->vendorModel->getCmsDataByID($form['vendor_id']);
            $vendorAccounts = $this->model->getVendorAccounts($form['vendor_accounts']);
	        if(count($formData) > 0){
                $formData1 = $formData[0];
                if(empty($formData1['item_name'])){
                    $formData = [];
                }
            }else{
                $submitter = $this->userInfo['first_name'].' '.$this->userInfo['last_name'];
                $formData1 = ['form_submit_name'=>$submitter,'form_submit_designation'=>$submitter,
                    'form_submit_email'=>$this->userInfo['email'],
		        ];
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
                'dynamicData'=>$dynamicData,
                'items'=>$items,
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
                'company'=>$company
            ];
            return view('index', $data);
        }
    }
}