<?php

namespace app\common\model;

use app\common\validate\Xedm;
use think\Db;
use \think\Model;
use app\common\lib\Tools;

/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/1/11
 * Time: 16:45
 */
class Xedms extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xedm();
    }

    /**
     * 获取所有的文章
     * @return array
     */
    public function getDataList()
    {
        $res = $this
            ->field("a.*")
            ->alias('a')//给主表取别名
            ->where('a.status','1')
            ->select();
        //$data = array_merge($data,$data,$data,$data,$data,$data,$data);
        return isset($res) ? $res->toArray() : [];
    }

    /**
     * 根据文章ID 获取文章详情
     * @param $id
     * @return array
     */
    public function getInfoByID($id)
    {
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.status','1')
            ->where("a.id = '" . $id."'")
            ->find();
        return isset($res) ? $res : [];
    }

    /**
     * 后台获取文章数据列表
     * @param $curr_page
     * @param int $limit
     * @param null $search
     * @return array
     */
    public function getCmsDatasForPage($curr_page, $limit = 1, $search = null)
    {
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->whereLike('a.name|a.content', '%' . $search . '%')
            ->where('a.status','1')
            ->order(['a.create_time' => 'desc'])
            ->limit($limit * ($curr_page - 1), $limit)
            ->select();
        return isset($res)?$res->toArray():[];
    }

    /**
     * 后台获取文章总数
     * @param null $search
     * @return int|string
     */
    public function getCmsDatasCount($search = null)
    {
        $count = $this
            ->alias('a')
            ->field('a.id')
            ->whereLike('a.name|a.content', '%' . $search . '%')
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
        $res = $this
            ->alias('a')
            ->field('a.*')
            ->where('a.id', $id)
            ->find();
        return $res;
    }

    /**
     * 更新文章内容
     * @param $input
     * @param int $id
     * @return array
     */
    public function updateCmsData($input,$id = 0)
    {
        $opTag = isset($input['tag']) ? $input['tag'] : 'edit';
        if ($opTag == 'del') {
            Db::name('xedms')
                ->where('id', $id)
                ->update(['status' => 0]);
            $validateRes = ['tag' => 1, 'message' => 'removed successfully'];
            insertCmsOpLogs(1,'EDM',$id,'remove event');
        } else {
            $saveData = [
                'name' => isset($input['name'])?$input['name']:'',
                'content' => isset($input['content'])?$input['content']:''
            ];
            $tokenData = ['__token__' => isset($input['__token__']) ? $input['__token__'] : '',];
            $validateRes = $this->validate($this->validate, $saveData, $tokenData);
            if ($validateRes['tag']) {
                //检查名称是否已经存在
                $result = $this->where('name',$input['name'])->find();
                if(!empty($result) && $result['id'] != $id){
                    return ['tag' => false, 'message' => 'Edm Name Exist!'];
                }
                $saveTag = $this
                    ->where('id', $id)
                    ->update($saveData);
                if ($saveTag) {
                    insertCmsOpLogs($saveTag,'EDM',$id,'edm update');
                }
                $validateRes['tag'] = $saveTag;
                $validateRes['message'] = $saveTag ? 'Edit success' : 'No change';
            }
        }
        return $validateRes;
    }

    /**
     * 进行新文章的添加操作
     * @param $data
     * @return array
     */

    public function addData($data)
    {
        $addData = [
            'name' => isset($data['name'])?$data['name']:'',
            'content' => isset($data['content'])?$data['content']:'',
            'status'=>'1'
        ];
        $tokenData = ['__token__' => isset($data['__token__']) ? $data['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $addData, $tokenData);
        if ($validateRes['tag']) {
            //检查名称是否已经存在
            $result = $this->where('name',$data['name'])->find();
            if(!empty($result)){
                return ['tag' => false, 'message' => 'Edm Name Exist!'];
            }
            $tag = $this->save($addData);
            if ($tag) {
                insertCmsOpLogs($tag,'EDM',$this->getLastInsID(),'add edm');
            }
            $validateRes['tag'] = $tag;
            $validateRes['message'] = $tag ? 'add successfully' : 'add failed';
        }
        return $validateRes;
    }
}