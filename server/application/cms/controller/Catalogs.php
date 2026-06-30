<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XdataFields;
use app\common\model\XcatalogAttrs;
use app\common\model\Xcatalogs;
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
class Catalogs extends CmsBase
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
        $this->model = new Xcatalogs();
        $this->catalogAttrModel = new XcatalogAttrs();
        $this->locationModel = new Xlocations();
        $this->boothModel = new XdataFields();
        $this->userModel = new Xusers();
        $this->exhibitorFormModel = new XexhibitorForms();
        $this->formModel = new Xzones();
        $this->formDataModel = new XformDatas();
    }

    private function getTtitles($attrs){
        $titles = [];
        $titles['name'] = $this->getLabelByKey($attrs,'name','Item Code');
        $titles['type'] = $this->getLabelByKey($attrs,'type','Item Type');
        $titles['category'] = $this->getLabelByKey($attrs,'category','Item Category');
        $titles['sub_category'] = $this->getLabelByKey($attrs,'sub_category','Item Sub Category');
        $titles['description'] = $this->getLabelByKey($attrs,'description','Description');
        $titles['advanced_rate'] = $this->getLabelByKey($attrs,'advanced_rate','Advanced Rate');
        $titles['standard_rate'] = $this->getLabelByKey($attrs,'standard_rate','Standard Rate');
        $titles['onsite_rate'] = $this->getLabelByKey($attrs,'onsite_rate','Onsite Rate');
        $titles['logo'] = $this->getLabelByKey($attrs,'logo','Image');
        $titles['length'] = 'L';
        $titles['depth'] = 'D';
        $titles['width'] = 'W';
        $titles['height'] = 'H';
        return $titles;
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
            $list = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$event_id,'Amenity');
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
            $articles = $this->model->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,'Amenity');
            $record_num = $this->model->getCmsDatasCount($search,'Amenity');
            $catalogAttrs = $this->catalogAttrModel->getCmsList($eventId,'Amenity');
            $titles = $this->getTtitles($catalogAttrs);
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'catalogAttrs'=>$catalogAttrs,
                'titles'=>$titles
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
                    'events'=>$events,
                    'type'=>'Amenity'
                ];
            return view('edit', $data);
        }
    }

    public function getcatalogAttrs(Request $request){
        if($request->isPost()){
            $eventId = $request->param('event_id');
            $catalogAttrs = $this->catalogAttrModel->getCmsList($eventId,'Amenity');
            $titles = [];
            if(!empty($catalogAttrs)){
                $titles = $this->getTtitles($catalogAttrs);
            }
            $data = [
                'catalogAttrs'=>$catalogAttrs,
                'titles'=>$titles
            ];
            return showMsg(1,'ok',$data);
        }else{
            return showMsg(0,'sorry,your request is invalid！');
        }
    }

    public function fieldList(Request $request)
    {
        $curr_page = 1;
        $search = '';
        $event_id = $request->param('event_id');
        if ($request->isPost()) {
            $list = $this->model->getCmsDatasForPage($curr_page, 1000, $search,'Amenity');
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
            $record_num = 5;
            $catalogAttrs = $this->catalogAttrModel->getCmsList($eventId,'Amenity');
            $articles = [];
            $articles[] = ['id'=>1,'key'=>'name','name'=>'Item Code','label'=>$this->getLabelByKey($catalogAttrs,'name','')];
            $articles[] = ['id'=>2,'key'=>'type','name'=>'Item Type','label'=>$this->getLabelByKey($catalogAttrs,'type','')];
            $articles[] = ['id'=>3,'key'=>'category','name'=>'Item Category','label'=>$this->getLabelByKey($catalogAttrs,'category','')];
            $articles[] = ['id'=>4,'key'=>'sub_category','name'=>'Item Sub Category','label'=>$this->getLabelByKey($catalogAttrs,'sub_category','')];
            $articles[] = ['id'=>5,'key'=>'description','name'=>'Description','label'=>$this->getLabelByKey($catalogAttrs,'description','')];
            $articles[] = ['id'=>6,'key'=>'advanced_rate','name'=>'Advanced Rate','label'=>$this->getLabelByKey($catalogAttrs,'advanced_rate','')];
            $articles[] = ['id'=>7,'key'=>'standard_rate','name'=>'Standard Rate','label'=>$this->getLabelByKey($catalogAttrs,'standard_rate','')];
            $articles[] = ['id'=>8,'key'=>'onsite_rate','name'=>'Onsite Rate','label'=>$this->getLabelByKey($catalogAttrs,'onsite_rate','')];
            $articles[] = ['id'=>9,'key'=>'logo','name'=>'Image','label'=>$this->getLabelByKey($catalogAttrs,'logo','')];
            $data = [
                'articles' => $articles,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => 1000,
                'events'=>$events,
                'event_id'=>$eventId,
                'catalogAttrs'=>$catalogAttrs
            ];
            return view('fieldList', $data);
        }
    }

    private function getLabelByKey($arr,$key,$default){
        if(empty($arr) || count($arr) == 0) return $default;
        foreach($arr as $value){
            if($value['key'] == $key){
                return !empty($value['label'])?$value['label']:$default;
            }
        }
        return $default;
    }

    public function editField(Request $request, $id='',$event_id='')
    {
        if ($request->isPost()) {
            $event_id = $request->param('event_id');
            $opRes = $this->catalogAttrModel->updateCmsData($request->post(),$id,$event_id);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            $article = $this->catalogAttrModel->getDataByKey($id,$event_id,'Amenity');
            if(empty($article)){
                $name = '';
                if($id == 'name') $name = 'Item Code';
                if($id == 'type') $name = 'Item Type';
                if($id == 'category') $name = 'Item Category';
                if($id == 'sub_category') $name = 'Item Sub Category';
                if($id == 'description') $name = 'Description';
                if($id == 'advanced_rate') $name = 'Advanced Rate';
                if($id == 'standard_rate') $name = 'Standard Rate';
                if($id == 'onsite_rate') $name = 'Onsite Rate';
                if($id == 'logo') $name = 'Image';
                $article = ['key'=>$id,'name'=>$name,'label'=>'','default'=>'','min'=>null,'max'=>null,
                    'options'=>'','description'=>''];
            }else{
                if(!empty($article['options'])){
                    $article['options'] = trim($article['options']);
                }
            }
            $data =
                [
                    'article' => $article,
                    'event_id'=>$event_id,
                    'key'=>$id,
                    'type'=>'Amenity'
                ];
            return view('editField', $data);
        }
    }

    public function getMainCategories(Request $request){
        if ($request->isPost()) {
            $eventId = $request->param('event_id');
            $article = $this->catalogAttrModel->getDataByKey('category',$eventId,'Amenity');
            $options = [];
            if(!empty($article)){
                $str = $article['options'];
                $options = explode("\r\n",$str);
            }
            return showMsg(1,'success',$options);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function getUsedMainCategories(Request $request){
        if ($request->isPost()) {
            $eventId = $request->param('event_id');
            $options = $this->model->getUsedCmsList($eventId,'Amenity');
            return showMsg(1,'success',$options);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function getSubCategories(Request $request){
        if ($request->isPost()) {
            $eventId = $request->param('event_id');
            $category = $request->param('category');
            $options = $this->catalogAttrModel->getSubCategories($eventId,$category,'Amenity');
            return showMsg(1,'success',$options);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }

    public function download(Request $request){
        $eventId = $request->param('event_id');
        $record_num = $this->model->getCmsDatasCount('','Amenity');
        $articles = $this->model->getCmsDatasForPage(1, $record_num, '',$eventId,'Amenity');
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'catalogs';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $catalogAttrs = $this->catalogAttrModel->getCmsList($eventId,'Amenity');
        $cellName = array();
        $cellName[] = $this->getLabelByKey($catalogAttrs,'name','Item Code');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'type','Item Type');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'category','Item Category');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'sub_category','Item Sub Category');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'description','Description');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'advanced_rate','Advanced Rate');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'standard_rate','Standard Rate');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'onsite_rate','Onsite Rate');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'logo','Image');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'length','Length');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'depth','Depth');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'width','Width');
        $cellName[] = $this->getLabelByKey($catalogAttrs,'height','Height');
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
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['type']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),$v['category']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),$v['sub_category']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['description']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['advanced_rate']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['standard_rate']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['have_onsite_rate']?$v['onsite_rate']:'-');
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),$v['logo']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($k+1+$topNumber),$v['length']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($k+1+$topNumber),$v['depth']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($k+1+$topNumber),$v['width']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.($k+1+$topNumber),$v['height']);
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
                $itemCode = (string)($currentSheet->getCell('A'.$rowIndex)->getValue());
                $description = (string)($currentSheet->getCell('E'.$rowIndex)->getValue());
                if(!empty($itemCode)){
                    $logo = (string)($currentSheet->getCell('I'.$rowIndex)->getValue());
                    $logoPath = $dstDirectory.$logo;
                    if(!empty($logo) && file_exists('temp'.DIRECTORY_SEPARATOR.$logo)){
                        rename('temp'.DIRECTORY_SEPARATOR.$logo,$logoPath);
                        $logoUrl = $request->domain().'/'.str_replace(DIRECTORY_SEPARATOR,"/",$logoPath);
                    }else{
                        $logoUrl = '';
                    }
                    $onsiteRate = (string)($currentSheet->getCell('H'.$rowIndex)->getValue());
                    $haveOnsiteRate = 1;
                    if($onsiteRate == "-"){
                        $haveOnsiteRate = 0;
                        $onsiteRate = 0.0;
                    }
                    $data[] = ['name'=>$itemCode,
                        'type'=>(string)($currentSheet->getCell('B'.$rowIndex)->getValue()),
                        'category'=>(string)($currentSheet->getCell('C'.$rowIndex)->getValue()),
                        'sub_category'=>(string)($currentSheet->getCell('D'.$rowIndex)->getValue()),
                        'description'=>$description,
                        'advanced_rate'=>(string)($currentSheet->getCell('F'.$rowIndex)->getValue()),
                        'standard_rate'=>(string)($currentSheet->getCell('G'.$rowIndex)->getValue()),
                        'onsite_rate'=>$onsiteRate,
                        'length'=>(string)($currentSheet->getCell('J'.$rowIndex)->getValue()),
                        'depth'=>(string)($currentSheet->getCell('K'.$rowIndex)->getValue()),
                        'width'=>(string)($currentSheet->getCell('L'.$rowIndex)->getValue()),
                        'height'=>(string)($currentSheet->getCell('M'.$rowIndex)->getValue()),
                        'logo'=>$logoUrl,
                        'have_onsite_rate'=>$haveOnsiteRate,
                        'event_id'=>$eventId,
                        'status'=>1,
                        'create_time'=>date('Y-m-d H:i:s',time()),
                        'update_time'=>date('Y-m-d H:i:s',time())];
                }
            }
            if(count($data) > 0){
                foreach($data as $k=>$v){
                    $item = Db::name('xcatalogs')
                        ->where(['event_id'=>$v['event_id'],'type'=>$v['type'],'name'=>$v['name'],'category'=>$v['category'],'sub_category'=>$v['sub_category']])
                        ->find();
                    if(!empty($item)){
                        Db::name('xcatalogs')->where('id',$item['id'])
                            ->update(['description'=>$v['description'],'advanced_rate'=>$v['advanced_rate'],
                                'standard_rate'=>$v['standard_rate'],'logo'=>$v['logo']
                                ,'length'=>$v['length'],'depth'=>$v['depth'],'width'=>$v['width'],'height'=>$v['height']
                                ,'have_onsite_rate'=>$v['have_onsite_rate'],'onsite_rate'=>$v['onsite_rate']
                                ,'update_time'=>date('Y-m-d H:i:s',time())]);
                    }else{
                        Db::name('xcatalogs')->insert($v);
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

    public function viewForms(Request $request){
        $curr_page = $request->param('curr_page', 1);
        $search = $request->param('str_search',null);
        $event_id = $request->param('event_id');
        $id = $request->param('catalog_id');
        if ($request->isPost()) {
            $list = $this->exhibitorFormModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$event_id,$id);
            foreach($list as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $list[$key] = $item;
            }
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
            $list = $this->exhibitorFormModel->getCmsDatasForPage($curr_page, $this->page_limit, $search,$eventId,$id);
            foreach($list as $key=>$item){
                $item['status_name'] = $this->exhibitorFormModel->getStatusName($item['status']);
                $list[$key] = $item;
            }
            $record_num = $this->exhibitorFormModel->getCmsDatasCount($search,$event_id,$id);
            $data = [
                'articles' => $list,
                'search' => $search,
                'record_num' => $record_num,
                'page_limit' => $this->page_limit,
                'events'=>$events,
                'event_id'=>$eventId,
                'catalog_id'=>$id
            ];
            return view('view_forms', $data);
        }
    }

    public function viewForm(Request $request){
        $formId = $request->param('form_id');
        $catalogId = $request->param('catalog_id');
        $article = $this->formModel->getCmsDataByID($formId);
        $formData = $this->formDataModel->getCmsData($article['event_id'],$catalogId,$formId);
        $data = [
            'article' => $article,
            'formData'=>$formData
        ];
        return view('view_form', $data);
    }
}
