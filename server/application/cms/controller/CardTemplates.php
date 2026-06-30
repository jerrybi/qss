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
use app\common\model\Xcatalogs;
use app\common\model\Xcompanies;
use app\common\model\XdataFields;
use app\common\model\XDynamicForm;
use app\common\model\XcardTemplates;
use app\common\model\Xevents;
use app\common\model\XformAttrs;
use app\common\model\XformDatas;
use app\common\model\XuserEvents;
use app\common\model\Xzones;
use app\common\model\Xusers;
use app\common\model\Xvendors;
use app\common\model\XvisitorType;
use FormDesign\Formdesign;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

class CardTemplates extends CmsBase
{
    protected $model;
    protected $userEventModel;
    protected $dataFieldModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XcardTemplates();
        $this->userEventModel = new XuserEvents();
        $this->dataFieldModel = new XdataFields();
    }

    public function index(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$event_id);
            return showMsg(1, 'success', $list);
        } else {
            $event = $this->userEventModel->getActiveEvent();
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$event['id']);
            $record_num = $this->model->getCmsDatasCount($search,$event['id']);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'event'=>$event
            ];
            return view('index', $data);
        }
    }

    public function add(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->addData($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $event = $this->userEventModel->getActiveEvent();
            $userField = $this->dataFieldModel->getCmsDataByKey($event['id'],'visitor_category');
            if($userField){
                $types = explode("\r\n",$userField['options']);
            }else{
                $types = [];
            }
            return view('add',['event'=>$event,'types'=>$types]);
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->isPost()) {
            $opRes = $this->model->updateCmsData($request->post(),$id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->model->getCmsDataByID($id);
            $event = $this->userEventModel->getActiveEvent();
            $userField = $this->dataFieldModel->getCmsDataByKey($article['event_id'],'visitor_category');
            if($userField){
                $types = explode("\r\n",$userField['options']);
            }else{
                $types = [];
            }
            $data =
                [
                    'article' => $article,
                    'event'=>$event,
                    'types'=>$types
                ];
            return view('edit', $data);
        }
    }
}