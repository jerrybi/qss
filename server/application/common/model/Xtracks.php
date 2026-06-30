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

class Xtracks extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsDatas($uid){
        $condition = ['a.status'=>'1'];
        $model = $this
            ->alias('a')
            ->field("a.*,b.user_name as op_user,c.name as zone")
            ->join('xadmins b','a.op_user_id=b.id','left')
            ->join('xzones c','c.id = a.zone_id','left')
            ->where($condition);
        if(!empty($uid)){
            $model = $model->where('a.user_id','=',$uid);
        }
        $res =  $model->order(['a.id' => 'asc'])
            ->select();
        return isset($res)?$res->toArray():[];
    }

    public function getCmsDatasCount($uid){
        $condition = ['a.status'=>'1'];
        $model = $this
            ->alias('a')
            ->field("a.*")
            ->where($condition);
        if(!empty($uid)){
            $model = $model->where('a.user_id','=',$uid);
        }
        return $model->count();
    }

    public function getCmsData($userID,$zoneID){
        $res = $this->alias('a')
            ->leftJoin('xadmins b','b.id = a.op_user_id')
            ->where('a.user_id',$userID)
            ->where('a.zone_id',$zoneID)
            ->field('a.*,b.user_name as op_user')
            ->find();
        return isset($res)?$res->toArray():[];
    }

    public function getCmsDataByDay($userID,$zoneID,$day){
        $res = $this->alias('a')
            ->leftJoin('xadmins b','b.id = a.op_user_id')
            ->where('a.user_id',$userID)
            ->where('a.zone_id',$zoneID)
            ->where('a.day',$day)
            ->field('a.*,b.user_name as op_user')
            ->find();
        return isset($res)?$res->toArray():[];
    }

    public function attendUser($userID,$zoneID,$opUserID,$checkinTime,$checkinStatus,$day,$eventID)
    {
        $res = $this->where('user_id',$userID)
            ->where('zone_id',$zoneID)
            ->where('day',$day)
            ->where('event_id',$eventID)
            ->where('status',1)
            ->find();
        if(empty($res)){
            $this->insert([
                'user_id'=>$userID,
                'zone_id'=>$zoneID,
                'status'=>1,
                'checkin_status'=>$checkinStatus,
                'op_user_id'=>$opUserID,
                'event_id'=>$eventID,
                'day'=>$day,
                'checkin_time'=>!empty($checkinTime)?$checkinTime:null,
                'create_time'=>date('Y-m-d H:i:s',time())
            ]);
            return ['status'=>200,'message'=>'attend successfully!'];
        }else{
            $this->where('id',$res['id'])->update([
                'checkin_status'=>$checkinStatus,
                'op_user_id'=>$opUserID,
                'checkin_time'=>!empty($checkinTime)?$checkinTime:null,
                'update_time'=>date('Y-m-d H:i:s',time())
            ]);
            return ['status'=>200,'message'=>'already attend!'];
        }
    }

    public function unAttendUser($userID,$zoneID,$day,$eventID)
    {
        $res = $this->where('user_id',$userID)
            ->where('zone_id',$zoneID)
            ->where('day',$day)
            ->where('event_id',$eventID)
            ->delete();
        return ['status'=>200,'message'=>'ok'];
    }

    public function getCmsDataByEvent($eventID){
        $res = Db::name('xtracks')->alias('a')
            ->leftJoin('xadmins b','b.id = a.op_user_id')
            ->where('a.event_id',$eventID)
            ->where('a.status',1)
            ->field('a.*,b.user_name as op_user')
            ->select();
        return isset($res)?$res:[];
    }
}