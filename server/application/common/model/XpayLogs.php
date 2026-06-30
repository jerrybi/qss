<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;


class XpayLogs extends BaseModel
{
    public function getOrderByOrderNo($orderNo){
        return $this->where(['order_id' => $orderNo])->find();
    }
}