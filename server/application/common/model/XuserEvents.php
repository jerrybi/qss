<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\lib\QRCode;
use app\common\validate\Xzone;
use think\Db;
use FormDesign\Formdesign;

class XuserEvents extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function checkUpdateData($userId)
    {
        $res = Db::name('xuser_events')
            ->where('user_id',$userId)
            ->find();
        if(empty($res)){
            $eventId = '';
            if($userId == 1){
                $res1 = Db::name('xevents')
                    ->where('status',1)
                    ->field('id')
                    ->find();
                $eventId = !empty($res1)?$res1['id']:'';
            }else{
                $res1 = Db::name('xevent_accounts')
                    ->alias('a')
                    ->leftJoin('xevents e','a.event_id = e.id')
                    ->where('e.status',1)
                    ->where('a.user_id',$userId)
                    ->whereOr('a.assign_accounts','like','%|'.$userId.'|%')
                    ->find();
                $eventId = !empty($res1)?$res1['event_id']:'';
            }
            if(!empty($eventId)){
                Db::name('xuser_events')
                    ->insert([
                        'id'=>Tools::create_guid(),
                        'user_id'=>$userId,
                        'event_id'=>$eventId,
                        'create_time'=>date('Y-m-d H:i:s',time())
                    ]);
            }
        }
    }

    public function getActiveEventId($userId){
        $res = Db::name('xuser_events')
            ->where('user_id',$userId)
            ->field('event_id')
            ->find();
        return !empty($res)?$res['event_id']:'';
    }

    public function setActiveEventId($userId,$eventId){
        $res = Db::name('xuser_events')
            ->where('user_id',$userId)
            ->field('id')
            ->find();
        if(empty($res)){
            Db::name('xuser_events')
                ->insert([
                    'id'=>Tools::create_guid(),
                    'user_id'=>$userId,
                    'event_id'=>$eventId,
                    'create_time'=>date('Y-m-d H:i:s',time())
                ]);
        }else{
            Db::name('xuser_events')
                ->where('id',$res['id'])
                ->update([
                    'event_id'=>$eventId,
                    'update_time'=>date('Y-m-d H:i:s',time())
                ]);
        }
        return ['tag'=>1,'message'=>'ok'];
    }

    public function getActiveEvent(){
        $userId = IAuth::getAdminIDCurrLogged();
        $res = Db::name('xuser_events')
            ->alias('a')
            ->leftJoin('xevents e','a.event_id=e.id')
            ->where('a.user_id',$userId)
            ->field('e.*')
            ->find();
        return !empty($res)?$res:[];
    }
}