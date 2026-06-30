<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use think\Db;

class XformAttrs extends BaseModel
{

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function updateCmsData($formId,$attrs){
        Db::transaction(function() use($formId,$attrs){
            Db::name('xform_attrs')->where('form_id',$formId)->delete();
            Db::name('xform_attrs')->insertAll($attrs);
        });
    }
}