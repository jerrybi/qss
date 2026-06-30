<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use think\Db;
use think\Exception;

class XBooking extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getLastData($eventId,$companyId,$formId){
        $res = Db::name('xform_booking')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
            ->order('submit_time','desc')->limit(1)->find();
        if(empty($res)) return [];
        $data = Db::name('xform_booking')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,
            'submit_time'=>$res['submit_time']])->select();
        return $data;
    }

    public function addCmsData($eventId,$companyId,$formId,$locationId,$input){
        $isDraft = 0;
        $submitTime = date('Y-m-d H:i:s',time());
        if(isset($input['presentation_date']) && isset($input['presentation_time'])){
            //检查这个时间有没有被预定
            $res = Db('xform_booking')->where(['event_id'=>$eventId,'presentation_date'=>$input['presentation_date'],
                'presentation_time'=>$input['presentation_time'],'form_id'=>$formId,'location_id'=>$locationId,'is_last_submit'=>1])->find();
            if(!empty($res) && $res['company_id'] != $companyId){
                return ['tag'=>0,'message'=>'Sorry,this date and time has been ordered!'];
            }
        }
        $presentDate = isset($input['presentation_date'])?$input['presentation_date']:date('Y-m-d',time());
        $presentTime = isset($input['presentation_time'])?$input['presentation_time']:'';
        $addData = [
            'presentation_date' => isset($input['presentation_date'])?$input['presentation_date']:date('Y-m-d',time()),
            'presentation_time' => isset($input['presentation_time'])?$input['presentation_time']:'',
            'is_new_product' => isset($input['is_new_product'])?$input['is_new_product']:1,
            'title' => isset($input['title'])?$input['title']:'',
            'synopsis' => isset($input['synopsis'])?$input['synopsis']:'',
            'product_img_url' => isset($input['product_img_url'])?$input['product_img_url']:'',
            'speaker_cv' => isset($input['speaker_cv'])?$input['speaker_cv']:'',
            'speaker_img_url' => isset($input['speaker_img_url'])?$input['speaker_img_url']:'',
            'form_submit_name' => isset($input['form_submit_name'])?$input['form_submit_name']:'',
            'form_submit_designation' => isset($input['form_submit_designation'])?$input['form_submit_designation']:'',
            'form_submit_email' => isset($input['form_submit_email'])?$input['form_submit_email']:'',
            'form_submit_instruction' => isset($input['form_submit_instruction'])?$input['form_submit_instruction']:'',
            'form_id' => $formId,
            'location_id' => isset($input['location_id'])?$input['location_id']:'',
            'location_group_id' => isset($input['location_group_id'])?$input['location_group_id']:'',
            'submit_time' => $submitTime,
            'is_draft' =>$isDraft,
            'is_last_submit'=>1,
            'company_id' => $companyId,
            'event_id' => $eventId,
            'create_time'=>date('Y-m-d H:i:s',time()),
            'update_time'=>date('Y-m-d H:i:s',time())
        ];
        $tag = 1;
        $error = '';
        $form = Db::name('xforms')->where('id',$formId)->find();
        Db::startTrans();
        try{
            Db::name('xform_booking')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_last_submit'=>1])->update(['is_last_submit'=>0]);
            Db::name('xform_booking')->insert($addData);
            if($isDraft == 0){
                $res = Db::name('xexhibitor_forms')
                    ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                    ->find();
                if(empty($res)){
                    Db::name('xexhibitor_forms')->insert(['event_id'=>$eventId,'company_id'=>$companyId,
                        'form_id'=>$formId,'main_type'=>$form['main_type'],'type'=>'Booking','status'=>1,'create_time'=>date('Y-m-d H:i:s',time())]);
                }else{
                    Db::name('xexhibitor_forms')
                        ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                        ->update(['main_type'=>$form['main_type'],'type'=>'Booking','status'=>1,'update_time'=>date('Y-m-d H:i:s',time())]);
                }
            }
            Db::commit();
        }catch (Exception $e){
            $tag = 0;
            $error = $e->getMessage();
            Db::rollback();
        }
        $validateRes['tag'] = $tag;
        $validateRes['message'] = $tag ? ($isDraft?'Saved successful':'Submitted successful') : 'Submitted failed'.$error;
        return $validateRes;
    }

    public function getUsedDateTime($eventId,$day,$times,$formId,$locationId){
        return Db::name('xform_booking')->where(['event_id'=>$eventId,'presentation_date'=>$day,
            'presentation_time'=>$times,'form_id'=>$formId,'location_id'=>$locationId,'is_last_submit'=>1])
            ->select();
    }

    public function getTotalUsedDateTime($eventId,$formId){
        return Db::name('xform_booking')->alias("a")
            ->join("xlocations b","a.location_id = b.id","left")
            ->join("xcompanies c","a.company_id = c.id","left")
            ->join("xbooths d","c.booth_id = d.id","left")
            ->where(['a.event_id'=>$eventId,'a.form_id'=>$formId,'a.is_draft'=>0,'a.is_last_submit'=>1])
            ->field("a.*,b.name as location,c.name as company,d.name as booth_name")
            ->select();
    }
}