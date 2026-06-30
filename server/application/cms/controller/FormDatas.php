<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XBadge;
use app\common\model\XBooking;
use app\common\model\XDynamicForm;
use app\common\model\Xevents;
use app\common\model\Xzones;
use app\common\model\XmanPower;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class FormDatas extends CmsBase
{
    protected $model;
    protected $badgeModel;
    protected $dynamicModel;
    protected $bookingModel;
    protected $manPowerModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xzones();
        $this->badgeModel = new XBadge();
        $this->dynamicModel = new XDynamicForm();
        $this->bookingModel = new XBooking();
        $this->manPowerModel = new XmanPower();
    }

    public function index(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search');
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
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,'');
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,'');
            $record_num = $this->model->getCmsDatasCount($search,$eventId,'');
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

    public function download(Request $request){
        $eventId = $request->param('event_id');
        $formId = $request->param('form_id');
        $form = $this->model->getCmsDataByID($formId);
        $objPHPExcel = new PHPExcel();
        $xlsTitle = $form['name'];
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $sheetIndex = 0;
        $objPHPExcel->setActiveSheetIndex($sheetIndex);
        if($form['type'] == 'Badge'){
            $this->addBadges($eventId,$form,$objPHPExcel,$cellKey);
        }else if($form['type'] == 'Amenity'){
            $sheetIndex = $this->addAmenity($eventId,$form,$objPHPExcel,$cellKey,$sheetIndex);
        }else if($form['type'] == 'Marketing'){
            $sheetIndex = $this->addMarketing($eventId,$form,$objPHPExcel,$cellKey,$sheetIndex);
        }else if($form['type'] == 'Booking'){
            $this->addBooking($eventId,$form,$objPHPExcel,$cellKey);
        }else if($form['type'] == 'Manpower'){
            $this->addManpower($eventId,$form,$objPHPExcel,$cellKey);
        }

        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.urlencode($xlsTitle).'.xls"');
        header("Content-Disposition:attachment;filename=".urlencode($fileName).".xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function downloadAll(Request $request){
        $eventId = $request->param('event_id');
        $forms = $this->model->getDataList($eventId,'');
        $objPHPExcel = new PHPExcel();
        $xlsTitle = 'AllForms';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $sheetIndex = 0;
        foreach ($forms as $k=>$v){
            if($sheetIndex == 0){
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
            }else{
                $objPHPExcel->createSheet();
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
            }
            if($v['type'] == 'Badge'){
                $this->addBadges($eventId,$v,$objPHPExcel,$cellKey);
            }else if($v['type'] == 'Amenity'){
                $sheetIndex = $this->addAmenity($eventId,$v,$objPHPExcel,$cellKey,$sheetIndex);
            }else if($v['type'] == 'Marketing'){
                $sheetIndex = $this->addMarketing($eventId,$v,$objPHPExcel,$cellKey,$sheetIndex);
            }else if($v['type'] == 'Booking'){
                $this->addBooking($eventId,$v,$objPHPExcel,$cellKey);
            }else if($v['type'] == 'Manpower'){
                $this->addManpower($eventId,$v,$objPHPExcel,$cellKey);
            }
            $sheetIndex++;
        }

        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.urlencode($xlsTitle).'.xls"');
        header("Content-Disposition:attachment;filename=".urlencode($fileName).".xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    private function getSheetTitle($title){
        $title = preg_replace("/\*|\:|\/|\\|\?|\[|\]/","",$title);
        if(strlen($title) > 31){
            $title = substr($title,0,31);
        }
        return $title;
    }

    private function addBadges($eventId,$form,$objPHPExcel,$cellKey){
        $topNumber = 1;
        $record_num1 = $this->badgeModel->getVendorReportCount($eventId,$form['id']);
        $articles1 = $this->badgeModel->getVendorReportForPage($eventId,$form['id'],1, $record_num1);
        $cellName1 = array();
        $cellName1[] = 'Booth No';
        $cellName1[] = 'Exhibiting Company';
        $cellName1[] = 'SQM';
        $cellName1[] = 'Rank';
        $cellName1[] = 'Salutation';
        $cellName1[] = 'First Name';
        $cellName1[] = 'Last Name';
        $cellName1[] = 'Name On Badge';
        $cellName1[] = 'Position/Job Title';
        $cellName1[] = 'Company Name';
        $cellName1[] = 'Email';
        $cellName1[] = 'Mobile No';
        $cellName1[] = 'Country/Region of Residence';
        $cellName1[] = 'Vaccination Type';
        $cellName1[] = 'Vaccination Effective Date(2 weeks after second dose)';
        $cellName1[] = 'Date of Entry';
        $cellName1[] = 'Date of Exit';
        $cellName1[] = 'Last City/Port of Embarkation Before Singapore';
//        $cellName1[] = 'Authorizer Person  (Pick up badge for the group)';
//        $cellName1[] = 'Authorizer\'s Designation (Pick up badge for the group)';
        if($form['show_dob'] == 1){
            $cellName1[] = 'Date of Birth';
            $cellName1[] = 'NRIC (last 4 Digits) / FIN NO';
        }
        $cellName1[] = 'Submitted By';
        $cellName1[] = 'Submittor\'s Designation';
        $cellName1[] = 'Submitor\'s Email';
        $cellName1[] = 'Further Instruction';
        $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']));
        foreach($cellName1 as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
//            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        foreach($articles1 as $k=>$v){
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
//            $objPHPExcel->getActiveSheet()->setCellValue('S'.($k+1+$topNumber),$v['vaccination_authorizer_person']);
//            $objPHPExcel->getActiveSheet()->setCellValue('T'.($k+1+$topNumber),$v['vaccination_authorizer_designation']);
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
        }
    }

    private function addAmenity($eventId,$form,$objPHPExcel,$cellKey,$sheetIndex){
        $topNumber = 1;
        //sheet1
        $itemNames = $this->dynamicModel->getCatalogItemNames($eventId,$form['id']);
        $articles1 = $this->dynamicModel->getCatalogItemReport($eventId,$form['id']);
        $companies = $this->dynamicModel->getCatalogCompanies($eventId,$form['id']);
//        $record_num1 = $this->dynamicModel->getVendorReportCount($eventId,$form['id']);
//        $articles1 = $this->dynamicModel->getVendorReportForPage($eventId,$form['id'],1, $record_num1);
        //sheet2
        $names = $this->dynamicModel->getVendorDynamicName($eventId,$form['id']);
        $record_num2 = $this->dynamicModel->getVendorDynamicReportCount($eventId,$form['id'],$names);
        $articles2 = $this->dynamicModel->getVendorDynamicReportForPage($eventId,$form['id'],1, $record_num2,$names);
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
            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']." Items"));
            foreach($cellName1 as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].$topNumber,$v);//设置表头数据
//                $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
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
        else if($form['have_dynamic'] == '1'){
            $cellName2 = array('ID');
            $cellName2[] = 'Name of Organisation';
            $cellName2[] = 'Booth Number';
            $cellName2[] = 'Date & Time Submitted';
            foreach ($names as $value){
                $cellName2[] = $value['dynamic_title'];
            }
            if($form['have_item'] == '1'){
                $objPHPExcel->createSheet();
                $sheetIndex++;
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
            }else{
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
            }
            $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']." Extras"));
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
        }else{
            $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']));
        }
        return $sheetIndex;
    }

    private function addMarketing($eventId,$form,$objPHPExcel,$cellKey,$sheetIndex){
        $topNumber = 1;
        $formId = $form['id'];
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
            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']." Items"));
            foreach($cellName1 as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].$topNumber,$v);//设置表头数据
//                $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
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
                $sheetIndex++;
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
            }else{
                $objPHPExcel->setActiveSheetIndex($sheetIndex);
            }
            $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']." Extras"));
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
        return $sheetIndex;
    }

    private function addBooking($eventId,$form,$objPHPExcel,$cellKey){
        $topNumber = 1;
        $formId = $form['id'];
        $res = $this->bookingModel->getTotalUsedDateTime($eventId,$formId);
        $cellName = array('ID');
        $cellName[] = 'Company Name';
        $cellName[] = 'Booth Number';
        $cellName[] = 'Date & Time Submitted';
        $cellName[] = 'Presentation Date';
        $cellName[] = 'Presentation Time';
        $cellName[] = 'Is New Product';
        $cellName[] = 'Title';
        $cellName[] = 'Synopsis';
        $cellName[] = 'Product Image';
        $cellName[] = 'Speaker CV';
        $cellName[] = 'Speaker Image';
        $cellName[] = 'Location';
        $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']));
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
//            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        //处理数据
        foreach($res as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$k+1);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['company']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),$v['booth_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),$v['create_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['presentation_date']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['presentation_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['is_new_product']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['title']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),$v['synopsis']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),$v['product_img_url']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),$v['speaker_cv']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($k+1+$topNumber),$v['speaker_img_url']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.($k+1+$topNumber),$v['location']);
        }
    }

    private function addManpower($eventId,$form,$objPHPExcel,$cellKey){
        $topNumber = 1;
        $formId = $form['id'];
        $record_num = $this->manPowerModel->getVendorReportCount($eventId,$formId);
        $articles = $this->manPowerModel->getVendorReportForPage($eventId,$formId,1, $record_num);
        $cellName = array('ID');
        $cellName[] = 'Name of Organisation';
        $cellName[] = 'Booth Number';
        $cellName[] = 'Date & Time Submitted';
        $cellName[] = 'Item Name';
        $cellName[] = 'Item Price';
        $cellName[] = 'Item From Date';
        $cellName[] = 'Item To Date';
        $cellName[] = 'Item Duration';
        $cellName[] = 'Item Staff Num';
        $cellName[] = 'Language';
        $objPHPExcel->getActiveSheet()->setTitle($this->getSheetTitle($form['name']));
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
//            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k+1].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        //处理数据
        foreach($articles as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$k+1);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['company_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),$v['booth_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),$v['create_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['item_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['item_price']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['item_from_date']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['item_to_date']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),$v['item_duration']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($k+1+$topNumber),$v['item_staff_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),$v['language']);
        }
    }
}
