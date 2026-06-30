<?php

namespace app\common\model;

use app\common\validate\Xserver;
use \think\Model;

/**
 * 配置项 model处理类
 * Class Xconfigs
 * @package app\common\model
 */
class Xservers extends BaseModel
{
    protected $validate;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->validate = new Xserver();
    }


    /**
     * 获取全部可修改状态的 数据
     * @param int $id
     * @return array|null|\PDOStatement|string|Model
     */
    public function getServerInfo()
    {
        $res = $this
            ->field('*')
            ->find();
        return $res;
    }

    /**
     * 更新数据
     * @param $id
     * @param $data
     * @return array
     */
    public function updateData($data)
    {
        $tag = 0;
         $saveData = [
            'id' => $data['id'],
            'server_name' => isset($data['server_name']) ? $data['server_name'] : '',
            'server_description' => isset($data['server_description']) ? $data['server_description'] : '',
            'server_timezone' => isset($data['server_timezone']) ? $data['server_timezone'] : '',
        ];
        $tokenData = ['__token__' => isset($data['__token__']) ? $data['__token__'] : '',];
        $validateRes = $this->validate($this->validate, $saveData, $tokenData);

        if ($validateRes['tag']) {
            $tag = $this
                ->where('id', $data['id'])
                ->update($saveData);
            $validateRes['message'] = $tag ? 'update success' : 'Sorry，data no change';
        }
        $validateRes['tag'] = $tag;
        return $validateRes;
    }
}