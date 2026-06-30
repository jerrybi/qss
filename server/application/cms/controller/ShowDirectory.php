<?php


namespace app\cms\controller;


use app\common\controller\CmsBase;
use app\common\lib\Tools;
use app\common\model\XcompanyAttrs;
use app\common\model\Xevents;
use app\common\model\Xconfigs;
use PHPExcel;
use PHPExcel_IOFactory;
use think\Db;
use think\Request;

class ShowDirectory extends CmsBase
{
    private $model;
    private $exhibitorModel;
    private $companyAttrModel;
    protected $eventId;
    protected $navHome;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xevents();
        $this->exhibitorModel = new Xconfigs();
        $this->companyAttrModel = new XcompanyAttrs();
    }

    public function index(Request $request){
        if($request->isPost()){
            $industry = $request->param('industry');
            $product = $request->param('product');
            $search = $request->param('search');
            $curPage = $request->param('cur');
            $eventId = $request->param('event_id');
            $where = [];
            $where[] = ['a.status','=','1'];
            $where[] = ['a.event_id','=',$eventId];
            if(!empty($industry)){
                $industryArr = explode("\n",$industry);
                foreach ($industryArr as $key=>$item){
                    $industryArr[$key] = '%'.$item.'%';
                }
                $where[] = ['a.industry','like',$industryArr,'AND'];
            }
            if(!empty($product)){
                $productArr = explode("\n",$product);
                foreach ($productArr as $key=>$item){
                    $productArr[$key] = '%'.$item.'%';
                }
                $where[] = ['a.product','like',$productArr,'AND'];
            }
            if(!empty($search)){
                $where[] = ['a.name|a.sub_company_name','like','%'.$search.'%'];
            }
            $limit = 10;
            if(empty($curPage)){
                $curPage = 1;
            }
            $total = Db::name('xcompanies a')->where($where)->count();
            $res = Db::name('xcompanies a')
                ->join('xbooths b','a.booth_id=b.id')
                ->where($where)->page($curPage)->limit($limit)
                ->field('a.*,b.name as booth,case when a.sub_company_name is null then a.name else a.sub_company_name end as mix_name')
                ->order('mix_name asc')
                ->select();
            return showMsg(1,'ok',['total'=>$total,'curPage'=>$curPage,'limit'=>$limit,'data'=>$res]);
        }else{
            $events = $this->model->getEventsList();
            $eventId = $events[0]['id'];
            $profile = $this->companyAttrModel->getDataByKey('product',$eventId);
            $str = $this->companyAttrModel->getDataByKey('industry',$eventId);
            $industry = explode("\r\n",$str['options']);
            $curEvent = $this->model->getCmsEventByID($eventId);
            $data = [
                'profile' => $profile['options'],
                'industry'=>$industry,
                'events'=>$events,
                'event_id'=>$eventId
            ];
            return view('index', $data);
        }
    }

//    public function detail(Request $request,$id){
//        $eventCode = $request->param('event');
//        $this->loadSharedData($eventCode);
//        $res = Db::name('xcompanies a')
//            ->join('xbooths b','a.booth_id=b.id')
//            ->where(['a.id'=>$id])
//            ->field('a.*,b.name as booth')
//            ->find();
//        $data = [
//            'exhibitor'=>$this->navHome,
//            'article'=>$res
//        ];
//        return view('detail',$data);
//    }

    public function download(Request $request){
        $industry = $request->param('industry');
        $product = $request->param('product');
        $search = $request->param('search');
        $eventId = $request->param('event_id');
        $where = [];
        $where[] = ['a.status','=','1'];
        $where[] = ['a.event_id','=',$eventId];
        if(!empty($industry)){
            $industryArr = explode("\n",$industry);
            foreach ($industryArr as $key=>$item){
                $industryArr[$key] = '%'.$item.'%';
            }
            $where[] = ['a.industry','like',$industryArr,'AND'];
        }
        if(!empty($product)){
            $productArr = explode("\n",$product);
            foreach ($productArr as $key=>$item){
                $productArr[$key] = '%'.$item.'%';
            }
            $where[] = ['a.product','like',$productArr,'AND'];
        }
        if(!empty($search)){
            $where[] = ['a.name|a.sub_company_name','like','%'.$search.'%'];
        }
        $res = Db::name('xcompanies a')
            ->join('xbooths b','a.booth_id=b.id')
            ->where($where)
            ->field('a.*,b.name as booth,b.location,b.badge')
            ->select();
        $objPHPExcel = new PHPExcel();
        $topNumber = 3;
        $xlsTitle = "showdirectory";
        $fileName = $xlsTitle.date('_YmdHis');
        $cellName = array();
        $cellName[] = ['','','No'];
        $cellName[] = ['','','Company Name'];
        $cellName[] = ['','','Type'];
        $cellName[] = ['','','Booth No'];
        $cellName[] = ['','','Location'];
        $cellName[] = ['','','Badge'];
        $cellName[] = ['','','Country of Origin'];
        $cellName[] = ['','','Profile'];
        $cellName[] = ['','','Logo'];
        $cellName[] = ['','','Email'];
        $cellName[] = ['','','Address Line1'];
        $cellName[] = ['','','Address Line2'];
        $cellName[] = ['','','Postal Code'];
        $cellName[] = ['','','Country'];
        $cellName[] = ['','','Phone'];
        $cellName[] = ['','','Fax'];
        $cellName[] = ['','','Website'];
        $companyAttrs = Db::name('xcompany_attrs')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->select();
        $industry = $this->getOptionsByKey($companyAttrs,'industry');
        $industries = explode("\r\n",$industry);
        foreach ($industries as $v){
            $cellName[] = ['','',$v];
        }
        $product = $this->getOptionsByKey($companyAttrs,'product');
        $productJson = json_decode($product,true);
        $products = [];
        if(!empty($productJson) && !empty($productJson[0]) && !empty($productJson[0]['children'])){
            foreach($productJson[0]['children'] as $k=>$v){
                //first level
                $title0 = $v['title'];
                if(!empty($v['children'])){
                    foreach($v['children'] as $k1=>$v1){
                        //2nd level
                        $title1 = $v1['title'];
                        if(empty($v1['children'])){
                            $cellName[] = [$title0,'',$title1];
                            $products[] = $title1;
                        }else{
                            foreach($v1['children'] as $k2=>$v2){
                                //3rd level
                                $cellName[] = [$title0,$title1,$v2['title']];
                                $products[] = $v2['title'];
                            }
                        }
                    }
                }else{
                    $cellName[] = [$title0,'',''];
                    $products[] = $title0;
                }
            }
        }
        $cellName[] = ['','','Submission Date'];
        $cellName[] = ['','','Modified Date'];
        $cellKey1 = Tools::getExcelColumnTitles(count($cellName));
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle("Show Directory");
        foreach($cellName as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'1',$v[0]);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'2',$v[1]);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'2')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'3',$v[2]);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'3')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'3')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        }
//        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
//        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
//        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        foreach ($cellKey1 as $v){
            $objPHPExcel->getActiveSheet()->getColumnDimension($v)->setWidth(30);
        }
        foreach ($res as $key=>$value){
            $index = 0;
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$key+1);
            $companyName = !empty($value['sub_company_name'])?$value['sub_company_name']:$value['name'];
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['type']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['booth']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['location']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['badge']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['origin_country']);
            if(!empty($value['sub_company_name'])) {
                $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$index].($key+1+$topNumber))->getAlignment()->setWrapText(true);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_profile']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_logo']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['sub_email']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_address_line1']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_address_line2']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_postal']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['sub_country']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['sub_phone_country_code'] . $value['sub_phone_area_code'] . $value['sub_phone_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_fax_country_code'].$value['sub_fax_area_code'].$value['sub_fax_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['sub_website']);
                //industry
                foreach($industries as $k=>$v){
                    if(strstr($value['sub_industry'],$v)){
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
                    }else{
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),'');
                    }
                }
                //product
                foreach($products as $k=>$v){
                    if(strstr($value['sub_product'],$v)){
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
                    }else{
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),'');
                    }
                }
            }else{
                $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$index].($key+1+$topNumber))->getAlignment()->setWrapText(true);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['profile']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['logo']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['email']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['address_line1']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['address_line2']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['postal']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['country']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++] . ($key + 1 + $topNumber), $value['phone_country_code'] . $value['phone_area_code'] . $value['phone_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['fax_country_code'].$value['fax_area_code'].$value['fax_number']);
                $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['website']);
                //industry
                foreach($industries as $k=>$v){
                    if(strstr($value['industry'],$v)){
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
                    }else{
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),'');
                    }
                }
                //product
                foreach($products as $k=>$v){
                    if(strstr($value['product'],$v)){
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$companyName);
                    }else{
                        $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),'');
                    }
                }
            }
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['create_time']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['update_time']);
        }
        //export excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.urlencode($xlsTitle).'.xls"');
        header("Content-Disposition:attachment;filename=".urlencode($fileName).".xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    private function getOptionsByKey($arr,$key){
        if(empty($arr) || count($arr) == 0) return '';
        foreach($arr as $value){
            if($value['key'] == $key){
                return $value['options'];
            }
        }
        return '';
    }
}