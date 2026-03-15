<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\model\SmsCode;
use app\model\RechargeOrder;
use app\model\AdminLog;
use think\facade\Db;

/**
 * 每日定时任务
 * 
 * 建议每天凌晨运行一次，用于清理过期数据、处理超时订单等
 * 
 * 运行方式：
 * php think daily:task
 */
class DailyTask extends Command
{
    protected function configure()
    {
        $this->setName('daily:task')
             ->setDescription('运行每日定时任务（清理过期数据、处理超时订单等）');
    }
    
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('');
        $output->writeln('╔══════════════════════════════════════════════════════════╗');
        $output->writeln('║            太初命理系统 - 每日定时任务                   ║');
        $output->writeln('╚══════════════════════════════════════════════════════════╝');
        $output->writeln('');
        $output->writeln('开始时间: ' . date('Y-m-d H:i:s'));
        $output->writeln('');
        
        $totalTasks = 0;
        $successTasks = 0;
        
        // 1. 清理过期短信验证码
        $totalTasks++;
        $output->write('▶ 清理过期短信验证码... ');
        try {
            $count = SmsCode::clearExpired();
            $output->writeln("✅ 已清理 {$count} 条");
            $successTasks++;
        } catch (\Exception $e) {
            $output->writeln('❌ 失败: ' . $e->getMessage());
        }
        
        // 2. 取消超时未支付订单
        $totalTasks++;
        $output->write('▶ 取消超时未支付订单... ');
        try {
            $count = $this->cancelExpiredOrders();
            $output->writeln("✅ 已取消 {$count} 个订单");
            $successTasks++;
        } catch (\Exception $e) {
            $output->writeln('❌ 失败: ' . $e->getMessage());
        }
        
        // 3. 清理旧日志
        $totalTasks++;
        $output->write('▶ 清理90天前的管理员日志... ');
        try {
            $count = AdminLog::cleanOldLogs(90);
            $output->writeln("✅ 已清理 {$count} 条");
            $successTasks++;
        } catch (\Exception $e) {
            $output->writeln('❌ 失败: ' . $e->getMessage());
        }
        
        // 4. 预生成明日运势数据
        $totalTasks++;
        $output->write('▶ 预生成明日运势数据... ');
        try {
            $this->generateTomorrowFortune();
            $output->writeln('✅ 完成');
            $successTasks++;
        } catch (\Exception $e) {
            $output->writeln('❌ 失败: ' . $e->getMessage());
        }
        
        // 5. 清理过期文件上传记录
        $totalTasks++;
        $output->write('▶ 清理未使用的上传文件记录... ');
        try {
            $count = $this->cleanUnusedUploads();
            $output->writeln("✅ 已清理 {$count} 条");
            $successTasks++;
        } catch (\Exception $e) {
            $output->writeln('❌ 失败: ' . $e->getMessage());
        }
        
        // 6. 生成每日统计（可选）
        $totalTasks++;
        $output->write('▶ 生成每日统计数据... ');
        try {
            $this->generateDailyStats();
            $output->writeln('✅ 完成');
            $successTasks++;
        } catch (\Exception $e) {
            $output->writeln('❌ 失败: ' . $e->getMessage());
        }
        
        // 输出总结
        $output->writeln('');
        $output->writeln('═══════════════════════════════════════════════════════════');
        $output->writeln('任务完成: ' . $successTasks . '/' . $totalTasks . ' 成功');
        $output->writeln('结束时间: ' . date('Y-m-d H:i:s'));
        $output->writeln('═══════════════════════════════════════════════════════════');
        $output->writeln('');
        
        return $successTasks === $totalTasks ? 0 : 1;
    }
    
    /**
     * 取消超时未支付订单
     */
    protected function cancelExpiredOrders(): int
    {
        $expiredOrders = RechargeOrder::where('status', RechargeOrder::STATUS_PENDING)
            ->where('expire_time', '<', date('Y-m-d H:i:s'))
            ->select();
        
        $count = 0;
        foreach ($expiredOrders as $order) {
            if ($order->cancel()) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * 预生成明日运势
     */
    protected function generateTomorrowFortune(): void
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        // 使用 DailyFortune 模型的方法生成
        $fortune = \app\model\DailyFortune::getByDate($tomorrow);
        if (!$fortune) {
            // 调用模型中的生成逻辑
            \app\model\DailyFortune::where('date', $tomorrow)->findOrEmpty();
        }
    }
    
    /**
     * 清理未使用的上传文件记录
     */
    protected function cleanUnusedUploads(): int
    {
        // 清理30天前上传且未使用的文件记录
        $count = Db::name('upload_files')
            ->where('used_count', 0)
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->delete();
        
        return $count;
    }
    
    /**
     * 生成每日统计数据
     */
    protected function generateDailyStats(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        
        // 统计数据并存入缓存或数据库
        $stats = [
            'date' => $yesterday,
            'new_users' => Db::name('tc_user')->whereBetween('created_at', [$yesterday . ' 00:00:00', $yesterday . ' 23:59:59'])->count(),
            'new_orders' => Db::name('tc_recharge_order')->whereBetween('created_at', [$yesterday . ' 00:00:00', $yesterday . ' 23:59:59'])->count(),
            'total_recharge' => Db::name('tc_recharge_order')->where('status', 'paid')->whereBetween('pay_time', [$yesterday . ' 00:00:00', $yesterday . ' 23:59:59'])->sum('amount'),
            'bazi_count' => Db::name('tc_bazi_record')->whereBetween('created_at', [$yesterday . ' 00:00:00', $yesterday . ' 23:59:59'])->count(),
            'tarot_count' => Db::name('tc_tarot_record')->whereBetween('created_at', [$yesterday . ' 00:00:00', $yesterday . ' 23:59:59'])->count(),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        // 存入系统配置或专用统计表
        Db::name('system_config')->where('config_key', 'daily_stats_' . $yesterday)->delete();
        Db::name('system_config')->insert([
            'config_key' => 'daily_stats_' . $yesterday,
            'config_value' => json_encode($stats),
            'config_type' => 'json',
            'description' => '每日统计数据-' . $yesterday,
            'category' => 'stats',
            'is_editable' => 0,
        ]);
    }
}
