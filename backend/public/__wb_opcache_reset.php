<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'opcache_reset' => function_exists('opcache_reset') ? opcache_reset() : null,
    'timestamp' => date('c'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
