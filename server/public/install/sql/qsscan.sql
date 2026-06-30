/*
Navicat MySQL Data Transfer

Source Server         : localhost3307
Source Server Version : 50505
Source Host           : 127.0.0.1:3307
Source Database       : qlsscantest

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2020-09-15 00:42:56
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for qls_xaccess_groups
-- ----------------------------
DROP TABLE IF EXISTS `qls_xaccess_groups`;
CREATE TABLE `qls_xaccess_groups` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `visitor_categories` mediumtext NOT NULL COMMENT 'visitor category id',
  `all_access` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有所有权限',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL COMMENT 'event id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xaccess_groups
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xadmins
-- ----------------------------
DROP TABLE IF EXISTS `qls_xadmins`;
CREATE TABLE `qls_xadmins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '管理员昵称',
  `picture` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员头像',
  `password` varchar(200) NOT NULL DEFAULT '' COMMENT '管理员登录密码',
  `role_id` int(11) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态标识 0：无效；1：正常；-1：删除',
  `content` varchar(500) NOT NULL DEFAULT '世界上没有两片完全相同的叶子！' COMMENT '备注信息',
  `email` varchar(255) DEFAULT NULL,
  `private_key` varchar(32) NOT NULL,
  `authenticate_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- ----------------------------
-- Records of qls_xadmins
-- ----------------------------
INSERT INTO `qls_xadmins` VALUES ('1', 'admin', '/cms/images/headshot/default.jpg', '317c775b607a08e911a338826c459cf4', '1', '2020-09-10 11:33:13', '1', 'HELLO', 'lingbohust@163.com', 'EMxccrVg5PIv6GCzHyLIqrECXTnxREH7', 'jIUMqvBZ2Ad7cLF8+jqiUBxqPrsj/smpKTCKSHQChAE=');
INSERT INTO `qls_xadmins` VALUES ('9', 'jerry.bi', '/cms/images/headshot/baZhaHei.png', '7a21a93b52d807ea9ab6ac311aa44b21', '5', '2020-09-08 15:21:57', '1', '运营', null, '$PHujHA6WkIWbHTp2AmBDMgKKTOfES0m', 'UEx/oq6KgtluTyxo6OT51NXx2SmkVNu8+MxB9+0pQFU=');
INSERT INTO `qls_xadmins` VALUES ('10', 'test', '/cms/images/headshot/baZhaHei.png', '', '5', '2020-09-08 15:34:05', '1', 'tester', 'lingbohust@163.com', '0l9y9#o47|Ty0]cJzD1{GZ8A$IYPA4^B', '/Hg8OFwqnYwPZYkYc6Hh0QSUCmKvnqQIUXqRpf48QCU=');

-- ----------------------------
-- Table structure for qls_xadmin_roles
-- ----------------------------
DROP TABLE IF EXISTS `qls_xadmin_roles`;
CREATE TABLE `qls_xadmin_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色称呼',
  `nav_menu_ids` text NOT NULL COMMENT '权限下的菜单ID',
  `special_grant` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态标识',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色表';

-- ----------------------------
-- Records of qls_xadmin_roles
-- ----------------------------
INSERT INTO `qls_xadmin_roles` VALUES ('1', 'Master Admin', '138|139|140|141|1|2|7|6|142|143|178|148|181|179|144|168|180|145|146|147|190|191|200|201|202|203|204|3|4|5|93|73|49|48|50|67|61|76|133|134|', '1', '2020-09-10 12:57:15', '1');
INSERT INTO `qls_xadmin_roles` VALUES ('2', 'Team Admin', '1|2|6|3|4|5|', '1', '2020-09-10 12:57:23', '1');
INSERT INTO `qls_xadmin_roles` VALUES ('5', 'Team User', '142|143|178|148|181|179|144|168|180|145|146|147|', '0', '2020-09-10 12:58:20', '1');

-- ----------------------------
-- Table structure for qls_xblacklists
-- ----------------------------
DROP TABLE IF EXISTS `qls_xblacklists`;
CREATE TABLE `qls_xblacklists` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `visitor_id` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `visitor_category` varchar(150) NOT NULL COMMENT 'visitor category id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xblacklists
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xcms_logs
-- ----------------------------
DROP TABLE IF EXISTS `qls_xcms_logs`;
CREATE TABLE `qls_xcms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(20) NOT NULL COMMENT '标签，为了区分所记录不同业务的日志',
  `op_id` varchar(150) NOT NULL DEFAULT '0' COMMENT '操作对象 ID',
  `op_msg` varchar(50) NOT NULL COMMENT '操作备案',
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `add_time` datetime NOT NULL COMMENT '记录添加时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of qls_xcms_logs
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xdevices
-- ----------------------------
DROP TABLE IF EXISTS `qls_xdevices`;
CREATE TABLE `qls_xdevices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `agent` varchar(255) DEFAULT NULL,
  `screen_size` varchar(50) DEFAULT NULL,
  `viewport_size` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of qls_xdevices
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xevents
-- ----------------------------
DROP TABLE IF EXISTS `qls_xevents`;
CREATE TABLE `qls_xevents` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `venue` varchar(512) NOT NULL,
  `country` varchar(256) NOT NULL,
  `timezone` varchar(200) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `is_encrypt` tinyint(1) DEFAULT NULL,
  `event_key` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xevents
-- ----------------------------

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
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xlocations
-- ----------------------------

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
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xlocation_groups
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xnav_menus
-- ----------------------------
DROP TABLE IF EXISTS `qls_xnav_menus`;
CREATE TABLE `qls_xnav_menus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'navMenu 主键',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级菜单ID',
  `action` varchar(100) NOT NULL DEFAULT '' COMMENT 'action地址（etc:admin/home）',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '自定义图标样式',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态，1：正常，-1：删除',
  `list_order` tinyint(4) NOT NULL DEFAULT '0' COMMENT '排序标识，越小越靠前',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '导航类型 0：菜单类  1：权限链接',
  PRIMARY KEY (`id`),
  KEY `id` (`id`,`name`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COMMENT='菜单导航表';

-- ----------------------------
-- Records of qls_xnav_menus
-- ----------------------------
INSERT INTO `qls_xnav_menus` VALUES ('2', 'Menu Manage', '1', 'cms/menu/index', 'fa fa-navicon', '1', '0', '2020-08-27 23:57:13', '0');
INSERT INTO `qls_xnav_menus` VALUES ('1', 'System Manage', '0', '/', 'fa fa-vcard', '1', '1', '2020-08-27 23:57:15', '0');
INSERT INTO `qls_xnav_menus` VALUES ('6', 'Member Manage', '1', 'cms/admin/index', 'fa fa-user', '1', '3', '2020-08-27 23:57:21', '0');
INSERT INTO `qls_xnav_menus` VALUES ('7', 'Role Manage', '1', 'cms/admin/role', 'fa fa-tag', '1', '2', '2020-08-27 23:57:24', '0');
INSERT INTO `qls_xnav_menus` VALUES ('29', 'Add Menu', '2', 'cms/menu/add', '/', '1', '0', '2020-09-10 12:53:45', '1');
INSERT INTO `qls_xnav_menus` VALUES ('30', 'Edit Menu', '2', 'cms/menu/edit', '/', '1', '0', '2020-09-10 12:53:54', '1');
INSERT INTO `qls_xnav_menus` VALUES ('31', 'Set Menu Auth', '2', 'cms/menu/auth', '/', '1', '0', '2020-09-10 12:54:13', '1');
INSERT INTO `qls_xnav_menus` VALUES ('38', 'Add Admin', '6', 'cms/admin/addAdmin', '/', '1', '0', '2020-09-10 12:54:36', '1');
INSERT INTO `qls_xnav_menus` VALUES ('39', 'Edit Admin', '6', 'cms/admin/editAdmin', '/', '1', '0', '2020-09-10 12:54:43', '1');
INSERT INTO `qls_xnav_menus` VALUES ('41', 'Add Role', '7', 'cms/admin/addRole', '/', '1', '0', '2020-09-10 12:54:48', '1');
INSERT INTO `qls_xnav_menus` VALUES ('42', 'Edit Role', '7', 'cms/admin/editRole', '/', '1', '0', '2020-09-10 12:54:55', '1');
INSERT INTO `qls_xnav_menus` VALUES ('142', 'Event', '0', '/', 'fa fa-tags', '1', '2', '2020-08-28 00:20:45', '0');
INSERT INTO `qls_xnav_menus` VALUES ('143', 'Events List', '142', 'cms/events/index', 'fa fa-list', '1', '0', '2020-08-17 15:40:19', '0');
INSERT INTO `qls_xnav_menus` VALUES ('144', 'Locations', '179', 'cms/locations/index', 'fa fa-map-marker', '1', '1', '2020-08-20 00:35:27', '0');
INSERT INTO `qls_xnav_menus` VALUES ('145', 'Visitor Categories', '180', 'cms/visitorCategories/index', 'fa fa-user-o', '1', '1', '2020-08-20 00:36:18', '0');
INSERT INTO `qls_xnav_menus` VALUES ('146', 'Access Groups', '180', 'cms/accessGroups/index', 'fa fa-lock', '1', '2', '2020-08-20 00:36:42', '0');
INSERT INTO `qls_xnav_menus` VALUES ('147', 'Blacklist', '180', 'cms/blacklists/index', 'fa fa-ban', '1', '3', '2020-08-20 00:37:00', '0');
INSERT INTO `qls_xnav_menus` VALUES ('148', 'Track', '178', 'cms/tracks/index', 'fa fa-road', '1', '1', '2020-08-20 00:41:54', '0');
INSERT INTO `qls_xnav_menus` VALUES ('181', 'Session', '178', 'cms/sessions/index', 'fa fa-link', '1', '2', '2020-08-20 00:45:03', '0');
INSERT INTO `qls_xnav_menus` VALUES ('149', 'events', '143', '/', '/', '-1', '0', '2020-08-18 00:34:59', '1');
INSERT INTO `qls_xnav_menus` VALUES ('150', 'Add Event', '143', 'cms/events/add', '/', '1', '0', '2020-08-18 00:35:21', '1');
INSERT INTO `qls_xnav_menus` VALUES ('151', 'Edit Event', '143', 'cms/events/edit', '/', '1', '0', '2020-08-18 00:35:41', '1');
INSERT INTO `qls_xnav_menus` VALUES ('152', 'view event log', '143', 'cms/events/viewLogs', '/', '1', '0', '2020-08-18 00:36:10', '1');
INSERT INTO `qls_xnav_menus` VALUES ('153', 'Add location', '144', 'cms/locations/add', '/', '1', '0', '2020-08-18 00:36:42', '1');
INSERT INTO `qls_xnav_menus` VALUES ('154', 'Edit location', '144', 'cms/locations/edit', '/', '1', '0', '2020-08-18 00:37:03', '1');
INSERT INTO `qls_xnav_menus` VALUES ('155', 'view location log', '144', 'cms/locations/viewLogs', '/', '1', '0', '2020-08-18 00:38:28', '1');
INSERT INTO `qls_xnav_menus` VALUES ('156', 'add visitor category', '145', 'cms/visitorCategories/add', '/', '1', '0', '2020-08-18 00:39:18', '1');
INSERT INTO `qls_xnav_menus` VALUES ('157', 'edit visitor categor', '145', 'cms/visitorCategories/edit', '/', '1', '0', '2020-08-18 00:39:41', '1');
INSERT INTO `qls_xnav_menus` VALUES ('158', 'view visitor categor', '145', 'cms/visitorCategories/viewLogs', '/', '1', '0', '2020-08-18 00:40:13', '1');
INSERT INTO `qls_xnav_menus` VALUES ('159', 'add access groups', '146', 'cms/accessGroups/add', '/', '1', '0', '2020-08-18 00:40:38', '1');
INSERT INTO `qls_xnav_menus` VALUES ('160', 'edit access groups', '146', 'cms/accessGroups/edit', '/', '1', '0', '2020-08-18 00:41:01', '1');
INSERT INTO `qls_xnav_menus` VALUES ('161', 'view access groups l', '146', 'cms/accessGroups/viewLogs', '/', '1', '0', '2020-08-18 00:41:25', '1');
INSERT INTO `qls_xnav_menus` VALUES ('162', 'add blacklist', '147', 'cms/blacklist/add', '/', '-1', '0', '2020-08-19 23:21:19', '1');
INSERT INTO `qls_xnav_menus` VALUES ('163', 'edit blacklist', '147', 'cms/blacklist/edit', '/', '-1', '0', '2020-08-19 23:21:24', '1');
INSERT INTO `qls_xnav_menus` VALUES ('164', 'view blacklist log', '147', 'cms/blacklist/viewLogs', '/', '-1', '0', '2020-08-19 23:21:28', '1');
INSERT INTO `qls_xnav_menus` VALUES ('165', 'add schedule', '148', 'cms/schedule/add', '/', '-1', '0', '2020-08-20 00:45:59', '1');
INSERT INTO `qls_xnav_menus` VALUES ('166', 'edit schedule', '148', 'cms/schedule/edit', '/', '-1', '0', '2020-08-20 00:46:52', '1');
INSERT INTO `qls_xnav_menus` VALUES ('167', 'view schedule log', '148', 'cms/schedule/viewLogs', '/', '-1', '0', '2020-08-20 00:46:56', '1');
INSERT INTO `qls_xnav_menus` VALUES ('168', 'Location Groups', '179', 'cms/locationGroups/index', 'fa fa-sitemap', '1', '2', '2020-08-20 00:35:54', '0');
INSERT INTO `qls_xnav_menus` VALUES ('169', 'add location group', '168', 'cms/locationGroups/add', '/', '1', '0', '2020-08-19 00:39:02', '1');
INSERT INTO `qls_xnav_menus` VALUES ('170', 'edit location groups', '168', 'cms/locationGroups/edit', '/', '1', '0', '2020-08-19 00:39:23', '1');
INSERT INTO `qls_xnav_menus` VALUES ('171', 'view location groups', '168', 'cms/locationGroups/viewLogs', '/', '1', '0', '2020-08-19 00:39:46', '1');
INSERT INTO `qls_xnav_menus` VALUES ('172', 'get list', '145', 'cms/visitorCategories/getList', '/', '1', '0', '2020-08-19 16:42:00', '1');
INSERT INTO `qls_xnav_menus` VALUES ('173', 'get list', '146', 'cms/accessGroups/getList', '/', '1', '0', '2020-08-19 17:20:31', '1');
INSERT INTO `qls_xnav_menus` VALUES ('174', 'get list', '168', 'cms/locationGroups/getList', '/', '1', '0', '2020-08-19 18:04:20', '1');
INSERT INTO `qls_xnav_menus` VALUES ('175', 'add blacklist', '147', 'cms/blacklists/add', '/', '1', '0', '2020-08-19 23:20:20', '1');
INSERT INTO `qls_xnav_menus` VALUES ('176', 'edit blacklist', '147', 'cms/blacklists/edit', '/', '1', '0', '2020-08-19 23:20:51', '1');
INSERT INTO `qls_xnav_menus` VALUES ('177', 'view blacklist log', '147', 'cms/blacklists/viewLogs', '/', '1', '0', '2020-08-19 23:21:11', '1');
INSERT INTO `qls_xnav_menus` VALUES ('178', 'Schedule', '0', '/', 'fa fa-calendar', '1', '3', '2020-08-28 00:20:55', '0');
INSERT INTO `qls_xnav_menus` VALUES ('179', 'Location Control', '0', '/', 'fa fa-map', '1', '4', '2020-08-28 00:21:06', '0');
INSERT INTO `qls_xnav_menus` VALUES ('180', 'Access Control', '0', '/', 'fa fa-lock', '1', '5', '2020-08-28 00:21:17', '0');
INSERT INTO `qls_xnav_menus` VALUES ('182', 'add track', '148', 'cms/tracks/add', '/', '1', '0', '2020-08-20 00:45:45', '1');
INSERT INTO `qls_xnav_menus` VALUES ('183', 'edit track', '148', 'cms/tracks/edit', '/', '1', '0', '2020-08-20 00:46:12', '1');
INSERT INTO `qls_xnav_menus` VALUES ('184', 'view track log', '148', 'cms/tracks/viewLogs', '/', '1', '0', '2020-08-20 00:46:39', '1');
INSERT INTO `qls_xnav_menus` VALUES ('185', 'add session', '181', 'cms/sessions/add', '/', '1', '0', '2020-08-20 00:47:20', '1');
INSERT INTO `qls_xnav_menus` VALUES ('186', 'edit session', '181', 'cms/sessions/edit', '/', '1', '0', '2020-08-20 00:47:40', '1');
INSERT INTO `qls_xnav_menus` VALUES ('187', 'view log', '181', 'cms/sessions/viewLogs', '/', '1', '0', '2020-08-20 00:48:05', '1');
INSERT INTO `qls_xnav_menus` VALUES ('188', 'get list ', '148', 'cms/tracks/getList', '/', '1', '0', '2020-08-20 11:55:07', '1');
INSERT INTO `qls_xnav_menus` VALUES ('189', 'get list', '144', 'cms/locations/getList', '/', '1', '0', '2020-08-20 11:55:34', '1');
INSERT INTO `qls_xnav_menus` VALUES ('190', 'Screen Setting', '0', '/', 'fa fa-cog', '1', '6', '2020-08-27 23:59:28', '0');
INSERT INTO `qls_xnav_menus` VALUES ('191', 'Config', '190', 'cms/screenSettings/config', 'fa fa-edit', '1', '0', '2020-08-28 11:57:28', '0');
INSERT INTO `qls_xnav_menus` VALUES ('192', 'Notification Screen', '190', 'cms/screenSettings/notification', 'fa fa-bullhorn', '-1', '1', '2020-08-28 10:17:16', '0');
INSERT INTO `qls_xnav_menus` VALUES ('193', 'Check-Out Screen', '190', 'cms/screenSettings/checkout', 'fa fa-toggle-left', '-1', '2', '2020-08-28 11:58:08', '0');
INSERT INTO `qls_xnav_menus` VALUES ('194', 'Denied Screen', '190', 'cms/screenSettings/deny', 'fa fa-ban', '-1', '3', '2020-08-28 11:58:22', '0');
INSERT INTO `qls_xnav_menus` VALUES ('195', 'Overcapacity Screen', '190', 'cms/screenSettings/overcapacity', 'fa fa-square', '-1', '4', '2020-08-28 11:58:29', '0');
INSERT INTO `qls_xnav_menus` VALUES ('196', 'Error Screen', '190', 'cms/screenSettings/error', 'fa fa-warning', '-1', '5', '2020-08-28 11:58:38', '0');
INSERT INTO `qls_xnav_menus` VALUES ('197', 'Capacity Status Screen', '190', 'cms/screenSettings/capacitystatus', 'fa fa-flash', '-1', '6', '2020-08-28 11:58:43', '0');
INSERT INTO `qls_xnav_menus` VALUES ('198', 'Check-In Screen', '190', 'cms/screenSettings/checkin', 'fa fa-toggle-right', '-1', '1', '2020-08-28 11:57:51', '0');
INSERT INTO `qls_xnav_menus` VALUES ('199', 'get screen data', '191', 'cms/screenSettings/getScreenData', '/', '1', '0', '2020-08-29 00:23:40', '1');
INSERT INTO `qls_xnav_menus` VALUES ('200', 'Reporting Center', '0', '/', 'fa fa-area-chart', '1', '8', '2020-09-04 12:38:40', '0');
INSERT INTO `qls_xnav_menus` VALUES ('201', 'Capacity Report', '200', 'cms/report/capacity', 'fa fa-trophy', '1', '0', '2020-09-04 12:50:01', '0');
INSERT INTO `qls_xnav_menus` VALUES ('202', 'Attendance Summary', '200', 'cms/report/attendSummary', 'fa fa-ticket', '1', '1', '2020-09-04 12:44:34', '0');
INSERT INTO `qls_xnav_menus` VALUES ('203', 'Checked-Ins', '200', 'cms/report/checkedIns', 'fa fa-toggle-left', '1', '2', '2020-09-04 12:45:47', '0');
INSERT INTO `qls_xnav_menus` VALUES ('204', 'Scanned Data', '200', 'cms/report/scanData', 'fa fa-suitcase', '1', '3', '2020-09-04 12:47:22', '0');
INSERT INTO `qls_xnav_menus` VALUES ('205', 'manual checkout', '203', 'cms/report/manualCheckout', '/', '1', '0', '2020-09-05 00:48:31', '1');
INSERT INTO `qls_xnav_menus` VALUES ('206', 'edit', '201', 'cms/report/editCapacity', '/', '1', '0', '2020-09-06 00:43:58', '1');
INSERT INTO `qls_xnav_menus` VALUES ('207', 'download', '201', 'cms/report/downloadList', '/', '1', '0', '2020-09-06 09:46:39', '1');
INSERT INTO `qls_xnav_menus` VALUES ('208', 'download', '202', 'cms/report/downloadList', '/', '1', '0', '2020-09-06 09:47:03', '1');
INSERT INTO `qls_xnav_menus` VALUES ('209', 'download', '203', 'cms/report/downloadList', '/', '1', '0', '2020-09-06 09:47:28', '1');
INSERT INTO `qls_xnav_menus` VALUES ('210', 'download', '204', 'cms/report/downloadList', '/', '1', '0', '2020-09-06 09:47:55', '1');

-- ----------------------------
-- Table structure for qls_xscreens
-- ----------------------------
DROP TABLE IF EXISTS `qls_xscreens`;
CREATE TABLE `qls_xscreens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of qls_xscreens
-- ----------------------------
INSERT INTO `qls_xscreens` VALUES ('1', 'main', 'Main Scanning Screen');
INSERT INTO `qls_xscreens` VALUES ('2', 'checkin', 'Access Check-In Screen');
INSERT INTO `qls_xscreens` VALUES ('3', 'checkout', 'Access Check-Out Screen');
INSERT INTO `qls_xscreens` VALUES ('4', 'deny', 'Access Denied Screen');
INSERT INTO `qls_xscreens` VALUES ('5', 'overcapacity', 'Overcapacity Screen');
INSERT INTO `qls_xscreens` VALUES ('6', 'error', 'Error Screen');
INSERT INTO `qls_xscreens` VALUES ('7', 'capacitystatus', 'Capacity Status Screen');

-- ----------------------------
-- Table structure for qls_xscreen_settings
-- ----------------------------
DROP TABLE IF EXISTS `qls_xscreen_settings`;
CREATE TABLE `qls_xscreen_settings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `screen_id` int(11) NOT NULL,
  `event_id` varchar(160) NOT NULL,
  `bg_url` varchar(255) DEFAULT NULL,
  `msg_bg_url` varchar(255) DEFAULT NULL,
  `show_manual_input` tinyint(1) DEFAULT NULL,
  `manual_input_text` varchar(255) DEFAULT NULL,
  `font_url` varchar(255) DEFAULT NULL,
  `font_size` int(11) DEFAULT NULL,
  `font_style` varchar(20) DEFAULT NULL,
  `font_color` varchar(20) DEFAULT NULL,
  `show_capacity` tinyint(1) DEFAULT NULL,
  `message_text_align` varchar(10) DEFAULT NULL,
  `message_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of qls_xscreen_settings
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xservers
-- ----------------------------
DROP TABLE IF EXISTS `qls_xservers`;
CREATE TABLE `qls_xservers` (
  `id` varchar(150) NOT NULL,
  `server_id` int(11) NOT NULL DEFAULT '101',
  `server_name` varchar(50) NOT NULL DEFAULT '',
  `server_description` varchar(255) DEFAULT NULL,
  `server_location` varchar(255) DEFAULT NULL,
  `server_timezone` varchar(255) NOT NULL,
  `server_url` varchar(512) NOT NULL,
  `server_key` varchar(32) NOT NULL,
  `server_version` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of qls_xservers
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xsessions
-- ----------------------------
DROP TABLE IF EXISTS `qls_xsessions`;
CREATE TABLE `qls_xsessions` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  `track_id` varchar(150) NOT NULL,
  `location_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xsessions
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xtracks
-- ----------------------------
DROP TABLE IF EXISTS `qls_xtracks`;
CREATE TABLE `qls_xtracks` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xtracks
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xvisitor_categories
-- ----------------------------
DROP TABLE IF EXISTS `qls_xvisitor_categories`;
CREATE TABLE `qls_xvisitor_categories` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `description` varchar(256) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序标识 越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:0:normal 0:removed',
  `event_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xvisitor_categories
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xvisitor_checkinouts
-- ----------------------------
DROP TABLE IF EXISTS `qls_xvisitor_checkinouts`;
CREATE TABLE `qls_xvisitor_checkinouts` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `visitor_id` varchar(150) NOT NULL DEFAULT '' COMMENT '标题',
  `visitor_category_id` varchar(150) NOT NULL DEFAULT '0',
  `given_name` varchar(30) DEFAULT NULL,
  `surname` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `organisation` varchar(50) DEFAULT NULL,
  `location_group_id` varchar(150) DEFAULT NULL,
  `first_checkin_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `current_checkin_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `first_checkout_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `current_checkout_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_checkedin` tinyint(1) NOT NULL DEFAULT '0',
  `is_checkedout` tinyint(1) NOT NULL DEFAULT '1',
  `access_group_id` varchar(150) DEFAULT NULL,
  `session_id` varchar(150) DEFAULT NULL,
  `event_id` varchar(150) NOT NULL COMMENT 'event id',
  `location_id` varchar(150) NOT NULL,
  `track_id` varchar(150) NOT NULL,
  `access_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'status:1:access granted 2:access exception granted 3:denied 4:manual checkout',
  `access_message` varchar(255) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xvisitor_checkinouts
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xvisitor_rules
-- ----------------------------
DROP TABLE IF EXISTS `qls_xvisitor_rules`;
CREATE TABLE `qls_xvisitor_rules` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_id` varchar(150) NOT NULL,
  `min_length` int(11) NOT NULL,
  `max_length` int(11) NOT NULL,
  `prefix` varchar(255) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `regular` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of qls_xvisitor_rules
-- ----------------------------

-- ----------------------------
-- Table structure for qls_xvisitor_scandatas
-- ----------------------------
DROP TABLE IF EXISTS `qls_xvisitor_scandatas`;
CREATE TABLE `qls_xvisitor_scandatas` (
  `id` varchar(150) NOT NULL COMMENT '主键',
  `visitor_id` varchar(150) NOT NULL DEFAULT '' COMMENT '标题',
  `visitor_category_id` varchar(150) NOT NULL DEFAULT '0',
  `given_name` varchar(30) DEFAULT NULL,
  `surname` varchar(30) DEFAULT NULL,
  `organisation` varchar(50) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `work_phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `source_from` varchar(255) DEFAULT NULL,
  `location_group_id` varchar(150) DEFAULT NULL,
  `scan_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_checkedin` tinyint(1) NOT NULL DEFAULT '0',
  `is_checkedout` tinyint(1) NOT NULL DEFAULT '1',
  `access_group_id` varchar(150) DEFAULT NULL,
  `session_id` varchar(150) DEFAULT NULL,
  `event_id` varchar(150) NOT NULL COMMENT 'event id',
  `location_id` varchar(150) NOT NULL,
  `track_id` varchar(150) NOT NULL,
  `access_status` int(11) NOT NULL DEFAULT '1' COMMENT 'status:1:access granted 2:access exception granted 3:denied 4:manual checkout',
  `access_message` varchar(255) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- ----------------------------
-- Records of qls_xvisitor_scandatas
-- ----------------------------
