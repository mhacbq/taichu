<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePageVersionsTable extends Migrator
{
    /**
     * 创建页面版本表
     */
    public function change()
    {
        $table = $this->table('page_versions', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('page_id', 'string', ['limit' => 100, 'null' => false, 'comment' => '页面唯一标识'])
            ->addColumn('content', 'json', ['null' => true, 'comment' => '页面内容（JSON格式）'])
            ->addColumn('settings', 'json', ['null' => true, 'comment' => '页面设置（JSON格式）'])
            ->addColumn('version', 'integer', ['null' => false, 'default' => 1, 'comment' => '版本号'])
            ->addColumn('author_id', 'integer', ['null' => false, 'default' => 0, 'comment' => '作者ID'])
            ->addColumn('author_name', 'string', ['limit' => 100, 'null' => false, 'default' => '', 'comment' => '作者名称'])
            ->addColumn('author_avatar', 'string', ['limit' => 500, 'null' => false, 'default' => '', 'comment' => '作者头像'])
            ->addColumn('description', 'string', ['limit' => 500, 'null' => false, 'default' => '', 'comment' => '版本描述'])
            ->addColumn('auto_save', 'boolean', ['null' => false, 'default' => false, 'comment' => '是否自动保存'])
            ->addColumn('is_draft', 'boolean', ['null' => false, 'default' => false, 'comment' => '是否为草稿'])
            ->addColumn('block_count', 'integer', ['null' => false, 'default' => 0, 'comment' => '内容块数量'])
            ->addColumn('preview_count', 'integer', ['null' => false, 'default' => 0, 'comment' => '预览次数'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addIndex(['page_id'])
            ->addIndex(['created_at'])
            ->create();
    }
}