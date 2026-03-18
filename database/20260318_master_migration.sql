-- =============================================================
-- 太初命理网站 - 全量迁移主脚本
-- 创建时间：2026-03-18
-- 说明：按顺序执行所有迁移。幂等设计，可重复运行。
--       首次部署：先执行 backup/01_create_database.sql →
--                 backup/02_create_tables.sql →
--                 backup/03_insert_basic_data.sql →
--                 本脚本
-- =============================================================

SET NAMES utf8mb4;

SELECT '▶ [1/14] 管理员表与角色初始化...' AS migration_step;
SOURCE 20260317_create_admin_users_table.sql;

SELECT '▶ [2/14] 管理员权限补充...' AS migration_step;
SOURCE 20260318_fix_admin_role_permissions.sql;

SELECT '▶ [3/14] 反作弊相关表...' AS migration_step;
SOURCE 20260317_create_anticheat_tables.sql;

SELECT '▶ [4/14] 知识库文章表...' AS migration_step;
SOURCE 20260317_create_knowledge_tables.sql;

SELECT '▶ [5/14] 推送通知表...' AS migration_step;
SOURCE 20260317_create_notification_tables.sql;

SELECT '▶ [6/14] 神煞表...' AS migration_step;
SOURCE 20260317_create_shensha_table.sql;

SELECT '▶ [7/14] 网站统计表...' AS migration_step;
SOURCE 20260317_create_admin_stats_tables.sql;

SELECT '▶ [8/14] 黄历数据表...' AS migration_step;
SOURCE 20260318_create_almanac_table.sql;

SELECT '▶ [9/14] SEO配置表...' AS migration_step;
SOURCE 20260318_create_seo_tables.sql;

SELECT '▶ [10/14] 积分记录字段补齐...' AS migration_step;
SOURCE 20260317_add_points_record_compat_fields.sql;

SELECT '▶ [11/14] 积分审计字段补齐...' AS migration_step;
SOURCE 20260318_add_points_record_audit_fields.sql;

SELECT '▶ [12/14] 充值订单退款字段补齐...' AS migration_step;
SOURCE 20260317_add_recharge_order_refund_fields.sql;

SELECT '▶ [13/17] 旧表名统一（sms/payment -> tc_ 前缀）...' AS migration_step;
SOURCE 20260318_unify_table_names.sql;

SELECT '▶ [14/17] 缺失表创建（合婚/塔罗/六爻/系统配置等22张表）...' AS migration_step;
SOURCE 20260318_create_missing_tables.sql;

SELECT '▶ [15/17] 高频查询与清理索引补齐...' AS migration_step;
SOURCE 20260318_add_points_recharge_sms_indexes.sql;

-- 注意：fix_hehun_points_config 依赖 system_config 表，须在 create_missing_tables 之后执行
SELECT '▶ [16/17] 合婚功能积分配置写入 system_config...' AS migration_step;
SOURCE 20260318_fix_hehun_points_config.sql;

SELECT '▶ [17/17] 神煞历史乱码数据回填...' AS migration_step;
SOURCE 20260318_fix_shensha_display_encoding.sql;



SELECT '✅ 全量迁移完成' AS migration_result;

