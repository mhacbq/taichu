<?php
/**
 * 网站内容管理表
 * 用于存储所有页面的文案、配置等
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateSiteContentsTable extends Migrator
{
    public function change()
    {
        $table = $this->table('site_contents', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('key', 'string', ['limit' => 100, 'comment' => '内容键名'])
              ->addColumn('page', 'string', ['limit' => 50, 'comment' => '所属页面'])
              ->addColumn('group', 'string', ['limit' => 50, 'default' => 'default', 'comment' => '内容分组'])
              ->addColumn('type', 'string', ['limit' => 20, 'default' => 'text', 'comment' => '内容类型：text,textarea,html,json,image'])
              ->addColumn('value', 'text', ['null' => true, 'comment' => '内容值'])
              ->addColumn('default_value', 'text', ['null' => true, 'comment' => '默认值'])
              ->addColumn('label', 'string', ['limit' => 100, 'comment' => '显示名称'])
              ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'comment' => '说明'])
              ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否启用'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['key'], ['unique' => true])
              ->addIndex(['page'])
              ->addIndex(['group'])
              ->create();
    }
}
