<?php
require_once 'backend/app/service/BaziCalculationService.php';

use app\service\BaziCalculationService;

$service = new BaziCalculationService();
$result = $service->calculateBazi('1990-05-15 10:30:00');

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
