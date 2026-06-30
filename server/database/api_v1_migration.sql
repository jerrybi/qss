-- =====================================================
-- QSS API v1 — Exhibitor REST API migration
-- 参考 Swoogo API 设计风格
-- =====================================================

-- 1) xexhibitors 表增加 API 密钥字段
ALTER TABLE `xexhibitors`
  ADD COLUMN `api_key` VARCHAR(128) NULL DEFAULT NULL COMMENT 'REST API Key (consumer_key)' AFTER `authenticate_key`,
  ADD COLUMN `api_secret` VARCHAR(128) NULL DEFAULT NULL COMMENT 'REST API Secret (consumer_secret)' AFTER `api_key`,
  ADD UNIQUE INDEX `idx_api_key` (`api_key`);

-- 2) Bearer Token 缓存表（用于令牌管理，可选用 Redis 替代）
CREATE TABLE IF NOT EXISTS `xapi_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `exhibitor_id` INT(11) NOT NULL COMMENT '关联 xexhibitors.id',
  `access_token` VARCHAR(512) NOT NULL COMMENT 'JWT Bearer Token',
  `expires_at` DATETIME NOT NULL COMMENT '过期时间',
  `client_ip` VARCHAR(45) NULL DEFAULT NULL COMMENT '请求 IP',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_access_token` (`access_token`(255)),
  INDEX `idx_exhibitor_id` (`exhibitor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API Bearer Token 管理表';

-- 3) API 访问日志表（用于审计和限流统计）
CREATE TABLE IF NOT EXISTS `xapi_logs` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `exhibitor_id` INT(11) NULL DEFAULT NULL COMMENT '关联 xexhibitors.id',
  `endpoint` VARCHAR(255) NULL DEFAULT NULL COMMENT '请求路径',
  `method` VARCHAR(10) NULL DEFAULT NULL COMMENT 'HTTP 方法',
  `params` TEXT NULL COMMENT '请求参数 (JSON)',
  `ip` VARCHAR(45) NULL DEFAULT NULL COMMENT '客户端 IP',
  `user_agent` VARCHAR(500) NULL DEFAULT NULL,
  `response_code` INT(11) NULL DEFAULT NULL COMMENT 'HTTP 状态码',
  `response_time` INT(11) NULL DEFAULT NULL COMMENT '响应耗时 (ms)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_exhibitor_id` (`exhibitor_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API 访问日志';
