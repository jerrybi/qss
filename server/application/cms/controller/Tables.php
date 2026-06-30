<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:10
 */

namespace app\cms\controller;


use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\Xevents;
use app\common\model\Xtables;
use app\common\model\Xzones;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class Tables extends CmsBase
{
    protected $model;
    protected $formAttrModel;
    protected $formDataModel;
    protected $companyModel;
    protected $catalogModel;
    protected $userModel;
    protected $dynamicModel;
    protected $zoneModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xtables();
        $this->zoneModel = new Xzones();
    }

    /**
     * 用户列表数据
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $event = new Xevents();
        $events = $event->getSimpleEventsList();
        $eventId = null;
        if(!empty($event_id)){
            $eventId = $event_id;
        }else if(!empty($events)){
            $eventId = $events[0]['id'];
        }
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            $record_num = $this->model->getCmsDatasCount($search,$eventId);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId
            ];
            return view('index', $data);
        }
    }

    public function add(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->addData($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $zones = $this->zoneModel->getSimpleList($events[0]['id']);
            return view('add',['events'=>$events,'zones'=>$zones]);
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $zones = $this->zoneModel->getSimpleList($article['event_id']);
            $data =
                [
                    'article' => $article,
                    'events'=>$events,
                    'zones'=>$zones
                ];
            return view('edit', $data);
        }
    }

    public function preview(Request $request, $id)
    {
        $article = $this->model->getCmsDataByID($id);
        $data =
            [
                'form' => $article
            ];
        return view('preview', $data);
    }

    public function download(Request $request){
        $eventId = $request->param('event_id');
        $formId = $request->param('form_id');
        $form = $this->model->getCmsDataByID($formId);
        //sheet1
        $itemNames = $this->dynamicModel->getCatalogItemNames($eventId,$formId);
        $articles1 = $this->dynamicModel->getCatalogItemReport($eventId,$formId);
        $companies = $this->dynamicModel->getCatalogCompanies($eventId,$formId);
//        $record_num1 = $this->dynamicModel->getVendorReportCount($eventId,$formId);
//        $articles1 = $this->dynamicModel->getVendorReportForPage($eventId,$formId,1, $record_num1);
        //sheet2
        $names = $this->dynamicModel->getVendorDynamicName($eventId,$formId);
        $record_num2 = $this->dynamicModel->getVendorDynamicReportCount($eventId,$formId,$names);
        $articles2 = $this->dynamicModel->getVendorDynamicReportForPage($eventId,$formId,1, $record_num2,$names);
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = $form['name'];
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        //sheet1
        if($form['have_item'] == '1'){
            $cellName1 = array('ID');
            $cellName1[] = 'Name of Organisation';
            $cellName1[] = 'Booth Number';
            $cellName1[] = 'Date & Time Submitted';
            foreach ($itemNames as $v){
                $cellName1[] = $v['item_name'];
            }
            $cellKey1 = Tools::getExcelColumnTitles(count($cellName1)+5);
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle("Items Information");
            foreach($cellName1 as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].$topNumber,$v);//设置表头数据
//                $objPHPExcel->getActiveSheet()->freezePane($cellKey1[$k+1].($topNumber+1));//冻结窗口
                $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].$topNumber)->getFont()->setBold(true);//设置加粗
                $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].$topNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
            foreach($companies as $k=>$v){
                $companyId = $v['company_id'];
                $companyDatas = $this->model->findCompanyDatas($articles1,$companyId);
                if(!empty($companyDatas)){
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[0].($k+1+$topNumber),$k+1);
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[1].($k+1+$topNumber),$companyDatas[0]['company_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[2].($k+1+$topNumber),$companyDatas[0]['booth_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[3].($k+1+$topNumber),$companyDatas[0]['create_time']);
                    for($i=4;$i<count($cellName1);$i++){
                        $companyItem = $this->model->findCompanyItemData($articles1,$companyId,$cellName1[$i]);
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$i].($k+1+$topNumber),isset($companyItem)?$companyItem['item_quantity']:0);
                    }
                }
            }
        }
        //sheet2
        if($form['have_dynamic'] == '1'){
            $cellName2 = array('ID');
            $cellName2[] = 'Name of Organisation';
            $cellName2[] = 'Booth Number';
            $cellName2[] = 'Date & Time Submitted';
            foreach ($names as $value){
                $cellName2[] = $value['dynamic_title'];
            }
            if($form['have_item'] == '1'){
                $objPHPExcel->createSheet();
                $objPHPExcel->setActiveSheetIndex(1);
            }else{
                $objPHPExcel->setActiveSheetIndex(0);
            }
            $objPHPExcel->getActiveSheet()->setTitle("Additional Information");
            foreach($cellName2 as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
//                $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
                $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
                $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
            //处理数据
            foreach($articles2 as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$k+1);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[1].($k+1+$topNumber),$v['company_name']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[2].($k+1+$topNumber),$v['booth_name']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[3].($k+1+$topNumber),$v['create_time']);
                foreach($names as $key=>$value){
                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey[4+$key].($k+1+$topNumber),$v[$value['dynamic_name']]);
                }
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

    public function report(Request $request)
    {
        $eventId = $request->param('event_id');
        $formId = $request->param('form_id');
        $curr_page = $request->param('page', 1);
        $limit = $request->param('limit',10);
        $type = $request->param('type',0);
        if ($request->isPost()) {
            $formId = $request->param("form_id");
            if($type == 0){
                $articles = $this->dynamicModel->getVendorReportForPage($eventId,$formId,$curr_page, $limit);
                $record_num = $this->dynamicModel->getVendorReportCount($eventId,$formId);
                $names = [];
            }else{
                $names = $this->dynamicModel->getVendorDynamicName($eventId,$formId);
                $articles = $this->dynamicModel->getVendorDynamicReportForPage($eventId,$formId,$curr_page, $limit,$names);
                $record_num = $this->dynamicModel->getVendorDynamicReportCount($eventId,$formId,$names);
            }
            $data = [
                'articles' => $articles,
                'record_num' => $record_num,
                'names' => $names
            ];
            return showMsg(1, 'success', $data);
        } else {
            $form = $this->model->getCmsDataByID($formId);
            $data = [
                'form'=>$form
            ];
            return view('report',$data);
        }
    }

    public function getNames(Request $request){
        if ($request->isPost()) {
            $formId = $request->param("form_id");
            $eventId = $request->param('event_id');
            $names = $this->dynamicModel->getVendorDynamicName($eventId,$formId);
            $data = [
                'names' => $names
            ];
            return showMsg(1, 'success', $data);
        } else {
            return showMsg(0,'invalid request');
        }
    }

    public function getOrderItems(Request $request){
        if ($request->isPost()) {
            $eventId = $request->param('event_id');
            $id = $request->param('id');
            $options = $this->catalogModel->getUsedCmsList($eventId,'Amenity');
            if(isset($id)){
                $items = $this->model->getCmsDataByID($id);
                $order_items = $items['order_items'];
            }else{
                $order_items = '';
            }
            foreach($options as $key=>$value){
                if(strpos($order_items,$value['category']) !== false){
                    $value['checked'] = true;
                }else{
                    $value['checked'] = false;
                }
                $options[$key] = $value;
            }
            return showMsg(1,'success',$options);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function getVendorAccounts(Request $request){
        if ($request->isPost()) {
            $vendorId = $request->param('vendor_id');
            $id = $request->param('id');
            $options = $this->userModel->getUserByCompanyId($vendorId,1);
            if(isset($id)){
                $items = $this->model->getCmsDataByID($id);
                $vendor_accounts = $items['vendor_accounts'];
            }else{
                $vendor_accounts = '';
            }
            foreach($options as $key=>$value){
                if(strpos($vendor_accounts,'"'.$value['login_name'].'"') !== false){
                    $value['checked'] = true;
                }else{
                    $value['checked'] = false;
                }
                $options[$key] = $value;
            }
            return showMsg(1,'success',$options);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function getList(Request $request){
        $zoneID = $request->param('zone_id');
        $list = $this->model->getSimpleList($zoneID);
        return showMsg(1, 'success', $list);
    }
}