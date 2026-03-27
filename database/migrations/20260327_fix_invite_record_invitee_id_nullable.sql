-- 修复 tc_invite_record 表 invitee_id 字段允许 NULL
-- 问题：getOrCreateInviteCode 创建邀请码记录时 invitee_id 默认为 0，
--       多个用户创建邀请码时触发 uk_invitee 唯一键冲突（Duplicate entry '0' for key 'uk_invitee'）
-- 修复：将 invitee_id 改为允许 NULL，MySQL 唯一键允许多个 NULL 值共存

ALTER TABLE `tc_invite_record`
    MODIFY COLUMN `invitee_id` INT UNSIGNED NULL DEFAULT NULL
        COMMENT '被邀请人ID（邀请码记录时为NULL，实际邀请后填写）';

-- 将已有的 invitee_id = 0 的邀请码记录（非真实邀请记录）更新为 NULL
UPDATE `tc_invite_record`
    SET `invitee_id` = NULL
    WHERE `invitee_id` = 0;
