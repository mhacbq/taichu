<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\BaziRecord;
use app\model\PointsRecord;
use think\facade\Db;

class Paipan extends BaseController
{
    // 排盘所需积分
    const BAZI_POINTS_COST = 10;
    
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 八字排盘
     */
    public function bazi()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 验证参数
        if (empty($data['birthDate'])) {
            return $this->error('请提供出生日期');
        }
        
        if (empty($data['gender'])) {
            return $this->error('请提供性别');
        }
        
        // 检查用户积分
        $userModel = \app\model\User::find($user['sub']);
        if (!$userModel) {
            return $this->error('用户不存在', 404);
        }
        
        if ($userModel->points < self::BAZI_POINTS_COST) {
            return $this->error('积分不足，请先充值', 403);
        }
        
        // 解析出生日期
        $birthDate = $data['birthDate'];
        $gender = $data['gender'];
        $location = $data['location'] ?? '';
        
        // 计算八字
        $bazi = $this->calculateBazi($birthDate);
        
        // 生成分析
        $analysis = $this->generateAnalysis($bazi, $gender);
        
        Db::startTrans();
        try {
            // 扣除积分
            $userModel->deductPoints(self::BAZI_POINTS_COST);
            
            // 记录积分变动
            PointsRecord::record(
                $user['sub'],
                '八字排盘消耗',
                -self::BAZI_POINTS_COST,
                'bazi',
                0,
                "排盘日期: {$birthDate}"
            );
            
            // 保存排盘记录
            $record = BaziRecord::create([
                'user_id' => $user['sub'],
                'birth_date' => $birthDate,
                'gender' => $gender,
                'location' => $location,
                'year_gan' => $bazi['year']['gan'],
                'year_zhi' => $bazi['year']['zhi'],
                'month_gan' => $bazi['month']['gan'],
                'month_zhi' => $bazi['month']['zhi'],
                'day_gan' => $bazi['day']['gan'],
                'day_zhi' => $bazi['day']['zhi'],
                'hour_gan' => $bazi['hour']['gan'],
                'hour_zhi' => $bazi['hour']['zhi'],
                'analysis' => $analysis,
            ]);
            
            Db::commit();
            
            return $this->success([
                'id' => $record->id,
                'bazi' => $bazi,
                'analysis' => $analysis,
                'points_cost' => self::BAZI_POINTS_COST,
                'remaining_points' => $userModel->points,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('排盘失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取排盘历史
     */
    public function history()
    {
        $user = $this->request->user;
        $limit = $this->request->get('limit', 20);
        
        $history = BaziRecord::getUserHistory($user['sub'], (int)$limit);
        
        return $this->success($history);
    }
    
    /**
     * 计算八字
     */
    protected function calculateBazi(string $birthDate): array
    {
        $timestamp = strtotime($birthDate);
        $year = date('Y', $timestamp);
        $month = date('n', $timestamp);
        $day = date('j', $timestamp);
        $hour = date('G', $timestamp);
        
        // 天干
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        // 地支
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        // 年柱
        $yearGanIndex = ($year - 4) % 10;
        $yearZhiIndex = ($year - 4) % 12;
        
        // 月柱（简化计算）
        $monthGanIndex = ($yearGanIndex * 2 + $month + 1) % 10;
        $monthZhiIndex = ($month + 1) % 12;
        
        // 日柱（简化计算，实际应使用万年历）
        $dayOffset = (int)(($timestamp - strtotime('1900-01-01')) / 86400);
        $dayGanIndex = ($dayOffset + 10) % 10;
        $dayZhiIndex = ($dayOffset + 12) % 12;
        
        // 时柱
        $shiZhiIndex = (int)(($hour + 1) / 2) % 12;
        $shiGanIndex = ($dayGanIndex * 2 + $shiZhiIndex) % 10;
        
        return [
            'year' => ['gan' => $tianGan[$yearGanIndex], 'zhi' => $diZhi[$yearZhiIndex]],
            'month' => ['gan' => $tianGan[$monthGanIndex], 'zhi' => $diZhi[$monthZhiIndex]],
            'day' => ['gan' => $tianGan[$dayGanIndex], 'zhi' => $diZhi[$dayZhiIndex]],
            'hour' => ['gan' => $tianGan[$shiGanIndex], 'zhi' => $diZhi[$shiZhiIndex]],
        ];
    }
    
    /**
     * 生成命理分析
     */
    protected function generateAnalysis(array $bazi, string $gender): string
    {
        $dayGan = $bazi['day']['gan'];
        $genderText = $gender === 'male' ? '男性' : '女性';
        
        $analysis = "您的日主为【{$dayGan}】，属{$genderText}。\n\n";
        $analysis .= "【年柱】{$bazi['year']['gan']}{$bazi['year']['zhi']}：代表祖上根基，早年运势。\n";
        $analysis .= "【月柱】{$bazi['month']['gan']}{$bazi['month']['zhi']}：代表父母兄弟，青年运势。\n";
        $analysis .= "【日柱】{$bazi['day']['gan']}{$bazi['day']['zhi']}：代表自身夫妻，中年运势。\n";
        $analysis .= "【时柱】{$bazi['hour']['gan']}{$bazi['hour']['zhi']}：代表子女晚景，晚年运势。\n\n";
        
        $analysis .= "您的八字五行分布较为均衡，性格稳重踏实。事业上宜循序渐进，财运方面有积累之象。\n";
        $analysis .= "感情方面，{$genderText}以{$dayGan}日主，配偶宫为{$bazi['day']['zhi']}，婚姻较为和谐。\n";
        $analysis .= "健康方面需注意脾胃保养，保持规律作息。";
        
        return $analysis;
    }
}
