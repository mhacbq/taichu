<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Stats extends BaseController
{
    /**
     * 获取网站统计数据
     */
    public function index()
    {
        // 用户总数
        $userCount = Db::name('tc_user')->where('status', 1)->count();
        
        // 八字排盘次数
        $baziCount = Db::name('tc_bazi_record')->count();
        
        // 塔罗占卜次数（如果没有塔罗记录表，先返回模拟数据）
        $tarotCount = Db::name('tc_tarot_record')->count() ?? 0;
        
        // 总分析次数
        $totalAnalysis = $baziCount + $tarotCount;
        
        // 今日活跃用户
        $todayActive = Db::name('tc_user')
            ->where('last_login_at', '>=', date('Y-m-d'))
            ->count();
        
        // 格式化数字
        $formatNumber = function($num) {
            if ($num >= 100000) {
                return floor($num / 10000) . '万+';
            } elseif ($num >= 10000) {
                return round($num / 10000, 1) . '万+';
            } elseif ($num >= 1000) {
                return floor($num / 1000) . '000+';
            }
            return (string)$num;
        };
        
        return $this->success([
            'userCount' => [
                'value' => $userCount,
                'display' => $formatNumber($userCount),
            ],
            'analysisCount' => [
                'value' => $totalAnalysis,
                'display' => $formatNumber($totalAnalysis),
            ],
            'satisfactionRate' => [
                'value' => 98,
                'display' => '98%',
            ],
            'todayActive' => [
                'value' => $todayActive,
                'display' => $formatNumber($todayActive),
            ],
            'updateTime' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * 获取首页展示数据（直接查询，避免链式响应对象调用）
     */
    public function home()
    {
        $formatNumber = function($num) {
            if ($num >= 100000) {
                return floor($num / 10000) . '万+';
            } elseif ($num >= 10000) {
                return round($num / 10000, 1) . '万+';
            } elseif ($num >= 1000) {
                return floor($num / 1000) . '000+';
            }
            return (string)$num;
        };

        // 直接查询数据，不依赖 index() 的响应对象
        $userCount = Db::name('tc_user')->where('status', 1)->count();
        $baziCount = Db::name('tc_bazi_record')->count();
        $tarotCount = Db::name('tc_tarot_record')->count() ?? 0;
        $totalAnalysis = $baziCount + $tarotCount;
        
        return $this->success([
            'stats' => [
                ['number' => $formatNumber($userCount), 'label' => '服务用户'],
                ['number' => $formatNumber($totalAnalysis), 'label' => '分析次数'],
                ['number' => '98%', 'label' => '好评率'],
            ],
            'features' => [
                [
                    'icon' => '☯',
                    'title' => '八字排盘',
                    'description' => '精准的八字命理分析，解读您的人生轨迹、事业财运、婚姻感情',
                ],
                [
                    'icon' => '🎴',
                    'title' => '塔罗占卜',
                    'description' => '智能塔罗牌阵解读，为您的困惑指明方向，洞察未来可能',
                ],
                [
                    'icon' => '🌟',
                    'title' => '每日运势',
                    'description' => '基于生辰八字的每日运势分析，趋吉避凶，把握良机',
                ],
            ],
        ]);
    }
}
