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
use app\common\model\XmanPower;
use app\common\model\Xorders;
use app\common\model\Xusers;
use think\facade\Env;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;

class Manpower extends UserBase
{
    private $model;
    private $exhibitorModel;
    private $formModel;
    private $companyModel;
    private $companyAttrModel;
    protected $userId;
    protected $userInfo;
    protected $navHome;
    private $e16Model;
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
        $this->e16Model = new XmanPower();
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
        $formId = $request->param("form_id");
        if ($request->isPost()) {
            $articles = $this->e16Model->getVendorReportForPage($this->userInfo['event_id'],$formId,$curr_page, $limit);
            $record_num = $this->e16Model->getVendorReportCount($this->userInfo['event_id'],$formId);
            $data = [
                'articles' => $articles,
                'record_num' => $record_num
            ];
            return showMsg(1, 'success', $data);
        } else {
            $data = [
                'user'=>$this->userInfo,
                'exhibitor'=>$this->navHome,
                'form_id'=>$formId
            ];
            return view('index',$data);
        }
    }
}