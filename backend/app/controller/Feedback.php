<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;

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
        
        // 验证参数
        if (empty($data['content'])) {
            return $this->error('请填写反馈内容');
        }
        
        $type = $data['type'] ?? 'suggestion';
        $allowTypes = ['suggestion', 'bug', 'complaint', 'praise', 'other'];
        
        if (!in_array($type, $allowTypes)) {
            $type = 'suggestion';
        }
        
        try {
            $feedbackId = Db::name('feedback')->insertGetId([
                'user_id' => $user['sub'],
                'type' => $type,
                'content' => $data['content'],
                'contact' => $data['contact'] ?? '',
                'status' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            return $this->success([
                'id' => $feedbackId,
            ], '反馈提交成功，感谢您的建议！');
        } catch (\Exception $e) {
            return $this->error('提交失败: ' . $e->getMessage());
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
        
        $list = Db::name('feedback')
            ->where('user_id', $user['sub'])
            ->order('created_at', 'desc')
            ->page((int)$page, (int)$limit)
            ->select()
            ->toArray();
        
        $total = Db::name('feedback')
            ->where('user_id', $user['sub'])
            ->count();
        
        // 转换状态文字
        foreach ($list as &$item) {
            $item['status_text'] = match((int)$item['status']) {
                0 => '待处理',
                1 => '处理中',
                2 => '已解决',
                3 => '已关闭',
                default => '未知',
            };
        }
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
        ]);
    }
}
