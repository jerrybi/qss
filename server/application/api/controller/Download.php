<?php


namespace app\api\controller;

use app\common\lib\MyPdf;
use app\common\model\XdownloadRecords;
use app\common\model\Xusers;
use think\Request;
use think\facade\Env;
use think\Db;
use app\common\lib\Tools;
use PHPExcel;
use PHPExcel_IOFactory;

class Download
{
    protected $userModel;
    protected $downloadRecordModel;
    public function __construct()
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:token,Origin,X-Requested-With,Content-Type,Accept,Authorization,Content-Disposition');
        header('Access-Control-Allow-Methods:POST,GET,PUT,DELETE');
        $this->userModel = new Xusers();
        $this->downloadRecordModel = new XdownloadRecords();
    }

    public function download(Request $request){
        $url = $request->param('url');
        $type = $request->param('type');
        if($type == 'brochure'){
            $merchantId = $request->param('merchantId');
            $visitorId = $request->param('visitorId');
            $brochureId = $request->param('brochureId');
            $date = date('Y-m-d',time());
            $this->downloadRecordModel->save(['merchant_id'=>$merchantId,'visitor_id'=>$visitorId,'brochure_id'=>$brochureId,'date'=>$date]);
        }
        header('Access-Control-Expose-Headers:Content-Disposition');
        $fp = fopen($url,'rb');
        $suffix = substr($url, strrpos($url, '.')+1);
        if($suffix == 'jpg' || $suffix == 'jpeg'){
            header('Content-Type:image/jpeg');
        }else if($suffix == 'png'){
            header('Content-Type:image/png');
        }else if($suffix == 'gif'){
            header('Content-Type:image/gif');
        }else if($suffix == 'pdf'){
            header('Content-Type:application/pdf');
        }else{
            header('Content-Type:application/octet-stream');
        }
        fpassthru($fp);
        fclose($fp);
        exit;
    }

    public function downloadTemplate(Request $request){
        $uid = $request->param('uid');
        $user = $this->userModel->getUserByUid($uid);
        $webPath = $request->domain();
        $html = '<html><head><style>html,body{width: 100%;height: 100%;background-color: yellow;}.container {width: 90%;height: 100%;margin: 0 auto;}.title {position: relative;height:300px;}.logo{width: 200px;height: 200px;margin-top: 100px}.title-right {float: right;text-align: right;position: absolute;left: 200px;right: 0;top: 0;bottom: 0;}.title-line1 {position: relative;font-size: 30px;height: 200px;text-align: right;}.title-line1 div{position: absolute;bottom: 0;right: 0;}.title-line2 {position: relative;font-size: 20px;height: 100px;text-align: right;}.title-line2 div{position: absolute;top: 0;right: 0;}.qr {position: relative;overflow: auto;margin: 20px 0px 40px 0px;}.qr-left {float: left;}.qr-left-line1{margin-bottom: 10px;}.qr-left-line{height: 60px;}.qr-left-line img{width: 40px;height: 40px;}.qr-left-line div{display: inline-block;}.qr-right {float: right;}.qr-right-line1{font-size: 24px;text-align: center;height: 30px;line-height: 30px;font-weight: 500;}.qr-right-line2{font-size: 20px;text-align: center;height: 30px;line-height: 30px;color: red}.qr-right-line3{width: 300px;height: 300px;}.flow-img{width: 100%;}.line {width: 100%;height: 2px;background-color: black;margin-bottom: 40px;margin-top: 40px;}.footer {position: relative;margin-bottom: 20px;overflow: auto;}.footer-left {width: 40%;font-size: 20px;font-weight: 600;float: left;text-align: left;}.footer-right {width: 40%;font-size: 18px;font-weight: 100;float: right;text-align: right;}</style></head><body><div class="container"><div class="title"><img class="logo" src="'.$webPath.'\static\images\logo.png"/><div class="title-right"><div class="title-line1"><div>Scan it &amp; Download</div></div><div class="title-line2"><div>Download your e-Brochure and leave your contact</div></div></div></div><div class="qr"><div class="qr-left"><div class="qr-left-line1">Scan the QRcode to ensure secure and authentic e-Brochures download</div><div class="qr-left-line"><img src="'.$webPath.'\static\images\ios_camera.png" /><div>Apple iOS Devices:<br/>Use the Camera app</div></div><div class="qr-left-line"><img src="'.$webPath.'\static\images\android_camera.png" /><div>Android Devices:<br />Use the Camera app</div></div></div><div class="qr-right"><div class="qr-right-line1">Welcome to:</div><div class="qr-right-line2">'.$user['organisation'].'</div><img class="qr-right-line3" src="'.$user['company_qr'].'" /></div></div><div class="flow"><img class="flow-img" src="'.$webPath.'\static\images\flow.png" /></div><div class="line"></div><div class="footer"><div class="footer-left">Thank you for scanning our QR code.We will be in touch with you real soon</div><div class="footer-right">Your personal data is protected.Only authorised merchants will have access to your limited information.Kindly approach respective merchants if you do not wish to have future follow-ups.</div></div></div></body></html>';
        $filename = "123.pdf";
        $content = MyPdf::createPdf($html,$filename);
        header('Access-Control-Expose-Headers:Content-Disposition');
        header('Content-Type:application/pdf');
        echo $content;
        exit;
    }

    public function downloadPdfZip(Request $request){
        $ids = $request->param('ids');
        $type = $request->param('type');
        $idArr = explode(",",$ids);
        if($type == 'brochure'){
            $merchantId = $request->param('merchantId');
            $visitorId = $request->param('visitorId');
            $date = date('Y-m-d',time());
            $data = [];
            foreach($idArr as $item){
                $data[] = ['merchant_id'=>$merchantId,'visitor_id'=>$visitorId,'brochure_id'=>$item,'date'=>$date];
            }
            $this->downloadRecordModel->saveAll($data);
        }
        $res = Db::name("xbrochures")->where('id','in',$idArr)->select();
        $zipName = date('YmdHis') . rand(1000, 9999).'.zip';
        $path = Env::get('root_path')."public\\temp\\";
        $zip = new \ZipArchive();
        $zip->open($path.$zipName,\ZipArchive::CREATE);
        if(!empty($res)){
            foreach($res as $value){
                $url = $value['url'];
                $filepath = Env::get('root_path').'public'.str_replace('/','\\',Tools::getUri($url));
                $zip->addFile($filepath,$value['name'].'.pdf');
            }
        }
        $zip->close();
        //输出字节流
        $fp = fopen($path.$zipName,'rb');
        header('Access-Control-Expose-Headers:Content-Disposition');
        header('Content-Type:application/zip;name='.$zipName);
        fpassthru($fp);
        fclose($fp);
        unlink($path.$zipName);
        exit;
    }

    public function downloadVisitors(Request $request){
        $merchantId = $request->param('merchantId');
        $ids = $request->param('ids');
        $idArr = explode(',',$ids);
        $data = $this->userModel->where('id','in',$idArr)->select();
        foreach($data as $key=>$value){
            $value['brochure_download_count'] = $this->downloadRecordModel
                ->where('merchant_id',$merchantId)->where('visitor_id',$value['unique_id'])->count();
            $data[$key] = $value;
        }
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'visitors';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellKey = array('A','B','C','D','E','F','G','H','I','J','K','L','M',
            'N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM',
            'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $cellName = array('SN','Unique ID','First Name','Last Name','Organisation','Email','Mobile','Brochures downloaded','Remarks');
        //处理表头
        foreach($cellName as $k=>$v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellKey[$k].$topNumber,$v);//设置表头数据
            $objPHPExcel->getActiveSheet()->freezePane($cellKey[$k].($topNumber+1));//冻结窗口
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getFont()->setBold(true);//设置加粗
            $objPHPExcel->getActiveSheet()->getStyle($cellKey[$k].$topNumber)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        //处理数据
        foreach($data as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($k+1+$topNumber),$k+1);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($k+1+$topNumber),$v['unique_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($k+1+$topNumber),$v['first_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($k+1+$topNumber),$v['last_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($k+1+$topNumber),$v['organisation']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($k+1+$topNumber),$v['email']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($k+1+$topNumber),$v['mobile']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($k+1+$topNumber),$v['brochure_download_count']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($k+1+$topNumber),$v['remarks']);
        }
        //导出excel
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel5');
        $objWriter->save('php://output');
        exit;
    }

}