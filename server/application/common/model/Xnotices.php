<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\Xnotice;
use think\Db;

class Xnotices extends BaseModel
{
    protected $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xnotice();
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
                'title' => isset($input['title']) ? $input['title'] : '',
                'content' => isset($input['content']) ?base64_decode($input['content']) : '',
                'event_id' => isset($input['event_id'])?$input['event_id']:'',
                'sort' => isset($input['sort'])?$input['sort']:0
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                $saveTag = $this->save($saveData, ['id' => $id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag, 'notice', $id, 'notice update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null)
    {
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db::name('xnotices')
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.title|a.content', '%' . $search . '%')
            ->where($condition)
            ->order(['a.sort' => 'desc'])
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
        $count = Db::name('xnotices')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.title|a.content', '%' . $search . '%')
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
        $res = Db::name('xnotices')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function addData($data)
    {
        $addData = [
            'title' => isset($data['title'])?$data['title']:'',
            'content' => isset($data['content'])?base64_decode($data['content']):'',
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'sort' => isset($data['sort'])?$data['sort']:0,
            'status' => 1
        ];
        $tokenData = ['__token__' => isset($data['__token__']) ? $data['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            $tag = $this->save($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'notice',$this->getLastInsID(),'add notice');
            }
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function getCmsList($eventId=null){
        $res = Db::name('xnotices')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->order('sort','desc')
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xnotices')->where('id',$id)->find();
    }
}