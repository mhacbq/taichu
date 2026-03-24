-- 邀请记录表新增 reward_time 字段
-- 用于记录邀请积分发放时间，配合 points_reward > 0 实现幂等性保护
ALTER TABLE `tc_invite_record`
    ADD COLUMN `reward_time` DATETIME DEFAULT NULL COMMENT '积分发放时间' AFTER `points_reward`;
