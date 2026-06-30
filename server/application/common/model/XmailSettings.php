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

class XmailSettings extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new XmailSetting();
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
            'sender_name' => isset($input['sender_name'])?$input['sender_name']:'',
            'sender_email' => isset($input['sender_email'])?$input['sender_email']:'',
            'sender_pwd' => isset($input['sender_pwd'])?$input['sender_pwd']:'',
            'mail_server' => isset($input['mail_server'])?$input['mail_server']:'',
            'mail_port' => isset($input['mail_port'])?$input['mail_port']:0,
            'send_type' => isset($input['send_type'])?$input['send_type']:0,
            'top_banner' => isset($input['top_banner'])?$input['top_banner']:'',
            'bottom_banner' => isset($input['bottom_banner'])?$input['bottom_banner']:'',
            'event_id' => isset($input['event_id'])?$input['event_id']:''
        ];
        $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $saveData, $tokenData);
        if ($validateRes['tag']) {
            $res = $this->getCmsData($eventId);
            if(empty($res)){
                $saveTag = $this->save($saveData);
            }else{
                $saveTag = $this->save($saveData,['event_id'=>$eventId]);
            }
            if ($saveTag) {
                insertCmsOpLogs($saveTag,'mail',$eventId,'mail setting update');
            }
            $validateRes['tag'] = $saveTag;
            $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
        }
        return $validateRes;
    }

    public function getCmsData($eventId){
        $res = $this->where('event_id',$eventId)->find();
        return $res;
    }

    public function duplicate($oldEventId, $newEventId){
        $old = $this->getCmsData($oldEventId);
        if($old){
            $data = $old->toArray();
            unset($data['id']);
            $data['event_id'] = $newEventId;
            $this->insert($data);
        }
    }
}