-- =============================================================
-- 修复 tc_user.openid 空串唯一键阻塞手机号注册
-- 目标：允许未绑定微信的手机号用户使用 NULL，而不是默认空串 ''
-- 设计：幂等执行；仅做结构兼容与空值归一化，不触碰真实 openid 数据
-- =============================================================

SET NAMES utf8mb4;

-- 1. 先把历史空串归一化为 NULL，避免唯一键 uk_openid 被 '' 长期占住
UPDATE `tc_user`
SET `openid` = NULL
WHERE `openid` = '';

UPDATE `tc_user`
SET `unionid` = NULL
WHERE `unionid` = '';

-- 2. 调整列默认值：未绑定微信的手机号用户应保持 NULL
ALTER TABLE `tc_user`
    MODIFY COLUMN `openid` VARCHAR(100) NULL DEFAULT NULL COMMENT '微信openid，手机号注册用户保持 NULL 以避免唯一键被空串占用',
    MODIFY COLUMN `unionid` VARCHAR(100) NULL DEFAULT NULL COMMENT '微信unionid，未绑定时保持 NULL';
