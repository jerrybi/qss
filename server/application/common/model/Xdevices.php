<?php

namespace app\common\model;

use think\Db;
use \think\Model;
use app\common\lib\Tools;

/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/1/11
 * Time: 16:45
 */
class Xdevices extends BaseModel
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
    public function getList($user_id="")
    {
        if(!empty($user_id)){
            $map[] = ['a.user_id','=',$user_id];
        }
        $res = $this
            ->field("a.*")
            ->alias('a')//给主表取别名
            ->where($map)
            ->select();
        return isset($res) ? $res->toArray() : [];
    }


    public function getInfoByUserId($userId,$uuid="")
    {
        $map[] = ['a.user_id','=',$user_id];
        if(!empty($uuid)){
            $map[] = ['a.uuid','=',$uuid];
        }
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where($map)
            ->select();
        return $res;
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
            ->field('a.*')
            ->whereLike('a.name', '%' . $search . '%')
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
            ->count();
        return $count;
    }
    
    /**
     * 进行新文章的添加操作
     * @param $data
     * @return array
     */

    public function addData($data)
    {

        $addData = [
            'user_id' => isset($data['user_id'])?$data['user_id']:0,
            'uuid' => isset($data['uuid'])?$data['uuid']:'',
            'type' => isset($data['type'])?$data['type']:'',
            'name' => isset($data['name'])?$data['name']:'',
            'os' => isset($data['os'])?$data['os']:'',
            'ip' => isset($data['ip'])?$data['ip']:'',
            'agent' => isset($data['agent'])?$data['agent']:'',
            'screen_size' => isset($data['screen_size'])?$data['screen_size']:'',
            'viewport_size' => isset($data['viewport_size'])?$data['viewport_size']:'',
            'created_at' => date('Y-m-d H:i:s', time())
        ];
       //检查名称是否已经存在
        $result = $this->where(['user_id'=>$data['user_id'],'uuid'=>$data['uuid']])->find();
        if(!empty($result)){
            return ['tag' => false, 'message' => 'User uuid Exist!'];
        }
        $tag = $this->insert($addData);
        if ($tag) {
            insertCmsOpLogs($tag,'DEVICES',$this->getLastInsID(),'add devices');
        }
        $validateRes['tag'] = $tag;
        $validateRes['message'] = $tag ? 'Add success' : 'Add failed';
        return $validateRes;
    }
}