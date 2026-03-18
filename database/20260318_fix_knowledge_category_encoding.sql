SET NAMES utf8mb4;
USE taichu;

UPDATE `tc_article_category`
SET
  `name` = CASE `slug`
    WHEN 'bazi-basic' THEN '八字基础'
    WHEN 'shishen-geju' THEN '十神格局'
    WHEN 'dayun-liunian' THEN '大运流年'
    WHEN 'tarot-system' THEN '塔罗体系'
    WHEN 'fengshui-zeji' THEN '风水择吉'
    ELSE `name`
  END,
  `description` = CASE `slug`
    WHEN 'bazi-basic' THEN '八字入门、排盘基础、十神速查等基础内容'
    WHEN 'shishen-geju' THEN '十神、格局、旺衰与喜忌专题'
    WHEN 'dayun-liunian' THEN '大运、流年、流月专题文章'
    WHEN 'tarot-system' THEN '塔罗牌义、牌阵与占卜指引'
    WHEN 'fengshui-zeji' THEN '风水布局、择吉黄历与应用文章'
    ELSE `description`
  END
WHERE `slug` IN ('bazi-basic', 'shishen-geju', 'dayun-liunian', 'tarot-system', 'fengshui-zeji')
  AND (`name` IS NULL OR `name` = '' OR `name` NOT REGEXP '[一-龥]');
