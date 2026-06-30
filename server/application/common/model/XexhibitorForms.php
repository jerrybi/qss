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

class XexhibitorForms extends BaseModel
{

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
    /**
     * 分页获取用户数据
     * @param int $curr_page
     * @param int $page_limit
     * @param null $search
     * @param null $user_type
     * @return array
     */
    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1, $search = null,$eventId = null,$companyId=''){
        $condition = [];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        if(!empty($companyId)){
            $condition['a.company_id']=$companyId;
        }
        $res = Db::name('xexhibitor_forms')
            ->alias('a')
            ->field('a.*,e.name as event_name,f.name,f.due_date')
            ->join('xevents e','e.id = a.event_id')
            ->join('xforms f','f.id = a.form_id')
            ->where('f.name','like','%' . $search . '%')
            ->where($condition)
            ->order(['a.id' => 'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res:[];
    }

    /**
     * 获取用户数量
     * @param null $search
     * @return float|string
     */
    public function getCmsDatasCount($search = null,$eventId = null,$companyId=''){
        $condition = [];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        if(!empty($companyId)){
            $condition['a.company_id']=$companyId;
        }
        $count = Db::name('xexhibitor_forms')
            ->alias('a')
            ->join('xforms f','f.id = a.form_id')
            ->where('f.name','like','%' . $search . '%')
            ->where($condition)
            ->count();
        return $count;
    }

    public function addData($data)
    {
        $addData = [
            'company_id' => isset($data['company_id'])?$data['company_id']:0,
            'form_id' => isset($data['form_id'])?$data['form_id']:0,
            'event_id' => isset($data['event_id'])?$data['event_id']:'',
            'status' => 0
        ];
        //检查名称是否已经存在
        $result = Db::name('xexhibitor_forms')->where('company_id',$data['company_id'])
            ->where('form_id',$data['form_id'])
            ->where('event_id',$data['event_id'])
            ->find();
        if(!empty($result)){
            return ['tag' => false, 'message' => 'Exhibitor Form Exist!'];
        }
        $tag = $this->save($addData);
        if ($tag) {
            insertCmsOpLogs($tag,'EXHIBITOR FORM',$this->getLastInsID(),'add form');
        }
        $validateRes['tag'] = $tag;
        $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        return $validateRes;
    }

    public function updateCmsDataStatus($input,$id = 0)
    {
        $saveData = [
            'status' => isset($input['status'])?$input['status']:0
        ];
        $saveTag = $this->save($saveData,['id'=>$id]);
        if ($saveTag) {
            insertCmsOpLogs($saveTag,'Exhibitor Form',$id,'Form update');
        }
        $validateRes['tag'] = $saveTag;
        $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        return $validateRes;
    }

    public function getCmsDataByID($id)
    {
        $res = Db::name('xexhibitor_forms')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getDataList($companyId,$eventId=null,$type='Marketing'){
        $res = Db::name('xexhibitor_forms')
            ->alias('a')
            ->field('a.*,b.name,b.due_date')
            ->join('xforms b','a.form_id = b.id')
            ->where('a.company_id',$companyId)
            ->where('a.event_id',$eventId)
            ->where('a.main_type','=',$type)
            ->where('b.status',1)
            ->order('b.sort','asc')
            ->order('b.name','asc')
            ->order('b.id','asc')
            ->select();
        return $res;
    }

    public function getStatusName($status){
        if($status == 0){
            return 'Pending';
        }else if($status == 1){
            return 'Submitted';
        }
    }
}