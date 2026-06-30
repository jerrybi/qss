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
use think\Db;

class Xconfigs extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xconfig();
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
            'top_banner_url' => isset($input['top_banner_url'])?$input['top_banner_url']:'default',
            'bottom_banner_url' => isset($input['bottom_banner_url'])?$input['bottom_banner_url']:'default',
            'app_bg_url' => isset($input['app_bg_url'])?$input['app_bg_url']:'',
            'app_text' => isset($input['app_text'])?$input['app_text']:'',
            'app_track_text' => isset($input['app_track_text'])?$input['app_track_text']:'',
            'show_time' => isset($input['show_time'])?$input['show_time']:5,
            'tip_position' => isset($input['tip_position'])?$input['tip_position']:10,
            'app_reg_position' => isset($input['app_reg_position'])?$input['app_reg_position']:10,
            'app_track_position' => isset($input['app_track_position'])?$input['app_track_position']:10,
            'fail_text_position' => isset($input['fail_text_position'])?$input['fail_text_position']:10,
            'text_attr' => isset($input['text_attr'])?$input['text_attr']:'',
            'text_track_attr' => isset($input['text_track_attr'])?$input['text_track_attr']:'',
            'fail_text' => isset($input['fail_text'])?$input['fail_text']:'',
            'content' => isset($input['content'])?base64_decode($input['content']):'default',
            'content_email' => isset($input['content_email'])?base64_decode($input['content_email']):'default',
            'exhibitor_booth_entitlement' => isset($input['exhibitor_booth_entitlement'])?$input['exhibitor_booth_entitlement']:'default',
            'support_email' => isset($input['support_email'])?$input['support_email']:'',
            'close_event' => isset($input['close_event'])?$input['close_event']:0,
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
                insertCmsOpLogs($saveTag,'exhibitor',$eventId,'exhibitor update');
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

    public function getSupportEmail($eventId){
        $res = $this->getCmsData($eventId);
        return !empty($res)?$res['support_email']:'';
    }

    public function isCloseEvent($eventId){
        $res = $this->getCmsData($eventId);
        return !empty($res)?$res['close_event']:0;
    }
}