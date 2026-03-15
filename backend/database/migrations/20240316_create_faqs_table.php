<?php
/**
 * FAQ常见问题表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateFaqsTable extends Migrator
{
    public function change()
    {
        $table = $this->table('faqs', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('category', 'string', ['limit' => 50, 'comment' => '分类'])
              ->addColumn('category_icon', 'string', ['limit' => 20, 'default' => '📌', 'comment' => '分类图标'])
              ->addColumn('question', 'string', ['limit' => 255, 'comment' => '问题'])
              ->addColumn('answer', 'text', ['comment' => '答案'])
              ->addColumn('hot_tags', 'string', ['limit' => 255, 'null' => true, 'comment' => '热门标签，逗号分隔'])
              ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否显示'])
              ->addColumn('view_count', 'integer', ['default' => 0, 'comment' => '查看次数'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['category'])
              ->addIndex(['is_active'])
              ->create();
    }
}
