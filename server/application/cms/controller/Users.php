<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:10
 */

namespace app\cms\controller;


use app\common\controller\CmsBase;
use app\common\lib\Email;
use app\common\lib\IAuth;
use app\common\lib\ImageUtil;
use app\common\lib\LogUtil;
use app\common\lib\QRCode;
use app\common\lib\Tools;
use app\common\model\Xadmins;
use app\common\model\XcardTemplates;
use app\common\model\Xcompanies;
use app\common\model\XdataFields;
use app\common\model\Xevents;
use app\common\model\XexhibitorForms;
use app\common\model\XfieldOptions;
use app\common\model\XformDatas;
use app\common\model\Xtables;
use app\common\model\Xtracks;
use app\common\model\XuserDatas;
use app\common\model\XuserStatus;
use app\common\model\XuserTables;
use app\common\model\Xzones;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use app\common\model\XvisitorType;
use think\Db;
use think\Exception;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\Env;
use think\Request;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class Users extends CmsBase
{
    protected $model;
    protected $companyModel;
    protected $vendorModel;
    protected $zoneModel;
    protected $tableModel;
    protected $userDataModel;
    protected $dataFieldModel;
    protected $fieldOptionModel;
    protected $userTableModel;
    protected $trackModel;
    protected $userStatusModel;
    protected $eventModel;
    protected $adminModel;
    protected $userCache;
    protected $cardTemplateModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->companyModel = new Xcompanies();
        $this->vendorModel = new Xvendors();
        $this->zoneModel = new Xzones();
        $this->tableModel = new Xtables();
        $this->userDataModel = new XuserDatas();
        $this->dataFieldModel = new XdataFields();
        $this->fieldOptionModel = new XfieldOptions();
        $this->userTableModel = new XuserTables();
        $this->trackModel = new Xtracks();
        $this->userStatusModel = new XuserStatus();
        $this->eventModel = new Xevents();
        $this->adminModel = new Xadmins();
        $this->cardTemplateModel = new XcardTemplates();
    }

    /**
     * 用户列表数据
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request){
        $curr_page = intval($request->param('curr_page', 1));
        $page_limit = intval($request->param('limit',$this->page_limit));
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $join = $request->param('join');
        $selfRegister = $request->param('self_register');
        $event = new Xevents();
        $events = $event->getSimpleEventsList();
        $eventId = null;
        if(!empty($event_id)){
            $eventId = $event_id;
        }else if(!empty($events)){
            $eventId = $events[0]['id'];
        }
        if ($request->isPost()) {
            $userFields = $this->dataFieldModel->getCmsList($eventId);
            $zones = $this->zoneModel->getZoneNames($eventId);
            $ids = $this->userDataModel->getUserList($search,$join,$selfRegister);
            $days = $this->eventModel->getEventDays($eventId);
            $total = $this->model->getCmsDatasCount($search,$eventId,$ids);
            $list = $this->model->getCmsDatasForPage($curr_page, $page_limit, $search,$eventId,$ids);
            if($list){
                foreach($list as $k => $v){
                    $items = $this->userDataModel->getDataList($v['id']);
                    foreach($userFields as $k1 => $v1){
                        $item = Tools::find_array_item($items,'key',$v1['key']);
                        if($v1['key'] == 'join'){
                            if(!empty($item)){
                                $v[$v1['key']] = Xusers::getUserStatus($item['value']);
                            }else{
                                $v[$v1['key']] = '';
                            }
                        } else if($v1['key'] == 'self_register'){
                            if(!empty($item)&&$item['value'] == '1'){
                                $v[$v1['key']] = 'Yes';
                            }else{
                                $v[$v1['key']] = 'No';
                            }
                        }else{
                            if(!empty($item)){
                                $v[$v1['key']] = $item['value'];
                            }else{
                                $v[$v1['key']] = '';
                            }
                        }
                    }
                    foreach($zones as $zone){
                        $v[$zone] = $this->userTableModel->getTableByZone($eventId,$v['id'],$zone);
                    }
//                    foreach($days as $day){
//                        $status = $this->userStatusModel->getDataByDay($v['id'],$day);
//                        $v['checkin_status_'.$day] = !empty($status)?$status['checkin_status']:0;
//                        $v['checkin_time_'.$day] = !empty($status)?$status['checkin_time']:'';
//                        $v['op_user_'.$day] = !empty($status)?$status['op_user']:'';
//                    }
                    $list[$k] = $v;
                }
            }
            return showMsg(1, 'success', ['total'=>$total,'data'=>$list]);
        } else {
            $userFields = $this->dataFieldModel->getCmsList($eventId);
            $userID= IAuth::getAdminIDCurrLogged();
            $field = $this->fieldOptionModel->getCmsDataByUserID($userID);
            $fieldOption = [
                'zone'=>'1',
                'table_no'=>'1',
                'checkin_status'=>'1',
                'checkin_at'=>'1',
                'checkin_by'=>'1',
                'event'=>'1',
                'actions'=>'1'
            ];
            if(!empty($field)){
                $res = json_decode($field,true);
                foreach($res as $v){
                    $fieldOption[$v['key']] = $v['value'];
                }
            }
            $zones = $this->zoneModel->getZoneNames($eventId);
//            $days = $this->eventModel->getEventDays($eventId);
            $days = [];
            $data = [
                'search' => $search,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'user_fields'=>$userFields,
                'permissions'=>$this->getCmsAdminPagePermissions(),
                'fieldOption'=>$fieldOption,
                'zones'=>$zones,
                'days'=>$days,
                'cur_day'=>date('Y-m-d',time()),
                'join'=>$join,
                'self_register'=>$selfRegister
            ];
            return view('index', $data);
        }
    }

    public function vendor(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $user_type = $request->param('user_type',1);
        $event_id = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$user_type);
            return showMsg(1, 'success', $list);
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $users = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$user_type,$eventId);
            $record_num = $this->model->getCmsDatasCount($search,$user_type);
            $data = [
                'articles' => $users,
                'search' => $search,
                'user_type' => $user_type,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId
            ];
            return view('vendor', $data);
        }
    }

    /**
     * 添加文章
     * @param Request $request
     * @return \think\response\View|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->param();
            $eventId = isset($input['event_id'])?$input['event_id']:'';
            $zoneId = isset($input['zone_id'])?$input['zone_id']:'';
            $tableId = isset($input['table_id'])?$input['table_id']:'';
//            // check if zone reach the capacity
//            $capacity = $this->zoneModel->getCapacity($zoneId);
//            $zoneCount = $this->model->getCheckedInCountByZone($eventId,$zoneId);
//            if($zoneCount >= $capacity){
//                return showMsg(0, 'Sorry the zone has reached the capacity!');
//            }
//            // check if table reach the capacity
//            $capacity = $this->tableModel->getCapacity($tableId);
//            $tableCount = $this->model->getCheckedInCountByTable($eventId,$tableId);
//            if($tableCount >= $capacity){
//                return showMsg(0, 'Sorry the table has reached the capacity!');
//            }
            $addData = [
                'unique_id' => Tools::create_guid(),
                'type' => isset($input['type'])?$input['type']:'',
                'zone_id' => $zoneId,
                'table_id' => $tableId,
                'event_id' => $eventId,
                'checkin_status' => 0,
                'status'=>1
            ];
            $opRes = $this->model->addData($addData);
            if($opRes['tag']){
                $id = $opRes['id'];
                $userFields = $this->dataFieldModel->getCmsList($eventId);
                $userDatas = [];
                if($userFields){
                    foreach($userFields as $k => $v){
                        $value = isset($input[$v['key']])?$input[$v['key']]:'';
                        if($v['type'] == 'checkbox'){
                            if(!empty($value)){
                                $data = implode("\r\n",$value);
                            }else{
                                $data = '';
                            }
                        }else{
                            if($v['type'] == 'dropdown' && $value == 'Others'){
                                $other = isset($input[$v['key'].'_other'])?$input[$v['key'].'_other']:'';
                                $value .= '-'.$other;
                            }
                            $data = trim($value);
                            $data = Tools::removeInvisibleCharacters($data);
                        }
                        $userDatas[] = [
                            'id'=>Tools::create_guid(),
                            'event_id' => $eventId,
                            'user_id'=>$id,
                            'key'=>$v['key'],
                            'value'=>$data,
                            'status'=>1
                        ];
                    }
                    $this->userDataModel->insertAll($userDatas);
                }
            }
            return showMsg(1, 'ok');
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $input = $request->param();
            $eventId = isset($input['event_id'])?$input['event_id']:'';
            $eventInfo = Tools::find_array_item($events,'id',$eventId);
            $zones = $this->zoneModel->getSimpleList($eventId);
            $tables = !empty($zones)?$this->tableModel->getSimpleList($zones[0]['id']):[];
            $userFields = $this->dataFieldModel->getCmsList($eventId);
            if($userFields){
                foreach($userFields as $k => $v){
                    $v['options'] = explode("\r\n",$v['options']);
                    if($v['key'] == 'serial_number'){
                        $v['default'] = Tools::create_number_unique();
                    }
                    $userFields[$k] = $v;
                }
            }
            return view('add',['events'=>$events,
                'zones'=>$zones,
                'tables'=>$tables,
                'user_fields'=>$userFields,
                'event_id'=>$eventId,
                'event_name'=>!empty($eventInfo)?$eventInfo['name']:''
            ]);
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $opRes = $this->model->updateCmsData($input,$id);
            $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
            if($opTag == 'del'){
                $this->userDataModel->where('user_id',$id)->delete();
                $this->userTableModel->where('user_id',$id)->delete();
            }else{
                $userFields = $this->dataFieldModel->getCmsList($input['event_id']);
                $userDatas = [];
                if($userFields){
                    foreach($userFields as $k => $v){
                        $value = isset($input[$v['key']])?$input[$v['key']]:'';
                        if($v['type'] == 'checkbox'){
                            if(!empty($value)){
                                $data = implode("\r\n",$value);
                            }else{
                                $data = '';
                            }
                        }else{
                            if($v['type'] == 'dropdown' && $value == 'Others'){
                                $other = isset($input[$v['key'].'_other'])?$input[$v['key'].'_other']:'';
                                $value .= '-'.$other;
                            }
                            $data = trim($value);
                            $data = Tools::removeInvisibleCharacters($data);
                        }
                        $userDatas[] = [
                            'id'=>Tools::create_guid(),
                            'event_id' => $input['event_id'],
                            'user_id'=>$id,
                            'key'=>$v['key'],
                            'value'=>$data,
                            'status'=>1
                        ];
                    }
                    Db::transaction(function () use($id,$userDatas){
                        $this->userDataModel->where('user_id',$id)->delete();
                        $this->userDataModel->insertAll($userDatas);
                    });
                }
            }
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $comments = [];
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventInfo = Tools::find_array_item($events,'id',$article['event_id']);
            $zones = $this->zoneModel->getSimpleList($article['event_id']);
            $tables = !empty($zones)?$this->tableModel->getSimpleList($article['zone_id']):[];
            $items = $this->userDataModel->getDataList($id);
            $userFields = $this->dataFieldModel->getCmsList($article['event_id']);
            if($userFields){
                foreach($userFields as $k => $v){
                    $item = Tools::find_array_item($items,'key',$v['key']);
                    if(!empty($item)){
                        $article[$v['key']] = $item['value'];
                        if($v['type'] == 'dropdown' && strpos($item['value'],'Others-') === 0) {
                            $article[$v['key']] = 'Others';
                            $article[$v['key'] . '_other'] = substr($item['value'], strlen('Others-'));
                        }
                    }else{
                        $article[$v['key']] = '';
                    }
                    $v['options'] = explode("\r\n",$v['options']);
                    $userFields[$k] = $v;
                }
            }
            $data =
                [
                    'article' => $article,
                    'comments' => $comments,
                    'zones' => $zones,
                    'tables'=>$tables,
                    'events'=>$events,
                    'user_fields'=>$userFields,
                    'event_name'=>!empty($eventInfo)?$eventInfo['name']:''
                ];
            return view('edit', $data);
        }
    }

    public function ajaxUpdateUserStatus(Request $request){
        if ($request->isPost()) {
            $user_id = $request->post('user_id', 0);
            $user_status = $request->post('user_status',0);
            $opRes = $this->model->updateUserStatus($user_id, $user_status);
            return showMsg($opRes['status'], $opRes['message']);
        } else {
            return showMsg(0, 'sorry，invalid request!');
        }
    }

    public function attend(Request $request,$id){
        $eventID = $request->param('event_id');
        $userID= IAuth::getAdminIDCurrLogged();
        $res = $this->userStatusModel->addData([
            'user_id'=>$id,
            'day'=>date('Y-m-d',time()),
            'event_id'=>$eventID,
            'checkin_status'=>1,
            'op_user_id'=>$userID,
            'checkin_time'=>date('Y-m-d H:i:s',time())
        ]);
        return showMsg($res,'ok');
    }

    public function unattend(Request $request,$id){
        $eventID = $request->param('event_id');
//        $userID= IAuth::getAdminIDCurrLogged();
        $res = $this->userStatusModel->deleteData($id,date('Y-m-d',time()),$eventID);
        return showMsg($res,'ok');
    }

    public function deleteAll(Request $request){
        $eventID = $request->param('event_id');
        $ids = $request->param('ids');
        if(!empty($ids)){
            $res = $this->model->where('id','in',$ids)->delete();
            // 同时删除userdatas和datatables表的数据
            $this->userDataModel->where('user_id','in',$ids)->delete();
            $this->userTableModel->where('user_id','in',$ids)->delete();
            $this->userStatusModel->where('user_id','in',$ids)->delete();
        }else{
            $res = $this->model->where('event_id','=',$eventID)->delete();
            // 同时删除userdatas和datatables表的数据
            $this->userDataModel->where('event_id','=',$eventID)->delete();
            $this->userTableModel->where('event_id','=',$eventID)->delete();
            $this->userStatusModel->where('event_id','=',$eventID)->delete();
        }
        return showMsg($res,'ok');
    }

    public function attendAll(Request $request){
        $ids = $request->param('ids');
        $userID= IAuth::getAdminIDCurrLogged();
        $res = $this->model->updateAllUserAttend($ids,$userID);
        return showMsg($res,'ok');
    }

    public function approve(Request $request){
        $eventID = $request->param('event_id');
        $ids = $request->param('ids');
        foreach($ids as $id){
            Db::name('xuser_datas')->where('event_id',$eventID)
                ->where('status',1)
                ->where('user_id',$id)
                ->where('key','=','join')
                ->update([
                    'value'=>1,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
            $zone = $this->userTableModel->getUserZone($eventID,$id);
            $templateName = 'accept'.substr($zone,strlen('zone'));
            $templateId = Db::name('xedm_templates')->where('event_id',$eventID)
                ->where('status',1)
                ->where('name',$templateName)
                ->value('id');
            if(!empty($templateId)){
                Db::name('xedm_tasks')->insert([
                    'user_id'=>$id,
                    'template_id'=>$templateId,
                    'event_id'=>$eventID,
                    'status'=>9,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
            }
        }
        return showMsg(200,'ok');
    }

    public function reject(Request $request){
        $eventID = $request->param('event_id');
        $ids = $request->param('ids');
        foreach($ids as $id){
            Db::name('xuser_datas')->where('event_id',$eventID)
                ->where('status',1)
                ->where('user_id',$id)
                ->where('key','=','join')
                ->update([
                    'value'=>2,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
            $zone = $this->userTableModel->getUserZone($eventID,$id);
            $templateName = 'reject'.substr($zone,strlen('zone'));
            $templateId = Db::name('xedm_templates')->where('event_id',$eventID)
                ->where('status',1)
                ->where('name',$templateName)
                ->value('id');
            if(!empty($templateId)){
                Db::name('xedm_tasks')->insert([
                    'user_id'=>$id,
                    'template_id'=>$templateId,
                    'event_id'=>$eventID,
                    'status'=>9,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
            }
        }
        return showMsg(200,'ok');
    }

    public function confirmation(Request $request){
        $eventID = $request->param('event_id');
        $ids = $request->param('ids');
        $templateName = 'confirmation';
        $templateId = Db::name('xedm_templates')->where('event_id',$eventID)
            ->where('status',1)
            ->where('name',$templateName)
            ->value('id');
        if(empty($templateId)){
            return showMsg(500,'Please set confirmation edm template!');
        }
        foreach($ids as $id){
            $res = Db::name('xedm_tasks')->where('event_id',$eventID)
                ->where('user_id',$id)
                ->where('template_id',$templateId)
                ->select();
            if(empty($res)){
                //确认当前用户的join状态是否为1(accepted)
                $res2 = Db::name('xuser_datas')->where('event_id',$eventID)
                    ->where('status',1)
                    ->where('user_id',$id)
                    ->where('key','=','join')
                    ->where('value','=','1')
                    ->select();
                if(!empty($res2)){
                    Db::name('xedm_tasks')->insert([
                        'user_id'=>$id,
                        'template_id'=>$templateId,
                        'event_id'=>$eventID,
                        'status'=>9,
                        'create_time'=>date('Y-m-d H:i:s',time()),
                        'update_time'=>date('Y-m-d H:i:s',time())
                    ]);
                }
            }
        }
        return showMsg(200,'ok');
    }

    public function reminder(Request $request){
        $eventID = $request->param('event_id');
        $ids = $request->param('ids');
        $templateName = 'reminder';
        $templateId = Db::name('xedm_templates')->where('event_id',$eventID)
            ->where('status',1)
            ->where('name',$templateName)
            ->value('id');
        if(empty($templateId)){
            return showMsg(500,'Please set reminder edm template!');
        }
        foreach($ids as $id){
            $res = Db::name('xedm_tasks')->where('event_id',$eventID)
                ->where('user_id',$id)
                ->where('template_id',$templateId)
                ->select();
            if(empty($res)){
                //确认当前用户的join状态是否为5(confirmed)
                $res2 = Db::name('xuser_datas')->where('event_id',$eventID)
                    ->where('status',1)
                    ->where('user_id',$id)
                    ->where('key','=','join')
                    ->where('value','=','5')
                    ->select();
                if(!empty($res2)){
                    Db::name('xedm_tasks')->insert([
                        'user_id'=>$id,
                        'template_id'=>$templateId,
                        'event_id'=>$eventID,
                        'status'=>9,
                        'create_time'=>date('Y-m-d H:i:s',time()),
                        'update_time'=>date('Y-m-d H:i:s',time())
                    ]);
                }
            }
        }
        return showMsg(200,'ok');
    }

    public function downloadQRCode(Request $request){
        //test decrypt
//        $decode = IAuth::decrypt2("eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.IjEwMDQi.rY4-1i40LbhiCCr04oxzbPTGsIWpnsTkRAv9Uph3LkNcxzMz-1PcNjrIStsIzNeOn9prOEo02Fum8QMVgrOgWQ");

        $id = $request->param('id');
        $res = $this->userDataModel->getCmsData($id,'serial_number');
        if(!empty($res)){
            $serialNumber = $res['value'];
        }
        if(empty($serialNumber)){
            $serialNumber = 'serial number not found';
        }
        $encrypt = IAuth::encrypt2($serialNumber);
        $filename = QRCode::create_qrcode($encrypt,null,$serialNumber.".jpg");
        $path = Env::get('root_path')."/public/qrcode/".$filename;
        ob_end_clean();
        $fp = fopen($path,'rb');
        header('Content-type:image/jpeg;charset=utf-8;name="'.$filename.'"');
        header("Content-Disposition:attachment;filename=".$filename);
        fpassthru($fp);
        fclose($fp);
        unlink($path);
        exit;
    }

    public function downloadAllQRCode(Request $request){
        $ids = $request->param('ids');
        $data = explode(",",$ids);
        $zipName = "QR".date('YmdHis') . rand(1000, 9999).'.zip';
        $path = Env::get('root_path')."public\\temp\\";
        $zip = new \ZipArchive();
        $zip->open($path.$zipName,\ZipArchive::CREATE);
        $paths = [];
        foreach($data as $v){
            $res = $this->userDataModel->getCmsData($v,'serial_number');
            if(!empty($res)){
                $serialNumber = $res['key'];
                if(!empty($serialNumber)){
                    $filename = QRCode::create_qrcode($serialNumber,null,$serialNumber.'.jpg');
                    $filepath = Env::get('root_path')."/public/qrcode/".$filename;
                    $paths[] = $filepath;
                    $zip->addFile($filepath,$filename);
                }
            }
        }
        $zip->close();
        //输出字节流
        $fp = fopen($path.$zipName,'rb');
        header('Access-Control-Expose-Headers:Content-Disposition');
        header('Content-Type:application/zip;name='.$zipName);
        header("Content-Disposition:attachment;filename=".$zipName);
        fpassthru($fp);
        fclose($fp);
        unlink($path.$zipName);
        foreach($paths as $path){
            unlink($path);
        }
        exit;
    }

    public function view(Request $request,$id){
        $article = $this->model->getCmsDataByID($id);
        $items = $this->userDataModel->getDataList($id);
        $userFields = $this->dataFieldModel->getCmsList($article['event_id']);
        if($userFields){
            foreach($userFields as $k => $v){
                $item = Tools::find_array_item($items,'key',$v['key']);
                if(!empty($item)){
                    $article[$v['key']] = $item['value'];
                }else{
                    $article[$v['key']] = '';
                }
                $v['options'] = explode("\r\n",$v['options']);
                $userFields[$k] = $v;
            }
        }
        $data =
            [
                'article' => $article,
                'user_fields'=>$userFields
            ];
        return view('view', $data);
    }

    public function trackList(Request $request){
        $id = $request->param('id');
        $article = $this->model->getCmsDataByID($id);
        $days = $this->eventModel->getEventDays($article['event_id']);
        if ($request->isPost()) {
            $zones = $this->userTableModel->getDataList($id);
            $items = [];
            if ($zones) {
                foreach ($zones as $k => $v) {
                    $item = [
                        'zone' => $v['zone_name'],
                        'zone_id' => $v['zone_id'],
                        'user_id' => $id,
                        'event_name' => $article['event_name'],
                        'event_id' => $article['event_id']
                    ];
                    foreach($days as $day){
                        $dayItem = $this->trackModel->getCmsDataByDay($id,$v['zone_id'],$day);
                        $item['checkin_status_'.$day] = !empty($dayItem)?$dayItem['checkin_status']:0;
                        $item['op_user_'.$day] = !empty($dayItem)?$dayItem['op_user']:'';
                        $item['checkin_time_'.$day] = !empty($dayItem)?$dayItem['checkin_time']:'';
                    }
                    $items[] = $item;
                }
            }
            return showMsg(200, 'ok',['data'=>$items,'total'=>count($items)]);
        }else{
            $data =
                [
                    'article' => $article,
                    'permissions'=>$this->getCmsAdminPagePermissions(),
                    'days' => $days,
                    'curDay' => date('Y-m-d',time())
                ];
            return view('track_list', $data);
        }
    }

    public function attendTrack(Request $request){
        $zoneID = $request->param('zone_id');
        $userID = $request->param('user_id');
        $eventID = $request->param('event_id');
        $opUserID= IAuth::getAdminIDCurrLogged();
        $this->trackModel->attendUser($userID,$zoneID,$opUserID,
            date('Y-m-d H:i:s',time()),1,date('Y-m-d',time()),$eventID);
        return showMsg(200,'ok');
    }

    public function unAttendTrack(Request $request){
        $zoneID = $request->param('zone_id');
        $userID = $request->param('user_id');
        $eventID = $request->param('event_id');
        $this->trackModel->unAttendUser($userID,$zoneID,date('Y-m-d',time()),$eventID);
        return showMsg(200,'ok');
    }

    public function remark(Request $request){
        $eventID = $request->param('event_id');
        $remark = $request->param('remark');
        $ids = $request->param('ids');
        foreach($ids as $id){
            $res = Db::name('xuser_datas')->where('event_id',$eventID)
                ->where('status',1)
                ->where('user_id',$id)
                ->where('key','=','remarks')
                ->find();
            if(!empty($res)){
                Db::name('xuser_datas')->where('id',$res['id'])
                    ->update([
                        'value'=>$remark,
                        'update_time'=>date('Y-m-d H:i:s',time())
                    ]);
            }else{
                Db::name('xuser_datas')->insert([
                    'id'=>Tools::create_guid(),
                    'user_id'=>$id,
                    'status'=>1,
                    'event_id'=>$eventID,
                    'key'=>'remarks',
                    'value'=>$remark,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
            }
        }
        return showMsg(200,'ok');
    }

    public function fieldOptions(Request $request){
        $userID= IAuth::getAdminIDCurrLogged();
        if ($request->isPost()) {
            $data = [
//                ['name'=>'Zone','key'=>'zone','value'=>$request->param('zone','0')],
//                ['name'=>'Table No','key'=>'table_no','value'=>$request->param('table_no','0')],
                ['name'=>'Checkin Status','key'=>'checkin_status','value'=>$request->param('checkin_status','0')],
                ['name'=>'Checkin At','key'=>'checkin_at','value'=>$request->param('checkin_at','0')],
                ['name'=>'Checkin By','key'=>'checkin_by','value'=>$request->param('checkin_by','0')],
                ['name'=>'Event','key'=>'event','value'=>$request->param('event','0')],
                ['name'=>'Actions','key'=>'actions','value'=>$request->param('actions','0')],
            ];
            $res = $this->fieldOptionModel->getCmsDataByUserID($userID);
            if(empty($res)){
                $res = $this->fieldOptionModel->addData($userID,json_encode($data));
            }else{
                $res = $this->fieldOptionModel->updateCmsData($userID,json_encode($data));
            }
            return showMsg($res['tag'],$res['message']);
        }else{
            $field = $this->fieldOptionModel->getCmsDataByUserID($userID);
            if(!empty($field)){
                $fieldOption = json_decode($field,true);
            }else{
                $fieldOption = [
//                    ['name'=>'Zone','key'=>'zone','value'=>'1'],
//                    ['name'=>'Table No','key'=>'table_no','value'=>'1'],
                    ['name'=>'Checkin Status','key'=>'checkin_status','value'=>'1'],
                    ['name'=>'Checkin At','key'=>'checkin_at','value'=>'1'],
                    ['name'=>'Checkin By','key'=>'checkin_by','value'=>'1'],
                    ['name'=>'Event','key'=>'event','value'=>'1'],
                    ['name'=>'Actions','key'=>'actions','value'=>'1'],
                ];
            }
            return view('field_options',['fieldOption'=>$fieldOption]);
        }
    }

    public function upload(Request $request){
        set_time_limit(0);
        $eventId = $request->param('event_id');
        $fileUrl = $request->param('file_url');
        $fileUrl = urldecode($fileUrl);
        $filePath = Env::get('root_path').'public/'.$fileUrl;
        $filePath = str_replace('/',DIRECTORY_SEPARATOR,$filePath);
        $this->userCache = [];

        //读取excel内容
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($filePath)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filePath)){
                showMsg(0,'can not read file!');
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn(1);
        $allRow = $currentSheet->getHighestRow();
        $data = array();
        $cellKey = Tools::getExcelColumnTitles(1000);
        $cellTitle = [];
        $maxColumn = array_search($allColumn,$cellKey)+1;
        $userFields = $this->dataFieldModel->getCmsList($eventId);
        $zones = $this->zoneModel->getZoneNames($eventId);
        $days = $this->eventModel->getEventDays($eventId);
        $serialNumberIdx = -1;
        for($i=0;$i<$maxColumn;$i++){
            $value = (string)($currentSheet->getCell($cellKey[$i].'1')->getValue());
            $cellTitle[$cellKey[$i]] = $value;
            if($value == 'Serial Number'){
                $serialNumberIdx = $i;
            }
        }
        if($serialNumberIdx >= 0){
            for($rowIndex = 2;$rowIndex<=$allRow;$rowIndex++){
                $serialNumber = (string)($currentSheet->getCell($cellKey[$serialNumberIdx].$rowIndex)->getValue());
                if(empty($serialNumber)) continue;
                // 查找serial number是否存在
                $userID = Db::name('xuser_datas')->where('key','serial_number')
                    ->where('value',$serialNumber)
                    ->where('status',1)
                    ->where('event_id',$eventId)
                    ->value('user_id');
                if(empty($userID)){
                    $item = [
                        'event_id'=>$eventId,
                        'status'=>1,
                        'unique_id'=>Tools::create_guid(),
                        'create_time'=>date('Y-m-d H:i:s',time()),
                        'update_time'=>date('Y-m-d H:i:s',time())
                    ];
                    $userID = Db::name('xusers')->insertGetId($item);
                    $userID = intval($userID);
                }
                if($userID > 0){
                    $item = [];
                    for($i=0;$i<$maxColumn;$i++){
                        $value = (string)($currentSheet->getCell($cellKey[$i].$rowIndex)->getValue());
                        $value = trim($value);
                        $value = Tools::removeInvisibleCharacters($value);
                        $name = $cellTitle[$cellKey[$i]];
                        $arrItem = Tools::find_array_item($userFields,"name",$name);
                        if(!empty($arrItem)){
                            $item[] = [
                                'id'=>Tools::create_guid(),
                                'event_id'=>$eventId,
                                'user_id'=>$userID,
                                'key'=>$arrItem['key'],
                                'value'=>$value,
                                'status'=>1,
                                'create_time'=>date('Y-m-d H:i:s',time()),
                                'update_time'=>date('Y-m-d H:i:s',time())
                            ];
                        }
                    }
                    if(!empty($item)){
                        //先清空该user id再插入
                        Db::name('xuser_datas')->where('event_id',$eventId)
                            ->where('user_id',$userID)
                            ->delete();
                        $res = Db::name('xuser_datas')->insertAll($item);
                    }
                    // add table data
                    // 先清空该user id的tables表数据再插入
                    Db::name('xuser_tables')
                        ->where('event_id',$eventId)
                        ->where('user_id',$userID)
                        ->delete();
                    $item = [];
                    for($i=0;$i<$maxColumn;$i++){
                        $value = (string)($currentSheet->getCell($cellKey[$i].$rowIndex)->getValue());
                        $columnName = $cellTitle[$cellKey[$i]];
                        if(in_array($columnName,$zones)){
                            $zoneID = $this->zoneModel->getIdByName($eventId,$columnName);
                            $tableID = $this->tableModel->getIdByName($eventId,$value,$zoneID);
                            if(!empty($tableID)){
                                $res = Db::name('xuser_tables')
                                    ->where('event_id',$eventId)
                                    ->where('user_id',$userID)
                                    ->where('zone_id',$zoneID)
                                    ->where('table_id',$tableID)
                                    ->where('status',1)
                                    ->find();
                                if(empty($res)){
                                    Db::name('xuser_tables')
                                        ->insert([
                                            'id'=>Tools::create_guid(),
                                            'event_id'=>$eventId,
                                            'user_id'=>$userID,
                                            'zone_id'=>$zoneID,
                                            'table_id'=>$tableID,
                                            'status'=>1,
                                            'create_time'=>date('Y-m-d H:i:s',time())
                                        ]);
                                }
                            }
                        }
                    }
                    // add user status
                    // 先清空该用户的status表再插入
                    $this->userStatusModel->where('event_id',$eventId)
                        ->where('user_id',$userID)
                        ->delete();
                    foreach($days as $day){
                        $status = ['checkin_status'=>0,'checkin_time'=>'','checkin_by'=>''];
                        for($i=0;$i<$maxColumn;$i++) {
                            $value = (string)($currentSheet->getCell($cellKey[$i] . $rowIndex)->getValue());
                            $columnName = $cellTitle[$cellKey[$i]];
                            if($columnName == $day.' Checkin Status'){
                                $status['checkin_status'] = $value=='checkin' ? 1 : 0;
                            }else if($columnName == $day.' Checkin At'){
                                $status['checkin_time'] = $value;
                            }else if($columnName == $day.' Checkin By'){
                                if(isset($this->userCache[$value])){
                                    $status['checkin_by'] = $this->userCache[$value];
                                }else{
                                    $adminId = $this->adminModel->getIdByUserName($value);
                                    $this->userCache[$value] = $adminId;
                                    $status['checkin_by'] = $adminId;
                                }
                            }
                        }
                        $this->userStatusModel->addData([
                            'user_id'=>$userID,
                            'event_id'=>$eventId,
                            'day'=>$day,
                            'checkin_status'=>$status['checkin_status'],
                            'checkin_time'=>!empty($status['checkin_time'])?$status['checkin_time']:null,
                            'op_user_id'=>$status['checkin_by']
                        ]);
                    }
                    // add track status
                    foreach($days as $day){
                        foreach($zones as $zone){
                            $status = ['checkin_status'=>0,'checkin_time'=>'','checkin_by'=>''];
                            for($i=0;$i<$maxColumn;$i++) {
                                $value = (string)($currentSheet->getCell($cellKey[$i] . $rowIndex)->getValue());
                                $columnName = $cellTitle[$cellKey[$i]];
                                if($columnName == $day.' '.$zone.' Checkin Status'){
                                    $status['checkin_status'] = $value == 'checkin' ? 1 : 0;
                                }else if($columnName == $day.' '.$zone.' Checkin At'){
                                    $status['checkin_time'] = $value;
                                }else if($columnName == $day.' '.$zone.' Checkin By'){
                                    if(isset($this->userCache[$value])){
                                        $status['checkin_by'] = $this->userCache[$value];
                                    }else{
                                        $adminId = $this->adminModel->getIdByUserName($value);
                                        $this->userCache[$value] = $adminId;
                                        $status['checkin_by'] = $adminId;
                                    }
                                }
                            }
                            $zoneID = $this->zoneModel->getIdByName($eventId,$zone);
                            $this->trackModel->attendUser($userID,$zoneID,$status['checkin_by'],
                                $status['checkin_time'],$status['checkin_status'],$day,$eventId);
                        }
                    }
                }
            }
        }
        return showMsg(1,'upload users successfully!');
    }

    public function download(Request $request){
        ini_set('max_execution_time', '600');
        ini_set('memory_limit',-1); //没有内存限制
        $eventId = $request->param('event_id');
        $userFields = $this->dataFieldModel->getCmsList($eventId);
        $record_num = $this->model->getCmsDatasCount(null,$eventId,null);
        $articles = $this->model->getCmsDatasForPage(1, $record_num, null,$eventId,null);
        if($articles){
            foreach($articles as $k => $v){
                $items = $this->userDataModel->getDataList($v['id']);
                foreach($userFields as $k1 => $v1){
                    $item = Tools::find_array_item($items,'key',$v1['key']);
                    if(!empty($item)){
                        $v[$v1['key']] = $item['value'];
//                        if($v1['key'] == 'join' && !empty($item['value'])){
//                            $v['reg_time'] = $item['create_time'];
//                        }
                    }else{
                        $v[$v1['key']] = '';
                    }
                }
                $articles[$k] = $v;
            }
        }
        $zones = $this->zoneModel->getSimpleList($eventId);
        $days = $this->eventModel->getEventDays($eventId);
//        ob_start();
//        $file = fopen("php://output", 'w');
//        fwrite($file,chr(0xEF).chr(0xBB).chr(0xBF));
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'users';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellName = array('ID');
        foreach($userFields as $v){
            $cellName[] = $v['name'];
        }
        $cellName[] = 'Reg Time';
        foreach($zones as $v){
            $cellName[] = $v['name'];
        }
//        foreach($zones as $v){
//            foreach($days as $day){
//                $cellName[] = $day.' '.$v['name'].' Checkin Status';
//                $cellName[] = $day.' '.$v['name'].' Checkin At';
//                $cellName[] = $day.' '.$v['name'].' Checkin By';
//            }
//        }
//        foreach($days as $day){
//            $cellName[] = $day.' Checkin Status';
//            $cellName[] = $day.' Checkin At';
//            $cellName[] = $day.' Checkin By';
//        }
        $cellName[] = 'Event';
//        fputcsv($file,$cellName);
        $cellKey = Tools::getExcelColumnTitles(count($cellName)+5);
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane('A'.($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        // 缓存数据 tables
        $tables = $this->userTableModel->getUserTablesByEvent($eventId);
        $tableCache = [];
        if($tables){
            foreach($tables as $v){
                $tableCache[$v['user_id'].'-'.$v['zone_name']] = $v['table_name'];
            }
        }
        // 缓存数据 tracks
//        $tracks = $this->trackModel->getCmsDataByEvent($eventId);
//        $trackCache = [];
//        if($tracks){
//            foreach($tracks as $v){
//                $trackCache[$v['user_id'].'-'.$v['zone_id'].'-'.$v['day']] = $v;
//            }
//        }
        // 缓存数据 status
//        $statuss = $this->userStatusModel->getDataByEvent($eventId);
//        $statusCache = [];
//        if($statuss){
//            foreach($statuss as $v){
//                $statusCache[$v['user_id'].'-'.$v['day']] = $v;
//            }
//        }
        //处理数据
        foreach($articles as $k=>$v){
            $index = 0;
            $row = [];
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$k+1);
//            $eow[] = $k+1;
            foreach($userFields as $v1){
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v[$v1['key']]);
//                  $row[] = $v[$v1['key']];
            }
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),isset($v['reg_time'])?$v['reg_time']:'');
            foreach($zones as $zone){
                $tableName = isset($tableCache[$v['id'].'-'.$zone['name']])?$tableCache[$v['id'].'-'.$zone['name']]:'';
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$tableName);
//            $row[] = $tableName;
            }
//            foreach($zones as $zone){
//                foreach($days as $day){
//                    $track = isset($trackCache[$v['id'].'-'.$zone['id'].'-'.$day])?$trackCache[$v['id'].'-'.$zone['id'].'-'.$day]:'';
//                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),!empty($track)&&$track['checkin_status']==1?'checkin':'');
//                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),!empty($track)?$track['checkin_time']:'');
//                    $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),!empty($track)?$track['op_user']:'');
//                }
//            }
//            foreach($days as $day){
//                $status = isset($statusCache[$v['id'].'-'.$day])?$statusCache[$v['id'].'-'.$day]:'';
//                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),!empty($status)&&$status['checkin_status']==1?'checkin':'');
//                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),!empty($status)?$status['checkin_time']:'');
//                $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),!empty($status)?$status['op_user']:'');
//            }
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey[$index++].($k+1+$topNumber),$v['event_name']);
//            $row[] = $v['event_name'];
//            fputcsv($file,$row);
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
//        Header("Content-type: application/octet-stream"); #通过这句代码客户端浏览器就能知道服务端返回的文件形式
//        Header("Accept-Ranges: bytes"); #告诉客户端浏览器返回的文件大小是按照字节进行计算的
//        Header("Content-Disposition: attachment; filename=".$fileName); #告诉浏览器返回的文件的名称
//        echo fread($file,filesize($file));
//        fclose($file);

    }

    public function resetPassword(Request $request,$id){
        $user = $this->model->getCmsDataByID($id);
        $rs = $this->model->updatePassword($id,'Reset Password','Your password has been reset.Please use following account and password to login',$user['email'],$request->domain());
        if (!$rs['status']) {
            return showMsg(0,$rs['msg']);
        }
        return showMsg(1,'password has been reset');
    }

    public function resetVendorPassword(Request $request,$id){
        $user = $this->model->getCmsDataByID($id);
        $rs = $this->model->updatePassword($id,'Reset Password','Your password has been reset.Please use following account and password to login',$user['email'],$request->domain());
        if (!$rs['status']) {
            return showMsg(0,$rs['msg']);
        }
        return showMsg(1,'password has been reset');
    }

    public function preview(Request $request,$id){
        $article = $this->model->getCmsDataByID($id);
        $items = $this->userDataModel->getDataList($id);
        $userFields = $this->dataFieldModel->getCmsList($article['event_id']);
        if($userFields){
            foreach($userFields as $k => $v){
                $item = Tools::find_array_item($items,'key',$v['key']);
                if(!empty($item)){
                    $article[$v['key']] = $item['value'];
                }else{
                    $article[$v['key']] = '';
                }
                $v['options'] = explode("\r\n",$v['options']);
                $userFields[$k] = $v;
            }
        }
        $visitorType = isset($article['visitor_category'])?$article['visitor_category']:'';
        $template = $this->cardTemplateModel->getDataByType($article['event_id'],$visitorType);
        $bg_width = 0;
        $bg_height = 0;
        $content1 = '';
        $content2 = '';
        $double_side = 0;
        if(!empty($template)){
            $data1 = json_decode($template['content1'],true);
            $data1 = $this->parseTemplateContent($data1,$article,$request->domain());
            $content1 = json_encode($data1);
            if($template['double_side'] == '1'){
                $data2 = json_decode($template['content2'],true);
                $data2 = $this->parseTemplateContent($data2,$article,$request->domain());
                $content2 = json_encode($data2);
            }
            $bg_width = $template['bg_width'];
            $bg_height = $template['bg_height'];
            $double_side = $template['double_side'];
        }
        $res =
            [
                'article' => $article,
                'user_fields'=>$userFields,
                'content1'=>$content1,
                'content2'=>$content2,
                'bg_width'=>$bg_width,
                'bg_height'=>$bg_height,
                'double_side'=>$double_side
            ];
        return view('preview', $res);
    }

    private function parseTemplateContent($templateData,$userData,$domain){
        foreach($templateData['objects'] as $k => $v){
            if($v['customType'] == 'text'){
                $matches = [];
                preg_match_all('/\[(.*)\]/U',$v['text'],$matches);
                $matcheResults = $matches[1];
                foreach($matcheResults as $v1){
                    $key = $this->dataFieldModel->getKeyByName($v1);
                    $value = isset($userData[$key])?$userData[$key]:'';
                    $v['text'] = str_replace('['.$v1.']',$value,$v['text']);
                }

            }else if($v['customType'] == 'image'){
                $matches = [];
                preg_match_all('/\[(.*)\]/U',$v['customText'],$matches);
                $matcheResults = $matches[1];
                if(count($matcheResults) > 0){
                    $key = $this->dataFieldModel->getKeyByName($matcheResults[0]);
                    $value = isset($userData[$key])?$userData[$key]:'';
                    $filename = ImageUtil::resize($value,$v['width']*$v['scaleX'],
                        $v['height']*$v['scaleY']);
                    $url = $domain.'/images/'.$filename;
                    $v['src'] = $url;
                }
            }else if($v['customType'] == 'qrcode'){
                $matches = [];
                preg_match_all('/\[(.*)\]/U',$v['customText'],$matches);
                $matcheResults = $matches[1];
                foreach($matcheResults as $v1){
                    $key = $this->dataFieldModel->getKeyByName($v1);
                    $value = isset($userData[$key])?$userData[$key]:'';
                    $v['customText'] = str_replace('['.$v1.']',$value,$v['customText']);
                }
                $qrUrl = QRCode::create_qrcode($v['customText'],null,null,
                    $v['width']*$v['scaleX'],$v['height']*$v['scaleY']);
                $v['src'] = $domain.'/qrcode/'.$qrUrl;
            }
            $templateData['objects'][$k] = $v;
        }
        return $templateData;
    }
}