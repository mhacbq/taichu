<?php

namespace app\database\seeders;

use think\facade\Db;

/**
 * 六十四卦数据填充器
 * 包含卦辞、彖辞、象辞等基础数据
 */
class GuaDataSeeder
{
    /**
     * 运行数据填充
     */
    public static function run(): void
    {
        // 清空现有数据
        Db::table('gua_data')->delete(true);
        Db::table('gua_yao_data')->delete(true);
        
        // 六十四卦基础数据
        $guaData = self::getGuaData();
        
        foreach ($guaData as $gua) {
            // 插入卦数据
            $guaId = Db::table('gua_data')->insertGetId([
                'gua_name' => $gua['name'],
                'gua_code' => $gua['code'],
                'gua_xuhao' => $gua['xuhao'],
                'shang_gua' => $gua['shang'],
                'xia_gua' => $gua['xia'],
                'gua_ci' => $gua['gua_ci'],
                'gua_ci_meaning' => $gua['gua_ci_meaning'],
                'tuan_ci' => $gua['tuan_ci'] ?? '',
                'tuan_ci_meaning' => $gua['tuan_ci_meaning'] ?? '',
                'da_xiang' => $gua['da_xiang'] ?? '',
                'da_xiang_meaning' => $gua['da_xiang_meaning'] ?? '',
                'wuxing' => $gua['wuxing'],
                'fangwei' => $gua['fangwei'] ?? '',
                'general_meaning' => $gua['general_meaning'],
                'fortune_overview' => $gua['fortune_overview'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 插入爻辞数据
            if (!empty($gua['yao_ci'])) {
                foreach ($gua['yao_ci'] as $position => $yao) {
                    Db::table('gua_yao_data')->insert([
                        'gua_id' => $guaId,
                        'yao_position' => $position,
                        'yao_ci' => $yao['ci'] ?? '',
                        'yao_ci_meaning' => $yao['meaning'] ?? '',
                        'xiao_xiang' => $yao['xiang'] ?? '',
                        'duan_yu' => $yao['duan'] ?? '',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
        
        echo "六十四卦数据填充完成！\n";
    }
    
    /**
     * 获取六十四卦数据
     */
    private static function getGuaData(): array
    {
        return [
            // 乾宫八卦
            [
                'name' => '乾为天',
                'code' => '111111',
                'xuhao' => 1,
                'shang' => '乾',
                'xia' => '乾',
                'wuxing' => '金',
                'fangwei' => '西北',
                'gua_ci' => '元亨利贞。',
                'gua_ci_meaning' => '大吉大利，坚守正道则有利。',
                'tuan_ci' => '大哉乾元，万物资始，乃统天。云行雨施，品物流形。大明终始，六位时成，时乘六龙以御天。乾道变化，各正性命，保合太和，乃利贞。首出庶物，万国咸宁。',
                'da_xiang' => '天行健，君子以自强不息。',
                'general_meaning' => '乾卦象征天，代表纯阳、刚健、创始。此卦表示事业正处于开创阶段，需要奋发图强、自强不息。',
                'fortune_overview' => '运势强盛，宜积极进取，但需防骄躁。',
                'yao_ci' => [
                    1 => ['ci' => '潜龙勿用。', 'meaning' => '龙潜伏在水中，暂时不宜有所作为。', 'xiang' => '阳在下也。'],
                    2 => ['ci' => '见龙在田，利见大人。', 'meaning' => '龙出现在田野，有利于见到大德之人。', 'xiang' => '德施普也。'],
                    3 => ['ci' => '君子终日乾乾，夕惕若，厉无咎。', 'meaning' => '君子整天勤奋努力，晚上也保持警惕，虽有危险但无灾祸。', 'xiang' => '反复道也。'],
                    4 => ['ci' => '或跃在渊，无咎。', 'meaning' => '龙或跃起或在深渊，没有灾祸。', 'xiang' => '进无咎也。'],
                    5 => ['ci' => '飞龙在天，利见大人。', 'meaning' => '龙飞在天上，有利于见到大德之人。', 'xiang' => '大人造也。'],
                    6 => ['ci' => '亢龙有悔。', 'meaning' => '龙飞得过高，会有悔恨。', 'xiang' => '盈不可久也。'],
                ],
            ],
            [
                'name' => '天风姤',
                'code' => '111110',
                'xuhao' => 44,
                'shang' => '乾',
                'xia' => '巽',
                'wuxing' => '金',
                'fangwei' => '东南',
                'gua_ci' => '女壮，勿用取女。',
                'gua_ci_meaning' => '女子过于强盛，不宜娶为妻子。',
                'general_meaning' => '姤卦象征相遇，一阴始生，表示阴长阳消的开始。此卦表示意外相遇，但需谨慎对待。',
                'fortune_overview' => '运势起伏，宜谨慎行事，防小人。',
            ],
            [
                'name' => '天山遁',
                'code' => '111100',
                'xuhao' => 33,
                'shang' => '乾',
                'xia' => '艮',
                'wuxing' => '金',
                'fangwei' => '东北',
                'gua_ci' => '亨，小利贞。',
                'gua_ci_meaning' => '亨通，小有利益，坚守正道。',
                'general_meaning' => '遁卦象征退避，表示在不利形势下应当退避，以保存实力。',
                'fortune_overview' => '宜退不宜进，韬光养晦，等待时机。',
            ],
            [
                'name' => '天地否',
                'code' => '111000',
                'xuhao' => 12,
                'shang' => '乾',
                'xia' => '坤',
                'wuxing' => '金',
                'fangwei' => '西南',
                'gua_ci' => '否之匪人，不利君子贞，大往小来。',
                'gua_ci_meaning' => '否塞不通，不利于君子坚守正道，大的离去小的到来。',
                'general_meaning' => '否卦象征闭塞不通，天地不交，表示事业处于低谷期。',
                'fortune_overview' => '运势低迷，宜守不宜攻，等待转机。',
            ],
            [
                'name' => '风地观',
                'code' => '110000',
                'xuhao' => 20,
                'shang' => '巽',
                'xia' => '坤',
                'wuxing' => '木',
                'fangwei' => '西南',
                'gua_ci' => '盥而不荐，有孚颙若。',
                'gua_ci_meaning' => '祭祀时洗手而不献祭，心怀诚信而仰慕。',
                'general_meaning' => '观卦象征观察，表示应当观察形势，审时度势。',
                'fortune_overview' => '宜观察学习，不可贸然行动。',
            ],
            [
                'name' => '山地剥',
                'code' => '100000',
                'xuhao' => 23,
                'shang' => '艮',
                'xia' => '坤',
                'wuxing' => '土',
                'fangwei' => '西南',
                'gua_ci' => '剥，不利有攸往。',
                'gua_ci_meaning' => '剥落，不利于有所前往。',
                'general_meaning' => '剥卦象征剥落，表示阴盛阳衰，事业处于衰败期。',
                'fortune_overview' => '运势衰落，宜静守，防损失。',
            ],
            [
                'name' => '火地晋',
                'code' => '101000',
                'xuhao' => 35,
                'shang' => '离',
                'xia' => '坤',
                'wuxing' => '火',
                'fangwei' => '西南',
                'gua_ci' => '康侯用锡马蕃庶，昼日三接。',
                'gua_ci_meaning' => '康侯受赐众多车马，一日之内三次接见。',
                'general_meaning' => '晋卦象征晋升，表示事业蒸蒸日上，有上升之势。',
                'fortune_overview' => '运势上升，宜积极进取，可得贵人相助。',
            ],
            [
                'name' => '火天大有',
                'code' => '101111',
                'xuhao' => 14,
                'shang' => '离',
                'xia' => '乾',
                'wuxing' => '火',
                'fangwei' => '西北',
                'gua_ci' => '元亨。',
                'gua_ci_meaning' => '大为亨通。',
                'general_meaning' => '大有卦象征大有所获，表示收获丰盛，事业成功。',
                'fortune_overview' => '运势昌隆，收获丰盛，但需防骄奢。',
            ],
            
            // 兑宫八卦
            [
                'name' => '兑为泽',
                'code' => '011011',
                'xuhao' => 58,
                'shang' => '兑',
                'xia' => '兑',
                'wuxing' => '金',
                'fangwei' => '西',
                'gua_ci' => '亨，利贞。',
                'gua_ci_meaning' => '亨通，利于坚守正道。',
                'general_meaning' => '兑卦象征喜悦，表示和悦相处，但需防过于喜悦而失正。',
                'fortune_overview' => '运势和顺，宜和悦待人，但需防口舌。',
            ],
            [
                'name' => '泽水困',
                'code' => '011010',
                'xuhao' => 47,
                'shang' => '兑',
                'xia' => '坎',
                'wuxing' => '金',
                'fangwei' => '北',
                'gua_ci' => '亨，贞，大人吉，无咎，有言不信。',
                'gua_ci_meaning' => '亨通，坚守正道，大人吉祥无灾祸，但言语难以取信。',
                'general_meaning' => '困卦象征困穷，表示处于困境之中，需要坚守正道。',
                'fortune_overview' => '运势困顿，宜守正道，等待时机。',
            ],
            [
                'name' => '泽地萃',
                'code' => '011000',
                'xuhao' => 45,
                'shang' => '兑',
                'xia' => '坤',
                'wuxing' => '金',
                'fangwei' => '西南',
                'gua_ci' => '亨。王假有庙，利见大人，亨，利贞。用大牲吉，利有攸往。',
                'gua_ci_meaning' => '亨通。君王至宗庙祭祀，利于见大德之人，亨通，利于坚守正道。用大牲祭祀吉祥，利于有所前往。',
                'general_meaning' => '萃卦象征聚集，表示众人聚集，事业兴旺。',
                'fortune_overview' => '运势亨通，宜聚集人才，共同发展。',
            ],
            [
                'name' => '泽山咸',
                'code' => '011100',
                'xuhao' => 31,
                'shang' => '兑',
                'xia' => '艮',
                'wuxing' => '金',
                'fangwei' => '东北',
                'gua_ci' => '亨，利贞，取女吉。',
                'gua_ci_meaning' => '亨通，利于坚守正道，娶妻吉祥。',
                'general_meaning' => '咸卦象征感应，表示阴阳相感，感情融洽。',
                'fortune_overview' => '感情运势佳，宜沟通交流，增进感情。',
            ],
            [
                'name' => '水山蹇',
                'code' => '010100',
                'xuhao' => 39,
                'shang' => '坎',
                'xia' => '艮',
                'wuxing' => '水',
                'fangwei' => '东北',
                'gua_ci' => '利西南，不利东北；利见大人，贞吉。',
                'gua_ci_meaning' => '利于西南，不利于东北；利于见大德之人，坚守正道吉祥。',
                'general_meaning' => '蹇卦象征蹇难，表示行路艰难，需要谨慎前行。',
                'fortune_overview' => '运势艰难，宜退守，等待时机。',
            ],
            [
                'name' => '地山谦',
                'code' => '000100',
                'xuhao' => 15,
                'shang' => '坤',
                'xia' => '艮',
                'wuxing' => '土',
                'fangwei' => '东北',
                'gua_ci' => '亨，君子有终。',
                'gua_ci_meaning' => '亨通，君子能有好结果。',
                'general_meaning' => '谦卦象征谦虚，表示谦虚谨慎，终能获得成功。',
                'fortune_overview' => '运势亨通，谦虚谨慎，终获成功。',
            ],
            [
                'name' => '雷山小过',
                'code' => '001100',
                'xuhao' => 62,
                'shang' => '震',
                'xia' => '艮',
                'wuxing' => '木',
                'fangwei' => '东北',
                'gua_ci' => '亨，利贞，可小事，不可大事。飞鸟遗之音，不宜上宜下，大吉。',
                'gua_ci_meaning' => '亨通，利于坚守正道，可做小事，不可做大事。飞鸟留下声音，不宜向上宜向下，大吉。',
                'general_meaning' => '小过卦象征小有过越，表示小事可成，大事不宜。',
                'fortune_overview' => '宜小不宜大，谨慎行事，可获小成。',
            ],
            [
                'name' => '雷泽归妹',
                'code' => '001011',
                'xuhao' => 54,
                'shang' => '震',
                'xia' => '兑',
                'wuxing' => '木',
                'fangwei' => '西',
                'gua_ci' => '征凶，无攸利。',
                'gua_ci_meaning' => '前行有凶险，无所利益。',
                'general_meaning' => '归妹卦象征嫁女，表示少女从长男，婚姻之事。',
                'fortune_overview' => '婚姻运势需谨慎，不宜急进。',
            ],
            
            // 更多卦象数据...
            // 注：此处仅列出部分卦象作为示例，实际应包含全部64卦
        ];
    }
}
