<?php
/**
 * QSS API v1 — REST API 基类控制器
 *
 * 参考 Swoogo API 设计:
 *   - Bearer Token 鉴权 (Authorization: Bearer <token>)
 *   - 统一 JSON 错误格式 {name, message, code, status}
 *   - 速率限制 (X-Rate-Limit-* 响应头)
 *   - 分页 / 字段筛选
 *
 * @author QSS
 */

namespace app\api\controller;

use app\common\lib\IAuth;
use app\common\lib\MyRedis;
use think\Db;
use think\Request;

class RestBase
{
    /** @var int|null 当前认证 exhibitor ID */
    protected $exhibitorId = null;

    /** @var array|null 当前 exhibitor 信息 */
    protected $exhibitor = null;

    /** @var int 速率限制: 每 10 分钟窗口最大请求数 */
    protected $rateLimitPerWindow = 2000;

    /** @var int 窗口大小 (秒) */
    protected $rateLimitWindow = 600;

    /** @var float 请求开始时间 */
    private $startTime;

    /**
     * 构造函数 — 统一设置 CORS 和 JSON 响应头
     */
    public function __construct()
    {
        $this->startTime = microtime(true);

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, Origin, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

        // 处理 CORS 预检
        if (Request::instance()->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * 认证中间件 — 校验 Bearer Token
     * @return bool
     */
    protected function authenticate()
    {
        $request = Request::instance();
        $authHeader = $request->header('authorization');

        if (empty($authHeader)) {
            $this->error('Unauthorized', 'Missing Authorization header', 401);
        }

        // 解析 Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $this->error('Unauthorized', 'Invalid token format. Expected: Bearer <token>', 401);
        }

        $token = trim($matches[1]);

        // 校验 JWT Token
        $result = IAuth::checkToken($token);
        if ($result['code'] != '200') {
            $msg = $result['code'] == '103' ? 'Token expired' : 'Invalid token';
            $this->error('Unauthorized', $msg, 401);
        }

        $tokenData = $result['data'];
        $scopes = isset($tokenData['scopes']) ? $tokenData['scopes'] : '';

        if ($scopes !== 'api_v1') {
            $this->error('Forbidden', 'Token scope mismatch', 403);
        }

        $this->exhibitorId = isset($tokenData['data']['exhibitor_id'])
            ? intval($tokenData['data']['exhibitor_id']) : 0;

        if (!$this->exhibitorId) {
            $this->error('Unauthorized', 'Invalid token payload', 401);
        }

        // 加载 exhibitor 信息
        $this->exhibitor = Db::name('xexhibitors')
            ->where(['id' => $this->exhibitorId, 'status' => 1])
            ->find();

        if (empty($this->exhibitor)) {
            $this->error('Forbidden', 'Account is disabled or not found', 403);
        }

        // 速率限制检查
        $this->checkRateLimit();

        return true;
    }

    /**
     * 速率限制检查 — 基于 Redis 滑动窗口
     */
    protected function checkRateLimit()
    {
        try {
            $redis = MyRedis::getInstance();
            $key = 'api_rate_limit_' . $this->exhibitorId;
            $count = $redis->get($key);

            if ($count === false || $count === null) {
                $redis->setEx($key, 1, $this->rateLimitWindow);
                $count = 1;
            } else {
                $count = (int)$count + 1;
                $redis->setEx($key, $count, $this->rateLimitWindow);
            }

            $remaining = max(0, $this->rateLimitPerWindow - $count);
            $ttl = $redis->ttl($key);
            $resetSeconds = $ttl > 0 ? $ttl : $this->rateLimitWindow;

            header('X-Rate-Limit-Limit: ' . $this->rateLimitPerWindow);
            header('X-Rate-Limit-Remaining: ' . $remaining);
            header('X-Rate-Limit-Reset: ' . $resetSeconds);

            if ($count > $this->rateLimitPerWindow) {
                $this->error('Too Many Requests', 'Rate limit exceeded. Try again in ' . $resetSeconds . ' seconds.', 429);
            }
        } catch (\Exception $e) {
            // Redis 不可用时不阻塞请求，仅记录日志
            \think\facade\Log::warning('Rate limit check failed: ' . $e->getMessage());
        }
    }

    /**
     * 成功响应 — Swoogo 风格 JSON
     * @param mixed $data
     * @param string $message
     */
    protected function success($data = [], $message = 'OK')
    {
        $this->logRequest(200);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 分页响应 — Swoogo 风格 items + _meta + _links
     * @param array $items
     * @param int $totalCount
     * @param int $page
     * @param int $perPage
     */
    protected function successPaginated($items, $totalCount, $page, $perPage)
    {
        $this->logRequest(200);
        $pageCount = $perPage > 0 ? (int)ceil($totalCount / $perPage) : 1;
        echo json_encode([
            'items' => $items,
            '_meta' => [
                'totalCount' => (int)$totalCount,
                'pageCount' => $pageCount,
                'currentPage' => (int)$page,
                'perPage' => (int)$perPage
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 错误响应 — Swoogo 风格统一格式
     * @param string $name    错误名称
     * @param string $message 错误描述
     * @param int    $status  HTTP 状态码
     * @param int    $code    内部错误码
     */
    protected function error($name, $message, $status = 400, $code = 0)
    {
        http_response_code($status);
        $this->logRequest($status);
        echo json_encode([
            'name' => $name,
            'message' => $message,
            'code' => $code,
            'status' => $status
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 记录 API 访问日志
     * @param int $responseCode
     */
    private function logRequest($responseCode)
    {
        $request = Request::instance();
        $responseTime = (int)((microtime(true) - $this->startTime) * 1000);

        try {
            Db::name('xapi_logs')->insert([
                'exhibitor_id'  => $this->exhibitorId,
                'endpoint'      => $request->path(),
                'method'        => $request->method(),
                'params'        => json_encode($request->param(), JSON_UNESCAPED_UNICODE),
                'ip'            => $request->ip(),
                'user_agent'    => substr($request->header('user-agent', ''), 0, 500),
                'response_code' => $responseCode,
                'response_time' => $responseTime,
                'created_at'    => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // 日志写入失败不影响主流程
        }
    }

    /**
     * 校验必填参数
     * @param array $required
     * @param array $params
     * @return bool
     */
    protected function validateRequired($required, $params)
    {
        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] === '' || $params[$field] === null) {
                $this->error('Bad Request', "Missing required parameter: {$field}", 400);
            }
        }
        return true;
    }
}
