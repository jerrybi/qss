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

class XuserTables extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsDatasForPage($user_id,$curr_page = 1, $page_limit = 1, $search = null){
        $condition = ['a.status'=>'1','a.user_id'=>$user_id];
        $res = Db('xuser_tables')
            ->alias('a')
            ->field('a.*,z.name as zone_name,t.name as table_name')
            ->join('xtables t','t.id = a.table_id')
            ->join('xzones z','z.id = a.zone_id')
            ->where('t.name|z.name', 'like', '%' . $search . '%')
            ->where($condition)
            ->order(['a.id'=>'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res:[];
    }

    public function getCmsDatasCount($user_id,$search = null){
        $condition = ['a.status'=>'1','a.user_id'=>$user_id];
        $count = Db('xuser_tables')
            ->alias('a')
            ->field("a.id")
            ->join('xtables t','t.id = a.table_id')
            ->join('xzones z','z.id = a.zone_id')
            ->where('t.name|z.name', 'like', '%' . $search . '%')
            ->where($condition)
            ->count();
        return $count;
    }

    public function addData($data)
    {
        $userID = isset($data['user_id'])?$data['user_id']:0;
        $tableID = isset($data['table_id'])?$data['table_id']:0;
        $addData = [
            'id'=>Tools::create_guid(),
            'user_id' => $userID,
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'zone_id' => isset($data['zone_id'])?$data['zone_id']:0,
            'table_id' => $tableID,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s',time())
        ];
        //检查名称是否已经存在
        $result = Db('xuser_tables')
            ->where('user_id',$userID)
            ->where('table_id',$tableID)
            ->where('status',1)
            ->find();
        if(!empty($result)){
            return ['tag' => false, 'message' => 'Table is added!'];
        }
        $tag = $this->insertGetId($addData);
        $validateRes['tag'] = $tag>0?1:0;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function updateCmsData($data,$id = 0)
    {
        $opTag = isset($data['tag']) ? $data['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xuser_tables')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
        } else {
            $userID = isset($data['user_id'])?$data['user_id']:0;
            $tableID = isset($data['table_id'])?$data['table_id']:0;
            $addData = [
                'zone_id' => isset($data['zone_id'])?$data['zone_id']:0,
                'table_id' => $tableID,
                'update_time' => date('Y-m-d H:i:s',time())
            ];
            //检查名称是否已经存在
            $result = Db('xuser_tables')
                ->where('user_id',$userID)
                ->where('table_id',$tableID)
                ->where('status',1)
                ->find();
            if(!empty($result) && $result['id'] != $id){
                return ['tag' => false, 'message' => 'Table Exist!'];
            }
            $saveTag = $this->save($addData,['id'=>$id]);
            $validateRes['tag'] = $saveTag;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }

    public function changeData($data)
    {
        $ids = isset($data['ids'])?$data['ids']:'';
        $eventID = isset($data['event_id'])?$data['event_id']:'';
        $zoneID = isset($data['zone_id'])?$data['zone_id']:0;
        $tableID = isset($data['table_id'])?$data['table_id']:0;
        $arr = explode("|",$ids);
        foreach($arr as $id){
            // 先删除该用户绑定的所有zone和table
            Db('xuser_tables')
                ->where('user_id',$id)
                ->where('event_id',$eventID)
                ->where('status',1)
                ->delete();
            //添加当前zone和table
            $addData = [
                'id'=>Tools::create_guid(),
                'user_id' => $id,
                'event_id' => $eventID,
                'zone_id' => $zoneID,
                'table_id' => $tableID,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s',time())
            ];
            $tag = $this->insertGetId($addData);
        }
        $validateRes['tag'] = 1;
        $validateRes['message'] = 'change successfully';
        return $validateRes;
    }

    public function getDataList($userID){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->leftJoin('xzones b','a.zone_id=b.id')
            ->field('a.*,b.name as zone_name')
            ->where($where)
            ->where('a.user_id',$userID)
            ->order(['a.id'=>'asc'])
            ->select();
        return $res;
    }

    public function getCmsDataByID($id){
        $res = $this->where('id',$id)->find();
        return isset($res)?$res->toArray():[];
    }

    public function getCountByZone($eventId,$zoneId){
        return Db::name('xuser_tables')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->where('a.event_id',$eventId)
            ->where('a.status',1)
            ->where('a.zone_id',$zoneId)
            ->where('b.status',1)
            ->count();
    }

    public function getUsersByZone($eventId,$zoneId,$day){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->join('xuser_status b','a.user_id = b.user_id')
            ->where('a.event_id',$eventId)
            ->where('a.zone_id',$zoneId)
            ->where('b.day',$day)
            ->where('b.status',1)
            ->field('b.user_id as id,b.checkin_status')
            ->select();
        return isset($res)?$res:[];
    }

    public function getUsersByTrackZone($eventId,$zoneId,$day){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->join('xtracks b','a.user_id = b.user_id and a.zone_id = b.zone_id')
            ->where('a.event_id',$eventId)
            ->where('a.zone_id',$zoneId)
            ->where('b.day',$day)
            ->where('b.status',1)
            ->field('b.user_id as id,b.checkin_status')
            ->select();
        return isset($res)?$res:[];
    }

    public function getCheckedInCountByZone($eventId,$zoneId,$day){
        return Db::name('xuser_tables')
            ->alias('a')
            ->join('xuser_status b','a.user_id = b.user_id')
            ->where('a.event_id',$eventId)
            ->where('a.status',1)
            ->where('a.zone_id',$zoneId)
            ->where('b.day',$day)
            ->where('b.checkin_status',1)
            ->where('b.status',1)
            ->count();
    }

    public function getCheckedInCountByTrackZone($eventId,$zoneId,$day){
        return Db::name('xuser_tables')
            ->alias('a')
            ->join('xtracks b','a.user_id = b.user_id and a.zone_id = b.zone_id')
            ->where('a.event_id',$eventId)
            ->where('a.status',1)
            ->where('a.zone_id',$zoneId)
            ->where('b.day',$day)
            ->where('b.checkin_status',1)
            ->where('b.status',1)
            ->count();
    }

    public function getCheckedInUsersByZone($eventId,$zoneId){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->where('a.event_id',$eventId)
            ->where('a.zone_id',$zoneId)
            ->where('b.checkin_status',1)
            ->where('b.status',1)
            ->field('b.id')
            ->select();
        return isset($res)?$res:[];
    }

    public function getCountByTable($eventId,$tableId){
        return Db::name('xuser_tables')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->where('a.event_id',$eventId)
            ->where('a.table_id',$tableId)
            ->where('b.status',1)
            ->count();
    }

    public function getUsersByTable($eventId,$tableId,$day){
        $res =  Db::name('xuser_tables')
            ->alias('a')
            ->join('xuser_status b','a.user_id = b.user_id')
            ->where('a.event_id',$eventId)
            ->where('a.table_id',$tableId)
            ->where('b.day',$day)
            ->where('b.status',1)
            ->field('b.user_id as id,b.checkin_status')
            ->select();
        return isset($res)?$res:[];
    }

    public function getUsersByTrackTable($eventId,$tableId,$day){
        $res =  Db::name('xuser_tables')
            ->alias('a')
            ->join('xtracks b','a.user_id = b.user_id and a.zone_id = b.zone_id')
            ->where('a.event_id',$eventId)
            ->where('a.table_id',$tableId)
            ->where('b.day',$day)
            ->where('b.status',1)
            ->field('b.user_id as id,b.checkin_status')
            ->select();
        return isset($res)?$res:[];
    }

    public function getCheckedInCountByTable($eventId,$tableId,$day){
        return Db::name('xuser_tables')
            ->alias('a')
            ->join('xuser_status b','a.user_id = b.user_id')
            ->where('a.event_id',$eventId)
            ->where('a.table_id',$tableId)
            ->where('b.day',$day)
            ->where('b.checkin_status',1)
            ->where('b.status',1)
            ->count();
    }

    public function getCheckedInCountByTrackTable($eventId,$tableId,$day){
        return Db::name('xuser_tables')
            ->alias('a')
            ->join('xtracks b','a.user_id = b.user_id and a.zone_id = b.zone_id')
            ->where('a.event_id',$eventId)
            ->where('a.table_id',$tableId)
            ->where('b.day',$day)
            ->where('b.checkin_status',1)
            ->where('b.status',1)
            ->count();
    }

    public function getCheckedInUsersByTable($eventId,$tableId){
        $res =  Db::name('xuser_tables')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->where('a.event_id',$eventId)
            ->where('a.table_id',$tableId)
            ->where('b.checkin_status',1)
            ->where('b.status',1)
            ->field('b.id')
            ->select();
        return isset($res)?$res:[];
    }

    public function getTableByZone($eventID,$userID,$zone){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->join('xzones z','a.zone_id = z.id')
            ->join('xtables t','a.table_id = t.id')
            ->where('a.event_id',$eventID)
            ->where('a.user_id',$userID)
            ->where('a.status',1)
            ->where('z.name',$zone)
            ->field('t.name as table_name')
            ->find();
        return isset($res['table_name'])?$res['table_name']:'';
    }

    public function getUserTables($eventID,$userID){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->join('xzones z','a.zone_id = z.id')
            ->join('xtables t','a.table_id = t.id')
            ->where('a.event_id',$eventID)
            ->where('a.user_id',$userID)
            ->where('a.status',1)
            ->field('t.name as table_name,z.name as zone_name')
            ->select();
        return $res;
    }

    public function getUserZone($eventID,$userID){
        $res = $this->getUserTables($eventID,$userID);
        return !empty($res)?$res[0]['zone_name']:'';
    }

    public function getUserTablesByEvent($eventID){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->join('xzones z','a.zone_id = z.id')
            ->join('xtables t','a.table_id = t.id')
            ->where('a.event_id',$eventID)
            ->where('a.status',1)
            ->field('a.user_id,t.name as table_name,z.name as zone_name')
            ->select();
        return $res;
    }

    public function getUserIdsByZone($eventId,$zoneId){
        $res = Db::name('xuser_tables')
            ->alias('a')
            ->where('a.event_id',$eventId)
            ->where('a.zone_id',$zoneId)
            ->where('a.status',1)
            ->field('distinct(a.user_id)')
            ->select();
        $ids = [];
        if($res){
            foreach($res as $v){
                $ids[] = $v['user_id'];
            }
        }
        return $ids;
    }
}