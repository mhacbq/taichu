<?php
/**
 * 塔罗牌阵表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateTarotSpreadsTable extends Migrator
{
    public function change()
    {
        $table = $this->table('tarot_spreads', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('key', 'string', ['limit' => 50, 'comment' => '牌阵标识'])
              ->addColumn('name', 'string', ['limit' => 50, 'comment' => '牌阵名称'])
              ->addColumn('icon', 'string', ['limit' => 50, 'comment' => '图标'])
              ->addColumn('description', 'string', ['limit' => 255, 'comment' => '描述'])
              ->addColumn('card_count', 'integer', ['comment' => '抽牌数量'])
              ->addColumn('positions', 'json', ['comment' => '牌位说明'])
              ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否启用'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['key'], ['unique' => true])
              ->create();
    }
}
