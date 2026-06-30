<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XdataFields;
use app\common\model\XcatalogAttrs;
use app\common\model\XvisitorType;
use app\common\model\Xevents;
use app\common\model\XexhibitorForms;
use app\common\model\XformDatas;
use app\common\model\Xzones;
use app\common\model\Xlocations;
use app\common\model\Xusers;
use think\Db;
use think\Exception;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;
use think\facade\env;

/**
 * 文章管理类
 * Class Article
 * @package app\cms\Controller
 */
class VisitorType extends CmsBase
{
    protected $model;
    protected $catalogAttrModel;
    protected $locationModel;
    protected $boothModel;
    protected $userModel;
    protected $exhibitorFormModel;
    protected $formModel;
    protected $formDataModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new XvisitorType();
        $this->userModel = new Xusers();
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
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId);
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
            return view('add',['events'=>$events,'type'=>'Amenity']);
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

    public function download(Request $request){
        $eventId = $request->param('event_id');
        $record_num = $this->model->getCmsDatasCount('');
        $articles = $this->model->getCmsDatasForPage(1, $record_num, '',$eventId);
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'catalogs';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $cellName = array();
        $cellName[] = 'name';
        $cellName[] = 'code';
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        //处理数据
        foreach($articles as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$v['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['code']);
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function upload(Request $request){
        set_time_limit(0);
        $eventId = $request->param('event_id');
        $fileUrl = $request->param('file_url');
        $filePath = Env::get('root_path').'public/'.$fileUrl;
        $filePath = str_replace('/',DIRECTORY_SEPARATOR,$filePath);
        //必须为zip文件
        if(!Tools::endWith($filePath,".zip")){
            return showMsg(0,'you can only upload zip file!');
        }
        //解压缩zip文件
        $zip = new \ZipArchive();
        $res = $zip->open($filePath);
        if ($res === TRUE) {
            //解压缩到test文件夹
            $zip->extractTo('temp');
            $zip->close();
        } else {
            return showMsg(0,'unzip failed!');
        }
        //获取zip的文件夹路径
        $excelPath = 'temp'.DIRECTORY_SEPARATOR.'template.xlsx';
        //读取excel内容
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($excelPath)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($excelPath)){
                return showMsg(0,'can not read file!');
            }
        }
        $PHPExcel = $PHPReader->load($excelPath);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();
        $data = array();
        $dstDirectory = 'upload'.DIRECTORY_SEPARATOR.date('Ymd',time()).DIRECTORY_SEPARATOR;
        if(!is_dir($dstDirectory)){
            mkdir($dstDirectory);
        }
        try{
            for($rowIndex = 2;$rowIndex<=$allRow;$rowIndex++){
                $name = (string)($currentSheet->getCell('A'.$rowIndex)->getValue());
                $code = (string)($currentSheet->getCell('B'.$rowIndex)->getValue());
                $data[] = ['name'=>$name,
                    'code'=>$code,
                    'event_id'=>$eventId,
                    'status'=>1,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())];
            }
            if(count($data) > 0){
                foreach($data as $k=>$v){
                    $item = Db::name('xvisitor_type')
                        ->where(['event_id'=>$v['event_id'],'code'=>$v['code']])
                        ->find();
                    if(!empty($item)){
                        Db::name('xvisitor_type')->where('id',$item['id'])
                            ->update(['name'=>$v['name'],'code'=>$v['code']
                                ,'update_time'=>date('Y-m-d H:i:s',time())]);
                    }else{
                        Db::name('xvisitor_type')->insert($v);
                    }
                }
                //删除zip文件和excel文件
                unlink($filePath);
                unlink($excelPath);
                return showMsg(1,'upload catalogs successfully!');
            }
        }catch (Exception $e){
            unlink($filePath);
            unlink($excelPath);
            return showMsg(0,$e->getMessage());
        }
        return showMsg(0,'file is empty!');
    }

    public function getList(Request $request){
        $eventID = $request->param('event_id');
        $list = $this->model->getCmsList($eventID);
        return showMsg(1, 'success', $list);
    }
}
