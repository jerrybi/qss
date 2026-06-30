<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\model\Xbrochures;
use app\common\model\XdownloadRecords;
use app\common\model\Xconfigs;
use app\common\model\Xusers;
use app\common\model\Xevents;
use think\Request;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class Configs extends CmsBase
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xconfigs();
    }

    /**
     * 获取文章列表数据
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $res = $this->model->updateCmsData($request->post());
            return showMsg($res['tag'],$res['message']);
        } else {
            $event_id = $request->param('event_id');
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $article = $this->model->getCmsData($eventId);
            $data = [
                'article' => $article,
                'events'=>$events,
                'event_id'=>$eventId
            ];
            return view('index', $data);
        }
    }

    public function getList(Request $request){
        $event_id = $request->param('event_id');
        $list = $this->model->getList($event_id);
        return showMsg(1,'success',$list);
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
            $opRes = $this->model->addData($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $event = new Xevents();
            $events = $event->getEventsList();
            return view('add',['events'=>json_encode($events)]);
        }
    }

    /**
     * 更新文章数据
     * @param Request $request
     * @param $id 文章ID
     * @return \think\response\View|void
     */
    public function edit(Request $request, $id='')
    {
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $data =
                [
                    'article' => $article
                ];
            return view('edit', $data);
        }
    }
}
