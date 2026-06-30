<?php
namespace app\vendor\controller;

use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\model\Xarticles;
use app\common\model\Xvendors;
use app\common\model\XvendorAttrs;
use app\common\model\Xevents;
use app\common\model\Xconfigs;
use app\common\model\XvendorForms;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\Xusers;
use think\facade\Env;
use think\Request;
use think\Db;

class Index extends UserBase
{
    private $model;
    private $exhibitorModel;
    private $formModel;
    private $formDataModel;
    private $companyModel;
    private $companyAttrModel;
    protected $userId;
    protected $userInfo;
    protected $navHome;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->exhibitorModel = new Xconfigs();
        $this->formModel = new Xzones();
        $this->formDataModel = new XformDatas();
        $this->companyModel = new Xvendors();
        $this->companyAttrModel = new XvendorAttrs();
        $this->userId = IAuth::getVendorUserIDCurrLogged();
        if(!$this->userId){
            return redirect('vendor/login/index');
        }
    }

    private function loadSharedData(){
        $this->userInfo = $this->model->getCmsDataByID($this->userId);
        $this->navHome = $this->exhibitorModel->getCmsData($this->userInfo['event_id']);
    }

    private function existFormId($datas,$formId){
        foreach($datas as $key=>$item){
            if($item['form_id'] == $formId){
                return true;
            }
        }
        return false;
    }

    public function report(Request $request,$id=0){
        if(!$this->userId) return redirect('vendor/login/index');
        $curr_page = $request->param('curr_page', 1);
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        if(empty($id)){
            $forms = $this->formModel->getDataListByVendor($eventId,$this->userInfo['company_id'],$this->userInfo['login_name']);
            foreach($forms as $key=>$item){
                $type = strtolower($item['type']);
                if($type == 'amenity' || $type == 'marketing'){
                    $type = 'datas';
                }
                $item['submit_num'] = Db::name('xform_'.$type)->where(['form_id'=>$item['id'],'is_draft'=>0,'is_last_submit'=>1,'event_id'=>$eventId])
                    ->group('company_id')->count();
                $forms[$key] = $item;
            }
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'articles'=>$forms
            ];
            return view('report', $data);
        }else{
            $form = $this->formModel->getData($id);
            $type = $form['type'];
            return redirect('/vendor/'.$type.'/index',['form_id'=>$id]);
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

    public function index(Request $request){
        if(!$this->userId) return redirect('vendor/login/index');
        $this->loadSharedData();
        $eventId = $this->userInfo['event_id'];
        $company = $this->companyModel->getCmsDataByID($this->userInfo['company_id']);
        $companyAttr = $this->companyAttrModel->getCmsList($company['event_id']);
        $res = [
            'name'=>['label'=>$this->getLabelByKey($companyAttr,'name','Company Name'),'value'=>$company['name']],
            'origin_country'=>['label'=>$this->getLabelByKey($companyAttr,'origin_country','Country of Origin'),'value'=>$company['origin_country']],
            'profile'=>['label'=>$this->getLabelByKey($companyAttr,'profile','Profile'),'value'=>$company['profile']],
            'logo'=>['label'=>$this->getLabelByKey($companyAttr,'logo','Logo'),'value'=>$company['logo']],
            'email'=>['label'=>$this->getLabelByKey($companyAttr,'email','Email'),'value'=>$company['email']],
            'address_line1'=>['label'=>$this->getLabelByKey($companyAttr,'address_line1','Address Line 1'),'value'=>$company['address_line1']],
            'address_line2'=>['label'=>$this->getLabelByKey($companyAttr,'address_line2','Address Line 2'),'value'=>$company['address_line2']],
            'postal'=>['label'=>$this->getLabelByKey($companyAttr,'postal','Postal/Zip Code'),'value'=>$company['postal']],
            'country'=>['label'=>$this->getLabelByKey($companyAttr,'country','Country'),'value'=>$company['country']],
            'phone'=>['label'=>$this->getLabelByKey($companyAttr,'phone','Business Phone'),'value'=>$company['phone_country_code'].$company['phone_area_code'].$company['phone_number']],
            'fax'=>['label'=>$this->getLabelByKey($companyAttr,'fax','Fax'),'value'=>$company['fax_country_code'].$company['fax_area_code'].$company['fax_number']],
            'website'=>['label'=>$this->getLabelByKey($companyAttr,'website','Website'),'value'=>$company['website']]
        ];
        $data = [
            'user'=>$this->userInfo,
            'exhibitor'=>$this->navHome,
            'company' => $res
        ];
        return view('index', $data);
    }
}
