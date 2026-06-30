<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\XdataField;
use think\Db;

class XdataFields extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    protected $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new XdataField();
    }

    public function updateCmsData($input,$id = 1)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $this->save(['status'=>0],['id'=>$id]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'data field',$id,'remove booth');
        } else {
            $fixedField = isset($input['fixed_field'])?$input['fixed_field']:0;
            if($fixedField){
                $name = isset($input['name_select'])?$input['name_select']:'';
            }else{
                $name = isset($input['name_input'])?$input['name_input']:'';
            }
            $key = isset($input['key'])?$input['key']:'';
            if(empty($key)){
                $key = $this->getKeyByName($name);
            }
            $tblName = isset($input['table_name'])?$input['table_name']:'';
            $eventID = isset($input['event_id'])?$input['event_id']:'';
            $options = isset($input['options'])?trim($input['options']):'';
            $saveData = [
                'name' => $name,
                'key' => $key,
                'table_name' => $tblName,
                'options' => $options,
                'type' => isset($input['type'])?$input['type']:'',
                'default' => isset($input['default'])?trim($input['default']):'',
                'placeholder' => isset($input['placeholder'])?$input['placeholder']:'',
                'required' => isset($input['required'])?$input['required']:0,
                'readonly' => isset($input['readonly'])?$input['readonly']:0,
                'exhibitor_visible' => isset($input['exhibitor_visible'])?$input['exhibitor_visible']:0,
                'fixed_field' => $fixedField,
                'width' => isset($input['width'])?$input['width']:0,
                'sort' => isset($input['sort'])?$input['sort']:0,
                'event_id' => $eventID
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                $saveTag = $this->save($saveData, ['id' => $id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag, 'data field', $id, 'data field update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
            if($validateRes['tag']){
                if($key == 'user_level'){
                    Db::transaction(function () use($options,$eventID) {
                        $arr = explode("\r\n",$options);
                        // delete datas that not belong to current options
                        Db::name('xlevel_settings')
                            ->where('event_id',$eventID)
                            ->where('user_level','not in',$arr)
                            ->delete();
                        foreach($arr as $v){
                            $res = Db::name('xlevel_settings')
                                ->where('event_id',$eventID)
                                ->where('user_level',$v)
                                ->where('status',1)
                                ->find();
                            if(empty($res)){
                                Db::name('xlevel_settings')
                                    ->insert([
                                        'user_level'=>$v,
                                        'background_color'=>'',
                                        'front_color'=>'',
                                        'status'=>1,
                                        'event_id'=>$eventID,
                                        'create_time'=>date('Y-m-d H:i:s',time())
                                    ]);
                            }
                        }
                    });
                }
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
        $res = Db::name('xdata_fields')
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where($condition)
            ->order('sort asc,id asc')
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res:[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsDatasCount($search = null,$eventId = null)
    {
        $model = Db::name('xdata_fields')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where('a.status','1');
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        $count = $model->count();
        return $count;
    }

    /**
     * 根据文章ID 获取文章内容
     * @param $id
     * @return array
     */
    public function getCmsDataByID($id)
    {
        $res = Db::name('xdata_fields')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getCmsDataByKey($eventID,$key)
    {
        $res = Db::name('xdata_fields')
            ->where('event_id', $eventID)
            ->where('key',$key)
            ->where('status',1)
            ->find();
        return $res;
    }

    public function addData($input)
    {
        $fixedField = isset($input['fixed_field'])?$input['fixed_field']:0;
        if($fixedField){
            $name = isset($input['name_select'])?$input['name_select']:'';
        }else{
            $name = isset($input['name_input'])?$input['name_input']:'';
        }
        $key = isset($input['key'])?$input['key']:'';
        if(empty($key)){
            $key = $this->getKeyByName($name);
        }
        $tblName = isset($input['table_name'])?$input['table_name']:'';
        $eventID = isset($input['event_id'])?$input['event_id']:'';
        $options = isset($input['options'])?trim($input['options']):'';
        $addData = [
            'name' => $name,
            'key' => $key,
            'table_name' => $tblName,
            'options' => $options,
            'type' => isset($input['type'])?$input['type']:'',
            'default' => isset($input['default'])?trim($input['default']):'',
            'placeholder' => isset($input['placeholder'])?$input['placeholder']:'',
            'required' => isset($input['required'])?$input['required']:0,
            'exhibitor_visible' => isset($input['exhibitor_visible'])?$input['exhibitor_visible']:0,
            'fixed_field' => $fixedField,
            'event_id' => $eventID,
            'width' => isset($input['width'])?$input['width']:0,
            'sort' => isset($input['sort'])?$input['sort']:0,
            'status' => 1
        ];
        $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            $tag = $this->save($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'data field',$this->getLastInsID(),'add data field');
            }
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        if($validateRes['tag']){
            if($key == 'user_level'){
                Db::transaction(function () use($options,$eventID) {
                    $arr = explode("\r\n",$options);
                    // delete datas that not belong to current options
                    Db::name('xlevel_settings')
                        ->where('event_id',$eventID)
                        ->where('user_level','not in',$arr)
                        ->delete();
                    foreach($arr as $v){
                        $res = Db::name('xlevel_settings')
                            ->where('event_id',$eventID)
                            ->where('user_level',$v)
                            ->where('status',1)
                            ->find();
                        if(empty($res)){
                            Db::name('xlevel_settings')
                                ->insert([
                                    'user_level'=>$v,
                                    'background_color'=>'',
                                    'front_color'=>'',
                                    'status'=>1,
                                    'event_id'=>$eventID,
                                    'create_time'=>date('Y-m-d H:i:s',time())
                                ]);
                        }
                    }
                });
            }
        }
        return $validateRes;
    }

    public function getCmsList($eventId=null){
        $res = Db::name('xdata_fields')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->order('sort asc,id asc')
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xdata_fields')->where('id',$id)->find();
    }

    public function getKeyByName($name) {
        $key = strtolower($name);
        $key = str_replace(" ","_",$key);
        return $key;
    }

    public function duplicate($oldEventId, $newEventId){
        $old = $this->getCmsList($oldEventId);
        if($old){
            foreach($old as $v){
                $v['event_id'] = $newEventId;
                unset($v['id']);
                $this->insert($v);
            }
        }
    }
}