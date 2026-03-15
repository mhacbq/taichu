<?php

declare(strict_types=1);

use think\migration\Migrator;
use think\migration\db\Column;

/**
 * AI提示词管理表
 */
class CreateAiPromptsTable extends Migrator
{
    public function change()
    {
        $table = $this->table('ai_prompts', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('name', 'string', ['limit' => 100, 'comment' => '提示词名称'])
            ->addColumn('key', 'string', ['limit' => 50, 'comment' => '提示词标识'])
            ->addColumn('type', 'string', ['limit' => 20, 'comment' => '类型：bazi/tarot/daily/general'])
            ->addColumn('system_prompt', 'text', ['comment' => '系统提示词'])
            ->addColumn('user_prompt_template', 'text', ['null' => true, 'comment' => '用户提示词模板'])
            ->addColumn('variables', 'json', ['null' => true, 'comment' => '变量定义'])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'comment' => '提示词说明'])
            ->addColumn('model_params', 'json', ['null' => true, 'comment' => '模型参数配置'])
            ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('is_enabled', 'tinyinteger', ['default' => 1, 'comment' => '是否启用'])
            ->addColumn('is_default', 'tinyinteger', ['default' => 0, 'comment' => '是否默认'])
            ->addColumn('usage_count', 'integer', ['default' => 0, 'comment' => '使用次数'])
            ->addColumn('created_by', 'integer', ['default' => 0, 'comment' => '创建人'])
            ->addColumn('updated_by', 'integer', ['default' => 0, 'comment' => '更新人'])
            ->addTimestamps()
            ->addIndex(['key'], ['unique' => true])
            ->addIndex(['type'])
            ->addIndex(['is_enabled'])
            ->addIndex(['is_default'])
            ->create();
    }
}
