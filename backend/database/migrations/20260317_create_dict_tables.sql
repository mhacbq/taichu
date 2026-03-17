-- 创建字典管理相关表
CREATE TABLE IF NOT EXISTS `tc_admin_dict_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '字典名称',
  `type` varchar(100) NOT NULL COMMENT '字典类型',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态: 0禁用 1正常',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='字典类型表';

CREATE TABLE IF NOT EXISTS `tc_admin_dict_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dict_type` varchar(100) NOT NULL COMMENT '字典类型',
  `label` varchar(100) NOT NULL COMMENT '字典标签',
  `value` varchar(100) NOT NULL COMMENT '字典键值',
  `css_class` varchar(100) DEFAULT '' COMMENT '样式属性',
  `list_class` varchar(100) DEFAULT '' COMMENT '表格回显样式',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '是否默认: 0否 1是',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态: 0禁用 1正常',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dict_type` (`dict_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='字典数据表';

-- 插入一些初始字典数据
INSERT IGNORE INTO `tc_admin_dict_type` (`name`, `type`, `status`, `remark`) VALUES 
('用户性别', 'sys_user_sex', 1, '用户性别列表'),
('菜单状态', 'sys_show_hide', 1, '菜单显示状态列表'),
('系统状态', 'sys_normal_disable', 1, '系统开关状态列表');

INSERT IGNORE INTO `tc_admin_dict_data` (`dict_type`, `label`, `value`, `list_class`, `is_default`, `status`, `sort_order`) VALUES 
('sys_user_sex', '男', '1', '', 1, 1, 1),
('sys_user_sex', '女', '2', '', 0, 1, 2),
('sys_user_sex', '未知', '0', 'info', 0, 1, 3),
('sys_show_hide', '显示', '0', 'primary', 1, 1, 1),
('sys_show_hide', '隐藏', '1', 'danger', 0, 1, 2),
('sys_normal_disable', '正常', '0', 'primary', 1, 1, 1),
('sys_normal_disable', '停用', '1', 'danger', 0, 1, 2);
