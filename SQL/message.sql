-- ----------------------------
-- 核心权限表
-- ----------------------------

-- ----------------------------
-- Table structure for yx_admin
-- ----------------------------
DROP TABLE IF EXISTS `yx_admin`;
CREATE TABLE `yx_admin`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_name` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `passwd` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '密码',
  `department` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '部门',
  `stype` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '用户类型 1.后台管理员 2.超级管理员',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1.启用 2.停用',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_permissions_api`;
CREATE TABLE `yx_permissions_api` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属菜单',
  `api_name` varchar(50) NOT NULL DEFAULT '' COMMENT '接口url',
  `stype` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '接口curd权限 1.增 2.删 3.改 4.查',
  `cn_name` varchar(50) NOT NULL DEFAULT '' COMMENT '权限名称',
  `content` varchar(200) NOT NULL DEFAULT '' COMMENT '权限的详细描述',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_api_name` (`api_name`,`delete_time`) USING BTREE,
  KEY `index_meun_id` (`menu_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Api接口权限';

-- ----------------------------
-- Table structure for yx_admin_permissions_group
-- ----------------------------
DROP TABLE IF EXISTS `yx_admin_permissions_group`;
CREATE TABLE `yx_admin_permissions_group`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '权限分组id',
  `admin_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员id',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_group_id_admin_id`(`group_id`, `admin_id`, `delete_time`) USING BTREE,
  INDEX `index_admin_id`(`admin_id`) USING BTREE
) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '管理员权限分组关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yx_admin_permissions_relation
-- ----------------------------
DROP TABLE IF EXISTS `yx_admin_permissions_relation`;
CREATE TABLE `yx_admin_permissions_relation`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `menu_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `api_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_group_id_menu_id_api_id`(`group_id`, `menu_id`, `api_id`, `delete_time`) USING BTREE
) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户分组权限关系表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pz_log_image
-- ----------------------------
DROP TABLE IF EXISTS `yx_log_image`;
CREATE TABLE `yx_log_image`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '上传者',
  `stype` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '1.index 2.admin',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 2 COMMENT '状态1.完成 2.未完成 3.弃用',
  `image_path` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图片路径',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_image_path`(`image_path`, `delete_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件上传日志' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for yx_menu
-- ----------------------------
DROP TABLE IF EXISTS `yx_menu`;
CREATE TABLE `yx_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上级id',
  `name` varchar(16) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `level` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '菜单等级',
  `icon_image` char(60) NOT NULL DEFAULT '' COMMENT '未选中的菜单标题图',
  `select_image` char(60) NOT NULL DEFAULT '' COMMENT '选中的菜单标题图',
  `link` varchar(100) NOT NULL DEFAULT '' COMMENT '链接',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='cms左侧菜单';

-- ----------------------------
-- Table structure for yx_permissions_group
-- ----------------------------
DROP TABLE IF EXISTS `yx_permissions_group`;
CREATE TABLE `yx_permissions_group`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分组名称',
  `content` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分组描述',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_group_name`(`group_name`, `delete_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '权限分组名称' ROW_FORMAT = Dynamic;

CREATE TABLE `yx_permissions_api` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属菜单',
  `api_name` varchar(50) NOT NULL DEFAULT '' COMMENT '接口url',
  `stype` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '接口curd权限 1.增 2.删 3.改 4.查',
  `cn_name` varchar(50) NOT NULL DEFAULT '' COMMENT '权限名称',
  `content` varchar(200) NOT NULL DEFAULT '' COMMENT '权限的详细描述',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_api_name` (`api_name`,`delete_time`) USING BTREE,
  KEY `index_meun_id` (`menu_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COMMENT='Api接口权限';

DROP TABLE IF EXISTS `yx_log_file`;
CREATE TABLE `yx_log_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '上传者',
  `stype` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '1.index 2.admin',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '状态1.完成 2.未完成 3.弃用',
  `image_path` char(60) NOT NULL DEFAULT '' COMMENT '文件路径',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_image_path` (`image_path`,`delete_time`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='文件上传日志';

-- -----------------------------
-- 业务功能表
-- -----------------------------

CREATE TABLE `yx_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `passwd` char(64) NOT NULL DEFAULT '' COMMENT '用户密码',
  `nick_name` char(30) NOT NULL DEFAULT '' COMMENT '用户名',
  `appid` char(13) NOT NULL DEFAULT '' COMMENT '用户APPID',
  `appkey` char(32) NOT NULL DEFAULT '' COMMENT '用户APPkey',
  `user_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '用户类型1.个人账户2.企业账户',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT 'email',
  `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '剩余金额（现金）',
  `free_trial` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1:需要审核;2:无需审核',
  `user_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '账户服务状态 1停止服务 2启用服务',
  `reservation_service` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '可否预用服务 1不可 2可以',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `p_uid` (`id`,`pid`) USING BTREE,
  UNIQUE KEY `index_mobile` (`mobile`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

DROP TABLE IF EXISTS `yx_admin_remittance`;
CREATE TABLE `yx_admin_remittance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `initiate_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发起操作人',
  `audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核人',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务服务id',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收款账户',
  `mobile` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '收款账户手机号',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1.待审核 2.已审核 3.取消',
  `credit` int(10) NOT NULL DEFAULT '0' COMMENT '收款数量',
  `message` varchar(100) NOT NULL DEFAULT '' COMMENT '详细描述',
  `admin_message` varchar(100) NOT NULL DEFAULT '' COMMENT '审核查看描述',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='cms 服务手动充值';

-- ----------------------------
-- Table structure for pz_user_con
-- ----------------------------
DROP TABLE IF EXISTS `yx_user_con`;
CREATE TABLE `yx_user_con`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `con_id` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_con_id`(`con_id`) USING BTREE,
  UNIQUE INDEX `uniq_uid`(`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户con_id和uid关系' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_areas`;
CREATE TABLE `yx_areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `code` char(12) NOT NULL DEFAULT '' COMMENT '统计用区划代码',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '层级',
  `area_name` varchar(20) NOT NULL DEFAULT '' COMMENT '区域名',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `index_pid` (`pid`,`delete_time`) USING BTREE,
  KEY `index_level` (`level`,`delete_time`) USING BTREE,
  KEY `index_area_name` (`area_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='省市区关系表';

-- ----------------------------
-- Table structure for pz_log_vercode
-- ----------------------------
DROP TABLE IF EXISTS `pz_log_vercode`;
CREATE TABLE `pz_log_vercode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `stype` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '验证码类型1.注册 2修改密码 3.快捷登录',
  `code` char(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '验证码内容',
  `mobile` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '验证码发送日志' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_order`;
CREATE TABLE `yx_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` char(23) NOT NULL DEFAULT '' COMMENT '生成唯一订单号',
  `third_order_id` char(28) NOT NULL DEFAULT '' COMMENT '第三方订单id',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `order_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '订单状态   1:待付款 2:取消订单 3:已关闭 4:已付款 5:已发货 6:已收货 7:待评价 8:退款申请确认 9:退款中 10:退款成功',
  `order_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单金额(优惠金额+实际支付的金额)',
  `pay_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际支付(第三方支付金额+商票抵扣金额)',
  `goods_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品金额',
  `discount_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `pay_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付类型 1.所有第三方支付 2.商票',
  `third_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '第三方支付金额',
  `third_pay_type` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '第三方支付类型1.支付宝 2.微信 3.银联 ',
  `message` varchar(255) NOT NULL DEFAULT '' COMMENT '买家留言信息',
  `third_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '第三方支付时间',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成订单时间',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发货时间',
  `rece_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收货时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_order_no` (`order_no`,`delete_time`) USING BTREE,
  KEY `index_uid_order_status` (`uid`,`order_status`) USING BTREE,
  KEY `index_uid_create_time` (`create_time`,`uid`) USING BTREE,
  KEY `index_uid_send_time` (`uid`,`send_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户现金消费订单' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_expense_log`;
CREATE TABLE `yx_expense_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'uid',
  `change_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1.消费 2.取消订单退还 3.充值 3.后台充值操作 ',
  `order_no` char(23) NOT NULL DEFAULT '' COMMENT '订单号',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '交易金额',
  `befor_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '交易前金额',
  `after_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '交易后金额',
  `message` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户现金消费记录' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_service_consumption_log`;
CREATE TABLE `yx_service_consumption_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'uid',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '消费服务id',
  `change_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1.消费 2.取消订单退还 3.充值 3.后台充值操作 ',
  `order_no` char(23) NOT NULL DEFAULT '' COMMENT '订单号',
  `money` int(10) NOT NULL DEFAULT '0' COMMENT '消费数量',
  `befor_money` int(10) NOT NULL DEFAULT '0.00' COMMENT '消费前数量',
  `after_money` int(10) NOT NULL DEFAULT '0.00' COMMENT '消费后数量',
  `message` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户服务消费记录' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_log_pay`;
CREATE TABLE `yx_log_pay` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_no` char(23) NOT NULL DEFAULT '' COMMENT '支付单号',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付用户',
  `payment` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付类型 1.普通订单 2.购买会员订单 3.虚拟商品订单',
  `pay_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '第三方支付方式 1.支付宝 2.微信 3.银联',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `prepay_id` char(36) NOT NULL DEFAULT '' COMMENT '微信prepay_id',
  `money` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付金额(整数，支付价格*100)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '1.成功 2.未成功',
  `notifydata` varchar(500) NOT NULL DEFAULT '' COMMENT '微信通知数据',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付成功时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uniq_pay_no` (`pay_no`,`delete_time`) USING BTREE,
  KEY `index_order_id_payment` (`order_id`,`payment`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方支付日志';

DROP TABLE IF EXISTS `yx_business`;
CREATE TABLE `yx_business` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '服务名称',
  `price` decimal(10,5) UNSIGNED NOT NULL DEFAULT 0.00000 COMMENT '统一服务价格', 
  `donate_num` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '赠送数量',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '业务类型及定价' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_sms_sending_channel`;
CREATE TABLE `yx_sms_sending_channel` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '通道名称',
  `channel_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '通道类型 1.http 2.cmpp ',
  `channel_port` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '连接端口,若无端口则不填',
  `channel_source` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '通道归属:1,中国移动;2,中国联通;3,中国电信;4,三网通',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务服务id',
  `channel_price` decimal(10,5) UNSIGNED NOT NULL DEFAULT 0.00000 COMMENT '通道价格',
  `channel_host` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '通道连接主机或者域名',
  `channel_postway` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'http请求方式:1,get;2,post;CMPP接口不填',
  `channel_source_addr` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '企业id,企业代码',
  `channel_shared_secret` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '网关登录密码',
  `channel_service_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '业务代码',
  `channel_template_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '模板ID',
  `channel_dest_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信接入码 短信端口号',
  `channel_flow_velocity` int(10) UNIQUE NOT NULL DEFAULT 0 COMMENT "通道最大流速/秒",
  `channel_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '通道状态:1,空闲;2,正常;3,忙碌;4,停止使用',
  `error_msg` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '通道错误信息',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '运营商通道' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_user_equities`;
CREATE TABLE `yx_user_equities` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务服务id',
  `price` decimal(10,5) UNSIGNED NOT NULL DEFAULT 0.00000 COMMENT '统一服务价格', 
  `agency_price` decimal(10,5) UNSIGNED NOT NULL DEFAULT 0.00000 COMMENT '代理价格',
  `num_balance` int(10) NOT NULL DEFAULT 0 COMMENT '条数余额',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index_business_id` (`business_id`,`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户可用业务及余额资费' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_user_qualification`;
CREATE TABLE `yx_user_qualification` (
  `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT  COMMENT '用户id',
  `company_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位或者主办人全称',
  `company_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 5 COMMENT '主办单位性质:1,国防机构;2,政府机关;3,事业单位;4,企业;5,个人;6社会团体;7,民办非企业单位;8,基金会;9,律师执业机构;10,外国在华文化中心;11,群众性团体组织;12,司法鉴定机构;13,宗教团体;14,境外机构;15,医疗机构;16,公证机构',
  `company_certificate_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '主办单位证件类型:1,营业执照（个人或企业）;3,组织机构代码证;4,事业单位法人证书;5,部队代号;9,组织机构代码证;12,组织机构代码证;13,统一社会信用代码证书;23,军队单位对外有偿服务许可证;27,外国企业常驻代表机构登记证',
  `company_certificate_num` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位证件号码',
  `province_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '省份id',
  `city_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '城市id',
  `county_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '地区id',
  `organizers_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位或主办人名称',
  `identity_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位证件住所',
  `mailingAddress_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位通讯地址(地区级)',
  `user_supp_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位通讯地址(街道门牌号级)',
  `investor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '投资人或主管单位',
  `entity_responsible_person_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人姓名',
  `entity_responsible_person_identity_types` tinyint(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人证件类型(参照【主办单位证件类型】)',
  `entity_responsible_person_identity_num` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人证件号码',
  `entity_responsible_person_mobile_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT ' 联系方式1',
  `entity_responsible_person_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系方式2',
  `entity_responsible_person_msn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT ' 应急联系电话',
  `entity_responsible_person_email` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '电子邮件地址',
  `entity_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '留言',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户认证信息'  ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_user_qualification_record`;
CREATE TABLE `yx_user_qualification_record` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `company_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位或者主办人全称',
  `company_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 5 COMMENT '主办单位性质:1,国防机构;2,政府机关;3,事业单位;4,企业;5,个人;6社会团体;7,民办非企业单位;8,基金会;9,律师执业机构;10,外国在华文化中心;11,群众性团体组织;12,司法鉴定机构;13,宗教团体;14,境外机构;15,医疗机构;16,公证机构',
  `company_certificate_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '主办单位证件类型:1,营业执照（个人或企业）;3,组织机构代码证;4,事业单位法人证书;5,部队代号;9,组织机构代码证;12,组织机构代码证;13,统一社会信用代码证书;23,军队单位对外有偿服务许可证;27,外国企业常驻代表机构登记证',
  `company_certificate_num` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位证件号码',
  `province_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '省份id',
  `city_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '城市id',
  `county_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '地区id',
  `organizers_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位或主办人名称',
  `identity_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位证件住所',
  `mailingAddress_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位通讯地址(地区级)',
  `user_supp_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '主办单位通讯地址(街道门牌号级)',
  `investor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '投资人或主管单位',
  `entity_responsible_person_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人姓名',
  `entity_responsible_person_identity_types` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人证件类型(参照【主办单位证件类型】)',
  `entity_responsible_person_identity_num` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人证件号码',
  `entity_responsible_person_mobile_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT ' 联系方式1',
  `entity_responsible_person_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '联系方式2',
  `entity_responsible_person_msn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT ' 应急联系电话',
  `entity_responsible_person_email` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '电子邮件地址',
  `entity_remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '留言',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1,已提交;2,审核中;3,审核通过;4,审核不通过',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户提交认证信息' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_model_temeplate`;
CREATE TABLE `yx_model_temeplate` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板标题',
  `template_id` char(23) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板id',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务服务id',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板内容',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY (`template_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '平台模板' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_user_model`;
CREATE TABLE `yx_user_model` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务服务id',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板标题',
  `template_id` char(23) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板id',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板内容',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1,提交申请;2,可用;3,审核通过;4,审核不通过;5,停用',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY (`template_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户自定义模板' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_blacklist`;
CREATE TABLE `yx_blacklist` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源：1.运营商;2.平台',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mobile_del` (`mobile`,`delete_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '黑名单' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_whitelist`;
CREATE TABLE `yx_whitelist` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源：1.平台',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mobile_del` (`mobile`,`delete_time`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '白名单' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_number_segment`;
CREATE TABLE `yx_number_segment` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源：1.移动;2.联通;3.电信;4,虚拟运营商',
  `name` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mobile_source` (`mobile`,`source`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '三网号码段' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_number_source`;
CREATE TABLE `yx_number_source` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` char(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `source` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '来源：1.移动;2.联通;3.电信;4,虚拟运营商',
  `source_name` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `province_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '省id',
  `province` varchar(20) NOT NULL DEFAULT '' COMMENT '区域名',
  `city_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '市id',
  `city` varchar(20) NOT NULL DEFAULT '' COMMENT '区域名',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `mobile_source` (`mobile`,`source`) USING BTREE,
  UNIQUE INDEX `mobile`(`mobile`) USING BTREE,
  UNIQUE INDEX `mobile_source_proinvice`(`mobile`, `source`, `province`, `city_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '三网号码归属省份及运营商' ROW_FORMAT = Dynamic;

ALTER TABLE `messagesend`.`yx_users` 
ADD COLUMN `appid` char(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户APPID' AFTER `nick_name`,
ADD COLUMN `appkey` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户APPkey' AFTER `appid`,
ADD UNIQUE INDEX `appid`(`appid`) USING BTREE;

DROP TABLE IF EXISTS `yx_user_send_task`;
CREATE TABLE `yx_user_send_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `task_name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `task_content` text COMMENT '发送内容',
  `mobile_content` text COMMENT '发送号码集合',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '请求源（ip）',
  `send_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送数量',
  `free_trial` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1:需要审核;2:审核通过;3:审核不通过;4:主管审核',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1：待发送,2:发送中;3:成功;4:失败',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通道id',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='营销任务表';

DROP TABLE IF EXISTS `yx_user_send_task_log`;
CREATE TABLE `yx_user_send_task_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务id',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `mobile` text COMMENT '发送号码集合',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1：待发送,2:发送中;3:成功;4:失败',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通道id',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='营销任务日志表';

ALTER TABLE `messagesend`.`yx_user_send_code_task_log` 
ADD COLUMN `status_message` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '状态' AFTER `source_status`,
ADD COLUMN `real_message` varchar(20) NOT NULL DEFAULT '' COMMENT '真实返回状态' AFTER `source_status`,
 ADD COLUMN `msgid` varchar(20) NOT NULL DEFAULT '' COMMENT 'msgid' AFTER `source_status`;

DROP TABLE IF EXISTS `yx_user_send_code_task_log`;
CREATE TABLE `yx_user_send_code_task_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
  `task_content` text COMMENT '发送内容',
  `mobile_content` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '请求源（ip）',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通道id',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '短信发送状态1：待发送,2:已发送;3:成功;4:失败',
  `source_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '网关状态1：待发送,2:已发送;3:成功;4:失败',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='行业发送记录表';
ALTER TABLE `messagesend`.`yx_users` 
ADD COLUMN `free_trial` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1:需要审核;2:无需审核' AFTER `money`;

INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (1, 'mar19111300075094089480', 4, '【米思米】安全围栏标准组件上市！不用设计，不用外发喷涂，不用组装！低至363.95元，第五天出货！赶紧过来下单吧。https://www.misumi.com.cn/mail/chn-gc19057-ml03/转发无效,详询021-52559388*6197,回T退订。', '【米思米】安全围栏标准组件上市！不用设计，不用外发喷涂，不用组装！低至363.95元，第五天出货！赶紧过来下单吧。https://www.misumi.com.cn/mail/chn-gc19057-ml03/转发无效,详询021-52559388*6197,回T退订。', '15599011983', '114.91.200.77', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (2, 'mar19111300142162041091', 4, '【米思米】安全围栏标准组件上市！不用设计，不用外发喷涂，不用组装！低至363.95元，第五天出货！赶紧过来下单吧。https://www.misumi.com.cn/mail/chn-gc19057-ml03/转发无效,详询021-52559388*6197,回T退订。', '【米思米】安全围栏标准组件上市！不用设计，不用外发喷涂，不用组装！低至363.95元，第五天出货！赶紧过来下单吧。https://www.misumi.com.cn/mail/chn-gc19057-ml03/转发无效,详询021-52559388*6197,回T退订。', '15599011983', '58.240.228.66', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (3, 'mar19111300484988602106', 4, '好友杨国帅向您推荐米思米SOLIDWORKS免费外挂插件，您尚未安装，速速安装并保存型号，轻松获得精美笔袋1个！ http://t.cn/Ai9H00fI ，回T退订【米思米】', '好友杨国帅向您推荐米思米SOLIDWORKS免费外挂插件，您尚未安装，速速安装并保存型号，轻松获得精美笔袋1个！ http://t.cn/Ai9H00fI ，回T退订【米思米】', '19783101836', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (4, 'mar19111300484939697099', 4, '尊敬的公司客户W03R3S,2019/8/31前,您只需再购买非模具类商品含税满3,000元即可参与活动，成功参与者即获小米充电宝1个！特选客户专享，转发无效。活动详询 021-63917080*8667 回T退订【米思米】', '尊敬的公司客户W03R3S,2019/8/31前,您只需再购买非模具类商品含税满3,000元即可参与活动，成功参与者即获小米充电宝1个！特选客户专享，转发无效。活动详询 021-63917080*8667 回T退订【米思米】', '16913506687', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (5, 'mar19111300484921461287', 4, 'W01SEA客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', 'W01SEA客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '16621730946', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (6, 'mar19111300484952720375', 4, '45客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '45客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '15201930079', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (7, 'mar19111300484951755893', 4, '45客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '45客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13671763755', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (8, 'mar19111300484967607257', 4, '49客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '49客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '18964746902', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (9, 'mar19111300484994442538', 4, '472客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '472客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13584966196', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (10, 'mar19111300484987958990', 4, '472客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '472客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '18914965198', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (11, 'mar19111300484938246780', 4, '472客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '472客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '15962535136', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (12, 'mar19111300484924279277', 4, '489客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '489客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13916464349', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (13, 'mar19111300485095390578', 4, '1351客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '1351客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '18721300586', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (14, 'mar19111300485003057778', 4, '1351客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '1351客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13817996164', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (15, 'mar19111300485016585661', 4, '1506客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '1506客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '15950917793', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (16, 'mar19111300485073500508', 4, '2613客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '2613客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13817774281', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (17, 'mar19111300485062979206', 4, '3266客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '3266客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13636659503', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (18, 'mar19111300485013450311', 4, '3675客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '3675客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13656224859', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (19, 'mar19111300485058989910', 4, '5234客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '5234客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13817505866', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (20, 'mar19111300485024657624', 4, '5833客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '5833客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13906215972', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (21, 'mar19111300485027313159', 4, '7844客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '7844客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '15021870368', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (22, 'mar19111300485096510647', 4, '8126客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '8126客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '15605272801', '52.80.226.19', 1, 1, 1, 0, 0, 0);
INSERT INTO `messagesend`.`yx_user_send_task`(`id`, `task_no`, `uid`, `task_name`, `task_content`, `mobile_content`, `source`, `send_num`, `free_trial`, `send_status`, `update_time`, `create_time`, `delete_time`) VALUES (23, 'mar19111300485030720522', 4, '8198客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '8198客户专属定制电脑包大派送！10月25-31日,代理品牌商品或智选MRO品（对象商品详见官网首页搜索栏下黑色导航栏）买满2次，金额不限即可参与活动,成功参与即获1个！转发无效。详询021-63917080转8667,回T退订【米思米】', '13918735681', '52.80.226.19', 1, 1, 1, 0, 0, 0);




DROP TABLE IF EXISTS `yx_user_send_code_task`;
CREATE TABLE `yx_user_send_code_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `task_name` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `task_content` text COMMENT '发送内容',
  `mobile_content` mediumtext CHARACTER SET utf32 COMMENT '发送号码集合',
  `send_msg_id` varchar(255) DEFAULT '' COMMENT '请求回复msgid',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '请求源（ip）',
  `real_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实际数量',
  `send_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送数量',
  `send_length` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '短信长度',
  `free_trial` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1:需要审核;2:审核通过;3:审核不通过',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通道ID',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1：待发送,2:发送中;3:成功;4:失败',
  `submit_time` varchar(50) DEFAULT '' COMMENT 'CMPP接口提交时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='行业任务表';

DROP TABLE IF EXISTS `yx_sensitive_word`;
CREATE TABLE `yx_sensitive_word` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(255) DEFAULT '' COMMENT '敏感词',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `word` (`word`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='敏感词库';

DROP TABLE IF EXISTS `yx_user_multimedia_message`;
CREATE TABLE `yx_user_multimedia_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `mobile_content` text COMMENT '手机号集合',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '请求源（ip）',
  `real_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实际数量',
  `send_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送数量',
  `free_trial` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1:需要审核;2:审核通过;3:审核不通过',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通道ID',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1：待发送,2:发送中;3:成功;4:失败',
  `status_message` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '状态',
  `real_message` varchar(20) NOT NULL DEFAULT '' COMMENT '真实返回状态',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '预约发送时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `task_no_uid` (`task_no`,`uid`) USING BTREE,
  KEY `title` (`title`) USING BTREE,
  KEY `send_status` (`send_status`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='彩信主表';

DROP TABLE IF EXISTS `yx_user_multimedia_message_frame`;
CREATE TABLE `yx_user_multimedia_message_frame` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `multimedia_message_id` char(23) NOT NULL DEFAULT '' COMMENT '彩信id',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '文字内容',
  `image_path` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图片路径',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `multimedia_message_id` (`multimedia_message_id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='彩信副表（帧）';

DROP TABLE IF EXISTS `yx_user_multimedia_message_log`;
CREATE TABLE `yx_user_multimedia_message_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_no` char(23) NOT NULL DEFAULT '' COMMENT '任务编号',
  `task_id` char(23) NOT NULL DEFAULT '' COMMENT '任务id',
  `task_content` text COMMENT '发送内容',
  `mobile` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '接收手机',
  `source` varchar(50) NOT NULL DEFAULT '' COMMENT '请求源（ip）',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '通道id',
  `send_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '短信发送状态1：待发送,2:已发送;3:成功;4:失败',
  `source_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '网关状态1：待发送,2:已发送;3:成功;4:失败',
  `user_query_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '用户查询状态1:未获取;2:已获取',
  `status_message` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '状态',
  `real_message` varchar(20) NOT NULL DEFAULT '' COMMENT '真实返回状态',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `delete_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `task_no` (`task_no`,`task_id`) USING BTREE,
  KEY `mobile` (`mobile`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='彩信发送记录表';
