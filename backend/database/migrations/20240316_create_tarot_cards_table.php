<?php
/**
 * 塔罗牌信息表
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateTarotCardsTable extends Migrator
{
    public function change()
    {
        $table = $this->table('tarot_cards', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('name', 'string', ['limit' => 50, 'comment' => '牌名'])
              ->addColumn('name_en', 'string', ['limit' => 50, 'null' => true, 'comment' => '英文名'])
              ->addColumn('number', 'integer', ['comment' => '牌号'])
              ->addColumn('arcana', 'string', ['limit' => 20, 'comment' => '大/小阿尔卡纳：major,minor'])
              ->addColumn('suit', 'string', ['limit' => 20, 'null' => true, 'comment' => '花色：cups,wands,swords,pentacles'])
              ->addColumn('element', 'string', ['limit' => 20, 'comment' => '元素：火,水,木,金,土,风'])
              ->addColumn('emoji', 'string', ['limit' => 10, 'comment' => '表情符号'])
              ->addColumn('color', 'string', ['limit' => 20, 'comment' => '颜色'])
              ->addColumn('keywords', 'string', ['limit' => 255, 'comment' => '关键词'])
              ->addColumn('meaning', 'text', ['comment' => '基本含义'])
              ->addColumn('upright_meaning', 'text', ['comment' => '正位含义'])
              ->addColumn('reversed_meaning', 'text', ['comment' => '逆位含义'])
              ->addColumn('advice', 'text', ['comment' => '建议'])
              ->addColumn('career_meaning', 'text', ['null' => true, 'comment' => '事业含义'])
              ->addColumn('love_meaning', 'text', ['null' => true, 'comment' => '感情含义'])
              ->addColumn('wealth_meaning', 'text', ['null' => true, 'comment' => '财富含义'])
              ->addColumn('is_active', 'boolean', ['default' => 1, 'comment' => '是否启用'])
              ->addColumn('created_at', 'datetime')
              ->addColumn('updated_at', 'datetime')
              ->addIndex(['number'])
              ->addIndex(['arcana'])
              ->addIndex(['element'])
              ->create();
    }
}
