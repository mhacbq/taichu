<?php
namespace database\seeders;

use think\facade\Db;

/**
 * 网站内容初始化数据
 */
class SiteContentSeeder
{
    public static function run()
    {
        // 首页内容
        $homeContent = [
            // Hero区域
            ['key' => 'hero_title', 'value' => '在迷茫中找到方向', 'description' => '首页主标题', 'page' => 'home', 'sort_order' => 1],
            ['key' => 'hero_subtitle', 'value' => '不是预测命运，而是帮你更懂自己', 'description' => '首页副标题', 'page' => 'home', 'sort_order' => 2],
            ['key' => 'hero_cta_primary', 'value' => '开始排盘', 'description' => '主按钮文字', 'page' => 'home', 'sort_order' => 3],
            ['key' => 'hero_cta_secondary', 'value' => '塔罗占卜', 'description' => '次按钮文字', 'page' => 'home', 'sort_order' => 4],
            ['key' => 'hero_user_count', 'value' => '12000+', 'description' => '用户数量显示', 'page' => 'home', 'sort_order' => 5],
            ['key' => 'hero_user_count_text', 'value' => '用户信赖选择', 'description' => '用户数说明', 'page' => 'home', 'sort_order' => 6],
            
            // Features区域
            ['key' => 'features_title', 'value' => '为你提供的服务', 'description' => '服务标题', 'page' => 'home', 'sort_order' => 10],
            ['key' => 'feature_1_title', 'value' => '八字分析', 'description' => '服务1标题', 'page' => 'home', 'sort_order' => 11],
            ['key' => 'feature_1_desc', 'value' => '基于传统命理学，为你分析性格特质、人生走势，帮你更好地认识自己', 'description' => '服务1描述', 'page' => 'home', 'sort_order' => 12],
            ['key' => 'feature_2_title', 'value' => '塔罗测试', 'description' => '服务2标题', 'page' => 'home', 'sort_order' => 13],
            ['key' => 'feature_2_desc', 'value' => '用78张塔罗牌探索你的内心世界，为你提供新的思考角度', 'description' => '服务2描述', 'page' => 'home', 'sort_order' => 14],
            ['key' => 'feature_3_title', 'value' => '每日指南', 'description' => '服务3标题', 'page' => 'home', 'sort_order' => 15],
            ['key' => 'feature_3_desc', 'value' => '基于你的出生日期，为你提供每日运势参考，增添生活趣味', 'description' => '服务3描述', 'page' => 'home', 'sort_order' => 16],
            
            // Testimonials区域
            ['key' => 'testimonials_title', 'value' => '他们的故事', 'description' => '评价标题', 'page' => 'home', 'sort_order' => 20],
            
            // About区域
            ['key' => 'about_title', 'value' => '关于太初', 'description' => '关于标题', 'page' => 'home', 'sort_order' => 30],
            ['key' => 'about_desc', 'value' => '太初文化是一个致力于将传统文化与现代科技相结合的平台。我们相信，古老的智慧依然可以照亮现代人的生活。', 'description' => '关于描述', 'page' => 'home', 'sort_order' => 31],
            ['key' => 'about_mission_1', 'value' => '传承传统文化', 'description' => '使命1', 'page' => 'home', 'sort_order' => 32],
            ['key' => 'about_mission_2', 'value' => '结合AI技术', 'description' => '使命2', 'page' => 'home', 'sort_order' => 33],
            ['key' => 'about_mission_3', 'value' => '提供个性参考', 'description' => '使命3', 'page' => 'home', 'sort_order' => 34],
            ['key' => 'about_mission_4', 'value' => '让传统文化更有趣', 'description' => '使命4', 'page' => 'home', 'sort_order' => 35],
        ];
        
        foreach ($homeContent as $item) {
            Db::table('site_contents')->insertOrUpdate($item, ['key' => $item['key'], 'page' => 'home']);
        }
        
        // 初始化用户评价
        $testimonials = [
            ['name' => '小雨', 'avatar' => '', 'content' => '毕业后一直很迷茫，不知道选什么工作。八字分析帮我梳理了自己的性格特点，现在做着自己喜欢的设计工作！', 'service_type' => 'bazi', 'sort_order' => 1, 'is_enabled' => 1],
            ['name' => '阿杰', 'avatar' => '', 'content' => '和女朋友感情遇到瓶颈，塔罗给了我新的思考角度。虽然最后我们分手了，但我更清楚自己想要什么。', 'service_type' => 'tarot', 'sort_order' => 2, 'is_enabled' => 1],
            ['name' => '小陈', 'avatar' => '', 'content' => '每天早上看运势已经成了习惯，不管准不准，至少给了我一天的好心情！', 'service_type' => 'daily', 'sort_order' => 3, 'is_enabled' => 1],
            ['name' => '琳琳', 'avatar' => '', 'content' => '八字分析说的INFJ性格特征太准了！让我更接纳自己的敏感和内向，不再强迫自己去社交。', 'service_type' => 'bazi', 'sort_order' => 4, 'is_enabled' => 1],
            ['name' => '大鹏', 'avatar' => '', 'content' => '塔罗牌让我看到了工作中的盲点，原来我一直在逃避沟通。现在已经升职做主管了！', 'service_type' => 'tarot', 'sort_order' => 5, 'is_enabled' => 1],
            ['name' => '思思', 'avatar' => '', 'content' => '抱着试试的心态排了八字，大运分析让我提前做了准备，今年真的顺利很多！', 'service_type' => 'bazi', 'sort_order' => 6, 'is_enabled' => 1],
        ];
        
        foreach ($testimonials as $item) {
            Db::table('testimonials')->insert($item);
        }
        
        // 初始化FAQ
        $faqs = [
            ['category' => 'general', 'question' => '八字分析真的准吗？', 'answer' => '八字分析基于传统命理学，可以为你提供性格和人生走势的参考。但它不是绝对的预测，更多是帮助你更好地认识自己。人生的道路最终由你自己选择和创造。', 'sort_order' => 1, 'is_enabled' => 1],
            ['category' => 'general', 'question' => '塔罗牌是迷信吗？', 'answer' => '塔罗牌是一种心理投射工具，通过象征性的图像帮助你探索内心。它不是预测未来的魔法，而是提供新的思考角度和自我对话的方式。', 'sort_order' => 2, 'is_enabled' => 1],
            ['category' => 'bazi', 'question' => '排八字需要提供什么信息？', 'answer' => '需要提供准确的出生日期（年月日时）和出生地点。时间越准确，分析结果越有价值。如果不知道具体时辰，也可以只提供年月日。', 'sort_order' => 3, 'is_enabled' => 1],
            ['category' => 'tarot', 'question' => '塔罗牌可以问什么问题？', 'answer' => '塔罗适合问关于内心、选择、人际关系的问题。不建议问具体的医疗诊断或法律问题。问题越具体，得到的启示越有针对性。', 'sort_order' => 4, 'is_enabled' => 1],
            ['category' => 'account', 'question' => '如何注册账号？', 'answer' => '点击首页右上角的"注册"按钮，填写手机号、设置密码即可。注册后可以获得100积分的见面礼！', 'sort_order' => 5, 'is_enabled' => 1],
            ['category' => 'points', 'question' => '积分怎么获得？', 'answer' => '你可以通过每日签到（10-50积分）、首次测试（免费）、邀请好友（50积分）等方式获得积分。部分高级功能需要消耗积分。', 'sort_order' => 6, 'is_enabled' => 1],
            ['category' => 'points', 'question' => '测试一次需要多少积分？', 'answer' => '八字分析通常需要30积分，塔罗测试根据牌阵不同需要10-50积分，每日运势是免费的。首次使用有免费机会哦！', 'sort_order' => 7, 'is_enabled' => 1],
            ['category' => 'general', 'question' => '分析结果可以保存吗？', 'answer' => '可以的！登录账号后，所有的分析结果都会自动保存到你的个人中心，随时可以查看历史记录。', 'sort_order' => 8, 'is_enabled' => 1],
        ];
        
        foreach ($faqs as $item) {
            Db::table('faqs')->insert($item);
        }
        
        echo "Site content seeded successfully.\n";
    }
}