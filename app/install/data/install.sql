
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for fn_admin
-- ----------------------------
DROP TABLE IF EXISTS `fn_admin`;
CREATE TABLE `fn_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL DEFAULT '',
  `pwd` varchar(100) NOT NULL DEFAULT '',
  `salt` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(255) DEFAULT '',
  `thumb` varchar(255) DEFAULT NULL,
  `theme` varchar(255) NOT NULL DEFAULT 'black' COMMENT '系统主题',
  `mobile` bigint(11) DEFAULT '0',
  `email` varchar(255) DEFAULT '',
  `desc` text COMMENT '备注',
  `did` int(11) NOT NULL DEFAULT '0' COMMENT '部门id',
  `position_id` int(11) NOT NULL DEFAULT '0' COMMENT '职位id',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `last_login_time` int(11) NOT NULL DEFAULT '0',
  `login_num` int(11) NOT NULL DEFAULT '0',
  `last_login_ip` varchar(64) NOT NULL DEFAULT '',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '1正常,0禁止登录,-1删除',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='管理员表';


-- ----------------------------
-- Table structure for fn_admin_group
-- ----------------------------
DROP TABLE IF EXISTS `fn_admin_group`;
CREATE TABLE `fn_admin_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `status` int(1) NOT NULL DEFAULT '1',
  `rules` varchar(1000) DEFAULT '' COMMENT '用户组拥有的规则id， 多个规则","隔开',
  `desc` text COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='权限分组表';

-- ----------------------------
-- Records of fn_admin_group
-- ----------------------------
INSERT INTO `fn_admin_group` VALUES ('1', '超级管理员', '1', '1,2,3,4,5,6,7,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,120,121,122,123,124,125,126,127,128,129,130,132,133,134,135,136,138,139,140,141,142,144,145,146,147,148,150,151,152,153,154,156,157,158,159,160,161,162,163,164,166,167,168,169,170,172,173,174,175,176,177,179,180,181,182,183,185,186,187,188,189,190,191,192,193,194,195,196,198,199,200,201,202,204,205,206,207,208,210,211,212,213,214,216,217,218,219,220,222,223,224,225,226,228,229,230,231,232,234,235,236,237,238,240,241,242,243,244,246,247,248,249,250,252,253,254,255,256,257,258,259,260,261,262,263', '超级管理员，系统自动分配所有可操作权限及菜单。', '0', '1732510855');
INSERT INTO `fn_admin_group` VALUES ('2', '测试角色', '1', '1,9,13,17,20,23,26,29,31,2,33,34,35,38,42,43,44,45,46,3,53,56,59,62,65,68,71,74,77,4,79,82,84,87,5,88,92,95,6,97,101,104,7,106,110,113,8,115,119', '测试角色', '0', '0');

-- ----------------------------
-- Table structure for fn_admin_group_access
-- ----------------------------
DROP TABLE IF EXISTS `fn_admin_group_access`;
CREATE TABLE `fn_admin_group_access` (
  `uid` int(11) unsigned DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限分组和管理员的关联表';

-- ----------------------------
-- Records of fn_admin_group_access
-- ----------------------------
INSERT INTO `fn_admin_group_access` VALUES ('1', '1', '0', '0');

-- ----------------------------
-- Table structure for fn_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_admin_log`;
CREATE TABLE `fn_admin_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `type` varchar(80) NOT NULL DEFAULT '' COMMENT '操作类型',
  `action` varchar(80) NOT NULL DEFAULT '' COMMENT '操作动作',
  `subject` varchar(80) NOT NULL DEFAULT '' COMMENT '操作主体',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '操作标题',
  `content` text COMMENT '操作描述',
  `module` varchar(32) NOT NULL DEFAULT '' COMMENT '模块',
  `controller` varchar(32) NOT NULL DEFAULT '' COMMENT '控制器',
  `function` varchar(32) NOT NULL DEFAULT '' COMMENT '方法',
  `rule_menu` varchar(255) NOT NULL DEFAULT '' COMMENT '节点权限路径',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '登录ip',
  `param_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作数据id',
  `param` text COMMENT '参数json格式',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0删除 1正常',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台操作日志表';

-- ----------------------------
-- Records of fn_admin_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_admin_module
-- ----------------------------
DROP TABLE IF EXISTS `fn_admin_module`;
CREATE TABLE `fn_admin_module` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '模块名称',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '模块所在目录，小写字母',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态,0禁用,1正常',
  `type` int(1) NOT NULL DEFAULT '2' COMMENT '模块类型,2普通模块,1系统模块',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='功能模块表';

-- ----------------------------
-- Records of fn_admin_module
-- ----------------------------
INSERT INTO `fn_admin_module` VALUES ('1', '后台模块', 'admin', '', '1', '1', '1639562910', '0');
INSERT INTO `fn_admin_module` VALUES ('2', '前台模块', 'home', '', '1', '1', '1639562910', '0');
INSERT INTO `fn_admin_module` VALUES ('3', '作者模块', 'author', '', '1', '1', '1639562910', '0');
INSERT INTO `fn_admin_module` VALUES ('4', 'API模块', 'api', '', '1', '1', '1639562910', '0');

-- ----------------------------
-- Table structure for fn_admin_rule
-- ----------------------------
DROP TABLE IF EXISTS `fn_admin_rule`;
CREATE TABLE `fn_admin_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `src` varchar(255) NOT NULL DEFAULT '' COMMENT 'url链接',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '日志操作名称',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  `menu` int(1) NOT NULL DEFAULT '0' COMMENT '是否是菜单,1是,2不是',
  `sort` int(11) NOT NULL DEFAULT '1' COMMENT '越小越靠前',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态,0禁用,1正常',
  `module` varchar(255) NOT NULL DEFAULT '' COMMENT '所属模块',
  `crud` varchar(255) NOT NULL DEFAULT '' COMMENT 'crud标识',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COMMENT='菜单及权限表';

-- ----------------------------
-- Records of fn_admin_rule
-- ----------------------------
INSERT INTO `fn_admin_rule` VALUES ('1', '0', '', '系统管理', '系统管理', 'ri-settings-3-line', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('2', '0', '', '系统工具', '系统工具', 'ri-list-settings-line', '1', '2', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('3', '0', '', '基础数据', '基础数据', 'ri-database-2-line', '1', '3', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('4', '0', '', '用户管理', '用户管理', 'ri-group-line', '1', '4', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('5', '0', '', '资讯中心', '资讯中心', 'ri-article-line', '1', '5', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('6', '0', '', '图集中心', '图集中心', 'ri-image-2-line', '1', '6', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('7', '0', '', '商品中心', '商品中心', 'ri-shopping-bag-3-line', '1', '7', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('9', '1', 'conf/index', '系统配置', '系统配置', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('10', '9', 'conf/add', '新建/编辑', '配置项', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('11', '9', 'conf/delete', '删除', '配置项', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('12', '9', 'conf/edit', '编辑', '配置详情', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('13', '1', 'module/index', '功能模块', '功能模块', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('14', '13', 'module/install', '安装', '功能模块', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('15', '13', 'module/upgrade', '升级', '功能模块', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('16', '13', 'module/uninstall', '卸载', '功能模块', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('17', '1', 'rule/index', '功能节点', '功能节点', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('18', '17', 'rule/add', '新建/编辑', '功能节点', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('19', '17', 'rule/delete', '删除', '功能节点', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('20', '1', 'role/index', '权限角色', '权限角色', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('21', '20', 'role/add', '新建/编辑', '权限角色', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('22', '20', 'role/delete', '删除', '权限角色', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('23', '1', 'department/index', '部门架构', '部门', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('24', '23', 'department/add', '新建/编辑', '部门', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('25', '23', 'department/delete', '删除', '部门', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('26', '1', 'position/index', '岗位职称', '岗位职称', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('27', '26', 'position/add', '新建/编辑', '岗位职称', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('28', '26', 'position/delete', '删除', '岗位职称', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('29', '1', 'admin/index', '系统用户', '系统用户', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('30', '29', 'admin/add', '添加/修改', '系统用户', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('31', '29', 'admin/view', '查看', '系统用户', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('32', '29', 'admin/delete', '删除', '系统用户', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('33', '2', 'log/index', '操作日志', '操作日志', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('34', '2', 'crud/index', '一键CRUD', 'CRUD代码生成', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('35', '34', 'crud/table', 'CRUD查看', 'CRUD查看', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('36', '34', 'crud/crud', 'CRUD操作', 'CRUD代码生成', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('37', '34', 'crud/menu', '生成菜单', '菜单生成', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('38', '2', 'database/database', '备份数据', '数据备份', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('39', '38', 'database/backup', '备份数据表', '数据', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('40', '38', 'database/optimize', '优化数据表', '数据表', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('41', '38', 'database/repair', '修复数据表', '数据表', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('42', '2', 'database/backuplist', '还原数据', '数据还原', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('43', '42', 'database/import', '还原数据表', '数据', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('44', '42', 'database/downfile', '下载备份数据', '备份数据', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('45', '42', 'database/del', '删除备份数据', '备份数据', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('46', '2', 'files/index', '附件管理', '附件管理', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('47', '46', 'files/edit', '编辑附件', '附件', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('48', '46', 'files/move', '移动附件', '附件', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('49', '46', 'files/delete', '删除附件', '附件', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('50', '46', 'files/get_group', '附件分组', '附件分组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('51', '46', 'files/add_group', '新建/编辑', '附件分组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('52', '46', 'files/del_group', '删除附件分组', '附件分组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('53', '3', 'nav/index', '导航设置', '导航组', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('54', '53', 'nav/add', '新建/编辑', '导航组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('55', '53', 'nav/delete', '删除', '导航组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('56', '3', 'nav/nav_info', '导航管理', '导航', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('57', '56', 'nav/nav_info_add', '新建/编辑', '导航', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('58', '56', 'nav/nav_info_delete', '删除导航', '导航', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('65', '3', 'slide/index', '轮播广告', '轮播组', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('66', '65', 'slide/add', '新建/编辑', '轮播组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('67', '65', 'slide/delete', '删除', '轮播组', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('68', '3', 'slide/slide_info', '轮播广告管理', '轮播图', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('69', '68', 'slide/slide_info_add', '新建/编辑', '轮播图', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('70', '68', 'slide/slide_info_delete', '删除', '轮播图', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('71', '3', 'links/index', '友情链接', '友情链接', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('72', '71', 'links/add', '新建/编辑', '友情链接', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('73', '71', 'links/delete', '删除', '友情链接', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('74', '3', 'keywords/index', 'SEO关键字', 'SEO关键字', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('75', '74', 'keywords/add', '新建/编辑', 'SEO关键字', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('76', '74', 'keywords/delete', '删除', 'SEO关键字', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('77', '3', 'search/index', '搜索关键字', '搜索关键字', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('78', '77', 'search/delete', '删除', '搜索关键字', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('79', '4', 'level/index', '用户等级', '用户等级', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('80', '79', 'level/add', '新建/编辑', '用户等级', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('81', '79', 'level/disable', '禁用/启用', '用户等级', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('82', '4', 'user/index', '用户管理', '用户', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('83', '82', 'user/edit', '编辑', '用户信息', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('84', '82', 'user/view', '查看', '用户信息', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('85', '82', 'user/disable', '禁用/启用', '用户', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('86', '4', 'user/record', '操作记录', '用户操作记录', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('87', '4', 'user/log', '操作日志', '用户操作日志', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('88', '5', 'article_cate/datalist', '文章分类', '文章分类', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('89', '88', 'article_cate/add', '新建', '文章分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('90', '88', 'article_cate/edit', '编辑', '文章分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('91', '88', 'article_cate/del', '删除', '文章分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('92', '5', 'article/datalist', '文章列表', '文章', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('93', '92', 'article/add', '新建', '文章', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('94', '92', 'article/edit', '编辑', '文章', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('95', '92', 'article/read', '查看', '文章', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('96', '92', 'article/del', '删除', '文章', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('97', '6', 'gallery_cate/datalist', '图集分类', '图集分类', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('98', '97', 'gallery_cate/add', '新建', '图集分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('99', '97', 'gallery_cate/edit', '编辑', '图集分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('100', '97', 'gallery_cate/del', '删除', '图集分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('101', '6', 'gallery/datalist', '图集列表', '图集', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('102', '101', 'gallery/add', '新建', '图集', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('103', '101', 'gallery/edit', '编辑', '图集', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('104', '101', 'gallery/read', '查看', '图集', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('105', '101', 'gallery/del', '删除', '图集', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('106', '7', 'goods_cate/datalist', '商品分类', '商品分类', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('107', '106', 'goods_cate/add', '新建', '商品分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('108', '106', 'goods_cate/edit', '编辑', '商品分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('109', '106', 'goods_cate/del', '删除', '商品分类', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('110', '7', 'goods/datalist', '商品列表', '商品', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('111', '110', 'goods/add', '新建', '商品', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('112', '110', 'goods/edit', '编辑', '商品', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('113', '110', 'goods/read', '查看', '商品', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('114', '110', 'goods/del', '删除', '商品', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('115', '3', 'pages/datalist', '单页管理', '单页管理', '', '1', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('116', '115', 'pages/add', '新建', '单页面', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('117', '115', 'pages/edit', '编辑', '单页面', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('118', '115', 'pages/del', '删除', '单页面', '', '2', '1', '1', '', '', '0', '0');
INSERT INTO `fn_admin_rule` VALUES ('120', '2', 'plugin/datalist', '插件管理', '插件管理', '', '1', '0', '1', '', '', '1723200432', '0');
INSERT INTO `fn_admin_rule` VALUES ('121', '120', 'plugin/config', '插件配置', '插件配置', '', '2', '0', '1', '', '', '1723203009', '0');
INSERT INTO `fn_admin_rule` VALUES ('122', '120', 'plugin/install', '插件安装', '插件安装', '', '2', '0', '1', '', '', '1723203032', '0');
INSERT INTO `fn_admin_rule` VALUES ('123', '120', 'plugin/uninstall', '插件卸载', '插件卸载', '', '2', '0', '1', '', '', '1723203056', '0');
INSERT INTO `fn_admin_rule` VALUES ('124', '120', 'plugin/state', '插件状态设置', '插件状态设置', '', '2', '0', '1', '', '', '1723209622', '0');
INSERT INTO `fn_admin_rule` VALUES ('125', '0', '', '作品管理', '作品', 'ri-book-line', '1', '4', '1', '', 'book', '1723363400', '0');
INSERT INTO `fn_admin_rule` VALUES ('126', '125', 'book/datalist', '作品列表', '作品列表', '', '1', '0', '1', '', 'book', '1723363400', '0');
INSERT INTO `fn_admin_rule` VALUES ('127', '126', 'book/add', '新建', '作品', '', '2', '0', '1', '', 'book', '1723363400', '0');
INSERT INTO `fn_admin_rule` VALUES ('128', '126', 'book/edit', '编辑', '作品', '', '2', '0', '1', '', 'book', '1723363400', '0');
INSERT INTO `fn_admin_rule` VALUES ('129', '126', 'book/read', '查看', '作品', '', '2', '0', '1', '', 'book', '1723363400', '0');
INSERT INTO `fn_admin_rule` VALUES ('130', '126', 'book/del', '删除', '作品', '', '2', '0', '1', '', 'book', '1723363400', '0');
INSERT INTO `fn_admin_rule` VALUES ('132', '125', 'category/datalist', '作品分类', '作品分类', '', '1', '0', '1', '', 'category', '1723363883', '0');
INSERT INTO `fn_admin_rule` VALUES ('133', '132', 'category/add', '新建', '作品分类', '', '2', '0', '1', '', 'category', '1723363883', '0');
INSERT INTO `fn_admin_rule` VALUES ('134', '132', 'category/edit', '编辑', '作品分类', '', '2', '0', '1', '', 'category', '1723363883', '0');
INSERT INTO `fn_admin_rule` VALUES ('135', '132', 'category/read', '查看', '作品分类', '', '2', '0', '1', '', 'category', '1723363883', '0');
INSERT INTO `fn_admin_rule` VALUES ('136', '132', 'category/del', '删除', '作品分类', '', '2', '0', '1', '', 'category', '1723363883', '0');
INSERT INTO `fn_admin_rule` VALUES ('138', '4', 'author/datalist', '作者管理', '作者管理', '', '1', '0', '1', '', 'author', '1723364315', '0');
INSERT INTO `fn_admin_rule` VALUES ('139', '138', 'author/add', '新建', '作者', '', '2', '0', '1', '', 'author', '1723364315', '0');
INSERT INTO `fn_admin_rule` VALUES ('140', '138', 'author/edit', '编辑', '作者', '', '2', '0', '1', '', 'author', '1723364315', '0');
INSERT INTO `fn_admin_rule` VALUES ('141', '138', 'author/read', '查看', '作者', '', '2', '0', '1', '', 'author', '1723364315', '0');
INSERT INTO `fn_admin_rule` VALUES ('142', '138', 'author/del', '删除', '作者', '', '2', '0', '1', '', 'author', '1723364315', '0');
INSERT INTO `fn_admin_rule` VALUES ('144', '126', 'volume/datalist', '章节卷', '章节卷', '', '2', '0', '1', '', 'volume', '1723367348', '0');
INSERT INTO `fn_admin_rule` VALUES ('145', '144', 'volume/add', '新建', '章节卷', '', '2', '0', '1', '', 'volume', '1723367348', '0');
INSERT INTO `fn_admin_rule` VALUES ('146', '144', 'volume/edit', '编辑', '章节卷', '', '2', '0', '1', '', 'volume', '1723367348', '0');
INSERT INTO `fn_admin_rule` VALUES ('147', '144', 'volume/read', '查看', '章节卷', '', '2', '0', '1', '', 'volume', '1723367348', '0');
INSERT INTO `fn_admin_rule` VALUES ('148', '144', 'volume/del', '删除', '章节卷', '', '2', '0', '1', '', 'volume', '1723367348', '0');
INSERT INTO `fn_admin_rule` VALUES ('150', '126', 'chapter/datalist', '章节列表', '章节列表', '', '2', '0', '1', '', 'chapter', '1723367674', '0');
INSERT INTO `fn_admin_rule` VALUES ('151', '150', 'chapter/add', '新建', '章节', '', '2', '0', '1', '', 'chapter', '1723367674', '0');
INSERT INTO `fn_admin_rule` VALUES ('152', '150', 'chapter/edit', '编辑', '章节', '', '2', '0', '1', '', 'chapter', '1723367674', '0');
INSERT INTO `fn_admin_rule` VALUES ('153', '150', 'chapter/read', '查看', '章节', '', '2', '0', '1', '', 'chapter', '1723367674', '0');
INSERT INTO `fn_admin_rule` VALUES ('154', '150', 'chapter/del', '删除', '章节', '', '2', '0', '1', '', 'chapter', '1723367674', '0');
INSERT INTO `fn_admin_rule` VALUES ('156', '125', 'chapter_verify/datalist', '修改章节审核', '修改章节审核', '', '1', '0', '1', '', 'chapter_verify', '1723384282', '0');
INSERT INTO `fn_admin_rule` VALUES ('157', '156', 'chapter_verify/add', '新建', '审核章节', '', '2', '0', '1', '', 'chapter_verify', '1723384282', '0');
INSERT INTO `fn_admin_rule` VALUES ('158', '156', 'chapter_verify/edit', '编辑', '审核章节', '', '2', '0', '1', '', 'chapter_verify', '1723384282', '0');
INSERT INTO `fn_admin_rule` VALUES ('159', '156', 'chapter_verify/read', '查看', '审核章节', '', '2', '0', '1', '', 'chapter_verify', '1723384282', '0');
INSERT INTO `fn_admin_rule` VALUES ('160', '156', 'chapter_verify/del', '删除', '审核章节', '', '2', '0', '1', '', 'chapter_verify', '1723384282', '0');
INSERT INTO `fn_admin_rule` VALUES ('161', '132', 'category/getsmallcate', '获取作品小类', '获取作品小类', '', '2', '0', '1', '', '', '1723558344', '0');
INSERT INTO `fn_admin_rule` VALUES ('162', '138', 'author/authorlist', '选择作者', '选择作者', '', '2', '0', '1', '', '', '1723558857', '0');
INSERT INTO `fn_admin_rule` VALUES ('163', '126', 'book/savefield', '作品字段更新', '作品字段更新', '', '2', '0', '1', '', '', '1723562302', '0');
INSERT INTO `fn_admin_rule` VALUES ('164', '125', 'chapter/verify', '新章节审核', '新章节审核', '', '1', '0', '1', '', '', '1723621074', '0');
INSERT INTO `fn_admin_rule` VALUES ('166', '3', 'adver/datalist', '广告管理', '广告管理', '', '1', '0', '1', '', 'adver', '1723987225', '0');
INSERT INTO `fn_admin_rule` VALUES ('167', '166', 'adver/add', '新建', '广告位', '', '2', '0', '1', '', 'adver', '1723987225', '0');
INSERT INTO `fn_admin_rule` VALUES ('168', '166', 'adver/edit', '编辑', '广告位', '', '2', '0', '1', '', 'adver', '1723987225', '0');
INSERT INTO `fn_admin_rule` VALUES ('169', '166', 'adver/read', '查看', '广告位', '', '2', '0', '1', '', 'adver', '1723987225', '0');
INSERT INTO `fn_admin_rule` VALUES ('170', '166', 'adver/del', '删除', '广告位', '', '2', '0', '1', '', 'adver', '1723987225', '0');
INSERT INTO `fn_admin_rule` VALUES ('172', '166', 'advsr/datalist', '广告列表', '广告列表', '', '2', '0', '1', '', 'advsr', '1723987423', '0');
INSERT INTO `fn_admin_rule` VALUES ('173', '172', 'advsr/add', '新建', '广告列表', '', '2', '0', '1', '', 'advsr', '1723987423', '0');
INSERT INTO `fn_admin_rule` VALUES ('174', '172', 'advsr/edit', '编辑', '广告列表', '', '2', '0', '1', '', 'advsr', '1723987423', '0');
INSERT INTO `fn_admin_rule` VALUES ('175', '172', 'advsr/read', '查看', '广告列表', '', '2', '0', '1', '', 'advsr', '1723987423', '0');
INSERT INTO `fn_admin_rule` VALUES ('176', '172', 'advsr/del', '删除', '广告列表', '', '2', '0', '1', '', 'advsr', '1723987423', '0');
INSERT INTO `fn_admin_rule` VALUES ('177', '126', 'book/booklist', '选择作品列表', '选择作品列表', '', '2', '0', '1', '', '', '1723989314', '0');
INSERT INTO `fn_admin_rule` VALUES ('179', '4', 'favorites/datalist', '收藏管理', '收藏管理', '', '1', '0', '1', '', 'favorites', '1725175169', '0');
INSERT INTO `fn_admin_rule` VALUES ('180', '179', 'favorites/add', '新建', '收藏', '', '2', '0', '1', '', 'favorites', '1725175169', '0');
INSERT INTO `fn_admin_rule` VALUES ('181', '179', 'favorites/edit', '编辑', '收藏', '', '2', '0', '1', '', 'favorites', '1725175169', '0');
INSERT INTO `fn_admin_rule` VALUES ('182', '179', 'favorites/read', '查看', '收藏', '', '2', '0', '1', '', 'favorites', '1725175169', '0');
INSERT INTO `fn_admin_rule` VALUES ('183', '179', 'favorites/del', '删除', '收藏', '', '2', '0', '1', '', 'favorites', '1725175169', '0');
INSERT INTO `fn_admin_rule` VALUES ('185', '4', 'readhistory/datalist', '阅读记录', '阅读记录', '', '1', '0', '1', '', 'readhistory', '1725265185', '0');
INSERT INTO `fn_admin_rule` VALUES ('186', '185', 'readhistory/add', '新建', '阅读记录', '', '2', '0', '1', '', 'readhistory', '1725265185', '0');
INSERT INTO `fn_admin_rule` VALUES ('187', '185', 'readhistory/edit', '编辑', '阅读记录', '', '2', '0', '1', '', 'readhistory', '1725265185', '0');
INSERT INTO `fn_admin_rule` VALUES ('188', '185', 'readhistory/read', '查看', '阅读记录', '', '2', '0', '1', '', 'readhistory', '1725265185', '0');
INSERT INTO `fn_admin_rule` VALUES ('189', '185', 'readhistory/del', '删除', '阅读记录', '', '2', '0', '1', '', 'readhistory', '1725265185', '0');
INSERT INTO `fn_admin_rule` VALUES ('190', '126', 'book/import', '导入作品', '导入作品', '', '2', '0', '1', '', '', '1725503050', '0');
INSERT INTO `fn_admin_rule` VALUES ('191', '0', '', '交易管理', '订单', 'ri-money-cny-circle-line', '1', '4', '1', '', 'order', '1728561934', '0');
INSERT INTO `fn_admin_rule` VALUES ('192', '191', 'order/datalist', '订单管理', '订单列表', '', '1', '0', '1', '', 'order', '1728561934', '0');
INSERT INTO `fn_admin_rule` VALUES ('193', '192', 'order/add', '新建', '订单', '', '2', '0', '1', '', 'order', '1728561934', '0');
INSERT INTO `fn_admin_rule` VALUES ('194', '192', 'order/edit', '编辑', '订单', '', '2', '0', '1', '', 'order', '1728561934', '0');
INSERT INTO `fn_admin_rule` VALUES ('195', '192', 'order/read', '查看', '订单', '', '2', '0', '1', '', 'order', '1728561934', '0');
INSERT INTO `fn_admin_rule` VALUES ('196', '192', 'order/del', '删除', '订单', '', '2', '0', '1', '', 'order', '1728561934', '0');
INSERT INTO `fn_admin_rule` VALUES ('198', '191', 'coin_log/datalist', '金币流水', '金币流水', '', '1', '0', '1', '', 'coin_log', '1728904606', '0');
INSERT INTO `fn_admin_rule` VALUES ('199', '198', 'coin_log/add', '新建', '金币流水', '', '2', '0', '1', '', 'coin_log', '1728904606', '0');
INSERT INTO `fn_admin_rule` VALUES ('200', '198', 'coin_log/edit', '编辑', '金币流水', '', '2', '0', '1', '', 'coin_log', '1728904606', '0');
INSERT INTO `fn_admin_rule` VALUES ('201', '198', 'coin_log/read', '查看', '金币流水', '', '2', '0', '1', '', 'coin_log', '1728904606', '0');
INSERT INTO `fn_admin_rule` VALUES ('202', '198', 'coin_log/del', '删除', '金币流水', '', '2', '0', '1', '', 'coin_log', '1728904606', '0');
INSERT INTO `fn_admin_rule` VALUES ('204', '4', 'bank_card/datalist', '银行卡', '银行卡', '', '1', '0', '1', '', 'bank_card', '1728915076', '0');
INSERT INTO `fn_admin_rule` VALUES ('205', '204', 'bank_card/add', '新建', '银行卡', '', '2', '0', '1', '', 'bank_card', '1728915076', '0');
INSERT INTO `fn_admin_rule` VALUES ('206', '204', 'bank_card/edit', '编辑', '银行卡', '', '2', '0', '1', '', 'bank_card', '1728915076', '0');
INSERT INTO `fn_admin_rule` VALUES ('207', '204', 'bank_card/read', '查看', '银行卡', '', '2', '0', '1', '', 'bank_card', '1728915076', '0');
INSERT INTO `fn_admin_rule` VALUES ('208', '204', 'bank_card/del', '删除', '银行卡', '', '2', '0', '1', '', 'bank_card', '1728915076', '0');
INSERT INTO `fn_admin_rule` VALUES ('210', '4', 'report/datalist', '举报管理', '举报管理', '', '1', '0', '1', '', 'report', '1729583935', '0');
INSERT INTO `fn_admin_rule` VALUES ('211', '210', 'report/add', '新建', '举报', '', '2', '0', '1', '', 'report', '1729583935', '0');
INSERT INTO `fn_admin_rule` VALUES ('212', '210', 'report/edit', '编辑', '举报', '', '2', '0', '1', '', 'report', '1729583935', '0');
INSERT INTO `fn_admin_rule` VALUES ('213', '210', 'report/read', '查看', '举报', '', '2', '0', '1', '', 'report', '1729583935', '0');
INSERT INTO `fn_admin_rule` VALUES ('214', '210', 'report/del', '删除', '举报', '', '2', '0', '1', '', 'report', '1729583935', '0');
INSERT INTO `fn_admin_rule` VALUES ('216', '4', 'withdraw/datalist', '提现管理', '提现管理', '', '1', '0', '1', '', 'withdraw', '1729585228', '0');
INSERT INTO `fn_admin_rule` VALUES ('217', '216', 'withdraw/add', '新建', '提现', '', '2', '0', '1', '', 'withdraw', '1729585228', '0');
INSERT INTO `fn_admin_rule` VALUES ('218', '216', 'withdraw/edit', '编辑', '提现', '', '2', '0', '1', '', 'withdraw', '1729585228', '0');
INSERT INTO `fn_admin_rule` VALUES ('219', '216', 'withdraw/read', '查看', '提现', '', '2', '0', '1', '', 'withdraw', '1729585228', '0');
INSERT INTO `fn_admin_rule` VALUES ('220', '216', 'withdraw/del', '删除', '提现', '', '2', '0', '1', '', 'withdraw', '1729585228', '0');
INSERT INTO `fn_admin_rule` VALUES ('222', '4', 'vip_log/datalist', 'VIP记录', 'VIP记录', '', '1', '0', '1', '', 'vip_log', '1729586244', '0');
INSERT INTO `fn_admin_rule` VALUES ('223', '222', 'vip_log/add', '新建', 'VIP', '', '2', '0', '1', '', 'vip_log', '1729586244', '0');
INSERT INTO `fn_admin_rule` VALUES ('224', '222', 'vip_log/edit', '编辑', 'VIP', '', '2', '0', '1', '', 'vip_log', '1729586244', '0');
INSERT INTO `fn_admin_rule` VALUES ('225', '222', 'vip_log/read', '查看', 'VIP', '', '2', '0', '1', '', 'vip_log', '1729586244', '0');
INSERT INTO `fn_admin_rule` VALUES ('226', '222', 'vip_log/del', '删除', 'VIP', '', '2', '0', '1', '', 'vip_log', '1729586244', '0');
INSERT INTO `fn_admin_rule` VALUES ('228', '4', 'sms_log/datalist', '短信记录', '短信记录', '', '1', '0', '1', '', 'sms_log', '1729586453', '0');
INSERT INTO `fn_admin_rule` VALUES ('229', '228', 'sms_log/add', '新建', '短信', '', '2', '0', '1', '', 'sms_log', '1729586453', '0');
INSERT INTO `fn_admin_rule` VALUES ('230', '228', 'sms_log/edit', '编辑', '短信', '', '2', '0', '1', '', 'sms_log', '1729586453', '0');
INSERT INTO `fn_admin_rule` VALUES ('231', '228', 'sms_log/read', '查看', '短信', '', '2', '0', '1', '', 'sms_log', '1729586453', '0');
INSERT INTO `fn_admin_rule` VALUES ('232', '228', 'sms_log/del', '删除', '短信', '', '2', '0', '1', '', 'sms_log', '1729586453', '0');
INSERT INTO `fn_admin_rule` VALUES ('234', '4', 'task/datalist', '任务管理', '任务管理', '', '1', '0', '1', '', 'task', '1729586624', '0');
INSERT INTO `fn_admin_rule` VALUES ('235', '234', 'task/add', '新建', '任务', '', '2', '0', '1', '', 'task', '1729586624', '0');
INSERT INTO `fn_admin_rule` VALUES ('236', '234', 'task/edit', '编辑', '任务', '', '2', '0', '1', '', 'task', '1729586624', '0');
INSERT INTO `fn_admin_rule` VALUES ('237', '234', 'task/read', '查看', '任务', '', '2', '0', '1', '', 'task', '1729586624', '0');
INSERT INTO `fn_admin_rule` VALUES ('238', '234', 'task/del', '删除', '任务', '', '2', '0', '1', '', 'task', '1729586624', '0');
INSERT INTO `fn_admin_rule` VALUES ('240', '4', 'sign_log/datalist', '签到管理', '签到管理', '', '1', '0', '1', '', 'sign_log', '1729586774', '0');
INSERT INTO `fn_admin_rule` VALUES ('241', '240', 'sign_log/add', '新建', '签到', '', '2', '0', '1', '', 'sign_log', '1729586774', '0');
INSERT INTO `fn_admin_rule` VALUES ('242', '240', 'sign_log/edit', '编辑', '签到', '', '2', '0', '1', '', 'sign_log', '1729586774', '0');
INSERT INTO `fn_admin_rule` VALUES ('243', '240', 'sign_log/read', '查看', '签到', '', '2', '0', '1', '', 'sign_log', '1729586774', '0');
INSERT INTO `fn_admin_rule` VALUES ('244', '240', 'sign_log/del', '删除', '签到', '', '2', '0', '1', '', 'sign_log', '1729586774', '0');
INSERT INTO `fn_admin_rule` VALUES ('246', '4', 'like_log/datalist', '点赞记录', '点赞记录', '', '1', '0', '1', '', 'like_log', '1729589589', '0');
INSERT INTO `fn_admin_rule` VALUES ('247', '246', 'like_log/add', '新建', '点赞', '', '2', '0', '1', '', 'like_log', '1729589589', '0');
INSERT INTO `fn_admin_rule` VALUES ('248', '246', 'like_log/edit', '编辑', '点赞', '', '2', '0', '1', '', 'like_log', '1729589589', '0');
INSERT INTO `fn_admin_rule` VALUES ('249', '246', 'like_log/read', '查看', '点赞', '', '2', '0', '1', '', 'like_log', '1729589589', '0');
INSERT INTO `fn_admin_rule` VALUES ('250', '246', 'like_log/del', '删除', '点赞', '', '2', '0', '1', '', 'like_log', '1729589589', '0');
INSERT INTO `fn_admin_rule` VALUES ('252', '4', 'search_log/datalist', '搜索记录', '搜索记录', '', '1', '0', '1', '', 'search_log', '1729589781', '0');
INSERT INTO `fn_admin_rule` VALUES ('253', '252', 'search_log/add', '新建', '搜索', '', '2', '0', '1', '', 'search_log', '1729589781', '0');
INSERT INTO `fn_admin_rule` VALUES ('254', '252', 'search_log/edit', '编辑', '搜索', '', '2', '0', '1', '', 'search_log', '1729589781', '0');
INSERT INTO `fn_admin_rule` VALUES ('255', '252', 'search_log/read', '查看', '搜索', '', '2', '0', '1', '', 'search_log', '1729589781', '0');
INSERT INTO `fn_admin_rule` VALUES ('256', '252', 'search_log/del', '删除', '搜索', '', '2', '0', '1', '', 'search_log', '1729589781', '0');
INSERT INTO `fn_admin_rule` VALUES ('257', '9', 'conf/test', '配置测试', '配置测试', '', '2', '0', '1', '', '', '1732250575', '0');
INSERT INTO `fn_admin_rule` VALUES ('259', '4', 'follow/datalist', '关注管理', '关注管理', '', '1', '0', '1', '', 'follow', '1732510855', '0');
INSERT INTO `fn_admin_rule` VALUES ('260', '259', 'follow/add', '新建', '关注', '', '2', '0', '1', '', 'follow', '1732510855', '0');
INSERT INTO `fn_admin_rule` VALUES ('261', '259', 'follow/edit', '编辑', '关注', '', '2', '0', '1', '', 'follow', '1732510855', '0');
INSERT INTO `fn_admin_rule` VALUES ('262', '259', 'follow/read', '查看', '关注', '', '2', '0', '1', '', 'follow', '1732510855', '0');
INSERT INTO `fn_admin_rule` VALUES ('263', '259', 'follow/del', '删除', '关注', '', '2', '0', '1', '', 'follow', '1732510855', '0');
-- ----------------------------
-- Table structure for fn_adver
-- ----------------------------
DROP TABLE IF EXISTS `fn_adver`;
CREATE TABLE `fn_adver` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` char(80) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '广告位置名称',
  `channel` smallint(4) DEFAULT '0' COMMENT '展示频道1app2官网3作家4手机版',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '广告位置类型1单图2多图3文字4代码',
  `swiper` tinyint(4) DEFAULT '0' COMMENT '轮播',
  `width` char(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '广告位置宽度',
  `height` char(20) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '广告位置高度',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态（0：禁用，1：正常）',
  `viewnum` smallint(5) NOT NULL DEFAULT '0' COMMENT '显示条数',
  `remarks` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '备注',
  `create_time` int(10) DEFAULT NULL,
  `update_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='广告位::crud';

-- ----------------------------
-- Records of fn_adver
-- ----------------------------
INSERT INTO `fn_adver` VALUES ('114', '首页轮播', '666', '2', '0', '100%', '3rem', '1', '5', '', '1723988433', '1724135869');
INSERT INTO `fn_adver` VALUES ('115', '公告', '888', '2', '0', '', '', '1', '5', '', '1723988819', null);
INSERT INTO `fn_adver` VALUES ('116', '编辑推荐', '666', '2', '0', '30%', '3rem', '1', '6', '', '1724081239', null);
INSERT INTO `fn_adver` VALUES ('117', '首页横幅', '666', '2', '0', '100%', '', '1', '1', '', '1724137055', null);
INSERT INTO `fn_adver` VALUES ('118', '新书上架', '666', '2', '0', '', '', '1', '7', '', '1724137419', null);

-- ----------------------------
-- Table structure for fn_advsr
-- ----------------------------
DROP TABLE IF EXISTS `fn_advsr`;
CREATE TABLE `fn_advsr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(80) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '广告标题',
  `color` varchar(10) CHARACTER SET utf8 DEFAULT NULL COMMENT '标题颜色',
  `adver_id` int(11) NOT NULL COMMENT '广告位置',
  `images` varchar(255) DEFAULT NULL COMMENT '图片地址',
  `advshtml` text COMMENT '内容',
  `type` tinyint(4) DEFAULT '0' COMMENT '类型1作品3链接6内容',
  `hits` int(11) DEFAULT '0' COMMENT '阅读量',
  `introduction` varchar(255) DEFAULT NULL COMMENT '描述',
  `link` varchar(255) DEFAULT NULL COMMENT '链接地址',
  `books` varchar(200) CHARACTER SET utf8 DEFAULT NULL COMMENT '作品ID集',
  `level` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '优先级',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态（0：禁用，1：正常）',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `adver_id` (`adver_id`,`level`) USING BTREE,
  KEY `status` (`status`,`adver_id`,`level`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='广告列表::crud';

-- ----------------------------
-- Records of fn_advsr
-- ----------------------------

-- ----------------------------
-- Table structure for fn_article
-- ----------------------------
DROP TABLE IF EXISTS `fn_article`;
CREATE TABLE `fn_article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cate_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属分类',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `desc` varchar(1000) DEFAULT '' COMMENT '摘要',
  `thumb` int(11) NOT NULL DEFAULT '0' COMMENT '缩略图:附件id',
  `original` int(1) NOT NULL DEFAULT '0' COMMENT '是否原创:1是,0否',
  `origin` varchar(255) NOT NULL DEFAULT '' COMMENT '来源或作者',
  `origin_url` varchar(255) NOT NULL DEFAULT '' COMMENT '来源地址',
  `content` text NOT NULL COMMENT '内容',
  `md_content` text NOT NULL COMMENT 'markdown内容',
  `read` int(11) NOT NULL DEFAULT '0' COMMENT '阅读量',
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '属性:1精华,2热门,3推荐',
  `is_home` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否首页显示:0否,1是',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态:1正常,0下架',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='文章::crud';

-- ----------------------------
-- Records of fn_article
-- ----------------------------
INSERT INTO `fn_article` VALUES ('1', '1', '怎么成为作者？', '', '0', '0', '', '', '<p>飞鸟阅读作者申请流程？<br />直接点击网页作者专区进入申请，或从网页下方点击作者投稿，进入作者专区进行作者申请。</p>\r\n<p>作者申请审核需要多长时间？<br />1、新书入库：审核时长为24小时。</p>\r\n<p>2、作者审核：审核时长为24个小时。</p>\r\n<p>3、签约审核：自作品上传一万字起，3－7个工作日内（不含周末）完成审核。但不排除作品跟进等情况，若进入跟进期，编辑会稍延长一段时间，以观察作品走向再决定是否签约；如进入签约审核的作品出现断更情况，将影响审核时间与结果；</p>\r\n<p>填写笔名时提示：笔名已存在，怎么办？<br />笔名不能重复，如已存在，请您稍加修改或另起笔名填写。</p>\r\n<p>（注：笔名填写后不可修改，请慎重填写）</p>', '', '2', '0', '1', '0', '1', '1', '1729600007', '0', '0');
INSERT INTO `fn_article` VALUES ('2', '1', '如何创建作品', '', '0', '0', '', '', '<p>如何创建作品<br />登录作者专区，进入作者专区点击“创建新书”即可创建作品。</p>\r\n<p>什么是专属作品？<br />专属作品为作品著作人主动在网站首发更新的作品，并同意飞鸟阅读作为此稿件版权的独家发布人。在撤销委托之前，保证不再将稿件投给其他任何媒体，有关此稿件发表和转载等任何事宜，由飞鸟阅读中文网全权代理。</p>\r\n<p>为什么我发布的作品没有通过审核？<br />请检查您的作者申请信息或者作品申请信息，是否有不符合飞鸟阅读中文网作品信息收录规定的字词或者言论。</p>\r\n<p>新书发布有什么限制？<br />新书发布应符合飞鸟阅读投稿须知。</p>\r\n<p>新书发布限制：</p>\r\n<p>1、作品名字应与内容相符，不具有文学性、故意夸大其词的广告性、政治性、恶搞性或淫亵性作品名将会被删除。</p>\r\n<p>2、上传的作品内容必须与符合飞鸟阅读收录标准，不符合收录标准的作品将被禁阅或删除。</p>\r\n<p>3、新作品将在48小时内审核完毕（节假日顺延），请建立完后立即上传章节，凡章节低于三章或少于3000字一般不会通过审核。</p>\r\n<p>4、飞鸟阅读中文网有权将该作品推荐给合作伙伴宣传或转载，以便为作者寻找更多带来收益的机会，不另行专门告知。</p>\r\n<p>（注：创建作品后请尽快提交新书审核，未通过审核的作品将在三个月后将被删除。请妥善保存好稿件，避免删除后遗失。）</p>\r\n<p>书名首字母的设置规则<br />书名首字母应设置为书名首字的拼音字母，若书名首字为数字或其他字符可随意填写一个字母作为作品的书名首字母。</p>', '', '3', '0', '1', '0', '1', '1', '1729600214', '0', '0');
INSERT INTO `fn_article` VALUES ('3', '1', '怎么管理作品', '', '0', '0', '', '', '<p>作品保存期限是多久？<br />未通过审核的作品三个月后将被删除。请妥善保存好稿件，避免删除后遗失。</p>\r\n<p>在哪可以修改我的作品信息？<br />登录【作者专区】--点击【作品库】--点击【编辑】，即可进行作品相关信息修改。</p>\r\n<p>如何上传作品封面？<br />未审核通过及初始状态的书籍登录【作者专区】--点击【作品库】--点击【编辑】--【点击上传封面】即可进行封面上传；</p>\r\n<p>如已通过审核的作品需要修改封面，请联系客服修改，已签约作品联系责编修改。</p>\r\n<p>我要发布新章节，如何操作？<br />登录【作者专区】--点击【作品库】--点击某作品的【章节管理】--点击【添加章节】即可发布新的章节。</p>\r\n<p>我要调整章节信息，怎样操作？<br />登录【作者专区】--点击【作品库】--点击某作品的【章节管理】，然后调整已发布章节的章节信息。</p>\r\n<p>我可以在线更新作品么？<br />登录【作者专区】--点击【作品库】--点击某作品的【章节管理】，然后在线更新作品。您还可以将已创作的内容保存为草稿，以便下次继续进行创作。</p>\r\n<p>我可以对作品圈子进行什么操作？<br />登录【作者专区】--点击【作品库】--点击某作品的【书评管理】--点击【圈主管理】，然后为当前已选作品设置圈主。圈主可以在作品信息页的书评区对书评进行置顶、加精、锁帖及删除的操作。作者本人也可以在自己作品的作品信息页书评区进行相同操作。</p>\r\n<p>我想查看一下最近的更新情况，在哪可以进行这个操作？<br />登录【作者专区】--点击【作品库】--点击某作品的【更新情况】查询您已选作品的最近更新情况。</p>\r\n<p>如何删除作品？<br />对于未签约、审核未通过作品，可登录【作者专区】--点击【作品库】--点击某作品的【删除】按钮，即可删除；已签约、审核已通过的作品不可删除。</p>', '', '2', '0', '1', '0', '1', '1', '1729600331', '0', '0');
INSERT INTO `fn_article` VALUES ('4', '1', '怎么管理自己的账号', '', '0', '0', '', '', '<p>注册/登录<br />什么是飞鸟阅读账号？<br />飞鸟阅读账号是您在使用飞鸟阅读中文网的重要身份标识，注册账号后，您可登录进行管理书架、投推荐票、投月票、发布评论、充值并阅读付费章节等操作。</p>\r\n<p>如何注册飞鸟阅读账号？<br />您可以通过电脑端的飞鸟阅读中文网首页，点击“注册”按钮，使用手机号进行注册。</p>\r\n<p>什么是飞鸟阅读昵称？<br />飞鸟阅读昵称是用户注册时必填的一项信息，可以使用中文、英文、数字和下划线，用户在发布评论、发送短信息等操作时均显示为此名称，建议您使用中文，便于网友们相互辨识。</p>\r\n<p>飞鸟阅读账号不能使用的几种情况，如果您填写的飞鸟阅读账号不能使用，请查看是否存在下列原因：<br />1、账号的输入不符合要求。请输入英文及数字作为用户名，长度在6-16个字符之间，且不能以数字作为开头；</p>\r\n<p>2、您输入的账号已经被使用。飞鸟阅读账号是您在飞鸟阅读中文网的账户登录名，具有唯一性。如果您在注册时出现“该用户名已被使用”的内容提示，则说明该账号之前已经被人注册使用，系统会自动进行提醒，请您重新输入另一个账号进行注册。</p>\r\n<p>如何修改我的账户登录密码？<br />登录飞鸟阅读中文网首页后，点击【账号头像--进入个人中心--账户信息修改--修改密码】，按照提示输入您的旧密码和新密码，即可修改成功。</p>\r\n<p>忘记飞鸟阅读账号、密码如何找回？<br />1、忘记账号：请联系在线客服提交昵称和注册手机号帮您查找用户名。</p>\r\n<p>2、忘记密码：在登录界面点击【忘记密码】通过已绑定手机号用户找回密码；</p>\r\n<p>3、未绑定手机号用户找回密码请联系客服提供证明自己是账号所有人的相关信息，进行在线申诉找回，因需要人工审核时间，5个工作日内将会告知找回结果。</p>\r\n<p>怎么注销账号？<br />1、账号注销是不可恢复的操作，阅读账号注销须知后，仍然需要注销，联系客服提供账号相关信息，以及个人中心截图；</p>\r\n<p>2、如果您是作者，请先在PC网页版【作者专区-网站公告-问题咨询】留言申请删除作品和关闭作者账号，等关闭作者账号后，联系客服申请注销账号。</p>\r\n<p>怎么修改绑定手机号？<br />1、登录飞鸟阅读中文网首页后，点击【账号头像--进入个人中心--账户信息修改--修改绑定】，即可修改手机号；</p>\r\n<p>2、手机号在72小时内有过注册、绑定、改绑的操作，会提示“操作频繁。请您耐心等待72小时之后再进行修改操作”。</p>', '', '1', '0', '1', '0', '1', '1', '1729600466', '0', '0');

-- ----------------------------
-- Table structure for fn_article_cate
-- ----------------------------
DROP TABLE IF EXISTS `fn_article_cate`;
CREATE TABLE `fn_article_cate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父类ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '分类名称',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `desc` varchar(1000) DEFAULT '' COMMENT '描述',
  `sort` int(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='文章分类::crud';

-- ----------------------------
-- Records of fn_article_cate
-- ----------------------------
INSERT INTO `fn_article_cate` VALUES ('1', '0', '帮助中心', '', '', '0', '1729590240', '0', '0');

-- ----------------------------
-- Table structure for fn_article_keywords
-- ----------------------------
DROP TABLE IF EXISTS `fn_article_keywords`;
CREATE TABLE `fn_article_keywords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `keywords_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联关键字id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `inid` (`keywords_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='文章关联表';

-- ----------------------------
-- Records of fn_article_keywords
-- ----------------------------
INSERT INTO `fn_article_keywords` VALUES ('1', '1', '1', '1', '1610198553');

-- ----------------------------
-- Table structure for fn_author
-- ----------------------------
DROP TABLE IF EXISTS `fn_author`;
CREATE TABLE `fn_author` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `sex` tinyint(4) DEFAULT NULL COMMENT '性别1男2女',
  `headimg` varchar(256) CHARACTER SET utf8 DEFAULT NULL COMMENT '头像',
  `true_name` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT '姓名',
  `mobile` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT '手机',
  `email` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '邮箱',
  `qq` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'qq',
  `province` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '省',
  `city` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '市',
  `county` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '地区',
  `address` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '详情地址',
  `postcode` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT '邮编',
  `idcard` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '身份证号',
  `idcardpos` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '身份证正面',
  `idcardside` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '身份证反面',
  `bankcard` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '银行卡号',
  `bankdeposit` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '开户银行',
  `bankprovince` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '开户行所在省',
  `bankcity` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '开户行所在市',
  `bankcounty` varchar(200) DEFAULT NULL COMMENT '银行地区',
  `bankaddress` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '完整银行地址',
  `bankcardphoto` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '银行卡照',
  `workunit` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '工作单位',
  `telephone` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '固定电话',
  `salt` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '密码盐',
  `password` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '密码',
  `ip` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT 'IP',
  `birth` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT '生日',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态',
  `update_time` int(11) DEFAULT '0' COMMENT '修改时间',
  `authstate` tinyint(4) DEFAULT '0' COMMENT '认证状态1已认证0未认证',
  `bankstate` tinyint(4) DEFAULT '0' COMMENT '银行认证状态：1已认证0未认证',
  `issign` tinyint(4) DEFAULT '0' COMMENT '是否签约',
  PRIMARY KEY (`id`),
  KEY `openid` (`openid`) USING BTREE,
  KEY `mobile` (`mobile`),
  KEY `join_time` (`create_time`),
  KEY `idx_w_i_s_j` (`issign`,`status`,`create_time`) USING BTREE,
  KEY `idcard` (`idcard`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作者::crud';

-- ----------------------------
-- Records of fn_author
-- ----------------------------

-- ----------------------------
-- Table structure for fn_author_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_author_log`;
CREATE TABLE `fn_author_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '类型1作者2作品',
  `pid` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID或作品ID',
  `front` varchar(2000) CHARACTER SET utf8 DEFAULT NULL COMMENT '操作前名称',
  `after` varchar(2000) CHARACTER SET utf8 DEFAULT NULL COMMENT '操作后名称',
  `action` tinyint(4) DEFAULT '0' COMMENT '操作行为|	1笔名2银行卡3作品名4作品简介5手机号',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态0待审1已审2驳回3完成',
  `intro` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '简介',
  `reason` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '操作理由',
  `editor` int(10) DEFAULT '0' COMMENT '操作员',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `updatetime` int(11) DEFAULT '0' COMMENT '更新状态',
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`pid`) USING BTREE,
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作家或作品行为日志';

-- ----------------------------
-- Records of fn_author_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_author_month_income
-- ----------------------------
DROP TABLE IF EXISTS `fn_author_month_income`;
CREATE TABLE `fn_author_month_income` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '作者ID',
  `realname` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `penname` varchar(200) DEFAULT NULL COMMENT '笔名',
  `idcard` varchar(50) DEFAULT NULL COMMENT '身份证',
  `mobile` varchar(50) DEFAULT NULL COMMENT '手机号',
  `month` int(10) DEFAULT '0' COMMENT '日期',
  `real_money` decimal(10,2) DEFAULT '0.00' COMMENT '实发工资',
  `tax` decimal(10,2) DEFAULT '0.00' COMMENT '税',
  `third_money` decimal(10,2) DEFAULT '0.00' COMMENT '三方总和',
  `own_money` decimal(10,2) DEFAULT '0.00' COMMENT '站内收益总和',
  `debt_deduction` decimal(10,2) DEFAULT '0.00' COMMENT '抵扣总和',
  `bankcard` varchar(200) DEFAULT NULL COMMENT '银行卡号',
  `openbank` varchar(250) DEFAULT NULL COMMENT '开户行',
  `accountlocation` varchar(250) DEFAULT NULL COMMENT '开户地',
  `editor` varchar(100) DEFAULT NULL COMMENT '责编',
  `editorid` int(10) DEFAULT '0' COMMENT '责编ID',
  `is_pay` tinyint(4) DEFAULT '0' COMMENT '是否已打款0未打1已打',
  `note` varchar(1000) DEFAULT NULL COMMENT '备注',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '总额',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `month` (`month`),
  KEY `idx_u_m_i` (`user_id`,`month`,`is_pay`) USING BTREE,
  KEY `idx_u_m` (`user_id`,`month`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作者月收益';

-- ----------------------------
-- Records of fn_author_month_income
-- ----------------------------

-- ----------------------------
-- Table structure for fn_author_sign
-- ----------------------------
DROP TABLE IF EXISTS `fn_author_sign`;
CREATE TABLE `fn_author_sign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `genre` tinyint(4) DEFAULT '0' COMMENT '协议类型：1正常协议2补充协议',
  `uid` int(10) DEFAULT '0' COMMENT '用户ID',
  `pid` int(10) DEFAULT '0' COMMENT '作品ID',
  `contractno` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '合同编号',
  `age` smallint(3) DEFAULT '0' COMMENT '年龄',
  `mode` tinyint(4) DEFAULT '0' COMMENT '签约方式1线上2线下',
  `type` tinyint(4) DEFAULT '0' COMMENT '签约方式1分成2买断3买断+保障计划4保底',
  `level` tinyint(4) DEFAULT '0' COMMENT '签约级别',
  `rtype` tinyint(4) DEFAULT '0' COMMENT '补充协议类型1笔名修改2银行卡修改3作品名修改4买断转分成修改',
  `divideratio` float(5,2) DEFAULT '0.00' COMMENT '分成比例，对应分成类型',
  `price` float(10,2) DEFAULT '0.00' COMMENT '价格，对应买断和保底',
  `attendance` tinyint(3) DEFAULT '0' COMMENT '初始全勤收入',
  `words` int(10) DEFAULT '0' COMMENT '签约字数',
  `qq` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'QQ',
  `mobile` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '手机',
  `flowid` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '实名认证流程Id',
  `realnameauth` tinyint(4) DEFAULT '0' COMMENT '实名认证状态：1成功，0失败|准备废除',
  `postcode` varchar(20) CHARACTER SET utf8 DEFAULT NULL COMMENT '邮编',
  `address` varchar(200) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '地址',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态0待审1完成2已审3已确认4拒绝5后台全通过',
  `outlinestate` tinyint(4) DEFAULT '0' COMMENT '大纲审核状态0待审1通过2拒绝',
  `confirmstate` tinyint(4) NOT NULL DEFAULT '0' COMMENT '确认状态：0待确认1已确认2拒绝',
  `reason` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '理由',
  `contract` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '合同',
  `rcontract` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '补充协议合同',
  `parentauth` varchar(250) CHARACTER SET utf8 DEFAULT NULL COMMENT '未成年确认书',
  `copyright_party_name` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '签约主体代码',
  `editor` int(10) DEFAULT '0' COMMENT '编辑',
  `applytme` int(11) DEFAULT '0' COMMENT '申请时间',
  `verifytime` int(11) DEFAULT '0' COMMENT '审核时间',
  `completetime` int(11) DEFAULT '0' COMMENT '完成时间',
  `contracttime` int(11) DEFAULT '0' COMMENT '生成合同时间',
  `confirmtime` int(11) DEFAULT '0' COMMENT '确认时间',
  `cid` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '旧签约ID',
  `lid` int(11) DEFAULT '0' COMMENT '操作日志ID',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `uid` (`uid`),
  KEY `genre` (`genre`,`rtype`,`uid`) USING BTREE,
  KEY `pid` (`pid`,`uid`) USING BTREE,
  KEY `idx_uid_status_completetime` (`uid`,`status`,`completetime`) USING BTREE,
  KEY `idx_p_u_g` (`pid`,`uid`,`genre`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of fn_author_sign
-- ----------------------------

-- ----------------------------
-- Table structure for fn_bank_card
-- ----------------------------
DROP TABLE IF EXISTS `fn_bank_card`;
CREATE TABLE `fn_bank_card` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `card_no` varchar(100) NOT NULL DEFAULT '' COMMENT '卡号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `bank_name` varchar(100) DEFAULT NULL COMMENT '银行名称',
  `card_image` varchar(255) DEFAULT NULL COMMENT '卡片图片',
  `bank_address` varchar(100) DEFAULT '' COMMENT '银行地址',
  `full_name` varchar(100) DEFAULT NULL COMMENT '卡主姓名',
  `mobile` varchar(50) DEFAULT NULL COMMENT '卡主电话',
  `status` tinyint(4) DEFAULT '0' COMMENT '卡片状态',
  `auth_status` tinyint(4) DEFAULT '0' COMMENT '认证状态',
  `remark` varchar(512) DEFAULT '' COMMENT '备注',
  `create_time` int(11) DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `card_no` (`card_no`),
  KEY `uid_status` (`user_id`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='银行卡::crud';

-- ----------------------------
-- Records of fn_bank_card
-- ----------------------------

-- ----------------------------
-- Table structure for fn_book
-- ----------------------------
DROP TABLE IF EXISTS `fn_book`;
CREATE TABLE `fn_book` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT COMMENT '标题',
  `title` varchar(255) DEFAULT NULL COMMENT '作品名称',
  `author` varchar(255) DEFAULT NULL COMMENT '作者',
  `authorid` int(10) NOT NULL DEFAULT '0' COMMENT '作者ID',
  `cover` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '封面图片',
  `filename` varchar(255) DEFAULT NULL COMMENT '文件名称',
  `localcover` tinyint(4) DEFAULT '0' COMMENT '是否为本地封面',
  `remark` text COMMENT '简介',
  `outline` text COMMENT '大纲',
  `outlinetime` int(11) DEFAULT '0' COMMENT '大纲时间',
  `style` varchar(100) DEFAULT NULL COMMENT '风格',
  `ending` varchar(100) DEFAULT NULL COMMENT '结局',
  `genre` smallint(6) DEFAULT '0' COMMENT '大类',
  `subgenre` smallint(6) DEFAULT '0' COMMENT '小类',
  `isfinish` tinyint(5) DEFAULT '0' COMMENT '完结状态0连载1完结',
  `finishtime` int(11) DEFAULT '0' COMMENT '完结时间',
  `chapters` int(10) DEFAULT '0' COMMENT '章节数',
  `label` varchar(255) DEFAULT NULL COMMENT '作品标签',
  `label_custom` varchar(100) DEFAULT NULL COMMENT '自定义标签',
  `hits` bigint(20) DEFAULT '0' COMMENT '流量',
  `words` int(10) DEFAULT '0' COMMENT '总字数',
  `comments` int(10) DEFAULT '0' COMMENT '评论数',
  `sort` int(10) DEFAULT '0' COMMENT '排序',
  `sharetitle` text CHARACTER SET utf8 COMMENT '分享标题',
  `sharedesc` text CHARACTER SET utf8 COMMENT '分享简介',
  `shareimage` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '分享图片',
  `status` tinyint(4) DEFAULT '0' COMMENT '是否上架',
  `editorid` int(11) DEFAULT '0' COMMENT '编辑ID',
  `editor` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '编辑人员',
  `issign` tinyint(4) DEFAULT '0' COMMENT '是否签约:0否1是',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `authorid` (`authorid`),
  KEY `create_time` (`create_time`),
  KEY `idx_s_i_s_c` (`status`,`issign`,`create_time`) USING BTREE,
  KEY `idx_s_i_c` (`status`,`create_time`) USING BTREE,
  KEY `title` (`title`(191),`author`(191)),
  KEY `finishtime` (`finishtime`),
  KEY `idx_iswz_finishtime` (`isfinish`,`finishtime`) USING BTREE,
  KEY `update_time` (`update_time`),
  KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作品::crud';

-- ----------------------------
-- Records of fn_book
-- ----------------------------

-- ----------------------------
-- Table structure for fn_book_monthly_salary
-- ----------------------------
DROP TABLE IF EXISTS `fn_book_monthly_salary`;
CREATE TABLE `fn_book_monthly_salary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '作者ID',
  `book_id` int(11) NOT NULL DEFAULT '0' COMMENT '作品ID',
  `sign_id` int(11) DEFAULT '0' COMMENT '签约ID',
  `sign_type` varchar(50) DEFAULT NULL COMMENT '签约类型',
  `month` int(10) DEFAULT '0' COMMENT '年月日',
  `pen_name` varchar(200) DEFAULT NULL COMMENT '笔名',
  `book_title` varchar(300) DEFAULT NULL COMMENT '作品名称',
  `word_count` int(10) DEFAULT '0' COMMENT '月更字数',
  `thousand_words_money` decimal(10,2) DEFAULT '0.00' COMMENT '千字价格',
  `minimum_amount` decimal(10,2) DEFAULT '0.00' COMMENT '保底金额',
  `total_minimum_amount` decimal(10,2) DEFAULT '0.00' COMMENT '累计保底金额',
  `is_super_guaranteed` varchar(50) DEFAULT NULL COMMENT '是否超保底',
  `super_guaranteed_money` decimal(10,2) DEFAULT '0.00' COMMENT '超保底金额',
  `other_income` decimal(10,2) DEFAULT '0.00' COMMENT '其他收益',
  `delay_money` decimal(10,2) DEFAULT '0.00' COMMENT '延迟金额',
  `channel_income` decimal(10,2) DEFAULT '0.00' COMMENT '渠道收益',
  `share_ratio` decimal(10,2) DEFAULT '0.00' COMMENT '分成比例',
  `copyright_income` decimal(10,2) DEFAULT '0.00' COMMENT '电子版权收益',
  `rewards_attendance` decimal(10,2) DEFAULT '0.00' COMMENT '酬勤奖励',
  `payment_status` tinyint(4) DEFAULT '0' COMMENT '支付状态（ 未支付0,已付1, 延迟2）',
  `note` varchar(1000) DEFAULT NULL COMMENT '备注',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '总稿费',
  `editor` varchar(50) DEFAULT NULL COMMENT '责编',
  `editor_id` int(11) DEFAULT '0' COMMENT '责编',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `bookid` (`book_id`),
  KEY `idx_bookid_month` (`book_id`,`month`) USING BTREE,
  KEY `idx_u_m_s` (`user_id`,`month`) USING BTREE,
  KEY `idx_month_status` (`month`) USING BTREE,
  KEY `month` (`month`),
  KEY `idx_u_m` (`user_id`,`month`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `idx_sign_month` (`sign_type`,`month`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作品月度收益';

-- ----------------------------
-- Records of fn_book_monthly_salary
-- ----------------------------

-- ----------------------------
-- Table structure for fn_category
-- ----------------------------
DROP TABLE IF EXISTS `fn_category`;
CREATE TABLE `fn_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '文档ID',
  `name` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '标题',
  `key` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级分类ID',
  `ordernum` smallint(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序（同级有效）',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `icons` varchar(250) DEFAULT NULL COMMENT '图标',
  `create_user_id` int(11) unsigned DEFAULT '0' COMMENT '创建人',
  `update_user_id` int(11) unsigned DEFAULT '0' COMMENT '修改人',
  `create_time` int(11) unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='作品分类::crud';

-- ----------------------------
-- Records of fn_category
-- ----------------------------
INSERT INTO `fn_category` VALUES ('1', '玄幻', 'xuanhuan', '0', '50', '1', null, '1', '0', '1414492021', '0');
INSERT INTO `fn_category` VALUES ('2', '奇幻', 'qihuan', '0', '50', '1', null, '1', '0', '1414492034', '0');
INSERT INTO `fn_category` VALUES ('3', '武侠', 'wuxia', '0', '50', '1', null, '1', '0', '1414492039', '0');
INSERT INTO `fn_category` VALUES ('4', '仙侠', 'xianxia', '0', '50', '1', null, '1', '0', '1414492045', '0');
INSERT INTO `fn_category` VALUES ('5', '都市', 'dushi', '0', '50', '1', null, '1', '0', '1414492050', '0');
INSERT INTO `fn_category` VALUES ('6', '历史', 'lishi', '0', '50', '1', null, '1', '0', '1414492056', '0');
INSERT INTO `fn_category` VALUES ('7', '军事', 'junshi', '0', '50', '1', null, '1', '0', '1414492062', '0');
INSERT INTO `fn_category` VALUES ('8', '游戏', 'youxi', '0', '50', '1', null, '1', '0', '1414492068', '0');
INSERT INTO `fn_category` VALUES ('9', '体育', 'tiyu', '0', '50', '1', '', '1', '1', '1414492076', '1723457967');
INSERT INTO `fn_category` VALUES ('10', '科幻', 'kehuan', '0', '50', '1', null, '1', '0', '1414492082', '0');
INSERT INTO `fn_category` VALUES ('11', '悬疑', 'xuanyi', '0', '50', '1', '', '1', '1', '1414492087', '1723457533');
INSERT INTO `fn_category` VALUES ('12', '同人', 'tongren', '0', '50', '1', null, '1', '0', '1414492093', '0');
INSERT INTO `fn_category` VALUES ('13', '女生', 'nvsheng', '0', '50', '1', null, '1', '0', '1414492098', '0');
INSERT INTO `fn_category` VALUES ('14', '其他', 'qita', '0', '50', '1', null, '1', '0', '1414492103', '0');
INSERT INTO `fn_category` VALUES ('15', '东方玄幻', 'dongfangxuanhuan', '1', '0', '1', '', '0', '0', '1723451741', '1723452165');
INSERT INTO `fn_category` VALUES ('16', '异世', 'yishi', '1', '0', '1', '', '0', '0', '1723453410', '0');
INSERT INTO `fn_category` VALUES ('17', '争霸', 'zhengba', '1', '0', '1', '', '0', '0', '1723453455', '0');
INSERT INTO `fn_category` VALUES ('18', '高武', 'gaowu', '1', '0', '1', '', '0', '0', '1723453499', '0');
INSERT INTO `fn_category` VALUES ('19', '魔法', 'mofa', '2', '0', '1', '', '0', '0', '1723453563', '0');
INSERT INTO `fn_category` VALUES ('20', '史诗', 'shishi', '2', '0', '1', '', '0', '0', '1723453587', '0');
INSERT INTO `fn_category` VALUES ('21', '神秘', 'shenmi', '2', '0', '1', '', '0', '0', '1723453609', '0');
INSERT INTO `fn_category` VALUES ('22', '现代', 'xiandai', '2', '0', '1', '', '0', '0', '1723453639', '0');
INSERT INTO `fn_category` VALUES ('23', '神史', 'shenshi', '2', '0', '1', '', '1', '0', '1723454040', '0');
INSERT INTO `fn_category` VALUES ('24', '空幻', 'konghuan', '2', '0', '1', '', '1', '0', '1723454060', '0');
INSERT INTO `fn_category` VALUES ('25', '武侠', 'wuxia', '3', '0', '1', '', '1', '0', '1723456636', '0');
INSERT INTO `fn_category` VALUES ('26', '潮流', 'chaoliu', '3', '0', '1', '', '1', '0', '1723456648', '0');
INSERT INTO `fn_category` VALUES ('27', '国术', 'guoshu', '3', '0', '1', '', '1', '0', '1723456657', '0');
INSERT INTO `fn_category` VALUES ('28', '古武', 'guwu', '3', '0', '1', '', '1', '0', '1723456665', '0');
INSERT INTO `fn_category` VALUES ('29', '武侠同人', 'wuxiatongren', '3', '0', '1', '', '1', '0', '1723456673', '0');
INSERT INTO `fn_category` VALUES ('30', '修真', 'xiuzhen', '4', '0', '1', '', '1', '0', '1723456796', '0');
INSERT INTO `fn_category` VALUES ('31', '幻修', 'huanxiu', '4', '0', '1', '', '1', '0', '1723456805', '0');
INSERT INTO `fn_category` VALUES ('32', '现修', 'xianxiu', '4', '0', '1', '', '1', '0', '1723456813', '0');
INSERT INTO `fn_category` VALUES ('33', '神话', 'shenhua', '4', '0', '1', '', '1', '0', '1723456824', '0');
INSERT INTO `fn_category` VALUES ('34', '古典', 'gudian', '4', '0', '1', '', '1', '0', '1723456833', '0');
INSERT INTO `fn_category` VALUES ('35', '生活', 'shenghuo', '5', '0', '1', '', '1', '0', '1723456867', '0');
INSERT INTO `fn_category` VALUES ('36', '娱乐', 'yule', '5', '0', '1', '', '1', '0', '1723456875', '0');
INSERT INTO `fn_category` VALUES ('37', '商场', 'shangchang', '5', '0', '1', '', '1', '0', '1723456883', '0');
INSERT INTO `fn_category` VALUES ('38', '异能', 'yineng', '5', '0', '1', '', '1', '0', '1723456894', '0');
INSERT INTO `fn_category` VALUES ('39', '异术', 'yishu', '5', '0', '1', '', '1', '0', '1723456902', '0');
INSERT INTO `fn_category` VALUES ('40', '校园', 'xiaoyuan', '5', '0', '1', '', '1', '0', '1723456910', '0');
INSERT INTO `fn_category` VALUES ('41', '架空', 'jiakong', '6', '0', '1', '', '1', '0', '1723457012', '0');
INSERT INTO `fn_category` VALUES ('42', '先秦', 'xianqin', '6', '0', '1', '', '1', '0', '1723457021', '0');
INSERT INTO `fn_category` VALUES ('43', '秦汉三国', 'qinhansanguo', '6', '0', '1', '', '1', '0', '1723457030', '0');
INSERT INTO `fn_category` VALUES ('44', '两晋隋唐', 'liangjinsuitang', '6', '0', '1', '', '1', '0', '1723457037', '0');
INSERT INTO `fn_category` VALUES ('45', '五代十国', 'wudaishiguo', '6', '0', '1', '', '1', '0', '1723457043', '0');
INSERT INTO `fn_category` VALUES ('46', '两宋元明', 'liangsongyuanming', '6', '0', '1', '', '1', '0', '1723457050', '0');
INSERT INTO `fn_category` VALUES ('47', '清民', 'qingmin', '6', '0', '1', '', '1', '0', '1723457056', '0');
INSERT INTO `fn_category` VALUES ('48', '外史', 'waishi', '6', '0', '1', '', '1', '0', '1723457064', '0');
INSERT INTO `fn_category` VALUES ('49', '传记', 'zhuanji', '6', '0', '1', '', '1', '0', '1723457070', '0');
INSERT INTO `fn_category` VALUES ('50', '传说', 'chuanshuo', '6', '0', '1', '', '1', '0', '1723457077', '0');
INSERT INTO `fn_category` VALUES ('51', '战争', 'zhanzheng', '7', '0', '1', '', '1', '0', '1723457102', '0');
INSERT INTO `fn_category` VALUES ('52', '战斗', 'zhandou', '7', '0', '1', '', '1', '0', '1723457110', '0');
INSERT INTO `fn_category` VALUES ('53', '激战', 'jizhan', '7', '0', '1', '', '1', '0', '1723457119', '0');
INSERT INTO `fn_category` VALUES ('54', '军旅', 'junlv', '7', '0', '1', '', '1', '0', '1723457125', '0');
INSERT INTO `fn_category` VALUES ('55', '抗战', 'kangzhan', '7', '0', '1', '', '1', '0', '1723457131', '0');
INSERT INTO `fn_category` VALUES ('56', '电竞', 'dianjing', '8', '0', '1', '', '1', '0', '1723457160', '0');
INSERT INTO `fn_category` VALUES ('57', '虚拟', 'xuni', '8', '0', '1', '', '1', '0', '1723457168', '0');
INSERT INTO `fn_category` VALUES ('58', '异界', 'yijie', '8', '0', '1', '', '1', '0', '1723457175', '0');
INSERT INTO `fn_category` VALUES ('59', '系统', 'xitong', '8', '0', '1', '', '1', '0', '1723457182', '0');
INSERT INTO `fn_category` VALUES ('60', '主播', 'zhubo', '8', '0', '1', '', '1', '0', '1723457190', '0');
INSERT INTO `fn_category` VALUES ('61', '星际', 'xingji', '10', '0', '1', '', '1', '0', '1723457234', '0');
INSERT INTO `fn_category` VALUES ('62', '体育', 'tiyu', '9', '0', '1', '', '1', '0', '1723457345', '0');
INSERT INTO `fn_category` VALUES ('63', '篮球', 'lanqiu', '9', '0', '1', '', '1', '0', '1723457355', '0');
INSERT INTO `fn_category` VALUES ('64', '足球', 'zuqiu', '9', '0', '1', '', '1', '0', '1723457365', '0');
INSERT INTO `fn_category` VALUES ('65', '推理', 'tuili', '11', '0', '1', '', '1', '0', '1723457410', '0');
INSERT INTO `fn_category` VALUES ('66', '悬疑', 'xuanyi', '11', '0', '1', '', '1', '0', '1723457990', '0');
INSERT INTO `fn_category` VALUES ('67', '生存', 'shengcun', '11', '0', '1', '', '1', '0', '1723457999', '0');
INSERT INTO `fn_category` VALUES ('68', '奇妙', 'qimiao', '11', '0', '1', '', '1', '0', '1723458006', '0');
INSERT INTO `fn_category` VALUES ('69', '传奇', 'chuanqi', '11', '0', '1', '', '1', '0', '1723458012', '0');
INSERT INTO `fn_category` VALUES ('70', '动漫同人', 'dongmantongren', '12', '0', '1', '', '1', '0', '1723458218', '0');
INSERT INTO `fn_category` VALUES ('71', '小说同人', 'xiaoshuotongren', '12', '0', '1', '', '1', '0', '1723458227', '0');
INSERT INTO `fn_category` VALUES ('72', '影视同人', 'yingshitongren', '12', '0', '1', '', '1', '0', '1723458239', '0');
INSERT INTO `fn_category` VALUES ('73', '古言', 'guyan', '13', '0', '1', '', '1', '0', '1723458268', '0');
INSERT INTO `fn_category` VALUES ('74', '现言', 'xianyan', '13', '0', '1', '', '1', '0', '1723458276', '0');
INSERT INTO `fn_category` VALUES ('75', '幻情', 'huanqing', '13', '0', '1', '', '1', '0', '1723458285', '0');
INSERT INTO `fn_category` VALUES ('76', '仙侠', 'xianxia', '13', '0', '1', '', '1', '0', '1723458292', '0');
INSERT INTO `fn_category` VALUES ('77', '青春', 'qingchun', '13', '0', '1', '', '1', '0', '1723458299', '0');
INSERT INTO `fn_category` VALUES ('78', '游戏', 'youxi', '13', '0', '1', '', '1', '0', '1723458307', '0');
INSERT INTO `fn_category` VALUES ('79', '科幻', 'kehuan', '13', '0', '1', '', '1', '0', '1723458314', '0');
INSERT INTO `fn_category` VALUES ('80', '悬疑', 'xuanyi', '13', '0', '1', '', '1', '0', '1723458320', '0');
INSERT INTO `fn_category` VALUES ('81', '轻小说', 'qingxiaoshuo', '13', '0', '1', '', '1', '0', '1723458329', '0');
INSERT INTO `fn_category` VALUES ('82', '短篇', 'duanpian', '13', '0', '1', '', '1', '0', '1723458335', '0');
INSERT INTO `fn_category` VALUES ('83', '现实', 'xianshi', '13', '0', '1', '', '1', '0', '1723458343', '0');
INSERT INTO `fn_category` VALUES ('84', '影视', 'yingshi', '14', '0', '1', '', '1', '0', '1723458353', '0');
INSERT INTO `fn_category` VALUES ('85', '出版', 'chuban', '14', '0', '1', '', '1', '0', '1723458362', '0');
INSERT INTO `fn_category` VALUES ('86', '穿梭', 'chuansuo', '10', '0', '1', '', '1', '0', '1723458414', '0');
INSERT INTO `fn_category` VALUES ('87', '未来', 'weilai', '10', '0', '1', '', '1', '0', '1723458427', '0');
INSERT INTO `fn_category` VALUES ('88', '机甲', 'jijia', '10', '0', '1', '', '1', '0', '1723458435', '0');
INSERT INTO `fn_category` VALUES ('89', '科技', 'keji', '10', '0', '1', '', '1', '0', '1723458442', '0');
INSERT INTO `fn_category` VALUES ('90', '变异', 'bianyi', '10', '0', '1', '', '1', '0', '1723458449', '0');
INSERT INTO `fn_category` VALUES ('91', '末世', 'moshi', '10', '0', '1', '', '1', '0', '1723458457', '0');

-- ----------------------------
-- Table structure for fn_chapter
-- ----------------------------
DROP TABLE IF EXISTS `fn_chapter`;
CREATE TABLE `fn_chapter` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `bookid` bigint(20) DEFAULT '0' COMMENT '作品ID',
  `title` varchar(255) DEFAULT NULL COMMENT '章节标题',
  `chaps` int(11) DEFAULT '0' COMMENT '第几章节',
  `authorid` int(11) DEFAULT '0' COMMENT '作者ID',
  `volumeid` smallint(6) DEFAULT '0' COMMENT '卷ID',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态',
  `draft` tinyint(3) DEFAULT '0' COMMENT '草稿',
  `verify` tinyint(4) DEFAULT '0' COMMENT '是否审核',
  `verifyresult` varchar(255) DEFAULT NULL COMMENT '审核结果',
  `verifypeople` varchar(200) DEFAULT NULL COMMENT '审核人',
  `verifytime` int(11) DEFAULT '0' COMMENT '审核时间',
  `wordnum` int(10) DEFAULT '0' COMMENT '字数',
  `trial_time` int(11) DEFAULT '0' COMMENT '定时时间',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  `firstpasstime` int(11) DEFAULT '0' COMMENT '首次审核通过时间',
  `firstverifyword` int(10) DEFAULT '0' COMMENT '首次过审字数',
  `ip` varchar(20) DEFAULT NULL COMMENT 'IP',
  PRIMARY KEY (`id`),
  KEY `chaps` (`chaps`) USING BTREE,
  KEY `idx_a_s_d_v` (`bookid`,`status`,`draft`,`verify`,`create_time`) USING BTREE,
  KEY `idx_a_s_d_v_c` (`bookid`,`status`,`draft`,`verify`,`chaps`) USING BTREE,
  KEY `idx_s_v_d_c` (`status`,`draft`,`verify`,`create_time`) USING BTREE,
  KEY `anid` (`bookid`),
  KEY `idx_a_s_d_v_v` (`bookid`,`status`,`draft`,`verify`,`verifytime`) USING BTREE,
  KEY `idx_a_s_d_v_f` (`bookid`,`status`,`draft`,`verify`,`firstpasstime`) USING BTREE,
  KEY `title` (`bookid`,`title`(191)) USING BTREE,
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='章节::crud';

-- ----------------------------
-- Records of fn_chapter
-- ----------------------------

-- ----------------------------
-- Table structure for fn_chapter_draft
-- ----------------------------
DROP TABLE IF EXISTS `fn_chapter_draft`;
CREATE TABLE `fn_chapter_draft` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT '0' COMMENT '章节ID',
  `aid` int(11) DEFAULT '0' COMMENT '作品ID',
  `bid` bigint(11) DEFAULT '0' COMMENT '作者ID',
  `vid` smallint(6) DEFAULT '0' COMMENT '卷ID',
  `title` varchar(255) DEFAULT NULL COMMENT '章节标题',
  `chaps` smallint(6) DEFAULT '0' COMMENT '章节序号',
  `content` text COMMENT '内容',
  `wordnum` smallint(6) DEFAULT '0' COMMENT '字数',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `aid` (`aid`,`bid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='章节草稿箱';

-- ----------------------------
-- Records of fn_chapter_draft
-- ----------------------------

-- ----------------------------
-- Table structure for fn_chapter_verify
-- ----------------------------
DROP TABLE IF EXISTS `fn_chapter_verify`;
CREATE TABLE `fn_chapter_verify` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT '0' COMMENT '章节ID',
  `aid` int(11) DEFAULT '0' COMMENT '作者ID',
  `bid` bigint(11) DEFAULT '0' COMMENT '作品ID',
  `vid` int(6) DEFAULT '0' COMMENT '卷ID',
  `title` varchar(255) DEFAULT NULL COMMENT '章节标题',
  `chaps` smallint(6) DEFAULT '0' COMMENT '章节序号',
  `content` text COMMENT '内容',
  `wordnum` smallint(6) DEFAULT '0' COMMENT '字数',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `aid` (`aid`,`bid`) USING BTREE,
  KEY `bid` (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='审核章节::crud';

-- ----------------------------
-- Records of fn_chapter_verify
-- ----------------------------

-- ----------------------------
-- Table structure for fn_coin_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_coin_log`;
CREATE TABLE `fn_coin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '变动类型0减1加',
  `amount` int(11) DEFAULT '0' COMMENT '数目',
  `balance` int(11) DEFAULT '0' COMMENT '余额',
  `title` varchar(200) DEFAULT NULL COMMENT '标题',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `create_time` int(11) DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币记录';

-- ----------------------------
-- Records of fn_coin_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_config
-- ----------------------------
DROP TABLE IF EXISTS `fn_config`;
CREATE TABLE `fn_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '配置名称',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '配置标识',
  `content` text COMMENT '配置内容',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ----------------------------
-- Records of fn_config
-- ----------------------------
INSERT INTO `fn_config` VALUES ('1', '网站配置', 'web', 'a:16:{s:2:"id";s:1:"1";s:11:"admin_title";s:12:"飞鸟阅读";s:5:"title";s:12:"飞鸟阅读";s:4:"logo";s:34:"/static/home/images/login_logo.png";s:4:"file";s:0:"";s:6:"domain";s:23:"https://www.feiniao.com";s:3:"icp";s:18:"京ICP备123456789";s:7:"version";s:6:"2.0.38";s:5:"beian";s:15:"京A2-123456789";s:8:"template";s:7:"default";s:12:"send_captcha";s:1:"2";s:13:"upload_driver";s:1:"1";s:8:"keywords";s:12:"飞鸟阅读";s:4:"desc";s:94:"飞鸟阅读是一套基于ThinkPHP6 + Layui + MySql打造功能丰富的小说管理系统。";s:4:"code";s:0:"";s:9:"copyright";s:96:"Copyright © feiniao All Rights Reserved 版权所有 北京飞鸟阅读网络技术有限公司";}', '1', '1612514630', '1730020051');
INSERT INTO `fn_config` VALUES ('2', '邮箱配置', 'email', 'a:8:{s:2:\"id\";s:1:\"2\";s:4:\"smtp\";s:11:\"smtp.qq.com\";s:9:\"smtp_port\";s:3:\"465\";s:9:\"smtp_user\";s:10:\"123@qq.com\";s:8:\"smtp_pwd\";s:6:\"123456\";s:4:\"from\";s:27:\"飞鸟阅读系统管理员\";s:5:\"email\";s:12:\"admin@qq.com\";s:8:\"template\";s:101:\"<p>飞鸟阅读是一套基于ThinkPHP6 + Layui + MySql打造功能丰富的小说管理系统。</p>\";}', '1', '1612521657', '1730020077');
INSERT INTO `fn_config` VALUES ('3', '微信配置', 'wechat', 'a:15:{s:13:\"official_open\";s:1:\"2\";s:5:\"appid\";s:0:\"\";s:9:\"appsecret\";s:0:\"\";s:2:\"id\";s:1:\"3\";s:5:\"token\";s:0:\"\";s:7:\"aes_key\";s:0:\"\";s:8:\"pay_open\";s:1:\"2\";s:5:\"mchid\";s:0:\"\";s:11:\"secrect_key\";s:0:\"\";s:8:\"cert_url\";s:0:\"\";s:4:\"file\";s:0:\"\";s:7:\"key_url\";s:0:\"\";s:8:\"xcx_open\";s:1:\"2\";s:9:\"xcx_appid\";s:0:\"\";s:13:\"xcx_appsecret\";s:0:\"\";}', '1', '1612522314', '1730020122');
INSERT INTO `fn_config` VALUES ('4', 'Token配置', 'token', 'a:5:{s:2:\"id\";s:1:\"4\";s:3:\"iss\";s:7:\"feiniao\";s:3:\"aud\";s:7:\"feiniao\";s:7:\"secrect\";s:7:\"feiniao\";s:7:\"exptime\";s:5:\"86400\";}', '1', '1627313142', '1728893702');
INSERT INTO `fn_config` VALUES ('5', '其他配置', 'other', 'a:9:{s:2:\"id\";s:1:\"5\";s:6:\"author\";s:12:\"飞鸟阅读\";s:7:\"version\";s:7:\"v2.0.18\";s:6:\"editor\";s:1:\"1\";s:10:\"srevicetel\";s:16:\"+86 400 888 8888\";s:12:\"sreviceemail\";s:18:\"server@feiniao.com\";s:9:\"sreviceqr\";s:52:\"/storage/202410/677d1e529bed5bf7895187b976d4da09.png\";s:4:\"file\";s:0:\"\";s:18:\"servicedescription\";s:262:\"服务时间:周一至周日 9:00--18:00\n(不同产品服务时间会有所区别，具体以购买时的服务承诺为准)\n在线服务: 微信关注公众号\n人工服务时间:周一至周日早9点-晚18点，如果您有任务问题，可以联系我们！\";}', '1', '1613725791', '1730020155');
INSERT INTO `fn_config` VALUES ('6', '奖励设置', 'reward', 'a:29:{s:4:\"open\";s:1:\"1\";s:12:\"day_1_reward\";s:2:\"10\";s:12:\"day_2_reward\";s:2:\"15\";s:12:\"day_3_reward\";s:2:\"20\";s:12:\"day_4_reward\";s:2:\"25\";s:12:\"day_5_reward\";s:2:\"30\";s:12:\"day_6_reward\";s:2:\"40\";s:12:\"day_7_reward\";s:2:\"50\";s:12:\"day_8_reward\";s:2:\"60\";s:10:\"account_id\";s:10:\"IqRniYjwxS\";s:7:\"account\";s:3:\"100\";s:9:\"mobile_id\";s:10:\"CtLOp5oQfF\";s:6:\"mobile\";s:3:\"500\";s:9:\"author_id\";s:10:\"53yK6BiEMH\";s:6:\"author\";s:3:\"600\";s:6:\"vip_id\";s:10:\"JFdImsXtVP\";s:3:\"vip\";s:4:\"1000\";s:10:\"chapter_id\";s:10:\"skljMg0dIR\";s:14:\"chapter_number\";s:1:\"5\";s:14:\"chapter_reward\";s:2:\"10\";s:7:\"like_id\";s:10:\"zAjKbIfPq0\";s:11:\"like_number\";s:2:\"10\";s:11:\"like_reward\";s:2:\"30\";s:13:\"invite_reward\";s:3:\"888\";s:14:\"invite_1_level\";s:2:\"10\";s:14:\"invite_2_level\";s:3:\"120\";s:14:\"invite_3_level\";s:3:\"360\";s:10:\"vip_reward\";s:3:\"1.5\";s:2:\"id\";s:1:\"6\";}', '1', '1725871325', '1728893732');
INSERT INTO `fn_config` VALUES ('7', '邀请设置', 'invite', 'a:5:{s:14:\"invite_content\";s:107:\"我是{nickname}！我在{sitename}发现很多免费小说，还可以下载，邀请你一起观看！！\";s:9:\"textColor\";s:18:\"rgb(254, 247, 210)\";s:4:\"file\";s:0:\"\";s:2:\"id\";s:1:\"7\";s:6:\"bglist\";s:52:\"/storage/202409/57b30ea4f30a39055fd4a93f97241c9b.jpg\";}', '1', '1726122843', '1726296335');
INSERT INTO `fn_config` VALUES ('8', 'VIP设置', 'vip', 'a:14:{s:4:\"open\";s:1:\"1\";s:11:\"level_1_day\";s:1:\"7\";s:7:\"level_1\";s:1:\"1\";s:11:\"level_2_day\";s:2:\"15\";s:7:\"level_2\";s:2:\"99\";s:11:\"level_3_day\";s:2:\"30\";s:7:\"level_3\";s:4:\"4999\";s:11:\"level_4_day\";s:2:\"90\";s:7:\"level_4\";s:4:\"5800\";s:11:\"level_5_day\";s:3:\"180\";s:7:\"level_5\";s:4:\"6600\";s:11:\"level_6_day\";s:3:\"365\";s:7:\"level_6\";s:4:\"8888\";s:2:\"id\";s:1:\"8\";}', '1', '1727752408', '1729357264');
INSERT INTO `fn_config` VALUES ('9', '支付宝设置', 'alipay', 'a:9:{s:4:\"open\";s:1:\"2\";s:2:\"id\";s:1:\"9\";s:5:\"appid\";s:6:\"123456\";s:6:\"public\";s:6:\"123456\";s:7:\"private\";s:6:\"123456\";s:11:\"public_cert\";s:0:\"\";s:4:\"file\";s:0:\"\";s:23:\"alipay_public_cert_path\";s:0:\"\";s:21:\"alipay_root_cert_path\";s:0:\"\";}', '1', '1728482799', '1730020230');
INSERT INTO `fn_config` VALUES ('11', '提现设置', 'withdraw', 'a:7:{s:4:\"open\";s:1:\"1\";s:9:\"price_min\";s:1:\"1\";s:9:\"price_max\";s:5:\"10000\";s:5:\"ratio\";s:4:\"1000\";s:3:\"tax\";s:4:\"0.07\";s:11:\"description\";s:443:\"<p>1、提现可重复发起!剩余金额可下次满足提现额度时申请提现!<br />2、提现一般3-5天到账(您理解或同意如遇高峰，提现到账时间会延长!)<br />3、为保证顺利提现，提现需用户按照提现规范操作，如用户未按提现要求操作或不符合第三方支付平台的要求等原因导致不能收款(如未实名或提现账号错误或解绑)，平台无需承担任务责任。</p>\";s:2:\"id\";s:2:\"11\";}', '1', '1728809920', '1728893122');
INSERT INTO `fn_config` VALUES ('12', '页面设置', 'page', 'a:15:{s:8:"home_nav";s:8:"NAV_HOME";s:11:"home_notice";s:3:"115";s:16:"home_editor_main";s:3:"119";s:18:"home_editor_images";s:3:"120";s:15:"home_editor_top";s:3:"121";s:13:"home_banner_1";s:3:"122";s:18:"home_banner_buttom";s:3:"123";s:15:"header_keywords";s:3:"124";s:16:"home_1_link_name";s:7:"home_ai";s:16:"home_2_link_name";s:9:"home_coin";s:12:"mobile_slide";s:3:"114";s:11:"mobile_edit";s:3:"116";s:13:"mobile_banner";s:3:"117";s:14:"mobile_newbook";s:3:"118";s:2:"id";s:2:"12";}', '1', '1729661325', '1729665071');
INSERT INTO `fn_config` VALUES ('13', 'SEO设置', 'seo', 'a:39:{s:10:\"site_title\";s:12:\"飞鸟阅读\";s:9:\"book_mark\";s:1:\"1\";s:10:\"book_split\";s:1:\",\";s:6:\"domain\";s:18:\"feiniao.paheng.net\";s:10:\"home_title\";s:96:\"{网站名}_书友最值得收藏的txt小说网_{网站名}小说无弹窗_新{网站名}官网\";s:13:\"home_keywords\";s:167:\"{网站名},{年份}免费全本小说,{年份}小说阅读网,{年份}免费阅读,{年份}全本小说,{网站名},{网站名}小说,{网站名}TXT,{网站名}官网\";s:16:\"home_description\";s:403:\"{网站名}小说网是广大书友最值得收藏的网络小说阅读网，新{网站名}，新域名{域名}小说网网站收录了当前最火热的网络小说，提供小说下载，txt全文免费下载。{网站名}小说网免费提供高质量的小说最新章节，是广大网络小说爱好者必备的小说阅读网。新{网站名}文学小说网倾情推荐的无弹窗小说阅读。\";s:4:\"type\";s:4:\"task\";s:14:\"classify_title\";s:124:\"好看的爽文系列{大类名}在线免费阅读无弹窗,{大类名}{网站名}小说排行榜txt打包下载-{网站名}\";s:17:\"classify_keywords\";s:103:\"{大类名},最新{大类名}小说,最新{大类名}{网站名}txt电子书,{大类名}小说排行榜\";s:20:\"classify_description\";s:158:\"{网站名}提供最新{大类名}免费在线阅读无弹窗，好看的{大类名}新书txt电子书免费下载，最新{大类名}热门小说排行榜。\";s:11:\"shuku_title\";s:86:\"{年份}免费小说作品在线阅读_{网站名}限时免费小说大全-{网站名}\";s:14:\"shuku_keywords\";s:46:\"小说,小说大全,{年份}限时免费小说\";s:17:\"shuku_description\";s:275:\"{网站名}限时免费小说频道,提供限时免费的玄幻小说,武侠小说,原创小说,网游小说,都市小说,言情小说,青春小说,历史小说,军事小说,网游小说,科幻小说,恐怖小说等小说作品,更多限时免费的小说尽在{网站名}。\";s:9:\"top_title\";s:74:\"{年份}小说人气排行榜单_{网站名}小说人气排行-{网站名}\";s:12:\"top_keywords\";s:65:\"{年份}最新小说排行榜,{年份}最新小说人气排行榜\";s:15:\"top_description\";s:162:\"{网站名}小说人气排行榜单,为您提供{网站名}人气排行榜,想看更多小说人气排行榜,欢迎访问{网站名}小说人气排行榜专区。\";s:13:\"quanben_title\";s:71:\"{年份}完本小说大全_{年份}完本小说在线阅读-{网站名}\";s:16:\"quanben_keywords\";s:35:\"{年份}小说,{年份}完本小说\";s:19:\"quanben_description\";s:177:\"{年份}完本小说大全,{网站名}为书友提供,{年份}完本小说在线阅读服务,列表中的小说均按照人气进行排序,更多完本小说尽在{网站名}。\";s:10:\"book_title\";s:110:\"爽文系列{书名}({作者}){网站名}小说网最新章节_{书名}无弹窗广告_{大类名}_{网站名}\";s:13:\"book_keywords\";s:67:\"{书名}txt{网站名}网下载,{书名}最新章节,{作者}作品\";s:16:\"book_description\";s:125:\"{网站名}为{作者}书迷提供热门爽文系列{大类名}小说{书名}在线免费阅读,{书名}{作品简介|len=50}\";s:12:\"author_title\";s:59:\"{作者}最新作品_{作者}全网最新更新_{网站名}\";s:15:\"author_keywords\";s:82:\"{作者}最新作品,{作者}最新章节,{作者}{作者所有作品名}原作者\";s:18:\"author_description\";s:143:\"{作者}于{作者注册时间}成为本站{作者签约状态}作者，创作了{作者所有作品名}等多部脍炙人口的热门作品。\";s:18:\"chapter_list_title\";s:128:\"{网站名}小说网提供{书名}({作者})最新爽文章节小说_{书名}无弹窗广告免费阅读_{大类名}_{网站名}\";s:21:\"chapter_list_keywords\";s:35:\"{书名}最新章节列表,{书名}\";s:24:\"chapter_list_description\";s:219:\"热门小说{网站名}小说排行榜{书名}最新章节由{网站名}({域名})提供，{作者}写的{大类名}{书名}是一本经典小说，新{网站名}文学官网为主本站无弹窗广告可放心阅读。\";s:13:\"chapter_title\";s:92:\"{网站名}小说网最新热门小说{章节名}_{书名}_{大类名}_{网站名}({域名})\";s:16:\"chapter_keywords\";s:29:\"{章节名},{书名},{作者}\";s:19:\"chapter_description\";s:159:\"此小说是{作者}写的{大类名}小说{书名}章节{章节名}，{网站名}提供的最新热门爽文系列小说排行榜小说{书名}最新章节。\";s:12:\"invite_title\";s:78:\"{网站名}邀请好友成为{网站名}读者可获得{邀请金币}个金币\";s:15:\"invite_keywords\";s:59:\"{网站名}邀请,{网站名}邀请函,{网站名}邀请码\";s:18:\"invite_description\";s:133:\"邀请好友成为{网站名}读者可获得{邀请金币}个金币，还能持续获得简介收益，请劳记自己的邀请码。\";s:10:\"task_title\";s:78:\"做任务赢金币_签到得金币_日常任务_看小说得金币_{网站名}\";s:13:\"task_keywords\";s:59:\"{网站名}任务,{网站名}签到,{网站名}日常任务\";s:16:\"task_description\";s:137:\"每天来{网站名}任务中心，完成日常任务，签到任务可获得巨量的任务奖励，任务所得金币可以提现哦。\";s:2:\"id\";s:2:\"13\";}', '1', '1732241462', '1732353714');
INSERT INTO `fn_config` VALUES ('14', '内容设置', 'content', 'a:3:{s:15:\"chapter_min_num\";s:2:\"20\";s:15:\"chapter_max_num\";s:5:\"10000\";s:2:\"id\";s:2:\"14\";}', '1', '1737603390', '1737604206');
-- ----------------------------
-- Table structure for fn_department
-- ----------------------------
DROP TABLE IF EXISTS `fn_department`;
CREATE TABLE `fn_department` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '部门名称',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级部门id',
  `leader_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '部门负责人ID',
  `phone` varchar(60) NOT NULL DEFAULT '' COMMENT '部门联系电话',
  `remark` varchar(1000) DEFAULT '' COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COMMENT='部门组织';

-- ----------------------------
-- Records of fn_department
-- ----------------------------
INSERT INTO `fn_department` VALUES ('1', '飞鸟科技', '0', '0', '13688888888', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('2', '分公司一', '1', '0', '13688888889', '', '1', '0', '1730002349');
INSERT INTO `fn_department` VALUES ('3', '人事部', '2', '0', '13688888898', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('4', '财务部', '2', '0', '13688888898', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('5', '市场部', '2', '0', '13688888978', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('6', '销售部', '2', '0', '13688889868', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('7', '技术部', '2', '0', '13688898858', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('8', '客服部', '2', '0', '13688988848', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('9', '销售一部', '6', '0', '13688998838', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('10', '销售二部', '6', '0', '13688999828', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('11', '分公司二', '1', '0', '13688999918', '', '1', '0', '1730002358');
INSERT INTO `fn_department` VALUES ('12', '人事部', '11', '0', '13688888886', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('13', '市场部', '11', '0', '13688888886', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('14', '财务部', '11', '0', '13688888876', '', '1', '0', '0');
INSERT INTO `fn_department` VALUES ('15', '销售部', '11', '0', '13688888666', '', '1', '0', '0');

-- ----------------------------
-- Table structure for fn_favorites
-- ----------------------------
DROP TABLE IF EXISTS `fn_favorites`;
CREATE TABLE `fn_favorites` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pid` int(11) NOT NULL COMMENT '作品ID',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收藏::crud';
-- ----------------------------
-- Records of fn_favorites
-- ----------------------------

DROP TABLE IF EXISTS `fn_follow`;
CREATE TABLE `fn_follow` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) DEFAULT '0' COMMENT '1作者2用户',
  `user_id` int(11) NOT NULL,
  `from_id` int(11) NOT NULL COMMENT '被关注者ID',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `from_id` (`from_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='关注::crud';

-- ----------------------------
-- Table structure for fn_file
-- ----------------------------
DROP TABLE IF EXISTS `fn_file`;
CREATE TABLE `fn_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(15) NOT NULL DEFAULT '' COMMENT '所属模块',
  `sha1` varchar(60) NOT NULL COMMENT 'sha1',
  `md5` varchar(60) NOT NULL COMMENT 'md5',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '原始文件名',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
  `filepath` varchar(255) NOT NULL DEFAULT '' COMMENT '文件路径+文件名',
  `filesize` int(10) NOT NULL DEFAULT '0' COMMENT '文件大小',
  `fileext` varchar(10) NOT NULL DEFAULT '' COMMENT '文件后缀',
  `mimetype` varchar(100) NOT NULL DEFAULT '' COMMENT '文件类型',
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件分组ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传会员ID',
  `uploadip` varchar(15) NOT NULL DEFAULT '' COMMENT '上传IP',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未审核1已审核-1不通过',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `admin_id` int(11) NOT NULL COMMENT '审核者id',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `audit_time` int(11) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '来源模块功能',
  `use` varchar(255) DEFAULT NULL COMMENT '用处',
  `download` int(11) NOT NULL DEFAULT '0' COMMENT '下载量',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COMMENT='文件表';

-- ----------------------------
-- Records of fn_file
-- ----------------------------
INSERT INTO `fn_file` VALUES ('74', 'admin', '228964d19b82e53a80b164dba38b43f95f033b6c', '57b30ea4f30a39055fd4a93f97241c9b', 'b84c8736-2f90-4dbe-97d9-4fa990efca44.jpg', '202409/57b30ea4f30a39055fd4a93f97241c9b.jpg', '/storage/202409/57b30ea4f30a39055fd4a93f97241c9b.jpg', '426324', 'jpg', 'image/jpeg', '0', '1', '127.0.0.1', '1', '1726284329', '1', '0', '1726284329', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('76', 'api', '353ee088538d1b8a74406b9609daf81ece45225b', 'd83937d471867368324b16f4b0fefdc1', 'blob', '202409/d83937d471867368324b16f4b0fefdc1', '/storage/202409/d83937d471867368324b16f4b0fefdc1', '22333', '', 'image/png', '0', '1', '127.0.0.1', '0', '1726642745', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('77', 'api', '353ee088538d1b8a74406b9609daf81ece45225b', 'd83937d471867368324b16f4b0fefdc1', 'blob', '202409/d83937d471867368324b16f4b0fefdc1', '/storage/202409/d83937d471867368324b16f4b0fefdc1', '22333', '', 'image/png', '0', '1', '127.0.0.1', '0', '1726642758', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('78', 'api', '784dfe4c090d4548b0d3c5c1e9515b4e30080909', '9736f17b99b5f821b5ca79e5a9570aec', 'blob', '202409/9736f17b99b5f821b5ca79e5a9570aec', '/storage/202409/9736f17b99b5f821b5ca79e5a9570aec', '23306', '', 'image/png', '0', '1', '127.0.0.1', '0', '1726642882', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('84', 'admin', 'bf554ab7918e7a02adc658d8a8e7b280fc8c4e1b', 'bbf7be518112a50d7e2fa22768443dc2', 'apiclient_cert.pem', '202409/bbf7be518112a50d7e2fa22768443dc2.pem', '/storage/202409/bbf7be518112a50d7e2fa22768443dc2.pem', '1513', 'pem', 'application/octet-stream', '0', '1', '27.189.239.252', '1', '1727249185', '1', '0', '1727249185', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('85', 'admin', '28e499ae009580520177a9c4a23e6115fbfc4376', 'ba78e18d0b216237eaa0bc4ecd08684c', 'apiclient_key.pem', '202409/ba78e18d0b216237eaa0bc4ecd08684c.pem', '/storage/202409/ba78e18d0b216237eaa0bc4ecd08684c.pem', '1704', 'pem', 'application/octet-stream', '0', '1', '27.189.239.252', '1', '1727249188', '1', '0', '1727249188', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('86', 'admin', 'ce567b7fd3a965632337c9de83ea0350978c7fd7', '5f16aff46a366256e044f1864b0c9859', 'apiclient_cert.pem', '202409/5f16aff46a366256e044f1864b0c9859.pem', '/storage/202409/5f16aff46a366256e044f1864b0c9859.pem', '1517', 'pem', 'application/octet-stream', '0', '1', '27.189.238.194', '1', '1727667527', '1', '0', '1727667527', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('87', 'admin', 'ec72c0d643dbdbdd74e296e01fb7acafb5e654ac', '7045518a9ef46074c7abeff647345c2b', 'apiclient_key.pem', '202409/7045518a9ef46074c7abeff647345c2b.pem', '/storage/202409/7045518a9ef46074c7abeff647345c2b.pem', '1704', 'pem', 'application/octet-stream', '0', '1', '27.189.238.194', '1', '1727667529', '1', '0', '1727667529', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('88', 'admin', '9725e7bcc5c1fc748f52036196c3ff91a153dbda', '7eb487afdfc03959a91a2fab46d2b339', 'alipayPublicKey_RSA2.crt', '202410/7eb487afdfc03959a91a2fab46d2b339.crt', '/storage/202410/7eb487afdfc03959a91a2fab46d2b339.crt', '392', 'crt', 'application/x-x509-ca-cert', '0', '1', '27.189.238.66', '1', '1728532743', '1', '0', '1728532743', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('89', 'admin', '1ea7a77f158a1ea0473f83a0c65310ebdbfcbd1e', '54dcd48ce996412715e43b4e52a8e47d', 'appCertPublicKey_2018070460514302.crt', '202410/54dcd48ce996412715e43b4e52a8e47d.crt', '/storage/202410/54dcd48ce996412715e43b4e52a8e47d.crt', '1675', 'crt', 'application/x-x509-ca-cert', '0', '1', '27.189.238.66', '1', '1728535183', '1', '0', '1728535183', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('90', 'admin', '55fe069e3e7f99ece4cfbd94d58dc823dceea750', 'da035ab2dfa389d1dcd6e475c0f3480a', 'alipayCertPublicKey_RSA2.crt', '202410/da035ab2dfa389d1dcd6e475c0f3480a.crt', '/storage/202410/da035ab2dfa389d1dcd6e475c0f3480a.crt', '3103', 'crt', 'application/x-x509-ca-cert', '0', '1', '27.189.238.66', '1', '1728535187', '1', '0', '1728535187', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('91', 'admin', 'b028798ef82381d42ff56c2de16924f8ccf7f239', 'b6612a80b13013892c8c5c0829f62367', 'alipayRootCert.crt', '202410/b6612a80b13013892c8c5c0829f62367.crt', '/storage/202410/b6612a80b13013892c8c5c0829f62367.crt', '5130', 'crt', 'application/x-x509-ca-cert', '0', '1', '27.189.238.66', '1', '1728535190', '1', '0', '1728535190', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('92', 'admin', '04617e51fe5c1204434a2e822ca1995a9aa59425', '677d1e529bed5bf7895187b976d4da09', '飞鸟阅读.png', '202410/677d1e529bed5bf7895187b976d4da09.png', '/storage/202410/677d1e529bed5bf7895187b976d4da09.png', '26395', 'png', 'image/png', '0', '1', '106.118.45.252', '1', '1728893780', '1', '0', '1728893780', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('93', 'api', 'd677993e6c0e7346da993d0a246369eaaf9bc730', 'd590636ff444385dfc93f5f8f5ab62a9', '024AF089-18DD-4594-9D4D-C1E893B39E09.jpeg', '202410/d590636ff444385dfc93f5f8f5ab62a9.jpeg', '/storage/202410/d590636ff444385dfc93f5f8f5ab62a9.jpeg', '114873', 'jpeg', 'image/jpeg', '0', '1', '106.118.45.252', '0', '1729043858', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('94', 'api', 'fc91009f2521e97a8ab2e7ab1f11c18fd721cbce', 'f7bc8339138825f0078443e85e3f9524', 'F79F1F57-1CFF-48B8-8EE8-2DBBA0D1015D.jpeg', '202410/f7bc8339138825f0078443e85e3f9524.jpeg', '/storage/202410/f7bc8339138825f0078443e85e3f9524.jpeg', '2687485', 'jpeg', 'image/jpeg', '0', '1', '106.118.45.252', '0', '1729066248', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('95', 'api', '04617e51fe5c1204434a2e822ca1995a9aa59425', '677d1e529bed5bf7895187b976d4da09', '飞鸟阅读.png', '202410/677d1e529bed5bf7895187b976d4da09.png', '/storage/202410/677d1e529bed5bf7895187b976d4da09.png', '26395', 'png', 'image/png', '0', '1', '106.118.45.252', '0', '1729086888', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('96', 'api', '04617e51fe5c1204434a2e822ca1995a9aa59425', '677d1e529bed5bf7895187b976d4da09', '飞鸟阅读.png', '202410/677d1e529bed5bf7895187b976d4da09.png', '/storage/202410/677d1e529bed5bf7895187b976d4da09.png', '26395', 'png', 'image/png', '0', '1', '106.118.45.252', '0', '1729086993', '0', '0', '0', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('97', 'admin', '26d418b76b7b3da773d55d837f261bd1f5ee1fed', '6750906c9bd369992d371566b239eee7', '6750906c9bd369992d371566b239eee7.png', '202410/6750906c9bd369992d371566b239eee7.png', '/storage/202410/6750906c9bd369992d371566b239eee7.png', '16653', 'png', 'image/png', '0', '1', '106.118.45.252', '1', '1729355883', '1', '0', '1729355883', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('98', 'admin', '26d418b76b7b3da773d55d837f261bd1f5ee1fed', '6750906c9bd369992d371566b239eee7', 'e03cc7ff65a2f6a7ee5e7fcc140222db.png', '202410/6750906c9bd369992d371566b239eee7.png', '/storage/202410/6750906c9bd369992d371566b239eee7.png', '16653', 'png', 'image/png', '0', '1', '106.118.45.252', '1', '1729356130', '1', '0', '1729356130', 'upload', 'thumb', '0');
INSERT INTO `fn_file` VALUES ('99', 'admin', 'a7ee4855b0dcee5d32fb1f431ad4d8e39118dd87', '0a079b82fae9b60c8244c73e9781637c', 'logo-pc.png', '202410/0a079b82fae9b60c8244c73e9781637c.png', '/storage/202410/0a079b82fae9b60c8244c73e9781637c.png', '16625', 'png', 'image/png', '0', '1', '27.189.239.149', '1', '1730000661', '1', '0', '1730000661', 'upload', 'thumb', '0');

-- ----------------------------
-- Table structure for fn_file_group
-- ----------------------------
DROP TABLE IF EXISTS `fn_file_group`;
CREATE TABLE `fn_file_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '分组名',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件分组表';

-- ----------------------------
-- Records of fn_file_group
-- ----------------------------

-- ----------------------------
-- Table structure for fn_gallery
-- ----------------------------
DROP TABLE IF EXISTS `fn_gallery`;
CREATE TABLE `fn_gallery` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '属性:1精华,2热门,3推荐',
  `is_home` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否首页显示:0否,1是',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0下架 1正常',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '图集名称',
  `thumb` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '缩略图',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '图集摘要',
  `content` text COMMENT '内容',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `origin` varchar(255) NOT NULL DEFAULT '' COMMENT '来源或作者',
  `origin_url` varchar(255) NOT NULL DEFAULT '' COMMENT '来源地址',
  `read` int(11) NOT NULL DEFAULT '0' COMMENT '阅读量',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图集::crud';

-- ----------------------------
-- Records of fn_gallery
-- ----------------------------

-- ----------------------------
-- Table structure for fn_gallery_cate
-- ----------------------------
DROP TABLE IF EXISTS `fn_gallery_cate`;
CREATE TABLE `fn_gallery_cate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '图集分类名称',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `desc` varchar(1000) DEFAULT '' COMMENT '描述',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图集分类::crud';

-- ----------------------------
-- Records of fn_gallery_cate
-- ----------------------------

-- ----------------------------
-- Table structure for fn_gallery_file
-- ----------------------------
DROP TABLE IF EXISTS `fn_gallery_file`;
CREATE TABLE `fn_gallery_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图集ID',
  `file_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文件id',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '图片名称',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '摘要',
  `filepath` varchar(200) NOT NULL DEFAULT '' COMMENT '图片路径',
  `link` varchar(200) NOT NULL DEFAULT '' COMMENT '链接地址',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图集关联表';

-- ----------------------------
-- Records of fn_gallery_file
-- ----------------------------

-- ----------------------------
-- Table structure for fn_gallery_keywords
-- ----------------------------
DROP TABLE IF EXISTS `fn_gallery_keywords`;
CREATE TABLE `fn_gallery_keywords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '图集ID',
  `keywords_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联关键字id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='图集关联表';

-- ----------------------------
-- Records of fn_gallery_keywords
-- ----------------------------
INSERT INTO `fn_gallery_keywords` VALUES ('1', '1', '1', '1644823517');

-- ----------------------------
-- Table structure for fn_goods
-- ----------------------------
DROP TABLE IF EXISTS `fn_goods`;
CREATE TABLE `fn_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '属性:1精华,2热门,3推荐',
  `is_home` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否首页显示:0否,1是',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '商品状态:0下架,1正常',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '商品名称',
  `thumb` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '缩略图',
  `banner` varchar(1000) NOT NULL DEFAULT '' COMMENT '商品轮播图',
  `tips` varchar(255) NOT NULL DEFAULT '' COMMENT '商品卖点，一句话推销',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '商品摘要',
  `content` text NOT NULL COMMENT '内容',
  `base_price` decimal(10,2) DEFAULT '0.00' COMMENT '市场价格',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '实际价格',
  `stocks` int(11) NOT NULL DEFAULT '0' COMMENT '商品库存',
  `sales` int(11) NOT NULL DEFAULT '0' COMMENT '商品销量',
  `address` varchar(200) NOT NULL DEFAULT '' COMMENT '商品发货地址',
  `start_time` int(10) unsigned DEFAULT '0' COMMENT '开始抢购时间',
  `end_time` int(10) unsigned DEFAULT '0' COMMENT '结束抢购时间',
  `read` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '阅读量',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_mail` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否包邮:0否,1是',
  `tag_values` varchar(200) NOT NULL DEFAULT '' COMMENT '商品标签:1正品保证,2一年保修,3七天退换,4赠运费险,5闪电发货,6售后无忧',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '编辑时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品::crud';

-- ----------------------------
-- Records of fn_goods
-- ----------------------------

-- ----------------------------
-- Table structure for fn_goods_cate
-- ----------------------------
DROP TABLE IF EXISTS `fn_goods_cate`;
CREATE TABLE `fn_goods_cate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级id',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `desc` varchar(1000) DEFAULT '' COMMENT '描述',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品分类::crud';

-- ----------------------------
-- Records of fn_goods_cate
-- ----------------------------

-- ----------------------------
-- Table structure for fn_goods_keywords
-- ----------------------------
DROP TABLE IF EXISTS `fn_goods_keywords`;
CREATE TABLE `fn_goods_keywords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `keywords_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联关键字id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `inid` (`keywords_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='商品关联表';

-- ----------------------------
-- Records of fn_goods_keywords
-- ----------------------------
INSERT INTO `fn_goods_keywords` VALUES ('1', '1', '1', '1', '1644823517');

-- ----------------------------
-- Table structure for fn_keywords
-- ----------------------------
DROP TABLE IF EXISTS `fn_keywords`;
CREATE TABLE `fn_keywords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '关键字名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='关键字表';

-- ----------------------------
-- Records of fn_keywords
-- ----------------------------
INSERT INTO `fn_keywords` VALUES ('1', '飞鸟阅读', '0', '1', '1610183567', '1610184824');

-- ----------------------------
-- Table structure for fn_like_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_like_log`;
CREATE TABLE `fn_like_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `book_id` int(11) DEFAULT '0' COMMENT '作品ID',
  `chapter_id` int(11) DEFAULT '0' COMMENT '章节ID',
  `like_date` date NOT NULL COMMENT '点赞日期',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `sign_date` (`like_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='点赞记录';

-- ----------------------------
-- Records of fn_like_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_links
-- ----------------------------
DROP TABLE IF EXISTS `fn_links`;
CREATE TABLE `fn_links` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '网站标题',
  `logo` int(11) NOT NULL DEFAULT '0' COMMENT '网站logo',
  `src` varchar(255) DEFAULT NULL COMMENT '链接',
  `target` int(1) NOT NULL DEFAULT '1' COMMENT '是否新窗口打开，1是,0否',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态:1可用-1禁用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='友情链接';

-- ----------------------------
-- Records of fn_links
-- ----------------------------
INSERT INTO `fn_links` VALUES ('1', '飞鸟阅读', '98', 'https://feiniao.paheng.net', '1', '1', '1', '1624516962', '1729603096');

-- ----------------------------
-- Table structure for fn_nav
-- ----------------------------
DROP TABLE IF EXISTS `fn_nav`;
CREATE TABLE `fn_nav` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '标识',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '1可用-1禁用',
  `desc` varchar(1000) DEFAULT NULL,
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='导航';

-- ----------------------------
-- Records of fn_nav
-- ----------------------------
INSERT INTO `fn_nav` VALUES ('1', '主导航', 'NAV_HOME', '1', '平台主导航', '0', '0');
INSERT INTO `fn_nav` VALUES ('2', '底部导航', 'footer_nav', '1', '', '0', '0');
INSERT INTO `fn_nav` VALUES ('3', '底部帮助中心', 'footer_help', '1', '', '0', '0');
INSERT INTO `fn_nav` VALUES ('4', '首页AI人工智能', 'home_ai', '1', '', '0', '0');
INSERT INTO `fn_nav` VALUES ('5', '首页阅读赚钱', 'home_coin', '1', '', '0', '0');

-- ----------------------------
-- Table structure for fn_nav_info
-- ----------------------------
DROP TABLE IF EXISTS `fn_nav_info`;
CREATE TABLE `fn_nav_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `nav_id` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT '',
  `src` varchar(255) DEFAULT NULL,
  `param` varchar(255) DEFAULT NULL,
  `target` int(1) NOT NULL DEFAULT '0' COMMENT '是否新窗口打开,默认0,1新窗口打开',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '1可用,-1禁用',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COMMENT='导航详情表';

-- ----------------------------
-- Records of fn_nav_info
-- ----------------------------
INSERT INTO `fn_nav_info` VALUES ('1', '0', '1', '首页', '/', '', '0', '1', '9', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('6', '0', '1', '公告', '/home/notice.html', '', '0', '1', '5', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('7', '0', '1', '手机版', '', '', '0', '1', '2', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('8', '0', '2', '关于我们', '/home/page-about.html', '', '1', '1', '9', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('9', '0', '2', '用户协议', '/home/page-agreement.html', '', '1', '1', '3', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('10', '0', '2', '隐私政策', '/home/page-privacy.html', '', '1', '1', '2', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('11', '0', '2', '联系我们', '/home/page-contactus.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('12', '0', '3', '怎么成为作者？', '/home/news-1.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('13', '0', '3', '如何创建作品', '/home/news-2.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('14', '0', '3', '怎么管理作品', '/home/news-3.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('15', '0', '3', '怎么管理自己的账号', '/home/news-4.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('16', '0', '4', '成为飞鸟阅读的签约作者利用人工智能为您赋能', '/home/page-homeai.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('17', '0', '5', '阅读小说，轻松赚现金！', '/home/page-homecoin.html', '', '1', '1', '0', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('18', '0', '1', '排行', '/home/rank.html', '', '0', '1', '7', '0', '0');
INSERT INTO `fn_nav_info` VALUES ('19', '0', '1', '排行', '/home/quanben.html', '', '0', '1', '6', '0', '0');

-- ----------------------------
-- Table structure for fn_order
-- ----------------------------
DROP TABLE IF EXISTS `fn_order`;
CREATE TABLE `fn_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `pid` int(10) DEFAULT '0' COMMENT '商品id',
  `order_id` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '0' COMMENT '订单号',
  `trade_no` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '支付宝订单号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_address` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '详细地址',
  `product_type` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '商品类型',
  `freight_price` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '运费金额',
  `total_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单商品总数',
  `total_price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单总价',
  `total_postage` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '邮费',
  `pay_price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
  `pay_postage` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付邮费',
  `deduction_price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '抵扣金额',
  `paid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `pay_type` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '支付方式',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态（-1 : 申请退款 -2 : 退货成功 0：待发货；1：待收货；2：已收货；3：待评价；-1：已退款）',
  `refund_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 未退款 1 申请中 2 已退款',
  `refund_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '退款申请类型',
  `refund_express` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '退货快递单号',
  `refund_express_name` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '退货快递名称',
  `refund_reason_wap_img` varchar(2000) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '退款图片',
  `refund_reason_wap_explain` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '退款用户说明',
  `refund_reason_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '退款时间',
  `refund_reason_wap` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '前台退款原因',
  `refund_reason` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '不退款的理由',
  `refund_price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `delivery_name` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '快递名称/送货人姓名',
  `delivery_code` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '快递公司编码',
  `delivery_type` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '发货类型',
  `delivery_id` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '快递单号/手机号',
  `kuaidi_label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '快递单号图片',
  `kuaidi_task_id` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '快递单任务id',
  `kuaidi_order_id` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '快递单订单号',
  `fictitious_content` varchar(500) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '虚拟发货内容',
  `use_integral` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '使用积分',
  `mark` varchar(512) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '备注',
  `is_del` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `remark` varchar(512) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '管理员备注',
  `shipping_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '配送方式 1=快递 ，2=门店自提',
  `is_channel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付渠道(0微信公众号1微信小程序)',
  `is_remind` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '消息提醒',
  `is_system_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '后台是否删除',
  `channel_type` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '用户访问端标识',
  `express_dump` varchar(502) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '订单面单打印信息',
  `virtual_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '虚拟商品类型',
  `virtual_info` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '虚拟商品信息',
  `pay_uid` int(11) NOT NULL DEFAULT '0' COMMENT '支付用户uid',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `order_id_2` (`order_id`,`user_id`) USING BTREE,
  KEY `uid` (`user_id`) USING BTREE,
  KEY `add_time` (`add_time`) USING BTREE,
  KEY `pay_price` (`pay_price`) USING BTREE,
  KEY `paid` (`paid`) USING BTREE,
  KEY `pay_time` (`pay_time`) USING BTREE,
  KEY `pay_type` (`pay_type`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `is_del` (`is_del`) USING BTREE,
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- ----------------------------
-- Records of fn_order
-- ----------------------------

-- ----------------------------
-- Table structure for fn_pages
-- ----------------------------
DROP TABLE IF EXISTS `fn_pages`;
CREATE TABLE `fn_pages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '页面名称',
  `thumb` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '缩略图',
  `banner` varchar(1000) NOT NULL DEFAULT '' COMMENT '图集相册',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '页面摘要',
  `content` text NOT NULL COMMENT '内容',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '页面状态:0下架,1正常',
  `read` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '阅读量',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT 'url文件名',
  `template` varchar(200) NOT NULL DEFAULT '' COMMENT '前端模板',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '编辑时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='单页面::crud';

-- ----------------------------
-- Records of fn_pages
-- ----------------------------
INSERT INTO `fn_pages` VALUES ('1', '关于我们', '1', '', '飞鸟阅读是一套基于ThinkPHP6+Layui+MySql打造功能丰富的小说管理系统。', '<p>飞鸟阅读(feiniao.paheng.net)创立于2002年5月，是国内领先的原创文学网站，隶属于引领行业的正版数字阅读平台和文学IP培育平台——阅文集团旗下。飞鸟阅读以推动中国原创文学事业为宗旨，长期致力于原创文学作者的挖掘与培养，并取得了巨大成果：</p>\n<p>2003年10月，飞鸟阅读开启“在线收费阅读”服务，成为真正意义上的网络文学赢利模式的先锋之一，就此奠定了原创文学的行业基础。此后，飞鸟又推出了作家福利、文学交互、内容发掘推广、版权管理等机制和体系，为原创文学的发展注入了巨大活力，有力推动了中国文学原创事业的发展。</p>\n<p>经过长期努力，飞鸟阅读已经形成了完善的创作、培养、销售为一体的电子在线出版机制，并得以向文化产业全面延伸。通过与国内优秀的网络游戏公司、影视公司和出版社全面展开版权运营，带动了飞鸟阅读众多优秀作品成功改编成网络游戏、影视剧、话剧以及出版线下图书等，形成了一套完整的产业链条。此外，在自有平台保持高速增长的同时，飞鸟阅读积极推进渠道拓展，在互联网、手机及其他手持阅读终端方面开拓出了巨大的市场。成立近10年以来，飞鸟阅读诞生了诸多经典，在历年各大原创作品排行榜占据领先位置。通过飞鸟资深内容专家团队的努力，培养出了众多著名网络作家，有力推动了网络职业作家这一全新职业群体的形成和扩大。</p>\n<p>作为国内领先的原创网络文学网站，飞鸟阅读的作品内容多元，其中玄幻、武侠、都市、历史、军事、游戏、竞技、悬疑、科幻等小说题材均具有极大影响力，适合多元用户群。作为原创文学代表企业，飞鸟阅读受到了众多国内外媒体、研究者的认可，先后荣获了 2010上海推进软件和信息服务业高新技术产业化活动周“上海市信息服务业新业态、新模式优秀企业大奖”、2009年网络文学节“2009年度最佳文学网站奖”、新周刊2009中国娇子新锐榜“年度最有价值网站奖”、“2008福布斯中国新锐媒体”奖等荣誉。</p>\n<p>飞鸟女生网(feiniao.paheng.net)成立于2009年11月，其前身是“飞鸟女生频道”，致力于对女性网络原创文学及作者的培养和挖掘。飞鸟女生网依托飞鸟阅读的成熟运作机制，成功实现了女性网络原创文学的商业化发展模式。</p>\n<p>飞鸟女生网的阶梯型写作全勤制度，在针对知名作者进行全方位宣传和包装的同时，兼顾对新进作者的培养。无论是知名作者还是新人写手，均享有签约作者的专属人身保险计划、VIP作品基本福利计划、分类优秀作品奖励计划、小众类型作品的扶持计划等。在飞鸟女生网丰富多样的福利设置吸引下，培养激励了众多优秀作者，使得网站内容呈现出个性鲜明、百花齐放的良好发展趋势。版权运作方面，飞鸟女生网的海量女性题材小说成为了影视改编剧的剧本摇篮。现如今，飞鸟女生网囊括了《琅琊榜》、《凤囚凰》、《炮灰攻略》等多部热门影视剧的原著小说版权。</p>\n<p>飞鸟女生网依托领先的电子原创阅读平台，在未来将继续引入移动阅读、实体出版、影视改编等多元拓展渠道，建立海量版权交易库，形成一个集版权运作、原创阅读为一体的综合性女性原创文化品牌。</p>', '1', '7', '0', 'about', 'default', '1', '1653984295', '1729582587', '0');
INSERT INTO `fn_pages` VALUES ('2', '联系我们', '0', '', '联系我们', '<p>联系我们</p>\r\n<table style=\"border-collapse:collapse;\" border=\"1\">\r\n<tbody>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">战略合作</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">人才招聘</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">线上投稿</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">战略协作，监察投诉</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">人才自荐，简历投放</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">线上投稿，编读往来</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">xubin@feiniao.com</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">hr@feiniao.com</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">books@feiniao.com</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">联系人：许先生</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">联系人：刘先生</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">联系人：苏先生 范小姐</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\"> </td>\r\n<td style=\"width:32.1444%;height:22.3906px;\"> </td>\r\n<td style=\"width:32.147%;height:22.3906px;\"> </td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">版权合作</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">影视合作</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">广告合作</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">改编衍生，授权出版</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">影视开发、投资发行等</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">广告购买，广告合作</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">yuanqiuyu@feiniao.com</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">yangyanmei@feiniao.com</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">cuiguoqi@feiniao.com</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\">联系人：袁女士</td>\r\n<td style=\"width:32.1444%;height:22.3906px;\">联系人：杨女士</td>\r\n<td style=\"width:32.147%;height:22.3906px;\">联系人：崔先生</td>\r\n</tr>\r\n<tr style=\"height:22.3906px;\">\r\n<td style=\"width:32.1444%;height:22.3906px;\"> </td>\r\n<td style=\"width:32.1444%;height:22.3906px;\"> </td>\r\n<td style=\"width:32.147%;height:22.3906px;\"> </td>\r\n</tr>\r\n<tr>\r\n<td style=\"width:32.1444%;\">内容分销</td>\r\n<td style=\"width:32.1444%;\">内容运营</td>\r\n<td style=\"width:32.147%;\">法务部</td>\r\n</tr>\r\n<tr>\r\n<td style=\"width:32.1444%;\">基地业务，第三方分销</td>\r\n<td style=\"width:32.1444%;\">CP合作，图书作品引入</td>\r\n<td style=\"width:32.147%;\">法律相关</td>\r\n</tr>\r\n<tr>\r\n<td style=\"width:32.1444%;\">yaozonghao@feiniao.com</td>\r\n<td style=\"width:32.1444%;\">wangke@feiniao.com</td>\r\n<td style=\"width:32.147%;\">zhlegal@feiniao.com</td>\r\n</tr>\r\n<tr>\r\n<td style=\"width:32.1444%;\">联系人：姚先生</td>\r\n<td style=\"width:32.1444%;\">联系人：王先生</td>\r\n<td style=\"width:32.147%;\">法务部</td>\r\n</tr>\r\n</tbody>\r\n</table>', '1', '36', '0', 'contactus', 'default', '1', '1729568321', '1729568374', '0');
INSERT INTO `fn_pages` VALUES ('3', '用户协议', '0', '', '', '<p>飞鸟阅读小说用户服务协议</p>\n<p>更新发布日期：2024年5月16日</p>\n<p>本《用户服务协议》系飞鸟阅读网络技术有限公司（以下简称为“飞鸟阅读”）向用户提供产品和服务时与用户（以下或称为“您”）达成的协议（以下简称为“本协议”）。本协议所约定之产品指飞鸟阅读依法享有知识产权并合法经营的由飞鸟阅读中文网（feiniao.paheng.net）及H5页面、移动终端软件“飞鸟阅读小说”App等组成的网络文学平台（以下统称“本产品”或“本软件”）；本协议约定之服务指飞鸟阅读通过本产品向用户提供的包括但不限于产品下载、安装、注册、登录、使用、会员、充值及消费以及各类文学作品的阅读、推荐、展示、分享、听书等服务（以下统称“本服务”）。</p>\n<p>在您开始使用本产品和服务之前，请您务必认真阅读并充分理解本协议，特别是涉及免除或者限制责任的条款、权利许可和信息使用的条款、法律适用和争议解决条款等。其中，免除或者限制责任条款等重要内容将以加粗形式提示您注意，您应重点阅读。如您不同意本协议，这将导致飞鸟阅读无法为您提供完整的服务，您也可以选择停止使用。如您选择同意或使用本服务，则视为您已充分理解本协议并承诺作为本协议的一方当事人接受协议、隐私政策以及与本平台相关的规则、规范、声明、公告、通知等内容的约束。如您在使用本产品时未使用注册账号登录，您将受限仅能使用部分功能和服务，但在使用过程您亦须遵守本协议及服务内相关条款约束。</p>\n<p>青少年用户特别提示：如果您未满18周岁，请在法定监护人的陪同下仔细阅读并充分理解本协议，并征得法定监护人的同意后使用本服务，并请特别注意必须遵守全国青少年网络文明公约：要善于网上学习，不浏览不良信息；要诚实友好交流，不侮辱欺诈他人；要增强自护意识，不随意约会网友；要维护网络安全，不破坏网络秩序；要有益身心健康，不沉溺虚拟时空。如您是监护人,您的被监护人在使用本产品及相关服务时可能使用购买产品或服务等功能。您作为监护人,请保管好您的支付设备、支付账户及支付密码等,以避免被监护人在未取得您同意的情况下通过您的账号使用购买等消费功能。</p>\n<p><br />一、协议范围</p>\n<p>1. 本协议描述飞鸟阅读向用户提供产品和服务的详细条款，是您接受飞鸟阅读提供的产品和服务时适用的通用条款。本协议内容同时包括本产品内不时公示的其他相关协议、各项规则和条款、帮助反馈、服务声明及公告指引、通知等（包括《隐私政策》《社区规范》《会员及自动续费协议》《关于作品涉嫌抄袭的投诉指引》）。上述内容一经发布即生效，并构成本协议的有效组成部分，您同样应当遵守。</p>\n<p>2. 本协议项下涉及的某些服务，可能会由飞鸟阅读的关联公司或与之合作的第三方向您提供，您知晓并同意接受上述服务内容，即视为接受双方之间的相关权利义务关系亦受其约束。若您在使用本产品某一项特定服务时，该服务可能会另有单独的协议、专项规则等，您在使用该项服务前应当仔细阅读并同意相关的单独协议、专项规则等。</p>\n<p>3. 随着经营情况的变化，飞鸟阅读有权修改或变更本协议条款，并将于相关页面发布公告新协议内容，而不另行对用户进行个别通知，更新后的协议条款通过公布即代替原来的协议条款，您可以通过本产品“登录”界面或“设置”页面查阅最新版本的协议条款。您有权不接受修改后的协议，但可能会导致您需要停止使用本产品及相关服务，若您在本协议内容公告变更后继续使用本服务，表示您已充分阅读、理解并接受修改后的协议内容，也将遵循修改后的协议内容使用本服务。</p>\n<p>二、本产品及相关服务</p>\n<p>1. 软件的获取</p>\n<p>1.1 飞鸟阅读给予您一项个人的、不可转让及非排他性的使用本产品的授权，您仅能以专有且非商业目的在单一的手机或其他终端设备上下载、安装、注册、登录、使用、显示、运行本产品，未经飞鸟阅读书面同意，您不得将本产品安装在未经飞鸟阅读明示许可的其他终端设备上，包括但不限于游戏机、电视机等。</p>\n<p>1.2 您可以直接从飞鸟阅读的官网上获取本产品，也可以从得到飞鸟阅读授权的第三方获取，不要安装非法或来路不明的软件程序。如果您从未经飞鸟阅读授权的第三方获取本产品或与本产品名称相同的软件程序，飞鸟阅读无法保证该产品和服务能够正常使用，您因此使用该等软件遭受损失的由您自行承担，飞鸟阅读不予负责。</p>\n<p>2. 软件的安装、卸载与更新</p>\n<p>2.1 飞鸟阅读可能为不同的终端设备开发了不同的应用版本，您必须选择下载与所用终端设备相匹配的版本。下载本产品后，您需要按照提示的步骤及指令正确安装。为提供更加优质、安全的服务，在本产品安装时飞鸟阅读可能推荐您安装其他软件，您可以选择安装或者不安装，或您在使用本产品时我们可能推荐您授权登录其他软件，您可以选择授权或者不授权。</p>\n<p>2.2 如果您不再需要使用本产品或者需要安装新版软件，可以自行卸载。如果您愿意帮助飞鸟阅读改进产品服务，请告知卸载的原因。</p>\n<p>2.3 为了改善用户体验、完善服务内容，飞鸟阅读将不断努力开发新的功能及服务，并为您不时提供软件更新（这些更新可能会采取软件替换、修改、功能强化、版本升级等形式）。</p>\n<p>3. 账号服务</p>\n<p>3.1 您应以真实的身份进行注册，您可以通过运营商正规渠道获取的手机号码注册、登录及使用本产品，您也可以通过微信、QQ和微博账号授权登录本产品，同一用户不得注册超过合理数量产品的账号，飞鸟阅读保留对服务改变和说明的权利。您理解并承诺，您在本产品设置的账号不得违反国家法律法规及政策，亦不得出现任何违法或不良信息。</p>\n<p>3.2 为保障您的合法权益，避免因您注册资料与真实情况不符而发生纠纷，请您注册时务必按照真实、全面、准确、有效的原则填写，并对所提供的信息承担相应的法律责任。飞鸟阅读有权对您的账号信息进行审核，对因您自身原因而造成的不能服务情况，飞鸟阅读保留结束您使用产品和服务资格的权利且不承担任何责任。</p>\n<p>3.3 若您所提供的资料有变更，您应及时登录账号进行更新及修改。若您提供的个人资料与事实不符，飞鸟阅读有权停止向您继续提供产品和服务。当您向飞鸟阅读主张其拥有某账号时，若该账号在飞鸟阅读的注册信息记录与您的身份信息不符，飞鸟阅读有权不予认定该账号为您所有。</p>\n<p>3.4 用户申请注册成功后，飞鸟阅读将根据本产品运营情况分配给您相应账号。您了解账号一旦设定，就不可变更；每注册一个账号，即一次合同缔约行为，成就一份独立的服务合同，但飞鸟阅读有权就同一用户名下的不同账号进行单独或整体的管理。本产品账号的所有权归飞鸟阅读所有，您完成申请注册手续后将获得账号的使用权。</p>\n<p>3.5 本产品账号使用权仅属于初始申请注册人，同时，初始申请注册人不得赠与、借用、租用、转让、买卖、交换、共享、继承或以其他方式许可非初始申请注册人使用其账号，由此导致的任何纠纷，用户应当自行承担相应责任及损失。如果飞鸟阅读发现使用者并非账号初始申请注册人，飞鸟阅读有权在未经通知的情况下回收该账号而无需承担法律责任，由此带来的包括但不限于该账号内用户资料和充值金额、数据、会员权益及服务内容等清空、冻结、无法退还、被视作违约金等风险和损失由用户自行承担，同时飞鸟阅读保留追究上述行为人法律责任的权利。</p>\n<p>3.6 您提供的注册信息是识别您身份的依据，请您务必妥善保管，并就其账号项下之一切活动负全部责任，在以正确的用户账号和密码登录的情况下，使用该账户的人即被视为用户本人，其所作出的任何行为即视为该用户的行为。您同意在任何情况下不向他人透露账户及密码信息，不多人共享同一个账号，飞鸟阅读（包括但不限于飞鸟阅读的网站和论坛管理人员、客服人员等）不会以任何方式询问用户密码，因您的原因造成的账户、密码等信息被冒用、盗用或非法使用，由此引起的一切风险、责任、损失、费用等应由您自行承担，包括但不限于(i)失窃用户未充分举证证明账号密码遗失、被盗是他人故意行为，导致飞鸟阅读无法或不予找回失窃账号给用户造成的损失；（ii）飞鸟阅读找回了失窃账号但账号内的虚拟物品已全部或部分丢失或被消耗而导致的损失等。</p>\n<p>3.7 如您发现有账号或密码被他人非法使用或有异常使用的情形，或您的手机或其他有关设备丢失时，请您立即以有效方式通知飞鸟阅读；您可以向飞鸟阅读申请暂停或停止本产品相应服务。为了保障用户合法权益，用户向飞鸟阅读提交修改密码、挂失等请求时，需要您按照飞鸟阅读要求提供可识别您身份的凭证文件及履行必要的手续等，用户没有提供其个人有效身份证件或者提供的个人有效身份证件与所注册的身份信息不一致的，飞鸟阅读有权拒绝用户上述请求。飞鸟阅读对您的请求采取行动需要合理时间，飞鸟阅读对采取行动之前用户账号已执行的指令不承担责任，并保留索取额外费用的权利。</p>\n<p>3.8 用户需自行配备注册和使用网络所需的移动终端设备及网路设备，并自行负担上网所需的各项费用，如为了向您提供有效的服务，本产品会利用您设备终端的处理器和宽带等资源。本产品使用过程中可能产生数据流量的费用，您需自行向运营商了解相关资费信息，并自行承担相应费用。</p>\n<p>3.9 用户注册本产品账号未及时进行初次登录或连续【360】天不登录或不使用的，飞鸟阅读有权回收其账号，以免造成资源浪费，可以从服务器上永久删除您的数据，也没有义务向您返还任何数据，由此带来的任何损失均由用户自行承担。</p>\n<p>3.10 在需要注销账户时，可以依次点击“我的-设置-账号（安全）设置-注销账号”，阅读注销须知后，请按要求填写手机号码后，点击“提交”，方便飞鸟阅读的客服与您联系确认，进行相应注销操作。账号注销成功后，账户内的数据将被清空且无法恢复，请您谨慎操作。</p>\n<p>4. 收费及充值<br />飞鸟阅读在本软件中提供收费和充值（包括虚拟币、会员、按天、按章购买等不同权益类型）服务，您可结合实际需求选择适合您的服务类型。</p>\n<p>4.1  收费</p>\n<p>1)您理解并同意，飞鸟阅读有权免费或要求付费提供本服务；飞鸟阅读收取的充值金额是飞鸟阅读为了向最终用户收取互联网增值服务/产品使用费，而提供的一种通用的收费渠道或计费方式，该充值金额不能用于本服务以外的任何产品或服务。您完成充值后，无论是否实际使用，均不支持退款、退还、兑换现金、抵扣其他商品或服务。若用户主张为未成年人充值的与其民事行为能力不符的金额申请退款的，需联系客服并配合提供相关证明材料，飞鸟阅读会对该用户的退款事宜进行审核。飞鸟阅读在提供服务时，可能会针对部分服务(包括但不限于收费章节阅读)向用户收取一定的费用。在此情况下，飞鸟阅读会在相关页面上做明确的提示。如用户拒绝支付该等费用，则不能使用相关的服务。</p>\n<p>2)您理解并同意，飞鸟阅读有权自行决定并修订相关的收费标准和规则，该类标准及规则一经发布即生效，并构成本协议的有效组成部分。飞鸟阅读可根据实际需要对收费服务的收费标准、方式进行修改和变更，也可对部分免费服务开始收费，可能就不同的服务制定不同的资费标准和收费方式，也可能按照飞鸟阅读所提供的服务的不同阶段确定不同的收费标准和收费方式，就同一产品和服务，以飞鸟阅读届时公布和执行的最新信息为准，飞鸟阅读会将有关服务的收费标准、收费方式、购买方式或其他有关收费标准的信息放置在该服务相关页面的显著位置，飞鸟阅读有权不另行向用户逐一通知。如果标准或收费方式等发生变化，用户没有停止使用飞鸟阅读的产品和服务，则视为用户接受调整的内容。</p>\n<p>3)飞鸟阅读在本产品和服务中的各种虚拟物品（包括但不限于飞鸟阅读币、读书币、书券、卡券等），是飞鸟阅读所提供的服务的一部分，其所有权归属飞鸟阅读，您只能在合乎法律规定和各服务规则的情况下使用。</p>\n<p>4)您知悉并同意：您向其拥有的飞鸟阅读相关服务的账号内充值兑换获取飞鸟阅读币等虚拟物品时，飞鸟阅读可能会同时赠与一定比例的虚拟物品（如书券等），您在使用及消耗虚拟物品时，如充值兑换虚拟物品与获赠的虚拟物品不可区分，充值兑换的虚拟物品将视为优先于飞鸟阅读赠予的虚拟物品被使用与消耗，如充值兑换的虚拟物品与获赠的虚拟物品可以区分，则按照实际使用及消耗量计算。</p>\n<p>5)您理解并同意，飞鸟阅读提供的是附期限的有偿阅读服务，并请您知悉并理解：如书籍作品因版权纠纷、作品解约、作者或作品自身原因（如违反法律法规、政策的规定或行业的规则、涉及侵权纠纷、违反公序良俗、出版精修等）、不可抗力或其他原因导致作品全部或部分被删除、下架、屏蔽而无法正常阅读的，飞鸟阅读不承担任何责任。无论您是否购买了作品的章节，作者有权对作品及文案等内容进行修改。</p>\n<p>6)用户在付费时使用第三方支付企业提供的服务的，应当遵守与该第三方的各项协议及其服务规则；在使用第三方支付服务过程中用户应当妥善保管个人信息，包括但不限于银行卡账号、密码、验证码等，飞鸟阅读对因第三方支付服务产生的纠纷不承担任何责任。</p>\n<p>7)若因违反本协议及飞鸟阅读公布的其他协议规则条款，导致账号被冻结及/中断、中止或终止使用的用户，不得因此要求飞鸟阅读作任何补偿或赔偿。</p>\n<p>4.2 充值</p>\n<p>1)飞鸟阅读对旗下产品不同权益服务设有不同的充值购买方式。您在相应产品内的充值行为仅视为该产品的相应权益服务的充值，飞鸟阅读各产品之间虚拟币及权益不通用，且不可置换、转让、销售、赠予及兑换现金。本平台之外的交易所获得的虚拟币将被认定为来源不符合飞鸟阅读的规则，飞鸟阅读将不对这一类交易中产生的任何问题进行支持或保障，同时将对因此造成的影响采取必要的保护措施。飞鸟阅读不提供用户间交易虚拟币的平台化服务，用户间交易虚拟币构成对本协议的违反，飞鸟阅读有权在未经通知的情况下，采取相应适当措施，以确保飞鸟阅读不提供用户虚拟币的平台化服务。</p>\n<p>2)用户理解并同意，若您通过充值获得对应虚拟币的相关服务，您的任何消耗虚拟币进行（自动）购买、兑换、订阅、下载或缓存等使用行为，即视为消费完成，飞鸟阅读对已经消费过的产品或服务不提供退款等售后服务，请您在充值时审慎选择充值金额。</p>\n<p>3)当您在飞鸟阅读旗下产品进行充值时，必须仔细确认账号及信息、充分阅读并理解本协议及相应规则后开通，若因为用户操作不当、不了解或未充分了解充值计费方式等因素造成充错账号、错选充值种类及权益类型、利用系统漏洞等非法方式或未在飞鸟阅读授权的渠道上获取或购买飞鸟阅读币的以及调整VIP等级来获取优惠服务等情形而损害自身权益，飞鸟阅读有权在不通知您的情况下对账号进行封禁处理，账号中的充值款（包括虚拟币等）不退还，由此引发的问题由您自行承担，不得因此要求飞鸟阅读作任何补偿或赔偿。</p>\n<p>4)若因飞鸟阅读充值系统自身故障造成用户充值失实，在飞鸟阅读恢复、存有有效数据和用户提供合法有效凭证的情况下，飞鸟阅读将根据用户充值情况作出变动及补救措施：若系统充值额小于用户实际充值额的，飞鸟阅读予以补其差额；若系统充值额大于用户实际充值额的，飞鸟阅读有权追回差额。</p>\n<p>5)用户理解并同意，您使用账户内的虚拟币订阅作品一旦成功，视为已消费完成，飞鸟阅读将不提供任何订单更改、修正服务。非经飞鸟阅读同意，用户订阅付费作品并已支付的虚拟币，将无法退还。用户订阅付费作品并已支付的虚拟币不会因飞鸟阅读提供的服务中止、中断或付费作品下架而退还。</p>\n<p>4.3 订阅及自动订阅</p>\n<p>1)您理解并同意，如您在作品章节订阅时选择了自动订阅服务，将视为您同意飞鸟阅读届时按照一定规则自动扣除您的虚拟币，一旦订阅成功，我们将为您开通下一章或多章的作品更新章节，但您所订阅（含批量订阅）的最新章节可能会基于网页端、手机端、H5等不同产品和服务形态设定不同的更新及展示节奏。</p>\n<p>2)您理解并同意，您授权的代扣权限将视为您本人的消费行为，如因此导致您自身的损失，您不会向飞鸟阅读主张任何权利及追究任何责任。</p>\n<p>3)本服务需要您自行取消自动订阅设置，您可以在“个人中心-设置”中进行取消操作，您在取消前自动订阅的扣款指令持续有效，飞鸟阅读基于该指令扣除的虚拟币不予退还和补偿。</p>\n<p>4.4 会员及自动续费</p>\n<p>1)飞鸟阅读在充值订阅服务之外，专门在飞鸟阅读会员APP内针对部分书籍开通了付费会员专区，同时还为您提供相应会员自动续费服务，具体会员权益内容以本产品具体页面展示的为准，相关规则见《会员及自动续费协议》。特别提醒，飞鸟阅读会员、按章节购买、按天购买仅能飞鸟阅读会员APP内享有和行使，且与飞鸟阅读虚拟币服务分属于不同权益类型的服务，不支持互相转换，与本软件其他产品终端互不通用，请您充值时注意选择适合您阅读的方式进行消费。</p>\n<p>2)在已开通的会员服务有效期内，若您要求取消或者终止会员服务的，将不会获得会员服务费用的退还。会员服务期限届满后，飞鸟阅读将停止继续向您提供会员权益服务，会员服务期限亦不会因您未登录使用本平台、未阅读作品等情况而延长，如您未在服务期限内享受服务内容或会员权益的，将视为已全部享用。</p>\n<p>三、用户个人信息保护及隐私政策</p>\n<p>1. 您在注册账号或使用本服务过程中，可能需要填写一些必要的信息。若国家法律法规有特殊规定的，您需要填写真实、完整、有效的身份信息，依据相关法律规定和本协议约定对所提供的信息承担相应的法律责任。若您填写的信息不真实、不完整或提供的资料已变更未更新，则无法使用本服务或受到限制，飞鸟阅读将不会承担任何责任。</p>\n<p>2.  飞鸟阅读将采取商业上合理的方式以保护您的个人信息的安全，将使用通常可以获得的安全技术和程序来保护您的个人信息不被未经授权的访问、使用或泄漏。对于非因飞鸟阅读过错而造成用户账号的丢失或用户个人资料的泄密，飞鸟阅读将不会承担任何责任。</p>\n<p>3. 飞鸟阅读承诺不会对外公开您的注册资料及您在使用本服务时存在飞鸟阅读的非公开内容，但如果出现下列情况将不在此承诺范围内：</p>\n<p>1)事先获得用户明确授权；</p>\n<p>2)有关法律法规或行政规章要求；</p>\n<p>3)司法或行政主管机关的要求；</p>\n<p>4)为维护飞鸟阅读和其他用户的合法权益；</p>\n<p>5)为维护社会公众的利益；</p>\n<p>6)飞鸟阅读合理怀疑有危害国家安全事情发生时，主动将相关资料供公安机关调查处理；</p>\n<p>7)飞鸟阅读可能会与第三方合作向用户提供相关的服务或功能，在此情况下，如该第三方同意承担与飞鸟阅读同等的保护用户隐私的责任，则飞鸟阅读可将用户的注册资料等提供给该第三方。</p>\n<p>4. 您理解并同意，飞鸟阅读有权在您账号信息页面展示合理范围内的互联网IP地址归属信息，以便于为公共利益实施监督。</p>\n<p>5. 用户同意，为了解用户需求和提升服务质量，飞鸟阅读有权定期或不定期以电子邮箱、短信、站内信、弹窗或其他用户提供、授权的有效方式发送有关的信息。</p>\n<p>6. 对飞鸟阅读提供服务中出现的标注第三方来源的信息，用户应当独立判断后查看或点击，飞鸟阅读对此不承担任何保证、担保、赔偿或其他相关责任。</p>\n<p>7. 在不透露单个用户个人隐私资料的前提下，为了更好地服务于用户，飞鸟阅读有权自己或和第三方合作对整个用户数据库进行技术分析并对已进行分析、整理后的用户数据库进行商业上的利用。</p>\n<p>8. 保护用户个人信息是飞鸟阅读的一项基本原则，除本协议另有规定外，更多关于您的个人资料和信息获取及使用会以《隐私政策》受到保护与规范，请在使用本产品前参阅。</p>\n<p>四、用户注意事项</p>\n<p>1. 服务变更、中断或终止</p>\n<p>1.1 飞鸟阅读可能会对服务内容进行变更，也可能会中断、中止或终止服务。您同意，飞鸟阅读有权对本协议内容进行变更并自公布之日起生效，您在相关更新条款发布后继续使用本产品及服务的，视为接受并认可相关条款的法律效力及对双方的约束力，且您认可相关条款（包括但不限于任何更新后的条款）具有溯及至您的账号首次登录本产品之日的法律效力并在该日起对双方有共同的约束力。</p>\n<p>1.2 如因飞鸟阅读、飞鸟阅读的合作方或突发性网络设备故障、失灵或人为操作的疏失、受到网络攻击等因不明原因停止、不可抗力、相关政府机构要求、基于法律或国家政策的规定及其他情况，发生未能事先通知中断或终止部分或全部服务，且造成任何损失的，飞鸟阅读无需对您或任何第三方承担责任。</p>\n<p>1.3 如因系统维护或升级的需要而需暂停服务，飞鸟阅读将尽可能事先进行通告。</p>\n<p>1.4 用户或飞鸟阅读可随时根据实际情况终止全部或部分服务。飞鸟阅读有权根据其实际情况终止向用户提供某一项或多项服务，并在相关页面进行通知，用户有权单方不经通知终止使用飞鸟阅读的本服务。</p>\n<p>1.5 结束用户服务后，用户使用本产品和服务的权利立即终止。自此，飞鸟阅读不再对用户承担任何义务，法律法规另有明确规定的除外。</p>\n<p>2. 广告</p>\n<p>2.1 您同意，飞鸟阅读有权在提供服务过程中自行或由第三方广告商向您推送广告、推广或宣传信息（包括商业与非商业信息）。</p>\n<p>2.2 飞鸟阅读向用户提供的本服务本身属于商业行为，用户需要支付相应的费用。您同意，所有对您收取费用的服务，均不能免除您接受飞鸟阅读在提供服务的过程中以各自形式投放商业性广告或其他任何类型的商业性信息，是飞鸟阅读为所有用户提供综合服务的有效对价，其方式和范围可不经向您特别通知而变更，您接受飞鸟阅读服务即视为对该规则的理解和接受，飞鸟阅读无须向您支付任何广告收益。</p>\n<p>2.3 飞鸟阅读依照法律的规定履行相关义务，您同意，对本产品中出现的广告信息，您应审慎判断其真实性和可靠性，您应对依该广告信息进行的判断和交易负责。除法律明确规定外，您因依该广告信息进行交易或前述广告商提供的内容而遭受的损失或损害，飞鸟阅读不承担责任。</p>\n<p>2.4 您同意并理解，非正当屏蔽飞鸟阅读所提供的广告将给飞鸟阅读造成极大的损失，因此您不会主动采取非正当手段屏蔽广告内容。</p>\n<p>2.5 如您希望不向您展示本产品当前页面的广告信息，你可以通过点击广告信息页面的“跳过”来停止加载相应广告。</p>\n<p>3. 关于单项服务的特殊约定</p>\n<p>3.1 本产品及相关服务包含飞鸟阅读以各种合法方式获取的信息或信息内容链接，同时也包括飞鸟阅读及其关联方、合作方合法运营的其他单项服务；</p>\n<p>3.2 您可以在本产品中开启和使用单项服务。某些单项服务可能需要您同时接受该单项服务特别指定的协议或者其他约束您与该单项服务提供者之间的规则。飞鸟阅读将在您计划使用前述单项服务时以醒目的方式向您提供这些协议、规则，供您查阅和同意。一旦您开始使用上述服务，则视为您就理解并接受有关单项服务的相关协议、规则的约束。</p>\n<p>3.3 在您使用本产品的服务时，飞鸟阅读可能会随附金币奖励、现金奖励、红包等除阅读之外的福利，但该等福利均为随号赠送福利。如飞鸟阅读根据技术排查，发现任何滥用金币奖励、现金奖励、红包等福利的作弊或通过恶意的不正当手段获取前述福利的行为，或对数据产生重大质疑，均可以判定您本次参加活动的行为无效，取消该账号所有福利，甚至封禁该账号（包括但不限于封停该用户名下全部或部分账号和禁用账号相关功能等）。</p>\n<p>4. 第三方产品和服务</p>\n<p>4.1 您在使用由第三方提供的产品或服务时，除遵守本协议约定外，还应遵守第三方的用户协议及相关政策规则。飞鸟阅读和第三方对可能出现的纠纷在法律规定和约定的范围各自承担责任。</p>\n<p>4.2 您使用本产品或要求飞鸟阅读提供特定服务时，本产品可能会调用第三方系统或者通过第三方支持您的使用或访问，使用或访问的结果由该第三方提供，飞鸟阅读不保证第三方提供服务及内容的安全性、准确性、有效性及其他不确定的风险，由此引发的任何争议及损害，与飞鸟阅读无关，飞鸟阅读不承担任何责任。</p>\n<p>5. 服务风险</p>\n<p>5.1 您理解并同意飞鸟阅读会尽其商业上的合理努力保障您在本产品中的数据存储安全，但是，飞鸟阅读并不能就此提供完全保证，您在使用本产品时，需要自行承担如下飞鸟阅读不可掌控的风险内容，飞鸟阅读对以下事宜不作任何类型的担保，由此产生的一切法律责任与纠纷一概与飞鸟阅读无关，均由您自行承担，不论是明确的或隐含的：</p>\n<p>5.1.1 飞鸟阅读不对您在软件中相关数据的删除或存储失败负责；</p>\n<p>5.1.2 因第三方如技术问题、网络、移动终端维护、故障、系统不稳定性及其他各种不可抗力、意外事件，以及战争、政府行为，司法行政机关的命令或因第三方因素可能引起的个人信息丢失、泄露等遭受的一切损失，飞鸟阅读及合作单位不承担责任。因技术故障等不可抗力事件影响到服务的正常运行的，飞鸟阅读及合作单位承诺在第一时间内与相关单位配合，及时处理进行修复。</p>\n<p>5.1.3 本产品与终端设备不相匹配所导致的任何产品问题、终端设备问题或损害。</p>\n<p>5.1.4 用户确认，使用本产品涉及到Internet服务，可能会受到各个环节不稳定因素的影响。因此服务存在因不可抗力、移动终端病毒或黑客攻击、系统不稳定、用户所在位置、用户关机以及其他任何技术、互联网络、通信线路原因等造成的服务中断、登录失败、资料同步不完整、页面打开速度慢、不能发送和接受阅读信息、或接发错信息或其他不能满足用户要求的风险。</p>\n<p>5.1.5 用户确认，在使用本产品存在有来自任何他人的包括威胁性的、诽谤性的、令人反感的或非法的内容或行为或对他人权利的侵犯（包括知识产权）的匿名或冒名的信息的风险，包括所有有关信息真实性、适用性、适于某一特定用途、所有权和非侵权性的默示担保和条件，对因此导致任何因用户不正当或非法使用服务产生的直接、间接、偶然、特殊及后续的损害，不承担任何责任。</p>\n<p>5.1.6 在适用法律允许的最大范围内，飞鸟阅读并不担保飞鸟阅读所提供的服务一定能满足用户的要求，也不担保提供的服务不会被中断。维护本产品安全与正常使用是飞鸟阅读和您的共同责任，飞鸟阅读将按照行业标准合理审慎地采取必要技术措施保护您的终端信息和数据安全，但是您承认和同意飞鸟阅读不能就此提供任何保证，并且对服务的及时性，安全性，无错误，以及信息是否能准确、及时、顺利的传送均不作任何担保。</p>\n<p>5.1.7 通过本产品的链接或标签所执行的第三方的商业信誉及其提供服务的质量及安全问题等，均由用户自行承担。</p>\n<p>五、用户行为规范</p>\n<p>1. 信息内容规范</p>\n<p>1.1 本条所述信息内容规范是指您在使用产品和服务过程中所制作、复制、发布、传播的任何内容，包括但不限于账号头像、昵称等用户基本信息，以及其他使用本产品登录账号或本产品及服务所产生的内容，由您单独承担发布内容的责任。</p>\n<p>1.2 您理解并同意，您不得利用本产品制作、复制、发布、传播如下干扰本服务正常运营，以及侵犯作者、其他用户或第三方合法权益的内容，包括但不限于：</p>\n<p>1.2.1 发布、传送、传播、储存违反国家法律、危害国家安全、祖国统一、社会稳定、公序良俗的内容，包括：</p>\n<p>1)反对宪法所确定的基本原则的内容；</p>\n<p>2)危害国家安全，泄露国家秘密，颠覆国家政权，破坏国家统一的内容；</p>\n<p>3)损害国家荣誉和利益的内容；</p>\n<p>4)煽动民族仇恨、民族歧视，破坏民族团结的内容；</p>\n<p>5)破坏国家宗教政策，宣扬邪教和封建迷信的内容；</p>\n<p>6)散布谣言，扰乱社会秩序，破坏社会稳定的内容；</p>\n<p>7)散布淫秽、色情、赌博、暴力、凶杀、恐怖或者教唆犯罪的内容；</p>\n<p>8)传播侮辱或者诽谤他人，侵害他人合法权益的内容；</p>\n<p>9)煽动非法集会、结社、游行、示威、聚众扰乱社会秩序的；</p>\n<p>10)以非法民间组织名义活动的；</p>\n<p>11)含有法律、行政法规禁止的其他内容；</p>\n<p>1.2.2 发布、传送、传播、储存侵害他人名誉权、肖像权、知识产权、商业秘密权等合法权利的内容；</p>\n<p>1.2.3 未经他人许可发布、传送、传播涉及他人隐私、个人信息或者资料的内容；</p>\n<p>1.2.4 发布、传送、传播骚扰信息、广告信息及垃圾信息或含有任何性或性暗示的信息；</p>\n<p>1.2.5 其他违反法律法规、政策及公序良俗、社会公德或干扰软件正常运营和侵犯其他用户或第三方合法权益的内容。</p>\n<p>用户理解，如果飞鸟阅读发现用户通过本产品所发布、传送、传播、储存的信息明显属于上段所列内容之一，飞鸟阅读有权根据上述信息的严重性采取必要的处理措施，包括但不限于立即停止传输，保存有关记录，向国家有关机关报告，并且屏蔽或删除含有该内容的地址、目录或关闭服务器等。</p>\n<p>1.3.您理解并同意，您参与互动发布的内容（如评论、书评、小故事）、自定义的头像需经过飞鸟阅读审核。</p>\n<p>1.4.就飞鸟阅读及合作伙伴的服务、产品、业务咨询应采取相应机构提供的沟通渠道，不得擅自在公众场合发布有关飞鸟阅读及相关服务的负面宣传。</p>\n<p>2. 软件使用规范</p>\n<p>2.1 用户在遵守法律法规及本协议的前提下可依本协议使用本产品及服务，不得实施包括但不限于如下行为：</p>\n<p>1)删除本产品及其他副本上一切关于著作权的信息；</p>\n<p>2)对本产品进行反向工程，如反汇编、反编译、修改网站网页或软件所使用的任何专有通讯协议、对动态随机存取内存中资料进行修改或锁定等任何技术性的与合理使用无关的行为或者其他探查、扫描、测试等等方式试图发现本产品的源代码、弱点或其他影响软件安全的行为；</p>\n<p>3)修改、破坏、遮盖本产品或相关产品程序、图像、动画、包装和手册等内容上的产品名称、公司标志、版权信息等；</p>\n<p>4)对本产品或相关产品的程序、图像、动画和音乐进行还原、剪辑、翻译和改编等任何修改行为；</p>\n<p>5)将本产品及服务以及与产品和服务相关的内容进行商业及非商业化使用；</p>\n<p>6)通过非飞鸟阅读开发、授权或认可的第三方兼容软件、系统、加速软件及其它各种作弊程序登录或使用本产品及服务，针对飞鸟阅读的产品和服务使用、组织、教唆他人使用非飞鸟阅读开发、授权或认证的插件和外挂，或销售此类软件程序；</p>\n<p>7)对飞鸟阅读拥有知识产权的内容进行监视、使用、出租、出借、复制、修改、链接、转载、汇编、发表、出版，建立镜像站点等；</p>\n<p>8)公开展示和播放本产品的全部或部分内容；</p>\n<p>9)对本产品或本产品运行过程中释放到任何无线手持终端内存中的数据及该软件运行过程中客户端与服务器端的交互数据，以及本产品允许所必需的系统数据，进行复制、修改、增加、删除、更换、挂接运行或创作任何衍生作品，形式包括但不限于使用插件、外挂或非经授权的第三方工具/服务接入本产品和相关系统；</p>\n<p>10)通过修改或伪造软件作品运行中的指令、数据、数据包，增加、删减、变动软件的功能或运行效果，或者将用于上述用途的软件、方法进行运营或向公众传播，无论这些行为是否为商业目的；</p>\n<p>11)通过非飞鸟阅读开发、授权的第三方软件、插件、外挂、系统，登录或使用飞鸟阅读产品及服务，或制作、发布、传播上述工具；</p>\n<p>12)自行或者授权、组织、教唆他人、授权第三方软件借助本产品发展与之有关的衍生产品、作品、服务、插件、外挂、兼容、互联等，对本产品及其组件、模块、数据进行干扰；</p>\n<p>13)制作、发布、使用、传播用于窃取本产品账号及他人个人信息、财产的恶意程序；</p>\n<p>14)制作、发布、使用、传播包含病毒、木马程序、定时炸弹等可能对飞鸟阅读或任何人的计算机系统造成伤害或影响其稳定性的内容；</p>\n<p>15)可能影响、破坏飞鸟阅读所提供的产品和服务的行为，包括但不限于可能损害、攻击、使服务器过度负荷或其他非正常使用行为；</p>\n<p>16)可能对互联网的正常运转造成不利影响或可能干扰他人以正常方式使用飞鸟阅读所提供的本产品的方式使用飞鸟阅读提供的产品和服务的行为；</p>\n<p>17)使用异常的方法登录账号（包括但不限于使用非飞鸟阅读开发、授权或认可的第三方软件、系统、模拟器等登录用户账号）、使用作废信用卡、盗窃他人信用卡等手段（俗称“黑卡”）进行充值、扰乱正常产品和服务秩序的行为；</p>\n<p>18)以任何不合法的方式、为任何不合法的目的、或以任何与本协议不一致的方式使用本产品和服务的行为。</p>\n<p>3. 服务使用规范</p>\n<p>3.1 您在使用本服务过程中，不得从事下列行为：</p>\n<p>1)采取截屏、内容分享图、拍屏、ORC识别等方式将服务内容进行复制、发行、表演、传播、存储到未经飞鸟阅读事先书面同意的任何渠道；</p>\n<p>2)通过技术手段或其他不正当手段抓取、破解飞鸟阅读接口，以获取本服务的内容，以及通过修改程序代码，使用模拟器等方式获取本服务相关的内容；</p>\n<p>3)实施影响飞鸟阅读经营的行为，包括自行或组织多人进行盗文、投票、刷单、刷分、多人用一个账号发布作品等；</p>\n<p>4)提交、发布虚假信息，或冒充、利用他人名义的；</p>\n<p>5)诱导其他用户点击链接页面或分享信息的；</p>\n<p>6)虚构事实、隐瞒真相以误导、欺骗他人的；</p>\n<p>7)侵害他人名誉权、肖像权、知识产权、商业秘密等合法权利及其他权益的；</p>\n<p>8)未经飞鸟阅读书面许可利用账号和本服务，以及第三方运营平台进行推广或互相推广的；</p>\n<p>9)企图牟取不正当利益或从事任何不正当竞争行为；</p>\n<p>10)利用本产品及服务从事任何违法犯罪活动的；</p>\n<p>11)制作、发布与以上行为相关的方法、工具，或对此类方法、工具进行运营或传播，无论这些行为是否为商业目的；</p>\n<p>12)使用飞鸟阅读任何知识产权，来创造或提供相同或类似的产品和服务；</p>\n<p>13)其他违反法律法规规定、侵犯飞鸟阅读和其他用户合法权益、干扰本产品正常运营或飞鸟阅读未明示授权的行为。</p>\n<p>14)您应销毁您已经获得的上述所有内容，并由您自行处理并承担由此导致的纠纷及法律责任，且飞鸟阅读不承担任何责任。</p>\n<p>4. 对自己行为负责</p>\n<p>4.1 如果您违反本条约定，或者违反法律法规及行政规定、违反本协议其他条款、隐私政策以及与本产品相关的规则、规范、声明、公告、通知等内容或根据有权机关的要求，飞鸟阅读有权独立判断并视情况暂时或永久性采取一项或措施：</p>\n<p>1)警示警告，要求您限期内进行整改，并有权公告处理结果；</p>\n<p>2)中止或终止您全部或部分账号，包括但不限于限制或禁止、冻结进行登陆、注册、重新注册、使用您名下全部或部分账号，以及该用户所使用的终端设备及后续该终端设备中的全部或部分账号；</p>\n<p>3)中止或终止全部或部分服务，包括但不限于限制或禁止、冻结进行充值、购买、浏览、下载、评论、投票、分享、收听等使用作品章节（包括已下载/已购买章节、最新章节及所有章节），以及屏蔽、删除、停止更新相关内容和作品章节；</p>\n<p>4)拒绝发布，立即停止传输信息；</p>\n<p>5)扣除并要求您返还您的非法获益（包括但不限于实物赠品、虚拟物品、相关功能或服务以及相关优惠折扣），且无需退还您的充值及会员金额，该金额视为您支付飞鸟阅读的违约金，如违约金不足以弥补因此给飞鸟阅读造成的损失，飞鸟阅读可通过法律途径向您追索。</p>\n<p>6)要求您承担损害赔偿责任，包括但不限于向受损失者进行赔偿以及飞鸟阅读首先承担了因用户行为导致行政处罚或侵权损害赔偿责任所遭受的损失。</p>\n<p>4.2您应主动销毁基于您的违约、侵权以及其他不正当手段所获得的所有内容，并由您自行处理并承担由此导致的纠纷及法律责任，飞鸟阅读不承担任何责任。</p>\n<p>4.3.飞鸟阅读有权公告处理结果，且有权根据实际情况决定是否恢复使用。对涉嫌违反法律法规、涉嫌违法犯罪及侵犯飞鸟阅读及第三方合法权益的行为将保存有关记录，并依法向有关主管部门报告、配合有关主管部门调查。如您申请恢复服务、解除冻结，您应按照飞鸟阅读要求如实提供您的身份证明以及飞鸟阅读要求的其他信息或文件，以便核实，且飞鸟阅读有权依照自行判断决定是否同意您的申请。您应当充分理解您的申请并不必然被允许。</p>\n<p>4.4.因飞鸟阅读采取前述措施由此带来的包括并不限于用户通信中断、用户资料、邮件和阅读记录、已购买章节等数据丢失、被屏蔽、无法阅读等损失由用户自行承担。</p>\n<p>六、产品管理规范</p>\n<p>1. 您理解并同意，使用本产品必须遵守国家有关法律和政策等，维护国家利益，保护国家安全，并遵守本协议。因您违反本协议，导致或发生第三方主张的任何索赔、要求或损失，您应当独立承担责任并应使飞鸟阅读免责，否则您应赔偿飞鸟阅读遭受的全部损失。</p>\n<p>2. 飞鸟阅读有权根据国家相关法规政策，按照本协议的规定，对您发布、传送、传播、存储的内容进行审查，以审核您使用本服务的行为不违反法律法规及社会公共道德准则、不侵犯飞鸟阅读或第三方的合法权益、不带有诋毁飞鸟阅读及本产品形象的要素，但并不因飞鸟阅读的审核而减轻您自身使用服务时应承担的责任，由此产生的一切责任和后果仍由您自行承担。</p>\n<p>3. 您了解并且同意只要您使用本产品及服务，您将遵守本协议中的权利义务。您应对本产品中的内容自行加以判断，并承担因使用内容而引起的所有风险，包括因对内容的正确性、完整性或实用性的依赖而产生的风险。您对所有在您的注册名下发生的一切行为负责；您了解并且同意飞鸟阅读不能为用户行为负责。使用本产品及服务产生的所有风险由您个人承担，飞鸟阅读因此受有损失的，您也应当一并赔偿。如果您发现本产品及服务中存在任何违法违规行为，有义务及时向飞鸟阅读举报。</p>\n<p>4. 飞鸟阅读仅对本协议中列明的责任及范围负责。因政府行为、或法律法规或国家相关主管部门新颁布、变更的法令、政策导致飞鸟阅读不能提供本服务的，不视为飞鸟阅读违约。飞鸟阅读有权基于版权及内容情况的变化对作品进行删除、下架、屏蔽、断开链接等处理，亦不视为飞鸟阅读违约。</p>\n<p>5. 如发现任何非法使用或异常使用用户账号或账号出现安全漏洞的情况，用户应立即通告飞鸟阅读。用户应配合飞鸟阅读的工作，据实回答飞鸟阅读客服人员提出的与使用飞鸟阅读本服务相关的问题，以维护自己和其他用户的合法权益。用户同意以飞鸟阅读系统的监测数据作为判断用户账号是否盗号、是否有通过使用黑卡充值、黑卡注册等方法进行的作弊行为以及其他违规行为的依据，除非用户能够提供充分、合法的证据证明其账号使用权、账号内虚拟物品等的合法来源等来推翻监测数据。</p>\n<p>6. 在适用法律允许的最大范围内，飞鸟阅读不就因用户使用飞鸟阅读的本产品和服务引起的，或在任何方面与飞鸟阅读的本服务有关的任何意外的、非直接的、特殊的、或间接的损害或请求（包括但不限于因人身伤害、因隐私泄漏、因未能履行包括诚信或合理谨慎在内的任何责任、因过失和因任何其他金钱上的损失或其他损失而造成的损害赔偿）承担任何责任。</p>\n<p>七、知识产权和所有权声明</p>\n<p>1. 飞鸟阅读所提供的产品及服务以及与本产品及服务相关的所有内容（包括但不限于信息、资料、文字、软件、声音、图片、视频、文字表述及其组合、图标、图饰、图表、色彩、界面设计、版面框架、有关数据、印刷材料或电子文档等）均受中华人民共和国相关法律法规及中华人民共和国加入的国际条约以及其他知识产权法律法规的保护，除相关权利人依照法律规定应享有的权利外，飞鸟阅读享有上述全部著作权、商标权、专利权、商业秘密等知识产权、所有权及其他权利。</p>\n<p>2. 未经飞鸟阅读或相关权利人事先书面同意，您不得为任何营利性或非营利性的目的自行实施、利用、转让或许可任何第三方实施、利用、转让上述所有内容，您不得对任何内容进行修改、拷贝、改编、散布、传送、展示、执行、复制、发行、授权、制作衍生著作、转移或销售或二次开发等行为。如您未遵守本条款的上述规定，将视为您严重违约，在法律允许的最大范围内且不损害其他相关方权利的情况下，飞鸟阅读可立即终止向您提供本产品和服务，解除双方间的服务协议和法律关系并有权采取相应措施来追究您的责任。</p>\n<p>3. 飞鸟阅读对您在使用产品及服务过程中所产生的任何数据信息（包括但不限于账号资料、充值数据、消费数据、阅读偏好等阅读行为相关数据等）拥有所有权。您通过其合法账号及密码正常登录本产品后，本产品向您显示的数据和信息为您就本产品和服务可获取的全部数据和信息。飞鸟阅读无义务向您提供、展示超出法律规定及您自行登录使用本产品及便可获取的数据和信息以外的数据和信息。</p>\n<p>4. 在飞鸟阅读提供的所有服务器上的数据（包括但不限于虚拟物品等）全部归飞鸟阅读所有。在法律所允许的最大限度内，飞鸟阅读有权决定保留或不保留服务器上的全部或部分数据。</p>\n<p>5. 您理解并同意，您使用本产品及相关服务时上传、发布、展示的内容（包括但不限于评论、小故事、图片、文章、下同）的知识产权归属您或原始著作权人所有。</p>\n<p>6. 飞鸟阅读提供信息存储空间，供您进行内容上传、发布、展示等活动。您应保证上传、发布、展示的内容均属原创或已获得合法授权，飞鸟阅读尊重并保护您及他人的知识产权等合法权益。否则，飞鸟阅读有权在收到相关权利方或者相关通知方的情况下移除该涉嫌侵权的内容，针对第三方提出的全部权利主张，您应自行处理并承担全部可能由此引起的法律责任，如因您的侵权行为导致飞鸟阅读及飞鸟阅读关联公司、合作方遭受损失的（包括但经济、商誉等损失），飞鸟阅读有权按照法律规定索赔以维护合法权益。</p>\n<p>7. 您理解并同意，您将通过本产品上传、发布、展示的内容的全部著作财产授权以及邻接权给飞鸟阅读，授权范围包括但不限于复制权、信息网络传播权、发行权、改编权、修改权、汇编权、翻译权、表演权、摄制权、广播权、制作衍生品和展示等权利，且上述权利授权为永久的、免费的、不可撤销的、全球范围内的、可转授权的。您在此确认并同意，飞鸟阅读对您上传、发布的内容可用于飞鸟阅读、飞鸟阅读书籍或飞鸟阅读品牌有关的任何宣传、广告、营销活动中，且该等使用包括但不限于在飞鸟阅读或其他网站、应用程序、产品或终端设备上。为避免疑惑，您同意上述权利的授权包括许可、使用、复制、发行、转播、传播、出租、展示您在飞鸟阅读上传、发布的内容中植入或附带的其他物料、素材。</p>\n<p>8. 您确认并同意授权飞鸟阅读以飞鸟阅读的名义或委托其他第三方对侵犯您上传、发布内容的行为进行代维权，飞鸟阅读有权自行对维权事宜做出觉得并独立实施维权行为。</p>\n<p>9. 本协议未明示授权的其他一切权利仍由飞鸟阅读所保留，您在行使这些权利时须另外取得飞鸟阅读的书面许可。飞鸟阅读如果未行使前述任何权利或是没有对您的过失或违约行为采取任何行动，并不构成对该项权利的放弃。</p>\n<p>八、遵守当地法律监管</p>\n<p>1. 您在使用本产品及服务过程中应当遵守当地相关的法律法规，并尊重当地的道德和风俗习惯。如果您的行为违反了当地法律法规或道德风俗，您应当为此独立承担责任。</p>\n<p>2. 您应避免因使用本产品服务而使本产品卷入政治和公共事件，否则本产品有权暂停或终止对您的服务并追究您的违约责任。</p>\n<p>九、终端安全责任</p>\n<p>1. 在任何情况下，您不应轻信借款、索要密码或其他涉及财产的网络信息。涉及财产操作的，请一定先核实对方身份，并请经常留意飞鸟阅读有关防范诈骗犯罪的提示。</p>\n<p>2. 本产品设有网络信息安全投诉、举报制度，您可通过帮助反馈页面或联系在线客服的方式进行网络信息安全的投诉与举报，飞鸟阅读会及时受理并处理相关投诉与举报。</p>\n<p>3. 飞鸟阅读在服务器数据出现异常（包括程序Bug导致的数据异常）时，有权将该服务器的数据还原到一定时点，对因此可能导致的用户账户内虚拟物品等发生减损的情形，飞鸟阅读可根据损失情况对用户提供一定的虚拟物品补偿，除此之外，飞鸟阅读不承担其他任何责任。用户认同此种数据还原方式和补偿方式，并同意不会就此向飞鸟阅读提出争议。</p>\n<p>十、未成年人保护</p>\n<p>1. 飞鸟阅读非常重视对未成年人个人信息的保护。若您是18周岁以下的未成年人，在使用本服务前，应事先取得您监护人的同意。如您是18周岁以下未成年人的监护人，请务必仔细阅读、充分理解本协议，并请您注意指导未成年人正确使用网络，告知未成年人上网注意事项。</p>\n<p>2. 飞鸟阅读非常重视未成年人的身心健康，未成年人缺乏自我保护能力，因此请在使用本服务时要注意辨别网络世界和真实世界的区别，避免沉迷于网络，影响日常的学习生活，请在填写个人资料或是与其他用户交流时，要提高安全意识，注意保护个人信息，在监护人的指导下，学习正确使用网络。监护人应指导并监督未成年人注册、使用和注销飞鸟阅读服务的行为，监护人应妥善保管您的设备、支付账户、支付密码等，应指导未成年人学习正确使用网络。</p>\n<p>3. 为呵护青少年健康成长，飞鸟阅读特别推出了青少年模式，在该模式下，我们精选了一些人文社科、经典名著等优质内容呈现在首页，以供青少年模式下的用户进行阅读。在青少年模式中，我们针对核心场景进行了优化，无法进行充值捧场等操作。开启青少年模式，需要先设置密码，用户开启和关闭青少年模式，请您注意妥善保管。</p>\n<p>十一、其他</p>\n<p>1. 本协议是飞鸟阅读与用户之间的法律关系的重要文件，如用户的任何书面或者口头意思表示与本协议不一致的，均应当与本协议为准，除非本协议被飞鸟阅读声明作废或者被新版本代替。</p>\n<p>2. 如本协议中的任何条款无论因何种原因完全或部分无效或不具有执行力，本协议的其余条款仍应有效并且对协议双方有约束力。</p>\n<p>3. 本协议的标题仅为方便阅读所设，并不影响本协议中任何规定的含义或解释。</p>\n<p>4. 本协议签订地为中华人民共和国北京市朝阳区。</p>\n<p>5. 本协议的成立、生效、履行、解释及纠纷解决，适用中华人民共和国大陆地区法律（不包括冲突法）。</p>\n<p>6. 若您和飞鸟阅读之间发生任何纠纷或争议，首先应友好协商解决；协商不成的，您同意将纠纷或争议提交至本协议签订地有管辖权的人民法院管辖。</p>\n<p>7. 飞鸟阅读可能通过如下一种或多种方式向您发出通知：网页公告、电子邮件、站内信、手机短信、客户端消息推送、服务窗通知或常规的信件传送等；通知于发送之日视为已送达收件人。飞鸟阅读可以信赖您所提供的联系信息是完整、准确且当前有效的。</p>\n<p>8. 基于业务发展等原因，飞鸟阅读或许需要将本协议项下权利义务转让给第三方，届时飞鸟阅读将按照本条第7款规定的方式通知您。</p>\n<p>9. 如果您对飞鸟阅读或本协议条款存有任何疑问，可以通过飞鸟阅读客服联系我们，联系电话：【400-888-8888】。</p>\n<p>10. 飞鸟阅读保留对本协议条款、产品和服务以及飞鸟阅读所提供的产品和服务的相关内容的最终解释权。</p>', '1', '2', '0', 'agreement', 'default', '1', '1729591785', '0', '0');
INSERT INTO `fn_pages` VALUES ('4', '隐私政策', '0', '', '', '<p>飞鸟阅读隐私政策</p>\n<p>更新发布日期：2023年7月25日<br />协议生效时间：2023年7月25日<br />导言</p>\n<p>欢迎您使用飞鸟阅读及服务！<br />为使用北京飞鸟阅读网络技术有限公司（以下简称“飞鸟阅读”或“我们”，注册地址：北京市朝阳区）提供的飞鸟阅读网站及服务（以下简称“本网站”、“本平台”或“本服务”），您应当阅读并遵守《飞鸟阅读隐私政策》（以下简称“本政策”或“政策”）。<br />请您务必审慎阅读、充分理解各条款内容，特别是免除或限制责任的相应条款，以及开通或使用某项服务的单独政策，并选择接受或不接受。限制、免责条款已采取加粗形式提示您注意。我们将在明确获得您同意和接受后，为您提供相应的服务。<br />如对本政策有任何疑问及反馈，请与本政策公布的联系方式客服联系。<br />为了给您提供更好的服务，我们会随网站的更新情况及法律法规的相关要求更新本政策的条款，该更新构成本政策的一部分。如该更新造成您在本政策下权利的实质减少或重大变更，我们将向您发送推送消息或以其他方式通知您，若您继续使用我们的服务，即表示您充分阅读、理解并同意受经修订的政策的约束。您有权不接受更新后的政策，但可能会导致您需要停止使用飞鸟阅读提供的某项服务。<br />如您有任何违反本政策的行为时，飞鸟阅读有权依照违反情况，随时单方限制、中止或终止向您提供本服务，并追究您的相关责任。</p>\n<p>一、定义及范围</p>\n<p>1.飞鸟阅读：是指飞鸟阅读向用户提供的具备在线阅读、书籍推荐、发表作品、作家交流等功能的网站。<br />2.用户：是指启用、浏览或上传数据至飞鸟阅读的用户，包括作家和读者，在本政策中更多地称为“您”。<br />3.本政策是您与本平台之间关于您在使用本平台中涉及用户个人信息保护相关事宜所订立的协议。<br />4.本政策内容同时包括飞鸟阅读可能不时发布的关于用户个人信息保护的相关政策、声明、业务规则及公告指引等内容（以下统称为“专项规则”）。上述内容一经正式发布，即为本政策不可分割的组成部分，您同样应当遵守。<br />5.<br />飞鸟阅读基于不同的终端类型，开发了不同的版本，具体服务和功能会有所不同，具体以您使用的版本为准。</p>\n<p>二、我们收集个人信息</p>\n<p>我们致力于保护您的隐私并遵守相关的法律法规。在您使用本网站及相关服务时，我们会收集您的信息以便提供更好的用户体验和服务。以下是我们收集的信息类型和用途：<br />1.个人信息<br />1.1 读者注册登录：当您注册登录时，我们会获取您提供的手机号码。如果您在我们的其他应用程序上使用相同的手机号码注册过账号，您可以直接登录。<br />1.2 作家注册登录：当您注册成为作家时，我们会获取您提供的手机号码、姓名、身份证号、QQ号。<br />1.3 作家个人资料：您可以自行编辑作家基本信息，包含头像、笔名、性别、QQ号码、电子邮箱、手机号码。如果您是未签约作家且未创建或长期不更新作品，我们会将您的笔名变更为“新作者+数字”，您可以在“作家专区-个人信息”中修改被重置笔名。<br />1.4 评论管理：作家可在作家专区管理读者对作品发表的评论记录，对评论加精以及回复评论。<br />1.5 作家交流区：当作家填写完整的个人资料，完成实名认证后，可以进入作家交流区发帖、回帖，如您拒绝实名认证将无法在作家交流区发帖、回帖，但仍然可以进入作家交流区查看帖子。<br />1.6 属地信息展示：我们将获取您的IP地址并在您发送的互动内容下展示IP地址归属地信息，以满足监管部门对互联网账号管理的有关要求。<br />1.7 云书架：您可以手动将书籍加入云书架，我们会记录您的书籍收藏信息。<br />1.8 阅读历史：我们会记录您在本平台阅读书籍的历史记录（最多显示最近阅读的前100本书）。<br />1.9 消息通知：读者可在个人中心的消息通知版块查看本平台发布的消息，包括系统消息、私人消息、查看回复、查看点赞等与您相关的消息。作家可在作家专区的消息通知版块查看本平台发布的消息，包括签约通知、收入通知、作家交流区通知及评论通知等与您相关的消息。<br />1.10账号安全：为保障账号安全，您可以自主更换绑定的手机号码、修改登录密码。<br />1.11作家签约：若您的作品列表出现签约按钮，代表您的作品可以与本平台签约，您可以点击按钮进行签约，签约前您需要完成实名认证。<br />2.交互行为信息<br />2.1 日志保存功能：我们使用日志保存功能收集用户的交互行为，以便改善本网站和相应服务。<br />2.2 Cookie和网络Beacon等技术：我们或我们的第三方合作伙伴使用Cookie、网络Beacon或其他技术收集您的信息，以保障本网站与服务的安全、高效运转，帮助您省去重复填写表单、输入搜索内容的步骤和流程，为您提供更便捷的网站和服务。这些信息主要用于以下用途：<br />（1）记住您的身份，以便我们辨认您作为我们的注册用户的身份。<br />（2）使用网络Beacon可以帮助我们计算浏览网络页面的用户或访问某些Cookie，我们会通过网络Beacon收集您浏览页面活动的信息，例如您访问的页面地址、您先前访问的援引页面的位址、您停留在页面的时间、您的浏览环境以及显示设定。<br />（3）分析您使用本网站与服务的方式和习惯，以改善和优化本网站与服务。<br />（4）为您提供定制化的广告和推荐内容，以提高广告的有效性和个性化体验。<br />（5）分析和统计我们的用户数量、访问量、使用率和其他有关本网站与服务的数据，以帮助我们改善和优化本网站与服务。<br />2.3 第三方服务：我们可能会与第三方服务合作，这些服务可能会使用Cookie和其他技术收集您的信息，以提供更好的服务和功能。例如，我们可能会使用Google Analytics来分析我们的网站流量和用户行为，以改善本网站与服务。这些第三方服务提供商有自己的隐私政策和数据处理方式，我们建议您查看它们的隐私政策以了解更多信息。</p>\n<p>三、我们如何使用个人信息</p>\n<p>因收集您的信息是出于遵守国家法律法规的规定以及向您提供本网站与相关服务之目的，为了实现这一目的，我们会将您的信息用于下列用途：<br />1.向您提供本网站与相关服务；<br />2.改善/优化本网站与相关服务；<br />3.在我们提供服务时，用于身份验证、客户服务、安全防范、诈骗监测、存档和备份用途，确保我们向您提供的本网站与相关服务的安全性；<br />4.为使您知晓自己使用我们服务的情况或了解我们的相关服务，向您发送服务状态的通知、营销活动通知、商业性电子信息或广告、服务提醒及相关信息；<br />5.使我们更加了解您如何接入和使用我们的服务，例如设置作品分类、格式/字体设定相关辅助阅读功能；<br />6.软件认证或管理软件升级；<br />7.预防或禁止非法的活动；<br />8.经您许可的其他用途。</p>\n<p>四、我们如何共享、转让、公开披露个人信息</p>\n<p>1.信息共享<br />我们非常重视对您的个人信息的保护，您的个人信息是我们为您提供本网站与服务的重要依据和组成部分，对于您的个人信息，我们仅在本政策所述目的和范围内或根据法律法规的要求收集和使用，并严格保密，我们不会与飞鸟阅读关联方以外的第三方公司、组织和个人共享您的个人信息，除非存在以下一种或多种情形：<br />（1）应您或其监护人合法要求而提供;<br />（2）获得您的同意或授权（包括用户本人或用户监护人授权、以及您与第三方之间达成的同意或授权）;<br />（3）飞鸟阅读及其关联公司内部的必要、合理共享。您知悉并同意，飞鸟阅读注册账号信息、相关阅读数据中不包括您个人敏感信息的部分个人信息可能会与我们的关联公司、合作伙伴及第三方服务供应商、承包商及代理分享共享。我们会努力确保关联公司采取不低于本政策所承诺的安全保障措施以及遵守我们提出的其他安全保护要求来保障您的个人信息安全;<br />（4）通过技术手段对您的个人信息进行去标识化处理，我们有权对整个去标识化的信息用户数据库进行分析及商业利用；<br />（5）为了保护您、飞鸟阅读及我们其他用户合法权益、社会公共利益、财产或安全（例如：欺诈或信用风险、抄袭盗版侵权等）、社会公共利益免遭损害而与第三方进行共享；<br />（6）某些情况下，只有共享您的个人信息，才能实现本网站与/或服务的核心功能或提供您需要的服务，或处理您与他人的纠纷或争议；<br />（7）依据您与我们签署的相关协议（例如：在线协议、平台规则等）或法律文件而共享的；<br />（8）符合您与其他第三方之间的有关约定的；<br />（9）基于合理商业习惯而共享的，例如：与第三方共享联合营销活动中的中奖/获胜者等信息，以便其能及时向您发放奖品/礼品等；我们接受尽调时等；<br />（10）有权机关的要求、诉讼争议需要、法律法规等规定的其他需要共享的情形。<br />2.信息转让<br />转让是指将个人信息控制权向其他公司、组织或个人转移的过程。原则上我们不会将您的个人信息转让，但以下情况除外：<br />（1）应您或其监护人合法要求而提供；<br />（2）获得您的同意或授权（包括用户本人或用户监护人授权、以及您与第三方之间达成的同意或授权）；<br />（3）如我们进行兼并、收购、重组、分立、破产、资产转让或类似的交易，而您的个人信息有可能作为此类交易的一部分而被转移，我们会要求新持有人继续遵守和履行本政策的全部内容（包括使用目的、使用规则、安全保护措施等），否则我们将要求其重新获取您的明示授权同意；<br />（4）法律法规规定的其他情形。<br />如具备上述事由确需转让的，我们会在转让前向您告知转让信息的目的、类型（如涉及您的个人敏感信息的，我们还会向您告知涉及的个人敏感信息的内容），并在征得您的授权同意后再转让，但法律法规另有规定的或本政策另有约定的除外。<br />3.公开披露<br />3.1公开披露是指向社会或不特定人群发布信息的行为。除了因需要对违规账号、欺诈行为等进行处罚公告、公布中奖/获胜者等名单时脱敏展示相关信息等必要事宜而进行的必要披露外，我们不会对您的个人信息进行公开披露，如具备合理事由确需公开披露的，我们会在公开披露前向您告知公开披露的信息类型和披露目的（如涉及您的个人敏感信息的，我们还会向您告知涉及的个人敏感信息的内容），并在征得您的授权同意后再公开披露，但法律法规另有规定的或本政策另有约定的除外。<br />3.2对于公开披露的您的个人信息，我们会充分重视风险，在收到公开披露申请后第一时间且审慎审查其正当性、合理性、合法性，并在公开披露时和公开披露后采取不低于本政策约定的个人信息安全保护措施和手段对其进行保护。<br />3.3请您知悉，即使已经取得您的授权同意，我们也仅会出于合法、正当、必要、特定、明确的目的公开披露您的个人信息，并尽量对公开披露内容中的个人信息进行匿名化处理。<br />3.4根据法律法规规定，针对以下情况，共享、转让、公开披露您的个人信息无需事先征得您的授权同意：<br />（1）与国家安全、国防安全直接相关的；<br />（2）与公共安全、公共卫生、重大社会公共利益直接相关的；<br />（3）与犯罪侦查、起诉、审判和判决执行等直接相关的；或根据法律法规的要求、行政机关或公检法等有权机关的要求的；<br />（4）为维护您或其他个人的生命、财产等重大合法权益但又很难得到您本人同意的；<br />（5）个人信息是您自行向社会公开的或者是从合法公开的渠道（如合法的新闻报道、政府信息公开等渠道）中收集到的；<br />（6）根据与您签订的相关协议或其他书面文件约定的；<br />（7）法律法规规定的其他情形。</p>\n<p>五、我们如何使用Cookie和同类技术</p>\n<p>Cookie对提升用户的网络使用体验十分重要，飞鸟阅读使用Cookie一般出于以下目的：<br />1.身份验证<br />Cookie可在您接入我们的服务时通知我们，以验证您的账号信息，确保您的账号安全。例如，Cookie技术可在您登入飞鸟阅读时通知我们，因此，我们能够在您访问网站时，识别是否为本人安全登录，向您显示与您相关的信息。<br />2.偏好设置<br />Cookie可帮助我们按照您所希望的服务样式、外观的个性化设置为您提供服务。例如，Cookie技术可记录您是否已经阅读过一些提示性的展现，防止重复提示对您造成骚扰；一些阅读展现（如字体大小等方面）可依您本人偏好设定，我们也会存放于Cookie中，在您访问时自动为您调整到您上次的设定值。<br />3.安全<br />Cookie可帮助我们保障数据和服务的安全性，排查针对我们服务的作弊、黑客、欺诈行为。例如，Cookie可以存放令牌的票据信息，能够由服务器验证是否是您自主在本站点的正常登录，通过票据中的加密信息，阻止多种类型的攻击，防止跨站信息窃取访问，防止身份冒充访问。<br />4.功能与服务<br />Cookie可帮助我们为用户提供更好的本网站与相关服务。例如，Cookie可以在您进行登录时，通过存储的信息，帮您填入您最后一次登入的账号名称，提高您的操作效率。<br />5.效率<br />Cookie可以避免不必要的服务器负载，提高服务效率，节省资源。例如，Cookie可帮助我们优化在服务器之间路由的流量，并且了解不同用户加载我们服务时的速度，有时我们可能会利用Cookie让您在使用网站时加载及响应得更快。</p>\n<p>六、我们如何保护个人信息安全</p>\n<p>1.保护用户个人信息是本平台的一项基本原则，本平台将会采取合理的措施保护用户的个人信息。除法律法规规定或本政策另有约定的情形外，未经用户许可本平台不会向第三方公开、透露用户个人信息。本平台对相关信息采用专业加密存储与传输方式，保障用户个人信息的安全。<br />2.本平台将按现有技术运用各种安全技术和程序尽力建立完善的管理制度来保护您的个人信息，我们将在任何时候尽力做到使您免遭受未经授权的访问、使用或披露。例如，https + 自研加密方法实现传输加密；密码加密存储，做到只能验证密码正确性但无法看到密码；进行恶意用户流量检测，对可疑用户行为予以分析后冻结账号，防止账号被盗给用户造成损失；数据库高可用（High Availability，指通过设计减少系统不能提供服务的时间）防止用户数据丢失，并且可进行备份和恢复等。<br />我们也请您理解，任何安全措施都无法做到无懈可击。</p>\n<p>七、我们如何保护和存储个人信息</p>\n<p>1.我们遵守法律法规的规定，在中华人民共和国境内运营中收集和产生的个人信息，均存储在中国境内，除法律法规有明确规定，我们亦会确保依据本隐私政策对您的个人信息提供足够的保护。目前，我们不会将上述信息传输至境外，如果我们向境外传输，我们将会遵循相关国家规定或者征求您的同意。<br />2.一般而言，我们仅在实现目的所必需的最短期限保留您的个人信息，除非法律有强制的存留要求。而我们判断前述期限的标准包括：<br />（1）完成与您相关的服务目的、维护相应服务及业务记录、应对您可能的查询或投诉；<br />（2）保证我们为您提供服务的安全和质量；<br />（3）您是否同意更长的留存时间；<br />（4）是否存在保留期限的其他特别约定。<br />在您的个人信息超出保留期限后，我们会根据适用法律的要求删除您的个人信息，或使其匿名化处理。<br />3.信息删除和匿名化处理<br />如飞鸟阅读决定停止运营的，我们将以公告形式将停止运营通知向您送达，在相关服务停止运营后我们将停止继续收集您的个人信息，并对已持有的您的个人信息进行删除或匿名化处理。<br />4.您理解并同意，为了更好地为您提供服务，本平台可以在您飞鸟阅读账号下关联该账号在飞鸟阅读其他客户端的数据记录（如书架内容），同样，本平台也可将您在本平台产生的数据记录关联至其他客户端上的该账号上。</p>\n<p>八、您的权利</p>\n<p>依照相关法律法规的规定，您有权在使用本网站与服务时，对您的个人信息行使合法权利。同时为了您可以便捷地访问和管理您的信息，我们在本网站与服务内为您提供了相应的“帮助中心”页面提示，您可以基于不同的需求点击查看相应操作。因不同的版本会存在一定差异，以下指引仅做参考，请您结合本网站版本来适用。<br />1.访问您的个人信息<br />您有权访问您的个人信息，法律法规规定的例外情况除外。如果您希望行使数据访问权，可以通过以下方式自行访问：<br />（1）如您是读者的，您可以在本网站点击账号头像进入个人中心，查看您的头像、昵称、ID；<br />（2）如您是作家的，您可以登录进入作家专区的个人信息版块，查看您的头像、昵称等信息。<br />2.更正、修改及补充个人信息：<br />（1）如您是读者的，您可以在本网站点击账号头像进入个人中心，编辑您的个人资料和修改头像；<br />（2）如您是作家，当您需要更正您的个人信息时，您可以自行进入作家专区的个人信息页面，修改您的昵称、头像、基础信息和认证信息，也可以联系客服协助您修改个人信息。但请您理解知悉，飞鸟阅读账号（用户名）是您使用本网站与服务的重要身份标识，具有唯一性，一经注册不可再次修改。<br />3.账号注销及个人信息删除、撤回<br />3.1账号注销：您有权注销您的账号，账号一经注销不可恢复，您在查阅确认注销须知后，请与我们的客服取得联系，提供账号相关信息，以及个人中心截图。当您符合约定的账户注销条件后，我们会尽快配合完成相应注销申请及操作。在注销账号之后，我们将停止为您提供相应的产品或服务。如果您是作者，请在“作者专区-网站公告-问题咨询”留言申请关闭作者账号，关闭后联系客服申请账号注销。<br />3.2个人信息删除、撤回：<br />3.2.1您可以在本网站页面直接删除的信息有书架记录、阅读历史，具体操作方式为：点击首页“书架”按钮，在“我的书架”中点击编辑来删除您的书架记录；在“我的消息”中清空消息内容。<br />3.2.2如您想删除其他个人信息的，在以下情形中，您可以与我们的客服取得联系，提出删除个人信息的请求：<br />（1）如果我们处理个人信息的行为违反法律法规；<br />（2）如果我们没有征得您的同意收集、使用了您的个人信息；<br />（3）如果我们处理您的个人信息的行为违反了与您的相关约定；<br />（4）如果您不再使用我们的本网站或服务，或您注销了账号、撤回同意；<br />（5）如果我们停止为您提供本网站或服务，或者保存期限已届满；<br />（6）我们处理目的已实现、无法实现或者为实现处理目的已不再必要。<br />3.2.3请您理解并知晓，当您选择删除您的个人信息后，我们基于本网站和服务稳定运营的考量，不会立即从我们的备份产品和服务中删除相应信息，会在后续更新备份时予以删除处理。<br />4.复制和转移个人信息：<br />4.1复制个人信息：<br />您有权复制我们收集关于您的个人信息。如您有相应需求的，您可以通过本政策所载联系方式与我们取得联系，我们在核实您的身份后，在符合法律法规规定的条件下，向您提供您在本网站与服务中的个人信息（包括用户名、头像、简介、手机号等个人资料）副本。<br />4.2转移个人信息：<br />若您有将您的个人信息转移至您指定的其他主体的需求的，您可以通过本政策所载联系方式与我们取得联系，我们在核实您的身份后，在法律法规规定允许条件下，以及在符合国家网信部门规定条件、且可行的技术实施措施下执行。<br />5.权利响应<br />5.1请您知晓并理解，如涉及如下相关个人信息需求的，按照法律法规的要求，我们将无法响应您的请求：<br />（1）与国家安全、国防安全直接相关的；<br />（2）与公共安全、公共卫生、重大公共利益直接相关的；<br />（3）与犯罪侦查、起诉、审判或判决执行直接相关的；<br />（4）有充分证据证明您存在恶意或滥用权利的；<br />（5）响应您的请求将导致您或其他主体的合法权益受到严重损害的；<br />（6）涉及商业秘密的；<br />（7）法律法规规定的其他情形。<br />5.2为保证您的个人信息安全，并使我们高效处理您的问题及时向您反馈，您需要配合我们对您的身份进行核实，需要您提交相应身份证明、有效联系方式及书面请求内容和相关证据，我们会核验您的身份后方能处理您的相应请求。<br />5.3我们一般会在15个工作日内作出请求反馈处理。如您不满意，还可以通过本政策具体联系方式进行投诉。<br />5.4在本网站某些服务功能中，我们可能仅依据信息系统、算法在内的非人工自动决策机制作出决定。如果这些决定影响您的合法权益，您可以通过客服与我们取得联系。<br />5.5对于您在合理范围内的需求，我们原则上不收取费用，但对于多次重复、超出合理限度的请求，我们将视情况收取一定的成本费用。对于无端重复、需要过多技术手段（例如需要开发新系统或从根本上改变现行惯例）、影响其他方合法权益、存有风险或者不切实际（例如涉及备份磁带上存放的信息）的请求，我们可能会予以拒绝。<br />6.如何联系我们<br />如您对使用本网站与服务有任何疑问，您可通过本网站底部界面进行查阅了解，点击在线客服或拨打电话400-888-8888周一至周五（8:00 –次日02:00）联系我们。您也可以通过以下地址与我们取得联系：<br />北京市朝阳区<br />北京飞鸟阅读网络技术有限公司</p>\n<p>九、未年人保护</p>\n<p>1.未成年人使用我们的服务前，应取得监护人的同意。如您为未成年人，应在监护人监护、指导下使用本服务，如您是未成年人的监护人，请您在未成年人使用我们的服务前仔细阅读、充分理解本政策内容。<br />2.我们根据国家相关法律法规的规定保护未成年人的个人信息，只会在法律法规允许、监护人明确同意或保护未成年人的权益所必要的情况下收集、使用或公开披露未成年人的个人信息。若您是未成年人的监护人，当您对您所监护的未成年人的个人信息有相关疑问时，您可以通过本政策“联系我们”中公示的联系方式与我们沟通解决。如果我们发现在未事先获得可证实的监护人同意的情况下收集了未成年人的个人信息，则会尽快删除相关数据。</p>\n<p>十、其他</p>\n<p>1.本政策签订地为中华人民共和国北京市朝阳区。<br />2.本政策的成立、生效、履行、解释及纠纷解决，适用中华人民共和国大陆地区法律（不包括冲突法）。<br />3.若您和本平台之间发生任何纠纷或争议，首先应友好协商解决；协商不成的，您同意将纠纷或争议提交本政策签订地有管辖权的人民法院管辖。<br />4.本政策项下所有的通知均可通过页面公告、电子邮件、短信或常规的信件传送等方式进行；该等通知于发送之日视为已送达收件人。<br />5.本政策所有条款的标题仅为阅读方便，本身并无实际涵义，不能作为本政策涵义解释的依据。<br />6.本政策条款无论因何种原因部分无效或不可执行，其余条款仍有效，对双方具有约束力。</p>\n<p><br />本行以下无正文</p>\n<p>北京飞鸟阅读网络技术有限公</p>', '1', '4', '0', 'privacy', 'default', '1', '1729592293', '0', '0');
INSERT INTO `fn_pages` VALUES ('5', '成为飞鸟阅读的签约作者 利用人工智能为您赋能', '0', '', '提升工作效率：AI技术可以通过自动化大量重复性任务来释放人类的创造力，使人们能够专注于更有价值的工作。比如，智能助手可以帮助管理等日程、回复邮件等琐事。', '<p>人工智能（AI）为个人带来的赋能主要体现在以下几个方面：</p>\n<p>提升工作效率：AI技术可以通过自动化大量重复性任务来释放人类的创造力，使人们能够专注于更有价值的工作。比如，智能助手可以帮助管理等日程、回复邮件等琐事。</p>\n<p>提升个人能力：AI可以通过智能推荐、个性化学习等方式，帮助个人更高效地获取知识和技能。例如，智能学习平台可以根据个人的学习习惯和进度，推荐合适的学习资料。</p>\n<p>优化人机关系：高新波委员提出，正确处理人和机器的关系，才能更好地释放“人机混合”智能时代的技术红利。这意味着，在人工智能的应用和发展中，需要确保其安全、可靠、可控。</p>', '1', '6', '0', 'homeai', 'default', '1', '1729602352', '0', '0');
INSERT INTO `fn_pages` VALUES ('6', '阅读小说，轻松赚现金！', '0', '', '在这个信息爆炸的时代，想要通过阅读来赚钱，其实并不难。AI技术为这一需求提供了极大的便利。想象一下，一款能够在你休息时自动帮你赚钱的应用或者平台，真是太酷了！这些平台就像你的个人“摇钱树”，不仅帮你稳定收入，还能在你忙碌之余，不断给你带来惊喜。\n\n例如，你可以利用AI代写工具来赚钱。这些工具可以帮助你快速生成内容，无论是文章、小说还是其他形式的创作，都可以通过付费服务他人来赚取收入。只需你愿意动手，便能在网络上找到丰富的变现途径。', '<p>在这个信息爆炸的时代，想要通过阅读来赚钱，其实并不难。AI技术为这一需求提供了极大的便利。想象一下，一款能够在你休息时自动帮你赚钱的应用或者平台，真是太酷了！这些平台就像你的个人“摇钱树”，不仅帮你稳定收入，还能在你忙碌之余，不断给你带来惊喜。</p>\n<p>例如，你可以利用AI代写工具来赚钱。这些工具可以帮助你快速生成内容，无论是文章、小说还是其他形式的创作，都可以通过付费服务他人来赚取收入。只需你愿意动手，便能在网络上找到丰富的变现途径。</p>', '1', '3', '0', 'homecoin', 'default', '1', '1729602986', '0', '0');

-- ----------------------------
-- Table structure for fn_pages_keywords
-- ----------------------------
DROP TABLE IF EXISTS `fn_pages_keywords`;
CREATE TABLE `fn_pages_keywords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '页面ID',
  `keywords_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联关键字id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `inid` (`keywords_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='单页面关联表';

-- ----------------------------
-- Records of fn_pages_keywords
-- ----------------------------
INSERT INTO `fn_pages_keywords` VALUES ('3', '1', '1', '1', '1729582587');
INSERT INTO `fn_pages_keywords` VALUES ('4', '3', '1', '1', '1729591785');
INSERT INTO `fn_pages_keywords` VALUES ('5', '4', '1', '1', '1729592293');
INSERT INTO `fn_pages_keywords` VALUES ('6', '5', '1', '1', '1729602352');
INSERT INTO `fn_pages_keywords` VALUES ('7', '6', '1', '1', '1729602986');

-- ----------------------------
-- Table structure for fn_position
-- ----------------------------
DROP TABLE IF EXISTS `fn_position`;
CREATE TABLE `fn_position` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '岗位名称',
  `work_price` int(10) NOT NULL DEFAULT '0' COMMENT '工时单价',
  `remark` varchar(1000) DEFAULT '' COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：-1删除 0禁用 1启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='岗位职称';

-- ----------------------------
-- Records of fn_position
-- ----------------------------
INSERT INTO `fn_position` VALUES ('1', '董事长', '1000', '董事长', '1', '0', '0');
INSERT INTO `fn_position` VALUES ('2', '人事总监', '1000', '人事部的最大领导', '1', '0', '0');
INSERT INTO `fn_position` VALUES ('3', '普通员工', '500', '普通员工', '1', '0', '0');

-- ----------------------------
-- Table structure for fn_readhistory
-- ----------------------------
DROP TABLE IF EXISTS `fn_readhistory`;
CREATE TABLE `fn_readhistory` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `read_date` date DEFAULT NULL COMMENT '阅读时间',
  `ip` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'IP',
  `book_id` int(11) DEFAULT NULL,
  `chapter_id` int(11) DEFAULT NULL,
  `position` decimal(6,2) DEFAULT '0.00' COMMENT '章节位置坐标',
  `readnum` int(10) DEFAULT '0' COMMENT '阅读次数',
  `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`) USING BTREE,
  KEY `idx_u_i_a` (`user_id`,`book_id`) USING BTREE,
  KEY `idx_a_u` (`book_id`,`user_id`) USING BTREE,
  KEY `idx_a_c_u` (`book_id`,`chapter_id`,`user_id`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `ip` (`ip`),
  KEY `create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='阅读记录::crud';

-- ----------------------------
-- Records of fn_readhistory
-- ----------------------------

-- ----------------------------
-- Table structure for fn_report
-- ----------------------------
DROP TABLE IF EXISTS `fn_report`;
CREATE TABLE `fn_report` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `contact` varchar(200) DEFAULT '' COMMENT '联系方式',
  `introduce` varchar(1000) DEFAULT NULL COMMENT '举报内容',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `user_id` int(10) DEFAULT '0' COMMENT '提交者',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '编辑时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='举报::crud';

-- ----------------------------
-- Records of fn_report
-- ----------------------------

-- ----------------------------
-- Table structure for fn_search_keywords
-- ----------------------------
DROP TABLE IF EXISTS `fn_search_keywords`;
CREATE TABLE `fn_search_keywords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '关键字',
  `times` int(11) NOT NULL DEFAULT '1' COMMENT '搜索次数',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1,2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='搜索关键字表';

-- ----------------------------
-- Records of fn_search_keywords
-- ----------------------------
INSERT INTO `fn_search_keywords` VALUES ('1', '飞鸟阅读', '1', '1');

-- ----------------------------
-- Table structure for fn_search_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_search_log`;
CREATE TABLE `fn_search_log` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `type` tinyint(4) DEFAULT '1' COMMENT '搜索类型1：小说，2：作者',
  `client` tinyint(4) DEFAULT '1' COMMENT '客户端1：app，2：官网，3作家，4后台',
  `keyword` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `resnum` int(10) DEFAULT NULL,
  `create_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='搜索记录表';

-- ----------------------------
-- Records of fn_search_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_sign_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_sign_log`;
CREATE TABLE `fn_sign_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `sign_date` date NOT NULL COMMENT '签到日期',
  `consecutive_days` int(11) DEFAULT '0' COMMENT '连续签到天数',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `sign_date` (`sign_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录';

-- ----------------------------
-- Records of fn_sign_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_slide
-- ----------------------------
DROP TABLE IF EXISTS `fn_slide`;
CREATE TABLE `fn_slide` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '标识',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '1可用-1禁用',
  `desc` varchar(1000) DEFAULT NULL,
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='幻灯片表';

-- ----------------------------
-- Records of fn_slide
-- ----------------------------
INSERT INTO `fn_slide` VALUES ('1', '首页轮播', 'INDEX_SLIDE', '1', '首页轮播组。', '0', '0');

-- ----------------------------
-- Table structure for fn_slide_info
-- ----------------------------
DROP TABLE IF EXISTS `fn_slide_info`;
CREATE TABLE `fn_slide_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slide_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(4) DEFAULT '0' COMMENT '类型',
  `title` varchar(255) DEFAULT NULL,
  `desc` varchar(1000) DEFAULT NULL,
  `img` varchar(255) NOT NULL DEFAULT '',
  `src` varchar(255) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '1可用-1禁用',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='幻灯片详情表';

-- ----------------------------
-- Records of fn_slide_info
-- ----------------------------
INSERT INTO `fn_slide_info` VALUES ('1', '1', '2', '首页轮播1', '<div class=\"col-lg-7 col-md-8\">\n					<div class=\"hero-left-content\">\n						<h2>用文字绘画，为情感谱曲！<br> <span>你的想象无边，我们的创作无限，小说等你来发掘！</span></h2>\n						<p>故事随手写，情感万相生，品味小说中的世界！</p>					\n					</div>\n				</div>', '', '', '1', '5', '1729355956', '1729356697');
INSERT INTO `fn_slide_info` VALUES ('2', '1', '2', '作家创作', '<div class=\"col-lg-7 col-md-8\">\n					<div class=\"hero-left-content\">\n						<h2>穿越千古脉络，情系字里千秋。<br><strong>书香袅袅，故事悠悠，走进小说，共鉴人生。</strong></h2>\n						<p>墨香涌动，情思无尽，小说的世界，等你来探索。</p>					\n					</div>\n				</div>', '', '', '1', '0', '1729356543', '0');

-- ----------------------------
-- Table structure for fn_sms_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_sms_log`;
CREATE TABLE `fn_sms_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天已经发送成功的次数',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后发送成功时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '验证码过期时间',
  `code` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '最后发送成功的验证码',
  `account` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '手机号或者邮箱',
  PRIMARY KEY (`id`),
  KEY `account` (`account`),
  KEY `code` (`account`,`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手机邮箱数字验证码表';

-- ----------------------------
-- Records of fn_sms_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_task
-- ----------------------------
DROP TABLE IF EXISTS `fn_task`;
CREATE TABLE `fn_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(4) DEFAULT '0' COMMENT '任务类型1：一次性，2：重复性，3：邀请，456：阅读章节奖励',
  `taskid` varchar(20) DEFAULT NULL COMMENT '任务ID',
  `task_date` date DEFAULT NULL COMMENT '日期',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态',
  `title` varchar(200) DEFAULT NULL COMMENT '任务名称',
  `reward` int(11) DEFAULT '0' COMMENT '奖励',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `task_date` (`task_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务';

-- ----------------------------
-- Records of fn_task
-- ----------------------------

-- ----------------------------
-- Table structure for fn_third
-- ----------------------------
DROP TABLE IF EXISTS `fn_third`;
CREATE TABLE `fn_third` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned DEFAULT '0' COMMENT '会员ID',
  `platform` varchar(30) DEFAULT '' COMMENT '第三方应用',
  `apptype` varchar(50) DEFAULT '' COMMENT '应用类型',
  `unionid` varchar(100) DEFAULT '' COMMENT '第三方UNIONID',
  `openid` varchar(100) DEFAULT '' COMMENT '第三方OPENID',
  `openname` varchar(100) DEFAULT '' COMMENT '第三方会员昵称',
  `access_token` varchar(255) DEFAULT '' COMMENT 'AccessToken',
  `refresh_token` varchar(255) DEFAULT 'RefreshToken',
  `expires_in` int(10) unsigned DEFAULT '0' COMMENT '有效期',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `logintime` bigint(16) DEFAULT NULL COMMENT '登录时间',
  `expiretime` bigint(16) DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`,`openid`),
  KEY `user_id` (`user_id`,`platform`),
  KEY `unionid` (`platform`,`unionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='三方登录';

-- ----------------------------
-- Records of fn_third
-- ----------------------------

-- ----------------------------
-- Table structure for fn_user
-- ----------------------------
DROP TABLE IF EXISTS `fn_user`;
CREATE TABLE `fn_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '用户微信昵称',
  `inviter` int(11) DEFAULT '0' COMMENT '邀请人ID',
  `author_id` int(11) DEFAULT '0' COMMENT '作者ID',
  `username` varchar(100) NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '密码',
  `securitypwd` varchar(255) DEFAULT NULL COMMENT '安全密码',
  `salt` varchar(100) NOT NULL DEFAULT '' COMMENT '密码盐',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `id_card` varchar(100) DEFAULT NULL COMMENT '身份证',
  `id_card_photo` varchar(250) DEFAULT NULL COMMENT '身份证照片',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机（也可以作为登录账号)',
  `mobile_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '手机绑定状态： 0未绑定 1已绑定',
  `realname_status` tinyint(4) DEFAULT '0' COMMENT '实名状态',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `coin` int(11) DEFAULT '0' COMMENT '金币',
  `headimgurl` varchar(255) NOT NULL DEFAULT '' COMMENT '微信头像',
  `sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0:未知 1:女 2:男 ',
  `desc` varchar(1000) NOT NULL DEFAULT '' COMMENT '个人简介',
  `birthday` int(11) DEFAULT '0' COMMENT '生日',
  `country` varchar(20) NOT NULL DEFAULT '' COMMENT '国家',
  `province` varchar(20) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(20) NOT NULL DEFAULT '' COMMENT '城市',
  `company` varchar(100) NOT NULL DEFAULT '' COMMENT '公司',
  `address` varchar(100) NOT NULL DEFAULT '' COMMENT '公司地址',
  `depament` varchar(20) NOT NULL DEFAULT '' COMMENT '部门',
  `position` varchar(20) NOT NULL DEFAULT '' COMMENT '职位',
  `qrcode_invite` varchar(200) DEFAULT NULL COMMENT '邀请场景二维码id',
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '等级  默认是普通会员',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态  -1删除 0禁用 1正常',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(64) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_num` int(11) NOT NULL DEFAULT '0',
  `register_time` int(11) NOT NULL DEFAULT '0' COMMENT '注册时间',
  `register_ip` varchar(64) NOT NULL DEFAULT '' COMMENT '注册IP',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '信息更新时间',
  `wx_platform` int(11) NOT NULL DEFAULT '0' COMMENT '首次注册来自于哪个微信平台',
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`),
  KEY `qrcode_invite` (`qrcode_invite`),
  KEY `email` (`email`),
  KEY `id_card` (`id_card`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- Records of fn_user
-- ----------------------------

-- ----------------------------
-- Table structure for fn_user_level
-- ----------------------------
DROP TABLE IF EXISTS `fn_user_level`;
CREATE TABLE `fn_user_level` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '等级名称',
  `desc` varchar(1000) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '状态:0禁用,1正常',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='会员等级表';

-- ----------------------------
-- Records of fn_user_level
-- ----------------------------
INSERT INTO `fn_user_level` VALUES ('1', '普通会员', '', '1', '1639562910', '0');
INSERT INTO `fn_user_level` VALUES ('2', '铜牌会员', '', '1', '1639562910', '0');
INSERT INTO `fn_user_level` VALUES ('3', '银牌会员', '', '1', '1639562910', '0');
INSERT INTO `fn_user_level` VALUES ('4', '黄金会员', '', '1', '1639562910', '0');
INSERT INTO `fn_user_level` VALUES ('5', '白金会员', '', '1', '1639562910', '0');
INSERT INTO `fn_user_level` VALUES ('6', '钻石会员', '', '1', '1639562910', '0');

-- ----------------------------
-- Table structure for fn_user_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_user_log`;
CREATE TABLE `fn_user_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `type` varchar(80) NOT NULL DEFAULT '' COMMENT '操作类型',
  `title` varchar(80) NOT NULL DEFAULT '' COMMENT '操作标题',
  `content` text COMMENT '操作描述',
  `module` varchar(32) NOT NULL DEFAULT '' COMMENT '模块',
  `controller` varchar(32) NOT NULL DEFAULT '' COMMENT '控制器',
  `function` varchar(32) NOT NULL DEFAULT '' COMMENT '方法',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '登录ip',
  `param_id` int(11) unsigned NOT NULL COMMENT '操作ID',
  `param` text COMMENT '参数json格式',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0删除 1正常',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户操作日志表';

-- ----------------------------
-- Records of fn_user_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_vip_log
-- ----------------------------
DROP TABLE IF EXISTS `fn_vip_log`;
CREATE TABLE `fn_vip_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '表id',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `level` tinyint(4) DEFAULT '0' COMMENT '级别',
  `order_id` int(11) DEFAULT '0' COMMENT '订单ID',
  `expire_time` int(10) unsigned DEFAULT '0' COMMENT '过期时间',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `s_u_e` (`status`,`user_id`,`expire_time`) USING BTREE,
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='vip记录';

-- ----------------------------
-- Records of fn_vip_log
-- ----------------------------

-- ----------------------------
-- Table structure for fn_volume
-- ----------------------------
DROP TABLE IF EXISTS `fn_volume`;
CREATE TABLE `fn_volume` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bookid` int(11) DEFAULT '0' COMMENT '作品ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '卷名称',
  `desc` varchar(1000) DEFAULT '' COMMENT '描述',
  `sort` int(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='章节卷::crud';

-- ----------------------------
-- Records of fn_volume
-- ----------------------------

-- ----------------------------
-- Table structure for fn_withdraw
-- ----------------------------
DROP TABLE IF EXISTS `fn_withdraw`;
CREATE TABLE `fn_withdraw` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0' COMMENT '提现人',
  `admin_id` int(11) DEFAULT '0' COMMENT '操作员',
  `tax` decimal(10,2) DEFAULT '0.00' COMMENT '税',
  `notes` varchar(255) DEFAULT NULL COMMENT '备注',
  `card_id` int(10) DEFAULT '0' COMMENT '收款账号',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '提现额',
  `coin` int(11) DEFAULT '0' COMMENT '金币',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`),
  KEY `uid_status_coin` (`user_id`,`status`,`coin`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现';

-- ----------------------------
-- Records of fn_withdraw
-- ----------------------------
