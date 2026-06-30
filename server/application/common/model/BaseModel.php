<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2018/10/25
 * Time: 16:21
 */

namespace app\common\model;

use think\Model;

class BaseModel extends Model
{
    protected $treeIds;
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->treeIds = [];
    }
    /**
     * 最新补充，为了添加 令牌验证功能，防止CSRF攻击
     * @param $validate 此为传入的 Validate类
     * @param array $checkData 所要验证的数据组(不包含 __TOKEN__)
     * @param $tokenData 所要验证的 __Toekn__ 数据组
     * @param string $scene 验证场景 默认default包含添加和更新，可自行扩展
     * @return array
     */
    public function validate($validate, $checkData = [], $tokenData, $scene = 'default')
    {
        $checkFlag = false;
        if (!$validate->scene($scene)->check($checkData)) {
            $errMsg = $validate->getError();
            $message = $errMsg ? $errMsg : 'verify failed';
        } else {
            if (!$validate->scene('token')->check($tokenData)) {
                $errMsg = $validate->getError();
                $message = $errMsg ? $errMsg : 'verify failed';
            } else {
                $checkFlag = true;
                $message = 'verify success';
            }
        }
        return ['tag' => $checkFlag, 'message' => $message];
    }

    public function getTreeIds($arr){
        $this->treeIds = [];
        $this->pushTreeIds($arr);
        return $this->treeIds;
    }

    public function pushTreeIds($arr){
        if($arr){
            foreach($arr as $v){
                array_push($this->treeIds,$v['id']);
                if(isset($v['children'])){
                    $this->pushTreeIds($v['children']);
                }
            }
        }
    }

    public function findTreeNode($arr,$id){
        if($arr){
            foreach($arr as $v){
                if($v['id'] == $id){
                    return $v;
                }
                if(isset($v['children']) && count($v['children']) > 0){
                    $item = $this->findTreeNode($v['children'],$id);
                    if($item){
                        return $item;
                    }
                }
            }
        }
        return null;
    }
}