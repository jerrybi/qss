<?php

namespace app\common\model;

use app\common\controller\Base;
use app\common\validate\XadminRole;
use think\Model;
use think\Db;

class XadminRoles extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new XadminRole();
    }

    /**
     * 获取正常角色列表
     * @return mixed
     */
    public function getNormalRoles()
    {
        // 超级管理员角色不参与分配
        $res = $this
            ->where('status', 1)
            ->where('id','<>',1)
            ->select()->toArray();
        return $res;
    }

    /*
     * 获取所有的角色列表
     */
    public function getAllRoles()
    {
        $res = $this
//            ->where('status', '<>', -1)
            ->order('status', 'desc')
            ->order('id', 'asc')
            ->select()->toArray();
        foreach ($res as $key => $v) {
            $role_name = $v['user_name'];
            $res[$key]['role_tip'] = "$role_name";
            if ($v['status'] == 1) {
                $res[$key]['status_tip'] = "<span>Enabled</span>";
            } else {
                $res[$key]['status_tip'] = "<span>Disabled</span>";
            }
        }
        return $res;
    }

    public function getParents($role_id){
        $res = $this->where('id','<',$role_id)->field('id')->select();
        $roleIds = [];
        foreach ($res as $key=>$value){
            array_push($roleIds,$value['id']);
        }
        $parents = Db::name('xadmins')->where('role_id','in',$roleIds)->where('status','=','1')
            ->field('user_name,role_id,picture,parent_id,id,email')
            ->select();
        return $parents;
    }

    /**
     * 添加新角色
     * @param $input
     * @return mixed
     */
    public function addRole($input)
    {
        $user_name = isset($input['user_name']) ? $input['user_name'] : '';
        $checkSameTag = $this->chkSameUserName($user_name);
        if ($checkSameTag) {
            $validateRes['tag'] = 0;
            $validateRes['message'] = 'the name is used,please use another！';
        } else {
            $addData = [
                'user_name' => $user_name,
                'nav_menu_ids' => $input['nav_menu_ids'] ? $input['nav_menu_ids'] : '',
                'special_grant' => $input['special_grant'] ? $input['special_grant'] : 0,
                'updated_at' => date("Y-m-d H:i:s", time()),
                'status' => intval($input['status']),
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $addData, $tokenData);
            if ($validateRes['tag']) {
                $tag = $this->insert($addData);
                $validateRes['tag'] = $tag;
                $validateRes['message'] = $tag ? 'Add role success' : 'Add role fail';
            }
        }
        return $validateRes;
    }

    /**
     * 修改角色数据
     * @param $id
     * @param $input
     * @return void|static
     */
    public function editRole($id, $input)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $tag = $this
                ->where('id', $id)
                ->update(['status' => -1]);
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'Role remove success' : 'Sorry,role remove fail！';
        } else {
            $sameTag = $this->chkSameUserName($input['user_name'], $id);
            if ($sameTag) {
                $validateRes['tag'] = 0;
                $validateRes['message'] = 'the name is used,please use another！';
            } else {
                $saveData = [
                    'user_name' => $input['user_name'],
                    'status' => intval($input['status']),
                    'nav_menu_ids' => $input['nav_menu_ids'],
                     'special_grant' => $input['special_grant']
                ];
                $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
                $validateRes = $this->validate($this->validate, $saveData, $tokenData);
                if ($validateRes['tag']) {
                    $tag = $this
                        ->where('id', $id)
                        ->update($saveData);
                    $validateRes['message'] = $tag ? 'Role edit success' : 'Role edit fail';
                }
            }
        }
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

    /**
     * 获取不同角色对应的数据
     * @param $id
     * @return array|null|\PDOStatement|string|Model
     */
    public function getRoleData($id)
    {
        $res = $this
            ->field('*')
            ->where('id', $id)
            ->find();
        return $res;
    }


}
