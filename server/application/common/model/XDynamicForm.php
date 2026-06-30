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

class XDynamicForm extends BaseModel
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function getLastData($eventId,$companyId,$formId,$isDynamic){
        $res = Db::name('xform_datas')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_dynamic'=>$isDynamic])->order('submit_time','desc')->limit(1)->find();
        if(empty($res)) return [];
        $data = Db::name('xform_datas')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_dynamic'=>$isDynamic,'submit_time'=>$res['submit_time']])->select();
        return $data;
    }

    public function getLastDataWithCatalog($eventId,$companyId,$formId){
        $res = Db::name('xform_datas')
            ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_dynamic'=>0])
            ->order('submit_time','desc')
            ->limit(1)
            ->find();
        if(empty($res)) return [];
        $data = Db::name('xform_datas')->alias('a')
            ->join("xcatalogs b","a.item_catalog_id = b.id","left")
            ->where(['a.event_id'=>$eventId,'a.company_id'=>$companyId,'a.form_id'=>$formId,'a.is_dynamic'=>0,'a.submit_time'=>$res['submit_time']])
            ->field("a.*,b.name,b.type,b.category,b.sub_category,b.description,b.advanced_rate,b.standard_rate,b.have_onsite_rate,b.onsite_rate,b.logo")
            ->select();
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
                'dynamic_name' => '',
                'dynamic_title' => '',
                'dynamic_value' => '',
                'item_catalog_id' => 0,
                'item_name' => '',
                'item_price' => 0.0,
                'item_quantity'=>0,
                'form_submit_name' => isset($input['form_submit_name'])?$input['form_submit_name']:'',
                'form_submit_designation' => isset($input['form_submit_designation'])?$input['form_submit_designation']:'',
                'form_submit_email' => isset($input['form_submit_email'])?$input['form_submit_email']:'',
                'submit_time' => $submitTime,
                'is_draft' =>$isDraft,
                'is_last_submit'=>0,
                'is_dynamic'=>0,
                'company_id' => $companyId,
                'event_id' => $eventId,
                'form_id' => $formId,
                'create_time'=>date('Y-m-d H:i:s',time()),
                'update_time'=>date('Y-m-d H:i:s',time())
            ];
        }else{
            foreach($formDataArr as $key=>$item){
                $addData[] = [
                    'dynamic_name' => '',
                    'dynamic_title' => '',
                    'dynamic_value' => '',
                    'item_catalog_id' => isset($item['item_catalog_id'])?$item['item_catalog_id']:0,
                    'item_name' => isset($item['item_name'])?$item['item_name']:'',
                    'item_price' => isset($item['item_price'])?$item['item_price']:0.0,
                    'item_quantity' => isset($item['item_quantity'])?$item['item_quantity']:0,
                    'form_submit_name' => isset($input['form_submit_name'])?$input['form_submit_name']:'',
                    'form_submit_designation' => isset($input['form_submit_designation'])?$input['form_submit_designation']:'',
                    'form_submit_email' => isset($input['form_submit_email'])?$input['form_submit_email']:'',
                    'submit_time' => $submitTime,
                    'is_draft' =>$isDraft,
                    'is_last_submit'=>$isDraft==1?0:1,
                    'is_dynamic'=>0,
                    'company_id' => $companyId,
                    'event_id' => $eventId,
                    'form_id' => $formId,
                    'create_time'=>date('Y-m-d H:i:s',time()),
                    'update_time'=>date('Y-m-d H:i:s',time())
                ];
            }
        }
        $fields = Db::name('xforms')->where(['event_id'=>$eventId,'id'=>$formId])->field("fields")->find();
        if(!empty($fields) && isset($fields['fields'])){
            $fieldDatas = json_decode($fields['fields'],true);
            if(!empty($fieldDatas)){
                foreach($fieldDatas as $value){
                    $name = $value['name'];
                    $title = $value['title'];
                    if(isset($input[$name])){
                        $addData[] = [
                            'dynamic_name' => $name,
                            'dynamic_title' => $title,
                            'dynamic_value' => $input[$name],
                            'item_catalog_id' => 0,
                            'item_name' => '',
                            'item_price' => 0.0,
                            'item_quantity'=>0,
                            'form_submit_name' => isset($input['form_submit_name'])?$input['form_submit_name']:'',
                            'form_submit_designation' => isset($input['form_submit_designation'])?$input['form_submit_designation']:'',
                            'form_submit_email' => isset($input['form_submit_email'])?$input['form_submit_email']:'',
                            'submit_time' => $submitTime,
                            'is_draft' =>$isDraft,
                            'is_last_submit'=>$isDraft==1?0:1,
                            'is_dynamic'=>1,
                            'company_id' => $companyId,
                            'event_id' => $eventId,
                            'form_id' => $formId,
                            'create_time'=>date('Y-m-d H:i:s',time()),
                            'update_time'=>date('Y-m-d H:i:s',time())
                        ];
                    }
                }
            }
        }
        $tag = 1;
        $error = '';
        $form = Db::name('xforms')->where('id',$formId)->find();
        Db::startTrans();
        try{
            Db::name('xform_datas')->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId,'is_draft'=>1])
                ->delete();
            if($isDraft == 0){
                Db::name('xform_datas')->where(['event_id'=>$eventId,'company_id'=>$companyId,
                    'form_id'=>$formId,'is_last_submit'=>1])->update(['is_last_submit'=>0]);
                $res = Db::name('xexhibitor_forms')
                    ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                    ->find();
                if(empty($res)){
                    Db::name('xexhibitor_forms')->insert(['event_id'=>$eventId,'company_id'=>$companyId,
                        'form_id'=>$formId,'main_type'=>$form['main_type'],'type'=>$input['form_type'],'status'=>1,'create_time'=>date('Y-m-d H:i:s',time())]);
                }else{
                    Db::name('xexhibitor_forms')
                        ->where(['event_id'=>$eventId,'company_id'=>$companyId,'form_id'=>$formId])
                        ->update(['main_type'=>$form['main_type'],'type'=>$input['form_type'],'status'=>1,'update_time'=>date('Y-m-d H:i:s',time())]);
                }
            }
            Db::name('xform_datas')->insertAll($addData);
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
        $data = Db::name('xform_datas')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>0,'is_draft'=>0,'is_last_submit'=>1])
            ->field('item_name,sum(item_quantity) as item_quantity')->group('item_name')->limit($page_limit * ($curr_page - 1), $page_limit)->select();
        return $data;
    }

    public function getVendorReportCount($eventId,$formId){
        $data = Db::name('xform_datas')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>0,'is_draft'=>0,'is_last_submit'=>1])
            ->group('item_name')->count();
        return $data;
    }

    public function getCatalogItemNames($eventId,$formId){
//        $data = Db::name('xform_datas')
//            ->field('item_name')
//            ->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>0,'is_draft'=>0,'is_last_submit'=>1])
//            ->where('item_quantity','>',0)
//            ->group('item_name')
//            ->order('mid(item_name,1,2)','asc')
//            ->order('mid(item_name,3,10)+1','asc')
//            ->select();
        $form = Db::name('xforms')->where(['event_id'=>$eventId,'id'=>$formId])->field('order_items')->find();
        $orderItems = explode("\r\n",$form['order_items']);
        $catIds = [];
        if(!empty($orderItems)){
            $res = Db::name('xcatalogs')->where('category','in',$orderItems)->field('id')->select();
            foreach ($res as $v){
                $catIds[] = $v['id'];
            }
        }
        $sql = "select item_name from ".config('database.prefix')."xform_datas";
        $sql .= " where event_id = '".$eventId."' and form_id = ".$formId." and is_dynamic = 0 and is_draft = 0 and is_last_submit = 1";
        if(count($catIds) > 0 ){
            $sql .= " and item_catalog_id in (".implode(",",$catIds).")";
        }
        $sql .= " and item_quantity > 0";
        $sql .= " group by item_name";
        $sql .= " order by mid(item_name,1,2) asc,mid(item_name,3,10)+1 asc";
        $data = Db::query($sql);
        return $data;
    }

    public function getCatalogCompanies($eventId,$formId){
        $data = Db::name('xform_datas')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>0,'is_draft'=>0,'is_last_submit'=>1])
            ->where('item_quantity','>',0)
            ->group('company_id')->select();
        return $data;
    }

    public function getCatalogItemReport($eventId,$formId){
        $data = Db::name('xform_datas')
            ->alias('a')
            ->join('xcompanies b','a.company_id=b.id')
            ->join('xbooths c','b.booth_id=c.id')
            ->where(['a.event_id'=>$eventId,'a.form_id'=>$formId,'a.is_dynamic'=>0,'a.is_draft'=>0,'a.is_last_submit'=>1])
            ->where('a.item_quantity','>',0)
            ->field('a.*,b.name as company_name,c.name as booth_name')->select();
        return $data;
    }

    public function getVendorDynamicName($eventId,$formId){
        //取最后一次提交的数据中所记录的dynamic_title,dynamic_name的值，因为表单可能经过修改之前提交的数据是旧的
//        $data = Db::name('xform_datas')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>1,'is_draft'=>0,'is_last_submit'=>1])
//            ->field('dynamic_title,dynamic_name')->group('dynamic_title,dynamic_name')->select();

        //由于不同用户不是必填项没有提交，导致提交上来的字段不完整，保险起见，改从form表的field字段来取值
//        $res = Db::name('xform_datas')->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>1,'is_draft'=>0,'is_last_submit'=>1])
//            ->field('company_id')->order('update_time desc')->find();
//        if(!empty($res)){
//            $data = Db::name('xform_datas')
//                ->where(['event_id'=>$eventId,'form_id'=>$formId,'company_id'=>$res['company_id'],'is_dynamic'=>1,'is_draft'=>0,'is_last_submit'=>1])
//                ->field('dynamic_title,dynamic_name')
//                ->select();
//            return $data;
//        }else{
//            return [];
//        }

        $data = [];
        $fields = Db::name('xforms')->where(['event_id'=>$eventId,'id'=>$formId])->field("fields")->find();
        if(!empty($fields) && isset($fields['fields'])){
            $fieldDatas = json_decode($fields['fields'],true);
            if(!empty($fieldDatas)){
                foreach($fieldDatas as $value){
                    $name = $value['name'];
                    $title = $value['title'];
                    $data[] = ['dynamic_title'=>$title,'dynamic_name'=>$name];
                }
            }
        }
        return $data;
    }

    public function getVendorDynamicReportForPage($eventId,$formId,$curr_page,$page_limit,$names){
        if(empty($names)) return [];
        $sql = "select a.company_id,b.name as company_name,c.name as booth_name,d.create_time";
        foreach($names as $value){
            $name = $value['dynamic_name'];
            $sql .= ",max(case a.dynamic_name when '".$name."' then a.dynamic_value else '' end) as '".$name."'";
        }
        $sql .= " from ".config("database.prefix")."xform_datas a ";
        $sql .= "join ".config("database.prefix")."xcompanies b on a.company_id = b.id ";
        $sql .= "join ".config("database.prefix")."xbooths c on b.booth_id = c.id ";
        $sql .= "join ".config("database.prefix")."xform_datas d on a.company_id = d.company_id ";
        $sql .= " where a.event_id='".$eventId."' and a.form_id=".$formId." and a.is_dynamic=1 and a.is_draft=0 and a.is_last_submit=1";
        $sql .= " group by a.company_id order by a.company_id asc";
        $sql .= " limit ".($curr_page-1).",".$page_limit;
        $data = Db::query($sql);
        return $data;
    }

    public function getVendorDynamicReportCount($eventId,$formId,$names){
        if(empty($names)) return 0;
        $data = Db::name("xform_datas")->where(['event_id'=>$eventId,'form_id'=>$formId,'is_dynamic'=>1,'is_draft'=>0,'is_last_submit'=>1])
            ->group('company_id')->count();
        return $data;
    }
}