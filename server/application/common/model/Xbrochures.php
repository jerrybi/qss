<?php


namespace app\common\model;


use think\Db;
use think\db\Where;

class Xbrochures extends BaseModel
{
    public function incBrochureRecord($uid,$num){
        //获取index最大的记录
        $curIndex = 0;
        $record = $this->where('user_id',$uid)->field('brochure_num')->order('index','desc')->limit(1)->find();
        if(!empty($record)){
            $curIndex = $record['index'];
        }
        $data = [];
        for($i=0;$i<$num;$i++){
            $data[] = ['index'=>$curIndex+$i+1,'user_id'=>$uid,'name'=>'','url'=>''];
        }
        $this->saveAll($data);
    }

    public function updateName($uid,$index,$name){
        $this->save(['name'=>$name],['user_id'=>$uid,'index'=>$index]);
    }

    public function updateUrl($uid,$index,$url){
        $this->save(['url'=>$url],['user_id'=>$uid,'index'=>$index]);
    }

    public function getBrochureList($uid){
        $brochures = $this->where('user_id',$uid)->order('index asc')->select();
        return $brochures;
    }
}