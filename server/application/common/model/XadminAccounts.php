<?php

namespace app\common\model;

use app\common\controller\Base;
use app\common\validate\XadminAccount;
use app\common\validate\XadminRole;
use think\Model;
use think\Db;

class XadminAccounts extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new XadminAccount();
    }

    /**
     * 获取正常角色列表
     * @return mixed
     */
    public function getNormalAccounts()
    {
        $res = $this
            ->where('status', 1)
            ->select()->toArray();
        return $res;
    }

    /*
     * 获取所有的角色列表
     */
    public function getAllAccounts()
    {
        $res = $this
//            ->where('status', '<>', -1)
            ->order('status', 'desc')
            ->order('id', 'asc')
            ->select()->toArray();
        foreach ($res as $key => $v) {
            $account_name = $v['account_name'];
            $res[$key]['account_tip'] = "$account_name";
            if ($v['status'] == 1) {
                $res[$key]['status_tip'] = "<span>Enabled</span>";
            } else {
                $res[$key]['status_tip'] = "<span>Disabled</span>";
            }
        }
        return $res;
    }

    public function getAccountsForPage($curr_page, $limit,$search = null)
    {
        $where[] = ["a.status",'<>',-1];
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->order('a.id', 'asc')
            ->where($where)
            ->whereLike('a.account_name', '%' . $search . '%')
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
            //todo
            $res[$key]['current_event'] = 0;
            $res[$key]['current_user'] = 0;
            $res[$key]['status_tip'] = "<span>$statusTip</span>";
        }
        return $res;
    }

    /**
     * 获取后台可显示管理员用户的数目
     * @param null $search
     * @return float|string
     */
    public function getAccountsCount($search = null)
    {

        $where[] = ["a.status",'<>',-1];
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->order('a.id', 'asc')
            ->where($where)
            ->whereLike('a.account_name', '%' . $search . '%')
            ->count();
        return $res;
    }

    /**
     * 添加新角色
     * @param $input
     * @return mixed
     */
    public function addAccount($input)
    {
        $account_name = isset($input['account_name']) ? $input['account_name'] : '';
        $checkSameTag = $this->chkSameUserName($account_name);
        if ($checkSameTag) {
            $validateRes['tag'] = 0;
            $validateRes['message'] = 'the name is used,please use another！';
        } else {
            $addData = [
                'account_name' => $account_name,
                'max_event' => $input['max_event'] ? $input['max_event'] : '',
                'max_user' => $input['max_user'] ? $input['max_user'] : 0,
                'updated_at' => date("Y-m-d H:i:s", time()),
                'status' => intval($input['status']),
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $addData, $tokenData);
            if ($validateRes['tag']) {
                $tag = $this->insert($addData);
                $validateRes['tag'] = $tag;
                $validateRes['message'] = $tag ? 'Add account success' : 'Add account fail';
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
    public function editAccount($id, $input)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $tag = $this
                ->where('id', $id)
                ->update(['status' => -1]);
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'Account remove success' : 'Sorry,account remove fail！';
        } else {
            $sameTag = $this->chkSameUserName($input['account_name'], $id);
            if ($sameTag) {
                $validateRes['tag'] = 0;
                $validateRes['message'] = 'the name is used,please use another！';
            } else {
                $saveData = [
                    'account_name' => $input['account_name'],
                    'status' => intval($input['status']),
                    'max_event' => $input['max_event'],
                     'max_user' => $input['max_user']
                ];
                $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
                $validateRes = $this->validate($this->validate, $saveData, $tokenData);
                if ($validateRes['tag']) {
                    $tag = $this
                        ->where('id', $id)
                        ->update($saveData);
                    $validateRes['message'] = $tag ? 'Account edit success' : 'Account edit fail';
                }
            }
        }
        return $validateRes;
    }

    /**
     * 判断当前数据库中是否有重名的管理员
     * @param $account_name
     * @param int $id
     * @return mixed
     */
    public function chkSameUserName($account_name, $id = 0)
    {
        $tag = $this
            ->field('account_name')
            ->where('account_name', $account_name)
            ->where('id', '<>', $id)
            ->count();
        return $tag;
    }

    /**
     * 获取不同角色对应的数据
     * @param $id
     * @return array|null|\PDOStatement|string|Model
     */
    public function getAccountData($id)
    {
        $res = $this
            ->field('*')
            ->where('id', $id)
            ->find();
        return $res;
    }
}
