<?php

namespace app\common\model;

use app\common\lib\IAuth;
use app\common\validate\Xevent;
use think\Db;
use \think\Model;
use app\common\lib\Tools;

/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/1/11
 * Time: 16:45
 */
class XeventAccounts extends BaseModel
{
    // 设置当前模型对应的完整数据表名称
    protected $autoWriteTimestamp = 'datetime';

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    /**
     * 获取所有的文章
     * @return array
     */
    public function getEventsList()
    {
        $userId = IAuth::getAdminIDCurrLogged();
        $res = $this
            ->field("a.*")
            ->alias('a')//给主表取别名
            ->where('a.assign_accounts','like','%|'.$userId.'|%')
            ->select();
        return isset($res) ? $res->toArray() : [];
    }

    public function getAssignAccounts($eventId)
    {
//        $userId = IAuth::getAdminIDCurrLogged();
        $res = $this
            ->field("a.*")
            ->alias('a')//给主表取别名
//            ->where('a.user_id','=',$userId)
            ->where('a.event_id','=',$eventId)
            ->find();
        return isset($res) ? $res['assign_accounts'] : '';
    }

    /**
     * 更新文章内容
     * @param $input
     * @param int $id
     * @return array
     */
    public function updateCmsEventData($input,$eventId)
    {
        $saveData = [
            'assign_accounts' => isset($input['assign_accounts'])?$input['assign_accounts']:''
        ];
        $where = ['event_id'=>$eventId];
        $res = $this->where($where)->find();
        if(empty($res)){
            $saveTag = $this->addEvent($input,$eventId);
        }else{
            $saveTag = $this
                ->where($where)
                ->update($saveData);
        }
//        if ($saveTag) {
//            insertCmsOpLogs($saveTag,'EVENT_ACCOUNTS',$userId,'event account update');
//        }
        $validateRes['tag'] = $saveTag;
        $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        return $validateRes;
    }

    /**
     * 进行新文章的添加操作
     * @param $data
     * @return array
     */

    public function addEvent($data,$eventId)
    {
        $addData = [
            'event_id' => $eventId,
            'assign_accounts' => isset($data['assign_accounts'])?$data['assign_accounts']:''
        ];
        $tag = $this->insert($addData);
        if ($tag) {
            insertCmsOpLogs($tag,'EVENT_ACCOUNTS',$this->getLastInsID(),'add event account');
        }
        $validateRes['tag'] = $tag;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }
}