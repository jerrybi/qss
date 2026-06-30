<?php

namespace app\common\model;

use app\common\validate\XlocationGroup;
use think\Db;
use \think\Model;
use app\common\lib\Tools;
use app\common\lib\MyRedis;

/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/1/11
 * Time: 16:45
 */
class XlocationGroups extends BaseModel
{
    // 设置当前模型对应的完整数据表名称
    protected $autoWriteTimestamp = 'datetime';
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new XlocationGroup();
    }

    /**
     * 获取所有的文章
     * @return array
     */
    public function getList($event_id="")
    {
        $map[] = ['a.status','=','1'];
        if(!empty($event_id)){
            $map[] = ['a.event_id','=',$event_id];
        }
        $res = $this
            ->field("a.*")
            ->alias('a')//给主表取别名
            ->where($map)
            ->select();
        return isset($res) ? $res->toArray() : [];
    }

    /**
     * 根据文章ID 获取文章详情
     * @param $id
     * @return array
     */
    public function getInfoByID($id)
    {
        $res = [];
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.status','1')
            ->where("a.id = '" . $id."'")
            ->find();
        return isset($res) ? $res->toArray() : [];
    }

    /**
     * 后台获取文章数据列表
     * @param $curr_page
     * @param int $limit
     * @param null $search
     * @return array
     */
    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null)
    {
        $res = $this
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where('a.status','1')
            ->order(['a.list_order' => 'desc', 'a.created_at' => 'desc'])
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsDatasCount($search = null)
    {
        $count = $this
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where('a.status','1')
            ->count();
        return $count;
    }

    public function getCmsDatasCapacityForPage($curr_page, $limit = 1, $param = null)
    {
        $where[] = ['a.status','=',1];
        if(!empty($param)){
            if(!empty($param['str_search'])){
                $where[] = ['a.name','like','%'.$param['str_search'].'%'];
            }
            if(!empty($param['event_id'])){
                $where[] = ['a.event_id','=',$param['event_id']];
            }
        }
        $res = $this
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','a.event_id = e.id')
            ->where($where)
            ->order(['a.list_order' => 'asc'])
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsDatasCapacityCount($param = null)
    {
        $where[] = ['a.status','=',1];
        if(!empty($param)){
            if(!empty($param['str_search'])){
                $where[] = ['a.name','like','%'.$param['str_search'].'%'];
            }
            if(!empty($param['event_id'])){
                $where[] = ['a.event_id','=',$param['event_id']];
            }
        }
        $count = $this
            ->alias('a')
            ->field('a.id')
            ->where($where)
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
        $res = $this
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->where('a.id', $id)
            ->find();
        return isset($res) ? $res->toArray() : [];
    }
    
    /**
     * 更新文章内容
     * @param $input
     * @param int $id
     * @return array
     */
    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xlocation_groups')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'LOCATION_GROUPS',$id,'remove location groups');
        } else {
            $saveData = [
                'name' => isset($input['name'])?$input['name']:'',
                'description' => isset($input['description'])?$input['description']:'',
                'capacity' => isset($input['capacity'])?$input['capacity']:50,
                'event_id' => isset($input['event_id'])?$input['event_id']:'',
                'list_order' => isset($input['list_order'])?$input['list_order']:0,
                'updated_at' => date('Y-m-d H:i:s', time())
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                //检查名称是否已经存在
                $result = $this->where('name',$input['name'])->find();
                if(!empty($result) && $result['id'] != $id){
                    return ['tag' => false, 'message' => 'Location Group Name Exist!'];
                }
                $saveTag = $this
                    ->where('id', $id)
                    ->update($saveData);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag,'LOCATION_GROUPS',$id,'location groups');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
                //如果capacity的值发生了改变，则删除该location group的缓存
//                if($result['capacity'] != $saveData['capacity']){
//                    MyRedis::getInstance()->del($result['event_id'].$result['id']);
//                }
            }
        }
        return $validateRes;
    }

    public function updateCapacity($input,$id = 0)
    {
        $result = $this->getInfoByID($id);
        $saveData = ['capacity'=>$input['capacity']];
        $saveTag = $this->where('id', $id)->update($saveData);
        if ($saveTag) {
            insertCmsOpLogs($saveTag,'LOCATION_GROUPS',$id,'location groups');
        }
        $validateRes['tag'] = $saveTag;
        $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        //如果capacity的值发生了改变，则删除该location group的缓存
        if($result['capacity'] != $saveData['capacity']){
            MyRedis::getInstance()->del($result['event_id'].$result['id']);
        }
        return $validateRes;
    }
    
    /**
     * 进行新文章的添加操作
     * @param $data
     * @return array
     */

    public function addData($data)
    {

        $addData = [
            'id' => Tools::create_guid(),
            'name' => isset($data['name'])?$data['name']:'',
            'description' => isset($data['description'])?$data['description']:'',
            'capacity' => isset($data['capacity'])?$data['capacity']:0,
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'list_order' => isset($data['list_order'])?$data['list_order']:0,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s', time()),
            'updated_at' => date('Y-m-d H:i:s', time())
        ];
        $tokenData = ['__token__' => isset($data['__token__']) ? $data['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            //检查名称是否已经存在
            $result = $this->where('name',$data['name'])->find();
            if(!empty($result)){
                return ['tag' => false, 'message' => 'Location Group Name Exist!'];
            }
            $tag = $this->insert($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'LOCATION_GROUPS',$this->getLastInsID(),'add location groups');
            }
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'Add success' : 'Add failed';
        }
        return $validateRes;
    }
}