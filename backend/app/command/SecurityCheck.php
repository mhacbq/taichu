<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Env;

/**
 * 安全检查命令
 * 
 * 运行安全扫描，检查配置和环境安全问题
 */
class SecurityCheck extends Command
{
    protected function configure()
    {
        $this->setName('security:check')
             ->setDescription('运行安全检查，扫描配置和环境安全问题');
    }
    
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('');
        $output->writeln('╔══════════════════════════════════════════════════════════╗');
        $output->writeln('║            太初命理系统 - 安全检查报告                   ║');
        $output->writeln('╚══════════════════════════════════════════════════════════╝');
        $output->writeln('');
        
        $issues = [];
        $warnings = [];
        $passed = [];
        
        // 1. 检查调试模式
        $output->writeln('▶ 检查调试模式...');
        $appDebug = Env::get('APP_DEBUG');
        if ($appDebug === 'true') {
            $issues[] = [
                'type' => '高危',
                'title' => '调试模式已开启',
                'desc' => '生产环境应该关闭调试模式（APP_DEBUG=false）',
            ];
        } else {
            $passed[] = '调试模式已关闭';
        }
        
        // 2. 检查JWT密钥
        $output->writeln('▶ 检查JWT密钥...');
        $jwtSecret = Env::get('JWT_SECRET');
        if (empty($jwtSecret) || strlen($jwtSecret) < 32) {
            $issues[] = [
                'type' => '高危',
                'title' => 'JWT密钥强度不足',
                'desc' => 'JWT_SECRET应该设置为至少32位的随机字符串',
            ];
        } elseif ($jwtSecret === 'your-secret-key-here-change-in-production') {
            $issues[] = [
                'type' => '高危',
                'title' => 'JWT密钥使用了默认值',
                'desc' => '请修改JWT_SECRET，使用生成的随机密钥',
            ];
        } else {
            $passed[] = 'JWT密钥配置正确';
        }
        
        // 3. 检查数据库密码
        $output->writeln('▶ 检查数据库配置...');
        $dbPassword = Env::get('DATABASE_PASSWORD');
        if (empty($dbPassword)) {
            $issues[] = [
                'type' => '高危',
                'title' => '数据库密码为空',
                'desc' => '请设置DATABASE_PASSWORD环境变量',
            ];
        } elseif (strlen($dbPassword) < 8) {
            $warnings[] = [
                'type' => '警告',
                'title' => '数据库密码强度较弱',
                'desc' => '建议使用至少8位的复杂密码',
            ];
        } else {
            $passed[] = '数据库密码已设置';
        }
        
        // 4. 检查微信支付密钥
        $output->writeln('▶ 检查支付配置...');
        $wxMchKey = Env::get('WECHAT_MCH_KEY');
        if (empty($wxMchKey)) {
            $warnings[] = [
                'type' => '警告',
                'title' => '微信支付密钥未配置',
                'desc' => '如果需要支付功能，请配置WECHAT_MCH_KEY',
            ];
        } else {
            $passed[] = '微信支付密钥已配置';
        }
        
        // 5. 检查AI API密钥
        $output->writeln('▶ 检查AI配置...');
        $aiApiKey = Env::get('AI_API_KEY');
        if (empty($aiApiKey)) {
            $warnings[] = [
                'type' => '警告',
                'title' => 'AI API密钥未配置',
                'desc' => '如果需要AI解盘功能，请配置AI_API_KEY',
            ];
        } else {
            $passed[] = 'AI API密钥已配置';
        }
        
        // 6. 检查日志路径
        $output->writeln('▶ 检查日志配置...');
        $logPath = Env::get('LOG_PATH');
        if (!empty($logPath) && strpos($logPath, '..') !== false) {
            $issues[] = [
                'type' => '中危',
                'title' => '日志路径可能存在安全风险',
                'desc' => '日志路径不应该包含..等特殊字符',
            ];
        } else {
            $passed[] = '日志路径配置正常';
        }
        
        // 7. 检查缓存配置
        $output->writeln('▶ 检查缓存配置...');
        $cacheType = Env::get('CACHE_TYPE');
        if (empty($cacheType)) {
            $warnings[] = [
                'type' => '警告',
                'title' => '缓存类型未明确设置',
                'desc' => '生产环境建议使用redis作为缓存驱动',
            ];
        } elseif ($cacheType === 'redis') {
            $passed[] = '已使用Redis缓存';
        } else {
            $warnings[] = [
                'type' => '警告',
                'title' => '建议使用Redis缓存',
                'desc' => '当前缓存类型: ' . $cacheType . '，生产环境建议使用redis',
            ];
        }
        
        // 8. 检查HTTPS配置
        $output->writeln('▶ 检查HTTPS配置...');
        $httpsForce = Env::get('HTTPS_FORCE');
        if ($appDebug !== 'true' && $httpsForce !== 'true') {
            $warnings[] = [
                'type' => '警告',
                'title' => '未强制启用HTTPS',
                'desc' => '生产环境建议启用HTTPS强制跳转',
            ];
        } else {
            $passed[] = 'HTTPS配置正常';
        }
        
        // 9. 检查目录权限
        $output->writeln('▶ 检查目录权限...');
        $runtimePath = app()->getRuntimePath();
        if (is_writable($runtimePath)) {
            $passed[] = 'Runtime目录可写入';
        } else {
            $issues[] = [
                'type' => '高危',
                'title' => 'Runtime目录不可写',
                'desc' => '请确保runtime目录有写入权限',
            ];
        }
        
        // 10. 检查敏感文件
        $output->writeln('▶ 检查敏感文件...');
        $sensitiveFiles = [
            '.env' => file_exists(root_path() . '.env'),
            '.git/config' => file_exists(root_path() . '.git/config'),
            'composer.lock' => file_exists(root_path() . 'composer.lock'),
        ];
        
        $webRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (!empty($webRoot) && strpos(root_path(), $webRoot) !== false) {
            $issues[] = [
                'type' => '高危',
                'title' => '项目目录位于Web根目录下',
                'desc' => '敏感文件可能被直接访问，建议将项目移到Web根目录之外',
            ];
        } else {
            $passed[] = '项目目录不在Web根目录下';
        }
        
        // 输出结果
        $output->writeln('');
        $output->writeln('═══════════════════════════════════════════════════════════');
        $output->writeln('  检查结果汇总');
        $output->writeln('═══════════════════════════════════════════════════════════');
        $output->writeln('');
        
        // 高危问题
        if (!empty($issues)) {
            $output->writeln('🔴 发现 ' . count($issues) . ' 个安全问题：');
            $output->writeln('');
            foreach ($issues as $i => $issue) {
                $output->writeln('  ' . ($i + 1) . '. [' . $issue['type'] . '] ' . $issue['title']);
                $output->writeln('     ' . $issue['desc']);
                $output->writeln('');
            }
        }
        
        // 警告
        if (!empty($warnings)) {
            $output->writeln('🟠 发现 ' . count($warnings) . ' 个警告：');
            $output->writeln('');
            foreach ($warnings as $i => $warning) {
                $output->writeln('  ' . ($i + 1) . '. [' . $warning['type'] . '] ' . $warning['title']);
                $output->writeln('     ' . $warning['desc']);
                $output->writeln('');
            }
        }
        
        // 通过项
        if (!empty($passed)) {
            $output->writeln('🟢 ' . count($passed) . ' 项检查通过');
            $output->writeln('');
        }
        
        // 总体评估
        $output->writeln('═══════════════════════════════════════════════════════════');
        if (empty($issues)) {
            if (empty($warnings)) {
                $output->writeln('✅ 系统安全状态良好，未发现安全问题');
            } else {
                $output->writeln('⚠️  系统存在警告，建议处理后再上线');
            }
            return 0;
        } else {
            $output->writeln('❌ 系统存在高危安全问题，请修复后再上线！');
            return 1;
        }
    }
}