<?php
/**
 * 塔罗问题模板表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateQuestionTemplatesTable extends Migrator
{
    public function change()
    {
        $table = $this->table('question_templates', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('topic_id', 'string', ['limit' => 50, 'comment' => '话题ID'])
              ->addColumn('topic_name', 'string', ['limit' => 50, 'comment' => '话题名称'])
              ->addColumn('topic_icon', 'string', ['limit' => 20, 'comment' => '话题图标'])
              ->addColumn('question', 'string', ['limit' => 255, 'comment' => '问题模板'])
              ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否启用'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['topic_id'])
              ->addIndex(['is_active'])
              ->create();
    }
}
