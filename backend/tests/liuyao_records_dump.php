<?php
declare(strict_types=1);

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . (getenv('DB_NAME') ?: 'taichu') . ';charset=utf8mb4', getenv('DB_USER') ?: 'taichu', getenv('DB_PASSWORD') ?: 'taichu123', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->query("SELECT id,user_id,question,yao_result,gua_code,gua_name,consumed_points,created_at FROM liuyao_records ORDER BY id DESC LIMIT 5");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
