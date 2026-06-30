<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\common\lib;
use think\cache\driver\Redis;

/**
 * Description of MyRedis
 *
 * @author 冬明
 */
class MyRedis {
    private static $instance;

    public $redis;

    private function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect(config('redis.host'), config('redis.port'), config('redis.time_out'));
        $this->redis->auth(config('redis.password'));
        if (config('redis.auth')) {
            $this->redis->auth(config('redis.auth'));
        }
    }

    public static function getInstance() {
        if (!self::$instance instanceof MyRedis) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key) {
        return $this->redis->get($key);
    }

    public function lPush($key, $value) {
        $this->redis->lPush($key, $value);
    }

    public function rPush($key, $value) {
        $this->redis->rPush($key, $value);
    }

    public function lPop($key) {
        return $this->redis->lPop($key);
    }

    public function rPop($key) {
        return $this->redis->rPop($key);
    }

    public function set($key, $value) {
        $this->redis->set($key, $value);
    }

    //    只在键值不存在时才对键进行设置操作,用来实现锁竞争机制，同一时间保证只有一个操作进行
    public function setNx($key, $value,$expire_in) {
        return $this->redis->set($key, $value,array('nx','ex'=>$expire_in));
    }
    
    public function setEx($key, $value, $expire_in) {
        $this->redis->setex($key, $expire_in, $value);
    }

    public function ttl($key) {
        return $this->redis->ttl($key);
    }

    public function expire($key, $ttl) {
        $this->redis->expire($key, $ttl);
    }

    public function del($key) {
        $this->redis->del($key);
    }

    public function zRem($key, $member) {
        $this->redis->zRem($key, $member);
    }

    public function zAdd($key, $score, $option) {
        $this->redis->zAdd($key, $score, $option);
    }

    public function zRangByScore($key, $start, $end) {
        return $this->redis->zRangeByScore($key, $start, $end);
    }

    public function zRange($key, $start, $end, $withscores = null) {
        return $this->redis->zRange($key, $start, $end, $withscores);
    }

    public function lRange($key, $start, $end) {
        return $this->redis->lRange($key, $start, $end);
    }

    public function hSet($key, $hashKeys, $value) {
        return $this->redis->hSet($key, $hashKeys, $value);
    }

    public function hGet($key, $hashKeys) {
        return $this->redis->hGet($key, $hashKeys);
    }

    public function hGetAll($key) {
        return $this->redis->hGetAll($key);
    }

    public function hDel($key, $hashKeys) {
        return $this->redis->hDel($key, $hashKeys);
    }
}
