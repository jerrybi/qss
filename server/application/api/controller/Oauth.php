<?php
/**
 * QSS API v1 — OAuth Token 控制器
 *
 * 参考 Swoogo POST /oauth2/token 设计
 *
 * 认证流程:
 *   1. Exhibitor 在后台获取 api_key + api_secret
 *   2. 调用本接口用 api_key:api_secret 的 Basic Auth 换取 Bearer Token
 *   3. 后续请求携带 Authorization: Bearer *** *   4. Token 有效期 30 分钟，过期后重新获取
 *
 * @author QSS
 */

namespace app\api\controller;

use app\common\lib\IAuth;
use app\common\lib\Tools;
use think\Db;
use think\Request;

class Oauth extends RestBase
{
    /**
     * POST /api/v1/oauth/token
     *
     * 请求头:
     *   Authorization: Basic base64(api_key:api_secret)
     *   Content-Type: application/x-www-form-urlencoded
     *
     * 请求体:
     *   grant_type=client_credentials
     *
     * 成功响应 (200):
     *   {
     *     "access_token": "eyJ0eXAiOiJKV1Q...",
     *     "expires_at": "2025-07-01 12:30:00",
     *     "type": "bearer"
     *   }
     */
    public function token()
    {
        $request = Request::instance();

        // 仅允许 POST
        if ($request->method() !== 'POST') {
            $this->error('Method Not Allowed', 'Only POST is supported', 405);
        }

        // 校验 grant_type
        $grantType = $request->post('grant_type', '');
        if ($grantType !== 'client_credentials') {
            $this->error('Bad Request', 'grant_type must be client_credentials', 400);
        }

        // 解析 Basic Auth 头
        $authHeader = $request->header('authorization');
        if (empty($authHeader) || !preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
            $this->error('Unauthorized', 'Missing or invalid Basic Authorization header', 401);
        }

        // Base64 解码
        $decoded = base64_decode(trim($matches[1]), true);
        if ($decoded === false || strpos($decoded, ':') === false) {
            $this->error('Unauthorized', 'Invalid credentials encoding', 401);
        }

        list($apiKey, $apiSecret) = explode(':', $decoded, 2);
        $apiKey = trim($apiKey);
        $apiSecret = trim($apiSecret);

        // 校验 API 凭证
        if (empty($apiKey) || empty($apiSecret)) {
            $this->error('Unauthorized', 'api_key and api_secret are required', 401);
        }

        // 查询 exhibitor
        $exhibitor = Db::name('xexhibitors')
            ->where(['api_key' => $apiKey, 'status' => 1])
            ->find();

        if (empty($exhibitor)) {
            $this->error('Unauthorized', 'Invalid api_key', 401);
        }

        // 校验 api_secret
        if ($exhibitor['api_secret'] !== hash('sha256', $apiSecret . $exhibitor['private_key'])) {
            $this->error('Unauthorized', 'Invalid api_secret', 401);
        }

        // 生成 JWT Bearer Token
        $expireSeconds = 1800; // 30 分钟 (与 Swoogo 一致)
        $tokenData = [
            'exhibitor_id' => $exhibitor['id'],
            'event_id'     => $exhibitor['event_id'],
            'unique_id'    => $exhibitor['unique_id'],
        ];

        $result = IAuth::createToken($tokenData, $expireSeconds, 'api_v1');

        if ($result['code'] != '200') {
            $this->error('Internal Server Error', 'Failed to generate token', 500);
        }

        $accessToken = $result['data'];
        $expiresAt = date('Y-m-d H:i:s', time() + $expireSeconds);

        // 记录 Token (用于审计/撤销)
        try {
            Db::name('xapi_tokens')->insert([
                'exhibitor_id' => $exhibitor['id'],
                'access_token' => $accessToken,
                'expires_at'   => $expiresAt,
                'client_ip'    => $request->ip(),
                'created_at'   => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // 记录失败不影响令牌发放
        }

        // 返回 Swoogo 风格响应
        echo json_encode([
            'access_token' => $accessToken,
            'expires_at'   => $expiresAt,
            'type'         => 'bearer'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
