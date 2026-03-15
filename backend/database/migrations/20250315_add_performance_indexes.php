<?php

declare(strict_types=1);

use think\migration\Migrator;
use think\migration\db\Column;

class AddPerformanceIndexes extends Migrator
{
    /**
     * 添加性能优化索引
     * 根据查询频率和慢查询日志分析添加
     */
    public function change()
    {
        // tc_user 表索引优化
        $this->table('tc_user')
            ->addIndex(['openid'], ['name' => 'idx_openid'])
            ->addIndex(['phone'], ['name' => 'idx_phone'])
            ->addIndex(['is_vip', 'vip_expire_at'], ['name' => 'idx_vip_status'])
            ->addIndex(['created_at'], ['name' => 'idx_created_at'])
            ->update();
        
        // tc_bazi_record 表索引优化
        $this->table('tc_bazi_record')
            ->addIndex(['user_id', 'created_at'], ['name' => 'idx_user_created'])
            ->addIndex(['birth_date'], ['name' => 'idx_birth_date'])
            ->update();
        
        // tc_points_record 表索引优化
        $this->table('tc_points_record')
            ->addIndex(['user_id', 'created_at'], ['name' => 'idx_user_created'])
            ->addIndex(['type'], ['name' => 'idx_type'])
            ->update();
        
        // tc_invite_record 表索引优化
        $this->table('tc_invite_record')
            ->addIndex(['inviter_id', 'created_at'], ['name' => 'idx_inviter_created'])
            ->addIndex(['invitee_id'], ['name' => 'idx_invitee'])
            ->addIndex(['invite_code'], ['name' => 'idx_invite_code'])
            ->update();
        
        // tc_hehun_record 表索引优化
        $this->table('tc_hehun_record')
            ->addIndex(['user_id', 'created_at'], ['name' => 'idx_user_created'])
            ->update();
        
        // tc_daily_fortune 表索引优化
        $this->table('tc_daily_fortune')
            ->addIndex(['date'], ['name' => 'idx_date'])
            ->update();
        
        // tc_checkin_record 表索引优化
        $this->table('tc_checkin_record')
            ->addIndex(['user_id', 'date'], ['name' => 'idx_user_date'])
            ->update();
    }
    
    /**
     * 回滚操作
     */
    public function down()
    {
        // tc_user 表
        $this->table('tc_user')
            ->removeIndexByName('idx_openid')
            ->removeIndexByName('idx_phone')
            ->removeIndexByName('idx_vip_status')
            ->removeIndexByName('idx_created_at')
            ->update();
        
        // tc_bazi_record 表
        $this->table('tc_bazi_record')
            ->removeIndexByName('idx_user_created')
            ->removeIndexByName('idx_birth_date')
            ->update();
        
        // tc_points_record 表
        $this->table('tc_points_record')
            ->removeIndexByName('idx_user_created')
            ->removeIndexByName('idx_type')
            ->update();
        
        // tc_invite_record 表
        $this->table('tc_invite_record')
            ->removeIndexByName('idx_inviter_created')
            ->removeIndexByName('idx_invitee')
            ->removeIndexByName('idx_invite_code')
            ->update();
        
        // tc_hehun_record 表
        $this->table('tc_hehun_record')
            ->removeIndexByName('idx_user_created')
            ->update();
        
        // tc_daily_fortune 表
        $this->table('tc_daily_fortune')
            ->removeIndexByName('idx_date')
            ->update();
        
        // tc_checkin_record 表
        $this->table('tc_checkin_record')
            ->removeIndexByName('idx_user_date')
            ->update();
    }
}