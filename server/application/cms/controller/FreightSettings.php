<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XfreightSettings;
use app\common\model\Xevents;
use think\Request;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class FreightSettings extends CmsBase
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XfreightSettings();
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
}
