<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateSmsConfigsTable extends Migrator
{
    /**
     * 创建短信配置表
     */
    public function change()
    {
        $table = $this->table('sms_configs', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('provider', 'string', ['limit' => 50, 'null' => false, 'default' => 'tencent', 'comment' => '短信服务商:tencent,aliyun'])
            ->addColumn('secret_id', 'string', ['limit' => 255, 'null' => false, 'comment' => '腾讯云SecretId'])
            ->addColumn('secret_key', 'string', ['limit' => 255, 'null' => false, 'comment' => '腾讯云SecretKey'])
            ->addColumn('sdk_app_id', 'string', ['limit' => 100, 'null' => false, 'comment' => '短信应用ID'])
            ->addColumn('sign_name', 'string', ['limit' => 100, 'null' => false, 'comment' => '短信签名'])
            ->addColumn('template_code', 'string', ['limit' => 100, 'null' => false, 'comment' => '验证码模板ID'])
            ->addColumn('template_register', 'string', ['limit' => 100, 'null' => true, 'comment' => '注册通知模板ID'])
            ->addColumn('template_reset', 'string', ['limit' => 100, 'null' => true, 'comment' => '密码重置模板ID'])
            ->addColumn('is_enabled', 'boolean', ['null' => false, 'default' => true, 'comment' => '是否启用'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['provider'], ['unique' => true])
            ->create();
    }
}
