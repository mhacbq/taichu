<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\ConfigService;
use think\response\Json;

/**
 * 系统配置（公开接口）
 */
class Config extends BaseController
{
    /**
     * 获取客户端配置
     */
    public function clientConfig(): Json
    {
        $config = ConfigService::getClientConfig();
        
        return $this->success($config);
    }
    
    /**
     * 获取功能开关状态
     */
    public function featureSwitches(): Json
    {
        $features = ConfigService::getFeatureSwitches();
        
        return $this->success($features);
    }
}