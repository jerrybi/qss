<?php
namespace app\index\controller;

use app\common\controller\UserBase;
use app\common\lib\Email;
use app\common\lib\FabricJs;
use app\common\lib\IAuth;
use app\common\lib\QRCode;
use app\common\lib\Tools;
use app\common\model\Xannouncements;
use app\common\model\Xarticles;
use app\common\model\XboothAttrs;
use app\common\model\XcardTemplates;
use app\common\model\XdataFields;
use app\common\model\Xcompanies;
use app\common\model\XcompanyAttrs;
use app\common\model\XedmTemplates;
use app\common\model\Xevents;
use app\common\model\XexhibitorForms;
use app\common\model\Xconfigs;
use app\common\model\XformDatas;
use app\common\model\Xtables;
use app\common\model\XuserDatas;
use app\common\model\XuserTables;
use app\common\model\Xzones;
use app\common\model\Xnotices;
use app\common\model\Xusers;
use think\facade\Env;
use think\Request;
use think\Db;
use PHPExcel;
use PHPExcel_IOFactory;

class Index extends UserBase
{
    private $model;
    private $eventModel;
    private $zoneModel;
    private $tableModel;
    private $userDataModel;
    private $userTableModel;
    private $configModel;
    private $dataFieldModel;
    protected $eventId;
    protected $navHome;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xusers();
        $this->eventModel = new Xevents();
        $this->zoneModel = new Xzones();
        $this->tableModel = new Xtables();
        $this->userDataModel = new XuserDatas();
        $this->userTableModel = new XuserTables();
        $this->configModel = new Xconfigs();
        $this->dataFieldModel = new XdataFields();
    }

    public function index(Request $request){
//        $data = IAuth::decodeEs256("eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE2ODQyOTU3ODYsInR5cGUiOiJjcCIsInVpZCI6IjcwODc2NTExNDQ0MTA0MCIsImFzYyI6eyJmcCI6IjY4MTM2OTciLCJscCI6IjgxMDkwODAwMjk5NzEyMDIifSwidHhwZiI6eyJmcHBheSI6dHJ1ZX0sIm1icnMiOnsibHBtciI6WyI4MTA4MDAiLCI4MTA5MDgiXX19.Q0fX2xfXP4TzdgyENyOQvP7vJMowlMe0GjGuu9rfSu-Hu-wqBLdNQn_z8gdJ17Xm-OJxq2oyEstDwK4zezpnAA");
//        $v1 = urlencode(IAuth::encrypt('v1'));
//        $v2 = urlencode(IAuth::encrypt('v2'));
//        $v3 = urlencode(IAuth::encrypt('v3'));
//        $v4 = urlencode(IAuth::encrypt('v4'));
//        $v5 = urlencode(IAuth::encrypt('v5'));
//        $v6 = urlencode(IAuth::encrypt('v6'));
//        $v7 = urlencode(IAuth::encrypt('v7'));
//        $v8 = urlencode(IAuth::encrypt('v8'));
//        $v9 = urlencode(IAuth::encrypt('v9'));
//          $v1004 = urlencode(IAuth::encrypt('222'));
//        $data = IAuth::decrypt(urldecode("v6%2FEakL9wZXNoi8RewxpZg%3D%3D"));
        return view('index');
    }

    public function detail(Request $request,$id){
        return view('detail');
    }

    public function download(Request $request){

    }

    public function openForm(Request $request){
        return redirect('/index/form?id=2g2glPPkOGivSwIH4c7tcw%3D%3D');
    }

    public function v1(Request $request){
        return view('v1');
    }

    public function v2(Request $request){
        return view('v2');
    }

    public function v3(Request $request){
        return view('v3');
    }

    public function v4(Request $request){
        return view('v4');
    }

    public function v5(Request $request){
        return view('v5');
    }

    public function v6(Request $request){
        return view('v6');
    }

    public function v7(Request $request){
        return view('v7');
    }

    public function v8(Request $request){
        return view('v8');
    }

    public function v9(Request $request){
        return view('v9');
    }

    public function v11(Request $request){
        return view('v11');
    }

    public function v12(Request $request){
        return view('v12');
    }

    public function v13(Request $request){
        return view('v13');
    }

    public function v14(Request $request){
        return view('v14');
    }

    public function v15(Request $request){
        return view('v15');
    }

    public function v16(Request $request){
        return view('v16');
    }

    public function v17(Request $request){
        return view('v17');
    }

    public function v18(Request $request){
        return view('v18');
    }

    public function v19(Request $request){
        return view('v19');
    }

    public function v20(Request $request){
        return view('v20');
    }

    public function confirmation(Request $request){
        return view('confirmation1');
    }

    public function reminderToRegister(Request $request){
        return view('reminder_to_register');
    }

    public function reminderToAttend(Request $request){
        return view('reminder_to_attend');
    }

    public function internalGuest(Request $request){
        return view('internal_guest');
    }

    public function singleCard(Request $request){
        return view('single_card');
    }

    public function doubleCard(Request $request){
        return view('double_card');
    }

    public function privacy(Request $request){
        return view('privacy');
    }

    private function isLogoTwoLine($data){
        if($data == 'zone1' ||$data == 'v1'){
            return true;
        }else{
            return false;
        }
    }

    public function receipt(Request $request){
        $p = $request->param('id');
        if(!empty($p)){
            $id = IAuth::decrypt($p);
            if(empty($id)){
                echo 'invalid request!';
                exit;
            }
            $status = Db::name('xedm_tasks')
                ->where('id',$id)
                ->value('status');
            if($status == 1){
                Db::name('xedm_tasks')
                    ->where('id',$id)
                    ->update([
                        'status'=>4,
                        'update_time'=>date('Y-m-d H:i:s',time())
                    ]);
            }
            echo 'ok';
            exit;
        }
        echo 'invalid request!';
        exit;
    }

    public function join(Request $request){
        $p = $request->param('id');
        if(!empty($p)){
            $event = $this->eventModel->getFirstEvent();
            $eventId = isset($event)?$event['id']:'';
            $closeEvent = $this->configModel->isCloseEvent($eventId);
            if($closeEvent){
                return redirect('/index/close?id='.urlencode($p));
            }
            $id = IAuth::decrypt($p);
            if(empty($id)){
                echo 'invalid request!';
                exit;
            }
            if($id == 'v1' || $id == 'v2' || $id == 'v3' || $id == 'v4' || $id == 'v5' || $id == 'v6' || $id == 'v7'
                || $id == 'v8' || $id == 'v9'){
//                $twoLine = $this->isLogoTwoLine($id);
                return redirect('/index/form?id='.urlencode($p));
            }
            $supportEmail = $this->configModel->getSupportEmail($eventId);
            $userId = $this->userDataModel->getUserIdBySerialNumber($eventId,$id);
            if($userId){
                $userDatas = Db::name('xuser_datas')->where('event_id',$eventId)
                    ->where('status',1)
                    ->where('user_id',$userId)
                    ->where('key','join')
                    ->find();
                $zone = $this->userTableModel->getUserZone($eventId,$userId);
                $twoLine = $this->isLogoTwoLine($zone);
                if(!empty($userDatas) && ($userDatas['value'] == '1' || $userDatas['value'] == '2' || $userDatas['value'] == '9')){
                    // already registered
                    return view('support',['email'=>$supportEmail,'twoLine'=>$twoLine]);
                }else{
                    return view('join',['id'=>urlencode($p),'twoLine'=>$twoLine]);
                }
            }else{
                echo 'invalid user!';
                exit;
            }
        }
        echo 'invalid request!';
        exit;
    }

    public function accept(Request $request){
        $id = $request->param('id');
        $id = IAuth::decrypt($id);
        if(empty($id)){
            echo 'invalid request!';
            exit;
        }
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        $userId = $this->userDataModel->getUserIdBySerialNumber($eventId,$id);
        if(empty($userId)){
            echo 'invalid user!';
            exit;
        }
        $userDatas = Db::name('xuser_datas')->where('event_id',$eventId)
            ->where('status',1)
            ->where('user_id',$userId)
            ->where('key','in',['first_name','last_name','self_register'])
            ->select();
        $firstName = '';
        $lastName = '';
        $selfRegister = '';
        foreach($userDatas as $v){
            if($v['key'] == 'first_name'){
                $firstName = $v['value'];
            }
            if($v['key'] == 'last_name'){
                $lastName = $v['value'];
            }
            if($v['key'] == 'self_register'){
                $selfRegister = $v['value'];
            }
        }
        $zone = $this->userTableModel->getUserZone($eventId,$userId);
        $twoLine = $this->isLogoTwoLine($zone);
//        if($selfRegister == 0){
//            $templateName = 'confirmation'.substr($zone,strlen('zone'));
//            $templateId = Db::name('xedm_templates')->where('event_id',$eventId)
//                ->where('status',1)
//                ->where('name',$templateName)
//                ->value('id');
//            Db::name('xedm_tasks')->insert([
//                'user_id'=>$userId,
//                'template_id'=>$templateId,
//                'event_id'=>$eventId,
//                'status'=>9,
//                'create_time'=>date('Y-m-d H:i:s',time()),
//                'update_time'=>date('Y-m-d H:i:s',time())
//            ]);
//        }
        $supportEmail = $this->configModel->getSupportEmail($eventId);
        return view('accept',['firstName'=>$firstName,'lastName'=>$lastName
            ,'email'=>$supportEmail,'twoLine'=>$twoLine,'selfRegister'=>$selfRegister]);
    }

    public function reject(Request $request){
        $id = $request->param('id');
        $id = IAuth::decrypt($id);
        if(empty($id)){
            echo 'invalid request!';
            exit;
        }
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        $supportEmail = $this->configModel->getSupportEmail($eventId);
        if($id == 'v1' || $id == 'v2' || $id == 'v3' || $id == 'v4' || $id == 'v5' || $id == 'v6' || $id == 'v7'
            || $id == 'v8' || $id == 'v9'){
            $twoLine = $this->isLogoTwoLine($id);
            return view('reject',['fullName'=>'','email'=>$supportEmail,'twoLine'=>$twoLine]);
        }
        $userId = $this->userDataModel->getUserIdBySerialNumber($eventId,$id);
        if($userId){
            $userDatas = Db::name('xuser_datas')->where('event_id',$eventId)
                ->where('status',1)
                ->where('user_id',$userId)
                ->where('key','in',['first_name','last_name'])
                ->select();
            $firstName = '';
            $lastName = '';
            foreach($userDatas as $v){
                if($v['key'] == 'first_name'){
                    $firstName = $v['value'];
                }
                if($v['key'] == 'last_name'){
                    $lastName = $v['value'];
                }
            }
            $zone = $this->userTableModel->getUserZone($eventId,$userId);
            $twoLine = $this->isLogoTwoLine($zone);
            $templateName = 'reject'.substr($zone,strlen('zone'));
            $templateId = Db::name('xedm_templates')->where('event_id',$eventId)
                ->where('status',1)
                ->where('name',$templateName)
                ->value('id');
            Db::name('xedm_tasks')->insert([
                'user_id'=>$userId,
                'template_id'=>$templateId,
                'event_id'=>$eventId,
                'status'=>9,
                'create_time'=>date('Y-m-d H:i:s',time()),
                'update_time'=>date('Y-m-d H:i:s',time())
            ]);
            Db::name('xuser_datas')->where('event_id',$eventId)
                ->where('status',1)
                ->where('user_id',$userId)
                ->where('key','=','join')
                ->update([
                    'value'=>2,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
            return view('reject',['firstName'=>$firstName,'lastName'=>$lastName
                ,'email'=>$supportEmail,'twoLine'=>$twoLine]);
        }else{
            return view('reject',['fullName'=>'','email'=>$supportEmail,'twoLine'=>true]);
        }
    }

    public function support(Request $request){
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        $supportEmail = $this->configModel->getSupportEmail($eventId);
        return view('support',['email'=>$supportEmail,'twoLine'=>true]);
    }

    public function close(Request $request){
        $id = $request->param('id');
        $id = IAuth::decrypt($id);
        if(empty($id)){
            echo 'invalid request!';
            exit;
        }
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        $supportEmail = $this->configModel->getSupportEmail($eventId);
        return view('close',['email'=>$supportEmail]);
    }

    public function form(Request $request){
//        if(!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == "" ) //判断规则
//        {
//            header("HTTP/1.1 404 Not Found"); //返回404状态码
//            header("Status: 404 Not Found"); //返回404状态码
//            exit;
//        }
        $p = $request->param('id');
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        if ($request->isPost()) {
            $id = IAuth::decrypt(urldecode($p));
            if(empty($id)){
                return showMsg(500,'invalid request!');
            }
            $input = $request->post();
            $selfRegister = 0;
            $existSerialNumber = false;
            $preEmail = '';
            if($id == 'v1' || $id == 'v2' || $id == 'v3' || $id == 'v4' || $id == 'v5' || $id == 'v6' || $id == 'v7'
                || $id == 'v8' || $id == 'v9'){
                $index = substr($id,1);
                $zone = 'zone'.$index;
                $table = 'table'.$index;
                $zoneId = $this->zoneModel->getIdByName($eventId,$zone);
                $tableId = $this->tableModel->getIdByName($eventId,$table,$zoneId);
                $addData = [
                    'unique_id' => Tools::create_guid(),
                    'type' => 0,
                    'zone_id' => $zoneId,
                    'table_id' => $tableId,
                    'event_id' => $eventId,
                    'checkin_status' => 0,
                    'status'=>1,
                    'create_time'=>date('Y-m-d H:i:s',time())
                ];
                $opRes = $this->model->addData($addData);
                $userId = $opRes['id'];
                $this->userTableModel->insert([
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'zone_id'=>$zoneId,
                    'table_id'=>$tableId,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ]);
                $selfRegister = 1;
            }else{
                $userId = $this->userDataModel->getUserIdBySerialNumber($eventId,$id);
                if(empty($userId)){
                    return showMsg(500,'invalid user!');
                }
                $existSerialNumber = true;
                $selfRegister = isset($input['self_register'])?$input['self_register']:0;
                //获取注册时的email
                $preEmail = Db::name('xuser_datas')->where('event_id',$eventId)
                    ->where('user_id',$userId)
                    ->where('key','=','email')
                    ->where('status',1)
                    ->value('value');
                // 删除数据重新添加
                Db::name('xuser_datas')->where('event_id',$eventId)
                    ->where('user_id',$userId)
//                    ->where('key','not in',['serial_number','visitor_category','remarks'])
                    ->where('key','in',['first_name','last_name','company','email',
                        'position','gender','attend_neom','attend_reception','salutation',
                        'country_code','contact_number','name_of_ea','email_address_of_ea',
                        'attend_leadership','sectors','gdpr_clause','dietary_restrictions',
                        'join','self_register','reg_time',''])
                    ->delete();
            }
            $email = isset($input['email'])?$input['email']:'';
            $firstName = isset($input['first_name'])?$input['first_name']:'';
            $lastName = isset($input['last_name'])?$input['last_name']:'';
            $gender = isset($input['gender'])?$input['gender']:'';
            $attendNeom = isset($input['attend_neom'])?$input['attend_neom']:'';
            $attendReception = isset($input['attend_reception'])?$input['attend_reception']:'';
            $salutation = isset($input['salutation'])?$input['salutation']:'';
            $countryPrefix = isset($input['country'])?$input['country']:'';
            $contactNumber = isset($input['contact_number'])?$input['contact_number']:'';
            $eaName = isset($input['name_of_ea'])?$input['name_of_ea']:'';
            $eaEmail = isset($input['email_address_of_ea'])?$input['email_address_of_ea']:'';
            $attendLeadership = isset($input['attend_leadership'])?$input['attend_leadership']:'';
            $sectors = isset($input['sectors'])?$input['sectors']:'';
            if($attendLeadership == 'No'){
                $sectors = '';
            }
            $gdprClause = isset($input['gdpr_clause'])?$input['gdpr_clause']:'';
            $dietaryRestrictions = isset($input['dietary_restrictions'])?$input['dietary_restrictions']:'';
            $other = isset($input['dietary_restrictions_other'])?$input['dietary_restrictions_other']:'';
            if($dietaryRestrictions == 'Others'){
                $dietaryRestrictions .= '-'.$other;
            }
            $join = 1;
            if($selfRegister == 1){
                $join = 9;
            }else if(!empty($preEmail) && $preEmail != $email){
                $join = 13;
            }
            $userDatas = [
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'first_name',
                    'value'=>$firstName,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'last_name',
                    'value'=>$lastName,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'company',
                    'value'=>isset($input['company'])?$input['company']:'',
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'email',
                    'value'=>$email,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'position',
                    'value'=>isset($input['position'])?$input['position']:'',
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'gender',
                    'value'=>$gender,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'attend_neom',
                    'value'=>$attendNeom,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'attend_reception',
                    'value'=>$attendReception,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'salutation',
                    'value'=>$salutation,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'country_code',
                    'value'=>$countryPrefix,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'contact_number',
                    'value'=>$contactNumber,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'name_of_ea',
                    'value'=>$eaName,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'email_address_of_ea',
                    'value'=>$eaEmail,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'attend_leadership',
                    'value'=>$attendLeadership,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'sectors',
                    'value'=>$sectors,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'gdpr_clause',
                    'value'=>$gdprClause,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],[
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'dietary_restrictions',
                    'value'=>$dietaryRestrictions,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'join',
                    'value'=>$join,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'self_register',
                    'value'=>$selfRegister,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ],
                [
                    'id'=>Tools::create_guid(),
                    'event_id' => $eventId,
                    'user_id'=>$userId,
                    'key'=>'reg_time',
                    'value'=>date('Y-m-d H:i:s',time()),
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'status'=>1
                ]
            ];
            $this->userDataModel->insertAll($userDatas);
            if($selfRegister){
                if(!$existSerialNumber){
                    $serialNumber = 'RG'.$userId;
//                $salutation = isset($input['salutation'])?$input['salutation']:'';
                    $this->userDataModel->insertAll(
                        [
//                        [
//                            'id'=>Tools::create_guid(),
//                            'event_id' => $eventId,
//                            'user_id'=>$userId,
//                            'key'=>'salutation',
//                            'value'=>$salutation,
//                            'create_time'=>date('Y-m-d H:i:s',time()),
//                            'status'=>1
//                        ],
//                        [
//                            'id'=>Tools::create_guid(),
//                            'event_id' => $eventId,
//                            'user_id'=>$userId,
//                            'key'=>'full_name',
//                            'value'=>$firstName.' '.$lastName,
//                            'create_time'=>date('Y-m-d H:i:s',time()),
//                            'status'=>1
//                        ],
                            [
                                'id'=>Tools::create_guid(),
                                'event_id' => $eventId,
                                'user_id'=>$userId,
                                'key'=>'serial_number',
                                'value'=>$serialNumber,
                                'create_time'=>date('Y-m-d H:i:s',time()),
                                'status'=>1
                            ]
                        ]
                    );
                }else{
                    $serialNumber = Db::name('xuser_datas')->where('event_id',$eventId)
                        ->where('user_id',$userId)
                        ->where('key','=','serial_number')
                        ->value('value');
                }
                $p = urlencode(IAuth::encrypt($serialNumber));
            }
            return showMsg(200,'register successfully!',['id'=>$p,'join'=>$join]);
        } else {
            $id = IAuth::decrypt($p);
            if(empty($id)){
                echo 'invalid request!';
                exit;
            }
            $salutation = '';
            $fullName = '';
            $firstName = '';
            $lastName = '';
            $company = '';
            $email = '';
            $position = '';
            $reception = '0';
            $f1 = '0';
            $join = 0;
            $selfRegister = 0;
            $zone = '';
            $twoLine = true;
            if($id == 'v1' || $id == 'v2' || $id == 'v3' || $id == 'v4' || $id == 'v5' || $id == 'v6' || $id == 'v7'
                || $id == 'v8' || $id == 'v9'){
                $userId = 0;
                $selfRegister = 1;
                $twoLine = $this->isLogoTwoLine($id);
                if($id == 'v1'){
                    $zone = 'zone1';
                }else if($id == 'v2'){
                    $zone = 'zone2';
                }else if($id == 'v3'){
                    $zone = 'zone3';
                }else if($id == 'v4'){
                    $zone = 'zone4';
                }else if($id == 'v5'){
                    $zone = 'zone5';
                }else if($id == 'v6'){
                    $zone = 'zone6';
                }else if($id == 'v7'){
                    $zone = 'zone7';
                }else if($id == 'v8'){
                    $zone = 'zone8';
                }else if($id == 'v9'){
                    $zone = 'zone9';
                }
            }else{
                $userId = $this->userDataModel->getUserIdBySerialNumber($eventId,$id);
                if(empty($userId)){
                    echo 'invalid user!';
                    exit;
                }
                $userDatas = Db::name('xuser_datas')->where('event_id',$eventId)
                    ->where('status',1)
                    ->where('user_id',$userId)
                    ->select();
                if($userDatas){
                    foreach($userDatas as $v){
                        if($v['key'] == 'salutation'){
                            $salutation = $v['value'];
                        }
                        if($v['key'] == 'first_name'){
                            $firstName = $v['value'];
                        }
                        if($v['key'] == 'last_name'){
                            $lastName = $v['value'];
                        }
                        if($v['key'] == 'full_name'){
                            $fullName = $v['value'];
                        }
                        if($v['key'] == 'company'){
                            $company = $v['value'];
                        }
                        if($v['key'] == 'email'){
                            $email = $v['value'];
                        }
                        if($v['key'] == 'position'){
                            $position = $v['value'];
                        }
                        if($v['key'] == 'join'){
                            $join = $v['value'];
                        }
                        if($v['key'] == 'self_register'){
                            $selfRegister = $v['value'];
                        }
                    }
                }
                $zone = $this->userTableModel->getUserZone($eventId,$userId);
                $twoLine = $this->isLogoTwoLine($zone);
            }
            $supportEmail = $this->configModel->getSupportEmail($eventId);
            if($join == '1' || $join == '2') {
                return view('support',['email'=>$supportEmail,'twoLine'=>$twoLine]);
            }else{
                $showReception = 0;
                $editReception = 0;
                $showF1 = 0;
                $editF1 = 0;
                $f1options = [];
                $f1Title = '';
                if($zone == 'zone1'){
                    $showReception = 1;
                    $editReception = 1;
                }else if($zone == 'zone2'){
//                    $f1options = [
//                        ['name'=>'f1[1]','value'=>1,'title'=>'15 September 2023, Friday'],
//                        ['name'=>'f1[2]','value'=>2,'title'=>'16 September 2023, Saturday'],
//                        ['name'=>'f1[3]','value'=>3,'title'=>'17 September 2023, Sunday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 15-17 September 2023, Friday - Sunday?';
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone3'){
//                    $f1options = [
//                        ['name'=>'f1[1]','value'=>1,'title'=>'15 September 2023, Friday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 15 September 2023, Friday? ';
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone4'){
//                    $f1options = [
//                        ['name'=>'f1[2]','value'=>2,'title'=>'16 September 2023, Saturday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 16 September 2023, Saturday?';
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone5'){
//                    $f1options = [
//                        ['name'=>'f1[3]','value'=>3,'title'=>'17 September 2023, Sunday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 17 September 2023, Sunday? ';
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone6'){
//                    $f1options = [
//                        ['name'=>'f1[1]','value'=>1,'title'=>'15 September 2023, Friday'],
//                        ['name'=>'f1[2]','value'=>2,'title'=>'16 September 2023, Saturday'],
//                        ['name'=>'f1[3]','value'=>3,'title'=>'17 September 2023, Sunday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 15-17 September 2023, Friday - Sunday?';
                    $showReception = 1;
                    $editReception = 1;
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone7'){
//                    $f1options = [
//                        ['name'=>'f1[1]','value'=>1,'title'=>'15 September 2023, Friday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 15 September 2023, Friday? ';
                    $showReception = 1;
                    $editReception = 1;
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone8'){
//                    $f1options = [
//                        ['name'=>'f1[2]','value'=>2,'title'=>'16 September 2023, Saturday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 16 September 2023, Saturday?';
                    $showReception = 1;
                    $editReception = 1;
                    $showF1 = 1;
                    $editF1 = 1;
                }else if($zone == 'zone9'){
//                    $f1options = [
//                        ['name'=>'f1[3]','value'=>3,'title'=>'17 September 2023, Sunday']
//                    ];
                    $f1Title = 'Will you be attending Neom Sky Suite at Formula 1 Singapore Grand Prix on 17 September 2023, Sunday? ';
                    $showReception = 1;
                    $editReception = 1;
                    $showF1 = 1;
                    $editF1 = 1;
                }
                $fieldList = $this->dataFieldModel->getCmsList($eventId);
                $dataFields = [];
                foreach($fieldList as $v){
                    if(!empty($v['options'])){
                        $v['options'] = explode("\r\n",$v['options']);
                    }
                    $dataFields[$v['key']] = $v;
                }
                return view('form',[
                    'selfRegister'=>$selfRegister,
                    'full_name'=>$fullName,
                    'first_name'=>$firstName,
                    'last_name'=>$lastName,
                    'salutation'=>$salutation,
                    'company'=>$company,
                    'email'=>$email,
                    'position'=>$position,
//                    'reception'=>$reception,
//                    'f1'=>$f1,
//                    'showReception'=>$showReception,
//                    'editReception'=>$editReception,
//                    'showF1'=>$showF1,
//                    'editF1'=>$editF1,
//                    'f1options'=>$f1options,
//                    'f1Title'=>$f1Title,
                    'twoLine'=>$twoLine,
                    'dataFields'=>$dataFields,
                    'id'=>urlencode($p)
                ]);
            }
        }
    }

    public function search(Request $request){
        return view('search');
    }

    public function result(Request $request){
        $name = $request->param('name');
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        $res = Db::name('xuser_datas')->where('event_id',$eventId)
            ->where('status',1)
            ->where(function ($query) use ($name){
                $query->whereOr([
                    [
                        ['key','=','first_name'],
                        ['value','like','%'.$name.'%']
                    ],
                    [
                        ['key','=','last_name'],
                        ['value','like','%'.$name.'%']
                    ]
                ]);
            })
            ->field('distinct(user_id)')
            ->select();
        $list = [];
        if($res){
            foreach($res as $v){
                $items = Db::name('xuser_datas')
                    ->where('status',1)
                    ->where('user_id',$v['user_id'])
                    ->select();
                if($items){
                    $firstName = '';
                    $lastName = '';
                    $email = '';
                    foreach($items as $item){
                        if($item['key'] == 'first_name'){
                            $firstName = $item['value'];
                        }
                        if($item['key'] == 'last_name'){
                            $lastName = $item['value'];
                        }
                        if($item['key'] == 'email'){
                            $email = $item['value'];
                        }
                    }
                    $list[] = [
                        'user_id'=>$v['user_id'],
                        'first_name'=>$firstName,
                        'last_name'=>$lastName,
                        'email'=>$email
                        ];
                }
            }
        }
        return view('result',['list'=>$list]);
    }

    public function user(Request $request){
        $id = $request->param('id');
        $webPath = $request->domain();
        $innerUrl = $webPath.'/index/makeCard/'.$id;
        $filename = QRCode::create_qrcode($innerUrl,null,$id."_card.jpg");
        $url = $webPath."/qrcode/".$filename;
        return view('user',['url'=>$url]);
    }

    private function getKeyByName($name) {
        $key = strtolower($name);
        $key = str_replace(" ","_",$key);
        return $key;
    }

    public function makeCard(Request $request,$id){
        $event = $this->eventModel->getFirstEvent();
        $eventId = isset($event)?$event['id']:'';
        $userDataModel = new XuserDatas();
        $userDatas = $userDataModel->getDataList($id);
        $userData = [];
        if($userDatas){
            foreach($userDatas as $v){
                $userData[$v['key']] = $v['value'];
            }
        }
        $visitorCategory = isset($userData['visitor_category'])?$userData['visitor_category']:'';
        if(empty($visitorCategory)){
            echo 'Visitor category is empty!';
            exit;
        }
        $cardTemplateModel = new XcardTemplates();
        $cardTemplate = $cardTemplateModel->getDataByType($eventId,$visitorCategory);
        if(empty($cardTemplate)){
            echo 'Card template is empty!';
            exit;
        }
        $imgPath1 = '';
        $imgPath2 = '';
        $fabricJs = new FabricJs();
        $content1 = !empty($cardTemplate['content1'])?$cardTemplate['content1']:'';
        if(!empty($content1)){
            $matches = [];
            preg_match_all('/\[\%(.*)\%\]/U',$content1,$matches);
            $customizeKeywords = $matches[1];
            foreach($customizeKeywords as $v){
                $key = $this->getKeyByName($v);
                $value = isset($userData[$key])?$userData[$key]:'';
                $content1 = str_replace('[%'.$v.'%]',$value,$content1);
            }
            $imgPath1 = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$id."_print_front.png";
            $res = $fabricJs->toPNG($content1,$cardTemplate['bg_width']*28.346,$cardTemplate['bg_height']*28.346,$imgPath1);
        }
        $content2 = !empty($cardTemplate['content2'])?$cardTemplate['content2']:'';
        if(!empty($content2)){
            $matches = [];
            preg_match_all('/\[\%(.*)\%\]/U',$content2,$matches);
            $customizeKeywords = $matches[1];
            foreach($customizeKeywords as $v){
                $key = $this->getKeyByName($v);
                $value = isset($userData[$key])?$userData[$key]:'';
                $content2 = str_replace('[%'.$v.'%]',$value,$content2);
            }
            $imgPath2 = Env::get('root_path').'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$id."_print_back.png";
            $res = $fabricJs->toPNG($content2,$cardTemplate['bg_width']*28.346,$cardTemplate['bg_height']*28.346,$imgPath2);
        }
        if($cardTemplate['double_side'] == '1'){
            if(file_exists($imgPath1) && file_exists($imgPath2)){
                $imgWidth = $cardTemplate['bg_width']*28.346;
                $imgHeight = $cardTemplate['bg_height']*28.346;
                $mapImage = imagecreatetruecolor($imgWidth,$imgHeight*2);
                $bgColor = imagecolorallocate($mapImage,50,40,0);
                imagefill($mapImage,0,0,$bgColor);
                $img1 = imagecreatefrompng($imgPath1);
                $img2 = imagecreatefrompng($imgPath2);
                imagecopy($mapImage,$img1,0,0,0,0,$imgWidth,$imgHeight);
                imagedestroy($img1);
                imagecopy($mapImage,$img2,0,$imgHeight,0,0,$imgWidth,$imgHeight);
                imagedestroy($img2);
                header("Content-type:image/png");
                header("Content-Disposition:attachment;filename=DigitalCard.png");
                imagepng($mapImage);
            }else{
                echo "digital card output error!";
                exit;
            }
        }else{
            if(file_exists($imgPath1)){
                ob_end_clean();
                $fp = fopen($imgPath1,'rb');
                header('Content-type:image/png;charset=utf-8;name=DigitalCard.png');
                header("Content-Disposition:attachment;filename=DigitalCard.png");
                fpassthru($fp);
                fclose($fp);
            }else{
                echo "digital card output error!";
                exit;
            }
        }
    }
}
