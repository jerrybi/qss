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
use app\common\model\XedmTasks;
use app\common\model\XedmTemplates;
use app\common\model\Xevents;
use app\common\model\Xexhibitors;
use app\common\model\XuserDatas;
use app\common\model\XuserEvents;
use app\common\model\Xusers;
use app\common\model\Xzones;
use think\Request;
use think\Db;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class EdmTasks extends CmsBase
{
    protected $model;
    protected $userEventModel;
    protected $userDataModel;
    protected $edmTemplateModel;
    protected $userModel;
    protected $zoneModel;
    protected $exhibitorModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XedmTasks();
        $this->userEventModel = new XuserEvents();
        $this->userDataModel = new XuserDatas();
        $this->edmTemplateModel = new XedmTemplates();
        $this->userModel = new Xusers();
        $this->zoneModel = new Xzones();
        $this->exhibitorModel = new Xexhibitors();
    }

    private function getValueByKey($items,$key){
        $item = Tools::find_array_item($items,'key',$key);
        return !empty($item)?$item['value']:'';
    }

    public function index(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $templateId = $request->param('template_id','');
        $status = $request->param('status','');
        $rsvpStatus = $request->param('rsvp_status','');
        $event_id = $request->param('event_id');
        $zone_id = $request->param('zone_id');
        $zone_name = $request->param('zone_name');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$templateId,$zone_id,
                $status,$rsvpStatus,$event_id);
            foreach($list as $k => $v){
                if($v['type'] == 'exhibitor'){
                    $item = $this->exhibitorModel->getCmsDataByID($v['user_id']);
                    $v['first_name'] = $item['first_name'];
                    $v['last_name'] = $item['last_name'];
                    $v['email'] = $item['email'];
                    $v['rsvp_status'] = '';
                }else{
                    $items = $this->userDataModel->getDataList($v['user_id']);
                    $v['first_name'] = $this->getValueByKey($items,'first_name');
                    $v['last_name'] = $this->getValueByKey($items,'last_name');
                    $v['email'] = $this->getValueByKey($items,'email');
                    $v['rsvp_status'] = $this->getValueByKey($items,'join');
                }
                $list[$k] = $v;
            }
            return showMsg(1, 'success', $list);
        } else {
            $event = $this->userEventModel->getActiveEvent();
            $zones = $this->zoneModel->getSimpleList($event['id']);
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$templateId,$zone_id,
                $status,$rsvpStatus,$event['id']);
            $record_num = $this->model->getCmsDatasCount($search,$templateId,$zone_id,$status,$rsvpStatus,$event['id']);
            $pending = $this->model->getCmsDatasCount($search,$templateId,$zone_id,9,$rsvpStatus,$event['id']);
            $success = $this->model->getCmsDatasCount($search,$templateId,$zone_id,1,$rsvpStatus,$event['id']);
            $fail = $this->model->getCmsDatasCount($search,$templateId,$zone_id,2,$rsvpStatus,$event['id']);
            $invalidAddr = $this->model->getCmsDatasCount($search,$templateId,$zone_id,3,$rsvpStatus,$event['id']);
            $receipt = $this->model->getCmsDatasCount($search,$templateId,$zone_id,4,$rsvpStatus,$event['id']);
            $success_rate = $record_num > 0 ? number_format(($success+$receipt) * 100 / $record_num,2) : 0;
            foreach($articles as $k => $v){
                if($v['type'] == 'exhibitor'){
                    $item = $this->exhibitorModel->getCmsDataByID($v['user_id']);
                    $v['first_name'] = $item['first_name'];
                    $v['last_name'] = $item['last_name'];
                    $v['email'] = $item['email'];
                    $v['rsvp_status'] = '';
                }else {
                    $items = $this->userDataModel->getDataList($v['user_id']);
                    $v['first_name'] = $this->getValueByKey($items, 'first_name');
                    $v['last_name'] = $this->getValueByKey($items, 'last_name');
                    $v['email'] = $this->getValueByKey($items, 'email');
                    $v['rsvp_status'] = $this->getValueByKey($items, 'join');
                }
                $articles[$k] = $v;
            }
            $templates = $this->edmTemplateModel->getList($event['id']);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'template_id' => $templateId,
                'status' => $status,
                'rsvp_status' => $rsvpStatus,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'event'=>$event,
                'templates'=>$templates,
                'zones'=>$zones,
                'zone_id'=>$zone_id,
                'zone_name'=>$zone_name,
                'success' => $success,
                'fail' => $fail,
                'pending' => $pending,
                'invalidAddr' => $invalidAddr,
                'receipt' => $receipt,
                'success_rate' =>$success_rate
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
            $event = $this->userEventModel->getActiveEvent();
            $templates = $this->edmTemplateModel->getList($event['id']);
            $zones = $this->zoneModel->getSimpleList($event['id']);
            return view('add',['event'=>$event,'templates'=>$templates,'zones'=>$zones]);
        }
    }

    public function assign(Request $request)
    {
        $id = $request->param('id');
        $eventID = $request->param('event_id');
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->assign($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $templates = $this->edmTemplateModel->getList($eventID);
            return view('assign',['ids'=>$id,'event_id'=>$eventID,'templates'=>$templates]);
        }
    }

    public function resend(Request $request)
    {
        $id = $request->param('id');
        Db::name('xedm_tasks')->where('id',$id)
            ->update([
                'status'=>9,
                'count'=>0,
                'total_count'=>0,
                'update_time'=>date('Y-m-d H:i:s')
            ]);
        return showMsg(200, 'Email is resent!');
    }

    public function resendSelected(Request $request)
    {
        $search = $request->param('str_search',null);
        $templateId = $request->param('template_id','');
        $status = $request->param('status','');
        $rsvpStatus = $request->param('rsvp_status','');
        $event_id = $request->param('event_id');
        $zone_id = $request->param('zone_id');
        $zone_name = $request->param('zone_name');
        $model = Db::name('xedm_tasks');
        if(!empty($search)){
            $user = Db::name('xuser_datas')->where('status',1)
                ->where('event_id',$event_id)
                ->where('value','like','%' . $search . '%')
                ->field('distinct(user_id)')
                ->select();
            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('user_id','in',$ids);
        }
        if(!empty($event_id)){
            $model = $model->where('event_id',$event_id);
        }
        if(!empty($templateId)){
            $model = $model->where('template_id',$templateId);
        }
        if(!empty($zone_id)){
            $user = Db::name('xuser_tables')->where('status',1)
                ->where('event_id',$event_id)
                ->where('zone_id','=',$zone_id)
                ->field('distinct(user_id)')
                ->select();
            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('user_id','in',$ids);
        }
        if(!empty($status)){
            $model = $model->where('status',$status);
        }
        if(!empty($rsvpStatus)){
            if($rsvpStatus == '1'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$event_id)
                    ->where('key','join')
                    ->where('value','=','1')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '2'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$event_id)
                    ->where('key','join')
                    ->where('value','=','2')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '9'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$event_id)
                    ->where('key','join')
                    ->where('value','not in',['1','2'])
                    ->field('distinct(user_id)')
                    ->select();
            }else{
                $user = null;
            }

            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('user_id','in',$ids);
        }
        $model->update([
            'status'=>9,
            'count'=>0,
            'total_count'=>0,
            'update_time'=>date('Y-m-d H:i:s')
        ]);
        return showMsg(200, 'Email is resent!');
    }

    public function edit(Request $request, $id)
    {
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        }
    }

    public function download(Request $request)
    {
        ini_set('max_execution_time', '600');
        ini_set('memory_limit', -1); //没有内存限制
        $search = $request->param('str_search',null);
        $templateId = $request->param('template_id','');
        $status = $request->param('status','');
        $rsvpStatus = $request->param('rsvp_status','');
        $event_id = $request->param('event_id');
        $zone_id = $request->param('zone_id');
        $articles = $this->model->getCmsDatas($search,$templateId,$zone_id,$status,$rsvpStatus,$event_id);
        foreach($articles as $k => $v){
            $items = $this->userDataModel->getDataList($v['user_id']);
            $v['first_name'] = $this->getValueByKey($items,'first_name');
            $v['last_name'] = $this->getValueByKey($items,'last_name');
            $v['email'] = $this->getValueByKey($items,'email');
            $v['rsvp_status'] = $this->getValueByKey($items,'join');
            $articles[$k] = $v;
        }
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'EDM Task';
        $fileName = $xlsTitle . date('_YmdHis');
        $cellName = array('ID');
        $cellName[] = 'User ID';
        $cellName[] = 'First Name';
        $cellName[] = 'Last Name';
        $cellName[] = 'Email';
        $cellName[] = 'Zone';
        $cellName[] = 'Template';
        $cellName[] = 'RSVP Status';
        $cellName[] = 'Status';
        $cellName[] = 'Event';
        $cellKey = Tools::getExcelColumnTitles(count($cellName) + 5);
        //处理表头
        foreach ($cellName as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k] . $topNumber, $v);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane('A' . ($topNumber + 1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k] . $topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k] . $topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        //处理数据
        foreach ($articles as $k => $v) {
            $index = 0;
            $row = [];
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $k + 1);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['user_id']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['first_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['last_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['email']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['zone_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['template_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $this->model->getRsvpStatus($v['rsvp_status']));
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $this->model->getStatus($v['status']));
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++] . ($k + 1 + $topNumber), $v['event_name']);
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}