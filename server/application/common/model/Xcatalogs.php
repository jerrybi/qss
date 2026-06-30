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

class Xcatalogs extends BaseModel
{
    protected $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xcatalog();
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
                'name' => isset($input['name'])?$input['name']:'',
                'type' => isset($input['type'])?$input['type']:'',
                'category' => isset($input['category'])?$input['category']:'',
                'sub_category' => isset($input['sub_category'])?$input['sub_category']:'',
                'description' => isset($input['description'])?$input['description']:'',
                'advanced_rate' => isset($input['advanced_rate'])?$input['advanced_rate']:'',
                'standard_rate' => isset($input['standard_rate'])?$input['standard_rate']:'',
                'have_onsite_rate' => isset($input['have_onsite_rate'])?$input['have_onsite_rate']:0,
                'onsite_rate' => isset($input['onsite_rate'])?$input['onsite_rate']:'',
                'logo' => isset($input['logo'])?$input['logo']:'',
                'have_deadline' => isset($input['have_deadline'])?$input['have_deadline']:0,
                'deadline' => isset($input['deadline'])?$input['deadline']:date('Y-m-d',time()),
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                $saveTag = $this->save($saveData, ['id' => $id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag, 'catalog', $id, 'catalog update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null,$type='Amenity')
    {
        $condition = ['a.status'=>'1','a.type'=>$type];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db::name('xcatalogs')
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
    public function getCmsDatasCount($search = null,$type='Amenity')
    {
        $count = Db::name('xcatalogs')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name', '%' . $search . '%')
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
        $res = Db::name('xcatalogs')
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
            'type' => isset($input['type'])?$input['type']:'',
            'category' => isset($input['category'])?$input['category']:'',
            'sub_category' => isset($input['sub_category'])?$input['sub_category']:'',
            'description' => isset($input['description'])?$input['description']:'',
            'advanced_rate' => isset($input['advanced_rate'])?$input['advanced_rate']:'',
            'standard_rate' => isset($input['standard_rate'])?$input['standard_rate']:'',
            'have_onsite_rate' => isset($input['have_onsite_rate'])?$input['have_onsite_rate']:0,
            'onsite_rate' => isset($input['onsite_rate'])?$input['onsite_rate']:'',
            'logo' => isset($input['logo'])?$input['logo']:'',
            'have_deadline' => isset($input['have_deadline'])?$input['have_deadline']:0,
            'deadline' => isset($input['deadline'])?$input['deadline']:date('Y-m-d',time()),
            'event_id' => isset($input['event_id'])?$input['event_id']:'',
            'create_time'=>date('Y-m-d H:i:s',time()),
            'status' => 1
        ];
        $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            $tag = $this->insertGetId($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'catalog',$this->getLastInsID(),'add catalog');
            }
            $validateRes['tag'] = $tag>0?1:0;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function getCmsList($eventId=null,$type='Amenity'){
        $res = Db::name('xcatalogs')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->where('type',$type)
            ->select();
        return $res;
    }

    public function getCmsListByCategory($eventId=null,$category='',$subCategory='',$type='Amenity'){
//        $res = Db::name('xcatalogs')
//            ->where('event_id',$eventId)
//            ->where('category',$category)
//            ->where('sub_category',$subCategory)
//            ->where('status',1)
//            ->where('type',$type)
//            ->select();
        $sql = "select * from ".config('database.prefix')."xcatalogs";
        $sql .= " where event_id = '".$eventId."'";
        $sql .= " and category = '".$category."'";
        $sql .= " and sub_category = '".$subCategory."'";
        $sql .= " and status = 1";
        $sql .= " and type = '".$type."'";
        $sql .= " order by mid(name,1,2) asc,mid(name,3,10)+1 asc";
        $res = Db::query($sql);
        return $res;
    }

    public function getData($id=1){
        return Db::name('xcatalogs')->where('id',$id)->find();
    }

    public function getUsedCmsList($eventId='',$type='Amenity'){
        $article = Db::name('xcatalogs')->group('category')->field('category')
            ->where(['event_id'=>$eventId,'status'=>1,'type'=>$type])->select();
        return $article;
    }
}