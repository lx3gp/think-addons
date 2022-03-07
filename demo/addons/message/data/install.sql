/*
Navicat MySQL Data Transfer

Source Server         : api.ai-baidu.cn
Source Server Version : 50562
Source Host           : 47.95.215.177:3306
Source Database       : www_ai_baidu_cn

Target Server Type    : MYSQL
Target Server Version : 50562
File Encoding         : 65001

Date: 2022-03-07 11:22:16
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tp_message
-- ----------------------------
DROP TABLE IF EXISTS `tp_message`;
CREATE TABLE `tp_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `aid` int(11) DEFAULT NULL COMMENT '文章ID',
  `content` varchar(255) DEFAULT NULL COMMENT '评论内容',
  `phone` char(11) DEFAULT NULL COMMENT '用户电话',
  `username` varchar(255) DEFAULT NULL COMMENT '评论用户名',
  `to_uid` int(11) DEFAULT NULL COMMENT '被评论的用户',
  `create_time` datetime DEFAULT NULL COMMENT '评论创建时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '该条评论状态:0 未审核 , 1 已审核，2 已暂停',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='插件message数据表';
