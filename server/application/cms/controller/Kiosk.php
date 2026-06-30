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
use app\common\lib\Tools;
use app\common\model\Xcompanies;
use app\common\model\Xevents;
use app\common\model\XexhibitorForms;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use app\common\model\XvisitorType;
use think\Request;

/**
 * 用户管理类
 * Class Users
 * @package app\cms\Controller
 */
class Kiosk extends CmsBase
{
    protected $model;
    protected $companyModel;
    protected $vendorModel;
    protected $visitorTypeModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->companyModel = new Xcompanies();
        $this->vendorModel = new Xvendors();
        $this->visitorTypeModel = new XvisitorType();
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
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$event_id);
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
            $users = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
            $record_num = $this->model->getCmsDatasCount($search);
            $data = [
                'articles' => $users,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId
            ];
            return view('index', $data);
        }
    }

    public function preview(Request $request, $id)
    {
        $article = $this->model->getCmsDataByID($id);
        $data =
            [
                'form' => $article
            ];
        return view('preview', $data);
    }
}