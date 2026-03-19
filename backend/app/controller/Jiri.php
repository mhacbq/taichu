<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\AiService;
use app\service\ConfigService;
use app\service\LunarService;
use think\facade\Db;
use think\facade\Log;

/**
 * 吉日查询控制器
 */
class Jiri extends BaseController
{
    protected $middleware = [
        \app\middleware\Auth::class,
    ];

    /** 默认积分消耗 */
    const DEFAULT_COST = 20;

    /**
     * 吉日查询
     * POST /api/jiri/query
     */
    public function query()
    {
        $user   = $this->request->user;
        $userId = (int) $user['sub'];

        $purpose   = trim($this->request->post('purpose', ''));   // 用途（嫁娶/开业/搬家/动土等）
        $startDate = trim($this->request->post('start_date', '')); // 查询起始日期
        $endDate   = trim($this->request->post('end_date', ''));   // 查询结束日期
        $birthDate = trim($this->request->post('birth_date', '')); // 当事人生辰（可选）
        $gender    = $this->request->post('gender', 'male');

        if (empty($purpose)) {
            return $this->error('请输入查询用途（如：嫁娶、开业、搬家等）');
        }
        if (empty($startDate) || empty($endDate)) {
            return $this->error('请输入查询日期范围');
        }

        // 限制查询范围不超过3个月
        $start = strtotime($startDate);
        $end   = strtotime($endDate);
        if ($start === false || $end === false || $end < $start) {
            return $this->error('日期格式不正确');
        }
        if (($end - $start) > 90 * 86400) {
            return $this->error('查询范围不能超过90天');
        }

        // 检查积分
        $cost = (int) ConfigService::get('points_costs.jiri', self::DEFAULT_COST);
        $userModel = \app\model\User::find($userId);
        if (!$userModel) {
            return $this->error('用户不存在');
        }
        if ($userModel->points < $cost) {
            return $this->error("积分不足，吉日查询需要 {$cost} 积分", 402, ['required' => $cost, 'current' => $userModel->points]);
        }

        // 构建提示词
        $prompt = $this->buildPrompt($purpose, $startDate, $endDate, $birthDate, $gender);

        Db::startTrans();
        try {
            // 扣除积分
            $userModel->addPoints(-$cost);
            \app\model\PointsRecord::record($userId, '吉日查询', -$cost, 'jiri', 0, "查询{$purpose}吉日 {$startDate}~{$endDate}");

            // 调用AI
            $aiService = new AiService();
            if (!$aiService->isAvailable()) {
                Db::rollback();
                return $this->error('AI服务暂不可用，请稍后重试');
            }
            $result = $aiService->generate(
                $prompt,
                '你是一位精通中国传统历法、黄历和易经的风水命理师，擅长根据用途和个人八字挑选吉日良辰。',
                2000
            );

            // 保存历史
            $recordId = Db::name('tc_jiri_record')->insertGetId([
                'user_id'    => $userId,
                'purpose'    => $purpose,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'birth_date' => $birthDate,
                'gender'     => $gender,
                'result'     => $result,
                'cost'       => $cost,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            Db::commit();

            return $this->success([
                'id'        => $recordId,
                'result'    => $result,
                'cost'      => $cost,
                'remaining' => $userModel->points,
            ], '查询成功');

        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('吉日查询失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->respondSystemException('jiri.query', $e, '查询失败，请稍后重试');
        }
    }

    /**
     * 构建AI提示词
     */
    private function buildPrompt(string $purpose, string $startDate, string $endDate, string $birthDate, string $gender): string
    {
        $prompt = "请在 {$startDate} 至 {$endDate} 期间，为"{$purpose}"挑选最多5个吉日";
        if (!empty($birthDate)) {
            $genderText = $gender === 'female' ? '女' : '男';
            $prompt .= "，当事人为{$genderText}性，生辰：{$birthDate}";
        }
        $prompt .= "。\n\n请提供：\n1. 推荐吉日列表（含农历日期）\n2. 每天的宜忌说明\n3. 最佳吉时推荐\n4. 注意事项\n\n请用JSON格式返回，结构为：{\"good_days\": [{\"date\": \"公历日期\", \"lunar\": \"农历日期\", \"ganzhi\": \"干支\", \"reason\": \"推荐理由\", \"best_time\": \"吉时\", \"avoid\": \"忌讳\"}], \"summary\": \"总体建议\"}";
        return $prompt;
    }
}
