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
use app\common\model\Xexhibitors;
use app\common\model\Xforms;
use think\facade\Env;
use think\Request;
use PHPExcel;
use PHPExcel_IOFactory;

class Exhibitors extends CmsBase
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xexhibitors();
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
            $record_num = $this->model->getCmsDatasCount($search,$eventId);

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

    /**
     * 添加文章
     * @param Request $request
     * @return \think\response\View|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->param();
            $loginName = isset($input['login_name'])?$input['login_name']:'';
            $firstName = isset($input['first_name'])?$input['first_name']:'';
            $lastName = isset($input['last_name'])?$input['last_name']:'';
            $company = isset($input['company'])?$input['company']:'';
            $phoneCountryCode = isset($input['phone_country_code'])?$input['phone_country_code']:'';
            $phoneAreaCode = isset($input['phone_area_code'])?$input['phone_area_code']:'';
            $phoneNumber = isset($input['phone_number'])?$input['phone_number']:'';
            $eventId = isset($input['event_id'])?$input['event_id']:'';
            $email = $request->param('email');
            $addData = [
                'unique_id' => Tools::create_guid(),
                'login_name' => $loginName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company' => $company,
                'phone_country_code' => $phoneCountryCode,
                'phone_area_code' => $phoneAreaCode,
                'phone_number' => $phoneNumber,
                'email' => $email,
                'event_id' => $eventId,
                'status'=>1,
                'create_time'=>date('Y-m-d H:i:s',time())
            ];
            $opRes = $this->model->addData($addData);
            //设置用户名密码
            if($opRes['tag']){
                $this->model->updatePassword($opRes['id'],'create_account');
            }else{
                return showMsg(500, $opRes['message']);
            }
            return showMsg(200, 'ok');
        } else {
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $input = $request->param();
            $eventId = isset($input['event_id'])?$input['event_id']:'';
            $eventInfo = Tools::find_array_item($events,'id',$eventId);
            return view('add',['events'=>$events,
                'event_id'=>$eventId,
                'event_name'=>!empty($eventInfo)?$eventInfo['name']:''
                ]);
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
            $comments = [];
            $event = new Xevents();
            $events = $event->getSimpleEventsList();
            $eventInfo = Tools::find_array_item($events,'id',$article['event_id']);
            $data =
                [
                    'article' => $article,
                    'comments' => $comments,
                    'events'=>$events,
                    'event_name'=>!empty($eventInfo)?$eventInfo['name']:''
                ];
            return view('edit', $data);
        }
    }

    /**
     * ajax 更新用户状态
     * @param Request $request
     */
    public function ajaxUpdateUserStatus(Request $request){
        if ($request->isPost()) {
            $user_id = $request->post('user_id', 0);
            $user_status = $request->post('user_status',0);
            $opRes = $this->model->updateUserStatus($user_id, $user_status);
            return showMsg($opRes['status'], $opRes['message']);
        } else {
            return showMsg(0, 'sorry，invalid request!');
        }
    }

    public function resetPassword(Request $request,$id){
        $user = $this->model->getCmsDataByID($id);
        $rs = $this->model->updatePassword($id,'reset_password');
        if (!$rs['status']) {
            return showMsg(400,$rs['msg']);
        }
        return showMsg(200,'password has been reset');
    }

    public function download(Request $request){
        ini_set('max_execution_time', '600');
        ini_set('memory_limit',-1); //没有内存限制
        $eventId = $request->param('event_id');
        $record_num = $this->model->getCmsDatasCount('');
        $articles = $this->model->getCmsDatasForPage(1, $record_num, '',$eventId);
        $objPHPExcel = new PHPExcel();
        $topNumber = 1;
        $xlsTitle = 'exhibitors';
        $fileName = $xlsTitle.date('_YmdHis');
        $cellName = array();
        $cellName[] = 'No';
        $cellName[] = 'Login Name';
        $cellName[] = 'First Name';
        $cellName[] = 'Last Name';
        $cellName[] = 'Company';
        $cellName[] = 'Phone Country Code';
        $cellName[] = 'Phone Area Code';
        $cellName[] = 'Phone Number';
        $cellName[] = 'Email';
        $cellName[] = 'Event';
        $cellKey1 = Tools::getExcelColumnTitles(count($cellName));
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle("Exhibitors");
        foreach($cellName as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$k].'1',$v);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle($cellKey1[$k].'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        foreach ($cellKey1 as $v){
            $objPHPExcel->getActiveSheet()->getColumnDimension($v)->setWidth(30);
        }
        foreach ($articles as $key=>$value){
            $index = 0;
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$key+1);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['login_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['first_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['last_name']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['company']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['phone_country_code']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['phone_area_code']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['phone_number']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['email']);
            $objPHPExcel->getActiveSheet()->setCellValue($cellKey1[$index++].($key+1+$topNumber),$value['event_name']);
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
        $eventId = $request->param('event_id');
        $fileUrl = $request->param('file_url');
        $filePath = Env::get('root_path').'/public/'.$fileUrl;
        //读取excel内容
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!$PHPReader->canRead($filePath)){
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if(!$PHPReader->canRead($filePath)){
                showMsg(0,'can not read file!');
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $currentSheet = $PHPExcel->getSheet(0);
        $allColumn = $currentSheet->getHighestColumn();
        $allRow = $currentSheet->getHighestRow();
        $data = array();
        for($rowIndex = 2;$rowIndex<=$allRow;$rowIndex++){
            $loginName = (string)($currentSheet->getCell('B'.$rowIndex)->getValue());
            $firstName = (string)($currentSheet->getCell('C'.$rowIndex)->getValue());
            $lastName = (string)($currentSheet->getCell('D'.$rowIndex)->getValue());
            $company = (string)($currentSheet->getCell('E'.$rowIndex)->getValue());
            $phoneCountryCode = (string)($currentSheet->getCell('F'.$rowIndex)->getValue());
            $phoneAreaCode = (string)($currentSheet->getCell('G'.$rowIndex)->getValue());
            $phoneNumber = (string)($currentSheet->getCell('H'.$rowIndex)->getValue());
            $email = (string)($currentSheet->getCell('I'.$rowIndex)->getValue());
            // 按照login_name去重
            $res = $this->model->where('login_name',$loginName)
                ->where('event_id',$eventId)
                ->find();
            if(empty($res)){
                $userId = $this->model->insertGetId([
                    'unique_id'=>Tools::create_guid(),
                    'login_name'=>$loginName,
                    'first_name'=>$firstName,
                    'last_name'=>$lastName,
                    'company'=>$company,
                    'phone_country_code'=>$phoneCountryCode,
                    'phone_area_code'=>$phoneAreaCode,
                    'phone_number'=>$phoneNumber,
                    'email'=>$email,
                    'event_id'=>$eventId,
                    'status'=>1,
                    'create_time'=>date('Y-m-d H:i:s',time())
                ]);
                // 创建密码
                if($userId){
                    $this->model->updatePassword($userId,'create_account');
                }
            }else{
                $this->model->where('id',$res['id'])
                    ->update([
                        'first_name'=>$firstName,
                        'last_name'=>$lastName,
                        'company'=>$company,
                        'phone_country_code'=>$phoneCountryCode,
                        'phone_area_code'=>$phoneAreaCode,
                        'phone_number'=>$phoneNumber,
                        'email'=>$email,
                        'update_time'=>date('Y-m-d H:i:s',time())]);
            }
        }
        return showMsg(1,'upload exhibitors successfully!');
    }
}