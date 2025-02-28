DROP TABLE IF EXISTS `__PREFIX__addons_baidupush`;
CREATE TABLE `__PREFIX__addons_baidupush` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) DEFAULT NULL COMMENT 'URL',
  `dateymd` int(10) DEFAULT '0' COMMENT '年月日',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态1成功0失败',
  `num` tinyint(4) DEFAULT '0' COMMENT '推送次数',
  `result` varchar(100) DEFAULT NULL COMMENT '结果',
  `remain` int(10) DEFAULT '0' COMMENT '剩余条数',
  `create_time` int(10) DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `dateymd` (`dateymd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='百度推送记录';