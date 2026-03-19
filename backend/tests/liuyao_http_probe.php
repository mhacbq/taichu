<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . (getenv('DB_NAME') ?: 'taichu') . ';charset=utf8mb4', getenv('DB_USER') ?: 'taichu', getenv('DB_PASSWORD') ?: 'taichu123', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$userId = (int) $pdo->query("SELECT id FROM tc_user WHERE phone = '13800138055' LIMIT 1")->fetchColumn();
if ($userId <= 0) {
    throw new RuntimeException('smoke user missing');
}
$pdo->prepare('DELETE FROM liuyao_records WHERE user_id = ?')->execute([$userId]);
$pdo->prepare('UPDATE tc_user SET points = 500 WHERE id = ?')->execute([$userId]);

$token = JWT::encode([
    'sub' => $userId,
    'iat' => time(),
    'exp' => time() + 7200,
], getenv('JWT_SECRET') ?: 'local-dev-secret-please-change-in-production-abc123', 'HS256');

$ch = curl_init('http://127.0.0.1/api/liuyao/divination');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Forwarded-Proto: https',
        'Authorization: Bearer ' . $token,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'question' => '本周项目推进会顺利吗？',
        'method' => 'time',
        'question_type' => '事业',
        'gender' => '男',
        'useAi' => false,
    ], JSON_UNESCAPED_UNICODE),
]);
$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'http_code' => $httpCode,
    'curl_error' => $error,
    'body' => json_decode((string) $body, true),
    'raw' => $body,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
