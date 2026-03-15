<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Db;

/**
 * 清理缓存命令
 * 
 * 用于手动或定时清理系统缓存
 * 
 * 运行方式：
 * php think cache:clear [type]
 * 
 * 参数说明：
 * - all: 清理所有缓存（默认）
 * - file: 清理文件缓存
 * - redis: 清理Redis缓存
 * - log: 清理日志文件
 * - temp: 清理临时文件
 */
class ClearCache extends Command
{
    protected function configure()
    {
        $this->setName('cache:clear')
             ->setDescription('清理系统缓存')
             ->addArgument('type', null, '清理类型(all/file/redis/log/temp)', 'all');
    }
    
    protected function execute(Input $input, Output $output)
    {
        $type = $input->getArgument('type');
        
        $output->writeln('');
        $output->writeln('╔══════════════════════════════════════════════════════════╗');
        $output->writeln('║            太初命理系统 - 清理缓存                       ║');
        $output->writeln('╚══════════════════════════════════════════════════════════╝');
        $output->writeln('');
        $output->writeln('清理类型: ' . $type);
        $output->writeln('');
        
        $success = true;
        
        switch ($type) {
            case 'all':
                $success = $this->clearAll($output);
                break;
            case 'file':
                $success = $this->clearFileCache($output);
                break;
            case 'redis':
                $success = $this->clearRedisCache($output);
                break;
            case 'log':
                $success = $this->clearLogs($output);
                break;
            case 'temp':
                $success = $this->clearTempFiles($output);
                break;
            default:
                $output->writeln('❌ 未知的清理类型: ' . $type);
                $output->writeln('可用类型: all, file, redis, log, temp');
                return 1;
        }
        
        $output->writeln('');
        $output->writeln('═══════════════════════════════════════════════════════════');
        if ($success) {
            $output->writeln('✅ 缓存清理完成');
        } else {
            $output->writeln('⚠️  部分清理失败，请检查权限');
        }
        $output->writeln('═══════════════════════════════════════════════════════════');
        $output->writeln('');
        
        return $success ? 0 : 1;
    }
    
    /**
     * 清理所有缓存
     */
    protected function clearAll(Output $output): bool
    {
        $success = true;
        
        $output->writeln('▶ 清理应用缓存...');
        try {
            Cache::clear();
            $output->writeln('  ✅ 应用缓存已清理');
        } catch (\Exception $e) {
            $output->writeln('  ❌ 失败: ' . $e->getMessage());
            $success = false;
        }
        
        $success = $this->clearFileCache($output) && $success;
        $success = $this->clearRedisCache($output) && $success;
        $success = $this->clearLogs($output) && $success;
        $success = $this->clearTempFiles($output) && $success;
        
        return $success;
    }
    
    /**
     * 清理文件缓存
     */
    protected function clearFileCache(Output $output): bool
    {
        $output->writeln('▶ 清理文件缓存...');
        try {
            $runtimePath = app()->getRuntimePath();
            $cachePath = $runtimePath . 'cache';
            
            if (is_dir($cachePath)) {
                $this->deleteDir($cachePath);
                $output->writeln('  ✅ 文件缓存已清理');
            } else {
                $output->writeln('  ℹ️  缓存目录不存在');
            }
            return true;
        } catch (\Exception $e) {
            $output->writeln('  ❌ 失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 清理Redis缓存
     */
    protected function clearRedisCache(Output $output): bool
    {
        $output->writeln('▶ 清理Redis缓存...');
        try {
            // 尝试清除Redis缓存
            Cache::store('redis')->clear();
            $output->writeln('  ✅ Redis缓存已清理');
            return true;
        } catch (\Exception $e) {
            $output->writeln('  ℹ️  Redis未配置或连接失败');
            return true; // Redis不是必须的
        }
    }
    
    /**
     * 清理日志文件
     */
    protected function clearLogs(Output $output): bool
    {
        $output->writeln('▶ 清理日志文件...');
        try {
            $runtimePath = app()->getRuntimePath();
            $logPath = $runtimePath . 'log';
            
            if (!is_dir($logPath)) {
                $output->writeln('  ℹ️  日志目录不存在');
                return true;
            }
            
            // 只清理7天前的日志，保留最近的
            $files = new \DirectoryIterator($logPath);
            $count = 0;
            $cutoffTime = strtotime('-7 days');
            
            foreach ($files as $file) {
                if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                    unlink($file->getPathname());
                    $count++;
                }
            }
            
            $output->writeln("  ✅ 已清理 {$count} 个日志文件");
            return true;
        } catch (\Exception $e) {
            $output->writeln('  ❌ 失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 清理临时文件
     */
    protected function clearTempFiles(Output $output): bool
    {
        $output->writeln('▶ 清理临时文件...');
        try {
            $runtimePath = app()->getRuntimePath();
            $tempPath = $runtimePath . 'temp';
            
            if (is_dir($tempPath)) {
                $this->deleteDir($tempPath);
                $output->writeln('  ✅ 临时文件已清理');
            } else {
                $output->writeln('  ℹ️  临时目录不存在');
            }
            return true;
        } catch (\Exception $e) {
            $output->writeln('  ❌ 失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 递归删除目录
     */
    protected function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        
        // 不删除根目录本身，只清空内容
        if ($dir !== app()->getRuntimePath() . 'cache' && 
            $dir !== app()->getRuntimePath() . 'temp') {
            rmdir($dir);
        }
    }
}
