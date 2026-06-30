/*
Navicat MySQL Data Transfer

Source Server         : localhost3307
Source Server Version : 50505
Source Host           : 127.0.0.1:3307
Source Database       : qlsscan1

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2021-02-16 23:02:14
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for qls_xlocation_groups
-- ----------------------------
DROP TABLE IF EXISTS `qls_xlocation_groups`;
CREATE TABLE `qls_xlocation_groups` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `capacity` int(11) NOT NULL DEFAULT '50',
  `access_groups` mediumtext NOT NULL COMMENT 'visitor category id',
  `all_access` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有所有权限',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xlocation_groups
-- ----------------------------
INSERT INTO `qls_xlocation_groups` VALUES ('6A7B661B-DF44-77F0-C230-39491B566C62', 'Exhibitor Entry', '', '1000', '', '1', '2020-09-06 00:52:52', '2020-08-19 10:35:52', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
INSERT INTO `qls_xlocation_groups` VALUES ('50A5B9FA-7B53-5AB2-A7BE-AA11FBADEE05', 'IOT Entry', '', '150', '2503848F-4B1C-9F53-C285-13E50280DC65|316450FD-E7B2-252D-3C57-5EBAA214AD7B|B4991D19-72C5-D592-B613-7E8BD524156A', '0', '2020-08-19 17:24:33', '2020-08-19 10:40:34', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
INSERT INTO `qls_xlocation_groups` VALUES ('83026483-B073-9AB7-8982-A1EA9AA9BC31', 'Fintech Entry', '', '50', '2503848F-4B1C-9F53-C285-13E50280DC65|316450FD-E7B2-252D-3C57-5EBAA214AD7B|B4991D19-72C5-D592-B613-7E8BD524156A', '0', '2020-08-19 17:24:34', '2020-08-19 10:41:05', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
INSERT INTO `qls_xlocation_groups` VALUES ('C8151018-9D63-5C15-84FF-D5E37870B0EF', 'Digital Marketing Entrance', '', '75', '2503848F-4B1C-9F53-C285-13E50280DC65|316450FD-E7B2-252D-3C57-5EBAA214AD7B|B4991D19-72C5-D592-B613-7E8BD524156A', '0', '2020-08-19 17:24:35', '2020-08-19 10:41:35', '0', '1', '6B1520B1-51C4-4D88-19D9-5FC2F7FB3F7B');
INSERT INTO `qls_xlocation_groups` VALUES ('0283C63D-ECD9-1CD3-B40E-AADC2A56722F', 'Food Reception Entry', '', '200', 'B4991D19-72C5-D592-B613-7E8BD524156A', '0', '2020-08-19 17:32:42', '2020-08-19 17:32:42', '0', '1', 'EAF9C507-5BE7-78B7-118B-65C4E2369EA3');
