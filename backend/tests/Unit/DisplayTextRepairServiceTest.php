<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\DisplayTextRepairService;
use tests\TestCase;

class DisplayTextRepairServiceTest extends TestCase
{
    public static function run(): void
    {
        self::assertSame('八字基础', DisplayTextRepairService::normalizeText('å…«å­—åŸºç¡€'), '应修复常见 mojibake 中文乱码');
        self::assertSame('塔罗体系', DisplayTextRepairService::normalizeText('????', '塔罗体系'), '问号占位应回退到给定兜底文案');
        self::assertSame(
            ['八字排盘', '塔罗占卜'],
            DisplayTextRepairService::normalizeKeywordList('["????", "å¡”ç½—å占åœ"]', ['八字排盘', '塔罗占卜']),
            '关键词数组应逐项修复并在无法恢复时回退到兜底关键词'
        );
    }
}
