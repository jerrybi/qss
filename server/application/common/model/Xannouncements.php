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
use app\common\validate\Xannouncement;
use app\common\validate\Xconfig;
use think\Db;

class Xannouncements extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xannouncement();
    }

    public function updateCmsData($input,$id = 1)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $this->save(['status'=>0],['id'=>$id]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'announcement',$id,'remove announcement');
        } else {
            $saveData = [
                'content' => isset($input['content']) ? $input['content'] : '',
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                $saveTag = $this->save($saveData, ['id' => $id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag, 'announcement', $id, 'announcement update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    /**
     * 后台获取文章数据列表
     * @param $curr_page
     * @param int $limit
     * @param null $search
     * @return array
     */
    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null)
    {
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = $this
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.content', '%' . $search . '%')
            ->where($condition)
            ->order(['a.update_time' => 'desc'])
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
            ->whereLike('a.content', '%' . $search . '%')
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
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function addData($data)
    {
        $addData = [
            'content' => isset($data['content'])?$data['content']:'',
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'status' => 1
        ];
        $tokenData = ['__token__' => isset($data['__token__']) ? $data['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            $tag = $this->save($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'Announcement',$this->getLastInsID(),'add announcement');
            }
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function getTopAnnouncements($eventId){
         $res = $this->where('event_id',$eventId)->where('status',1)
            ->order('update_time','desc')->limit(3)->select();
         if(!empty($res)){
             foreach($res as $key=>$item){
                 $item['post_date'] = date('d/M/Y',strtotime($item['update_time']));
                 $res[$key] = $item;
             }
         }
         return $res;
    }
}