<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use app\model\DailyFortune;
use app\service\LunarService;


$reflection = new ReflectionClass(DailyFortune::class);
$buildMethod = $reflection->getMethod('buildFortunePayload');
$buildMethod->setAccessible(true);
$descriptionMethod = $reflection->getMethod('getAspectDescription');
$descriptionMethod->setAccessible(true);
$legacyConst = $reflection->getReflectionConstant('LEGACY_DESCRIPTIONS');
$legacyDescriptions = is_object($legacyConst) ? $legacyConst->getValue() : [];

$dates = ['2026-03-18', '2026-03-19', '2026-06-01'];
$aspectMap = [
    'career' => 'career_desc',
    'wealth' => 'wealth_desc',
    'love' => 'love_desc',
    'health' => 'health_desc',
];

$results = [];
$failures = [];

foreach ($dates as $date) {
    $first = $buildMethod->invoke(null, $date);
    $second = $buildMethod->invoke(null, $date);
    $almanac = LunarService::solarToLunar($date);
    $yi = array_values(array_filter(explode(',', (string) ($first['yi'] ?? ''))));
    $ji = array_values(array_filter(explode(',', (string) ($first['ji'] ?? ''))));

    if ($first !== $second) {
        $failures[] = [
            'date' => $date,
            'issue' => 'same-date-output-not-stable',
        ];
    }

    $aspectChecks = [];
    foreach ($aspectMap as $aspect => $descField) {
        $scoreField = $aspect . '_score';
        $expectedDescription = $descriptionMethod->invoke(null, $aspect, (int) $first[$scoreField], $almanac, $yi, $ji);
        $actualDescription = (string) ($first[$descField] ?? '');
        $usedLegacyText = in_array($actualDescription, $legacyDescriptions[$descField] ?? [], true);
        $hasContextualMarkers = str_contains($actualDescription, '黄历宜')
            || str_contains($actualDescription, '忌')
            || str_contains($actualDescription, '日里')
            || str_contains($actualDescription, '吉神')
            || str_contains($actualDescription, '需留意');
        $matched = $actualDescription === $expectedDescription && !$usedLegacyText && $hasContextualMarkers;

        if (!$matched) {
            $failures[] = [
                'date' => $date,
                'aspect' => $aspect,
                'issue' => 'description-score-mismatch',
                'score' => $first[$scoreField],
                'description' => $actualDescription,
                'expected' => $expectedDescription,
                'usedLegacyText' => $usedLegacyText,
                'hasContextualMarkers' => $hasContextualMarkers,
            ];
        }

        $aspectChecks[$aspect] = [
            'score' => $first[$scoreField],
            'description' => $actualDescription,
            'matched_rule' => $matched,
            'has_contextual_markers' => $hasContextualMarkers,
        ];
    }


    $results[] = [
        'date' => $date,
        'overall_score' => $first['overall_score'] ?? null,
        'summary' => $first['summary'] ?? '',
        'yi' => $first['yi'] ?? '',
        'ji' => $first['ji'] ?? '',
        'aspects' => $aspectChecks,
    ];
}

echo json_encode([
    'ok' => empty($failures),
    'results' => $results,
    'failures' => $failures,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($failures) ? 0 : 1);
