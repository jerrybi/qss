<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\lib\Email;
use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\lib\QRCode;
use app\common\validate\Xuser;
use think\Db;

class Xusers extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    public function __construct($data = [])
    {
        parent::__construct($data);
    }
    /**
     * 分页获取用户数据
     * @param int $curr_page
     * @param int $page_limit
     * @param null $search
     * @param null $user_type
     * @return array
     */
    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1, $search = null,$eventId = null,$ids = null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $model = $this
            ->alias('a')
            ->field("a.*,e.name as event_name,e.enable_track,b.user_name as op_user,c.name as zone,d.name as table_no")
            ->join('xadmins b','a.op_user_id=b.id','left')
            ->join('xevents e','e.id = a.event_id')
            ->join('xzones c','c.id = a.zone_id','left')
            ->join('xtables d','d.id = a.table_id','left')
            ->where($condition);
        if(!is_null($ids)){
            $model = $model->where('a.id','in',$ids);
        }
        $res =  $model->order(['a.id' => 'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    /**
     * 获取用户数量
     * @param null $search
     * @param null $user_type
     * @return float|string
     */
    public function getCmsDatasCount($search = null,$eventId = null,$ids = null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $model = $this
            ->alias('a')
            ->field("a.*")
            ->where($condition);
        if(!is_null($ids)){
            $model = $model->where('a.id','in',$ids);
        }
        return $model->count();
    }

    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xusers')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 200, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'EXHIBITOR',$id,'remove exhibitor');
        } else {
            $saveData = [
                'type' => isset($input['type'])?$input['type']:'',
                'zone_id' => isset($input['zone_id'])?$input['zone_id']:'',
                'table_id' => isset($input['table_id'])?$input['table_id']:'',
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $saveTag = $this
                ->save($saveData,['id'=>$id]);
            if ($saveTag) {
                insertCmsOpLogs($saveTag,'User',$id,'User update');
            }
            $validateRes['tag'] = $saveTag ? 200 : 500;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }

    public function addData($addData)
    {
        $tag = $this->insertGetId($addData);
        if ($tag) {
            $lastId = $this->getLastInsID();
            insertCmsOpLogs($tag,'EXHIBITOR',$lastId,'add exhibitor');
            $validateRes['id'] = $lastId;
        }
        $validateRes['tag'] = $tag;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function getCmsDataByID($id = 0){
        $res = $this
            ->alias('a')
            ->field("a.id,a.unique_id,a.login_name,a.status,a.event_id,
            a.type,a.zone_id,a.table_id,a.checkin_status,a.checkin_time,
            e.name as event_name,e.enable_track,b.user_name as op_user,c.name as zone,d.name as table_no")
            ->join('xadmins b','a.op_user_id=b.id','left')
            ->join('xevents e','e.id = a.event_id','left')
            ->join('xzones c','c.id = a.zone_id','left')
            ->join('xtables d','d.id = a.table_id','left')
            ->where('a.id',$id)
            ->find();
        return $res;
    }

    public function updateUserAttend($id,$userID){
        return $this->where('id',$id)->update(['op_user_id'=>$userID,
            'checkin_time'=>date('Y-m-d H:i:s',time()),
            'checkin_status'=>1]);
    }

    public function updateUserUnAttend($id,$userID){
        return $this->where('id',$id)->update(['op_user_id'=>0,
            'checkin_time'=>'',
            'checkin_status'=>0]);
    }

    public function updateAllUserAttend($ids,$userID){
        return $this->where('id','in',$ids)->update(['op_user_id'=>$userID,
            'checkin_time'=>date('Y-m-d H:i:s',time()),
            'checkin_status'=>1]);
    }

    /**
     * 更新用户状态
     * @param int $userID
     * @param int $user_status
     * @return array
     */
    public function updateUserStatus($userID = 0,$user_status = 0){
        $status = $this
            ->where("unique_id",$userID)
            ->update(["status"=>$user_status]);
        $message = $status?"Update success":"Sorry，update failed";
        return ['status'=>$status,'message'=>$message];
    }

    public function getUserByEmail($email,$type) {
        return $this->where(['email' => $email,'type'=>$type])->find();
    }

    public function getUserByUserName($userName,$type) {
        return $this->where(['user_id' => $userName,'type'=>$type])->find();
    }

    public function getUserByUid($uid) {
        return $this->field('id,unique_id,login_name,status,event_id,type')
            ->where(['unique_id' => $uid])->find();
    }

    public function getUidById($id){
        $res = $this->field('unique_id')->where('id',$id)->find();
        return !empty($res)?$res['unique_id']:'';
    }

    public function checkUserLogin($input)
    {
        $flag = false;
        $message = "Login success";
        $userName = isset($input['user_name']) ? $input['user_name'] : '';
        $publicKey = isset($input['public_key']) ? $input['public_key'] : '';
        $type = isset($input['type'])?$input['type']:0;
        $code = isset($input['code']) ? $input['code'] : '';
//        $verifyCode = isset($input['login_verifyCode']) ? $input['login_verifyCode'] : '';
//        测试模式下删除google recaptcha
//        $gRecaptchaResposne = isset($input['g_recaptcha_response']) ? $input['g_recaptcha_response'] : '';
//        if(empty($gRecaptchaResposne)){
//            return ['tag'=>$flag,'message'=>'invalid recaptcha!'];
//        }
//        $recaptchaRes = HttpUtil::http_post_data('https://www.google.com/recaptcha/api/siteverify'
//                , http_build_query(['secret'=>config('sys_auth.GOOGLE_RECAPTCHA_SECRET')
//                    ,'response'=>$gRecaptchaResposne
//                    ,'remoteip'=> Tools::get_ip()]));
//        if($recaptchaRes[0] != 200){
//            return ['tag'=>$flag,'message'=>'invalid recaptcha!'];
//        }
//        $recaptchJson = json_decode($recaptchaRes[1]);
//        LogUtil::info('[recaptch]'.$recaptchaRes[1]);
//        if(empty($recaptchJson)){
//            return ['tag'=>$flag,'message'=>'invalid recaptcha!'];
//        }
//        if(!$recaptchJson->success){
//            return ['tag'=>$flag,'message'=>'invalid recaptcha!'];
//        }
//        if($recaptchJson->score < 0.5){
//            return ['tag'=>$flag,'message'=>'login is under risk!'];
//        }
        $res = $this
            ->field('id op_id,private_key,authenticate_key')
            ->where('login_name', $userName)
            ->where('status', 1)
            ->where('type',$type)
            ->find();
        if ($res) {
//                if ($res->password == IAuth::setAdminUsrPassword($pwd)) {
            if ($res->authenticate_key == IAuth::setUserAuthKey($publicKey,$res->private_key)) {
                $flag = $res->op_id;
                IAuth::setSessionUserCurrLogged($res->op_id);
            } else {
                $message = "Login failed,please check your username or password!";
            }
        } else {
            $message = "The username is invalid or expired!";
        }

        return [
            'tag' => $flag,
            'message' => $message
        ];
    }

    public function checkVendorUserLogin($input)
    {
        $flag = false;
        $message = "Login success";
        $userName = isset($input['user_name']) ? $input['user_name'] : '';
        $publicKey = isset($input['public_key']) ? $input['public_key'] : '';
        $type = isset($input['type'])?$input['type']:0;
        $res = $this
            ->field('id op_id,private_key,authenticate_key')
            ->where('login_name', $userName)
            ->where('status', 1)
            ->where('type',$type)
            ->find();
        if ($res) {
            if ($res->authenticate_key == IAuth::setUserAuthKey($publicKey,$res->private_key)) {
                $flag = $res->op_id;
                IAuth::setSessionVendorUserCurrLogged($res->op_id);
            } else {
                $message = "Login failed,please check your username or password!";
            }
        } else {
            $message = "The username is invalid or expired!";
        }

        return [
            'tag' => $flag,
            'message' => $message
        ];
    }

    public function updatePasswordByUser($id,$input){
        $oldPublicKey = $input['old_public_key'];
        $newPublicKey = $input['new_public_key'];
        $res = $this->where(['id'=>$id])->find();
        $authKey = IAuth::aesEncrypt($oldPublicKey, $res['private_key'], config('sys_auth.AES_IV'));
        if($authKey != $res['authenticate_key']){
            return ['tag'=>0,'message'=>'old password is wrong!'];
        }
        $newAuthKey = IAuth::aesEncrypt($newPublicKey, $res['private_key'], config('sys_auth.AES_IV'));
        $tag = $this->where('id',$id)->update(['authenticate_key'=>$newAuthKey]);
        if(empty($tag)){
            return ['tag'=>0,'message'=>'update password failed'];
        }else{
            return ['tag'=>1,'message'=>'update password success'];
        }
    }

    public function updatePassword($id,$title,$content,$email,$domain){
        $user = $this->getCmsDataByID($id);
        //生成新的随机密码
        $password = Tools::randCode(8,-1);
        $md5Pwd = md5($password);
        $publicKey = IAuth::aesEncrypt($user['login_name'],$md5Pwd,config('sys_auth.CLIENT_AES_IV'));
        $privateKey = Tools::randCode();
        $authenticateKey = IAuth::aesEncrypt($publicKey, $privateKey, config('sys_auth.AES_IV'));
        $user['private_key'] = $privateKey;
        $user['authenticate_key'] = $authenticateKey;
        $user->save();
        //通过邮件发送新密码给用户
//        $rs = $this->sendExhibitorEmail($user,$title,$email,$password,$domain);
        $task = [
            'id'=>Tools::create_guid(),
            'name'=>'exhibitor_update_password',
            'data'=>json_encode(['user_id'=>$user['unique_id'],'title'=>$title,'email'=>$email,'password'=>$password,'domain'=>$domain]),
            'status'=>0,
            'create_time'=>Date('Y-m-d H:i:s',time())
        ];
        Db::name('xtasks')->insert($task);
        return ['status'=>1,'msg'=>'success'];
    }

    public function getUserByCompanyId($companyId,$type=0){
        return Db::name('xusers')->where(['company_id'=>$companyId,'type'=>$type,'status'=>1])
            ->field('id,login_name')->select();
    }

    public function removeUser($list){
        Db::name('xusers')->where('id','in',$list)->delete();
    }

    public function getVendorAccounts($acc){
        $accounts = json_decode($acc,true);
        return Db::name('xusers')->where(['type'=>1,'status'=>1,'login_name'=>$accounts])
            ->field('id,login_name,first_name,last_name,phone_country_code,phone_area_code,phone_number,email')
            ->select();
    }

    public function getUsersByCompanyId($companyId){
        return Db::name('xusers')->where(['company_id'=>$companyId,'status'=>1])
            ->field('id,login_name,first_name,last_name,phone_country_code,phone_area_code,phone_number,email')
            ->select();
    }

    public static function getUserStatus($join){
        if($join == '1'){
            return 'RSVP already';
        }else if($join == '2'){
            return 'Declined';
        }else if($join == '9'){
            return 'Pending Review';
        }else if($join == '0'){
            return 'Initial';
        }else if($join == '3'){
            return 'Rejected by TIMP';
        }else if($join == '4'){
            return 'Cancelled';
        }else if($join == '5'){
            return 'Confirmation';
        }else if($join == '6'){
            return 'Reminder to register';
        }else if($join == '7'){
            return 'Reminder to attend';
        }else if($join == '8'){
            return 'Invalid';
        }else if($join == '10'){
            return 'Thank you';
        }else if($join == '11'){
            return 'Miss you';
        }else if($join == '13'){
            return 'Email not match';
        }else{
            return '';
        }
    }
}