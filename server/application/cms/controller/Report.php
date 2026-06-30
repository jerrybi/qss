<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XdataFields;
use app\common\model\Xtables;
use app\common\model\XuserDatas;
use app\common\model\XuserEvents;
use app\common\model\Xusers;
use app\common\model\XuserStatus;
use app\common\model\XuserTables;
use app\common\model\Xevents;
use app\common\model\XlocationGroups;
use app\common\model\Xzones;
use think\Request;
use app\common\lib\IAuth;
use app\common\lib\MyRedis;
use app\common\lib\MTPhpExcel;
use think\Db;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class Report extends CmsBase
{
    protected $zoneModel;
    protected $userModel;
    protected $tableModel;
    protected $userDataModel;
    protected $dataFieldModel;
    protected $userTableModel;
    protected $eventModel;
    protected $userStatusModel;
    protected $userEventModel;

    public function __construct()
    {
        parent::__construct();
        $this->zoneModel = new Xzones();
        $this->userModel = new Xusers();
        $this->tableModel = new Xtables();
        $this->userDataModel = new XuserDatas();
        $this->dataFieldModel = new XdataFields();
        $this->userTableModel = new XuserTables();
        $this->eventModel = new Xevents();
        $this->userStatusModel = new XuserStatus();
        $this->userEventModel = new XuserEvents();
    }

    public function index(Request $request)
    {
        return view('index');
    }

    public function zones(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $day = $request->param('day');
        $event = new Xevents();
        $events = $event->getSimpleEventsList();
        $eventId = null;
        if(!empty($event_id)){
            $eventId = $event_id;
        }else if(!empty($events)){
            $eventId = $events[0]['id'];
        }
        $event = $this->eventModel->getCmsEventByID($eventId);
        $days = $this->eventModel->getEventDays($eventId);
        if(empty($day)){
            $day = $days[0];
        }
        if ($request->isPost()) {
            $list = $this->zoneModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            if($list){
                foreach($list as $k => $v){
                    $v['capacity'] = $this->userTableModel->getCountByZone($eventId,$v['id']);
                    if($event['enable_track'] == '1'){
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByTrackZone($eventId,$v['id'],$day);
                    }else{
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByZone($eventId,$v['id'],$day);
                    }
                    $v['pending'] = $v['capacity'] - $v['checked_in'];
                    $list[$k] = $v;
                }
            }
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->zoneModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            $record_num = $this->zoneModel->getCmsDatasCount($search,$eventId);
            if($articles){
                foreach($articles as $k => $v){
                    $v['capacity'] = $this->userTableModel->getCountByZone($eventId,$v['id']);
                    if($event['enable_track'] == '1'){
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByTrackZone($eventId,$v['id'],$day);
                    }else{
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByZone($eventId,$v['id'],$day);
                    }
                    $v['pending'] = $v['capacity'] - $v['checked_in'];
                    $articles[$k] = $v;
                }
            }
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'days'=>$days,
                'day'=>$day
            ];
            return view('zones', $data);
        }
    }

    public function zoneView(Request $request){
        $id = $request->param('id');
        $eventID = $request->param('event_id');
        $day = $request->param('day');
        $userFields = $this->dataFieldModel->getCmsList($eventID);
        $event = $this->eventModel->getCmsEventByID($eventID);
        if($request->isPost()){
            if($event['enable_track'] == '1'){
                $res = $this->userTableModel->getUsersByTrackZone($eventID,$id,$day);
            }else{
                $res = $this->userTableModel->getUsersByZone($eventID,$id,$day);
            }
            if($res){
                foreach($res as $k => $v){
                    $items = $this->userDataModel->getDataList($v['id']);
                    foreach($userFields as $k1 => $v1){
                        $item = Tools::find_array_item($items,'key',$v1['key']);
                        if(!empty($item)){
                            $v[$v1['key']] = $item['value'];
                        }else{
                            $v[$v1['key']] = '';
                        }
                    }
                    $res[$k] = $v;
                }
            }
            $total = count($res);
            return showMsg(200,'ok',['total'=>$total,'data'=>$res]);
        }else{
            $data = [
                'user_fields'=>$userFields,
                'event_id' => $eventID,
                'zone_id' => $id
            ];
            return view('zone_view',$data);
        }
    }

    public function tables(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $day = $request->param('day');
        $event = new Xevents();
        $events = $event->getSimpleEventsList();
        $eventId = null;
        if(!empty($event_id)){
            $eventId = $event_id;
        }else if(!empty($events)){
            $eventId = $events[0]['id'];
        }
        $event = $this->eventModel->getCmsEventByID($eventId);
        $days = $this->eventModel->getEventDays($eventId);
        if(empty($day)){
            $day = $days[0];
        }
        if ($request->isPost()) {
            $list = $this->tableModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            if($list){
                foreach($list as $k => $v){
                    $v['capacity'] = $this->userTableModel->getCountByTable($eventId,$v['id']);
                    if($event['enable_track'] == '1'){
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByTrackTable($eventId,$v['id'],$day);
                    }else{
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByTable($eventId,$v['id'],$day);
                    }
                    $v['pending'] = $v['capacity'] - $v['checked_in'];
                    $list[$k] = $v;
                }
            }
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->tableModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            $record_num = $this->tableModel->getCmsDatasCount($search,$eventId);
            if($articles){
                foreach($articles as $k => $v){
                    $v['capacity'] = $this->userTableModel->getCountByTable($eventId,$v['id']);
                    if($event['enable_track'] == '1'){
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByTrackTable($eventId,$v['id'],$day);
                    }else{
                        $v['checked_in'] = $this->userTableModel->getCheckedInCountByTable($eventId,$v['id'],$day);
                    }
                    $v['pending'] = $v['capacity'] - $v['checked_in'];
                    $articles[$k] = $v;
                }
            }
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'days'=>$days,
                'day'=>$day
            ];
            return view('tables', $data);
        }
    }

    public function tableView(Request $request){
        $id = $request->param('id');
        $eventID = $request->param('event_id');
        $day = $request->param('day');
        $userFields = $this->dataFieldModel->getCmsList($eventID);
        $event = $this->eventModel->getCmsEventByID($eventID);
        if($request->isPost()){
            if($event['enable_track'] == '1'){
                $res = $this->userTableModel->getUsersByTrackTable($eventID,$id,$day);
            }else{
                $res = $this->userTableModel->getUsersByTable($eventID,$id,$day);
            }
            if($res){
                foreach($res as $k => $v){
                    $items = $this->userDataModel->getDataList($v['id']);
                    foreach($userFields as $k1 => $v1){
                        $item = Tools::find_array_item($items,'key',$v1['key']);
                        if(!empty($item)){
                            $v[$v1['key']] = $item['value'];
                        }else{
                            $v[$v1['key']] = '';
                        }
                    }
                    $res[$k] = $v;
                }
            }
            $total = count($res);
            return showMsg(200,'ok',['total'=>$total,'data'=>$res]);
        }else{
            $data = [
                'user_fields'=>$userFields,
                'event_id' => $eventID,
                'table_id' => $id
            ];
            return view('table_view',$data);
        }
    }

    public function visitorCategory(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $day = $request->param('day');
        $event = new Xevents();
        $events = $event->getSimpleEventsList();
        $eventId = null;
        if(!empty($event_id)){
            $eventId = $event_id;
        }else if(!empty($events)){
            $eventId = $events[0]['id'];
        }
        $days = $this->eventModel->getEventDays($eventId);
        if(empty($day)){
            $day = $days[0];
        }
        if ($request->isPost()) {
            return showMsg(1, 'success');
        } else {
            $res = Db::name('xdata_fields')->where('status',1)
                ->where('key','visitor_category')
                ->where('event_id',$eventId)
                ->field('options')
                ->find();
            $articles = [];
            $totalRegister = 0;
            $totalAttendance = 0;
            $totalDropoutRate = 0;
            if(!empty($res)){
                $option = explode("\r\n",$res['options']);
                foreach($option as $v){
                    $userDatas = Db::name('xuser_datas')
                        ->alias('a')
                        ->join('xusers b','a.user_id = b.id')
                        ->where('a.status',1)
                        ->where('a.key','visitor_category')
                        ->where('a.value',$v)
                        ->where('b.event_id',$eventId)
                        ->field('a.user_id')
                        ->select();
                    if(!empty($userDatas)){
                        $ids = [];
                        foreach($userDatas as $v1){
                            $ids[] = $v1['user_id'];
                        }
                        $attendCount = $this->userStatusModel->where('status',1)
                            ->where('user_id','in',$ids)
                            ->where('day',$day)
                            ->where('checkin_status',1)
                            ->where('event_id',$eventId)
                            ->count();
                        $registerCount = count($userDatas);
                        $dropoutRate = $registerCount>0?number_format(($registerCount-$attendCount)*100/$registerCount,
                            2,'.',','):0;
                        $articles[] = [
                            'visitor_category'=>$v,
                            'register_count'=>$registerCount,
                            'attend_count'=>$attendCount,
                            'dropout_rate'=>$dropoutRate
                            ];
                        $totalRegister += $registerCount;
                        $totalAttendance += $attendCount;
                    }else{
                        $articles[] = [
                            'visitor_category'=>$v,
                            'register_count'=>0,
                            'attend_count'=>0,
                            'dropout_rate'=>0
                        ];
                    }
                }
            }
            $totalDropoutRate = $totalRegister>0?number_format(($totalRegister-$totalAttendance)*100/$totalRegister,
                2,'.',','):0;
            $record_num = count($articles);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => 1000,
                'events'=>$events,
                'event_id'=>$eventId,
                'totalRegister'=>$totalRegister,
                'totalAttendance'=>$totalAttendance,
                'totalDropoutRate'=>$totalDropoutRate,
                'days'=>$days,
                'day'=>$day
            ];
            return view('visitor_category', $data);
        }
    }

    public function rsvp(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
//        $startDay = $request->param('start_day');
//        $endDay = $request->param('end_day');
        $zone_id = $request->param('zone_id');
        $zone_name = $request->param('zone_name');
        $event = $this->userEventModel->getActiveEvent();
//        $days = $this->eventModel->getEventDays($event['id']);
//        if(empty($startDay)){
//            $startDay = $days[0];
//        }
//        if(empty($endDay)){
//            $endDay = end($days);
//        }
        if ($request->isPost()) {
            return showMsg(1, 'success');
        } else {
            $items = [];
            $zones = $this->zoneModel->getSimpleList($event['id']);
            if(empty($zone_id)){
                $zone_id = $zones[0]['id'];
                $zone_name = $zones[0]['name'];
            }
            $ids = $this->userTableModel->getUserIdsByZone($event['id'],$zone_id);
            $res = Db::name('xuser_datas')->where('status',1)
                ->where('event_id',$event['id'])
                ->where('user_id','in',$ids)
                ->field('min(create_time) as min_day,max(create_time) as max_day')
                ->find();
            $startDay = isset($res)&&!empty($res['min_day'])?date('Y-m-d',strtotime($res['min_day'])):date('Y-m-d',time());
            $endDay = isset($res)&&!empty($res['max_day'])?date('Y-m-d',strtotime($res['max_day'])):date('Y-m-d',time());
            $cur = $startDay;
            $total = Db::name('xusers')->where('status',1)
                ->where('event_id',$event['id'])
                ->where('id','in',$ids)
                ->count();
            $actionTotal = 0;
            do{
                $totalAccept = Db::name('xuser_datas')->where('status',1)
                    ->where('key','join')
                    ->where('value','1')
                    ->where('event_id',$event['id'])
                    ->where('user_id','in',$ids)
                    ->whereBetweenTime('create_time',$cur)
                    ->count();
                $totalReject = Db::name('xuser_datas')->where('status',1)
                    ->where('key','join')
                    ->where('value','2')
                    ->where('event_id',$event['id'])
                    ->where('user_id','in',$ids)
                    ->whereBetweenTime('create_time',$cur)
                    ->count();
                $actionTotal += $totalAccept+$totalReject;
                $items[] = [
                    'zone'=>$zone_name,
                    'day'=>$cur,
                    'total'=>$total-$actionTotal,
                    'total_accept'=>$totalAccept,
                    'total_reject'=>$totalReject
                ];
                $cur = date('Y-m-d',strtotime('+1 day',strtotime($cur)));
            }while(strtotime($cur) <= strtotime($endDay));

            // total report in this date range
//            $total = Db::name('xusers')->where('status',1)
//                ->where('event_id',$event['id'])
//                ->where('create_time','>= time',date('Y-m-d 00:00:00',strtotime($startDay)))
//                ->where('create_time','<= time',date('Y-m-d 23:59:59',strtotime($endDay)))
//                ->count();
            $totalAccept = Db::name('xuser_datas')->where('status',1)
                ->where('key','join')
                ->where('value','1')
                ->where('event_id',$event['id'])
                ->where('user_id','in',$ids)
                ->where('create_time','>= time',date('Y-m-d 00:00:00',strtotime($startDay)))
                ->where('create_time','<= time',date('Y-m-d 23:59:59',strtotime($endDay)))
                ->count();
            $totalReject = Db::name('xuser_datas')->where('status',1)
                ->where('key','join')
                ->where('value','2')
                ->where('event_id',$event['id'])
                ->where('user_id','in',$ids)
                ->where('create_time','>= time',date('Y-m-d 00:00:00',strtotime($startDay)))
                ->where('create_time','<= time',date('Y-m-d 23:59:59',strtotime($endDay)))
                ->count();
            $data = [
                'articles' => $items,
                'search' => $search,
                'record_num' => 1,
                'page_limit' => 1000,
                'event'=>$event,
                'totalAccept'=>$totalAccept,
                'totalReject'=>$totalReject,
//                'days'=>$days,
//                'min_day'=>$days[0],
//                'max_day'=>end($days),
//                'start_day'=>$startDay,
//                'end_day'=>$endDay,
                'zones'=>$zones,
                'zone_id'=>$zone_id,
                'zone_name'=>$zone_name
            ];
            return view('rsvp', $data);
        }
    }
}
