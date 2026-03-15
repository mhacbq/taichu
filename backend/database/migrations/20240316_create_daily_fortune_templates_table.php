<?php
/**
 * 每日运势模板表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateDailyFortuneTemplatesTable extends Migrator
{
    public function change()
    {
        $table = $this->table('daily_fortune_templates', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('type', 'string', ['limit' => 30, 'comment' => '模板类型'])
              ->addColumn('category', 'string', ['limit' => 30, 'comment' => '分类'])
              ->addColumn('content', 'text', ['comment' => '内容'])
              ->addColumn('min_score', 'integer', ['default' => 0, 'comment' => '适用最低分'])
              ->addColumn('max_score', 'integer', ['default' => 100, 'comment' => '适用最高分'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否启用'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['type'])
              ->addIndex(['category'])
              ->addIndex(['is_active'])
              ->create();
    }
}
