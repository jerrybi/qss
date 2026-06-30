<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\XdataField;
use app\common\validate\XfieldOption;
use think\Db;

class XfieldOptions extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    protected $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new XfieldOption();
    }

    public function updateCmsData($userID,$field)
    {
        $tag = $this->where('user_id',$userID)
            ->update([
                'field'=>$field,
                'update_time' => date('Y-m-d H:i:s',time())
            ]);
        $res['tag'] = $tag;
        $res['message'] = $tag ? 'update successfully' : 'update failed';
        return $res;
    }

    public function getCmsDataByUserID($userID)
    {
        $res = Db::name('xfield_options')
            ->alias('a')
            ->field('a.field')
            ->where('a.user_id', $userID)
            ->where('a.status',1)
            ->find();
        return isset($res)?$res['field']:[];
    }

    public function addData($userID,$field)
    {
        $tag = $this->insert([
            'field'=>$field,
            'user_id'=>$userID,
            'create_time' => date('Y-m-d H:i:s',time()),
            'status' => 1
        ]);
        $res['tag'] = $tag;
        $res['message'] = $tag ? 'add successfully' : 'add failed';
        return $res;
    }
}