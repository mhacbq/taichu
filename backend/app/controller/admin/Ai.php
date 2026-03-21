<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\SystemConfig;
use think\Request;
use think\facade\Log;

class Ai extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取AI配置
     */
    public function getConfig()
    {
        if (!$this->hasAdminPermission('ai_view')) {
            return $this->error('无权限查看AI配置', 403);
        }

        try {
            $configs = SystemConfig::getAIConfig();
            
            $displayConfigs = [
                'ai_is_enabled' => $configs['ai_is_enabled'] ?? false,
                'ai_api_url' => $configs['ai_api_url'] ?? '',
                'ai_api_key' => $configs['ai_api_key'] ?? '',
                'ai_model' => $configs['ai_model'] ?? 'gpt-3.5-turbo',
                'ai_max_tokens' => $configs['ai_max_tokens'] ?? 2000,
                'ai_temperature' => $configs['ai_temperature'] ?? 0.7,
                'ai_timeout' => $configs['ai_timeout'] ?? 30,
                'ai_retry_times' => $configs['ai_retry_times'] ?? 3,
            ];

            return $this->success($displayConfigs);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_config_get', $e, '获取AI配置失败');
        }
    }

    /**
     * 保存AI配置
     */
    public function saveConfig()
    {
        if (!$this->hasAdminPermission('ai_edit')) {
            return $this->error('无权限编辑AI配置', 403);
        }

        $data = $this->request->post();

        try {
            $allowedFields = [
                'ai_is_enabled',
                'ai_api_url',
                'ai_api_key',
                'ai_model',
                'ai_max_tokens',
                'ai_temperature',
                'ai_timeout',
                'ai_retry_times'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $config = SystemConfig::where('config_group', 'ai')
                        ->where('config_key', $field)
                        ->find();

                    if ($config) {
                        $config->config_value = (string) $data[$field];
                        $config->save();
                    } else {
                        SystemConfig::create([
                            'config_group' => 'ai',
                            'config_key' => $field,
                            'config_value' => (string) $data[$field],
                            'description' => $this->getFieldDescription($field),
                            'is_encrypted' => $field === 'ai_api_key' ? 1 : 0
                        ]);
                    }
                }
            }

            $this->logOperation('save_ai_config', 'system', [
                'detail' => '更新AI配置',
                'fields' => array_keys(array_intersect_key($data, array_flip($allowedFields)))
            ]);

            return $this->success([], 'AI配置保存成功');
        } catch (\Throwable $e) {
            Log::error('保存AI配置失败: ' . $e->getMessage());
            return $this->respondSystemException('admin_ai_config_save', $e, '保存AI配置失败');
        }
    }

    /**
     * 测试AI连接
     */
    public function testConnection()
    {
        if (!$this->hasAdminPermission('ai_edit')) {
            return $this->error('无权限测试AI配置', 403);
        }

        try {
            $configs = SystemConfig::getAIConfig();
            $apiUrl = $configs['ai_api_url'] ?? '';
            $apiKey = $configs['ai_api_key'] ?? '';

            if (empty($apiUrl) || empty($apiKey)) {
                return $this->error('AI配置不完整，请先配置API地址和密钥');
            }

            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'verify' => false
            ]);

            $response = $client->post($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'model' => $configs['ai_model'] ?? 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hello']
                    ],
                    'max_tokens' => 10
                ]
            ]);

            $result = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() === 200) {
                return $this->success([
                    'status' => 'success',
                    'message' => 'AI服务连接成功',
                    'response_time' => $response->getHeaderLine('X-Response-Time') ?: '未知'
                ]);
            } else {
                return $this->error('AI服务返回错误: ' . ($result['error']['message'] ?? '未知错误'));
            }
        } catch (\Throwable $e) {
            Log::error('测试AI连接失败: ' . $e->getMessage());
            return $this->error('测试AI连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取字段描述
     */
    protected function getFieldDescription($field)
    {
        $descriptions = [
            'ai_is_enabled' => '是否启用AI服务',
            'ai_api_url' => 'AI服务API地址',
            'ai_api_key' => 'AI服务API密钥',
            'ai_model' => 'AI模型名称',
            'ai_max_tokens' => '最大生成token数',
            'ai_temperature' => '温度参数(0-2)',
            'ai_timeout' => '请求超时时间(秒)',
            'ai_retry_times' => '失败重试次数'
        ];

        return $descriptions[$field] ?? $field;
    }
}
