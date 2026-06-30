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
use app\common\validate\Xexhibitor;
use think\Db;

class Xexhibitors extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xexhibitor();
    }
    /**
     * 分页获取用户数据
     * @param int $curr_page
     * @param int $page_limit
     * @param null $search
     * @return array
     */
    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1, $search = null,$eventId = null){
        $condition = ['a.status'=>['1','99']];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = $this
            ->alias('a')
            ->field("a.*,e.name as event_name")
            ->join('xevents e','e.id = a.event_id')
            ->where('a.login_name|a.unique_id', 'like', '%' . $search . '%')
            ->where($condition)
            ->order(['a.id' => 'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    /**
     * 获取用户数量
     * @param null $search
     * @return float|string
     */
    public function getCmsDatasCount($search = null,$eventId = null){
        $condition = ['a.status'=>['1','99']];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $count = $this
            ->alias('a')
            ->field("a.*")
            ->where('a.login_name|a.unique_id', 'like', '%' . $search . '%')
            ->where($condition)
            ->count();
        return $count;
    }

    public function getCmsDataList($eventId = null){
        $condition = ['a.status'=>['1','99']];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = $this
            ->alias('a')
            ->field("a.id,a.login_name")
            ->where($condition)
            ->order(['a.id' => 'asc'])
            ->select();
        return isset($res)?$res->toArray():[];
    }

    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xexhibitors')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'EXHIBITOR',$id,'remove exhibitor');
        } else {
            $saveData = [
                'login_name' => isset($input['login_name'])?$input['login_name']:'',
                'first_name' => isset($input['first_name'])?$input['first_name']:'',
                'last_name' => isset($input['last_name'])?$input['last_name']:'',
                'phone_country_code' => isset($input['phone_country_code'])?$input['phone_country_code']:'',
                'phone_area_code' => isset($input['phone_area_code'])?$input['phone_area_code']:'',
                'phone_number' => isset($input['phone_number'])?$input['phone_number']:'',
                'email' => isset($input['email'])?$input['email']:'',
                'company' => isset($input['company'])?$input['company']:'',
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                //检查名称是否已经存在
                $result = $this->where('login_name',$input['login_name'])->find();
                if(!empty($result) && $result['id'] != $id){
                    return ['tag' => false, 'message' => 'Login Name Exist!'];
                }
                $saveTag = $this
                    ->save($saveData,['id'=>$id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag,'User',$id,'User update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    public function addData($addData)
    {
        //检查名称是否已经存在
        $result = $this->where('login_name',$addData['login_name'])->find();
        if(!empty($result)){
            return ['tag' => false, 'message' => 'Login Name '.$addData['login_name'].' Exist!'];
        }
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
            ->where('id',$id)
            ->field('id,unique_id,login_name,first_name,last_name,company,status,event_id,phone_country_code,phone_area_code,phone_number,email')
            ->find();
        return $res;
    }

    /**
     * 更新用户状态
     * @param int $userID
     * @param int $user_status
     * @return array
     */
    public function updateUserStatus($userID = 0,$user_status = 0){
        $status = $this
            ->where("id",$userID)
            ->update(["status"=>$user_status]);
        $message = $status?"Update success":"Sorry，update failed";
        return ['status'=>$status,'message'=>$message];
    }

    public function getUserByEmail($email) {
        return $this->where(['email' => $email])->find();
    }

    public function getUserByUserName($userName) {
        return $this->where(['user_id' => $userName])->find();
    }

    public function getUserByUid($uid) {
        return $this->field('id,unique_id,login_name,first_name,last_name,company,status,event_id,phone_country_code,phone_area_code,phone_number,email')
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
        $res = $this
            ->field('id op_id,private_key,authenticate_key,status')
            ->where('login_name', $userName)
//            ->where('status', 1)
            ->find();
        if ($res) {
            if($res->status == '1'){
                //                if ($res->password == IAuth::setAdminUsrPassword($pwd)) {
                if ($res->authenticate_key == IAuth::setUserAuthKey($publicKey,$res->private_key)) {
                    $flag = $res->op_id > 0 ? true : false;
                    IAuth::setSessionUserCurrLogged($res->op_id);
                } else {
                    $message = "Login failed,please check your username or password!";
                }
            } else if($res->status == '99'){
                $message = "User has been disabled!";
            } else {
                $message = "The username is invalid!";
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
        $res = $this
            ->field('id op_id,private_key,authenticate_key')
            ->where('login_name', $userName)
            ->where('status', 1)
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

    public function updatePassword($id,$templateName){
        $user = $this->getCmsDataByID($id);
        //生成新的随机密码
        $password = Tools::randCode(8,-1);
//        $password = config('sys_auth.DEFAULT_PWD');
        $md5Pwd = md5($password);
        $publicKey = IAuth::aesEncrypt($user['login_name'],$md5Pwd,config('sys_auth.CLIENT_AES_IV'));
        $privateKey = Tools::randCode();
        $authenticateKey = IAuth::aesEncrypt($publicKey, $privateKey, config('sys_auth.AES_IV'));
        $user['private_key'] = $privateKey;
        $user['authenticate_key'] = $authenticateKey;
        $user->save();
        //通过邮件发送新密码给用户
        $templateId = Db::name('xedm_templates')->where('event_id',$user['event_id'])
            ->where('status',1)
            ->where('name',$templateName)
            ->value('id');
        if(!empty($templateId)){
            Db::name('xedm_tasks')->insert([
                'type' => 'exhibitor',
                'user_id'=>$id,
                'template_id'=>$templateId,
                'event_id'=>$user['event_id'],
                'data'=>json_encode(['pwd'=>$password]),
                'status'=>9,
                'create_time'=>date('Y-m-d H:i:s',time()),
                'update_time'=>date('Y-m-d H:i:s',time())
            ]);
        }
        return ['status'=>1,'msg'=>'success'];
    }

    public function removeUser($list){
        Db::name('xusers')->where('id','in',$list)->delete();
    }

    /**
     * 为 Exhibitor 生成 REST API 密钥对
     * @param int $id Exhibitor ID
     * @return array ['api_key'=>..., 'api_secret'=>...]
     */
    public function generateApiCredentials($id)
    {
        $exhibitor = $this->where('id', $id)->find();
        if (empty($exhibitor)) {
            return ['status' => false, 'message' => 'Exhibitor not found'];
        }

        // 生成 api_key: 前缀 + 随机 hex
        $apiKey = 'qss_' . bin2hex(random_bytes(16));

        // 生成 api_secret: 32 位随机字符串
        $rawSecret = bin2hex(random_bytes(20));

        // 存储时用 private_key 做 salt 的 sha256
        $hashedSecret = hash('sha256', $rawSecret . $exhibitor['private_key']);

        $this->where('id', $id)->update([
            'api_key'    => $apiKey,
            'api_secret' => $hashedSecret
        ]);

        return [
            'status'      => true,
            'api_key'     => $apiKey,
            'api_secret'  => $rawSecret,  // 明文仅返回一次
            'message'     => 'API credentials generated successfully. Please save your api_secret securely.'
        ];
    }

    /**
     * 撤销 Exhibitor 的 API 密钥
     * @param int $id Exhibitor ID
     * @return array
     */
    public function revokeApiCredentials($id)
    {
        $result = $this->where('id', $id)->update([
            'api_key'    => null,
            'api_secret' => null
        ]);
        return [
            'status'  => $result ? true : false,
            'message' => $result ? 'API credentials revoked' : 'Revoke failed'
        ];
    }
}