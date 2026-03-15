<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateSmsCodesTable extends Migrator
{
    /**
     * 创建短信验证码表
     */
    public function change()
    {
        $table = $this->table('sms_codes', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('phone', 'string', ['limit' => 20, 'null' => false, 'comment' => '手机号'])
            ->addColumn('code', 'string', ['limit' => 10, 'null' => false, 'comment' => '验证码'])
            ->addColumn('type', 'string', ['limit' => 50, 'null' => false, 'default' => 'register', 'comment' => '验证码类型:register,login,reset'])
            ->addColumn('expire_time', 'datetime', ['null' => false, 'comment' => '过期时间'])
            ->addColumn('is_used', 'boolean', ['null' => false, 'default' => false, 'comment' => '是否已使用'])
            ->addColumn('ip', 'string', ['limit' => 50, 'null' => true, 'comment' => '发送IP'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addIndex(['phone', 'type'])
            ->addIndex(['code'])
            ->addIndex(['expire_time'])
            ->addIndex(['created_at'])
            ->create();
    }
}
