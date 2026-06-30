<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\model\XaccessGroups;
use app\common\model\XlocationGroups;
use app\common\model\Xevents;
use think\Request;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class LocationGroups extends CmsBase
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XlocationGroups();
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
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search);
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search);
            $record_num = $this->model->getCmsDatasCount($search);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
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
            return view('add',['events'=>$events]);
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
            $opRes = $this->model->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $event = new Xevents();
            $events = $event->getEventsList();
            $comments = [];
            $data =
                [
                    'article' => $article,
                    'comments' => $comments,
                    'events'=>$events
                ];
            return view('edit', $data);
        }
    }

    /**
     * 文章操作日志列表
     * @param $id
     * @return \think\response\View
     */
    public function viewLogs($id){
        $logs = getCmsOpViewLogs($id,'LOCATION_GROUPS');
        return view('view_logs',['logs' => $logs]);
    }
}
