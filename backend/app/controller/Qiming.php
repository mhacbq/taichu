<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\AiService;
use app\service\ConfigService;
use app\service\PointsService;
use think\facade\Db;
use think\facade\Log;

/**
 * 取名建议控制器
 */
class Qiming extends BaseController
{
    protected $middleware = [
        \app\middleware\Auth::class,
    ];

    /** 默认积分消耗 */
    const DEFAULT_COST = 100;

    /**
     * 取名建议
     * POST /api/qiming/suggest
     */
    public function suggest()
    {
        $user = $this->request->user;
        $userId = (int) $user['sub'];

        $surname   = trim($this->request->post('surname', ''));
        $gender    = $this->request->post('gender', 'male');
        $birthDate = trim($this->request->post('birth_date', ''));
        $birthHour = $this->request->post('birth_hour', '');
        $style     = trim($this->request->post('style', ''));
        $taboos    = trim($this->request->post('taboos', ''));

        if (empty($surname)) {
            return $this->error('请输入姓氏');
        }
        if (empty($birthDate)) {
            return $this->error('请输入出生日期');
        }

        // 检查积分
        $costInfo = ConfigService::calculatePointsCost('qiming');
        $cost = $costInfo['final'];
        $userModel = \app\model\User::find($userId);
        if (!$userModel) {
            return $this->error('用户不存在');
        }
        if ($userModel->points < $cost) {
            return $this->error("积分不足，取名需要 {$cost} 积分", 402, ['required' => $cost, 'current' => $userModel->points]);
        }

        $prompt = $this->buildPrompt($surname, $gender, $birthDate, $birthHour, $style, $taboos);

        Db::startTrans();
        try {
            // 扣除积分
            $userModel->addPoints(-$cost);
            \app\model\PointsRecord::record($userId, '取名建议', -$cost, 'qiming', 0, "为姓'{$surname}'生成取名建议");

            // 调用AI
            $aiService = new AiService();
            if (!$aiService->isAvailable()) {
                Db::rollback();
                return $this->error('AI服务暂不可用，请稍后重试');
            }
            $result = $aiService->generate(
                $prompt,
                '你是一位精通中国传统文化、易经五行和诗词的命名大师，擅长根据生辰八字为新生儿取名。',
                2000
            );

            // 保存历史
            $recordId = Db::name('tc_qiming_record')->insertGetId([
                'user_id'          => $userId,
                'surname'          => mb_substr($surname, 0, 10),
                'gender'           => $gender === 'female' ? 2 : 1,
                'birth_date'       => $birthDate,
                'birth_time'       => empty($birthHour) ? '00:00:00' : $birthHour,
                'wuxing_lack'      => '', // 可以通过八字排盘获取，这里暂留空
                'name_suggestions' => $result,
                'points_used'      => $cost,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            Db::commit();

            return $this->success([
                'id'         => $recordId,
                'result'     => $result,
                'cost'       => $cost,
                'remaining'  => $userModel->points,
            ], '取名成功');

        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('取名建议失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->respondSystemException('qiming.suggest', $e, '取名失败，请稍后重试');
        }
    }

    /**
     * 取名历史
     * GET /api/qiming/history
     */
    public function history()
    {
        $user   = $this->request->user;
        $userId = (int) $user['sub'];
        $page   = (int) $this->request->get('page', 1);
        $limit  = (int) $this->request->get('limit', 10);

        $list = Db::name('tc_qiming_record')
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->field('id,surname,gender,birth_date,birth_time,name_suggestions,points_used,created_at')
            ->select()
            ->toArray();

        $total = Db::name('tc_qiming_record')
            ->where('user_id', $userId)
            ->count();

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 构建AI提示词
     */
    private function buildPrompt(string $surname, string $gender, string $birthDate, string $birthHour, string $style, string $taboos): string
    {
        $genderText = $gender === 'female' ? '女' : '男';
        $prompt = "请为一位{$genderText}孩取名，姓氏为"{$surname}"，出生日期：{$birthDate}";
        if (!empty($birthHour)) {
            $prompt .= "，出生时辰：{$birthHour}";
        }
        if (!empty($style)) {
            $prompt .= "，期望风格：{$style}";
        }
        if (!empty($taboos)) {
            $prompt .= "，忌讳字/词：{$taboos}";
        }
        $prompt .= "。\n\n请提供5个取名方案，每个方案包括：\n1. 完整姓名（含姓氏）\n2. 名字的寓意解析\n3. 五行分析\n4. 音律美感说明\n\n请用JSON格式返回，结构为：{\"names\": [{\"name\": \"姓名\", \"meaning\": \"寓意\", \"wuxing\": \"五行\", \"phonetics\": \"音律\"}]}";
        return $prompt;
    }
}