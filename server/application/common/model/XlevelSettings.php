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
use app\common\validate\Xzone;
use think\Db;
use FormDesign\Formdesign;

class XlevelSettings extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null)
    {
        $res = $this
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->where('a.status','1')
            ->order(['a.user_level' => 'asc'])
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    public function getCmsDatasCount($search = null)
    {
        $count = $this
            ->alias('a')
            ->field('a.id')
            ->where('a.status','1')
            ->count();
        return $count;
    }

    public function getCmsData($userLevel, $eventId = null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db('xlevel_settings')
            ->where($condition)
            ->where('user_level',$userLevel)
            ->find();
        return $res;
    }

    public function getCmsDataById($id){
        $res = Db('xlevel_settings')
            ->where('id',$id)
            ->find();
        return $res;
    }

    public function addCmsData($input)
    {
        $addData = [
            'user_level' => isset($input['user_level'])?$input['user_level']:'',
            'background_color' => isset($input['background_color'])?$input['background_color']:0,
            'front_color' => isset($input['front_color'])?$input['front_color']:0,
            'event_id' => isset($input['event_id'])?$input['event_id']:'',
            'create_time' => date('Y-m-d H:i:s',time()),
            'status' => 1
        ];
        $tag = $this->insertGetId($addData);
        $validateRes['tag'] = $tag>0?1:0;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xlevel_settings')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
        } else {
            $saveData = [
                'user_level' => isset($input['user_level'])?$input['user_level']:'',
                'background_color' => isset($input['background_color'])?$input['background_color']:0,
                'front_color' => isset($input['front_color'])?$input['front_color']:0,
                'event_id' => isset($input['event_id'])?$input['event_id']:'',
                'update_time' => date('Y-m-d H:i:s',time())
            ];
            $saveTag = $this->save($saveData,['id'=>$id]);
            $validateRes['tag'] = $saveTag;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }
}