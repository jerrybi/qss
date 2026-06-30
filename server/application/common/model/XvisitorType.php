<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\Xcatalog;
use think\Db;

class XvisitorType extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function updateCmsData($input,$id = 1)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $this->save(['status'=>0],['id'=>$id]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
        } else {
            $saveData = [
                'name' => isset($input['name'])?$input['name']:'',
                'code' => isset($input['code'])?$input['code']:'',
                'status' => isset($input['status'])?$input['status']:1,
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $saveTag = $this->save($saveData, ['id' => $id]);
            $validateRes['tag'] = $saveTag;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null)
    {
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db::name('xvisitor_type')
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where($condition)
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res:[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsDatasCount($search = null)
    {
        $count = Db::name('xvisitor_type')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where('a.status','1')
            ->count();
        return $count;
    }

    /**
     * 根据文章ID 获取文章内容
     * @param $id
     * @return array
     */
    public function getCmsDataByID($id)
    {
        $res = Db::name('xvisitor_type')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function addData($input)
    {
        $addData = [
            'name' => isset($input['name'])?$input['name']:'',
            'code' => isset($input['code'])?$input['code']:'',
            'event_id' => isset($input['event_id'])?$input['event_id']:'',
            'create_time'=>date('Y-m-d H:i:s',time()),
            'status' => 1
        ];
        $tag = $this->insertGetId($addData);
        $validateRes['tag'] = $tag>0?1:0;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function getCmsList($eventId=null){
        $res = Db::name('xvisitor_type')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xvisitor_type')->where('id',$id)->find();
    }
}