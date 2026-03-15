<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * AI提示词模型
 */
class AiPrompt extends Model
{
    protected $table = 'ai_prompts';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'variables' => 'json',
        'model_params' => 'json',
        'sort_order' => 'integer',
        'is_enabled' => 'integer',
        'is_default' => 'integer',
        'usage_count' => 'integer',
    ];
    
    // 提示词类型
    const TYPE_BAZI = 'bazi';
    const TYPE_TAROT = 'tarot';
    const TYPE_DAILY = 'daily';
    const TYPE_GENERAL = 'general';
    
    const TYPES = [
        self::TYPE_BAZI => '八字解盘',
        self::TYPE_TAROT => '塔罗解读',
        self::TYPE_DAILY => '每日运势',
        self::TYPE_GENERAL => '通用对话',
    ];
    
    /**
     * 获取类型名称
     */
    public function getTypeNameAttr($value, $data): string
    {
        return self::TYPES[$data['type']] ?? '其他';
    }
    
    /**
     * 获取启用的提示词列表
     */
    public static function getEnabledList(string $type = null): array
    {
        $query = self::where('is_enabled', 1);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->order('sort_order', 'asc')
            ->order('created_at', 'desc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取默认提示词
     */
    public static function getDefault(string $type): ?self
    {
        return self::where('type', $type)
            ->where('is_enabled', 1)
            ->where('is_default', 1)
            ->find();
    }
    
    /**
     * 根据key获取提示词
     */
    public static function getByKey(string $key): ?self
    {
        return self::where('key', $key)
            ->where('is_enabled', 1)
            ->find();
    }
    
    /**
     * 渲染用户提示词模板
     */
    public function renderUserPrompt(array $variables = []): string
    {
        $template = $this->user_prompt_template ?: '';
        
        // 替换变量
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            $template = str_replace('{{ ' . $key . ' }}', (string) $value, $template);
        }
        
        return $template;
    }
    
    /**
     * 增加使用次数
     */
    public function incrementUsage(): void
    {
        $this->usage_count++;
        $this->save();
    }
    
    /**
     * 设置默认提示词
     */
    public static function setDefault(int $id, string $type): void
    {
        // 先取消该类型的所有默认
        self::where('type', $type)->update(['is_default' => 0]);
        
        // 设置新的默认
        $prompt = self::find($id);
        if ($prompt) {
            $prompt->is_default = 1;
            $prompt->save();
        }
    }
}
