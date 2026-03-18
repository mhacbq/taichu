<?php
declare(strict_types=1);

namespace app\service;

/**
 * 请求/日志敏感信息脱敏工具
 */
class SensitiveDataSanitizer
{
    protected const SENSITIVE_FIELD_KEYWORDS = [
        'password',
        'pwd',
        'passwd',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'apikey',
        'app_secret',
        'appsecret',
        'private_key',
        'privatekey',
        'public_key',
        'publickey',
        'secret_id',
        'secretid',
        'secret_key',
        'secretkey',
        'access_key',
        'accesskey',
        'cert',
        'certificate',
        'authorization',
        'openid',
        'unionid',
        'id_card',
        'idcard',
        'identity_card',
        'phone',
        'mobile',
        'email',
        'bank_card',
        'bankcard',
        'card_no',
        'cardno',
        'cvv',
        'mch_id',
        'mchid',
        'sign_name',
        'signname',
        'session',
        'cookie',
    ];

    public static function sanitizeArray(array $data): array
    {
        $sanitized = self::sanitizeValue($data);
        return is_array($sanitized) ? $sanitized : [];
    }

    public static function sanitizeValue(mixed $value, ?string $field = null): mixed
    {
        if ($field !== null && self::isSensitiveField($field)) {
            return self::maskFieldValue($field, $value);
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = self::sanitizeValue($item, is_string($key) ? $key : null);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return self::sanitizeArray(get_object_vars($value));
        }

        return $value;
    }

    public static function maskResponseData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::maskResponseData($value);
                continue;
            }

            if (is_object($value)) {
                $data[$key] = self::maskResponseData(get_object_vars($value));
                continue;
            }

            if (!is_string($key) || !is_string($value) || !self::shouldMaskResponseField($key)) {
                continue;
            }

            $data[$key] = self::maskFieldValue($key, $value);
        }

        return $data;
    }

    public static function getFilteredRequestParams(object $request): array
    {
        if (isset($request->filteredParams) && is_array($request->filteredParams)) {
            return $request->filteredParams;
        }

        $params = method_exists($request, 'param') ? $request->param() : [];
        return is_array($params) ? self::sanitizeArray($params) : [];
    }

    public static function sanitizeRequestPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : $url;
    }

    public static function isSensitiveField(string $field): bool
    {
        $normalized = strtolower(trim($field));
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?: '';

        foreach (self::SENSITIVE_FIELD_KEYWORDS as $keyword) {
            $compactKeyword = preg_replace('/[^a-z0-9]/', '', $keyword) ?: '';
            if (($keyword !== '' && str_contains($normalized, $keyword))
                || ($compactKeyword !== '' && str_contains($compact, $compactKeyword))) {
                return true;
            }
        }

        return false;
    }

    protected static function shouldMaskResponseField(string $field): bool
    {
        $normalized = strtolower(trim($field));
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?: '';

        foreach (['phone', 'mobile', 'email', 'id_card', 'idcard', 'identity_card', 'bank_card', 'bankcard', 'card_no', 'cardno', 'openid', 'unionid'] as $keyword) {
            $compactKeyword = preg_replace('/[^a-z0-9]/', '', $keyword) ?: '';
            if (($keyword !== '' && str_contains($normalized, $keyword))
                || ($compactKeyword !== '' && str_contains($compact, $compactKeyword))) {
                return true;
            }
        }

        return false;
    }

    protected static function maskFieldValue(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return '***REDACTED***';
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return $stringValue;
        }

        $normalized = strtolower(trim($field));
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?: '';

        if (str_contains($normalized, 'phone') || str_contains($normalized, 'mobile') || str_contains($compact, 'phone') || str_contains($compact, 'mobile')) {
            return self::maskPhone($stringValue);
        }

        if (str_contains($normalized, 'email') || str_contains($compact, 'email')) {
            return self::maskEmail($stringValue);
        }

        if (str_contains($normalized, 'id_card') || str_contains($normalized, 'idcard') || str_contains($normalized, 'identity_card')
            || str_contains($compact, 'idcard') || str_contains($compact, 'identitycard')) {
            return self::maskIdCard($stringValue);
        }

        if (str_contains($normalized, 'bank_card') || str_contains($normalized, 'bankcard') || str_contains($normalized, 'card_no')
            || str_contains($compact, 'bankcard') || str_contains($compact, 'cardno')) {
            return self::maskCardNumber($stringValue);
        }

        return self::maskValue($stringValue);
    }

    protected static function maskValue(string $value): string
    {
        $length = strlen($value);
        if ($length <= 4) {
            return '****';
        }

        $prefix = substr($value, 0, 2);
        $suffix = substr($value, -2);
        $maskLength = min(max($length - 4, 0), 8);

        return $prefix . str_repeat('*', $maskLength) . $suffix;
    }

    protected static function maskPhone(string $phone): string
    {
        if (preg_match('/^\d{11}$/', $phone) === 1) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }

        return self::maskValue($phone);
    }

    protected static function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return self::maskValue($email);
        }

        [$local, $domain] = $parts;
        if ($local === '') {
            return '***@' . $domain;
        }

        $visiblePrefix = substr($local, 0, min(2, strlen($local)));
        return $visiblePrefix . '***@' . $domain;
    }

    protected static function maskIdCard(string $idCard): string
    {
        if (strlen($idCard) >= 8) {
            return substr($idCard, 0, 4) . str_repeat('*', max(strlen($idCard) - 8, 4)) . substr($idCard, -4);
        }

        return self::maskValue($idCard);
    }

    protected static function maskCardNumber(string $cardNumber): string
    {
        if (strlen($cardNumber) >= 8) {
            return substr($cardNumber, 0, 4) . str_repeat('*', max(strlen($cardNumber) - 8, 4)) . substr($cardNumber, -4);
        }

        return self::maskValue($cardNumber);
    }
}
