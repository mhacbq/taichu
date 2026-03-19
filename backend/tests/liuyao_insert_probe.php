<?php
declare(strict_types=1);

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . (getenv('DB_NAME') ?: 'taichu') . ';charset=utf8mb4', getenv('DB_USER') ?: 'taichu', getenv('DB_PASSWORD') ?: 'taichu123', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$data = [
    'user_id' => 1,
    'question' => '探针：本周项目推进会顺利吗？',
    'yao_result' => '321112',
    'gua_code' => '56',
    'gua_name' => '兑离',
    'gua_ci' => '',
    'yao_ci' => '',
    'interpretation' => 'probe',
    'ai_analysis' => '',
    'is_ai_analysis' => 0,
    'consumed_points' => 0,
    'created_at' => date('Y-m-d H:i:s'),
];

$sql = "INSERT INTO liuyao_records (user_id,question,yao_result,gua_code,gua_name,gua_ci,yao_ci,interpretation,ai_analysis,is_ai_analysis,consumed_points,created_at) VALUES (:user_id,:question,:yao_result,:gua_code,:gua_name,:gua_ci,:yao_ci,:interpretation,:ai_analysis,:is_ai_analysis,:consumed_points,:created_at)";
$stmt = $pdo->prepare($sql);
$stmt->execute($data);

echo json_encode(['insert_id' => (int) $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
