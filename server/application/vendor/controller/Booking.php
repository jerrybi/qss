<?php


namespace app\vendor\controller;


use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\model\XcatalogAttrs;
use app\common\model\Xcatalogs;
use app\common\model\Xcompanies;
use app\common\model\XcompanyAttrs;
use app\common\model\Xconfigs;
use app\common\model\Xzones;
use app\common\model\XBooking;
use app\common\model\XlocationGroups;
use app\common\model\Xlocations;
use app\common\model\Xorders;
use app\common\model\Xusers;
use think\facade\Env;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;

class Booking extends UserBase
{
    private $model;
    private $exhibitorModel;
    private $formModel;
    private $companyModel;
    private $companyAttrModel;
    protected $userId;
    protected $userInfo;
    protected $navHome;
    private $bookingModel;
    private $catalogModel;
    private $catalogAttrModel;
    private $locationGroupModel;
    private $locationModel;
    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->exhibitorModel = new Xconfigs();
        $this->formModel = new Xzones();
        $this->companyModel = new Xcompanies();
        $this->companyAttrModel = new XcompanyAttrs();
        $this->bookingModel = new XBooking();
        $this->catalogModel = new Xcatalogs();
        $this->catalogAttrModel = new XcatalogAttrs();
        $this->locationGroupModel = new XlocationGroups();
        $this->locationModel = new Xlocations();
        $this->userId = IAuth::getVendorUserIDCurrLogged();
        if(!$this->userId){
            return redirect('vendor/login/index');
        }
    }

    private function loadSharedData(){
        $this->userInfo = $this->model->getCmsDataByID($this->userId);
        $this->navHome = $this->exhibitorModel->getCmsData($this->userInfo['event_id']);
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

    public function index(Request $request){
        if(!$this->userId) return redirect('vendor/login/index');
        $this->loadSharedData();
        $formId = $request->param("form_id");
        $locationId = $request->param("location_id");
        $form = $this->formModel->getCmsDataByID($formId);
        if($request->isPost()){
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
                'form' => $form,
                'days'=>$days,
                'times'=>$times,
                'articles'=>$articles,
                'record_num'=>count($formData),
                'page_limit'=>1000
            ];
            return showMsg(1,'ok', $data);
        }else{
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
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'form' => $form,
                'location'=>$locations
            ];
            return view('index',$data);
        }
    }
}