<?php
/**
 * 用户评价表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateTestimonialsTable extends Migrator
{
    public function change()
    {
        $table = $this->table('testimonials', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('name', 'string', ['limit' => 50, 'comment' => '用户姓名'])
              ->addColumn('avatar', 'string', ['limit' => 255, 'null' => true, 'comment' => '头像URL'])
              ->addColumn('content', 'text', ['comment' => '评价内容'])
              ->addColumn('service_type', 'string', ['limit' => 20, 'comment' => '服务类型：bazi,tarot,daily'])
              ->addColumn('rating', 'integer', ['default' => 5, 'comment' => '评分'])
              ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否显示'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['service_type'])
              ->addIndex(['is_active'])
              ->create();
    }
}
