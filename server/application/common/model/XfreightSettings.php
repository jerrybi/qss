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
use app\common\validate\Xconfig;
use app\common\validate\XmailSetting;
use think\Db;

class XfreightSettings extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function updateCmsData($input)
    {
        $eventId = isset($input['event_id'])?$input['event_id']:'';
        if(empty($eventId)){
            $validateRes['tag'] = 400;
            $validateRes['message'] = 'Event can not be empty!';
            return $validateRes;
        }
        $saveData = [
            'content' => isset($input['content'])?base64_decode($input['content']):'',
            'event_id' => isset($input['event_id'])?$input['event_id']:''
        ];
        $res = $this->getCmsData($eventId);
        if(empty($res)){
            $saveTag = $this->save($saveData);
        }else{
            $saveTag = $this->save($saveData,['event_id'=>$eventId]);
        }
        if ($saveTag) {
            insertCmsOpLogs($saveTag,'freight',$eventId,'freight setting update');
        }
        $validateRes['tag'] = $saveTag;
        $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        return $validateRes;
    }

    public function getCmsData($eventId){
        $res = $this->where('event_id',$eventId)->find();
        return $res;
    }
}