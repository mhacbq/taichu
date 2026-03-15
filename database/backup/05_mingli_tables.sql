-- =====================================================
-- 命理专业功能数据库表
-- 包含神煞、黄历、命理知识库等
-- =====================================================

USE taichu;

-- =====================================================
-- 1. 神煞表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_shensha` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '神煞名称',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: daji大吉 ji吉 ping平 xiong凶 daxiong大凶',
    `category` VARCHAR(30) NOT NULL COMMENT '分类: guiren贵人 xueye学业 ganqing感情 jiankang健康 caiyun财运 qita其他',
    `description` VARCHAR(500) NOT NULL COMMENT '含义说明',
    `effect` VARCHAR(500) COMMENT '影响描述',
    `check_rule` TEXT NOT NULL COMMENT '查法规则说明',
    `check_code` TEXT COMMENT '查法实现代码',
    `gan_rules` JSON COMMENT '天干查法规则 {甲:["丑","未"],乙:["子","未"]}',
    `zhi_rules` JSON COMMENT '地支查法规则 {子:["酉"],午:["卯"]}',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_type` (`type`),
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='神煞表';

-- 插入常用神煞数据
INSERT INTO `tc_shensha` (`name`, `type`, `category`, `description`, `effect`, `check_rule`, `gan_rules`, `zhi_rules`, `sort`) VALUES
('天乙贵人', 'daji', 'guiren', '最吉之神，命中逢之，遇事有人帮，遇危难有人救', '遇难成祥，逢凶化吉，人缘极佳，易得他人帮助', '甲戊见牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方', '{"甲":["丑","未"],"戊":["丑","未"],"乙":["子","未"],"己":["子","未"],"丙":["亥","酉"],"丁":["亥","酉"],"壬":["卯","巳"],"癸":["卯","巳"],"庚":["午","寅"],"辛":["午","寅"]}', NULL, 1),
('文昌贵人', 'ji', 'xueye', '主聪明好学，利文途考学', '聪明过人，学业有成，考试顺利，利于文职', '甲乙巳午报君知，丙戊申宫丁己鸡，庚猪辛鼠壬逢虎，癸人见卯入云梯', '{"甲":["巳","午"],"乙":["巳","午"],"丙":["申"],"戊":["申"],"丁":["酉"],"己":["酉"],"庚":["亥"],"辛":["子"],"壬":["寅"],"癸":["卯"]}', NULL, 2),
('太极贵人', 'ji', 'guiren', '主人聪明好学，喜神秘文化', '悟性高，对命理、宗教、玄学有兴趣，逢凶化吉', '甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美', '{"甲":["子","午"],"乙":["子","午"],"丙":["酉","卯"],"丁":["酉","卯"],"戊":["辰","戌","丑","未"],"己":["辰","戌","丑","未"],"庚":["寅","亥"],"辛":["寅","亥"],"壬":["巳","申"],"癸":["巳","申"]}', NULL, 3),
('天德贵人', 'daji', 'guiren', '天地德秀之气，逢凶化吉之神', '一生安逸，不犯刑律，不遇凶祸，福气好', '正丁二坤宫，三壬四辛同，五乾六甲上，七癸八艮逢，九丙十居乙，子巽丑庚中', NULL, '{"子":["巳"],"丑":["庚"],"寅":["丁"],"卯":["申"],"辰":["壬"],"巳":["辛"],"午":["甲"],"未":["癸"],"申":["寅"],"酉":["丙"],"戌":["乙"],"亥":["巳"]}', 4),
('月德贵人', 'daji', 'guiren', '乃太阴之德，功能与天德略同而稍逊', '逢凶化吉，灾少福多，一生少病痛', '寅午戌月在丙，申子辰月在壬，亥卯未月在甲，巳酉丑月在庚', NULL, '{"寅":["丙"],"午":["丙"],"戌":["丙"],"申":["壬"],"子":["壬"],"辰":["壬"],"亥":["甲"],"卯":["甲"],"未":["甲"],"巳":["庚"],"酉":["庚"],"丑":["庚"]}', 5),
('福星贵人', 'ji', 'guiren', '主人一生福禄无缺，格局配合得当，必然多福多寿', '一生福禄无缺，享福深厚，平安幸福', '甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常', '{"甲":["寅","子"],"丙":["寅","子"],"戊":["申"],"己":["未"],"丁":["亥"],"乙":["丑","卯"],"癸":["丑","卯"],"庚":["午"],"辛":["巳"],"壬":["辰"]}', NULL, 6),
('桃花', 'ping', 'ganqing', '主人漂亮多情，风流潇洒', '人缘好，异性缘佳，感情丰富，但可能感情复杂', '申子辰在酉，巳酉丑在午，亥卯未在子，寅午戌在卯', NULL, '{"申":["酉"],"子":["酉"],"辰":["酉"],"巳":["午"],"酉":["午"],"丑":["午"],"亥":["子"],"卯":["子"],"未":["子"],"寅":["卯"],"午":["卯"],"戌":["卯"]}', 7),
('羊刃', 'xiong', 'jiankang', '司刑之星，性情刚强', '性格刚烈，易有刀伤手术，但也代表能力强', '甲刃在卯，乙刃在寅，丙戊刃在午，丁己刃在巳，庚刃在酉，辛刃在申，壬刃在子，癸刃在亥', '{"甲":["卯"],"乙":["寅"],"丙":["午"],"戊":["午"],"丁":["巳"],"己":["巳"],"庚":["酉"],"辛":["申"],"壬":["子"],"癸":["亥"]}', NULL, 8),
('劫煞', 'xiong', 'caiyun', '主破财、阻碍', '破财、阻碍、是非、意外', '申子辰见巳，亥卯未见申，寅午戌见亥，巳酉丑见寅', NULL, '{"申":["巳"],"子":["巳"],"辰":["巳"],"亥":["申"],"卯":["申"],"未":["申"],"寅":["亥"],"午":["亥"],"戌":["亥"],"巳":["寅"],"酉":["寅"],"丑":["寅"]}', 9),
('孤辰', 'xiong', 'ganqing', '主孤独，不利婚姻', '孤独，少依靠，婚姻不顺，与亲人缘薄', '亥子丑人，见寅为孤辰，见戌为寡宿', NULL, '{"亥":["寅"],"子":["寅"],"丑":["寅"]}', 10),
('寡宿', 'xiong', 'ganqing', '主孤独，不利婚姻', '孤独，婚姻不顺，女命尤其注意', '亥子丑人，见戌为寡宿，见寅为孤辰', NULL, '{"亥":["戌"],"子":["戌"],"丑":["戌"]}', 11),
('阴差阳错', 'xiong', 'ganqing', '主婚姻不顺', '婚姻不利，夫妻不和，男克妻女克夫', '丙子、丁丑、戊寅、辛卯、壬辰、癸巳、丙午、丁未、戊申、辛酉、壬戌、癸亥', NULL, NULL, 12),
('十恶大败', 'xiong', 'caiyun', '主不善理财，花钱大手大脚', '不善理财，花钱如流水，难以积蓄', '甲辰、乙巳、丙申、丁亥、戊戌、己丑、庚辰、辛巳、壬申、癸亥', NULL, NULL, 13),
('金舆', 'ji', 'caiyun', '主富贵，聪明富贵', '聪明富贵，性柔貌愿，举止温和', '甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎', '{"甲":["辰"],"乙":["巳"],"丙":["未"],"戊":["未"],"丁":["申"],"己":["申"],"庚":["戌"],"辛":["亥"],"壬":["丑"],"癸":["寅"]}', NULL, 14),
('华盖', 'ping', 'guiren', '主聪明好学，喜艺术、宗教', '聪明好学，喜艺术、玄学、宗教，有出世思想', '寅午戌见戌，巳酉丑见丑，申子辰见辰，亥卯未见未', NULL, '{"寅":["戌"],"午":["戌"],"戌":["戌"],"巳":["丑"],"酉":["丑"],"丑":["丑"],"申":["辰"],"子":["辰"],"辰":["辰"],"亥":["未"],"卯":["未"],"未":["未"]}', 15);

-- =====================================================
-- 2. 黄历表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_almanac` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `solar_date` DATE NOT NULL COMMENT '公历日期',
    `lunar_date` VARCHAR(50) COMMENT '农历日期',
    `lunar_year` VARCHAR(20) COMMENT '农历年',
    `lunar_month` TINYINT COMMENT '农历月',
    `lunar_day` TINYINT COMMENT '农历日',
    `ganzhi_year` VARCHAR(10) COMMENT '年柱干支',
    `ganzhi_month` VARCHAR(10) COMMENT '月柱干支',
    `ganzhi_day` VARCHAR(10) COMMENT '日柱干支',
    `nayin` VARCHAR(50) COMMENT '纳音',
    `constellation` VARCHAR(20) COMMENT '星座',
    `xingsu` VARCHAR(20) COMMENT '星宿',
    `pengzu` VARCHAR(100) COMMENT '彭祖百忌',
    `yi` JSON COMMENT '宜 ["嫁娶","出行",...]',
    `ji` JSON COMMENT '忌 ["动土","安葬",...]',
    `chong` VARCHAR(10) COMMENT '冲',
    `sha` VARCHAR(10) COMMENT '煞方',
    `chong_desc` VARCHAR(200) COMMENT '冲煞说明',
    `zhiri` VARCHAR(10) COMMENT '十二值日',
    `xiu` VARCHAR(20) COMMENT '二十八宿',
    `taishen` VARCHAR(100) COMMENT '胎神占方',
    `jishen` JSON COMMENT '吉神 ["天乙贵人","文昌贵人",...]',
    `xiongsha` JSON COMMENT '凶煞 ["劫煞","羊刃",...]',
    `shichen` JSON COMMENT '时辰吉凶 [{"name":"子时","time":"23:00-01:00","type":"xiong","yiji":""},...]',
    `shengxiao_teji` VARCHAR(50) COMMENT '特吉生肖',
    `shengxiao_ciji` VARCHAR(50) COMMENT '次吉生肖',
    `shengxiao_daidai` VARCHAR(50) COMMENT '带衰生肖',
    `fangwei_xishen` VARCHAR(20) COMMENT '喜神方位',
    `fangwei_caishen` VARCHAR(20) COMMENT '财神方位',
    `fangwei_fushen` VARCHAR(20) COMMENT '福神方位',
    `fangwei_yanggui` VARCHAR(20) COMMENT '阳贵神方位',
    `fangwei_yingui` VARCHAR(20) COMMENT '阴贵神方位',
    `is_jieqi` TINYINT DEFAULT 0 COMMENT '是否节气日',
    `jieqi_name` VARCHAR(20) COMMENT '节气名称',
    `status` TINYINT DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_solar_date` (`solar_date`),
    INDEX `idx_lunar` (`lunar_year`, `lunar_month`, `lunar_day`),
    INDEX `idx_ganzhi` (`ganzhi_year`, `ganzhi_month`, `ganzhi_day`),
    INDEX `idx_jieqi` (`is_jieqi`, `jieqi_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='黄历表';

-- =====================================================
-- 3. 命理知识库表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_knowledge_category` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `icon` VARCHAR(100) COMMENT '图标',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_sort` (`sort`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='命理知识分类表';

-- 插入默认分类
INSERT INTO `tc_knowledge_category` (`name`, `icon`, `sort`) VALUES
('八字基础', 'calendar', 1),
('十神详解', 'user', 2),
('格局分析', 'grid', 3),
('大运流年', 'trend', 4),
('神煞解读', 'star', 5),
('塔罗入门', 'magic', 6),
('塔罗牌义', 'cards', 7),
('风水基础', 'compass', 8),
('易经入门', 'book', 9);

CREATE TABLE IF NOT EXISTS `tc_knowledge_article` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL COMMENT '分类ID',
    `title` VARCHAR(200) NOT NULL COMMENT '文章标题',
    `summary` VARCHAR(500) COMMENT '文章摘要',
    `cover` VARCHAR(500) COMMENT '封面图',
    `content` LONGTEXT COMMENT '文章内容（Markdown）',
    `level` VARCHAR(20) DEFAULT 'beginner' COMMENT '难度: beginner入门 intermediate进阶 advanced高级',
    `author` VARCHAR(50) COMMENT '作者',
    `read_count` INT UNSIGNED DEFAULT 0 COMMENT '阅读数',
    `like_count` INT UNSIGNED DEFAULT 0 COMMENT '点赞数',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT DEFAULT 1 COMMENT '状态 0草稿 1已发布',
    `publish_time` DATETIME COMMENT '发布时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category` (`category_id`),
    INDEX `idx_level` (`level`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sort` (`sort`),
    INDEX `idx_publish` (`publish_time`),
    FULLTEXT INDEX `idx_title_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='命理知识文章表';

-- =====================================================
-- 4. 用户反馈表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_user_feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '反馈类型: bazi八字 tarot塔罗 fortune运势 suggestion建议 bug_bug other其他',
    `content` TEXT NOT NULL COMMENT '反馈内容',
    `rating` TINYINT COMMENT '评分 1-5',
    `related_id` VARCHAR(50) COMMENT '关联记录ID',
    `related_type` VARCHAR(30) COMMENT '关联类型',
    `contact` VARCHAR(100) COMMENT '联系方式',
    `reply` TEXT COMMENT '回复内容',
    `replier_id` INT UNSIGNED COMMENT '回复人ID',
    `replied_at` DATETIME COMMENT '回复时间',
    `status` TINYINT DEFAULT 0 COMMENT '状态 0待处理 1已处理 2已回复',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- =====================================================
-- 5. 系统配置表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_system_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `value` TEXT COMMENT '配置值',
    `type` VARCHAR(20) DEFAULT 'string' COMMENT '类型: string int float json boolean',
    `description` VARCHAR(500) COMMENT '配置说明',
    `group` VARCHAR(50) DEFAULT 'general' COMMENT '分组',
    `is_system` TINYINT DEFAULT 0 COMMENT '是否系统配置 0否 1是',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_key` (`key`),
    INDEX `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- 插入默认配置
INSERT INTO `tc_system_config` (`key`, `value`, `type`, `description`, `group`, `is_system`) VALUES
('site.disclaimer', '本网站的命理分析仅供参考娱乐，不应作为人生重大决策的唯一依据。命理学说属于传统文化范畴，未经科学验证。如有健康、法律、投资等问题，请咨询相关专业人士。', 'string', '网站免责声明', 'site', 1),
('site.shichen_notice', '1', 'boolean', '是否显示时辰准确性提醒', 'site', 0),
('site.jieqi_notice', '1', 'boolean', '是否显示节气换月提醒', 'site', 0),
('site.dst_notice', '1', 'boolean', '是否显示夏令时提醒', 'site', 0),
('bazi.show_canggan', '1', 'boolean', '排盘是否显示藏干', 'bazi', 0),
('bazi.show_shishen', '1', 'boolean', '排盘是否显示十神', 'bazi', 0),
('bazi.show_shensha', '1', 'boolean', '排盘是否显示神煞', 'bazi', 0),
('bazi.show_nayin', '1', 'boolean', '排盘是否显示纳音', 'bazi', 0),
('bazi.show_zhangsheng', '1', 'boolean', '排盘是否显示十二长生', 'bazi', 0),
('bazi.show_taisyuan', '1', 'boolean', '排盘是否显示胎元', 'bazi', 0),
('bazi.show_minggong', '1', 'boolean', '排盘是否显示命宫', 'bazi', 0),
('bazi.show_kongwang', '1', 'boolean', '排盘是否显示空亡', 'bazi', 0),
('points.bazi_cost', '10', 'int', '八字排盘消耗积分', 'points', 0),
('points.tarot_cost', '5', 'int', '塔罗占卜消耗积分', 'points', 0),
('points.fortune_cost', '0', 'int', '每日运势消耗积分', 'points', 0);

-- =====================================================
-- 6. 节气表
-- =====================================================
CREATE TABLE IF NOT EXISTS `tc_jieqi` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `year` INT NOT NULL COMMENT '年份',
    `name` VARCHAR(20) NOT NULL COMMENT '节气名称',
    `solar_date` DATE NOT NULL COMMENT '公历日期',
    `solar_time` TIME COMMENT '具体时间',
    `ganzhi_month` VARCHAR(10) COMMENT '对应月柱',
    `description` VARCHAR(500) COMMENT '节气说明',
    `yang_sheng` TEXT COMMENT '养生建议',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `uk_year_name` (`year`, `name`),
    INDEX `idx_year` (`year`),
    INDEX `idx_date` (`solar_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节气表';

-- 插入2024-2026年节气数据示例
INSERT INTO `tc_jieqi` (`year`, `name`, `solar_date`, `solar_time`, `ganzhi_month`, `description`, `yang_sheng`) VALUES
(2026, '立春', '2026-02-04', '04:01:00', '庚寅', '春季开始，万物复苏', '早睡早起，多食辛甘发散之品'),
(2026, '雨水', '2026-02-18', '23:51:00', '庚寅', '降雨开始，雨量渐增', '调养脾胃，注意保暖'),
(2026, '惊蛰', '2026-03-05', '21:58:00', '辛卯', '春雷乍动，惊醒蛰虫', '保肝护肝，多吃清淡食物'),
(2026, '春分', '2026-03-20', '22:45:00', '辛卯', '昼夜平分，春季过半', '阴阳平衡，多参加户外活动'),
(2026, '清明', '2026-04-05', '02:39:00', '壬辰', '天气晴朗，草木繁茂', '养肝健脾，慎食发物'),
(2026, '谷雨', '2026-04-20', '09:38:00', '壬辰', '雨生百谷，降水增多', '健脾祛湿，少食生冷'),
(2026, '立夏', '2026-05-05', '19:48:00', '癸巳', '夏季开始，万物生长', '养心护心，午睡养神'),
(2026, '小满', '2026-05-21', '08:36:00', '癸巳', '麦类饱满，但尚未成熟', '清热利湿，饮食清淡');
