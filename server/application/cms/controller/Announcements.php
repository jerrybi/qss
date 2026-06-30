<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\model\Xannouncements;
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
class Announcements extends CmsBase
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xannouncements();
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
        $event_id = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search);
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
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            foreach ($articles as $key=>$item){
                $item['post_date'] = date('d/M/Y',strtotime($item['update_time']));
                $articles[$key] = $item;
            }
            $record_num = $this->model->getCmsDatasCount($search);
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
            $events = $event->getSimpleEventsList();
            return view('add',['events'=>$events]);
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
}
