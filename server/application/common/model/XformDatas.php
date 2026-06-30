<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use think\Db;

class XformDatas extends BaseModel
{

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getCmsData($eventId,$companyId,$formId){
        return Db::name('xform_datas')
            ->alias('a')
            ->field('a.event_id,a.company_id,a.form_id,a.name,a.value,b.type')
            ->join('xform_attrs b',['a.form_id = b.form_id','a.name = b.name'],'left')
            ->where(['a.event_id'=>$eventId,'a.company_id'=>$companyId,'a.form_id'=>$formId])
            ->select();
    }
}