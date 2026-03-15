<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePaymentConfigsTable extends Migrator
{
    /**
     * 创建微信支付配置表
     */
    public function change()
    {
        $table = $this->table('payment_configs', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('type', 'string', ['limit' => 50, 'null' => false, 'comment' => '支付类型:wechat_jsapi,wechat_native'])
            ->addColumn('mch_id', 'string', ['limit' => 100, 'null' => false, 'comment' => '商户号'])
            ->addColumn('app_id', 'string', ['limit' => 100, 'null' => false, 'comment' => '应用ID'])
            ->addColumn('api_key', 'string', ['limit' => 255, 'null' => false, 'comment' => 'API密钥'])
            ->addColumn('api_cert', 'text', ['null' => true, 'comment' => 'API证书内容'])
            ->addColumn('api_key_pem', 'text', ['null' => true, 'comment' => 'API证书私钥'])
            ->addColumn('notify_url', 'string', ['limit' => 500, 'null' => false, 'comment' => '支付回调地址'])
            ->addColumn('is_enabled', 'boolean', ['null' => false, 'default' => true, 'comment' => '是否启用'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['type'], ['unique' => true])
            ->create();
    }
}
