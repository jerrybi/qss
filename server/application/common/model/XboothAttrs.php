<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\XboothAttr;
use think\Db;

class XboothAttrs extends BaseModel
{
    private $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new xboothAttr();
    }

    public function updateCmsData($input,$id = 1)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $this->save(['status'=>0],['id'=>$id]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'booth',$id,'remove booth');
        } else {
            $saveData = [
                'key' => isset($input['key'])?$input['key']:'',
                'name' => isset($input['name'])?$input['name']:'',
                'label' => isset($input['label'])?$input['label']:'',
                'default' => isset($input['default'])?$input['default']:'',
                'min' => isset($input['min'])?$input['min']:null,
                'max' => isset($input['max'])?$input['max']:null,
                'options' => isset($input['options'])?$input['options']:null,
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
//            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
//            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
//            if ($validateRes['tag']) {
//                $saveTag = $this->save($saveData, ['id' => $id]);
//                if ($saveTag) {
//                    insertCmsOpLogs($saveTag, 'booth', $id, 'booth update');
//                }
//                $validateRes['tag'] = $saveTag;
//                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
//            }
            $res = $this->where(['key' => $id,'event_id'=>$input['event_id']])->find();
            if(empty($res)){
                $saveTag = $this->save($saveData);
            }else{
                $saveTag = $this->save($saveData, ['key' => $id,'event_id'=>$input['event_id']]);
            }
            if ($saveTag) {
                insertCmsOpLogs($saveTag, 'booth', $id, 'booth update');
            }
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
        $res = Db::name('xbooth_attrs')
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.key', '%' . $search . '%')
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
        $count = Db::name('xbooth_attrs')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.key', '%' . $search . '%')
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
        $res = Db::name('xbooth_attrs')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getCmsList($eventId=null){
        $res = Db::name('xbooth_attrs')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xbooth_attrs')->where('id',$id)->find();
    }

    public function getDataByKey($key='',$eventId=''){
        return Db::name('xbooth_attrs')->where('key',$key)->where('event_id',$eventId)->find();
    }
}