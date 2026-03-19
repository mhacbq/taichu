<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use app\service\BaziCalculationService;
use app\service\LiuyaoService;

function step(string $label, callable $runner): mixed {
    echo "[STEP] {$label}\n";
    $result = $runner();
    if (is_array($result)) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        var_export($result);
        echo "\n";
    }
    echo "\n";
    return $result;
}

$moment = new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai'));
if ((int) $moment->format('G') >= 23) {
    $moment = $moment->modify('+1 day');
}

$base = step('qiGuaByTime', static fn() => LiuyaoService::qiGuaByTime(
    (int) $moment->format('Y'),
    (int) $moment->format('n'),
    (int) $moment->format('j'),
    (int) $moment->format('G')
));

$base['bian_gua'] = step('getBianGua', static fn() => LiuyaoService::getBianGua($base['yao_code']));
$base['hu_gua'] = step('getHuGua', static fn() => LiuyaoService::getHuGua($base['yao_code']));

$pillar = step('calculateDayPillar', static fn() => (new BaziCalculationService())->calculateDayPillar(
    (int) $moment->format('Y'),
    (int) $moment->format('n'),
    (int) $moment->format('j')
));

$ganList = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
$zhiList = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
$riGan = $ganList[(int) $pillar['gan_index']];
$riZhi = $zhiList[(int) $pillar['zhi_index']];

echo "riChen={$riGan}{$riZhi}\n\n";

$bazi = step('calculateBazi', static fn() => (new BaziCalculationService())->calculateBazi($moment->format('Y-m-d H:i:s'), '男'));
$liuShen = step('getLiuShen', static fn() => LiuyaoService::getLiuShen($riGan));
$guaInfo = step('getGuaInfo', static fn() => LiuyaoService::getGuaInfo($base['yao_code']));
$shiYing = step('getShiYing', static fn() => LiuyaoService::getShiYing($base['main_gua'], $base['yao_code']));
$liuqin = step('getLiuQin', static fn() => LiuyaoService::getLiuQin($base['main_gua'], LiuyaoService::BA_GUA_WUXING[$guaInfo['gong']] ?? '金', $base['yao_code']));
$yongshen = step('getYongShen', static fn() => LiuyaoService::getYongShen('事业', $liuqin, $shiYing, $base['yao_code'], '男', [
    'ri_gan' => $riGan,
    'ri_zhi' => $riZhi,
    'ri_chen' => $riGan . $riZhi,
    'yue_jian' => (($bazi['month']['zhi'] ?? '') ? ($bazi['month']['zhi'] . '月') : ''),
    'divination_at' => $moment->format('Y-m-d H:i:s'),
    'xunkong' => LiuyaoService::calculateXunKong($riGan, $riZhi),
]));
