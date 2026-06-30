<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\lib\Tools;
use app\common\lib\QRCode;
use app\common\validate\Xtable;
use think\Db;
use FormDesign\Formdesign;

class XuserStatus extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function addData($data)
    {
        $userID = isset($data['user_id'])?$data['user_id']:0;
        $day = isset($data['day'])?$data['day']:'';
        $eventID = isset($data['event_id'])?$data['event_id']:'';
        if(empty($userID) || empty($day) || empty($eventID)){
            return ['tag' => false, 'message' => 'invalid parameter!'];
        }
        $result = Db('xuser_status')
            ->where('user_id',$userID)
            ->where('day',$day)
            ->where('event_id',$eventID)
            ->where('status',1)
            ->find();
        if(empty($result)){
            $tag = $this->insertGetId([
                'user_id' => $userID,
                'event_id' => $eventID,
                'day' => $day,
                'checkin_status' => isset($data['checkin_status'])?$data['checkin_status']:0,
                'op_user_id' => isset($data['op_user_id'])?$data['op_user_id']:0,
                'checkin_time' => isset($data['checkin_time'])?$data['checkin_time']:null,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s',time())
            ]);
        }else{
            $this->where('id',$result['id'])->update([
                'checkin_status' => isset($data['checkin_status'])?$data['checkin_status']:0,
                'op_user_id' => isset($data['op_user_id'])?$data['op_user_id']:0,
                'checkin_time' => isset($data['checkin_time'])?$data['checkin_time']:null,
                'update_time' => date('Y-m-d H:i:s',time())
            ]);
            $tag = $result['id'];
        }
        $validateRes['tag'] = $tag>0?1:0;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function deleteData($userID,$day,$eventID){
        return $this->where('user_id',$userID)
            ->where('day',$day)
            ->where('event_id',$eventID)
            ->delete();
    }

    public function getDataList($userID){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_status')
            ->alias('a')
            ->field('a.*')
            ->where($where)
            ->where('a.user_id',$userID)
            ->order(['a.day'=>'asc'])
            ->select();
        return $res;
    }

    public function getDataByDay($userID,$day){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_status')
            ->alias('a')
            ->leftJoin('xadmins b','a.op_user_id = b.id')
            ->field('a.*,b.user_name as op_user')
            ->where($where)
            ->where('a.user_id',$userID)
            ->where('a.day',$day)
            ->find();
        return $res;
    }

    public function getCmsDataByID($id){
        $res = $this->where('id',$id)->find();
        return isset($res)?$res->toArray():[];
    }

    public function getDataByEvent($eventID){
        $res = Db::name('xuser_status')
            ->alias('a')
            ->leftJoin('xadmins b','a.op_user_id = b.id')
            ->field('a.*,b.user_name as op_user')
            ->where('a.status',1)
            ->where('a.event_id',$eventID)
            ->select();
        return $res;
    }

}