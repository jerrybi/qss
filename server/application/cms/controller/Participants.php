<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\model\Xbrochures;
use app\common\model\XdownloadRecords;
use app\common\model\Xusers;
use app\common\model\Xevents;
use think\Request;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class Participants extends CmsBase
{
    protected $model;
    protected $brochureModel;
    protected $downloadRecordModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->brochureModel = new Xbrochures();
        $this->downloadRecordModel = new XdownloadRecords();
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
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,1);
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,1);
            $record_num = $this->model->getCmsDatasCount($search);
            foreach($articles as $key=>$item){
                $item['brochure_download_num'] = $this->downloadRecordModel->where('visitor_id',$item['unique_id'])->count();
                $articles[$key] = $item;
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
    public function edit(Request $request, $id)
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

    /**
     * 文章操作日志列表
     * @param $id
     * @return \think\response\View
     */
    public function viewLogs($id){
        $logs = getCmsOpViewLogs($id,'TRACK');
        return view('view_logs',['logs' => $logs]);
    }
    public function viewBrochures($id){
        $uid = $this->model->getUidById($id);
        $articles = $this->downloadRecordModel->field('brochure_id,count(*) as count')
            ->group('brochure_id')->where('visitor_id',$uid)->select();
        foreach($articles as $key=>$value){
            $data = $this->brochureModel->where('id',$value['brochure_id'])->field('name,id')->find();
            $value['name'] = $data['name'];
            $value['id'] = $data['id'];
            $articles[$key] = $value;
        }
        $record_num = count($articles);
        $data = [
            'articles'=>$articles,
            'record_num'=>$record_num,
            'page_limit'=>$this->page_limit
        ];
        return view('view_brochures',$data);
    }
}
