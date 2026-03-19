<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use app\controller\Liuyao;
use app\model\User;

$app = new \think\App(dirname(__DIR__));
$app->initialize();
$request = $app->request;

try {
    $pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306') . ';dbname=' . (getenv('DB_NAME') ?: 'taichu') . ';charset=utf8mb4', getenv('DB_USER') ?: 'taichu', getenv('DB_PASSWORD') ?: 'taichu123', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $userId = (int) $pdo->query("SELECT id FROM tc_user WHERE phone = '13800138055' LIMIT 1")->fetchColumn();
    if ($userId <= 0) {
        throw new RuntimeException('smoke user missing');
    }

    $request->user = ['sub' => $userId];
    $controller = new Liuyao($app);
    $userModel = User::find($userId);
    if (!$userModel) {
        throw new RuntimeException('user model missing');
    }

    $data = [
        'question' => '本周项目推进会顺利吗？',
        'method' => 'time',
        'question_type' => '事业',
        'gender' => '男',
        'useAi' => false,
        'user_id' => $userId,
    ];

    $buildCore = new ReflectionMethod($controller, 'buildDivinationCore');
    $buildCore->setAccessible(true);
    $buildInterpretation = new ReflectionMethod($controller, 'buildInterpretation');
    $buildInterpretation->setAccessible(true);
    $persist = new ReflectionMethod($controller, 'persistDivinationOutcome');
    $persist->setAccessible(true);
    $buildFrontend = new ReflectionMethod($controller, 'buildFrontendDivinationResult');
    $buildFrontend->setAccessible(true);

    $core = $buildCore->invoke($controller, $data);
    $interpretation = $buildInterpretation->invoke($controller, $core);
    [$recordId, $remainingPoints] = $persist->invoke($controller, $userModel, $data, $core, $interpretation, null, 0);
    $payload = $buildFrontend->invoke($controller, $recordId, $core, $interpretation, null, 0, $remainingPoints, true);

    echo json_encode([
        'record_id' => $recordId,
        'remaining_points' => $remainingPoints,
        'payload_keys' => array_keys($payload),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, '[INTERNAL-FAIL] ' . $e::class . ': ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(1);
}
