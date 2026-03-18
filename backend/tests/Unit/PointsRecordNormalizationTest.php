<?php
declare(strict_types=1);

namespace tests\Unit;

use app\model\PointsRecord;
use tests\TestCase;

class PointsRecordNormalizationTest extends TestCase
{
    public static function run(): void
    {
        $rows = [
            [
                'id' => 101,
                'user_id' => 7,
                'action' => '塔罗占卜消耗',
                'points' => -20,
                'amount' => 0,
                'balance' => null,
                'type' => 'tarot',
                'remark' => '牌阵: 三张牌',
                'reason' => '',
                'created_at' => '2026-03-18 12:00:00',
            ],
            [
                'id' => 100,
                'user_id' => 7,
                'action' => '充值获取',
                'points' => 100,
                'amount' => 0,
                'balance' => null,
                'type' => 'recharge',
                'remark' => '',
                'reason' => '',
                'created_at' => '2026-03-18 11:00:00',
            ],
            [
                'id' => 99,
                'user_id' => 8,
                'action' => '管理员扣除',
                'points' => 0,
                'amount' => 15,
                'balance' => null,
                'type' => 'consume',
                'remark' => '',
                'reason' => '',
                'created_at' => '2026-03-18 10:00:00',
            ],
        ];

        $normalized = PointsRecord::normalizeRecordList($rows, [
            7 => 80,
            8 => 35,
        ]);

        self::assertSame('reduce', $normalized[0]['direction'], '负积分记录应识别为减少');
        self::assertSame(20, $normalized[0]['amount'], '负积分记录应补齐绝对值数量');
        self::assertSame(80, $normalized[0]['balance'], '最新记录应回填当前余额');
        self::assertSame('牌阵: 三张牌', $normalized[0]['reason'], '原因应优先回填 reason/remark/action');

        self::assertSame('add', $normalized[1]['direction'], '正积分记录应识别为增加');
        self::assertSame(100, $normalized[1]['balance'], '同一用户更早记录应按运行余额反推');

        self::assertSame('reduce', $normalized[2]['direction'], 'amount>0 且 type=consume 的记录应识别为减少');
        self::assertSame(15, $normalized[2]['amount'], '缺少 points 时应保留原始 amount');
        self::assertSame(35, $normalized[2]['balance'], '不同用户应独立使用各自余额快照');
    }
}
