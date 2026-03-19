<?php
declare(strict_types=1);

namespace tests\Unit;

use app\model\TarotRecord;
use ReflectionClass;
use tests\TestCase;

class TarotRecordCompatibilityTest extends TestCase
{
    public static function run(): void
    {
        $reflection = new ReflectionClass(TarotRecord::class);
        $cacheProperty = $reflection->getProperty('tableColumnsCache');
        $cacheProperty->setAccessible(true);
        $originalCache = $cacheProperty->getValue();

        try {
            $cacheProperty->setValue([
                TarotRecord::class => [
                    'id' => true,
                    'user_id' => true,
                    'type' => true,
                    'topic' => true,
                    'cards' => true,
                    'interpretation' => true,
                    'share_code' => true,
                    'create_time' => true,
                ],
            ]);

            $legacyRecord = new TarotRecord([
                'id' => 11,
                'user_id' => 9,
                'type' => 'three',
                'topic' => '这份工作值得继续吗？',
                'cards' => json_encode([
                    ['name' => '太阳', 'reversed' => false],
                    ['name' => '星星', 'reversed' => false],
                ], JSON_UNESCAPED_UNICODE),
                'interpretation' => '旧表记录解读',
                'share_code' => 'LEGACY123',
                'create_time' => '2026-03-19 08:00:00',
            ]);

            $legacyApi = $legacyRecord->toApiArray();
            self::assertSame('three', $legacyApi['spread_type'], '旧表 type 字段应映射为 spread_type');
            self::assertSame('这份工作值得继续吗？', $legacyApi['question'], '旧表 topic 字段应映射为占问文本');
            self::assertSame(1, $legacyApi['is_public'], '缺少 is_public 但存在 share_code 时应视为可公开分享');
            self::assertSame('2026-03-19 08:00:00', $legacyApi['created_at'], '旧表 create_time 应映射为 created_at');
            self::assertSame('太阳', $legacyApi['cards'][0]['name'] ?? '', '旧表 JSON cards 应被正确解码');

            $cacheProperty->setValue([
                TarotRecord::class => [
                    'id' => true,
                    'user_id' => true,
                    'spread_type' => true,
                    'question' => true,
                    'cards' => true,
                    'interpretation' => true,
                    'is_public' => true,
                    'share_code' => true,
                    'view_count' => true,
                    'created_at' => true,
                ],
            ]);

            $newRecord = new TarotRecord([
                'id' => 12,
                'user_id' => 10,
                'spread_type' => 'single',
                'question' => '今天适合行动吗？',
                'cards' => [
                    ['name' => '愚者', 'reversed' => false],
                ],
                'interpretation' => '新表记录解读',
                'is_public' => 0,
                'share_code' => 'NEWCODE8',
                'view_count' => 6,
                'created_at' => '2026-03-19 09:00:00',
            ]);

            $newApi = $newRecord->toApiArray();
            self::assertSame('single', $newApi['spread_type'], '新表 spread_type 应保持原样输出');
            self::assertSame('今天适合行动吗？', $newApi['question'], '新表 question 应保持原样输出');
            self::assertSame(0, $newApi['is_public'], '新表 is_public=0 时不应被 share_code 误判为公开');
            self::assertSame(6, $newApi['view_count'], '新表浏览次数应正确透出');
        } finally {
            $cacheProperty->setValue($originalCache);
        }
    }
}
