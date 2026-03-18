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
        if (empty($data['content'])) {
            return $this->error('请填写反馈内容');
        }

        
        $type = $data['type'] ?? 'suggestion';
        $allowTypes = ['suggestion', 'bug', 'complaint', 'praise', 'other'];
        
        if (!in_array($type, $allowTypes, true)) {
            $type = 'suggestion';
        }

        $title = $this->resolveFeedbackTitle($data, $type);
        
        try {
            $feedback = FeedbackModel::create([
                'user_id' => (int) $user['sub'],
                'type' => $type,
                'title' => $title,
                'content' => (string) $data['content'],
                'contact' => (string) ($data['contact'] ?? ''),
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
                    'has_contact' => !empty($data['contact']),
                    'content_length' => mb_strlen((string) $data['content']),
                ]
            );
        }
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
