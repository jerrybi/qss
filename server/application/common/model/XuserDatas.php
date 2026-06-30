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
use app\common\validate\Xtable;
use think\Db;
use FormDesign\Formdesign;

class XuserDatas extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getDataList($userID){
        $where[] = ['status','=',1];
        $res = Db::name('xuser_datas')
            ->field('*')
            ->where($where)
            ->where('user_id',$userID)
            ->order(['id'=>'asc'])
            ->select();
        return $res;
    }

    public function getCmsData($userID,$key){
        $where[] = ['status','=',1];
        $res = Db::name('xuser_datas')
            ->field('*')
            ->where($where)
            ->where('user_id',$userID)
            ->where('key',$key)
            ->find();
        return $res;
    }

    public function getUserIdBySerialNumber($eventID,$serialNumber){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_datas')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->join('xevents c','b.event_id = c.id')
            ->field('a.user_id')
            ->where($where)
            ->where('a.event_id',$eventID)
            ->where('b.status',1)
            ->where('c.status',1)
            ->where('a.key','serial_number')
            ->where('a.value',$serialNumber)
            ->find();
        return isset($res['user_id'])?$res['user_id']:0;
    }

    public function getUserIdByOnSiteNumber($eventID,$onSiteNumber){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_datas')
            ->alias('a')
            ->field('a.user_id')
            ->where($where)
            ->where('a.event_id',$eventID)
            ->where('a.key','onsite_number')
            ->where('a.value',$onSiteNumber)
            ->find();
        return isset($res['user_id'])?$res['user_id']:0;
    }

    public function getUserIdByEmail($eventID,$email){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_datas')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->join('xevents c','b.event_id = c.id')
            ->field('a.user_id')
            ->where($where)
            ->where('a.event_id',$eventID)
            ->where('b.status',1)
            ->where('c.status',1)
            ->where('a.key','email')
            ->where('a.value',$email)
            ->find();
        return isset($res['user_id'])?$res['user_id']:0;
    }

    public function getUserIdByName($eventID,$firstName,$lastName){
        $where[] = ['a.status','=',1];
        $res = Db::name('xuser_datas')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->join('xevents c','b.event_id = c.id')
            ->field('a.user_id')
            ->where($where)
            ->where('a.event_id',$eventID)
            ->where('b.status',1)
            ->where('c.status',1)
            ->where('a.key','first_name')
            ->where('a.value',$firstName)
            ->select();
        $ids = [];
        if($res){
            foreach($res as $v){
                $ids[] = $v['user_id'];
            }
        }
        $res = Db::name('xuser_datas')
            ->where('status',1)
            ->where('event_id',$eventID)
            ->where('key','last_name')
            ->where('value',$lastName)
            ->where('user_id','in',$ids)
            ->select();
        return $res;
    }

    public function getUserIdByBadgeName($eventID,$zoneID,$badgeName){
        $where[] = ['a.status','=',1];
        if(!empty($zoneID)){
            $result = Db::name('xuser_tables')->where('zone_id',$zoneID)
                ->where('status',1)
                ->select();
            $users = [];
            if($result){
                foreach($result as $v){
                    $users[] = $v['user_id'];
                }
            }
            if($users){
                $where[] = ['a.user_id','in',$users];
            }
        }
        $res = Db::name('xuser_datas')
            ->alias('a')
            ->join('xusers b','a.user_id = b.id')
            ->join('xevents c','b.event_id = c.id')
            ->field('a.user_id,a.value as badge_name')
            ->where($where)
            ->where('a.event_id',$eventID)
            ->where('b.status',1)
            ->where('c.status',1)
            ->where('a.key','badge_name')
            ->where('a.value','like','%'.$badgeName.'%')
            ->select();
        return $res;
    }

    public function getUserList($search,$join,$selfRegister){
        if(empty($search)&&empty($join)&&empty($selfRegister)) return null;
        $model = Db::name('xuser_datas')
            ->field('distinct(user_id)')
            ->where('status',1);
        if(!empty($search)){
            $res = Db::name('xuser_datas')
                ->field('distinct(user_id)')
                ->where('status',1)
                ->where('value','like','%'.$search.'%')
                ->select();
            $ids = Tools::array_filter($res,'user_id');
            $model = $model->where('user_id','in',$ids);
        }
        if(!empty($join)){
            if($join == '-1'){
                $res = Db::name('xuser_datas')
                    ->field('distinct(user_id)')
                    ->where('status',1)
                    ->where('key','=','join')
                    ->where('value','in',['','0'])
                    ->select();
            }else{
                $res = Db::name('xuser_datas')
                    ->field('distinct(user_id)')
                    ->where('status',1)
                    ->where('key','=','join')
                    ->where('value','=',$join)
                    ->select();
            }
            $ids = Tools::array_filter($res,'user_id');
            $model = $model->where('user_id','in',$ids);
        }
        if(!empty($selfRegister)){
            if($selfRegister == '1'){
                $res = Db::name('xuser_datas')
                    ->field('distinct(user_id)')
                    ->where('status',1)
                    ->where('key','=','self_register')
                    ->where('value','=','1')
                    ->select();
            }else{
                $res = Db::name('xuser_datas')
                    ->field('distinct(user_id)')
                    ->where('status',1)
                    ->where('key','=','self_register')
                    ->where('value','<>','1')
                    ->select();
            }
            $ids = Tools::array_filter($res,'user_id');
            $model = $model->where('user_id','in',$ids);
        }
        $res = $model->select();
        $ids = Tools::array_filter($res,'user_id');
        return $ids;
    }

    public function getMatchedOnSiteNumber($eventId,$search){
        if(empty($search)){
            return null;
        }
        $res = Db::name('xuser_datas')
            ->field('distinct(user_id)')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->where('value','like','%'.$search.'%')
            ->select();
        $ids = Tools::array_filter($res,'user_id');
        $res1 = Db::name('xuser_datas')
            ->field('value')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->where('key','onsite_number')
            ->where('user_id','in',$ids)
            ->select();
        $onsiteNumbers = Tools::array_filter($res1,'value');
        $onsiteNumbers = array_filter($onsiteNumbers,function ($value){
            return ($value !== null && $value !== false && $value !== '' && $value !== '0' && !is_array($value));
        });
        return $onsiteNumbers;
    }
}