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
use app\common\model\Xevents;
use app\common\model\Xtables;
use app\common\model\XuserTables;
use app\common\model\Xzones;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class UserTable extends CmsBase
{
    protected $model;
    protected $zoneModel;
    protected $tableModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XuserTables();
        $this->zoneModel = new Xzones();
        $this->tableModel = new Xtables();
    }

    public function index(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $userID = $request->param('id');
        $eventID = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($userID,$curr_page, $this->page_limit, $search);
            return showMsg(1, 'success', $list);
        } else {
            $articles = $this->model->getCmsDatasForPage($userID,$curr_page, $this->page_limit, $search);
            $record_num = $this->model->getCmsDatasCount($userID,$search);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'user_id' => $userID,
                'event_id' => $eventID
            ];
            return view('index', $data);
        }
    }

    public function add(Request $request)
    {
        $userID = $request->param('user_id');
        $eventID = $request->param('event_id');
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->addData($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $zones = $this->zoneModel->getSimpleList($eventID);
            $tables = !empty($zones)?$this->tableModel->getSimpleList($zones[0]['id']):[];
            return view('add',['user_id'=>$userID,'event_id'=>$eventID,'zones'=>$zones,'tables'=>$tables]);
        }
    }

    public function edit(Request $request, $id)
    {
        $userID = $request->param('user_id');
        $eventID = $request->param('event_id');
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $zones = $this->zoneModel->getSimpleList($eventID);
            $tables = $this->tableModel->getSimpleList($article['zone_id']);
            $data =
                [
                    'article' => $article,
                    'zones'=>$zones,
                    'tables'=>$tables,
                    'user_id'=>$userID,
                    'event_id'=>$eventID
                ];
            return view('edit', $data);
        }
    }

    public function change(Request $request)
    {
        $id = $request->param('id');
        $eventID = $request->param('event_id');
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->changeData($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $zones = $this->zoneModel->getSimpleList($eventID);
            $tables = !empty($zones)?$this->tableModel->getSimpleList($zones[0]['id']):[];
            return view('change',['ids'=>$id,'event_id'=>$eventID,'zones'=>$zones,'tables'=>$tables]);
        }
    }
}