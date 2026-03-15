<?php
namespace database\seeders;

use think\facade\Db;

/**
 * 塔罗牌初始化数据
 */
class TarotSeeder
{
    public static function run()
    {
        // 大阿尔卡那牌
        $majorArcana = [
            ['id' => 0, 'name' => '愚者', 'name_en' => 'The Fool', 'keywords' => '开始,自由,冒险', 'is_major' => 1],
            ['id' => 1, 'name' => '魔术师', 'name_en' => 'The Magician', 'keywords' => '创造力,行动力,自信', 'is_major' => 1],
            ['id' => 2, 'name' => '女祭司', 'name_en' => 'The High Priestess', 'keywords' => '直觉,神秘,内在智慧', 'is_major' => 1],
            ['id' => 3, 'name' => '皇后', 'name_en' => 'The Empress', 'keywords' => '丰饶,母性,创造力', 'is_major' => 1],
            ['id' => 4, 'name' => '皇帝', 'name_en' => 'The Emperor', 'keywords' => '权威,稳定,控制', 'is_major' => 1],
            ['id' => 5, 'name' => '教皇', 'name_en' => 'The Hierophant', 'keywords' => '传统,教导,信仰', 'is_major' => 1],
            ['id' => 6, 'name' => '恋人', 'name_en' => 'The Lovers', 'keywords' => '爱情,选择,和谐', 'is_major' => 1],
            ['id' => 7, 'name' => '战车', 'name_en' => 'The Chariot', 'keywords' => '意志力,胜利,决心', 'is_major' => 1],
            ['id' => 8, 'name' => '力量', 'name_en' => 'Strength', 'keywords' => '勇气,耐心,内在力量', 'is_major' => 1],
            ['id' => 9, 'name' => '隐者', 'name_en' => 'The Hermit', 'keywords' => '内省,独处,寻找真理', 'is_major' => 1],
            ['id' => 10, 'name' => '命运之轮', 'name_en' => 'Wheel of Fortune', 'keywords' => '命运,变化,周期', 'is_major' => 1],
            ['id' => 11, 'name' => '正义', 'name_en' => 'Justice', 'keywords' => '公正,平衡,因果', 'is_major' => 1],
            ['id' => 12, 'name' => '倒吊人', 'name_en' => 'The Hanged Man', 'keywords' => '牺牲,等待,新视角', 'is_major' => 1],
            ['id' => 13, 'name' => '死神', 'name_en' => 'Death', 'keywords' => '结束,转变,新生', 'is_major' => 1],
            ['id' => 14, 'name' => '节制', 'name_en' => 'Temperance', 'keywords' => '平衡,调和,中庸', 'is_major' => 1],
            ['id' => 15, 'name' => '恶魔', 'name_en' => 'The Devil', 'keywords' => '欲望,束缚,物质主义', 'is_major' => 1],
            ['id' => 16, 'name' => '塔', 'name_en' => 'The Tower', 'keywords' => '突变,觉醒,破坏', 'is_major' => 1],
            ['id' => 17, 'name' => '星星', 'name_en' => 'The Star', 'keywords' => '希望,灵感,宁静', 'is_major' => 1],
            ['id' => 18, 'name' => '月亮', 'name_en' => 'The Moon', 'keywords' => '幻想,恐惧,潜意识', 'is_major' => 1],
            ['id' => 19, 'name' => '太阳', 'name_en' => 'The Sun', 'keywords' => '成功,活力,快乐', 'is_major' => 1],
            ['id' => 20, 'name' => '审判', 'name_en' => 'Judgement', 'keywords' => '重生,觉醒,评价', 'is_major' => 1],
            ['id' => 21, 'name' => '世界', 'name_en' => 'The World', 'keywords' => '完成,圆满,成就', 'is_major' => 1],
        ];
        
        foreach ($majorArcana as $card) {
            $card['is_enabled'] = 1;
            Db::table('tarot_cards')->insert($card);
        }
        
        // 初始化塔罗牌阵
        $spreads = [
            [
                'name' => '单张牌',
                'type' => 'single',
                'description' => '最简单的牌阵，抽取一张牌回答一个具体问题',
                'card_count' => 1,
                'positions' => json_encode([['name' => '答案', 'meaning' => '对问题的直接回答']]),
                'is_free' => 1,
                'points_required' => 10,
                'sort_order' => 1,
                'is_enabled' => 1,
            ],
            [
                'name' => '三张牌（过去现在未来）',
                'type' => 'three',
                'description' => '经典的三张牌阵，揭示事情的发展脉络',
                'card_count' => 3,
                'positions' => json_encode([
                    ['name' => '过去', 'meaning' => '过去的影响和根源'],
                    ['name' => '现在', 'meaning' => '当前的状况'],
                    ['name' => '未来', 'meaning' => '可能的发展趋势'],
                ]),
                'is_free' => 1,
                'points_required' => 20,
                'sort_order' => 2,
                'is_enabled' => 1,
            ],
            [
                'name' => '三张牌（身心灵）',
                'type' => 'three',
                'description' => '从身体、心理、精神三个层面解读',
                'card_count' => 3,
                'positions' => json_encode([
                    ['name' => '身体', 'meaning' => '身体层面和实际行动'],
                    ['name' => '心理', 'meaning' => '情绪和想法'],
                    ['name' => '精神', 'meaning' => '深层的精神需求'],
                ]),
                'is_free' => 0,
                'points_required' => 20,
                'sort_order' => 3,
                'is_enabled' => 1,
            ],
            [
                'name' => '凯尔特十字',
                'type' => 'celtic',
                'description' => '最全面的牌阵之一，深入分析问题的各个方面',
                'card_count' => 10,
                'positions' => json_encode([
                    ['name' => '现状', 'meaning' => '当前状况的核心'],
                    ['name' => '阻碍', 'meaning' => '面临的挑战或阻碍'],
                    ['name' => '基础', 'meaning' => '问题的根源基础'],
                    ['name' => '过去', 'meaning' => '过去的影响'],
                    ['name' => '未来', 'meaning' => '近期的走向'],
                    ['name' => '自我', 'meaning' => '你的态度和感受'],
                    ['name' => '环境', 'meaning' => '外部环境的影响'],
                    ['name' => '希望', 'meaning' => '你的期望或恐惧'],
                    ['name' => '结果', 'meaning' => '最终结果'],
                    ['name' => '指引', 'meaning' => '给你的建议'],
                ]),
                'is_free' => 0,
                'points_required' => 50,
                'sort_order' => 4,
                'is_enabled' => 1,
            ],
        ];
        
        foreach ($spreads as $spread) {
            Db::table('tarot_spreads')->insert($spread);
        }
        
        // 初始化问题模板
        $questions = [
            // 感情类
            ['category' => 'love', 'question' => '我和TA的感情发展会如何？', 'sort_order' => 1, 'is_enabled' => 1],
            ['category' => 'love', 'question' => '我什么时候能遇到对的人？', 'sort_order' => 2, 'is_enabled' => 1],
            ['category' => 'love', 'question' => '我们的关系出了什么问题？', 'sort_order' => 3, 'is_enabled' => 1],
            ['category' => 'love', 'question' => '我应该主动表白吗？', 'sort_order' => 4, 'is_enabled' => 1],
            ['category' => 'love', 'question' => '前任还会回来吗？', 'sort_order' => 5, 'is_enabled' => 1],
            
            // 事业类
            ['category' => 'career', 'question' => '我现在的职业发展正确吗？', 'sort_order' => 6, 'is_enabled' => 1],
            ['category' => 'career', 'question' => '我应该跳槽吗？', 'sort_order' => 7, 'is_enabled' => 1],
            ['category' => 'career', 'question' => '我能得到晋升机会吗？', 'sort_order' => 8, 'is_enabled' => 1],
            ['category' => 'career', 'question' => '我适合创业吗？', 'sort_order' => 9, 'is_enabled' => 1],
            ['category' => 'career', 'question' => '这份工作适合我吗？', 'sort_order' => 10, 'is_enabled' => 1],
            
            // 学业类
            ['category' => 'study', 'question' => '我能考上理想的学校吗？', 'sort_order' => 11, 'is_enabled' => 1],
            ['category' => 'study', 'question' => '我的考试能通过吗？', 'sort_order' => 12, 'is_enabled' => 1],
            ['category' => 'study', 'question' => '我适合学什么专业？', 'sort_order' => 13, 'is_enabled' => 1],
            
            // 抉择类
            ['category' => 'choice', 'question' => '我应该选择A还是B？', 'sort_order' => 14, 'is_enabled' => 1],
            ['category' => 'choice', 'question' => '这个决定对吗？', 'sort_order' => 15, 'is_enabled' => 1],
            
            // 生活类
            ['category' => 'life', 'question' => '我最近的状态怎么样？', 'sort_order' => 16, 'is_enabled' => 1],
            ['category' => 'life', 'question' => '我需要注意什么？', 'sort_order' => 17, 'is_enabled' => 1],
        ];
        
        foreach ($questions as $q) {
            Db::table('question_templates')->insert($q);
        }
        
        echo "Tarot data seeded successfully.\n";
    }
}