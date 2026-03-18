SET NAMES utf8mb4;

USE taichu;

-- 修复历史神煞数据中的 ?? / ???? / 乱码占位。
-- 仅针对默认种子对应的 sort + type + category 组合执行兜底回填，避免误伤自定义神煞。
UPDATE `tc_shensha`
SET
    `name` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '天乙贵人'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '文昌贵人'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '太极贵人'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '天德贵人'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '月德贵人'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '福星贵人'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '桃花'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '羊刃'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '劫煞'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '孤辰'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '寡宿'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '阴差阳错'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '十恶大败'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '金舆'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '华盖'
        ELSE `name`
    END,
    `description` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '最吉之神，命中逢之，遇事有人帮，遇危难有人救'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '主聪明好学，利文途考学'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '主人聪明好学，喜神秘文化'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '天地德秀之气，逢凶化吉之神'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '乃太阴之德，功能与天德略同而稍逊'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '主人一生福禄无缺，格局配合得当，必然多福多寿'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '主人漂亮多情，风流潇洒'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '司刑之星，性情刚强'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '主破财、阻碍'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '主孤独，不利婚姻'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '主孤独，不利婚姻'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '主婚姻不顺'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '主不善理财，花钱大手大脚'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '主富贵，聪明富贵'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '主聪明好学，喜艺术、宗教'
        ELSE `description`
    END,
    `effect` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '遇难成祥，逢凶化吉，人缘极佳，易得他人帮助'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '聪明过人，学业有成，考试顺利，利于文职'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '悟性高，对命理、宗教、玄学有兴趣，逢凶化吉'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '一生安逸，不犯刑律，不遇凶祸，福气好'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '逢凶化吉，灾少福多，一生少病痛'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '一生福禄无缺，享福深厚，平安幸福'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '人缘好，异性缘佳，感情丰富，但可能感情复杂'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '性格刚烈，易有刀伤手术，但也代表能力强'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '破财、阻碍、是非、意外'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '孤独，少依靠，婚姻不顺，与亲人缘薄'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '孤独，婚姻不顺，女命尤其注意'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '婚姻不利，夫妻不和，男克妻女克夫'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '不善理财，花钱如流水，难以积蓄'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '聪明富贵，性柔貌愿，举止温和'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '聪明好学，喜艺术、玄学、宗教，有出世思想'
        ELSE `effect`
    END,
    `check_rule` = CASE
        WHEN `sort` = 1 AND `type` = 'daji' AND `category` = 'guiren' THEN '甲戊见牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方'
        WHEN `sort` = 2 AND `type` = 'ji' AND `category` = 'xueye' THEN '甲乙巳午报君知，丙戊申宫丁己鸡，庚猪辛鼠壬逢虎，癸人见卯入云梯'
        WHEN `sort` = 3 AND `type` = 'ji' AND `category` = 'guiren' THEN '甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美'
        WHEN `sort` = 4 AND `type` = 'daji' AND `category` = 'guiren' THEN '正丁二坤宫，三壬四辛同，五乾六甲上，七癸八艮逢，九丙十居乙，子巽丑庚中'
        WHEN `sort` = 5 AND `type` = 'daji' AND `category` = 'guiren' THEN '寅午戌月在丙，申子辰月在壬，亥卯未月在甲，巳酉丑月在庚'
        WHEN `sort` = 6 AND `type` = 'ji' AND `category` = 'guiren' THEN '甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常'
        WHEN `sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing' THEN '申子辰在酉，巳酉丑在午，亥卯未在子，寅午戌在卯'
        WHEN `sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang' THEN '甲刃在卯，乙刃在寅，丙戊刃在午，丁己刃在巳，庚刃在酉，辛刃在申，壬刃在子，癸刃在亥'
        WHEN `sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '申子辰见巳，亥卯未见申，寅午戌见亥，巳酉丑见寅'
        WHEN `sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '亥子丑人，见寅为孤辰，见戌为寡宿'
        WHEN `sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '亥子丑人，见戌为寡宿，见寅为孤辰'
        WHEN `sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing' THEN '丙子、丁丑、戊寅、辛卯、壬辰、癸巳、丙午、丁未、戊申、辛酉、壬戌、癸亥'
        WHEN `sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun' THEN '甲辰、乙巳、丙申、丁亥、戊戌、己丑、庚辰、辛巳、壬申、癸亥'
        WHEN `sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun' THEN '甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎'
        WHEN `sort` = 15 AND `type` = 'ping' AND `category` = 'guiren' THEN '寅午戌见戌，巳酉丑见丑，申子辰见辰，亥卯未见未'
        ELSE `check_rule`
    END,
    `updated_at` = NOW()
WHERE (
    (`name` <> '' AND `name` NOT REGEXP '[一-龥]')
    OR (`description` <> '' AND `description` NOT REGEXP '[一-龥]')
    OR (`effect` <> '' AND `effect` NOT REGEXP '[一-龥]')
    OR (`check_rule` <> '' AND `check_rule` NOT REGEXP '[一-龥]')
)
AND (
    (`sort` = 1 AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 2 AND `type` = 'ji' AND `category` = 'xueye')
    OR (`sort` = 3 AND `type` = 'ji' AND `category` = 'guiren')
    OR (`sort` = 4 AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 5 AND `type` = 'daji' AND `category` = 'guiren')
    OR (`sort` = 6 AND `type` = 'ji' AND `category` = 'guiren')
    OR (`sort` = 7 AND `type` = 'ping' AND `category` = 'ganqing')
    OR (`sort` = 8 AND `type` = 'xiong' AND `category` = 'jiankang')
    OR (`sort` = 9 AND `type` = 'xiong' AND `category` = 'caiyun')
    OR (`sort` = 10 AND `type` = 'xiong' AND `category` = 'ganqing')
    OR (`sort` = 11 AND `type` = 'xiong' AND `category` = 'ganqing')
    OR (`sort` = 12 AND `type` = 'xiong' AND `category` = 'ganqing')
    OR (`sort` = 13 AND `type` = 'xiong' AND `category` = 'caiyun')
    OR (`sort` = 14 AND `type` = 'ji' AND `category` = 'caiyun')
    OR (`sort` = 15 AND `type` = 'ping' AND `category` = 'guiren')
);
