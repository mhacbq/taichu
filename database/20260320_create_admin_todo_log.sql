-- 创建管理端待办日志表
CREATE TABLE IF NOT EXISTS `tc_admin_todo_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `todo_id` varchar(100) NOT NULL COMMENT '待办事项ID',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `todo_id` (`todo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理端待办操作日志';
