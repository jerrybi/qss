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
use think\Db;
use FormDesign\Formdesign;

class XedmTasks extends BaseModel
{

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1, $search = null,$templateId=null,
                                       $zoneId=null,$status=null,$rsvpStatus=null,$eventId = null){
        $model = Db('xedm_tasks')
            ->alias('a')
            ->field('a.*,e.name as event_name,t.name as template_name,b.zone_id,z.name as zone_name')
            ->join('xevents e','e.id = a.event_id')
            ->join('xedm_templates t','t.id = a.template_id')
            ->join('xuser_tables b','a.user_id = b.user_id and a.event_id = b.event_id and b.status = 1','left')
            ->join('xzones z','z.id = b.zone_id and z.event_id = b.event_id and z.status = 1','left')
            ->where('t.status',1);
        if(!empty($search)){
            $user = Db::name('xuser_datas')->where('status',1)
                ->where('event_id',$eventId)
                ->where('value','like','%' . $search . '%')
                ->field('distinct(user_id)')
                ->select();
            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('a.user_id','in',$ids);
        }
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        if(!empty($zoneId)){
            $model = $model->where('b.zone_id','=',$zoneId);
        }
        if(!empty($templateId)){
            $model = $model->where('a.template_id',$templateId);
        }
        if(!empty($status)){
            $model = $model->where('a.status',$status);
        }
        if(!empty($rsvpStatus)){
            if($rsvpStatus == '1'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','=','1')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '2'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','=','2')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '9'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','not in',['1','2'])
                    ->field('distinct(user_id)')
                    ->select();
            }else{
                $user = null;
            }

            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('a.user_id','in',$ids);
        }
        $res = $model->order(['a.id'=>'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res:[];
    }

    public function getCmsDatasCount($search = null,$templateId=null,$zoneId=null,$status=null,$rsvpStatus=null,$eventId=null){
        $model = Db('xedm_tasks')
            ->alias('a')
            ->join('xevents e','e.id = a.event_id')
            ->join('xedm_templates t','t.id = a.template_id')
            ->join('xuser_tables b','a.user_id = b.user_id and a.event_id = b.event_id and b.status = 1','left')
            ->join('xzones z','z.id = b.zone_id and z.event_id = b.event_id and z.status = 1','left')
            ->where('t.status',1)
            ->field("*");
        if(!empty($search)){
            $user = Db::name('xuser_datas')->where('status',1)
                ->where('event_id',$eventId)
                ->where('value','like','%' . $search . '%')
                ->field('distinct(user_id)')
                ->select();
            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('a.user_id','in',$ids);
        }
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        if(!empty($zoneId)){
            $model = $model->where('b.zone_id','=',$zoneId);
        }
        if(!empty($templateId)){
            $model = $model->where('a.template_id',$templateId);
        }
        if(!empty($status)){
            $model = $model->where('a.status',$status);
        }
        if(!empty($rsvpStatus)){
            if($rsvpStatus == '1'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','=','1')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '2'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','=','2')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '9'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','not in',['1','2'])
                    ->field('distinct(user_id)')
                    ->select();
            }else{
                $user = null;
            }

            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('a.user_id','in',$ids);
        }
        $count = $model->count();
        return $count;
    }

    public function getCmsDatas($search = null,$templateId=null,
                                       $zoneId=null,$status=null,$rsvpStatus=null,$eventId = null){
        $model = Db('xedm_tasks')
            ->alias('a')
            ->field('a.*,e.name as event_name,t.name as template_name,b.zone_id,z.name as zone_name')
            ->join('xevents e','e.id = a.event_id')
            ->join('xedm_templates t','t.id = a.template_id')
            ->join('xuser_tables b','a.user_id = b.user_id and a.event_id = b.event_id and b.status = 1','left')
            ->join('xzones z','z.id = b.zone_id and z.event_id = b.event_id and z.status = 1','left')
            ->where('t.status',1);
        if(!empty($search)){
            $user = Db::name('xuser_datas')->where('status',1)
                ->where('event_id',$eventId)
                ->where('value','like','%' . $search . '%')
                ->field('distinct(user_id)')
                ->select();
            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('a.user_id','in',$ids);
        }
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        if(!empty($zoneId)){
            $model = $model->where('b.zone_id','=',$zoneId);
        }
        if(!empty($templateId)){
            $model = $model->where('a.template_id',$templateId);
        }
        if(!empty($status)){
            $model = $model->where('a.status',$status);
        }
        if(!empty($rsvpStatus)){
            if($rsvpStatus == '1'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','=','1')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '2'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','=','2')
                    ->field('distinct(user_id)')
                    ->select();
            }else if($rsvpStatus == '9'){
                $user = Db::name('xuser_datas')->where('status',1)
                    ->where('event_id',$eventId)
                    ->where('key','join')
                    ->where('value','not in',['1','2'])
                    ->field('distinct(user_id)')
                    ->select();
            }else{
                $user = null;
            }

            $ids = [];
            if($user){
                foreach($user as $v){
                    $ids[] = $v['user_id'];
                }
            }
            $model = $model->where('a.user_id','in',$ids);
        }
        $res = $model->order(['a.id'=>'asc'])
            ->select();
        return isset($res)?$res:[];
    }

    public function addData($data)
    {
        $eventId = isset($data['event_id'])?$data['event_id']:'';
        $templateId = isset($data['template_id'])?$data['template_id']:0;
        $zoneId = isset($data['zone_id'])?$data['zone_id']:0;
        $join = isset($data['join'])?$data['join']:'';
        $res = Db::name('xedm_tasks')
            ->where('template_id',$templateId)
            ->where('event_id',$eventId)
            ->field('user_id')
            ->select();
        $ids = [];
        if($res){
            foreach($res as $k => $v){
                $ids[] = $v['user_id'];
            }
        }
        $res1 = Db::name('xuser_tables')
            ->where('status',1)
            ->where('event_id',$eventId)
            ->where('zone_id',$zoneId)
            ->field('distinct(user_id)')
            ->select();
        $ids1 = [];
        if($res1){
            foreach($res1 as $v){
                $ids1[] = $v['user_id'];
            }
        }

//        $res2 = Db::name('xuser_datas')
//            ->where('status',1)
//            ->where('event_id',$eventId)
//            ->where('key','self_register')
//            ->where('value','1')
//            ->field('distinct(user_id)')
//            ->select();
//        $ids2 = [];
//        if($res2){
//            foreach($res2 as $v){
//                $ids2[] = $v['user_id'];
//            }
//        }

        $userDataModel = new XuserDatas();
        $ids2 = $userDataModel->getUserList(null,$join,2);

        $res = Db::name('xusers')
            ->where('status',1)
            ->where('event_id',$eventId)
            ->where('id','not in',$ids)
            ->where('id','in',$ids2)
            ->where('id','in',$ids1)
            ->field('id')
            ->select();
        $data = [];
        if($res){
            foreach($res as $v){
                $data[] = [
                    'user_id'=>$v['id'],
                    'template_id'=>$templateId,
                    'event_id'=>$eventId,
                    'status'=>9,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ];
            }
        }
        Db::name('xedm_tasks')->insertAll($data);
        $validateRes['tag'] = 1;
        $validateRes['message'] = 'add successfully';
        return $validateRes;
    }

    public function assign($data)
    {
        $eventId = isset($data['event_id'])?$data['event_id']:'';
        $templateId = isset($data['template_id'])?$data['template_id']:0;
        $ids1 = isset($data['ids'])?$data['ids']:'';
        $res = Db::name('xedm_tasks')
            ->where('template_id',$templateId)
            ->where('event_id',$eventId)
            ->field('user_id')
            ->select();
        $ids = [];
        if($res){
            foreach($res as $k => $v){
                $ids[] = $v['user_id'];
            }
        }

        $ids2 = explode("|",$ids1);

        $res = Db::name('xusers')
            ->where('status',1)
            ->where('event_id',$eventId)
            ->where('id','not in',$ids)
            ->where('id','in',$ids2)
            ->field('id')
            ->select();
        $data = [];
        if($res){
            foreach($res as $v){
                $data[] = [
                    'user_id'=>$v['id'],
                    'template_id'=>$templateId,
                    'event_id'=>$eventId,
                    'status'=>9,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ];
            }
        }
        Db::name('xedm_tasks')->insertAll($data);
        $validateRes['tag'] = 1;
        $validateRes['message'] = 'add successfully';
        return $validateRes;
    }

    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xedm_tasks')
                ->where('id', $id)
                ->delete();
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
        }else{
            $validateRes = ['tag' => 0, 'message' => 'not support'];
        }
        return $validateRes;
    }

    public static function getRsvpStatus($status){
        if($status == '1'){
            return 'Accept';
        }else if($status == '2'){
            return 'Reject';
        }else if($status == '9'){
            return 'Audit';
        }else{
            return 'Not RSVP';
        }
    }

    public static function getStatus($status){
        if($status == '9'){
            return 'Pending';
        }else if($status == '1'){
            return 'Success';
        }else if($status == '2'){
            return 'Fail';
        }else if($status == '3'){
            return 'Invalid Addr';
        }else if($status == '4'){
            return 'Receipt';
        }else{
            return '';
        }
    }
}