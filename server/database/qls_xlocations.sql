/*
Navicat MySQL Data Transfer

Source Server         : localhost3307
Source Server Version : 50505
Source Host           : 127.0.0.1:3307
Source Database       : qlsscan1

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2021-02-16 23:02:25
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for qls_xlocations
-- ----------------------------
DROP TABLE IF EXISTS `qls_xlocations`;
CREATE TABLE `qls_xlocations` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `location_group` varchar(150) NOT NULL COMMENT 'visitor category id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xlocations
-- ----------------------------
INSERT INTO `qls_xlocations` VALUES ('F8ADEE93-D5CB-CD96-1A14-26F96023AAB8', 'Level 5 Foyer Entrance 1', '', '6A7B661B-DF44-77F0-C230-39491B566C62', '2020-08-19 19:28:34', '2020-08-19 19:28:34', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
INSERT INTO `qls_xlocations` VALUES ('269F335A-2B94-BB05-D042-A199B97EEC18', 'Level 5 Foyer Entrance 2', '', '6A7B661B-DF44-77F0-C230-39491B566C62', '2020-08-19 20:07:12', '2020-08-19 20:07:12', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
INSERT INTO `qls_xlocations` VALUES ('342CFBDC-3CDE-3A55-D180-7C34605304EB', 'Level 5 Foyer Entrance 3', '', '0283C63D-ECD9-1CD3-B40E-AADC2A56722F', '2020-08-19 20:26:56', '2020-08-19 20:26:56', '0', '1', 'EAF9C507-5BE7-78B7-118B-65C4E2369EA3');
INSERT INTO `qls_xlocations` VALUES ('36BE6BEA-F166-56FD-1AE2-366E8EC8B536', 'IOT Entrance', '', '50A5B9FA-7B53-5AB2-A7BE-AA11FBADEE05', '2020-08-19 20:27:22', '2020-08-19 20:27:22', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
