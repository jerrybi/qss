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

class XmanPower extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getLastData($eventId,$companyId,$formId){
        $res = Db::name('xform_manpower')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
            ->order('submit_time','desc')->limit(1)->find();
        if(empty($res)) return [];
        $data = Db::name('xform_manpower')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,
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
                'item_catalog_id' => 0,
                'item_name' => '',
                'item_price' => '',
                'item_from_date'=>date('Y-m-d',time()),
                'item_to_date'=>date('Y-m-d',time()),
                'item_duration'=>0,
                'item_staff_num'=>0,
                'language' => '',
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
                    'item_catalog_id' => isset($item['item_catalog_id'])?$item['item_catalog_id']:0,
                    'item_name' => isset($item['item_name'])?$item['item_name']:'',
                    'item_price' => isset($item['item_price'])?$item['item_price']:'',
                    'item_from_date' => isset($item['item_from_date'])?$item['item_from_date']:date('Y-m-d',time()),
                    'item_to_date' => isset($item['item_to_date'])?$item['item_to_date']:date('Y-m-d',time()),
                    'item_duration' => isset($item['item_duration'])?$item['item_duration']:0,
                    'item_staff_num' => isset($item['item_staff_num'])?$item['item_staff_num']:0,
                    'language' => isset($input['language'])?$input['language']:'',
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
            Db::name('xform_manpower')->where(['event_id'=>$eventId,'company_id'=>$companyId,
                'form_id'=>$formId,'is_draft'=>1])->delete();
            if($isDraft == 0){
                Db::name('xform_manpower')->where(['event_id'=>$eventId,'company_id'=>$companyId,
                    'form_id'=>$formId,'is_last_submit'=>1])->update(['is_last_submit'=>0]);
                $res = Db::name('xexhibitor_forms')
                    ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                    ->find();
                if(empty($res)){
                    Db::name('xexhibitor_forms')->insert(['event_id'=>$eventId,'company_id'=>$companyId,
                        'form_id'=>$formId,'main_type'=>$form['main_type'],'type'=>'Manpower','status'=>1,'create_time'=>date('Y-m-d',time())]);
                }else{
                    Db::name('xexhibitor_forms')
                        ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                        ->update(['main_type'=>$form['main_type'],'type'=>'Manpower','status'=>1,'update_time'=>date('Y-m-d',time())]);
                }
            }
            Db::name('xform_manpower')->insertAll($addData);
            Db::commit();
        }catch (Exception $e){
            $tag = 0;
            $error = $e->getMessage();
            Db::rollback();
        }
        $validateRes['tag'] = $tag;
        $validateRes['message'] = $tag ? ($isDraft?'Saved successful':'Submitted successful') : 'Submitted failed '.$error;
        return $validateRes;
    }

    public function getVendorReportForPage($eventId,$formId,$curr_page,$page_limit){
        $data = Db::name('xform_manpower')
            ->alias('a')
            ->field('a.*,b.name as company_name,c.name as booth_name')
            ->join('xcompanies b','a.company_id=b.id')
            ->join('xbooths c','b.booth_id=c.id')
            ->where(['a.event_id'=>$eventId,'a.form_id'=>$formId,'a.is_draft'=>0,'a.is_last_submit'=>1])
            ->limit($page_limit * ($curr_page - 1), $page_limit)
            ->select();
        return $data;
    }

    public function getVendorReportCount($eventId,$formId){
        $data = Db::name('xform_manpower')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_draft'=>0,'is_last_submit'=>1])
            ->count();
        return $data;
    }
}