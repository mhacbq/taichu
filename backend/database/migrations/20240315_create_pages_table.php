<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePagesTable extends Migrator
{
    /**
     * 创建页面表
     */
    public function change()
    {
        $table = $this->table('pages', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('page_id', 'string', ['limit' => 100, 'null' => false, 'comment' => '页面唯一标识'])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false, 'default' => '', 'comment' => '页面标题'])
            ->addColumn('description', 'string', ['limit' => 500, 'null' => false, 'default' => '', 'comment' => '页面描述'])
            ->addColumn('content', 'json', ['null' => true, 'comment' => '页面内容（JSON格式）'])
            ->addColumn('settings', 'json', ['null' => true, 'comment' => '页面设置（JSON格式）'])
            ->addColumn('version', 'integer', ['null' => false, 'default' => 1, 'comment' => '版本号'])
            ->addColumn('status', 'string', ['limit' => 20, 'null' => false, 'default' => 'draft', 'comment' => '状态:published/draft/hidden'])
            ->addColumn('seo_title', 'string', ['limit' => 255, 'null' => false, 'default' => '', 'comment' => 'SEO标题'])
            ->addColumn('seo_keywords', 'string', ['limit' => 500, 'null' => false, 'default' => '', 'comment' => 'SEO关键词'])
            ->addColumn('seo_description', 'string', ['limit' => 1000, 'null' => false, 'default' => '', 'comment' => 'SEO描述'])
            ->addColumn('updated_by', 'integer', ['null' => false, 'default' => 0, 'comment' => '更新者ID'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['page_id'], ['unique' => true])
            ->addIndex(['status'])
            ->addIndex(['updated_at'])
            ->create();
    }
}