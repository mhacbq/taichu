<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\Feedback as FeedbackModel;
use app\service\ConfigService;


class Feedback extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 提交反馈
     */
    public function submit()
    {
        $data = $this->request->post();
        $user = $this->request->user;

        if (!ConfigService::isFeatureEnabled('feedback')) {
            return $this->error('用户反馈功能暂未开放', 403);
        }

        // 验证参数
        $content = trim((string) ($data['content'] ?? ''));
        if (empty($content) || mb_strlen($content) < 5) {
            return $this->error('反馈内容不能少于5个字符');
        }

        if (mb_strlen($content) > 5000) {
            return $this->error('反馈内容不能超过5000个字符');
        }

        // 验证联系方式（邮箱或手机号）
        $contact = trim((string) ($data['contact'] ?? ''));
        if (!empty($contact)) {
            if (!$this->isValidContact($contact)) {
                return $this->error('请填写有效的邮箱地址或手机号码');
            }
        }


        $type = $data['type'] ?? 'suggestion';
        $allowTypes = ['suggestion', 'bug', 'complaint', 'praise', 'other'];

        if (!in_array($type, $allowTypes, true)) {
            $type = 'suggestion';
        }

        $title = $this->resolveFeedbackTitle($data, $type);

        // 防注入处理
        $safeContent = $this->sanitizeInput($content);
        $safeTitle = $this->sanitizeInput($title);
        $safeContact = $this->sanitizeInput($contact);

        try {
            $feedback = FeedbackModel::create([
                'user_id' => (int) $user['sub'],
                'type' => $type,
                'title' => $safeTitle,
                'content' => $safeContent,
                'contact' => $safeContact,
                'status' => FeedbackModel::STATUS_PENDING,
            ]);

            return $this->success([
                'id' => $feedback->id,
            ], '反馈提交成功，感谢您的建议！');
        } catch (\Throwable $e) {
            return $this->respondSystemException(
                'feedback.submit',
                $e,
                '提交失败，请稍后重试',
                [
                    'user_id' => (int) ($user['sub'] ?? 0),
                    'type' => $type,
                    'has_contact' => !empty($contact),
                    'content_length' => mb_strlen($content),
                ]
            );
        }
    }

    /**
     * 验证联系方式（邮箱或手机号）
     */
    private function isValidContact(string $contact): bool
    {
        // 邮箱格式验证
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // 中国大陆手机号验证
        if (preg_match('/^1[3-9]\d{9}$/', $contact)) {
            return true;
        }

        return false;
    }

    /**
     * 输入内容防注入处理（XSS防护）
     */
    private function sanitizeInput(string $input): string
    {
        // 移除潜在的脚本标签和事件处理器
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/<object\b[^>]*>(.*?)<\/object>/is',
            '/<embed\b[^>]*>(.*?)<\/embed>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<\s*\/?\s*[a-zA-Z][^>]*(?:on\w+\s*=)/i',
        ];

        $cleaned = preg_replace($patterns, '', $input);

        // 转义HTML特殊字符
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $cleaned;
    }
    
    /**
     * 获取我的反馈列表
     */
    public function myList()
    {
        $user = $this->request->user;
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 10);
        
        $list = FeedbackModel::where('user_id', (int) $user['sub'])
            ->order('created_at', 'desc')
            ->page((int) $page, (int) $limit)
            ->select()
            ->toArray();
        
        $total = FeedbackModel::where('user_id', (int) $user['sub'])
            ->count();
        
        // 转换状态文字
        foreach ($list as &$item) {
            $item['status_text'] = match ((int) $item['status']) {
                FeedbackModel::STATUS_PENDING => '待处理',
                FeedbackModel::STATUS_PROCESSING => '处理中',
                FeedbackModel::STATUS_RESOLVED => '已解决',
                FeedbackModel::STATUS_CLOSED => '已关闭',
                default => '未知',
            };
        }
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
        ]);
    }

    protected function resolveFeedbackTitle(array $data, string $type): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title !== '') {
            return mb_substr($title, 0, 200);
        }

        $content = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($data['content'] ?? ''))) ?? '');
        if ($content !== '') {
            return mb_substr($content, 0, 200);
        }

        $typeLabels = [
            'suggestion' => '功能建议',
            'bug' => '问题反馈',
            'complaint' => '投诉反馈',
            'praise' => '表扬反馈',
            'other' => '其他反馈',
        ];

        return $typeLabels[$type] ?? '用户反馈';
    }
}
