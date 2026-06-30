<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\XcatalogAttr;
use think\Db;

class XcatalogAttrs extends BaseModel
{
    private $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new xcatalogAttr();
    }

    public function updateCmsData($input,$id = 1)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $this->save(['status'=>0],['id'=>$id]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'catalog',$id,'remove catalog');
        } else {
            $saveData = [
                'type' => isset($input['type'])?$input['type']:'',
                'key' => isset($input['key'])?$input['key']:'',
                'name' => isset($input['name'])?$input['name']:'',
                'label' => isset($input['label'])?$input['label']:'',
                'default' => isset($input['default'])?$input['default']:'',
                'min' => isset($input['min'])?$input['min']:null,
                'max' => isset($input['max'])?$input['max']:null,
                'options' => isset($input['options'])?$input['options']:null,
                'description' => isset($input['description'])?$input['description']:'',
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $res = $this->where(['key' => $id,'event_id'=>$input['event_id'],'type'=>$input['type']])->find();
            if(empty($res)){
                $saveTag = $this->save($saveData);
            }else{
                $saveTag = $this->save($saveData, ['key' => $id,'event_id'=>$input['event_id'],'type'=>$input['type']]);
            }
            if ($saveTag) {
                insertCmsOpLogs($saveTag, 'catalog', $id, 'catalog update');
            }
            $validateRes['tag'] = $saveTag;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null,$type)
    {
        $condition = ['a.status'=>'1','a.type'=>$type];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db::name('xcatalog_attrs')
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
    public function getCmsDatasCount($search = null,$type)
    {
        $count = Db::name('xcatalog_attrs')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.key', '%' . $search . '%')
            ->where('a.status','1')
            ->where('a.type',$type)
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
        $res = Db::name('xcatalog_attrs')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getCmsList($eventId=null,$type){
        $res = Db::name('xcatalog_attrs')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->where('type',$type)
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xcatalog_attrs')->where('id',$id)->find();
    }

    public function getDataByKey($key='',$eventId='',$type){
        return Db::name('xcatalog_attrs')->where('key',$key)
            ->where('event_id',$eventId)
            ->where('type',$type)
            ->find();
    }

    public function getSubCategories($eventId='',$category,$type){
        $res = $this->getDataByKey('sub_category',$eventId,$type);
        $options = [];
        if(!empty($res)){
            $str = $res['options'];
            if(!empty($str)){
                $data = json_decode($str,true);
                if(isset($data[$category])){
                    $options = $data[$category];
                }
            }
        }
        return $options;
    }
}