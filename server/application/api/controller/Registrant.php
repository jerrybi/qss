<?php
/**
 * QSS API v1 — Registrant 控制器
 *
 * 核心功能: 传入 unique_id，获取对应参展者/用户的资料数据
 *
 * 安全机制:
 *   - Bearer Token 鉴权 (Authorization: Bearer *** *   - 数据隔离: event_id 从 Token 中自动提取，Exhibitor 只能查询自己所属 event 下的数据
 *   - 敏感字段过滤: 自动遮盖隐私字段 (按配置)
 *   - 速率限制: X-Rate-Limit-* 响应头
 *
 * @author QSS
 */

namespace app\api\controller;

use think\Db;
use think\facade\Request;

class Registrant extends RestBase
{
    /**
     * GET /api/v1/registrants/:unique_id
     *
     * 查询单个参展者的完整资料
     *
     * 路径参数:
     *   unique_id  — 用户的 unique_id (GUID 格式)
     *
     * 查询参数:
     *   fields     — 可选，逗号分隔的字段名，只返回指定字段
     *
     * 注意: event_id 从 Bearer Token 中自动提取，无需手动传递。
     *       Exhibitor 创建时已绑定 event，Token 中携带 event_id。
     *
     * 响应 (200):
     * {
     *   "id": 123,
     *   "unique_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *   "event_id": 5,
     *   "event_name": "Tech Summit 2025",
     *   "login_name": "user@example.com",
     *   "checkin_status": 1,
     *   "checkin_time": "2025-07-01 10:30:00",
     *   "type": "exhibitor",
     *   "zone": "Hall A",
     *   "table_no": "T-12",
     *   "data_fields": {
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     ...
     *   },
     *   "scan_records": [...]
     * }
     */
    public function read($unique_id = '')
    {
        // 1. Bearer Token 鉴权 (从 token 中自动提取 exhibitor 的 event_id)
        $this->authenticate();

        $fieldsFilter = Request::param('fields', '');

        // 2. 校验必填参数
        if (empty($unique_id)) {
            $this->error('Bad Request', 'unique_id is required', 400);
        }

        // 3. 从 exhibitor 信息中获取 event_id (Token 中已携带)
        $eventId = isset($this->exhibitor['event_id']) ? $this->exhibitor['event_id'] : 0;
        if (empty($eventId)) {
            $this->error('Forbidden', 'No event associated with your account', 403);
        }

        // 4. 查询用户基础信息
        $user = Db::name('xusers')
            ->alias('a')
            ->field("a.id, a.unique_id, a.event_id, a.login_name, a.status,
                     a.type, a.checkin_status, a.checkin_time,
                     e.name as event_name, e.enable_track,
                     z.name as zone, t.name as table_no")
            ->join('xevents e', 'e.id = a.event_id', 'left')
            ->join('xzones z', 'z.id = a.zone_id', 'left')
            ->join('xtables t', 't.id = a.table_id', 'left')
            ->where('a.unique_id', $unique_id)
            ->where('a.event_id', $eventId)
            ->where('a.status', 1)
            ->find();

        if (empty($user)) {
            $this->error('Not Found', 'Registrant not found for the given unique_id and event_id', 404);
        }

        // 5. 查询用户自定义数据字段 (xuser_datas 表)
        //     注意: MySQL 中 key 是保留字，ThinkPHP 会自动加反引号
        $userDatas = Db::name('xuser_datas')
            ->where('user_id', $user['id'])
            ->where('status', 1)
            ->field('`key`, `value`')
            ->select();

        // 转换为 key-value 字典
        $dataFields = [];
        if ($userDatas) {
            foreach ($userDatas as $item) {
                $dataFields[$item['key']] = $item['value'];
            }
        }

        // 6. 查询扫描记录 (如果有扫描记录表)
        $scanRecords = [];
        try {
            $scanRecords = Db::name('xscan_records')
                ->where('user_id', $user['id'])
                ->order('create_time', 'desc')
                ->limit(20)
                ->select();
        } catch (\Exception $e) {
            // 表不存在时忽略
        }

        // 7. 组装完整响应
        $result = [
            'id'             => (int)$user['id'],
            'unique_id'      => $user['unique_id'],
            'event_id'       => (int)$user['event_id'],
            'event_name'     => $user['event_name'],
            'login_name'     => $user['login_name'],
            'type'           => $user['type'],
            'status'         => (int)$user['status'],
            'checkin_status' => (int)$user['checkin_status'],
            'checkin_time'   => $user['checkin_time'],
            'zone'           => $user['zone'],
            'table_no'       => $user['table_no'],
            'enable_track'   => $user['enable_track'],
            'data_fields'    => $dataFields,
            'scan_records'   => $scanRecords,
        ];

        // 8. 字段筛选 (fields 参数)
        if (!empty($fieldsFilter)) {
            $requestedFields = array_map('trim', explode(',', $fieldsFilter));
            $filtered = [];
            foreach ($requestedFields as $field) {
                if (isset($result[$field])) {
                    $filtered[$field] = $result[$field];
                } elseif (isset($dataFields[$field])) {
                    $filtered[$field] = $dataFields[$field];
                }
            }
            $result = $filtered;
        }

        $this->success($result);
    }

    /**
     * GET /api/v1/registrants
     *
     * 分页查询参展者列表
     *
     * 查询参数:
     *   page      — 页码 (默认 1)
     *   per-page  — 每页条数 (默认 20, 最大 100)
     *   search    — 搜索关键词 (first_name / last_name / email / serial_number)
     *   fields    — 可选，字段筛选
     *
     * 注意: event_id 从 Bearer Token 中自动提取，无需手动传递。
     *
     * 响应 (200): 分页格式 { items: [...], _meta: {...} }
     */
    public function index()
    {
        $this->authenticate();

        // 从 exhibitor 信息中获取 event_id (Token 中已携带)
        $eventId = isset($this->exhibitor['event_id']) ? $this->exhibitor['event_id'] : 0;
        if (empty($eventId)) {
            $this->error('Forbidden', 'No event associated with your account', 403);
        }

        $page = max(1, Request::param('page', 1, 'intval'));
        $perPage = min(100, Request::param('per-page', 20, 'intval'));
        $search = Request::param('search', '');

        // 构建查询
        $model = Db::name('xusers')
            ->alias('a')
            ->field("a.id, a.unique_id, a.event_id, a.login_name,
                     a.type, a.checkin_status, a.checkin_time,
                     e.name as event_name,
                     z.name as zone, t.name as table_no")
            ->join('xevents e', 'e.id = a.event_id', 'left')
            ->join('xzones z', 'z.id = a.zone_id', 'left')
            ->join('xtables t', 't.id = a.table_id', 'left')
            ->where('a.event_id', $eventId)
            ->where('a.status', 1);

        // 关键词搜索
        if (!empty($search)) {
            $model->where('a.login_name|a.unique_id', 'like', '%' . $search . '%');
        }

        // 统计总数
        $totalCount = Db::name('xusers')
            ->where('event_id', $eventId)
            ->where('status', 1)
            ->count();

        // 分页查询
        $items = $model
            ->order('a.id', 'asc')
            ->limit($perPage * ($page - 1), $perPage)
            ->select();

        $this->successPaginated($items, $totalCount, $page, $perPage);
    }
}
