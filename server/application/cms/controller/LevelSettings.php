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
use app\common\model\XdataFields;
use app\common\model\Xevents;
use app\common\model\XlevelSettings;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class LevelSettings extends CmsBase
{
    protected $model;
    protected $dataFieldModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XlevelSettings();
        $this->dataFieldModel = new XdataFields();
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
        if($request->isPost()){
            $articles = $this->model->getCmsDatasForPage($curr_page,$this->page_limit,$search);
            $record_num = $this->model->getCmsDatasCount($search);
            return showMsg(1, 'success', ['total'=>$record_num,'data'=>$articles]);
        }else{
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventId = null;
            if(!empty($event_id)){
                $eventId = $event_id;
            }else if(!empty($events)){
                $eventId = $events[0]['id'];
            }
            $data = [
                'search' => $search,
                'page_limit' => $this->page_limit,
                'permissions'=>$this->getCmsAdminPagePermissions(),
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
            $opRes = $this->model->addCmsData($input);
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