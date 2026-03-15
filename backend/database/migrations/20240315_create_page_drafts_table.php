<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePageDraftsTable extends Migrator
{
    /**
     * 创建页面草稿表
     */
    public function change()
    {
        $table = $this->table('page_drafts', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('page_id', 'string', ['limit' => 100, 'null' => false, 'comment' => '页面唯一标识'])
            ->addColumn('admin_id', 'integer', ['null' => false, 'default' => 0, 'comment' => '管理员ID'])
            ->addColumn('content', 'json', ['null' => true, 'comment' => '页面内容（JSON格式）'])
            ->addColumn('settings', 'json', ['null' => true, 'comment' => '页面设置（JSON格式）'])
            ->addColumn('auto_save', 'boolean', ['null' => false, 'default' => true, 'comment' => '是否自动保存'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['page_id', 'admin_id'], ['unique' => true])
            ->addIndex(['updated_at'])
            ->create();
    }
}