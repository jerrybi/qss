<?php

namespace app\common\model;

use app\common\lib\IAuth;
use app\common\validate\Xadmin;
use think\Db;
use app\common\lib\Tools;
use app\common\lib\HttpUtil;
use app\common\lib\LogUtil;

class Xadmins extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xadmin();
    }

    /**
     * 分页获取管理员数据
     * @param $curr_page
     * @param $limit
     * @param null $search
     * @return array
     */
    public function getAdminsForPage($curr_page, $limit,$search = null)
    {
        $where[] = ["a.status",'<>',-1];
        $res = $this
            ->alias('a')
            ->field('a.*,ar.user_name role_name')
            ->join('xadmin_roles ar', 'a.role_id = ar.id')
            ->order('a.id', 'desc')
            ->where($where)
            ->whereLike('a.user_name|a.content', '%' . $search . '%')
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        foreach ($res as $key => $v) {
            if ($v['status'] == 1) {
                $statusTip = 'Enabled';
                $statusColor = 'blue';
            } else {
                $statusTip = 'Disabled';
                $statusColor = 'cyan';
            }
            $roleTag = $v['role_id'] % 5;
            $role_name = $v['role_name'];
            switch ($roleTag) {
                case 0:
                    $roleColor = 'orange';
                    break;
                case 1:
                    $roleColor = 'green';
                    break;
                case 3:
                    $roleColor = 'cyan';
                    break;
                default:
                    $roleColor = 'blue';
                    break;
            }
            $res[$key]['role_tip'] = $role_name;
            $res[$key]['status_tip'] = $statusTip;
            $res[$key]['picture'] = imgToServerView($res[$key]['picture']);
        }
        return $res;
    }

    /**
     * 获取后台可显示管理员用户的数目
     * @param null $search
     * @return float|string
     */
    public function getAdminsCount($search = null)
    {

        $where[] = ["a.status",'<>',-1];
        $res = $this
            ->alias('a')
            ->field('a.*,ar.user_name role_name')
            ->join('xadmin_roles ar', 'a.role_id = ar.id')
            ->order('a.id', 'desc')
            ->where($where)
            ->whereLike('a.user_name|a.content', '%' . $search . '%')
            ->count();
        return $res;
    }

    /**
     * 根据ID 获取管理员数据
     * @param $id
     * @return array
     */
    public function getAdminData($id)
    {
        $res = $this
            ->alias('a')
            ->field('a.id,a.user_name,a.picture,a.role_id,a.created_at,a.email,a.parent_id,
                     a.status,a.content,ar.user_name role_name')
            ->join('xadmin_roles ar', 'ar.id = a.role_id')
            ->where('a.id', $id)
            ->find();
        return isset($res)?$res->toArray():[];
    }

    /**
     * 添加后台管理员
     * @param $input
     * @return int|void
     */
    public function addAdmin($input)
    {
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';
        $sameTag = $this->chkSameUserName($user_name);
        if ($sameTag) {
            $validateRes['tag'] = 0;
            $validateRes['message'] = 'This nickname is used,please change to another!';
        } else {
//            $password = config('sys_auth.DEFAULT_PWD');
//            $publicKey = IAuth::aesEncrypt($user_name,md5($password),'qsxxqsxxqsxxqsxx');
            $addData = [
                'user_name' => $user_name,
                'picture' => isset($input['picture']) ? $input['picture'] : '',
                'email' => isset($input['email']) ? $input['email'] : '',
//                'password' => IAuth::setAdminUsrPassword($input['password']),
//                're_password' => IAuth::setAdminUsrPassword($input['re_password']),
                'created_at' => date("Y-m-d H:i:s", time()),
                'role_id' => isset($input['role_id'])?intval($input['role_id']):0,
                'parent_id' => isset($input['parent_id'])?intval($input['parent_id']):0,
                'status' => isset($input['status'])?intval($input['status']):0,
                'content' => isset($input['content'])?$input['content']:'',
                 'public_key' => isset($input['public_key'])?$input['public_key']:'',
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $addData, $tokenData);
            if ($validateRes['tag']) {
                $privateKey = Tools::randCode();
                $result = IAuth::aesEncrypt($input['public_key'], $privateKey, config('sys_auth.AES_IV'));
                $addData['private_key'] = $privateKey;
                $addData['authenticate_key'] = $result;
                $tag = $this->allowField(true)->save($addData);
                $validateRes['tag'] = $tag;
                $validateRes['message'] = $tag ? 'Add success' : 'Add fail';
            }
        }
        return $validateRes;

    }

    /**
     * 当前在线管理员 对个人信息的修改
     * @param $id
     * @param $input
     * @param $cmsAID
     * @return array
     */
    public function editCurrAdmin($id, $input, $cmsAID)
    {
        $tag = 0;
        $saveData = [
            'user_name' => isset($input['user_name'])?$input['user_name']:null,
            'picture' => isset($input['picture'])?$input['picture']:'',
            'content' => isset($input['content'])?$input['content']:'',
            'email' => isset($input['email']) ? $input['email'] : '',
        ];
        $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $saveData, $tokenData, 'cms_admin');
        if ($validateRes['tag']) {
            if ($cmsAID && ($cmsAID != $id)) {
                $message = "you have no permission to edit!";
            } else {
//                if ($input['password']) {
//                    //TODO 如果输入了新密码
//                    if ($input['password'] !== $input['re_password']){
//                        $message = "两次输入的密码不一样";
//                    }else{
//                        $saveData['password'] = IAuth::setAdminUsrPassword($input['password']);
//                        $tag = $this
//                            ->where('id', $id)
//                            ->update($saveData);
//                        $message = $tag ? '信息修改成功' : '数据无变动，修改失败';
//                    }
//                }else{
//                    $tag = $this
//                        ->where('id', $id)
//                        ->update($saveData);
//                    $message = $tag ? '信息修改成功' : '数据无变动，修改失败';
//                }
                if (isset($input['public_key'])) {
                    $res = $this->field('private_key')->where(['id'=>$id])->find();
                    $result = IAuth::aesEncrypt($input['public_key'], $res['private_key'], config('sys_auth.AES_IV'));
                    $saveData['authenticate_key'] = $result;
                    $tag = $this
                        ->where('id', $id)
                        ->update($saveData);
                    $message = $tag ? 'Update success' : 'data no change,update failed';
                }else{
                    $tag = $this
                        ->where('id', $id)
                        ->update($saveData);
                    $message = $tag ? 'Update success' : 'data no change,update failed';
                }
            }
        }else{
            $message = $validateRes['message'];
        }
        return ['tag' => $tag,'message' => $message];
    }

    /**
     * 根据ID 修改管理员数据
     * @param $id
     * @param $input
     * @return void|static
     */
    public function editAdmin($id, $input)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $tag = $this
                ->where('id', $id)
                ->update(['status' => -1]);
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'Admin remove success' : 'Sorry,admin remove failed';
        } else {
            $sameTag = $this->chkSameUserName($input['user_name'], $id);
            if ($sameTag) {
                $validateRes['tag'] = 0;
                $validateRes['message'] = 'the user name is used,please change another';
            } else {
                $saveData = [
                    'user_name' => isset($input['user_name'])?$input['user_name']:'',
                    'picture' => isset($input['picture']) ? $input['picture'] : '',
                    'role_id' => isset($input['role_id'])?intval($input['role_id']):0,
                    'parent_id' => isset($input['parent_id'])?intval($input['parent_id']):0,
                    'status' => isset($input['status'])?intval($input['status']):0,
                    'content' => $input['content'],
                    'email' => isset($input['email']) ? $input['email'] : '',
                ];
                $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
//                if ($input['password']) {
                if (isset($input['public_key'])) {
                    //TODO 如果输入了新密码
//                    $saveData['password'] = IAuth::setAdminUsrPassword($input['password']);
//                    $saveData['re_password'] = IAuth::setAdminUsrPassword($input['re_password']);
                    $validateRes = $this->validate($this->validate, $saveData, $tokenData);
                    if($validateRes['tag']){
                        $res = $this->field('private_key')->where(['id'=>$id])->find();
                        $result = IAuth::aesEncrypt($input['public_key'], $res['private_key'], config('sys_auth.AES_IV'));
                        $saveData['authenticate_key'] = $result;
                    }
                } else {
                    $validateRes = $this->validate($this->validate, $saveData, $tokenData, 'edit_admin_no_pwd');
                }

                if ($validateRes['tag']) {
                    $tag = $this->allowField(true)->save($saveData, ['id' => $id]);
                    $validateRes['tag'] = $tag;
                    $validateRes['message'] = $tag ? 'Admin update success' : 'Data no change，update failed';
                }
            }
        }
        return $validateRes;
    }

    public function updatePassword($id, $input)
    {
        $oldPublicKey = $input['old_public_key'];
        $newPublicKey = $input['new_public_key'];
        $res = $this->field('private_key,authenticate_key')->where(['id'=>$id])->find();
        $authKey = IAuth::aesEncrypt($oldPublicKey, $res['private_key'], config('sys_auth.AES_IV'));
        if($authKey != $res['authenticate_key']){
            return ['tag'=>0,'message'=>'old password is wrong!'];
        }
        $newAuthKey = IAuth::aesEncrypt($newPublicKey, $res['private_key'], config('sys_auth.AES_IV'));
        $tag = $this->where('id',$id)->update(['authenticate_key'=>$newAuthKey]);
        $validateRes=['tag'=>$tag,'message'=>$tag ? 'update password success' : 'update password failed'];
        return $validateRes;
    }

    /**
     * 判断当前数据库中是否有重名的管理员
     * @param $user_name
     * @param int $id
     * @return mixed
     */
    public function chkSameUserName($user_name, $id = 0)
    {
        $tag = $this
            ->field('user_name')
            ->where('user_name', $user_name)
            ->where('id', '<>', $id)
            ->count();
        return $tag;
    }

    public function getIdByUserName($user_name)
    {
        $res = $this
            ->field('user_name')
            ->where('user_name', $user_name)
            ->field('id')
            ->find();
        return !empty($res)?$res->id : 0;
    }

    /**
     * 获取当前管理员权限下的 导航菜单
     * @param int $id
     * @return mixed
     */
    public function getAdminNavMenus($id = 1)
    {
        $nav_menu_ids = $this
            ->alias('a')
            ->join('xadmin_roles ar', 'ar.id = a.role_id')
            ->where([['a.id', '=', $id], ['a.status', '=', 1]])
            ->value('nav_menu_ids');
        return $nav_menu_ids;
    }

    /**
     * 管理员登录 反馈
     * @param $input
     * @return bool|mixed
     */
    public function checkAdminLogin($input)
    {
        $flag = false;
        $message = "Login success";
        $userName = isset($input['user_name']) ? $input['user_name'] : '';
        $pwd = isset($input['password']) ? $input['password'] : '';
        $publicKey = isset($input['public_key']) ? $input['public_key'] : '';
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
            ->field('password,id op_id,private_key,authenticate_key')
            ->where('user_name', $userName)
            ->where('status', 1)
            ->find();
        if ($res) {
//                if ($res->password == IAuth::setAdminUsrPassword($pwd)) {
            if ($res->authenticate_key == IAuth::setAdminUsrAuthKey($publicKey,$res->private_key)) {
                $flag = $res->op_id;
                IAuth::setSessionAdminCurrLogged($res->op_id);
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

    /**
     * 检查 管理员是否对此URL有管理权限
     * @param int $adminID
     * @param string $authUrl
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkAdminAuth($adminID = 0, $authUrl = '')
    {
        $checkTag = false;
        $nav_menu_ids = $this->getAdminNavMenus($adminID);
        if (is_string($nav_menu_ids)) {
//            $arrMenus = explode("|", $nav_menu_ids);
            $arrMenus = $this->getTreeIds(json_decode($nav_menu_ids,true));
            foreach ($arrMenus as $key => $menu_id) {
                if ($menu_id) {
                    $checkTag = $this->checkAuthUrlForMenuID($menu_id, $authUrl);
                    if ($checkTag) {
                        break;
                    } else {
                        //此时判断其的 下级权限中是否满足 当前访问的权限
                        $childMenus = Db::name('xnav_menus')
                            ->field("n2.id")
                            ->alias('n1')
                            ->join("xnav_menus n2", "n1.id = n2.parent_id")
                            ->where(
                                [
                                    ["n2.parent_id", '=', $menu_id],
                                    ['n2.status', '=', 1],
                                    ['n2.type', '=', 1]
                                ])
                            ->select();
                        foreach ($childMenus as $key2 => $child_menu) {
                            $checkTag = $this->checkAuthUrlForMenuID($child_menu['id'], $authUrl);
                            if ($checkTag) {
                                break;
                            } else {
                                continue;
                            }
                        }
                        if ($checkTag) {
                            break;
                        }
                    }
                }
            }
        }
        return $checkTag;
    }

    /**
     * 忽略 因操作系统不同对链接字符串大小写的敏感
     * @param int $menu_id
     * @param $authUrl
     * @return bool
     */
    public function checkAuthUrlForMenuID($menu_id = 0, $authUrl)
    {
        $checkTag = false;
        $menuAction = Db::name('xnav_menus')
            ->where([["id", '=', $menu_id], ['status', '=', 1]])
            ->value('action');
        if ("/" . strtolower($menuAction) == strtolower($authUrl)) {
            $checkTag = true;
        }
        return $checkTag;
    }

    public function getChildAccounts($user_id){
        $res = $this->where('parent_id','=',$user_id)->where('status','=','1')
            ->field('user_name,role_id,picture,parent_id,id,email')
            ->select();
        return $res;
    }

    public function getPagePermissions($adminID = 0, $menuID = 0){
        $nav_menu_ids = $this->getAdminNavMenus($adminID);
        if (is_string($nav_menu_ids)) {
            $menu = $this->findTreeNode(json_decode($nav_menu_ids,true),$menuID);
            if($menu){
                $childs = $menu['children'];
                return [
                    'view'=>Tools::find_array_item($childs,'title','View')!=null?true:false,
                    'add'=>Tools::find_array_item($childs,'title','Add')!=null?true:false,
                    'edit'=>Tools::find_array_item($childs,'title','Edit')!=null?true:false,
                    'delete'=>Tools::find_array_item($childs,'title','Delete')!=null?true:false,
                    'import'=>Tools::find_array_item($childs,'title','Import')!=null?true:false,
                    'export'=>Tools::find_array_item($childs,'title','Export')!=null?true:false
                ];
            }
        }
        return [
            'view'=>false,
            'add'=>false,
            'edit'=>false,
            'delete'=>false,
            'import'=>false,
            'export'=>false
        ];
    }
}
