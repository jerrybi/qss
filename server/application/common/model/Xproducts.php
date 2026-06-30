<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;


class Xproducts extends BaseModel
{
    public function getProductList() {
        return $this->select();
    }

    public function getBrochureNum($code){
        $data = $this->where('code',$code)->find();
        return intval($data['max_brochure']);
    }

    public function getProduct($code){
        $data = $this->where('code',$code)->find();
        return $data;
    }
}