<?php
/**
 * Created by PhpStorm.
 * User: moTzxx
 * Date: 2019/4/26
 * Time: 10:12
 */

namespace app\common\model;


class Xorders extends BaseModel
{
    public function getOrderByUserId($userId) {
        return $this->where(['user_id' => $userId,'end_date'=>['<=',time()]])->find();
    }

    public function getOrderByOrderId($orderId) {
        return $this->where(['order_id' => $orderId])->find();
    }
}