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

-- -----------------------------
-- 业务功能表
-- -----------------------------

DROP TABLE IF EXISTS `yx_users`;
CREATE TABLE `yx_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级id',
  `passwd` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `nick_name` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `user_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '用户类型1.个人账户2.企业账户',
  `mobile` char(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'email',
  `money` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '剩余金额（现金）',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `p_uid`(`id`, `pid`) USING BTREE,
  UNIQUE INDEX `index_mobile`(`mobile`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

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
) ENGINE = InnoDB AUTO_INCREMENT = 440 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户con_id和uid关系' ROW_FORMAT = Dynamic;

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
) ENGINE = InnoDB AUTO_INCREMENT = 649 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '验证码发送日志' ROW_FORMAT = Dynamic;

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
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '统一价格', 
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '业务类型及定价' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_user_equities`;
CREATE TABLE `yx_user_equities` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '业务服务id',
  `business_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '统一价格',
  `agency_price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '代理价格',
  `num_balance` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '条数余额',
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
  `entity_responsible_person_identity_types` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '负责人证件类型(参照【主办单位证件类型】)',
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
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板内容',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '平台模板' ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `yx_user_model`;
CREATE TABLE `yx_user_model` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板',
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '短信模板内容',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1,提交申请;2,可用;3,审核通过;4,审核不通过;5,停用',
  `update_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `delete_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '用户自定义模板' ROW_FORMAT = Dynamic;