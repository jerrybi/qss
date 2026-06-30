<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:10
 */

namespace app\cms\controller;


use app\common\controller\CmsBase;
use app\common\model\XBooking;
use app\common\model\Xcatalogs;
use app\common\model\Xcompanies;
use app\common\model\Xevents;
use app\common\model\XformAttrs;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\XlocationGroups;
use app\common\model\Xlocations;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use FormDesign\Formdesign;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class FormBookings extends CmsBase
{
    protected $model;
    protected $formAttrModel;
    protected $formDataModel;
    protected $companyModel;
    protected $catalogModel;
    protected $userModel;
    protected $bookingModel;
    protected $locationGroupModel;
    protected $locationModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xzones();
        $this->formAttrModel = new XformAttrs();
        $this->formDataModel = new XformDatas();
        $this->companyModel = new Xcompanies();
        $this->catalogModel = new Xcatalogs();
        $this->userModel = new Xusers();
        $this->bookingModel = new XBooking();
        $this->locationGroupModel = new XlocationGroups();
        $this->locationModel = new Xlocations();
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
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,'Booking');
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,'Booking');
            $record_num = $this->model->getCmsDatasCount($search,$eventId,'Booking');
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
//            $formDesign = new Formdesign();
//            $parse_content = json_decode(urldecode(base64_decode($input['data'])),true);
//            $design_content = '';
//            if(!empty($parse_content)) {
//                $res = $formDesign->unparse_form(array(
//                    'content_parse' => $parse_content['parse'],
//                    'content_data' => serialize($parse_content['data'])
//                ), array(), array('action' => 'preview'));
//                if(!$res[0]){
//                    return ['tag'=>false,'message'=>$res[1]];
//                }else{
//                    $design_content = $res[1];
//                }
//            }
//            $source_form = (!empty($parse_content) && isset($parse_content['template']))?$parse_content['template']:'';
            $design_content = null;
            $source_form = null;
            $opRes = $this->model->addData($input,$design_content,$source_form);
//            if($opRes['tag']){
//                $formId = $opRes['tag'];
//                $attrs = [];
//                if(!empty($parse_content) && isset($parse_content['data'])){
//                    $datas = $parse_content['data'];
//                    foreach($datas as $key=>$item){
//                        if(!empty($item['name'])){
//                            $attrs[] = ['form_id'=>$formId,'name'=>$item['name'],'type'=>$item['leipiplugins'],
//                                'create_time'=>date('Y-m-d H:i:s',time()),'update_time'=>date('Y-m-d H:i:s',time())];
//                        }
//                    }
//                }
//                if(count($attrs) > 0){
//                    $this->formAttrModel->updateCmsData($formId,$attrs);
//                }
//            }
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            return view('add',['events'=>$events]);
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->isPost()) {
//            $formDesign = new Formdesign();
//            $parse_content = json_decode(urldecode(base64_decode($request->param('data'))),true);
//            $design_content = '';
//            if(!empty($parse_content)){
//                $res = $formDesign->unparse_form(array(
//                    'content_parse'=>$parse_content['parse'],
//                    'content_data'=>serialize($parse_content['data'])
//                ),array(),array('action'=>'preview'));
//                if(!$res[0]){
//                    return ['tag'=>false,'message'=>$res[1]];
//                }else{
//                    $design_content = $res[1];
//                }
//            }
//            $source_form = (!empty($parse_content) && isset($parse_content['template']))?$parse_content['template']:'';
            $design_content = null;
            $source_form = null;
            $opRes = $this->model->updateCmsData($request->post(),$design_content,$source_form,$id);
//            if($opRes['tag']){
//                $formId = $id;
//                $attrs = [];
//                if(!empty($parse_content) && isset($parse_content['data'])){
//                    $datas = $parse_content['data'];
//                    foreach($datas as $key=>$item){
//                        if(!empty($item['name'])){
//                            $attrs[] = ['form_id'=>$formId,'name'=>$item['name'],'type'=>$item['leipiplugins'],
//                                'create_time'=>date('Y-m-d H:i:s',time()),'update_time'=>date('Y-m-d H:i:s',time())];
//                        }
//                    }
//                }
//                if(count($attrs) > 0){
//                    $this->formAttrModel->updateCmsData($formId,$attrs);
//                }
//            }
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $data =
                [
                    'article' => $article,
                    'events'=>$events
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
        $res = $this->bookingModel->getTotalUsedDateTime($eventId,$formId);
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = $form['name'];
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
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
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
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
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.urlencode($xlsTitle).'.xls"');
        header("Content-Disposition:attachment;filename=".urlencode($fileName).".xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
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

    public function report(Request $request)
    {
        $eventId = $request->param('event_id');
        $formId = $request->param('form_id');
        $form = $this->model->getCmsDataByID($formId);
        $locationId = $request->param("location_id");
        if($request->isPost()){
            $count = ((strtotime($form['due_date'])-strtotime($form['early_submission_date']))/86400)+1;
            $days = [];
            $articles = [];
            $times = ['1100 hrs','1130 hrs','1200 hrs','LUNCH','1400 hrs','1430 hrs','1500 hrs','1530 hrs','1600 hrs'];
            for($i=0;$i<$count;$i++){
                $day = strtotime('+'.$i.' day',strtotime($form['early_submission_date']));
                $days[] = $day;
                $res = $this->bookingModel->getUsedDateTime($eventId,Date('Y-m-d',$day),$times,$formId,$locationId);
                $timeItem = [];
                foreach($times as $k=>$v){
                    $status = $this->isSlotUsed($v,$res);
                    $timeItem[$v] = $status;
                }
                $articles[$day] = $timeItem;
            }
            $data = [
                'form' => $form,
                'days'=>$days,
                'times'=>$times,
                'articles'=>$articles,
                'record_num'=>count($articles),
                'page_limit'=>1000
            ];
            return showMsg(1,'ok', $data);
        }else{
            $locationGroups = $this->locationGroupModel->getList($eventId);
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
                'form' => $form,
                'location'=>$locations
            ];
            return view('report',$data);
        }
    }

    public function getOrderItems(Request $request){
        if ($request->isPost()) {
            $eventId = $request->param('event_id');
            $id = $request->param('id');
            $options = $this->catalogModel->getUsedCmsList($eventId,'Booking');
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
}