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

class Xtables extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xtable();
    }

    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1, $search = null,$eventId = null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db('xtables')
            ->alias('a')
            ->field('a.*,e.name as event_name,z.name as zone')
            ->join('xevents e','e.id = a.event_id')
            ->join('xzones z','z.id = a.zone_id')
            ->where('a.name', 'like', '%' . $search . '%')
            ->where($condition)
            ->order(['a.sort' => 'asc','a.id'=>'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res:[];
    }

    public function getCmsDatasCount($search = null,$eventId=null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $count = Db('xtables')
            ->alias('a')
            ->field("*")
            ->where('name', 'like', '%' . $search . '%')
            ->where($condition)
            ->count();
        return $count;
    }

    public function addData($data)
    {
        $addData = [
            'name' => isset($data['name'])?urldecode(base64_decode($data['name'])):'',
            'capacity' => isset($data['capacity'])?$data['capacity']:0,
            'zone_id' => isset($data['zone_id'])?$data['zone_id']:0,
            'sort' => isset($data['sort'])?$data['sort']:0,
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'status' => 1
        ];
        $tokenData = ['__token__' => isset($data['__token__']) ? $data['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            //检查名称是否已经存在
            $result = Db('xtables')->where('name',$data['name'])->where('event_id',$data['event_id'])->find();
            if(!empty($result)){
                return ['tag' => false, 'message' => 'Form Name Exist!'];
            }
            $tag = $this->insertGetId($addData);
            $validateRes['tag'] = $tag>0?1:0;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xtables')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'FORM',$id,'remove form');
        } else {
            $saveData = [
                'name' => isset($input['name'])?urldecode(base64_decode($input['name'])):'',
                'capacity' => isset($input['capacity'])?$input['capacity']:0,
                'zone_id' => isset($input['zone_id'])?$input['zone_id']:0,
                'sort' => isset($input['sort'])?$input['sort']:0,
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
//            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
//            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
//            if ($validateRes['tag']) {
                //检查名称是否已经存在
                $result = Db('xtables')->where('name',$input['name'])->where('event_id',$input['event_id'])->find();
                if(!empty($result) && $result['id'] != $id){
                    return ['tag' => false, 'message' => 'Name Exist!'];
                }
                $saveTag = $this->save($saveData,['id'=>$id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag,'Form',$id,'Form update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
//            }
        }
        return $validateRes;
    }

    public function getCmsDataByID($id)
    {
        $res = Db::name('xtables')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getSimpleList($zoneId=null){
        $res = Db::name('xtables')
            ->field('id,name')
            ->where('zone_id',$zoneId)
            ->where('status',1)
            ->order(['sort'=>'asc','name'=>'asc','id'=>'asc'])
            ->select();
        return $res;
    }

    public function getDataList($eventId=null){
        $where[] = ['status','=',1];
        if(!empty($eventId)){
            $where[] = ['event_id','=',$eventId];
        }
        $res = Db::name('xtables')
            ->field('*')
            ->where($where)
            ->order(['sort'=>'asc','id'=>'asc'])
            ->select();
        return $res;
    }


    public function getData($id=1){
        return Db::name('xtables')->where('id',$id)->find();
    }

    public function getCapacity($id){
        return $this->where('id',$id)->value('capacity');
    }

    public function getIdByName($event_id,$name,$zoneID){
        return Db::name('xtables')
            ->where('event_id',$event_id)
            ->where('name',$name)
            ->where('zone_id',$zoneID)
            ->where('status',1)
            ->value('id');
    }

    public function getDataByName($event_id,$name){
        return Db::name('xtables')
            ->where('event_id',$event_id)
            ->where('name',$name)
            ->where('status',1)
            ->field('id,zone_id')
            ->select();
    }

    public function getFirstTable($eventID,$zoneID){
        return Db::name('xtables')
            ->where('event_id',$eventID)
            ->where('zone_id',$zoneID)
            ->where('status',1)
            ->find();
    }

    public function duplicate($oldEventId, $newEventId, $zoneId){
        $old = $this->getDataList($oldEventId);
        if($old){
            foreach($old as $v){
                $v['event_id'] = $newEventId;
                $v['zone_id'] = $zoneId;
                unset($v['id']);
                $this->insert($v);
            }
        }
    }
}