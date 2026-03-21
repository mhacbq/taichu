<?php
/**
 * 种子数据导入脚本
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载应用
$app = new \think\App();
$http = $app->http;
$response = $http->run();

use think\facade\Db;

echo "正在执行帮助中心数据导入...\n";

try {
        $sqlFile = dirname(__DIR__) . '/database/20260321_seed_help_page_content_and_faqs.sql';
    
    if (!file_exists($sqlFile)) {
        die("错误：找不到 SQL 文件: $sqlFile\n");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // 分割SQL语句
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
    
    foreach ($statements as $sql) {
        if (empty($sql)) continue;
        try {
            Db::execute($sql);
            echo "成功执行SQL语句\n";
        } catch (\Exception $e) {
            echo "执行失败: " . $e->getMessage() . "\n";
            // 继续执行其他语句
        }
    }
    
    echo "数据导入完成！\n";
    
} catch (\Exception $e) {
    echo "发生错误: " . $e->getMessage() . "\n";
}
