<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\Xcompany;
use think\Db;

class Xcompanies extends BaseModel
{
    protected $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xcompany();
    }

    public function updateCmsData($input,$id = 1)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            $this->save(['status'=>0],['id'=>$id]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'booth',$id,'remove booth');
        } else {
            $saveData = [
                'name' => isset($input['name'])?$input['name']:'',
                'type' => isset($input['type'])?$input['type']:'',
                'booth_id' => isset($input['booth_id'])?$input['booth_id']:0,
                'admin_account' => isset($input['admin_account'])?$input['admin_account']:'',
                'origin_country' => isset($input['origin_country'])?$input['origin_country']:'',
                'profile' => isset($input['profile'])?$input['profile']:'',
                'logo' => isset($input['logo'])?$input['logo']:'',
                'email' => isset($input['email'])?$input['email']:'',
                'address_line1' => isset($input['address_line1'])?$input['address_line1']:'',
                'address_line2' => isset($input['address_line2'])?$input['address_line2']:'',
                'postal' => isset($input['postal'])?$input['postal']:'',
                'country' => isset($input['country'])?$input['country']:'',
                'phone_country_code' => isset($input['phone_country_code'])?$input['phone_country_code']:'',
                'phone_area_code' => isset($input['phone_area_code'])?$input['phone_area_code']:'',
                'phone_number' => isset($input['phone_number'])?$input['phone_number']:'',
                'fax_country_code' => isset($input['fax_country_code'])?$input['fax_country_code']:'',
                'fax_area_code' => isset($input['fax_area_code'])?$input['fax_area_code']:'',
                'fax_number' => isset($input['fax_number'])?$input['fax_number']:'',
                'website' => isset($input['website'])?$input['website']:'',
                'billing_address_line1' => isset($input['billing_address_line1'])?$input['billing_address_line1']:'',
                'billing_address_line2' => isset($input['billing_address_line2'])?$input['billing_address_line2']:'',
                'billing_postal' => isset($input['billing_postal'])?$input['billing_postal']:'',
                'billing_country' => isset($input['billing_country'])?$input['billing_country']:'',
                'event_id' => isset($input['event_id'])?$input['event_id']:'',
                'parent_id' => isset($input['parent_id'])?$input['parent_id']:0,
                'update_time'=>date('Y-m-d H:i:s',time())
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                $saveTag = $this->save($saveData, ['id' => $id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag, 'company', $id, 'company update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null,$parentId = null)
    {
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        if($parentId !== null){
            $condition['a.parent_id'] = $parentId;
        }
        $res = Db::name('xcompanies')
            ->alias('a')
            ->field('a.*,e.name as event_name,b.name as booth,b.location,b.badge,b.size as area,b.type as stand_type')
            ->join('xevents e','e.id = a.event_id')
            ->join('xbooths b','a.booth_id = b.id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where($condition)
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res:[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @param null $eventId
     * @param null $parentId
     * @return int|string
     */
    public function getCmsDatasCount($search = null,$eventId = null,$parentId = null)
    {
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        if($parentId !== null){
            $condition['a.parent_id'] = $parentId;
        }
        $count = Db::name('xcompanies')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where($condition)
            ->count();
        return $count;
    }

    /**
     * 根据文章ID 获取文章内容
     * @param $id
     * @return array
     */
    public function getCmsDataByID($id)
    {
        $res = Db::name('xcompanies')
            ->alias('a')
            ->field('a.*,b.name as booth,b.location,b.badge,b.size as sqm')
            ->join('xbooths b','a.booth_id = b.id')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function addData($input)
    {
        $addData = [
            'name' => isset($input['name'])?$input['name']:'',
            'type' => isset($input['type'])?$input['type']:'',
            'booth_id' => isset($input['booth_id'])?$input['booth_id']:0,
            'admin_account' => isset($input['admin_account'])?$input['admin_account']:'',
            'origin_country' => isset($input['origin_country'])?$input['origin_country']:'',
            'profile' => isset($input['profile'])?$input['profile']:'',
            'logo' => isset($input['logo'])?$input['logo']:'',
            'email' => isset($input['email'])?$input['email']:'',
            'address_line1' => isset($input['address_line1'])?$input['address_line1']:'',
            'address_line2' => isset($input['address_line2'])?$input['address_line2']:'',
            'postal' => isset($input['postal'])?$input['postal']:'',
            'country' => isset($input['country'])?$input['country']:'',
            'phone_country_code' => isset($input['phone_country_code'])?$input['phone_country_code']:'',
            'phone_area_code' => isset($input['phone_area_code'])?$input['phone_area_code']:'',
            'phone_number' => isset($input['phone_number'])?$input['phone_number']:'',
            'fax_country_code' => isset($input['fax_country_code'])?$input['fax_country_code']:'',
            'fax_area_code' => isset($input['fax_area_code'])?$input['fax_area_code']:'',
            'fax_number' => isset($input['fax_number'])?$input['fax_number']:'',
            'website' => isset($input['website'])?$input['website']:'',
            'billing_address_line1' => isset($input['billing_address_line1'])?$input['billing_address_line1']:'',
            'billing_address_line2' => isset($input['billing_address_line2'])?$input['billing_address_line2']:'',
            'billing_postal' => isset($input['billing_postal'])?$input['billing_postal']:'',
            'billing_country' => isset($input['billing_country'])?$input['billing_country']:'',
            'event_id' => isset($input['event_id'])?$input['event_id']:'',
            'parent_id' => isset($input['parent_id'])?$input['parent_id']:0,
            'status' => 1,
            'create_time'=>date('Y-m-d H:i:s',time()),
            'update_time'=>date('Y-m-d H:i:s',time())
        ];
        $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            $tag = $this->insertGetId($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'company',$this->getLastInsID(),'add company');
            }
            $validateRes['tag'] = $tag>0?1:0;
            $validateRes['id'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function getCmsList($eventId=null,$parentId=null){
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        if($parentId !== null){
            $condition['a.parent_id'] = $parentId;
        }
        $res = Db::name('xcompanies')
            ->alias('a')
            ->field('a.*,b.name as booth,b.location,b.badge')
            ->join('xbooths b','a.booth_id = b.id')
            ->where($condition)
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xcompanies')
            ->alias('a')
            ->field('a.*,b.name as booth,b.location,b.badge')
            ->join('xbooths b','a.booth_id = b.id')
            ->where('a.id',$id)
            ->find();
    }

    public function updateSubProfile($companyId,$param){
        $subCompanyName = $param['sub_company_name'];
        $subEmail = $param['sub_email'];
        $subCountry = $param['sub_country'];
        $subAddressLine1 = $param['sub_address_line1'];
        $subAddressLine2 = $param['sub_address_line2'];
        $subPhoneCountryCode = $param['sub_phone_country_code'];
        $subPhoneAreaCode = $param['sub_phone_area_code'];
        $subPhoneNumber = $param['sub_phone_number'];
        $subFaxCountryCode = $param['sub_fax_country_code'];
        $subFaxAreaCode = $param['sub_fax_area_code'];
        $subFaxNumber = $param['sub_fax_number'];
        $subProfile = $param['sub_profile'];
        $subLogo = $param['sub_logo'];
        $subPostal = $param['sub_postal'];
        $subWebsite = $param['sub_website'];
        $subIndustry = $param['sub_industry'];
        $subProduct = $param['sub_product'];
        $subBillingAddressLine1 = $param['sub_billing_address_line1'];
        $subBillingAddressLine2 = $param['sub_billing_address_line2'];
        $subBillingPostal = $param['sub_billing_postal'];
        $subBillingCountry = $param['sub_billing_country'];
        Db::name('xcompanies')->where('id',$companyId)
            ->update(['sub_company_name'=>$subCompanyName,'sub_email'=>$subEmail,
                'sub_country'=>$subCountry,
                'sub_address_line1'=>$subAddressLine1,'sub_address_line2'=>$subAddressLine2,
                'sub_phone_country_code'=>$subPhoneCountryCode,'sub_phone_area_code'=>$subPhoneAreaCode,
                'sub_phone_number'=>$subPhoneNumber,
                'sub_fax_country_code'=>$subFaxCountryCode,'sub_fax_area_code'=>$subFaxAreaCode,
                'sub_fax_number'=>$subFaxNumber,
                'sub_profile'=>$subProfile,'sub_logo'=>$subLogo,'sub_postal'=>$subPostal,
                'sub_website'=>$subWebsite,'sub_industry'=>$subIndustry,'sub_product'=>$subProduct,
                'sub_billing_address_line1'=>$subBillingAddressLine1,'sub_billing_address_line2'=>$subBillingAddressLine2,
                'sub_billing_postal'=>$subBillingPostal,'sub_billing_country'=>$subBillingCountry,
                'update_time'=>date('Y-m-d H:i:s',time())]);
    }

    public static function getLabelByKey($arr,$key,$default){
        if(empty($arr) || count($arr) == 0) return $default;
        foreach($arr as $value){
            if($value['key'] == $key){
                return !empty($value['label'])?$value['label']:$default;
            }
        }
        return $default;
    }

    public static function getTtitles($attrs){
        $titles = [];
        $titles['name'] = Xcompanies::getLabelByKey($attrs,'name','Company');
        $titles['type'] = Xcompanies::getLabelByKey($attrs,'type','Type');
        $titles['booth'] = Xcompanies::getLabelByKey($attrs,'booth','Booth');
        $titles['origin_country'] = Xcompanies::getLabelByKey($attrs,'origin_country','Country of Origin');
        $titles['email'] = Xcompanies::getLabelByKey($attrs,'email','Email');
        return $titles;
    }

    public static function getTtitles1($attrs){
        $titles = [];
        $titles['name'] = Xcompanies::getLabelByKey($attrs,'name','Company');
        $titles['type'] = Xcompanies::getLabelByKey($attrs,'type','Type');
        $titles['booth'] = Xcompanies::getLabelByKey($attrs,'booth','Booth');
        $titles['location'] = Xcompanies::getLabelByKey($attrs,'location','Location');
        $titles['badge'] = Xcompanies::getLabelByKey($attrs,'badge','Badge');
        $titles['admin_account'] = Xcompanies::getLabelByKey($attrs,'admin_account','Exhibitor Admin');
        $titles['origin_country'] = Xcompanies::getLabelByKey($attrs,'origin_country','Country of Origin');
        $titles['profile'] = Xcompanies::getLabelByKey($attrs,'profile','Company Profile');
        $titles['logo'] = Xcompanies::getLabelByKey($attrs,'logo','Company Logo');
        $titles['email'] = Xcompanies::getLabelByKey($attrs,'email','Contact Email');
        $titles['address_line1'] = Xcompanies::getLabelByKey($attrs,'address_line1','Address Line 1');
        $titles['address_line2'] = Xcompanies::getLabelByKey($attrs,'address_line2','Address Line 2');
        $titles['postal'] = Xcompanies::getLabelByKey($attrs,'postal','Postal/Zip Code');
        $titles['country'] = Xcompanies::getLabelByKey($attrs,'country','Country');
        $titles['phone'] = Xcompanies::getLabelByKey($attrs,'phone','Business Phone');
        $titles['fax'] = Xcompanies::getLabelByKey($attrs,'fax','Fax');
        $titles['website'] = Xcompanies::getLabelByKey($attrs,'website','Website');
        $titles['industry'] = Xcompanies::getLabelByKey($attrs,'industry','Industries and Sectors');
        $titles['product'] = Xcompanies::getLabelByKey($attrs,'product','Products and Services');
        $titles['billing_address_line1'] = Xcompanies::getLabelByKey($attrs,'billing_address_line1','Billing Address Line 1');
        $titles['billing_address_line2'] = Xcompanies::getLabelByKey($attrs,'billing_address_line2','Billing Address Line 2');
        $titles['billing_postal'] = Xcompanies::getLabelByKey($attrs,'billing_postal','Billing Postal/Zip Code');
        $titles['billing_country'] = Xcompanies::getLabelByKey($attrs,'billing_country','Billing Country');
        return $titles;
    }
}