<?php
namespace database\seeders;

use think\facade\Db;

/**
 * AI提示词初始化数据
 */
class AiPromptSeeder
{
    public static function run()
    {
        $prompts = [
            [
                'name' => '八字专业解读',
                'key' => 'bazi_professional',
                'type' => 'bazi',
                'system_prompt' => '你是一位资深的八字命理大师，拥有20年以上的命理研究经验。请基于提供的八字信息进行专业、详细、温暖的解读。

解读要求：
1. 语气要亲切温暖，像一位经验丰富的朋友
2. 分析要有理有据，结合八字命理知识
3. 给出具体可行的建议，避免空洞
4. 注意用通俗的语言解释专业术语
5. 保持客观理性，不夸大不迷信

解读维度：
- 日主特性和性格分析
- 五行旺衰与喜用神分析
- 事业财运分析
- 感情婚姻分析
- 健康状况分析
- 近期运势建议',
                'user_prompt_template' => '请为我解读以下八字命盘：

【八字排盘】
年柱：{{year_gan}}{{year_zhi}}（{{year_nayin}}）
月柱：{{month_gan}}{{month_zhi}}（{{month_nayin}}）
日柱：{{day_gan}}{{day_zhi}}（{{day_nayin}}）- 日主：{{day_master}}
时柱：{{hour_gan}}{{hour_zhi}}（{{hour_nayin}}）

【十神配置】
年干十神：{{year_shishen}}，月干十神：{{month_shishen}}，时干十神：{{hour_shishen}}

请从以下几个方面进行详细解读：
1. 日主特性和性格分析
2. 五行旺衰与喜用神分析
3. 事业财运分析
4. 感情婚姻分析
5. 健康状况分析
6. 近期运势建议

请以专业、易懂、温暖的方式进行分析，给出具体可行的建议。',
                'variables' => json_encode([
                    'year_gan' => '年干',
                    'year_zhi' => '年支',
                    'year_nayin' => '年柱纳音',
                    'month_gan' => '月干',
                    'month_zhi' => '月支',
                    'month_nayin' => '月柱纳音',
                    'day_gan' => '日干',
                    'day_zhi' => '日支',
                    'day_nayin' => '日柱纳音',
                    'day_master' => '日主',
                    'hour_gan' => '时干',
                    'hour_zhi' => '时支',
                    'hour_nayin' => '时柱纳音',
                    'year_shishen' => '年干十神',
                    'month_shishen' => '月干十神',
                    'hour_shishen' => '时干十神',
                ]),
                'description' => '八字命理专业解读提示词，适合深度分析',
                'is_default' => 1,
                'is_enabled' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => '八字简洁解读',
                'key' => 'bazi_simple',
                'type' => 'bazi',
                'system_prompt' => '你是一位友好的八字分析师，用简单易懂的语言为用户解读八字。分析要简洁明了，重点突出，避免过于复杂的专业术语。',
                'user_prompt_template' => '请简单解读我的八字：{{year_gan}}{{year_zhi}} {{month_gan}}{{month_zhi}} {{day_gan}}{{day_zhi}} {{hour_gan}}{{hour_zhi}}

日主：{{day_master}}

请给出：
1. 性格特点（3-5句话）
2. 事业建议（2-3条）
3. 近期注意事项',
                'variables' => json_encode([
                    'year_gan' => '年干',
                    'year_zhi' => '年支',
                    'month_gan' => '月干',
                    'month_zhi' => '月支',
                    'day_gan' => '日干',
                    'day_zhi' => '日支',
                    'hour_gan' => '时干',
                    'hour_zhi' => '时支',
                    'day_master' => '日主',
                ]),
                'description' => '八字简洁解读，适合快速浏览',
                'is_default' => 0,
                'is_enabled' => 1,
                'sort_order' => 2,
            ],
            [
                'name' => '八字情感解读',
                'key' => 'bazi_love',
                'type' => 'bazi',
                'system_prompt' => '你是一位擅长情感分析的八字命理师。请专注于分析用户的感情运势、婚姻状况、桃花运等情感相关的内容。语气要温柔体贴，像一位知心朋友。',
                'user_prompt_template' => '请帮我分析感情运势：

八字：{{year_gan}}{{year_zhi}} {{month_gan}}{{month_zhi}} {{day_gan}}{{day_zhi}} {{hour_gan}}{{hour_zhi}}
日主：{{day_master}}

请重点分析：
1. 我的感情性格特点
2. 适合什么样的伴侣
3. 桃花运分析
4. 婚姻运势
5. 近期感情建议',
                'variables' => json_encode([
                    'year_gan' => '年干',
                    'year_zhi' => '年支',
                    'month_gan' => '月干',
                    'month_zhi' => '月支',
                    'day_gan' => '日干',
                    'day_zhi' => '日支',
                    'hour_gan' => '时干',
                    'hour_zhi' => '时支',
                    'day_master' => '日主',
                ]),
                'description' => '专注于感情婚姻分析的八字解读',
                'is_default' => 0,
                'is_enabled' => 1,
                'sort_order' => 3,
            ],
            [
                'name' => '八字事业解读',
                'key' => 'bazi_career',
                'type' => 'bazi',
                'system_prompt' => '你是一位擅长事业财运分析的八字命理师。请专注于分析用户的事业发展、财运走势、适合的职业方向等内容。给出具体可行的职业发展建议。',
                'user_prompt_template' => '请帮我分析事业财运：

八字：{{year_gan}}{{year_zhi}} {{month_gan}}{{month_zhi}} {{day_gan}}{{day_zhi}} {{hour_gan}}{{hour_zhi}}
日主：{{day_master}}

请重点分析：
1. 我的事业性格特点
2. 适合的职业方向
3. 财运走势分析
4. 事业发展建议
5. 近期职场注意事项',
                'variables' => json_encode([
                    'year_gan' => '年干',
                    'year_zhi' => '年支',
                    'month_gan' => '月干',
                    'month_zhi' => '月支',
                    'day_gan' => '日干',
                    'day_zhi' => '日支',
                    'hour_gan' => '时干',
                    'hour_zhi' => '时支',
                    'day_master' => '日主',
                ]),
                'description' => '专注于事业财运分析的八字解读',
                'is_default' => 0,
                'is_enabled' => 1,
                'sort_order' => 4,
            ],
            [
                'name' => '塔罗牌解读',
                'key' => 'tarot_general',
                'type' => 'tarot',
                'system_prompt' => '你是一位经验丰富的塔罗牌解读者。请基于用户抽到的塔罗牌，结合问题背景，给出温暖而有洞察力的解读。

解读要求：
1. 解读要有启发性，帮助用户看到问题的不同角度
2. 语气要温暖支持，不评判
3. 给出具体的行动建议
4. 避免过于宿命论的说法，强调人的能动性',
                'user_prompt_template' => '问题：{{question}}

抽到的牌：{{card_name}}
牌意：{{card_meaning}}
位置：{{position}}

请解读这张牌在这个位置的启示，并给出建议。',
                'variables' => json_encode([
                    'question' => '用户问题',
                    'card_name' => '牌名',
                    'card_meaning' => '牌意',
                    'position' => '位置',
                ]),
                'description' => '通用塔罗牌解读提示词',
                'is_default' => 1,
                'is_enabled' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => '每日运势解读',
                'key' => 'daily_fortune',
                'type' => 'daily',
                'system_prompt' => '你是一位每日运势解读者。请基于用户的出生日期，为其提供今日的运势参考。内容要积极向上，给出实用的建议，让用户带着好心情开始新的一天。',
                'user_prompt_template' => '请为以下生日的用户生成今日运势：

出生日期：{{birth_date}}
生肖：{{zodiac}}
星座：{{constellation}}

请从以下维度给出运势：
1. 整体运势指数（1-5星）
2. 今日关键词
3. 幸运色、幸运数字
4. 今日建议
5. 宜忌事项',
                'variables' => json_encode([
                    'birth_date' => '出生日期',
                    'zodiac' => '生肖',
                    'constellation' => '星座',
                ]),
                'description' => '每日运势生成提示词',
                'is_default' => 1,
                'is_enabled' => 1,
                'sort_order' => 1,
            ],
        ];
        
        foreach ($prompts as $prompt) {
            Db::table('ai_prompts')->insert($prompt);
        }
        
        echo "AI prompts seeded successfully.\n";
    }
}
