# QSS Project

## Project Paths

| Resource | Path |
|---|---|
| Project root | `/Volumes/projects/qss/project/` |
| App (React Native client) | `app/` |
| Server (PHP backend) | `server/` |
| Git repo | `git@github.com:jerrybi/qss.git` |

## Project Structure

QSS 项目分为两部分：

- **app/** — React Native 客户端（Android/iOS）
- **server/** — PHP 服务端（ThinkPHP 5.1 框架）
- **qss-app/** — 已从 git 排除（`.gitignore`），不要提交

## Server 架构

```
server/
├── application/
│   ├── api/controller/        ← REST API 控制器 (含 API v1)
│   │   ├── RestBase.php        ← API 基类 (Bearer Token 鉴权 + 限流)
│   │   ├── Oauth.php           ← POST /api/v1/oauth/token
│   │   ├── Registrant.php      ← GET /api/v1/registrants
│   │   ├── User.php            ← App 端用户 API
│   │   └── Index.php           ← App 端业务 API
│   ├── cms/controller/         ← Admin 后台控制器
│   ├── exhibitor/controller/   ← Exhibitor 前台控制器
│   ├── common/
│   │   ├── controller/         ← 基类 (ApiBase, Base, UserBase)
│   │   ├── model/              ← 数据模型 (Xexhibitors, Xusers, ...)
│   │   └── lib/                ← 工具库 (IAuth, MyRedis, Tools, ...)
│   └── ...
├── config/
│   ├── sys_auth.php            ← 认证配置 (AES key, JWT key, session)
│   └── database.php
├── route/route.php             ← 路由配置
└── database/                   ← SQL 迁移脚本
```

## API v1 — Exhibitor REST API

参考 [Swoogo API](https://developer.swoogo.com/api-reference/introduction) 设计。

### 安全机制

| 机制 | 说明 |
|------|------|
| OAuth2 Bearer Token | Exhibitor 用 api_key:api_secret 换取 JWT Token (30 分钟过期) |
| 数据隔离 | Exhibitor 只能查询自己所属 event_id 下的数据 |
| 速率限制 | 2000 请求 / 10 分钟窗口 (Redis 滑动窗口) |
| 访问日志 | 所有 API 请求记录到 `xapi_logs` 表 |
| Secret 哈希存储 | api_secret 在数据库中以 SHA256(private_key salt) 存储 |

### 数据库

执行迁移: `server/database/api_v1_migration.sql`

| 新增表/字段 | 说明 |
|------------|------|
| `xexhibitors.api_key` | API Key (consumer key), 格式 `qss_` + 32 hex |
| `xexhibitors.api_secret` | API Secret 哈希值 (SHA256) |
| `xapi_tokens` | Bearer Token 管理表 |
| `xapi_logs` | API 访问日志表 |

### API 端点

**Base URL**: `/api/v1`

#### 1. 获取 Token

```
POST /api/v1/oauth/token

Headers:
  Authorization: Basic base64...t)
  Content-Type: application/x-www-form-urlencoded

Body:
  grant_type=client_credentials

Response 200:
{
  "access_token": "eyJ0eXA...",
  "expires_at": "2025-07-01 12:30:00",
  "type": "bearer"
}
```

#### 2. 查询单个用户资料

```
GET /api/v1/registrants/{unique_id}?event_id=5

Headers:
  Authorization: Bearer ***

可选参数:
  fields=id,email,first_name,last_name   ← 字段筛选

Response 200:
{
  "status": "success",
  "data": {
    "id": 123,
    "unique_id": "xxx-xxx-xxx",
    "event_id": 5,
    "event_name": "Tech Summit 2025",
    "checkin_status": 1,
    "zone": "Hall A",
    "data_fields": {
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com"
    }
  }
}
```

#### 3. 分页查询用户列表

```
GET /api/v1/registrants?event_id=5&page=1&per-page=20

Response 200 (Swoogo 分页格式):
{
  "items": [...],
  "_meta": {
    "totalCount": 95,
    "pageCount": 5,
    "currentPage": 1,
    "perPage": 20
  }
}
```

### 错误响应格式

```json
{
  "name": "Not Found",
  "message": "Registrant not found",
  "code": 0,
  "status": 404
}
```

| HTTP Status | 含义 |
|------------|------|
| 400 | 参数错误 |
| 401 | 未认证 / Token 过期 |
| 403 | 无权限 (跨 event 访问) |
| 404 | 用户不存在 |
| 429 | 超出速率限制 |
| 500 | 服务器内部错误 |

### 生成 API 密钥

在后台代码中调用:

```php
$exhibitorModel = new \app\common\model\Xexhibitors();
$result = $exhibitorModel->generateApiCredentials($exhibitorId);
// $result['api_key']    → 存储到数据库
// $result['api_secret'] → 明文仅返回一次，需安全保存
```

## 关键技术点

- **ThinkPHP 5.1**: `Db::name('表名')` 查询，`Request::instance()` 获取请求
- **xuser_datas 表**: 列名 `key`/`value` 是 MySQL 保留字，查询时用反引号 `` `key` `` `` `value` ``
- **JWT**: 用 Firebase/JWT 库，HS256 签名，密钥在 `config/sys_auth.php` 的 `API_TOKEN_KEY`
- **Redis**: 速率限制用 `MyRedis::getInstance()`，Key 格式 `api_rate_limit_{exhibitor_id}`
- **认证流程**: IAuth::createToken() 生成 JWT → IAuth::checkToken() 校验
