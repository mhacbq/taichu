<?php
declare(strict_types=1);

namespace app\service;

class TarotElementService
{
    private const ELEMENT_ASPECTS = [
        '风' => '理性思考',
        '水' => '情感感受',
        '火' => '行动执行',
        '土' => '物质现实',
    ];

    private const ELEMENT_ALIASES = [
        '风' => '风', 'air' => '风', 'wind' => '风', 'sword' => '风', 'swords' => '风', '宝剑' => '风',
        '水' => '水', 'water' => '水', 'cup' => '水', 'cups' => '水', '圣杯' => '水',
        '火' => '火', 'fire' => '火', 'wand' => '火', 'wands' => '火', '权杖' => '火',
        '土' => '土', 'earth' => '土', 'pentacle' => '土', 'pentacles' => '土', 'coin' => '土', 'coins' => '土', 'disk' => '土', 'disks' => '土', '星币' => '土', '金币' => '土', '钱币' => '土', '圆盘' => '土',
    ];

    private const CARD_NAME_ELEMENT_MAP = [
        '愚者' => '风',
        '魔术师' => '风',
        '女祭司' => '水',
        '皇后' => '土',
        '皇帝' => '火',
        '教皇' => '土',
        '恋人' => '风',
        '战车' => '水',
        '力量' => '火',
        '隐者' => '土',
        '命运之轮' => '火',
        '正义' => '风',
        '倒吊人' => '水',
        '死神' => '水',
        '节制' => '火',
        '恶魔' => '土',
        '塔' => '火',
        '星星' => '风',
        '月亮' => '水',
        '太阳' => '火',
        '审判' => '火',
        '世界' => '土',
    ];

    private const RELATION_MAP = [
        '友好尊严' => [
            '土-水' => '土承水意，情绪获得承载，现实层面更容易稳步落地。',
            '火-风' => '风助火势，意志得到思想鼓动，行动更容易迅速展开。',
        ],
        '敌对尊严' => [
            '土-风' => '风土相逆，理念表达受现实框架压制，推进阻力较大。',
            '水-火' => '水火相激，感受与行动彼此牵制，局势容易出现内耗。',
        ],
        '中性尊严' => [
            '土-火' => '火土相接，行动有落点，但速度会被现实节奏放缓。',
            '水-风' => '水风并行，理性与感受同时发声，需要整合后才能形成结论。',
        ],
    ];

    public static function normalizeElement(string $element): string
    {
        $element = trim($element);
        if ($element === '') {
            return '';
        }

        $normalizedKey = mb_strtolower($element, 'UTF-8');
        return self::ELEMENT_ALIASES[$normalizedKey] ?? self::ELEMENT_ALIASES[$element] ?? '';
    }

    public static function resolveCardElement(array $card): string
    {
        foreach (['element', 'element_cn', 'elementName'] as $field) {
            $element = self::normalizeElement((string)($card[$field] ?? ''));
            if ($element !== '') {
                return $element;
            }
        }

        foreach (['suit', 'suit_name', 'group', 'arcana_group'] as $field) {
            $element = self::normalizeElement((string)($card[$field] ?? ''));
            if ($element !== '') {
                return $element;
            }
        }

        $name = trim((string)($card['name'] ?? ''));
        if ($name !== '' && isset(self::CARD_NAME_ELEMENT_MAP[$name])) {
            return self::CARD_NAME_ELEMENT_MAP[$name];
        }

        foreach (['权杖' => '火', '圣杯' => '水', '宝剑' => '风', '星币' => '土', '金币' => '土', '钱币' => '土', '圆盘' => '土'] as $marker => $element) {
            if ($name !== '' && mb_strpos($name, $marker, 0, 'UTF-8') !== false) {
                return $element;
            }
        }

        return '';
    }

    public static function normalizePairKey(string $left, string $right): string
    {
        $left = self::normalizeElement($left);
        $right = self::normalizeElement($right);
        if ($left === '' || $right === '') {
            return '';
        }

        if ($left === $right) {
            return $left . '-' . $right;
        }

        $pair = [$left, $right];
        sort($pair, SORT_STRING);

        return implode('-', $pair);
    }

    public static function getAspect(string $element): string
    {
        $element = self::normalizeElement($element);
        return self::ELEMENT_ASPECTS[$element] ?? '内在能量';
    }

    public static function formatRelation(string $left, string $right): string
    {
        $left = self::normalizeElement($left);
        $right = self::normalizeElement($right);
        if ($left === '' || $right === '') {
            return '';
        }

        if ($left === $right) {
            return '同元素共鸣：主题被持续放大，牌阵焦点十分明确。';
        }

        $pairKey = self::normalizePairKey($left, $right);
        foreach (self::RELATION_MAP as $label => $relations) {
            if (isset($relations[$pairKey])) {
                return $label . '：' . $relations[$pairKey];
            }
        }

        return '';
    }

    public static function describeTransition(array $from, array $to): string
    {
        $fromElement = self::resolveCardElement($from);
        $toElement = self::resolveCardElement($to);
        $fromName = (string)($from['name'] ?? '前牌');
        $toName = (string)($to['name'] ?? '后牌');

        if ($fromElement === '' || $toElement === '') {
            return "元素线索不足，暂以牌义推进为主，{$fromName} → {$toName}";
        }

        if ($fromElement === $toElement) {
            $fromMeaning = (string)(($from['reversed'] ?? false) ? (($from['reversed_meaning'] ?? '') ?: ($from['meaning'] ?? '')) : ($from['meaning'] ?? ''));
            $toMeaning = (string)(($to['reversed'] ?? false) ? (($to['reversed_meaning'] ?? '') ?: ($to['meaning'] ?? '')) : ($to['meaning'] ?? ''));

            return "同元素{$fromElement}的深化，从「{$fromMeaning}」发展为「{$toMeaning}」";
        }

        $relation = self::formatRelation($fromElement, $toElement);
        $fromAspect = self::getAspect($fromElement);
        $toAspect = self::getAspect($toElement);

        if ($relation !== '') {
            return "{$relation} 当前更像从{$fromAspect}转向{$toAspect}，{$fromName} → {$toName}";
        }

        return "从{$fromAspect}转向{$toAspect}，{$fromName} → {$toName}";
    }
}
