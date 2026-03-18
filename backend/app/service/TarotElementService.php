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

    public static function normalizePairKey(string $left, string $right): string
    {
        $left = trim($left);
        $right = trim($right);
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
        return self::ELEMENT_ASPECTS[$element] ?? '内在能量';
    }

    public static function formatRelation(string $left, string $right): string
    {
        $left = trim($left);
        $right = trim($right);
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
        $fromElement = trim((string)($from['element'] ?? ''));
        $toElement = trim((string)($to['element'] ?? ''));
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
