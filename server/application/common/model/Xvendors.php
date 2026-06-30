<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;
use app\common\validate\Xvendor;
use think\Db;

class Xvendors extends BaseModel
{
    protected $validate;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xvendor();
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
                'event_id' => isset($input['event_id'])?$input['event_id']:''
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                $saveTag = $this->save($saveData, ['id' => $id]);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag, 'vendor', $id, 'vendor update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null,$eventId = null)
    {
        $condition = ['a.status'=>'1'];
        if(!empty($eventId)){
            $condition['a.event_id'] = $eventId;
        }
        $res = Db::name('xvendors')
            ->alias('a')
            ->field('a.*,e.name as event_name')
            ->join('xevents e','e.id = a.event_id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where($condition)
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res:[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsDatasCount($search = null)
    {
        $count = Db::name('xvendors')
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name', '%' . $search . '%')
            ->where('a.status','1')
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
        $res = Db::name('xvendors')
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    public function addData($input)
    {
        $addData = [
            'name' => isset($input['name'])?$input['name']:'',
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
            'event_id' => isset($input['event_id'])?$input['event_id']:'',
            'status' => 1,
            'create_time'=>Date('Y-m-d H:i:s',time()),
            'update_time'=>Date('Y-m-d H:i:s',time())
        ];
        $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            $tag = $this->insertGetId($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'vendor',$this->getLastInsID(),'add vendor');
            }
            $validateRes['tag'] = $tag>0?1:0;
            $validateRes['id'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }

    public function getCmsList($eventId=null){
        $res = Db::name('xvendors')
            ->where('event_id',$eventId)
            ->where('status',1)
            ->select();
        return $res;
    }

    public function getData($id=1){
        return Db::name('xvendors')->where('id',$id)->find();
    }
}