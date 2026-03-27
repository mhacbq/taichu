-- =====================================================
-- 塔罗牌维度含义数据（感情/事业/健康/财运）
-- 对应表：tc_tarot_card
-- 创建时间：2026-03-27
-- 说明：幂等 UPDATE，可重复执行
-- =====================================================

-- =====================================================
-- 大阿卡纳 22张
-- =====================================================
UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情上迎来全新开始，以开放纯真的心态去爱，可能遇到命中注定的相遇',
                           `love_reversed`   = '感情中过于冲动，不考虑后果，可能因轻率而错失真爱或造成伤害',
                           `career_meaning`  = '事业上迎来新机遇，勇于尝试新领域，创业或转行的好时机',
                           `career_reversed` = '工作中缺乏计划，冲动行事，可能因准备不足而失败',
                           `health_meaning`  = '身体状态充满活力，适合开始新的健康计划，保持积极乐观',
                           `health_reversed` = '健康方面需注意意外伤害，不要忽视身体信号，避免冒险行为',
                           `wealth_meaning`  = '财运上有意外之财的可能，但需谨慎投资，不要盲目冒险',
                           `wealth_reversed` = '财务上因冲动消费或投资失误而损失，需要更谨慎的财务规划'
WHERE `name` = '愚者' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中主动出击，用魅力和技巧吸引对方，关系充满激情与创意',
                           `love_reversed`   = '感情中可能存在欺骗或操控，对方或自己言行不一，需警惕',
                           `career_meaning`  = '事业上能力出众，善用资源，有能力将想法变为现实，升职加薪可期',
                           `career_reversed` = '工作中技能不足或缺乏自信，可能存在欺骗行为，需提升专业能力',
                           `health_meaning`  = '精力充沛，身心协调，适合学习新技能，健康状况良好',
                           `health_reversed` = '健康方面可能因过度自信而忽视问题，需要更全面的健康检查',
                           `wealth_meaning`  = '财运旺盛，善于把握商机，投资有回报，财务管理能力强',
                           `wealth_reversed` = '财务上可能因判断失误或被欺骗而损失，需谨慎对待投资建议'
WHERE `name` = '魔术师' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要倾听内心，对方可能有隐藏的情感，关系充满神秘感',
                           `love_reversed`   = '感情中存在秘密或误解，直觉告诉你有些事情不对劲，需要沟通',
                           `career_meaning`  = '工作中依靠直觉和内在智慧，适合研究、咨询、教育等领域',
                           `career_reversed` = '工作中信息不透明，可能有隐藏的竞争或阴谋，需保持警觉',
                           `health_meaning`  = '身体与心灵需要平衡，多关注内在感受，冥想和休息有益健康',
                           `health_reversed` = '健康方面可能有隐藏的问题尚未被发现，建议进行全面检查',
                           `wealth_meaning`  = '财运需要耐心等待，不宜急于求成，隐性收入或意外财富可能出现',
                           `wealth_reversed` = '财务上信息不透明，可能有隐藏的损失或风险，需仔细审查财务状况'
WHERE `name` = '女祭司' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情甜蜜，充满爱与关怀，适合结婚生子，关系稳定而温暖',
                           `love_reversed`   = '感情中过度依赖或控制，可能因嫉妒或占有欲而产生矛盾',
                           `career_meaning`  = '事业上创意丰富，适合艺术、设计、美食等创意行业，项目顺利推进',
                           `career_reversed` = '工作中创意受阻，可能因过度保护或不愿放手而影响团队发展',
                           `health_meaning`  = '身体健康，生育能力强，适合养生调理，女性健康状况良好',
                           `health_reversed` = '健康方面可能因过度放纵或忽视自我而出现问题，需要自律',
                           `wealth_meaning`  = '财运丰厚，投资有回报，适合房产、农业等稳健投资',
                           `wealth_reversed` = '财务上可能因过度消费或投资失误而出现问题，需要节制'
WHERE `name` = '皇后' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要稳定和安全感，伴侣可靠负责，关系有明确的承诺',
                           `love_reversed`   = '感情中过于控制或强势，缺乏灵活性，可能因固执而产生冲突',
                           `career_meaning`  = '事业上领导力强，适合管理岗位，能建立有效的系统和规则',
                           `career_reversed` = '工作中过于独断，不听取他人意见，可能因权力滥用而失去支持',
                           `health_meaning`  = '身体强健，自律性强，适合规律的运动和健康饮食计划',
                           `health_reversed` = '健康方面可能因过度控制或压力过大而出现心血管问题',
                           `wealth_meaning`  = '财运稳健，善于理财，投资保守但稳定，财务基础牢固',
                           `wealth_reversed` = '财务上可能因过于保守而错失机会，或因固执而做出错误决策'
WHERE `name` = '皇帝' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情遵循传统，可能走向婚姻，重视家庭价值观，关系得到认可',
                           `love_reversed`   = '感情中可能因传统观念束缚而无法自由发展，或存在虚伪的承诺',
                           `career_meaning`  = '工作中遵循规则，适合教育、宗教、法律等传统行业，获得认可',
                           `career_reversed` = '工作中过于墨守成规，无法适应变化，可能因固守旧制而落后',
                           `health_meaning`  = '健康方面遵循传统医学，定期检查，保持规律的生活方式',
                           `health_reversed` = '健康上可能因盲目遵从而忽视个人需求，需要寻求专业的个性化建议',
                           `wealth_meaning`  = '财运稳定，遵循传统投资方式，适合长期稳健的理财计划',
                           `wealth_reversed` = '财务上可能因过于保守而错失新兴投资机会，需要适当创新'
WHERE `name` = '教皇' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情美好，两情相悦，面临重要的感情选择，关系走向深入',
                           `love_reversed`   = '感情中面临艰难选择，可能存在三角关系或价值观不合',
                           `career_meaning`  = '工作中面临重要选择，需要权衡利弊，团队合作和谐',
                           `career_reversed` = '工作中决策困难，可能因价值观不同而与同事产生冲突',
                           `health_meaning`  = '身心和谐，情绪稳定，人际关系良好，整体健康状况佳',
                           `health_reversed` = '健康方面可能因情感问题而影响身心，需要平衡工作与生活',
                           `wealth_meaning`  = '财运上面临重要的财务选择，需要谨慎权衡，合作投资有利',
                           `wealth_reversed` = '财务上因决策失误或合作不当而出现问题，需要重新评估'
WHERE `name` = '恋人' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中主动追求，意志坚定，克服阻碍，感情顺利推进',
                           `love_reversed`   = '感情中过于强势，缺乏沟通，可能因控制欲强而产生矛盾',
                           `career_meaning`  = '事业上目标明确，执行力强，能克服困难取得成功',
                           `career_reversed` = '工作中方向不明，可能因过于强硬而失去合作机会',
                           `health_meaning`  = '身体状态良好，运动能力强，适合挑战性的体育活动',
                           `health_reversed` = '健康方面可能因过度劳累或强行坚持而造成身体损伤',
                           `wealth_meaning`  = '财运上积极进取，能克服财务困难，投资有望获得回报',
                           `wealth_reversed` = '财务上可能因冲动决策或缺乏控制而出现损失'
WHERE `name` = '战车' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中以温柔和耐心化解矛盾，用爱的力量维系关系',
                           `love_reversed`   = '感情中缺乏自信，可能因软弱而被对方控制或忽视',
                           `career_meaning`  = '工作中以内在力量克服困难，领导力来自于同理心和耐心',
                           `career_reversed` = '工作中缺乏自信，可能因情绪失控而影响工作表现',
                           `health_meaning`  = '身体健康，免疫力强，以温和的方式保持健康，恢复能力好',
                           `health_reversed` = '健康方面可能因压力过大或缺乏自律而出现健康问题',
                           `wealth_meaning`  = '财运上以耐心和毅力积累财富，长期投资有回报',
                           `wealth_reversed` = '财务上可能因缺乏自律而过度消费，或因软弱而被人利用'
WHERE `name` = '力量' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要独处和反思，可能暂时远离感情，寻找真正的自我',
                           `love_reversed`   = '感情中过于孤僻，拒绝他人的关爱，可能因逃避而错失良缘',
                           `career_meaning`  = '工作中需要独立思考，适合研究、咨询等需要深度思考的工作',
                           `career_reversed` = '工作中过于孤立，不愿与团队合作，可能因固执而错失机会',
                           `health_meaning`  = '健康方面需要休息和独处，冥想和静修有益身心健康',
                           `health_reversed` = '健康上可能因过度孤立而产生心理问题，需要适当的社交',
                           `wealth_meaning`  = '财运上需要谨慎规划，不宜冒险，稳健的长期投资更适合',
                           `wealth_reversed` = '财务上可能因过于保守或孤立而错失投资机会'
WHERE `name` = '隐士' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情迎来重要转折，可能遇到命中注定的人，关系进入新阶段',
                           `love_reversed`   = '感情中遭遇不顺，可能因外部因素而影响关系，需要耐心等待',
                           `career_meaning`  = '事业迎来重要机遇，把握时机，可能有意外的晋升或转机',
                           `career_reversed` = '工作中遭遇挫折，可能因时机不对而错失机会，需要耐心等待',
                           `health_meaning`  = '健康状况可能有所变化，注意季节性疾病，保持规律的生活',
                           `health_reversed` = '健康方面可能遭遇意外或突发状况，需要提前做好预防',
                           `wealth_meaning`  = '财运有起伏，可能有意外之财，也可能有意外损失，需谨慎',
                           `wealth_reversed` = '财务上遭遇不顺，可能因外部因素而出现损失，需要灵活应对'
WHERE `name` = '命运之轮' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要公平对待，双方都要承担责任，关系建立在诚实基础上',
                           `love_reversed`   = '感情中存在不公平，可能因逃避责任而产生矛盾，需要坦诚沟通',
                           `career_meaning`  = '工作中公正处事，适合法律、审计等需要公正的职业',
                           `career_reversed` = '工作中可能遭遇不公平对待，或因逃避责任而影响职业发展',
                           `health_meaning`  = '健康方面需要平衡，保持规律的生活，注意身体各系统的协调',
                           `health_reversed` = '健康上可能因不良习惯的后果而出现问题，需要改变生活方式',
                           `wealth_meaning`  = '财运上因果报应，诚实经营有回报，法律相关的财务事项需谨慎',
                           `wealth_reversed` = '财务上可能因不诚实或逃避责任而遭受损失，需要面对财务问题'
WHERE `name` = '正义' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要暂停和反思，可能需要牺牲某些东西来换取关系的进步',
                           `love_reversed`   = '感情中无谓的等待和牺牲，可能因固执而无法前进',
                           `career_meaning`  = '工作中需要暂停思考，换个角度看问题，可能有意外的顿悟',
                           `career_reversed` = '工作中因拖延或固执而错失机会，需要改变思维方式',
                           `health_meaning`  = '健康方面需要休息和调整，可能需要暂时放慢脚步',
                           `health_reversed` = '健康上可能因拖延就医而使问题加重，需要及时处理',
                           `wealth_meaning`  = '财运上需要耐心等待，不宜急于求成，可能需要短期牺牲换取长期收益',
                           `wealth_reversed` = '财务上因拖延或固执而错失机会，需要改变财务策略'
WHERE `name` = '倒吊人' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情迎来重大转变，旧的关系模式结束，新的开始即将到来',
                           `love_reversed`   = '感情中拒绝改变，执着于已经结束的关系，无法向前看',
                           `career_meaning`  = '事业迎来重大转变，旧的工作方式结束，新的机遇即将到来',
                           `career_reversed` = '工作中拒绝改变，固守旧有模式，可能因此而被淘汰',
                           `health_meaning`  = '健康方面可能需要彻底改变生活方式，旧的健康问题得到解决',
                           `health_reversed` = '健康上可能因拒绝改变而使健康问题持续恶化',
                           `wealth_meaning`  = '财运上旧的财务模式结束，新的财务机遇即将到来',
                           `wealth_reversed` = '财务上因拒绝改变而持续亏损，需要彻底调整财务策略'
WHERE `name` = '死神' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中保持平衡，双方相互包容，关系和谐稳定，共同成长',
                           `love_reversed`   = '感情中失衡，可能因一方过度付出或索取而产生矛盾',
                           `career_meaning`  = '工作中保持平衡，善于协调各方关系，工作效率高',
                           `career_reversed` = '工作中失衡，可能因过度工作或缺乏耐心而影响工作质量',
                           `health_meaning`  = '健康状况良好，注重身心平衡，适合温和的养生方式',
                           `health_reversed` = '健康方面可能因生活失衡而出现问题，需要调整生活节奏',
                           `wealth_meaning`  = '财运稳健，善于平衡收支，长期投资有回报',
                           `wealth_reversed` = '财务上可能因过度消费或投资失衡而出现问题'
WHERE `name` = '节制' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中可能存在不健康的依赖或控制，需要审视关系的本质',
                           `love_reversed`   = '感情中打破不健康的束缚，重新获得自由，关系得到净化',
                           `career_meaning`  = '工作中可能因物质欲望而做出不道德的选择，需要警惕',
                           `career_reversed` = '工作中打破不良的工作习惯，从束缚中解脱，重新找到工作动力',
                           `health_meaning`  = '健康方面可能存在不良习惯或成瘾问题，需要寻求帮助',
                           `health_reversed` = '健康上开始克服不良习惯，健康状况逐渐改善',
                           `wealth_meaning`  = '财运上可能因贪婪或不良习惯而出现财务问题',
                           `wealth_reversed` = '财务上打破不良的消费习惯，开始建立健康的财务体系'
WHERE `name` = '恶魔' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中可能遭遇突然的变故，关系面临重大考验或破裂',
                           `love_reversed`   = '感情中虽然有危机，但通过及时沟通避免了最坏的结果',
                           `career_meaning`  = '工作中可能遭遇突然的变故，如裁员或公司重组，需要做好准备',
                           `career_reversed` = '工作中虽然有危机，但通过灵活应对避免了最坏的结果',
                           `health_meaning`  = '健康方面可能遭遇突发状况，需要及时就医，不要忽视身体信号',
                           `health_reversed` = '健康上虽然有警示，但通过及时处理避免了严重后果',
                           `wealth_meaning`  = '财运上可能遭遇突然的财务损失，需要做好风险管理',
                           `wealth_reversed` = '财务上虽然有风险，但通过及时调整避免了重大损失'
WHERE `name` = '塔' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情充满希望，可能遇到理想的伴侣，关系充满美好的期待',
                           `love_reversed`   = '感情中失去希望，可能因失望而放弃，需要重新找回对爱的信念',
                           `career_meaning`  = '工作中充满灵感，前景光明，适合创意和艺术类工作',
                           `career_reversed` = '工作中缺乏灵感，对未来感到迷茫，需要重新找到工作的意义',
                           `health_meaning`  = '健康状况良好，身心得到治愈，适合冥想和放松',
                           `health_reversed` = '健康方面可能因焦虑或失望而影响身心健康',
                           `wealth_meaning`  = '财运良好，有意外之财的可能，投资前景乐观',
                           `wealth_reversed` = '财务上可能因悲观情绪而错失机会，需要重新建立财务信心'
WHERE `name` = '星星' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中存在不确定性，可能有隐藏的情感或误解，需要直觉引导',
                           `love_reversed`   = '感情中真相逐渐浮现，恐惧和误解得到化解，关系变得清晰',
                           `career_meaning`  = '工作中存在不确定性，可能有隐藏的竞争或阴谋，需要保持警觉',
                           `career_reversed` = '工作中隐藏的问题逐渐浮现，通过直觉和洞察力找到解决方案',
                           `health_meaning`  = '健康方面可能有隐藏的问题，需要关注心理健康和睡眠质量',
                           `health_reversed` = '健康上隐藏的问题得到揭示，通过治疗逐渐恢复',
                           `wealth_meaning`  = '财运上存在不确定性，可能有隐藏的风险，需要谨慎投资',
                           `wealth_reversed` = '财务上隐藏的风险逐渐浮现，通过及时调整避免损失'
WHERE `name` = '月亮' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情幸福美满，充满阳光和喜悦，关系积极向上，可能有喜事',
                           `love_reversed`   = '感情中虽然有些小挫折，但整体仍然积极，需要避免过度乐观',
                           `career_meaning`  = '事业蒸蒸日上，成功在望，工作充满活力，获得认可和奖励',
                           `career_reversed` = '工作中虽然有些延迟，但整体前景仍然光明，需要避免傲慢',
                           `health_meaning`  = '身体健康，精力充沛，适合户外活动，整体健康状况极佳',
                           `health_reversed` = '健康方面虽然有些小问题，但整体状况良好，注意防晒',
                           `wealth_meaning`  = '财运旺盛，投资有回报，可能有意外之财，财务状况良好',
                           `wealth_reversed` = '财务上虽然有些小挫折，但整体前景仍然乐观'
WHERE `name` = '太阳' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中迎来重要的觉醒，可能需要做出关键决定，宽恕和解',
                           `love_reversed`   = '感情中拒绝面对问题，无法原谅过去，关系陷入僵局',
                           `career_meaning`  = '工作中迎来重要的转折，可能需要做出关键决定，职业觉醒',
                           `career_reversed` = '工作中拒绝面对现实，错误的决策可能影响职业发展',
                           `health_meaning`  = '健康方面迎来重要的转变，可能需要做出关键的健康决定',
                           `health_reversed` = '健康上拒绝面对健康问题，可能因逃避而使情况恶化',
                           `wealth_meaning`  = '财运上迎来重要的财务决策时刻，正确的选择将带来丰厚回报',
                           `wealth_reversed` = '财务上因错误的决策或逃避而错失重要机会'
WHERE `name` = '审判' AND `type` = 'major';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情圆满，关系达到新的高度，可能走向婚姻或长期承诺',
                           `love_reversed`   = '感情中虽然接近圆满，但仍有最后的障碍需要克服',
                           `career_meaning`  = '事业取得重大成就，项目圆满完成，可能有出国机会',
                           `career_reversed` = '工作中虽然接近成功，但仍有最后的障碍，需要坚持',
                           `health_meaning`  = '健康状况极佳，身心达到平衡，整体健康水平高',
                           `health_reversed` = '健康方面虽然接近康复，但仍需坚持治疗和调养',
                           `wealth_meaning`  = '财运达到顶峰，投资获得丰厚回报，财务状况圆满',
                           `wealth_reversed` = '财务上虽然接近目标，但仍有最后的障碍需要克服'
WHERE `name` = '世界' AND `type` = 'major';

-- =====================================================
-- 小阿卡纳 - 权杖组（14张）
-- =====================================================
UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中迎来新的激情，可能开始一段充满热情的新恋情',
                           `love_reversed`   = '感情中激情减退，新的开始受阻，需要重新点燃热情',
                           `career_meaning`  = '事业上迎来新的灵感和机遇，适合开始新项目或创业',
                           `career_reversed` = '工作中缺乏灵感，新项目受阻，需要寻找新的动力',
                           `health_meaning`  = '精力充沛，适合开始新的健康计划，充满活力',
                           `health_reversed` = '健康方面缺乏动力，新的健康计划难以启动',
                           `wealth_meaning`  = '财运上迎来新的商机，适合开始新的投资计划',
                           `wealth_reversed` = '财务上新的投资计划受阻，需要重新规划'
WHERE `name` = '权杖一' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中规划未来，考虑长期关系，可能面临感情上的重要决定',
                           `love_reversed`   = '感情中犹豫不决，无法规划未来，关系陷入不确定',
                           `career_meaning`  = '工作中制定长远计划，展望未来，适合战略规划',
                           `career_reversed` = '工作中缺乏远见，计划受阻，需要重新审视目标',
                           `health_meaning`  = '健康方面制定长期健康计划，展望健康的未来',
                           `health_reversed` = '健康上缺乏长期规划，健康目标难以实现',
                           `wealth_meaning`  = '财运上制定长期财务计划，展望财务自由',
                           `wealth_reversed` = '财务上缺乏长远规划，财务目标难以实现'
WHERE `name` = '权杖二' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中关系向外扩展，可能面临异地恋或跨文化关系',
                           `love_reversed`   = '感情中合作出现问题，关系扩展受阻',
                           `career_meaning`  = '事业上扩张发展，寻求合作，等待项目结果',
                           `career_reversed` = '工作中扩张计划受阻，合作出现问题',
                           `health_meaning`  = '健康方面扩展健康视野，尝试新的健康方法',
                           `health_reversed` = '健康上新的健康方法效果不佳，需要重新评估',
                           `wealth_meaning`  = '财运上扩大投资范围，寻求合作机会',
                           `wealth_reversed` = '财务上扩张计划受阻，合作投资出现问题'
WHERE `name` = '权杖三' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情稳定，可能有婚礼或订婚等喜事，家庭和谐',
                           `love_reversed`   = '感情中暂时不和，喜事推迟，家庭出现矛盾',
                           `career_meaning`  = '工作取得阶段性成果，团队和谐，值得庆祝',
                           `career_reversed` = '工作中团队出现矛盾，阶段性目标推迟实现',
                           `health_meaning`  = '健康状况稳定，适合庆祝健康里程碑',
                           `health_reversed` = '健康方面出现小波折，健康目标推迟实现',
                           `wealth_meaning`  = '财运稳定，可能有财务上的喜事，投资有回报',
                           `wealth_reversed` = '财务上出现小波折，财务目标推迟实现'
WHERE `name` = '权杖四' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中存在竞争或冲突，可能有第三者介入',
                           `love_reversed`   = '感情中冲突得到缓解，双方开始寻找共识',
                           `career_meaning`  = '工作中面临激烈竞争，团队内部有冲突',
                           `career_reversed` = '工作中竞争压力减轻，团队开始合作',
                           `health_meaning`  = '健康方面面临挑战，需要克服身体上的困难',
                           `health_reversed` = '健康上挑战得到缓解，身体状况逐渐改善',
                           `wealth_meaning`  = '财运上面临激烈竞争，投资市场波动',
                           `wealth_reversed` = '财务上竞争压力减轻，市场趋于稳定'
WHERE `name` = '权杖五' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中获得认可，关系得到外界祝福，感情顺利',
                           `love_reversed`   = '感情中因傲慢而失去对方，公众形象影响感情',
                           `career_meaning`  = '工作中取得成功，获得认可和奖励，领导力得到肯定',
                           `career_reversed` = '工作中因傲慢而失去支持，成功被延迟',
                           `health_meaning`  = '健康方面取得进步，健康目标实现，值得庆祝',
                           `health_reversed` = '健康上因过度自信而忽视问题，健康目标延迟实现',
                           `wealth_meaning`  = '财运旺盛，投资成功，获得财务上的认可',
                           `wealth_reversed` = '财务上因傲慢而失误，财务成功被延迟'
WHERE `name` = '权杖六' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中坚守立场，为关系而战，不轻易放弃',
                           `love_reversed`   = '感情中因压力过大而退缩，无法坚守关系',
                           `career_meaning`  = '工作中坚守立场，面对挑战不退缩，维护自己的权益',
                           `career_reversed` = '工作中因压力过大而退缩，无法坚守立场',
                           `health_meaning`  = '健康方面坚持健康计划，面对挑战不放弃',
                           `health_reversed` = '健康上因压力过大而放弃健康计划',
                           `wealth_meaning`  = '财运上坚守财务立场，面对压力不轻易妥协',
                           `wealth_reversed` = '财务上因压力过大而做出不利的财务决策'
WHERE `name` = '权杖七' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中快速发展，可能有重要消息，感情进展顺利',
                           `love_reversed`   = '感情中进展缓慢，消息中断，感情发展受阻',
                           `career_meaning`  = '工作中快速推进，消息频繁，项目进展顺利',
                           `career_reversed` = '工作中进展缓慢，消息中断，项目受阻',
                           `health_meaning`  = '健康方面快速恢复，健康状况迅速改善',
                           `health_reversed` = '健康上恢复缓慢，健康改善受阻',
                           `wealth_meaning`  = '财运上快速获利，投资迅速见效',
                           `wealth_reversed` = '财务上进展缓慢，投资回报延迟'
WHERE `name` = '权杖八' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中保持警惕，坚守关系，接近重要里程碑',
                           `love_reversed`   = '感情中精疲力竭，防线被突破，考虑放弃',
                           `career_meaning`  = '工作中保持警惕，坚持到底，接近项目完成',
                           `career_reversed` = '工作中精疲力竭，防线被突破，考虑放弃',
                           `health_meaning`  = '健康方面坚持治疗，接近康复，保持警惕',
                           `health_reversed` = '健康上精疲力竭，治疗效果不佳，需要调整方案',
                           `wealth_meaning`  = '财运上坚持财务计划，接近财务目标',
                           `wealth_reversed` = '财务上精疲力竭，财务计划难以为继'
WHERE `name` = '权杖九' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中承担过多责任，关系压力大，需要分担',
                           `love_reversed`   = '感情中卸下不必要的负担，关系压力得到释放',
                           `career_meaning`  = '工作中承担过多责任，工作压力大，需要学会委托',
                           `career_reversed` = '工作中卸下不必要的负担，工作压力得到释放',
                           `health_meaning`  = '健康方面因压力过大而影响健康，需要减轻负担',
                           `health_reversed` = '健康上压力得到释放，健康状况逐渐改善',
                           `wealth_meaning`  = '财运上财务压力大，承担过多财务责任',
                           `wealth_reversed` = '财务上卸下不必要的财务负担，财务压力得到释放'
WHERE `name` = '权杖十' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满热情，可能有新的感情消息，感情充满活力',
                           `love_reversed`   = '感情中因幼稚而受挫，感情消息不可靠',
                           `career_meaning`  = '工作中充满热情，有新想法，适合探索新领域',
                           `career_reversed` = '工作中因缺乏经验而受挫，新想法难以实现',
                           `health_meaning`  = '健康方面充满活力，适合尝试新的健康方法',
                           `health_reversed` = '健康上因缺乏经验而选择了不适合的健康方法',
                           `wealth_meaning`  = '财运上有新的财务想法，充满探索精神',
                           `wealth_reversed` = '财务上因缺乏经验而做出不成熟的财务决策'
WHERE `name` = '权杖侍从' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满激情，行动力强，可能有冲动的感情决定',
                           `love_reversed`   = '感情中因冲动而做出错误决定，感情受到影响',
                           `career_meaning`  = '工作中行动力强，勇于冒险，适合需要快速行动的工作',
                           `career_reversed` = '工作中因鲁莽而犯错，缺乏计划的行动导致失败',
                           `health_meaning`  = '健康方面充满活力，适合高强度运动，但需注意安全',
                           `health_reversed` = '健康上因冲动而造成运动伤害，需要更谨慎',
                           `wealth_meaning`  = '财运上勇于投资，行动力强，但需注意风险',
                           `wealth_reversed` = '财务上因冲动投资而损失，需要更谨慎的财务规划'
WHERE `name` = '权杖骑士' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满魅力，自信独立，吸引力强，关系充满活力',
                           `love_reversed`   = '感情中因自负而导致关系紧张，控制欲影响感情',
                           `career_meaning`  = '工作中自信领导，魅力十足，适合需要领导力的工作',
                           `career_reversed` = '工作中因自负而失去支持，控制欲影响团队合作',
                           `health_meaning`  = '健康方面充满活力，自信面对健康挑战',
                           `health_reversed` = '健康上因自负而忽视健康问题，需要更谦虚地面对',
                           `wealth_meaning`  = '财运旺盛，自信投资，财务领导力强',
                           `wealth_reversed` = '财务上因自负而做出错误决策，需要更谦虚地面对财务问题'
WHERE `name` = '权杖皇后' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满魅力，领导力强，是可靠的伴侣',
                           `love_reversed`   = '感情中因独断专行而失去伴侣的信任',
                           `career_meaning`  = '工作中领导力卓越，远见卓识，适合高层管理',
                           `career_reversed` = '工作中因独断专行而失去团队支持',
                           `health_meaning`  = '健康方面充满活力，领导自己的健康管理',
                           `health_reversed` = '健康上因急躁而做出不利于健康的决定',
                           `wealth_meaning`  = '财运旺盛，财务领导力强，投资决策果断',
                           `wealth_reversed` = '财务上因急躁而做出错误的投资决策'
WHERE `name` = '权杖国王' AND `type` = 'minor';

-- =====================================================
-- 小阿卡纳 - 圣杯组（14张）
-- =====================================================
UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情迎来美好的新开始，充满爱与希望，可能遇到真爱',
                           `love_reversed`   = '感情中期望过高而失望，情感新开始受阻',
                           `career_meaning`  = '工作中充满创意和热情，适合创意类工作，情感投入工作',
                           `career_reversed` = '工作中情感受阻，创意枯竭，工作热情减退',
                           `health_meaning`  = '身心得到滋养，情绪健康，适合冥想和心灵修炼',
                           `health_reversed` = '健康方面情绪波动，心理健康需要关注',
                           `wealth_meaning`  = '财运上有新的财务机遇，充满希望',
                           `wealth_reversed` = '财务上期望过高而失望，新的财务机遇受阻'
WHERE `name` = '圣杯一' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情美好，两情相悦，关系和谐，可能走向承诺',
                           `love_reversed`   = '感情中沟通不畅，关系出现裂痕，需要修复',
                           `career_meaning`  = '工作中合作愉快，伙伴关系良好，团队和谐',
                           `career_reversed` = '工作中合作出现问题，伙伴关系破裂',
                           `health_meaning`  = '身心和谐，人际关系良好，情绪稳定',
                           `health_reversed` = '健康方面人际关系影响健康，需要修复关系',
                           `wealth_meaning`  = '财运上合作投资有利，伙伴关系带来财务机遇',
                           `wealth_reversed` = '财务上合作出现问题，伙伴关系影响财务'
WHERE `name` = '圣杯二' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中有喜事，可能有婚礼或庆祝活动，朋友支持',
                           `love_reversed`   = '感情中因流言蜚语而产生矛盾，朋友关系影响感情',
                           `career_meaning`  = '工作中团队庆祝成功，同事关系融洽，有喜事',
                           `career_reversed` = '工作中因流言蜚语而影响团队关系',
                           `health_meaning`  = '健康方面社交活动有益健康，适合参加团体活动',
                           `health_reversed` = '健康上过度放纵影响健康，需要节制',
                           `wealth_meaning`  = '财运上有财务上的喜事，可能有意外之财',
                           `wealth_reversed` = '财务上因过度放纵而影响财务状况'
WHERE `name` = '圣杯三' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要独处和反思，可能对现有关系感到不满',
                           `love_reversed`   = '感情中走出冷漠，重新接受爱，关系得到改善',
                           `career_meaning`  = '工作中需要反思，可能对现有工作感到不满',
                           `career_reversed` = '工作中走出冷漠，重新接受新机会',
                           `health_meaning`  = '健康方面需要内省，关注心理健康，适合冥想',
                           `health_reversed` = '健康上走出冷漠，重新关注健康',
                           `wealth_meaning`  = '财运上对现有财务状况不满，需要反思财务目标',
                           `wealth_reversed` = '财务上走出冷漠，重新接受新的财务机遇'
WHERE `name` = '圣杯四' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中经历失去或失望，需要时间疗愈，但仍有希望',
                           `love_reversed`   = '感情中走出悲伤，接受失去，开始新的感情旅程',
                           `career_meaning`  = '工作中经历失败或失望，需要从中学习，重新出发',
                           `career_reversed` = '工作中走出失败的阴影，重新找到工作的意义',
                           `health_meaning`  = '健康方面情绪低落影响健康，需要心理疏导',
                           `health_reversed` = '健康上走出情绪低落，身心逐渐恢复',
                           `wealth_meaning`  = '财运上经历财务损失，需要从中学习，重新规划',
                           `wealth_reversed` = '财务上走出损失的阴影，重新建立财务计划'
WHERE `name` = '圣杯五' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满怀旧情怀，可能与旧爱重逢，关系纯真美好',
                           `love_reversed`   = '感情中活在过去，无法接受新的感情，执着于旧爱',
                           `career_meaning`  = '工作中回顾过去的经验，从中汲取智慧，传承知识',
                           `career_reversed` = '工作中活在过去，无法适应新的工作环境',
                           `health_meaning`  = '健康方面回顾过去的健康习惯，从中学习',
                           `health_reversed` = '健康上活在过去，无法接受新的健康方法',
                           `wealth_meaning`  = '财运上回顾过去的财务经验，从中学习',
                           `wealth_reversed` = '财务上活在过去，无法适应新的财务环境'
WHERE `name` = '圣杯六' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满幻想，可能有多个追求者，需要做出选择',
                           `love_reversed`   = '感情中回归现实，面对感情中的真实问题',
                           `career_meaning`  = '工作中有多种选择，但需要分清现实与幻想',
                           `career_reversed` = '工作中回归现实，面对工作中的真实挑战',
                           `health_meaning`  = '健康方面需要面对现实，不要沉溺于幻想',
                           `health_reversed` = '健康上回归现实，面对健康问题，寻求实际解决方案',
                           `wealth_meaning`  = '财运上有多种投资选择，但需要分清现实与幻想',
                           `wealth_reversed` = '财务上回归现实，面对财务中的真实问题'
WHERE `name` = '圣杯七' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中选择离开不满足的关系，寻找更深的情感连接',
                           `love_reversed`   = '感情中因恐惧而无法离开不健康的关系',
                           `career_meaning`  = '工作中选择离开不满足的工作，寻找更有意义的职业',
                           `career_reversed` = '工作中因恐惧而无法离开不满意的工作',
                           `health_meaning`  = '健康方面选择放弃不健康的生活方式，寻找更好的健康方法',
                           `health_reversed` = '健康上因恐惧而无法改变不健康的生活方式',
                           `wealth_meaning`  = '财运上选择放弃不满足的财务状况，寻找新的财务机遇',
                           `wealth_reversed` = '财务上因恐惧而无法改变不满意的财务状况'
WHERE `name` = '圣杯八' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中愿望实现，关系幸福满足，充满爱与喜悦',
                           `love_reversed`   = '感情中因贪婪而不满足，过度索取影响关系',
                           `career_meaning`  = '工作中愿望实现，工作满足感强，成就感高',
                           `career_reversed` = '工作中因贪婪而不满足，过度追求影响工作质量',
                           `health_meaning`  = '健康状况良好，身心满足，适合享受生活',
                           `health_reversed` = '健康上因过度放纵而影响健康，需要节制',
                           `wealth_meaning`  = '财运旺盛，愿望实现，财务满足感强',
                           `wealth_reversed` = '财务上因贪婪而不满足，过度追求影响财务稳定'
WHERE `name` = '圣杯九' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情圆满，家庭幸福，关系长久稳定，充满爱',
                           `love_reversed`   = '感情中因缺乏沟通而产生裂痕，家庭关系出现矛盾',
                           `career_meaning`  = '工作中团队和谐，工作满足感强，职业圆满',
                           `career_reversed` = '工作中团队关系出现裂痕，工作满足感下降',
                           `health_meaning`  = '健康状况极佳，家庭支持有益健康，身心和谐',
                           `health_reversed` = '健康方面家庭矛盾影响健康，需要修复关系',
                           `wealth_meaning`  = '财运圆满，家庭财务和谐，长期财务稳定',
                           `wealth_reversed` = '财务上家庭财务出现矛盾，需要沟通解决'
WHERE `name` = '圣杯十' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中有新的情感消息，充满好奇和期待',
                           `love_reversed`   = '感情中因情绪化而产生误解，情感不成熟',
                           `career_meaning`  = '工作中有新的创意消息，充满好奇心',
                           `career_reversed` = '工作中因情绪化而影响工作，需要更成熟的处理方式',
                           `health_meaning`  = '健康方面关注情绪健康，适合情感表达和创意活动',
                           `health_reversed` = '健康上情绪化影响健康，需要情绪管理',
                           `wealth_meaning`  = '财运上有新的财务消息，充满好奇心',
                           `wealth_reversed` = '财务上因情绪化而做出不成熟的财务决策'
WHERE `name` = '圣杯侍从' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满浪漫，可能收到感情邀请，关系充满激情',
                           `love_reversed`   = '感情中因不切实际而受挫，可能遭遇情感骗局',
                           `career_meaning`  = '工作中充满创意和想象力，适合创意类工作',
                           `career_reversed` = '工作中因不切实际而受挫，创意难以落地',
                           `health_meaning`  = '健康方面充满活力，适合创意性的健康活动',
                           `health_reversed` = '健康上因不切实际而选择了不适合的健康方法',
                           `wealth_meaning`  = '财运上有浪漫的财务机遇，但需注意风险',
                           `wealth_reversed` = '财务上因不切实际而做出错误的财务决策'
WHERE `name` = '圣杯骑士' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满慈悲和关怀，情感稳定，是温柔的伴侣',
                           `love_reversed`   = '感情中因过度敏感而情绪化，情感依赖影响关系',
                           `career_meaning`  = '工作中充满同理心，适合护理、咨询等关怀类工作',
                           `career_reversed` = '工作中因过度敏感而情绪化，影响工作表现',
                           `health_meaning`  = '健康方面情绪稳定，适合情感疗愈和心理健康活动',
                           `health_reversed` = '健康上因过度敏感而影响心理健康',
                           `wealth_meaning`  = '财运上以直觉指导财务决策，情感稳定有助于财务',
                           `wealth_reversed` = '财务上因情绪化而做出不理智的财务决策'
WHERE `name` = '圣杯皇后' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中情感成熟，平衡理性与感性，是可靠的伴侣',
                           `love_reversed`   = '感情中因冷漠而导致关系紧张，情感操控影响关系',
                           `career_meaning`  = '工作中情感成熟，善于处理人际关系，适合管理岗位',
                           `career_reversed` = '工作中因冷漠而失去团队信任，情感操控影响团队',
                           `health_meaning`  = '健康方面情感平衡，身心健康，适合情感疗愈',
                           `health_reversed` = '健康上因情感压抑而影响健康，需要情感表达',
                           `wealth_meaning`  = '财运上情感成熟，财务决策理性，长期财务稳定',
                           `wealth_reversed` = '财务上因情感操控而影响财务决策'
WHERE `name` = '圣杯国王' AND `type` = 'minor';

-- =====================================================
-- 小阿卡纳 - 宝剑组（14张）
-- =====================================================
UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要清晰的沟通，突破误解，以理智面对感情',
                           `love_reversed`   = '感情中思维混乱，沟通受阻，误解加深',
                           `career_meaning`  = '工作中思维清晰，突破障碍，适合需要分析和决策的工作',
                           `career_reversed` = '工作中思维混乱，决策受阻，需要重新梳理思路',
                           `health_meaning`  = '健康方面思维清晰，适合认知训练和心理健康活动',
                           `health_reversed` = '健康上思维混乱影响健康决策，需要寻求专业建议',
                           `wealth_meaning`  = '财运上思维清晰，财务决策理性，突破财务障碍',
                           `wealth_reversed` = '财务上思维混乱，财务决策受阻'
WHERE `name` = '宝剑一' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中面临艰难选择，暂时保持平衡，需要做出决定',
                           `love_reversed`   = '感情中被迫做出选择，压力过大，逃避感情问题',
                           `career_meaning`  = '工作中面临艰难选择，暂时保持平衡，需要做出决定',
                           `career_reversed` = '工作中被迫做出选择，压力过大，逃避工作问题',
                           `health_meaning`  = '健康方面面临健康选择，需要做出决定',
                           `health_reversed` = '健康上逃避健康问题，需要面对现实',
                           `wealth_meaning`  = '财运上面临财务选择，需要做出决定',
                           `wealth_reversed` = '财务上逃避财务问题，需要面对现实'
WHERE `name` = '宝剑二' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中经历心碎和痛苦，可能面临分离或失去',
                           `love_reversed`   = '感情中开始从心碎中恢复，放下痛苦，走向治愈',
                           `career_meaning`  = '工作中经历挫折和痛苦，可能面临失业或项目失败',
                           `career_reversed` = '工作中开始从挫折中恢复，重新找到工作方向',
                           `health_meaning`  = '健康方面经历身心痛苦，需要疗愈和关怀',
                           `health_reversed` = '健康上开始从痛苦中恢复，身心逐渐治愈',
                           `wealth_meaning`  = '财运上经历财务损失和痛苦，需要从中学习',
                           `wealth_reversed` = '财务上开始从损失中恢复，重新建立财务计划'
WHERE `name` = '宝剑三' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中需要暂停和休息，给关系一些空间',
                           `love_reversed`   = '感情中因焦虑而无法休息，关系压力过大',
                           `career_meaning`  = '工作中需要暂停和休息，恢复精力后再出发',
                           `career_reversed` = '工作中因焦虑而无法休息，工作压力过大',
                           `health_meaning`  = '健康方面需要充分休息，适合冥想和放松',
                           `health_reversed` = '健康上因焦虑而无法休息，影响健康恢复',
                           `wealth_meaning`  = '财运上需要暂停财务活动，休息后再做决策',
                           `wealth_reversed` = '财务上因焦虑而无法休息，财务压力过大'
WHERE `name` = '宝剑四' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中存在冲突和背叛，关系面临严峻考验',
                           `love_reversed`   = '感情中放下执念，冲突得到解决，关系走向和解',
                           `career_meaning`  = '工作中存在冲突和背叛，职场竞争激烈',
                           `career_reversed` = '工作中放下执念，冲突得到解决，团队走向和解',
                           `health_meaning`  = '健康方面因冲突和压力而影响健康',
                           `health_reversed` = '健康上放下执念，压力减轻，健康逐渐恢复',
                           `wealth_meaning`  = '财运上存在财务冲突，可能遭遇欺骗',
                           `wealth_reversed` = '财务上放下执念，财务冲突得到解决'
WHERE `name` = '宝剑五' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中走向平静，从困境中过渡，关系逐渐改善',
                           `love_reversed`   = '感情中陷入僵局，无法从困境中走出',
                           `career_meaning`  = '工作中走向平静，从困境中过渡，工作逐渐改善',
                           `career_reversed` = '工作中陷入僵局，无法从困境中走出',
                           `health_meaning`  = '健康方面走向康复，从困境中过渡',
                           `health_reversed` = '健康上陷入僵局，康复进展缓慢',
                           `wealth_meaning`  = '财运上走向平静，从财务困境中过渡',
                           `wealth_reversed` = '财务上陷入僵局，无法从财务困境中走出'
WHERE `name` = '宝剑六' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中存在欺骗或隐瞒，需要警惕',
                           `love_reversed`   = '感情中真相揭晓，欺骗被发现，需要面对后果',
                           `career_meaning`  = '工作中存在欺骗或不诚实，需要警惕',
                           `career_reversed` = '工作中真相揭晓，不诚实行为被发现',
                           `health_meaning`  = '健康方面可能存在隐藏的健康问题，需要诚实面对',
                           `health_reversed` = '健康上隐藏的问题被揭示，需要面对真相',
                           `wealth_meaning`  = '财运上存在财务欺骗或隐瞒，需要警惕',
                           `wealth_reversed` = '财务上真相揭晓，财务欺骗被发现'
WHERE `name` = '宝剑七' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中感到受困，无法自由表达，关系有束缚感',
                           `love_reversed`   = '感情中打破束缚，重获自由，关系得到改善',
                           `career_meaning`  = '工作中感到受困，无法自由发展，工作有束缚感',
                           `career_reversed` = '工作中打破束缚，重获自由，工作得到改善',
                           `health_meaning`  = '健康方面感到受困，身心受到限制',
                           `health_reversed` = '健康上打破束缚，身心逐渐恢复自由',
                           `wealth_meaning`  = '财运上感到受困，财务受到限制',
                           `wealth_reversed` = '财务上打破束缚，财务逐渐恢复自由'
WHERE `name` = '宝剑八' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中充满焦虑和担忧，可能有噩梦般的感情经历',
                           `love_reversed`   = '感情中逐渐走出焦虑，克服对感情的恐惧',
                           `career_meaning`  = '工作中充满焦虑和担忧，工作压力过大',
                           `career_reversed` = '工作中逐渐走出焦虑，克服对工作的恐惧',
                           `health_meaning`  = '健康方面焦虑和压力影响健康，需要心理疏导',
                           `health_reversed` = '健康上逐渐走出焦虑，心理健康逐渐改善',
                           `wealth_meaning`  = '财运上充满财务焦虑，担忧财务状况',
                           `wealth_reversed` = '财务上逐渐走出财务焦虑，财务状况逐渐改善'
WHERE `name` = '宝剑九' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中经历最痛苦的时刻，关系可能走向终结',
                           `love_reversed`   = '感情中最困难的时刻已经过去，开始重新出发',
                           `career_meaning`  = '工作中经历最困难的时刻，可能面临失业或项目失败',
                           `career_reversed` = '工作中最困难的时刻已经过去，开始重新出发',
                           `health_meaning`  = '健康方面经历最困难的时刻，需要全力以赴',
                           `health_reversed` = '健康上最困难的时刻已经过去，开始康复',
                           `wealth_meaning`  = '财运上经历最困难的财务时刻，需要全力以赴',
                           `wealth_reversed` = '财务上最困难的时刻已经过去，开始财务重建'
WHERE `name` = '宝剑十' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中有新的消息，充满好奇，需要谨慎沟通',
                           `love_reversed`   = '感情中因言语失当而产生麻烦，需要更谨慎地沟通',
                           `career_meaning`  = '工作中有新的消息，充满好奇心，适合学习新技能',
                           `career_reversed` = '工作中因言语失当而产生麻烦，需要更谨慎地沟通',
                           `health_meaning`  = '健康方面充满好奇，适合学习新的健康知识',
                           `health_reversed` = '健康上因不成熟的健康决策而产生问题',
                           `wealth_meaning`  = '财运上有新的财务消息，充满好奇心',
                           `wealth_reversed` = '财务上因言语失当而产生财务麻烦'
WHERE `name` = '宝剑侍从' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中行动迅速，直言不讳，但可能因冲动而伤害对方',
                           `love_reversed`   = '感情中因盲目冲动而失误，咄咄逼人影响关系',
                           `career_meaning`  = '工作中行动迅速，思维敏捷，但可能因冲动而犯错',
                           `career_reversed` = '工作中因盲目冲动而失误，咄咄逼人影响团队',
                           `health_meaning`  = '健康方面行动迅速，但需注意冲动行为带来的伤害',
                           `health_reversed` = '健康上因冲动而造成伤害，需要更谨慎',
                           `wealth_meaning`  = '财运上行动迅速，但需注意冲动投资带来的风险',
                           `wealth_reversed` = '财务上因冲动投资而失误，需要更谨慎'
WHERE `name` = '宝剑骑士' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中独立理性，公正对待感情，以智慧处理关系',
                           `love_reversed`   = '感情中因冷酷而导致关系紧张，过于批判影响感情',
                           `career_meaning`  = '工作中独立理性，公正处事，适合需要分析和判断的工作',
                           `career_reversed` = '工作中因冷酷而失去同事信任，过于批判影响团队',
                           `health_meaning`  = '健康方面理性面对健康问题，适合认知疗法',
                           `health_reversed` = '健康上因过于批判而影响心理健康',
                           `wealth_meaning`  = '财运上理性投资，公正评估财务状况',
                           `wealth_reversed` = '财务上因冷酷而失去合作机会，过于批判影响财务'
WHERE `name` = '宝剑皇后' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中以智慧和公正处理感情，是可靠的伴侣',
                           `love_reversed`   = '感情中因滥用权威而失去伴侣信任',
                           `career_meaning`  = '工作中以智慧和权威领导，公正处事，适合高层管理',
                           `career_reversed` = '工作中因滥用职权而失去团队信任',
                           `health_meaning`  = '健康方面以智慧面对健康问题，适合认知疗法',
                           `health_reversed` = '健康上因独断专行而忽视健康建议',
                           `wealth_meaning`  = '财运上以智慧和权威管理财务，公正评估投资',
                           `wealth_reversed` = '财务上因滥用权威而失去合作机会'
WHERE `name` = '宝剑国王' AND `type` = 'minor';

-- =====================================================
-- 小阿卡纳 - 星币组（14张）
-- =====================================================
UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中建立稳固的物质基础，关系走向实际和稳定',
                           `love_reversed`   = '感情中物质基础不稳，关系缺乏安全感',
                           `career_meaning`  = '事业上迎来新的财务机遇，适合开始新的商业项目',
                           `career_reversed` = '工作中财务机遇受阻，新项目难以启动',
                           `health_meaning`  = '健康方面建立稳固的健康基础，适合开始新的健康计划',
                           `health_reversed` = '健康上健康基础不稳，新的健康计划难以启动',
                           `wealth_meaning`  = '财运上迎来新的财务机遇，适合开始新的投资计划',
                           `wealth_reversed` = '财务上新的投资机遇受阻，需要重新规划'
WHERE `name` = '星币一' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中保持平衡，适应变化，灵活处理感情问题',
                           `love_reversed`   = '感情中因应接不暇而失衡，关系出现混乱',
                           `career_meaning`  = '工作中保持平衡，适应变化，善于处理多重任务',
                           `career_reversed` = '工作中因应接不暇而失衡，工作出现混乱',
                           `health_meaning`  = '健康方面保持平衡，适应变化，灵活调整健康计划',
                           `health_reversed` = '健康上因应接不暇而失衡，健康计划出现混乱',
                           `wealth_meaning`  = '财运上保持财务平衡，适应市场变化',
                           `wealth_reversed` = '财务上因应接不暇而失衡，财务出现混乱'
WHERE `name` = '星币二' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中团队合作，共同建立关系，技能互补',
                           `love_reversed`   = '感情中因沟通不畅而产生矛盾，合作出现问题',
                           `career_meaning`  = '工作中团队合作，技能得到认可，项目顺利推进',
                           `career_reversed` = '工作中因沟通不畅而导致团队破裂，技能不足',
                           `health_meaning`  = '健康方面团队合作，共同维护健康，技能互补',
                           `health_reversed` = '健康上因沟通不畅而影响健康合作',
                           `wealth_meaning`  = '财运上团队合作，共同创造财富，技能得到认可',
                           `wealth_reversed` = '财务上因沟通不畅而导致合作破裂'
WHERE `name` = '星币三' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中保守稳定，但可能因占有欲强而产生矛盾',
                           `love_reversed`   = '感情中因过度控制而影响关系，需要放手',
                           `career_meaning`  = '工作中保守稳定，善于保护资源，但可能因贪婪而失去机会',
                           `career_reversed` = '工作中因过度控制而影响团队，需要放手',
                           `health_meaning`  = '健康方面保守稳定，但可能因过度控制而影响健康',
                           `health_reversed` = '健康上因过度控制而影响健康，需要放松',
                           `wealth_meaning`  = '财运上保守稳定，善于保护财富，但可能因贪婪而错失机会',
                           `wealth_reversed` = '财务上因过度控制而影响财务灵活性'
WHERE `name` = '星币四' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中经历物质困境，关系面临考验，需要相互支持',
                           `love_reversed`   = '感情中处境逐渐好转，关系开始改善',
                           `career_meaning`  = '工作中经历物质困境，可能面临失业或财务压力',
                           `career_reversed` = '工作中处境逐渐好转，开始获得外界援助',
                           `health_meaning`  = '健康方面经历健康困境，需要外界援助',
                           `health_reversed` = '健康上处境逐渐好转，开始获得健康援助',
                           `wealth_meaning`  = '财运上经历财务困境，需要外界援助',
                           `wealth_reversed` = '财务上处境逐渐好转，开始获得财务援助'
WHERE `name` = '星币五' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中慷慨给予，关系平衡，相互支持',
                           `love_reversed`   = '感情中因自私而导致失衡，关系出现纠纷',
                           `career_meaning`  = '工作中慷慨分享，团队平衡，公平对待',
                           `career_reversed` = '工作中因自私而导致失衡，团队出现纠纷',
                           `health_meaning`  = '健康方面慷慨给予，适合志愿服务和帮助他人',
                           `health_reversed` = '健康上因自私而影响健康关系',
                           `wealth_meaning`  = '财运上慷慨给予，财务平衡，公平交易',
                           `wealth_reversed` = '财务上因自私而导致财务纠纷'
WHERE `name` = '星币六' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中耐心等待，评估关系，长期投资感情',
                           `love_reversed`   = '感情中因缺乏耐心而功亏一篑，急于求成',
                           `career_meaning`  = '工作中耐心等待，评估项目，长期投资事业',
                           `career_reversed` = '工作中因缺乏耐心而功亏一篑，急于求成',
                           `health_meaning`  = '健康方面耐心等待，评估健康状况，长期投资健康',
                           `health_reversed` = '健康上因缺乏耐心而放弃健康计划',
                           `wealth_meaning`  = '财运上耐心等待，评估投资，长期投资有回报',
                           `wealth_reversed` = '财务上因缺乏耐心而功亏一篑，急于求成'
WHERE `name` = '星币七' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中勤奋经营，精进技艺，专注于关系的发展',
                           `love_reversed`   = '感情中因缺乏专注而导致关系停滞',
                           `career_meaning`  = '工作中勤奋工作，精进技艺，专注于职业发展',
                           `career_reversed` = '工作中因缺乏专注而导致工作停滞',
                           `health_meaning`  = '健康方面勤奋锻炼，精进健康技艺，专注于健康目标',
                           `health_reversed` = '健康上因缺乏专注而导致健康计划停滞',
                           `wealth_meaning`  = '财运上勤奋积累，精进财务技艺，专注于财务目标',
                           `wealth_reversed` = '财务上因缺乏专注而导致财务停滞'
WHERE `name` = '星币八' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中独立自主，富足满足，不依赖他人',
                           `love_reversed`   = '感情中因过分炫耀而导致关系危机，孤独',
                           `career_meaning`  = '工作中独立自主，富足满足，事业有成',
                           `career_reversed` = '工作中因过分炫耀而导致职业危机',
                           `health_meaning`  = '健康方面独立自主，富足满足，健康状况良好',
                           `health_reversed` = '健康上因过分炫耀而导致健康危机',
                           `wealth_meaning`  = '财运旺盛，独立富足，财务自给自足',
                           `wealth_reversed` = '财务上因过分炫耀而导致财务危机'
WHERE `name` = '星币九' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中家族支持，财富稳定，关系长久稳定',
                           `love_reversed`   = '感情中家庭内部出现矛盾，财务纠纷影响关系',
                           `career_meaning`  = '工作中家族事业，财富传承，长期稳定',
                           `career_reversed` = '工作中家族内部出现矛盾，财务纠纷影响工作',
                           `health_meaning`  = '健康方面家族健康传承，长期稳定的健康状况',
                           `health_reversed` = '健康上家族健康问题，需要关注遗传性疾病',
                           `wealth_meaning`  = '财运旺盛，家族财富，长期稳定的财务状况',
                           `wealth_reversed` = '财务上家庭内部出现财务纠纷，需要解决'
WHERE `name` = '星币十' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中有新的感情消息，充满实际和勤奋',
                           `love_reversed`   = '感情中因不切实际而错失感情机遇',
                           `career_meaning`  = '工作中有新的工作机遇，充满勤奋和学习精神',
                           `career_reversed` = '工作中因不切实际而错失工作机遇',
                           `health_meaning`  = '健康方面有新的健康消息，充满学习精神',
                           `health_reversed` = '健康上因不切实际而选择了不适合的健康方法',
                           `wealth_meaning`  = '财运上有新的财务机遇，充满勤奋和学习精神',
                           `wealth_reversed` = '财务上因不切实际而错失财务机遇'
WHERE `name` = '星币侍从' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中稳健可靠，责任心强，是值得信赖的伴侣',
                           `love_reversed`   = '感情中因过分保守而缺乏激情，关系一成不变',
                           `career_meaning`  = '工作中稳健可靠，责任心强，适合需要坚持的工作',
                           `career_reversed` = '工作中因过分保守而缺乏创新，工作一成不变',
                           `health_meaning`  = '健康方面稳健可靠，坚持健康计划，责任心强',
                           `health_reversed` = '健康上因过分保守而缺乏灵活性，健康计划一成不变',
                           `wealth_meaning`  = '财运上稳健可靠，责任心强，长期投资有回报',
                           `wealth_reversed` = '财务上因过分保守而缺乏灵活性，错失投资机会'
WHERE `name` = '星币骑士' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中富饶务实，给予安全感，是温暖的伴侣',
                           `love_reversed`   = '感情中因缺乏安全感而过度依赖，物质主义影响关系',
                           `career_meaning`  = '工作中富饶务实，给予团队安全感，适合管理岗位',
                           `career_reversed` = '工作中因缺乏安全感而过度依赖，物质主义影响工作',
                           `health_meaning`  = '健康方面富饶务实，给予身体安全感，适合养生',
                           `health_reversed` = '健康上因缺乏安全感而过度依赖，影响健康',
                           `wealth_meaning`  = '财运旺盛，富饶务实，给予财务安全感',
                           `wealth_reversed` = '财务上因缺乏安全感而过度依赖，物质主义影响财务'
WHERE `name` = '星币皇后' AND `type` = 'minor';

UPDATE `tc_tarot_card` SET
                           `love_meaning`    = '感情中成功稳健，财富丰厚，是可靠的伴侣',
                           `love_reversed`   = '感情中因利欲熏心而失去伴侣信任',
                           `career_meaning`  = '工作中成功稳健，财富丰厚，适合高层管理',
                           `career_reversed` = '工作中因利欲熏心而失去团队信任',
                           `health_meaning`  = '健康方面成功稳健，财富丰厚，适合高端健康管理',
                           `health_reversed` = '健康上因贪婪而忽视健康，需要重新关注',
                           `wealth_meaning`  = '财运旺盛，成功稳健，财富丰厚',
                           `wealth_reversed` = '财务上因利欲熏心而失去信誉，财务受损'
WHERE `name` = '星币国王' AND `type` = 'minor';



-- 修复 tc_invite_record 表 invitee_id 字段允许 NULL
-- 问题：getOrCreateInviteCode 创建邀请码记录时 invitee_id 默认为 0，
--       多个用户创建邀请码时触发 uk_invitee 唯一键冲突（Duplicate entry '0' for key 'uk_invitee'）
-- 修复：将 invitee_id 改为允许 NULL，MySQL 唯一键允许多个 NULL 值共存

ALTER TABLE `tc_invite_record`
    MODIFY COLUMN `invitee_id` INT UNSIGNED NULL DEFAULT NULL
        COMMENT '被邀请人ID（邀请码记录时为NULL，实际邀请后填写）';

-- 将已有的 invitee_id = 0 的邀请码记录（非真实邀请记录）更新为 NULL
UPDATE `tc_invite_record`
    SET `invitee_id` = NULL
    WHERE `invitee_id` = 0;


-- 创建 tc_user_vip 表（VIP会员表）
CREATE TABLE IF NOT EXISTS `tc_user_vip` (
                                             `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                             `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                                             `vip_type` VARCHAR(20) NOT NULL DEFAULT 'month' COMMENT 'VIP类型：month/quarter/year',
    `start_time` DATETIME NOT NULL COMMENT '开始时间',
    `end_time` DATETIME NOT NULL COMMENT '到期时间',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1有效 2已过期',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_user` (`user_id`),
    INDEX `idx_status_end` (`status`, `end_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户VIP会员表';

-- 创建 tc_task_log 表（任务完成日志表）
CREATE TABLE IF NOT EXISTS `tc_task_log` (
                                             `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                             `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                                             `task_id` VARCHAR(50) NOT NULL COMMENT '任务ID',
    `task_type` VARCHAR(20) NOT NULL DEFAULT 'daily' COMMENT '任务类型：daily/once/unlimited',
    `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_task` (`user_id`, `task_id`),
    INDEX `idx_user_created` (`user_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务完成日志表';

-- 创建 tc_checkin_record 表（每日签到记录表）
CREATE TABLE IF NOT EXISTS `tc_checkin_record` (
                                                   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                                   `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                                                   `date` DATE NOT NULL COMMENT '签到日期',
                                                   `consecutive_days` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '连续签到天数',
                                                   `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
                                                   `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                                                   UNIQUE KEY `uniq_user_date` (`user_id`, `date`),
    INDEX `idx_user_date` (`user_id`, `date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日签到记录表';

-- 创建 tc_checkin_log 表（Task.php 使用的签到日志表）
CREATE TABLE IF NOT EXISTS `tc_checkin_log` (
                                                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                                `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                                                `checkin_date` DATE NOT NULL COMMENT '签到日期',
                                                `points` INT NOT NULL DEFAULT 0 COMMENT '获得积分',
                                                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                                                UNIQUE KEY `uniq_user_date` (`user_id`, `checkin_date`),
    INDEX `idx_user_date` (`user_id`, `checkin_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务签到日志表';
