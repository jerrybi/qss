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

class XBadge extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getLastData($eventId,$companyId,$formId){
        $res = Db::name('xform_badge')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
            ->order('submit_time','desc')->limit(1)->find();
        if(empty($res)) return [];
        $data = Db::name('xform_badge')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,
            'submit_time'=>$res['submit_time']])->select();
        return $data;
    }

    public function addCmsData($eventId,$companyId,$formId,$input){
        $isDraft = isset($input['is_draft'])?$input['is_draft']:0;
        $formDatas = isset($input['form_data'])?$input['form_data']:'';
        $formDataArr = json_decode($formDatas,true);
        $addData = [];
        $submitTime = date('Y-m-d H:i:s',time());
        if(empty($formDataArr)){
            $addData[] = [
                'booth_no' => '',
                'exhibiting_company' => '',
                'sqm' => '',
                'rank' => '',
                'salutation' => '',
                'first_name' => '',
                'last_name' => '',
                'badge_name' => '',
                'job_title' => '',
                'company' => '',
                'country' => '',
                'email' => '',
                'mobile' => '',
                'vaccination_type' => '',
                'vaccination_effective_date' => '',
                'vaccination_entry_date' => '',
                'vaccination_exit_date' => '',
                'vaccination_last_city' => '',
                'vaccination_authorizer_person' => '',
                'vaccination_authorizer_designation' => '',
                'dob' => '',
                'nric' => '',
                'organisation_agency' => '',
                'badge_submit_name' => isset($input['badge_submit_name'])?$input['badge_submit_name']:'',
                'badge_submit_designation' => isset($input['badge_submit_designation'])?$input['badge_submit_designation']:'',
                'badge_submit_email' => isset($input['badge_submit_email'])?$input['badge_submit_email']:'',
                'badge_submit_instruction' => isset($input['badge_submit_instruction'])?$input['badge_submit_instruction']:'',
                'badge_submit_timestamp' => isset($input['badge_submit_timestamp'])?$input['badge_submit_timestamp']:'',
                'form_submit_name' => isset($input['form_submit_name'])?$input['form_submit_name']:'',
                'form_submit_designation' => isset($input['form_submit_designation'])?$input['form_submit_designation']:'',
                'form_submit_email' => isset($input['form_submit_email'])?$input['form_submit_email']:'',
                'form_submit_instruction' => isset($input['form_submit_instruction'])?$input['form_submit_instruction']:'',
                'submit_time' => $submitTime,
                'is_draft' =>$isDraft,
                'is_last_submit'=>0,
                'company_id' => $companyId,
                'event_id' => $eventId,
                'form_id' => $formId,
                'create_time'=>date('Y-m-d H:i:s',time()),
                'update_time'=>date('Y-m-d H:i:s',time())
            ];
        }else{
            foreach($formDataArr as $key=>$item){
                $addData[] = [
                    'booth_no' => isset($item['booth_no'])?$item['booth_no']:'',
                    'exhibiting_company' => isset($item['exhibiting_company'])?$item['exhibiting_company']:'',
                    'sqm' => isset($item['sqm'])?$item['sqm']:'',
                    'rank' => isset($item['rank'])?$item['rank']:'',
                    'salutation' => isset($item['salutation'])?$item['salutation']:'',
                    'first_name' => isset($item['first_name'])?$item['first_name']:'',
                    'last_name' => isset($item['last_name'])?$item['last_name']:'',
                    'badge_name' => isset($item['badge_name'])?$item['badge_name']:'',
                    'job_title' => isset($item['job_title'])?$item['job_title']:'',
                    'company' => isset($item['company'])?$item['company']:'',
                    'country' => isset($item['country'])?$item['country']:'',
                    'email' => isset($item['email'])?$item['email']:'',
                    'mobile' => isset($item['mobile'])?$item['mobile']:'',
                    'vaccination_type' => isset($item['vaccination_type'])?$item['vaccination_type']:'',
                    'vaccination_effective_date' => isset($item['vaccination_effective_date'])?$item['vaccination_effective_date']:'',
                    'vaccination_entry_date' => isset($item['vaccination_entry_date'])?$item['vaccination_entry_date']:'',
                    'vaccination_exit_date' => isset($item['vaccination_exit_date'])?$item['vaccination_exit_date']:'',
                    'vaccination_last_city' => isset($item['vaccination_last_city'])?$item['vaccination_last_city']:'',
                    'vaccination_authorizer_person' => isset($item['vaccination_authorizer_person'])?$item['vaccination_authorizer_person']:'',
                    'vaccination_authorizer_designation' => isset($item['vaccination_authorizer_designation'])?$item['vaccination_authorizer_designation']:'',
                    'dob' => isset($item['dob'])?$item['dob']:'',
                    'nric' => isset($item['nric'])?$item['nric']:'',
                    'organisation_agency' => isset($item['organisation_agency'])?$item['organisation_agency']:'',
                    'badge_submit_name' => isset($item['badge_submit_name'])?$item['badge_submit_name']:'',
                    'badge_submit_designation' => isset($item['badge_submit_designation'])?$item['badge_submit_designation']:'',
                    'badge_submit_email' => isset($item['badge_submit_email'])?$item['badge_submit_email']:'',
                    'badge_submit_instruction' => isset($item['badge_submit_instruction'])?$item['badge_submit_instruction']:'',
                    'badge_submit_timestamp' => isset($item['badge_submit_timestamp'])?$item['badge_submit_timestamp']:'',
                    'form_submit_name' => isset($input['form_submit_name'])?$input['form_submit_name']:'',
                    'form_submit_designation' => isset($input['form_submit_designation'])?$input['form_submit_designation']:'',
                    'form_submit_email' => isset($input['form_submit_email'])?$input['form_submit_email']:'',
                    'form_submit_instruction' => isset($input['form_submit_instruction'])?$input['form_submit_instruction']:'',
                    'submit_time' => $submitTime,
                    'is_draft' =>$isDraft,
                    'is_last_submit'=>$isDraft==1?0:1,
                    'company_id' => $companyId,
                    'event_id' => $eventId,
                    'form_id' => $formId,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ];
            }
        }
        $tag = 1;
        $error = '';
        $form = Db::name('xforms')->where('id',$formId)->find();
        Db::startTrans();
        try{
            Db::name('xform_badge')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_draft'=>1])->delete();
            if($isDraft == 0){
                Db::name('xform_badge')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_last_submit'=>1])->update(['is_last_submit'=>0]);
                $res = Db::name('xexhibitor_forms')
                    ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                    ->find();
                if(empty($res)){
                    Db::name('xexhibitor_forms')->insert(['event_id'=>$eventId,'company_id'=>$companyId,
                        'form_id'=>$formId,'main_type'=>$form['main_type'],'type'=>'Badge','status'=>1,'create_time'=>date('Y-m-d H:i:s',time())]);
                }else{
                    Db::name('xexhibitor_forms')
                        ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                        ->update(['main_type'=>$form['main_type'],'type'=>'Badge','status'=>1,'update_time'=>date('Y-m-d H:i:s',time())]);
                }
            }
            Db::name('xform_badge')->insertAll($addData);
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

    public function getVendorReportForPage($eventId,$formId,$curr_page,$page_limit){
        $data = Db::name('xform_badge')
            ->alias('a')
            ->field('a.*,b.name as company_name,c.name as booth_name')
            ->join('xcompanies b','a.company_id=b.id')
            ->join('xbooths c','b.booth_id=c.id')
            ->where(['a.event_id'=>$eventId,'a.form_id'=>$formId,'a.is_draft'=>0,'a.is_last_submit'=>1])
            ->limit($page_limit * ($curr_page - 1), $page_limit)->select();
        return $data;
    }

    public function getVendorReportCount($eventId,$formId){
        $data = Db::name('xform_badge')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_draft'=>0,'is_last_submit'=>1])
            ->count();
        return $data;
    }
}