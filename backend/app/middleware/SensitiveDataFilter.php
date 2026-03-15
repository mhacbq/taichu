<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Log;

/**
 * 敏感数据过滤中间件
 * 
 * 自动过滤日志和响应中的敏感信息
 */
class SensitiveDataFilter
{
    /**
     * 敏感字段列表
     */
    protected $sensitiveFields = [
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
        'cert',
        'certificate',
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
        'cvv',
    ];
    
    /**
     * 需要脱敏的响应字段
     */
    protected $maskFields = [
        'phone',
        'mobile',
        'email',
        'id_card',
        'idcard',
        'bank_card',
        'bankcard',
        'card_no',
        'openid',
        'unionid',
    ];
    
    public function handle($request, \Closure $next)
    {
        // 过滤请求参数中的敏感数据（用于日志记录）
        $this->filterRequestForLog($request);
        
        $response = $next($request);
        
        // 过滤响应中的敏感数据
        $this->filterResponse($response);
        
        return $response;
    }
    
    /**
     * 过滤请求参数用于日志
     */
    protected function filterRequestForLog($request): void
    {
        $params = $request->param();
        $filteredParams = $this->filterSensitiveData($params);
        
        // 将过滤后的参数存入请求属性，供日志使用
        $request->filteredParams = $filteredParams;
    }
    
    /**
     * 递归过滤敏感数据
     */
    protected function filterSensitiveData(array $data, string $prefix = ''): array
    {
        $filtered = [];
        
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value, $fullKey);
            } else {
                // 检查是否是敏感字段
                $lowerKey = strtolower($key);
                $isSensitive = false;
                
                foreach ($this->sensitiveFields as $sensitive) {
                    if (strpos($lowerKey, $sensitive) !== false) {
                        $isSensitive = true;
                        break;
                    }
                }
                
                if ($isSensitive && is_string($value) && strlen($value) > 0) {
                    $filtered[$key] = $this->maskValue($value);
                } else {
                    $filtered[$key] = $value;
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * 脱敏值
     */
    protected function maskValue(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 4) {
            return '****';
        }
        
        // 保留前2位和后2位，中间用*代替
        $prefix = substr($value, 0, 2);
        $suffix = substr($value, -2);
        $masked = $prefix . str_repeat('*', min($length - 4, 8)) . $suffix;
        
        return $masked;
    }
    
    /**
     * 脱敏手机号
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) === 11) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return $this->maskValue($phone);
    }
    
    /**
     * 脱敏邮箱
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) === 2) {
            $local = $parts[0];
            $domain = $parts[1];
            
            if (strlen($local) > 2) {
                $local = substr($local, 0, 2) . str_repeat('*', min(strlen($local) - 2, 4));
            } else {
                $local = str_repeat('*', strlen($local));
            }
            
            return $local . '@' . $domain;
        }
        return $this->maskValue($email);
    }
    
    /**
     * 脱敏身份证号
     */
    protected function maskIdCard(string $idCard): string
    {
        if (strlen($idCard) === 18) {
            return substr($idCard, 0, 4) . '**********' . substr($idCard, -4);
        }
        return $this->maskValue($idCard);
    }
    
    /**
     * 过滤响应数据
     */
    protected function filterResponse($response): void
    {
        $content = $response->getContent();
        
        if (empty($content)) {
            return;
        }
        
        // 尝试解析JSON
        $data = json_decode($content, true);
        
        if ($data === null) {
            return;
        }
        
        // 递归脱敏响应数据
        $maskedData = $this->maskResponseData($data);
        
        // 重新编码
        $newContent = json_encode($maskedData);
        $response->content($newContent);
    }
    
    /**
     * 递归脱敏响应数据
     */
    protected function maskResponseData(array $data): array
    {
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (is_array($value)) {
                $data[$key] = $this->maskResponseData($value);
            } elseif (is_string($value)) {
                // 根据字段类型选择脱敏方式
                if (strpos($lowerKey, 'phone') !== false || strpos($lowerKey, 'mobile') !== false) {
                    $data[$key] = $this->maskPhone($value);
                } elseif (strpos($lowerKey, 'email') !== false) {
                    $data[$key] = $this->maskEmail($value);
                } elseif (strpos($lowerKey, 'id_card') !== false || strpos($lowerKey, 'idcard') !== false) {
                    $data[$key] = $this->maskIdCard($value);
                } elseif (in_array($lowerKey, $this->maskFields)) {
                    $data[$key] = $this->maskValue($value);
                }
            }
        }
        
        return $data;
    }
}