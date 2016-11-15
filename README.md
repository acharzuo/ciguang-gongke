# ciguang-gongke
基于wecenter 1.93版深度开发的,功课系统


# 版本更新

v1.2. 修改首页中功课的显示方式,增加人的信息以及点赞的信息
v1.1. 增加金鸡报晓功课
v1.0. 系统完成基本功课功能,上线



## 1.2版更新SQL

alter table 表名称 modify 字段名称 字段类型 [是否允许非空];



ALTER table wc_user_lessons add add_time INT(10) NOT NULL DEFAULT 0 COMMENT '添加时间';
ALTER table wc_user_lessons add votes INT(10) NOT NULL DEFAULT 0 COMMENT '点赞数';
ALTER table wc_user_lessons add dizangchan BIGINT NOT NULL DEFAULT 0 COMMENT '地藏忏';
ALTER table wc_user_lessons add nianfoshichang BIGINT NOT NULL DEFAULT 0 COMMENT '地藏忏';
update wc_user_lessons set add_time = unix_timestamp(create_time);



CREATE TABLE `wc_lessons_vote` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `type` varchar(16) DEFAULT NULL,
  `item_id` int(10) NOT NULL,
  `rating` tinyint(1) DEFAULT '0',
  `time` int(10) NOT NULL,
  `reputation_factor` int(10) DEFAULT '0',
  `item_uid` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `type` (`type`),
  KEY `item_id` (`item_id`),
  KEY `time` (`time`),
  KEY `item_uid` (`item_uid`)
)  DEFAULT CHARSET=utf8;



## 在首页插入内容


## 设置默认首页的方法
在：system\core\uri.php有个默认控制器，修改这个参数

···php
var $default_vars = array(
'app_dir' => 'home', //应用名
'controller' => 'main', //文件名
'action' => 'index'//执行的程序
); 
···

