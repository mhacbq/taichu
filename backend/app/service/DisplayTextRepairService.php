<?php
declare(strict_types=1);

namespace app\service;

class DisplayTextRepairService
{
    private const FULL_PLACEHOLDER_PATTERN = '/^[?？\s,，.。;；:：!！、_\-\/\\|]+$/u';
    private const PARTIAL_PLACEHOLDER_PATTERN = '/[?？]{2,}/u';
    private const MOJIBAKE_HINT_PATTERN = '/[ÃÂâ€åäæçéèêëìíîïðñòóôõöøùúûüýÿ]/u';

    public static function normalizeText(mixed $value, string $fallback = ''): string
    {
        $text = trim((string) $value);
        $fallback = trim($fallback);

        if ($text === '') {
            return $fallback;
        }

        if (self::containsChinese($text)
            && preg_match(self::MOJIBAKE_HINT_PATTERN, $text) !== 1
            && !self::looksLikePlaceholder($text)) {
            return $text;
        }


        $repaired = self::repairMojibake($text);
        if ($repaired !== null) {
            return $repaired;
        }

        if (($fallback !== '') && self::looksLikePlaceholder($text)) {
            return $fallback;
        }

        if (($fallback !== '') && self::looksSuspicious($text)) {
            return $fallback;
        }

        return $text;
    }

    public static function normalizeKeywordList(mixed $keywords, array $fallback = []): array
    {
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            if (is_array($decoded)) {
                $keywords = $decoded;
            } else {
                $keywords = preg_split('/[,，]/', $keywords) ?: [];
            }
        }

        if (!is_array($keywords)) {
            $keywords = [];
        }

        $fallback = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $fallback), static fn (string $item): bool => $item !== ''));
        $normalized = [];

        foreach (array_values($keywords) as $index => $item) {
            $normalizedItem = self::normalizeText($item, $fallback[$index] ?? '');
            if ($normalizedItem !== '') {
                $normalized[] = $normalizedItem;
            }
        }

        if (empty($normalized) && !empty($fallback)) {
            $normalized = $fallback;
        }

        return array_values(array_unique($normalized));
    }

    public static function looksLikePlaceholder(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return preg_match(self::FULL_PLACEHOLDER_PATTERN, $text) === 1
            || preg_match(self::PARTIAL_PLACEHOLDER_PATTERN, $text) === 1;
    }

    public static function looksSuspicious(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return self::looksLikePlaceholder($text)
            || preg_match(self::MOJIBAKE_HINT_PATTERN, $text) === 1;
    }


    public static function containsChinese(string $text): bool
    {
        return preg_match('/[\x{4e00}-\x{9fff}]/u', $text) === 1;
    }

    private static function repairMojibake(string $text): ?string
    {
        $attempts = [
            ['UTF-8', 'Windows-1252//IGNORE'],
            ['UTF-8', 'ISO-8859-1//IGNORE'],
            ['Windows-1252', 'UTF-8//IGNORE'],
            ['ISO-8859-1', 'UTF-8//IGNORE'],
        ];

        foreach ($attempts as [$from, $to]) {
            $repaired = @iconv($from, $to, $text);
            if (!is_string($repaired)) {
                continue;
            }

            $repaired = trim($repaired);
            if ($repaired === '' || preg_match('//u', $repaired) !== 1) {
                continue;
            }

            if (self::containsChinese($repaired)
                && preg_match(self::MOJIBAKE_HINT_PATTERN, $repaired) !== 1
                && !self::looksLikePlaceholder($repaired)) {
                return $repaired;
            }

        }

        return null;
    }

}
