<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateRechargeOrdersTable extends Migrator
{
    /**
     * 创建充值订单表
     */
    public function change()
    {
        $table = $this->table('recharge_orders', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('order_no', 'string', ['limit' => 64, 'null' => false, 'comment' => '系统订单号'])
            ->addColumn('user_id', 'integer', ['null' => false, 'comment' => '用户ID'])
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false, 'comment' => '充值金额（元）'])
            ->addColumn('points', 'integer', ['null' => false, 'comment' => '获得积分'])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'pending', 'comment' => '订单状态:pending,paid,cancelled,refunded'])
            ->addColumn('payment_type', 'string', ['limit' => 50, 'null' => false, 'default' => 'wechat_jsapi', 'comment' => '支付方式'])
            ->addColumn('pay_order_no', 'string', ['limit' => 100, 'null' => true, 'comment' => '微信支付订单号'])
            ->addColumn('pay_time', 'datetime', ['null' => true, 'comment' => '支付时间'])
            ->addColumn('expire_time', 'datetime', ['null' => false, 'comment' => '订单过期时间'])
            ->addColumn('client_ip', 'string', ['limit' => 50, 'null' => true, 'comment' => '客户端IP'])
            ->addColumn('user_agent', 'string', ['limit' => 500, 'null' => true, 'comment' => '用户代理'])
            ->addColumn('callback_data', 'json', ['null' => true, 'comment' => '回调数据'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['order_no'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addIndex(['status'])
            ->addIndex(['pay_order_no'])
            ->addIndex(['created_at'])
            ->create();
    }
}
