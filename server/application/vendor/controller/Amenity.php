<?php


namespace app\vendor\controller;


use app\common\controller\UserBase;
use app\common\lib\IAuth;
use app\common\model\Xannouncements;
use app\common\model\XboothAttrs;
use app\common\model\XdataFields;
use app\common\model\XcatalogAttrs;
use app\common\model\Xcatalogs;
use app\common\model\Xcompanies;
use app\common\model\XcompanyAttrs;
use app\common\model\XexhibitorForms;
use app\common\model\Xconfigs;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\XDynamicForm;
use app\common\model\Xnotices;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use think\facade\Env;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;

class Amenity extends UserBase
{
    private $model;
    private $exhibitorModel;
    private $formModel;
    private $companyModel;
    private $companyAttrModel;
    protected $userId;
    protected $userInfo;
    protected $navHome;
    private $dynamicModel;
    private $catalogModel;
    private $catalogAttrModel;
    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->exhibitorModel = new Xconfigs();
        $this->formModel = new Xzones();
        $this->companyModel = new Xcompanies();
        $this->companyAttrModel = new XcompanyAttrs();
        $this->dynamicModel = new XDynamicForm();
        $this->catalogModel = new Xcatalogs();
        $this->catalogAttrModel = new XcatalogAttrs();
        $this->userId = IAuth::getVendorUserIDCurrLogged();
        if(!$this->userId){
            return redirect('vendor/login/index');
        }
    }

    private function loadSharedData(){
        $this->userInfo = $this->model->getCmsDataByID($this->userId);
        $this->navHome = $this->exhibitorModel->getCmsData($this->userInfo['event_id']);
    }

    public function index(Request $request){
        if(!$this->userId) return redirect('vendor/login/index');
        $this->loadSharedData();
        $curr_page = $request->param('page', 1);
        $limit = $request->param('limit',10);
        $type = $request->param('type',0);
        if ($request->isPost()) {
            $formId = $request->param("form_id");
            if($type == 0){
                $articles = $this->dynamicModel->getVendorReportForPage($this->userInfo['event_id'],$formId,$curr_page, $limit);
                $record_num = $this->dynamicModel->getVendorReportCount($this->userInfo['event_id'],$formId);
                $names = [];
            }else{
                $names = $this->dynamicModel->getVendorDynamicName($this->userInfo['event_id'],$formId);
                $articles = $this->dynamicModel->getVendorDynamicReportForPage($this->userInfo['event_id'],$formId,$curr_page, $limit,$names);
                $record_num = $this->dynamicModel->getVendorDynamicReportCount($this->userInfo['event_id'],$formId,$names);
            }
            $data = [
                'articles' => $articles,
                'record_num' => $record_num,
                'names' => $names
            ];
            return showMsg(1, 'success', $data);
        } else {
            $formId = $request->param('form_id');
            $form = $this->formModel->getCmsDataByID($formId);
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'form'=>$form
            ];
            return view('index',$data);
        }
    }

    public function getNames(Request $request){
        if(!$this->userId) return redirect('vendor/login/index');
        $this->loadSharedData();
        if ($request->isPost()) {
            $formId = $request->param("form_id");
            $names = $this->dynamicModel->getVendorDynamicName($this->userInfo['event_id'],$formId);
            $data = [
                'names' => $names
            ];
            return showMsg(1, 'success', $data);
        } else {
            return showMsg(0,'invalid request');
        }
    }
}