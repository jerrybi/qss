<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\lib\Email;
use app\common\lib\IAuth;
use app\common\lib\Tools;
use app\common\lib\QRCode;
use app\common\validate\Xexhibitor;
use think\Db;

class Xvisitors extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsDatasForPage($curr_page = 1, $page_limit = 1,
                                       $exhibitorId = null, $search = null, $onsiteNumbers = null,$eventId = null){
        $model = $this
            ->alias('a')
            ->field("a.id,a.first_name,a.last_name,a.full_name,a.organization,a.phone,a.email,a.flag,a.remark,
            a.title,a.serial_number,a.exhibitor_id,a.event_id,a.visit_time,a.visit_date,a.create_time,a.update_time,
            e.name as event_name,ex.login_name as exhibitor_name")
            ->join('xevents e','e.id = a.event_id')
            ->join('xexhibitors ex','ex.id = a.exhibitor_id');
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        if(!empty($exhibitorId)){
            $model = $model->where('a.exhibitor_id',$exhibitorId);
        }
        $model = $model->where(function ($query) use ($search,$onsiteNumbers) {
            if(!empty($search) && $onsiteNumbers !== null){
                $query->where('a.first_name|a.last_name|a.full_name|a.email|a.organization|a.remark','like','%'.$search.'%')
                    ->whereOr('a.serial_number','in',$onsiteNumbers);
            }else if(!empty($search)){
                $query->where('a.first_name|a.last_name|a.full_name|a.email|a.organization|a.remark','like','%'.$search.'%');
            }else if($onsiteNumbers !== null){
                $query->where('a.serial_number','in',$onsiteNumbers);
            }
        });
        $res = $model->order(['a.create_time' => 'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    public function getCmsDatasForPage2($curr_page = 1, $page_limit = 1,
                                       $exhibitorId = null, $search = null, $onsiteNumbers = null,$eventId = null){
        $model = $this
            ->alias('a')
            ->field("a.id,a.first_name,a.last_name,a.full_name,a.organization,a.phone,a.email,a.flag,a.remark,
            a.title,a.serial_number,a.exhibitor_id,a.event_id,a.visit_time,a.visit_date,a.create_time,a.update_time,
            a.img_card,ex.login_name as exhibitor_name,
            e.name as event_name")
            ->join('xevents e','e.id = a.event_id')
            ->join('xexhibitors ex','ex.id = a.exhibitor_id');
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        if(!empty($exhibitorId)){
            $model = $model->where('a.exhibitor_id',$exhibitorId);
        }
        $model = $model->where(function ($query) use ($search,$onsiteNumbers) {
            if(!empty($search) && $onsiteNumbers !== null){
                $query->where('a.first_name|a.last_name|a.full_name|a.email|a.organization|a.remark','like','%'.$search.'%')
                    ->whereOr('a.serial_number','in',$onsiteNumbers);
            }else if(!empty($search)){
                $query->where('a.first_name|a.last_name|a.full_name|a.email|a.organization|a.remark','like','%'.$search.'%');
            }else if($onsiteNumbers !== null){
                $query->where('a.serial_number','in',$onsiteNumbers);
            }
        });
        $res = $model->order(['a.create_time' => 'asc'])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    public function getCmsDatasCount($exhibitorId = null,$search = null,$onsiteNumbers = null,$eventId = null){
        $model = $this
            ->alias('a')
            ->field("a.id");
        if(!empty($eventId)){
            $model = $model->where('a.event_id',$eventId);
        }
        if(!empty($exhibitorId)){
            $model = $model->where('a.exhibitor_id',$exhibitorId);
        }
        $model = $model->where(function ($query) use ($search,$onsiteNumbers) {
            if(!empty($search) && $onsiteNumbers !== null){
                $query->where('a.first_name|a.last_name|a.full_name|a.email|a.organization|a.remark','like','%'.$search.'%')
                    ->whereOr('a.serial_number','in',$onsiteNumbers);
            }else if(!empty($search)){
                $query->where('a.first_name|a.last_name|a.full_name|a.email|a.organization|a.remark','like','%'.$search.'%');
            }else if($onsiteNumbers !== null){
                $query->where('a.serial_number','in',$onsiteNumbers);
            }
        });
        $count = $model->count();
        return $count;
    }
}