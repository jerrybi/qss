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
use think\Db;
use FormDesign\Formdesign;

class XcardTemplates extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1, $search = null,$eventId = null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db('xcard_templates')
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
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
        $count = Db('xcard_templates')
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
            'content1' => isset($data['content1'])?urldecode(base64_decode($data['content1'])):'',
            'content2' => isset($data['content2'])?urldecode(base64_decode($data['content2'])):'',
            'bg_width' => isset($data['bg_width'])?$data['bg_width']:0,
            'bg_height' => isset($data['bg_height'])?$data['bg_height']:0,
            'bg_size_type' => isset($data['bg_size_type'])?$data['bg_size_type']:0,
            'bg_size_index' => isset($data['bg_size_index'])?$data['bg_size_index']:0,
            'double_side' => isset($data['double_side'])?$data['double_side']:0,
            'type' => isset($data['type'])?$data['type']:'',
            'sort' => isset($data['sort'])?$data['sort']:0,
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'status' => 1
        ];
        //检查名称是否已经存在
        $result = Db::name('xcard_templates')->where('name',$data['name'])
            ->where('event_id',$data['event_id'])->find();
        if(!empty($result)){
            return ['tag' => false, 'message' => 'Form Name Exist!'];
        }
        $tag = $this->insertGetId($addData);
        $validateRes['tag'] = $tag>0?1:0;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xcard_templates')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
        } else {
            $saveData = [
                'name' => isset($input['name'])?urldecode(base64_decode($input['name'])):'',
                'content1' => isset($input['content1'])?urldecode(base64_decode($input['content1'])):'',
                'content2' => isset($input['content2'])?urldecode(base64_decode($input['content2'])):'',
                'bg_width' => isset($input['bg_width'])?$input['bg_width']:0,
                'bg_height' => isset($input['bg_height'])?$input['bg_height']:0,
                'bg_size_type' => isset($input['bg_size_type'])?$input['bg_size_type']:0,
                'bg_size_index' => isset($input['bg_size_index'])?$input['bg_size_index']:0,
                'double_side' => isset($input['double_side'])?$input['double_side']:0,
                'type' => isset($input['type'])?$input['type']:'',
                'sort' => isset($input['sort'])?$input['sort']:0,
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            //检查名称是否已经存在
            $result = Db('xcard_templates')->where('name',$input['name'])
                ->where('event_id',$input['event_id'])->find();
            if(!empty($result) && $result['id'] != $id){
                return ['tag' => false, 'message' => 'Name Exist!'];
            }
            $saveTag = $this->save($saveData,['id'=>$id]);
            $validateRes['tag'] = $saveTag;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }

    public function getCmsDataByID($id)
    {
        $res = Db::name('xcard_templates')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xcard_templates')->where('id',$id)->find();
    }

    public function getList(){
        $res = Db::name('xcard_templates')
            ->where('status',1)
            ->field('id,name')
            ->order('sort asc')
            ->select();
        return !empty($res)?$res:[];
    }

    public function getDataByType($eventId,$type){
        return Db::name('xcard_templates')
            ->where(['event_id'=>$eventId])
            ->where('status',1)
            ->where(function ($query) use ($type) {
                $query->where('type',$type)
                    ->whereOr('type','like','%|'.$type)
                    ->whereOr('type','like',$type.'|%')
                    ->whereOr('type','like','%|'.$type.'|%');
            })
            ->find();
    }
}