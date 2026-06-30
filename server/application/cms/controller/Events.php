<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\IAuth;
use app\common\model\XdataFields;
use app\common\model\XedmTemplates;
use app\common\model\XeventAccounts;
use app\common\model\Xevents;
use app\common\model\XmailSettings;
use app\common\model\Xtables;
use app\common\model\XuserEvents;
use app\common\model\XvisitorRules;
use app\common\model\Xzones;
use think\Request;
use think\Db;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class Events extends CmsBase
{
    protected $model;
//    protected $visitorRuleModel;
    protected $eventAccountModel;
    protected $userEventModel;
    protected $dataFieldModel;
    protected $zoneModel;
    protected $tableModel;
    protected $mailSettingModel;
    protected $edmTemplateModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xevents();
//        $this->visitorRuleModel = new XvisitorRules();
        $this->eventAccountModel = new XeventAccounts();
        $this->userEventModel = new XuserEvents();
        $this->dataFieldModel = new XdataFields();
        $this->zoneModel = new Xzones();
        $this->tableModel = new Xtables();
        $this->mailSettingModel = new XmailSettings();
        $this->edmTemplateModel = new XedmTemplates();
    }

    /**
     * 获取文章列表数据
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request)
    {
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search');
        $userId = IAuth::getAdminIDCurrLogged();
        if ($request->isPost()) {
            $list = $this->model->getCmsEventsForPage($curr_page, $this->page_limit, $search);
            $activeEventId = $this->userEventModel->getActiveEventId($userId);
            foreach($list as $k => $v){
                $v['isActive'] = $v['id']==$activeEventId?true:false;
                $list[$k] = $v;
            }
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsEventsForPage($curr_page, $this->page_limit, $search);
            $record_num = $this->model->getCmsEventsCount($search);
            $activeEventId = $this->userEventModel->getActiveEventId($userId);
            foreach($articles as $k => $v){
                $v['isActive'] = $v['id']==$activeEventId?true:false;
                $articles[$k] = $v;
            }
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
            ];
            return view('index', $data);
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
            $opRes = $this->model->addEvent($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            return view('add');
        }
    }
    
    /**
     * 更新文章数据
     * @param Request $request
     * @param $id 文章ID
     * @return \think\response\View|void
     */
    public function edit(Request $request, $id)
    {
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsEventData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsEventByID($id);
            $comments = [];
            $data =
                [
                    'article' => $article,
                    'comments' => $comments,
                ];
            return view('edit', $data);
        }
    }

//    public function rule(Request $request, $id)
//    {
//        if ($request->isPost()) {
//            $opRes = $this->visitorRuleModel->updateCmsData($request->post(),$id);
//            return showMsg($opRes['tag'], $opRes['message']);
//        } else {
//            $article = $this->visitorRuleModel->getCmsDataByID($id);
//            if(empty($article)){
//                $res = $this->model->getCmsEventByID($id);
//                $eventName = $res['name'];
//            }else{
//                $eventName = $article['event_name'];
//            }
//            $data =
//                [
//                    'article' => $article,
//                    'eventId' => $id,
//                    'eventName' => $eventName
//                ];
//            return view('rule', $data);
//        }
//    }

    public function bindAdmin(Request $request, $id)
    {
        if ($request->isPost()) {
            $opRes = $this->eventAccountModel->updateCmsEventData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $res = $this->model->getCmsEventByID($id);
            $eventAccounts = $this->eventAccountModel->getAssignAccounts($id);
            $data =
                [
                    'article' => $res,
                    'eventAccounts'=>$eventAccounts
                ];
            return view('bindAdmin', $data);
        }
    }

    public function active(Request $request, $id)
    {
        $userId = IAuth::getAdminIDCurrLogged();
        $opRes = $this->userEventModel->setActiveEventId($userId,$id);
        return showMsg($opRes['tag'], $opRes['message']);
    }

    public function duplicate(Request $request, $id)
    {
        // 复制event
        $eventId = $this->model->duplicateEvent($id);
        // 复制data field
        $this->dataFieldModel->duplicate($id,$eventId);
        // 复制zone
        $this->zoneModel->duplicate($id,$eventId);
        $zone = $this->zoneModel->getFirstZone($eventId);
        $zoneId = !empty($zone)?$zone['id'] : 0;
        // 复制table
        $this->tableModel->duplicate($id,$eventId,$zoneId);
        // 复制mail setting
        $this->mailSettingModel->duplicate($id,$eventId);
        // 复制edm template
        $this->edmTemplateModel->duplicate($id,$eventId);
        return showMsg(200, 'success');
    }

    public function getData(Request $request){
        $event_id = $request->param('event_id');
        $data = $this->model->getCmsEventByID($event_id);
        return showMsg(1,'success',$data);
    }

    /**
     * 文章操作日志列表
     * @param $id
     * @return \think\response\View
     */
    public function viewLogs($id){
        $logs = getCmsOpViewLogs($id,'EVENT');
        return view('view_logs',['logs' => $logs]);
    }

    public function getDays(Request $request){
        $event_id = $request->param('event_id');
        $data = $this->model->getEventDays($event_id);
        return showMsg(1,'success',$data);
    }
}
