<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\TarotCard;
use think\facade\Log;

/**
 * 塔罗牌管理控制器
 */
class TarotCards extends BaseController
{
    /**
     * 获取塔罗牌列表（分页+筛选）
     */
    public function index()
    {
        try {
            $params = $this->request->get();
            $result = TarotCard::getAdminList($params);
            return $this->success($result);
        } catch (\Throwable $e) {
            Log::error('获取塔罗牌列表失败: ' . $e->getMessage());
            return $this->error('获取列表失败');
        }
    }

    /**
     * 获取单张牌详情
     */
    public function detail(int $id)
    {
        try {
            $card = TarotCard::find($id);
            if (!$card) {
                return $this->error('塔罗牌不存在', 404);
            }
            return $this->success($card->toArray());
        } catch (\Throwable $e) {
            Log::error('获取塔罗牌详情失败: ' . $e->getMessage());
            return $this->error('获取详情失败');
        }
    }

    /**
     * 更新塔罗牌（仅允许编辑含义字段和启用状态）
     */
    public function update(int $id)
    {
        try {
            $card = TarotCard::find($id);
            if (!$card) {
                return $this->error('塔罗牌不存在', 404);
            }

            $data = $this->request->post();

            // 允许编辑的字段白名单
            $allowedFields = [
                'is_enabled',
                'meaning',
                'reversed_meaning',
                'love_meaning',
                'love_reversed',
                'career_meaning',
                'career_reversed',
                'health_meaning',
                'health_reversed',
                'wealth_meaning',
                'wealth_reversed',
            ];

            $updateData = [];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                return $this->error('没有可更新的字段');
            }

            $card->save($updateData);
            return $this->success($card->toArray(), '更新成功');
        } catch (\Throwable $e) {
            Log::error('更新塔罗牌失败: ' . $e->getMessage());
            return $this->error('更新失败');
        }
    }

    /**
     * 切换启用状态
     */
    public function toggleStatus(int $id)
    {
        try {
            $card = TarotCard::find($id);
            if (!$card) {
                return $this->error('塔罗牌不存在', 404);
            }

            $newStatus = $card->is_enabled ? 0 : 1;
            $card->save(['is_enabled' => $newStatus]);

            return $this->success([
                'id'         => $card->id,
                'is_enabled' => $newStatus,
            ], $newStatus ? '已启用' : '已停用');
        } catch (\Throwable $e) {
            Log::error('切换塔罗牌状态失败: ' . $e->getMessage());
            return $this->error('操作失败');
        }
    }

    /**
     * 批量切换状态
     */
    public function batchStatus()
    {
        try {
            $ids    = $this->request->post('ids', []);
            $status = (int)$this->request->post('status', 1);

            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要操作的塔罗牌');
            }

            TarotCard::whereIn('id', $ids)->update(['is_enabled' => $status]);

            return $this->success([], $status ? '批量启用成功' : '批量停用成功');
        } catch (\Throwable $e) {
            Log::error('批量操作塔罗牌失败: ' . $e->getMessage());
            return $this->error('批量操作失败');
        }
    }

    /**
     * 获取统计信息
     */
    public function stats()
    {
        try {
            $total   = TarotCard::count();
            $enabled = TarotCard::where('is_enabled', 1)->count();
            $major   = TarotCard::where('is_major', 1)->count();
            $minor   = TarotCard::where('is_major', 0)->count();

            $suitStats = [];
            foreach (TarotCard::SUIT_LABELS as $suit => $label) {
                $suitStats[] = [
                    'suit'  => $suit,
                    'label' => $label,
                    'count' => TarotCard::where('suit', $suit)->count(),
                ];
            }

            return $this->success([
                'total'      => $total,
                'enabled'    => $enabled,
                'disabled'   => $total - $enabled,
                'major'      => $major,
                'minor'      => $minor,
                'suit_stats' => $suitStats,
            ]);
        } catch (\Throwable $e) {
            Log::error('获取塔罗牌统计失败: ' . $e->getMessage());
            return $this->error('获取统计失败');
        }
    }
}
