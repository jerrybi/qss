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

class XedmTemplates extends BaseModel
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
        $res = Db('xedm_templates')
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
        $count = Db('xedm_templates')
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
            'subject' => isset($data['subject'])?urldecode(base64_decode($data['subject'])):'',
            'content' => isset($data['content'])?urldecode(base64_decode($data['content'])):0,
            'sort' => isset($data['sort'])?$data['sort']:0,
            'text_attr' => isset($data['text_attr'])?$data['text_attr']:'',
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'status' => 1
        ];
        //检查名称是否已经存在
        $result = Db::name('xedm_templates')->where('name',$data['name'])
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
            Db::name('xedm_templates')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
        } else {
            $saveData = [
                'name' => isset($input['name'])?urldecode(base64_decode($input['name'])):'',
                'subject' => isset($input['subject'])?urldecode(base64_decode($input['subject'])):'',
                'content' => isset($input['content'])?urldecode(base64_decode($input['content'])):'',
                'sort' => isset($input['sort'])?$input['sort']:0,
                'text_attr' => isset($input['text_attr'])?$input['text_attr']:'',
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            //检查名称是否已经存在
            $result = Db('xedm_templates')->where('name',$input['name'])
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
        $res = Db::name('xedm_templates')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xedm_templates')->where('id',$id)->find();
    }

    public function getDataByName($eventId,$name){
        return Db::name('xedm_templates')
            ->where('status',1)
            ->where('event_id',$eventId)
            ->where('name',$name)
            ->find();
    }

    public function getList($eventId){
        $res = Db::name('xedm_templates')
            ->where('status',1)
            ->where('event_id',$eventId)
            ->field('id,name')
            ->order('sort asc')
            ->select();
        return !empty($res)?$res:[];
    }

    public function duplicate($oldEventId, $newEventId){
        $old = Db::name('xedm_templates')
            ->where('status',1)
            ->where('event_id',$oldEventId)
            ->field('*')
            ->order('sort asc')
            ->select();
        if($old){
            foreach($old as $v){
                $v['event_id'] = $newEventId;
                unset($v['id']);
                $this->insert($v);
            }
        }
    }
}