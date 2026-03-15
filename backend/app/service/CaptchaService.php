<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;

/**
 * 图形验证码服务
 */
class CaptchaService
{
    /**
     * 生成验证码
     */
    public static function generate(): array
    {
        // 生成随机验证码（6位数字+字母）
        $code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4);
        
        // 生成唯一key
        $key = bin2hex(random_bytes(16));
        
        // 存储到缓存（5分钟有效期）
        Cache::set('captcha:' . $key, $code, 300);
        
        return [
            'key' => $key,
            'code' => $code,
            'expire' => 300,
        ];
    }
    
    /**
     * 验证验证码
     */
    public static function verify(string $key, string $code): bool
    {
        if (empty($key) || empty($code)) {
            return false;
        }
        
        $cacheKey = 'captcha:' . $key;
        $cachedCode = Cache::get($cacheKey);
        
        if (empty($cachedCode)) {
            return false;
        }
        
        // 验证后删除，防止重复使用
        if (strcasecmp($cachedCode, $code) === 0) {
            Cache::delete($cacheKey);
            return true;
        }
        
        return false;
    }
    
    /**
     * 生成图片（Base64）
     */
    public static function generateImage(string $code): string
    {
        // 创建图片
        $width = 120;
        $height = 40;
        $image = imagecreatetruecolor($width, $height);
        
        // 背景色
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bgColor);
        
        // 添加干扰线
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }
        
        // 添加干扰点
        for ($i = 0; $i < 50; $i++) {
            $pointColor = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $pointColor);
        }
        
        // 绘制文字
        $fontColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
        $fontSize = 20;
        $x = 15;
        
        for ($i = 0; $i < strlen($code); $i++) {
            $angle = rand(-15, 15);
            imagettftext($image, $fontSize, $angle, $x, 28, $fontColor, __DIR__ . '/../../extend/font.ttf', $code[$i]);
            $x += 25;
        }
        
        // 输出为base64
        ob_start();
        imagepng($image);
        $data = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($data);
    }
}
