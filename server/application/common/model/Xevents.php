<?php

namespace app\common\model;

use app\common\lib\IAuth;
use app\common\validate\Xevent;
use think\Db;
use \think\Model;
use app\common\lib\Tools;

/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/1/11
 * Time: 16:45
 */
class Xevents extends BaseModel
{
    // 设置当前模型对应的完整数据表名称
    protected $autoWriteTimestamp = 'datetime';
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xevent();
    }

    /**
     * 获取所有的文章
     * @return array
     */
    public function getEventsList()
    {
        return $this->getSimpleEventsList();
    }

    public function getSimpleEventsList()
    {
        $userId = IAuth::getAdminIDCurrLogged();
        if($userId != 1){
            $res = $this
                ->field("a.id,a.name")
                ->alias('a')//给主表取别名
                ->join('xevent_accounts b','a.id=b.event_id')
                ->where('a.status','1')
                ->where('b.assign_accounts','like','%|'.$userId.'|%')
                ->select();
        }else{
            $res = $this
                ->field("a.id,a.name")
                ->alias('a')//给主表取别名
                ->where('a.status','1')
                ->select();
        }
        return isset($res) ? $res->toArray() : [];
    }

    /**
     * 根据文章ID 获取文章详情
     * @param $id
     * @return array
     */
    public function getInfoByID($id)
    {
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.status','1')
            ->where("a.id = '" . $id."'")
            ->find();
        return isset($res) ? $res : [];
    }

    /**
     * 后台获取文章数据列表
     * @param $curr_page
     * @param int $limit
     * @param null $search
     * @return array
     */
    public function getCmsEventsForPage($curr_page, $limit = 1, $search = null)
    {
        $userId = IAuth::getAdminIDCurrLogged();
        if($userId != 1){
            $res = $this
                ->alias('a')
                ->field('a.*')
                ->join('xevent_accounts b','a.id=b.event_id')
                ->whereLike('a.name', '%' . $search . '%')
                ->where('a.status','1')
                ->where('b.assign_accounts','like','%|'.$userId.'|%')
                ->order(['a.list_order' => 'desc', 'a.created_at' => 'desc'])
                ->limit($limit * ($curr_page - 1), $limit)
                ->select();
        }else{
            $res = $this
                ->alias('a')
                ->field('a.*')
                ->whereLike('a.name', '%' . $search . '%')
                ->where('a.status','1')
                ->order(['a.list_order' => 'desc', 'a.created_at' => 'desc'])
                ->limit($limit * ($curr_page - 1), $limit)
                ->select();
        }
        return isset($res)?$res->toArray():[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsEventsCount($search = null)
    {
        $userId = IAuth::getAdminIDCurrLogged();
        if($userId != 1){
            $count = $this
                ->alias('a')
                ->field('a.id')
                ->join('xevent_accounts b','a.id=b.event_id')
                ->whereLike('a.name', '%' . $search . '%')
                ->where('a.status','1')
                ->where('b.assign_accounts','like','%|'.$userId.'|%')
                ->count();
        }else{
            $count = $this
                ->alias('a')
                ->field('a.id')
                ->whereLike('a.name', '%' . $search . '%')
                ->where('a.status','1')
                ->count();
        }
        return $count;
    }

    /**
     * 根据文章ID 获取文章内容
     * @param $id
     * @return array
     */
    public function getCmsEventByID($id)
    {
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function getCmsEventByCode($code)
    {
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.code', $code)
            ->find();
        return $res;
    }

    /**
     * 更新文章内容
     * @param $input
     * @param int $id
     * @return array
     */
    public function updateCmsEventData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xevents')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'EVENT',$id,'remove event');
        } else {
            $saveData = [
                'name' => isset($input['name'])?$input['name']:'',
                'code' => isset($input['code'])?$input['code']:'',
                'description' => isset($input['description'])?$input['description']:'',
                'venue' => isset($input['venue'])?$input['venue']:'',
                'country' => isset($input['country'])?$input['country']:'',
                'timezone' => isset($input['timezone'])?$input['timezone']:'',
                'start_time' => isset($input['start_time'])?$input['start_time']:'',
                'end_time' => isset($input['end_time'])?$input['end_time']:'',
                'show_directory_start_time' => isset($input['show_directory_start_time'])?$input['show_directory_start_time']:'',
                'show_directory_end_time' => isset($input['show_directory_end_time'])?$input['show_directory_end_time']:'',
                'list_order' => isset($input['list_order'])?$input['list_order']:0,
                'is_encrypt' => isset($input['is_encrypt'])?$input['is_encrypt']:0,
                'enable_track' => isset($input['enable_track'])?$input['enable_track']:0,
                'lat' => isset($input['lat'])?$input['lat']:0.0,
                'lng' => isset($input['lng'])?$input['lng']:0.0,
                'updated_at' => date('Y-m-d H:i:s', time())
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                //检查名称是否已经存在
                $result = $this->where('name',$input['name'])->find();
                if(!empty($result) && $result['id'] != $id){
                    return ['tag' => false, 'message' => 'Event Name Exist!'];
                }
                $result = $this->where('code',$input['code'])->find();
                if(!empty($result) && $result['id'] != $id){
                    return ['tag' => false, 'message' => 'Event Code Exist!'];
                }
                if($saveData['is_encrypt'] == 1){
                    $saveData['event_key'] = Tools::randCode();   
                }else{
                    $saveData['event_key'] = '';
                }
                $saveTag = $this
                    ->where('id', $id)
                    ->update($saveData);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag,'EVENT',$id,'event update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    /**
     * 进行新文章的添加操作
     * @param $data
     * @return array
     */

    public function addEvent($data)
    {

        $addData = [
            'id' => isset($data['id'])?$data['id']:'',
            'name' => isset($data['name'])?$data['name']:'',
            'code' => isset($data['code'])?$data['code']:'',
            'description' => isset($data['description'])?$data['description']:'',
            'venue' => isset($data['venue'])?$data['venue']:'',
            'country' => isset($data['country'])?$data['country']:'',
            'timezone' => isset($data['timezone'])?$data['timezone']:'',
            'start_time' => isset($data['start_time'])?$data['start_time']:'',
            'end_time' => isset($data['end_time'])?$data['end_time']:'',
            'show_directory_start_time' => isset($data['show_directory_start_time'])?$data['show_directory_start_time']:'',
            'show_directory_end_time' => isset($data['show_directory_end_time'])?$data['show_directory_end_time']:'',
            'list_order' => isset($data['list_order'])?$data['list_order']:0,
            'is_encrypt' => isset($data['is_encrypt'])?$data['is_encrypt']:0,
            'enable_track' => isset($data['enable_track'])?$data['enable_track']:0,
            'lat' => isset($data['lat'])?$data['lat']:0.0,
            'lng' => isset($data['lng'])?$data['lng']:0.0,
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
                return ['tag' => false, 'message' => 'Event Name Exist!'];
            }
            $result = $this->where('code',$data['code'])->find();
            if(!empty($result)){
                return ['tag' => false, 'message' => 'Event Code Exist!'];
            }
            if($addData['is_encrypt'] == 1){
                $addData['event_key'] = Tools::randCode();   
            }else{
                $addData['event_key'] = '';
            }
            $tag = $this->insert($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'EVENT',$this->getLastInsID(),'add event');
            }
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function getFirstEvent(){
        return Db::name('xevents')->where(['status'=>1])->order(['list_order' => 'desc', 'created_at' => 'desc'])->find();
    }

    public function getEventDays($id){
        $res = $this->where('id',$id)->find();
        $data = [];
        if($res){
            $startDay = date('Y-m-d',strtotime($res['start_time']));
            $endDay = date('Y-m-d',strtotime($res['end_time']));
            $cur = $startDay;
            do{
                $data[] = $cur;
                $cur = date('Y-m-d',strtotime('+1 day',strtotime($cur)));
            }while(strtotime($cur) <= strtotime($endDay));
        }
        return $data;
    }

    public function duplicateEvent($id){
        $res = $this->where('id',$id)->find();
        if($res){
            $tid = Tools::create_guid();
            $data = $res->toArray();
            $data['id'] = $tid;
            $data['name'] = $data['name'].' copy';
            $data['code'] = $data['code'].'_copy';
            $data['last_sync_id'] = 0;
            $this->insert($data);
            return $tid;
        }
        return null;
    }
}