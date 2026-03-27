<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Log;
use think\facade\Db;

/**
 * 反作弊系统控制器
 */
class Anticheat extends BaseController
{
    /**
     * 获取风险事件列表
     */
    public function riskEvents(Request $request)
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看风险事件', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', 20)
            );
            $status = $request->get('status', '');
            $type = $request->get('type', '');

            $query = Db::name('tc_anti_cheat_event')->order('id', 'desc');
            if ($status !== '') {
                $query->where('status', (int) $status);
            }
            if ($type) {
                $query->where('type', $type);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list' => $list,
                'total' => $total,
            ], '获取成功');
        } catch (\Exception $e) {
            Log::error('获取风险事件失败: ' . $e->getMessage());
            return $this->error('获取失败', 500);
        }
    }

    /**
     * 获取风险事件详情
     */
    public function riskEventDetail($id)
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看详情', 403);
        }

        try {
            $event = Db::name('tc_anti_cheat_event')->where('id', $id)->find();
            if (!$event) {
                return $this->error('事件不存在', 404);
            }
            return $this->success($event, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 处理风险事件
     */
    public function handleRiskEvent(Request $request, $id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限处理风险事件', 403);
        }

        try {
            $data = $request->put();
            $status = $data['status'] ?? 1;
            $remark = $data['remark'] ?? '';

            Db::name('tc_anti_cheat_event')->where('id', $id)->update([
                'status'         => $status,
                'handle_remark'  => $remark,
                'handler_id'     => $this->getAdminId(),
                'handle_at'      => date('Y-m-d H:i:s'),
            ]);

            $this->logOperation('handle_risk_event', 'anticheat', [
                'target_id' => $id,
                'detail'    => "处理风险事件, 状态: {$status}, 备注: {$remark}",
            ]);

            return $this->success(null, '操作成功');
        } catch (\Exception $e) {
            return $this->error('操作失败', 500);
        }
    }

    /**
     * 获取反作弊规则列表
     */
    public function riskRules()
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看规则', 403);
        }

        if (!SchemaInspector::tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        try {
            $list = Db::name('tc_anti_cheat_rule')
                ->order('id', 'desc')
                ->select()
                ->toArray();

            $list = array_map(fn(array $item) => $this->transformRiskRuleRow($item), $list);
            return $this->success($list, '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->getAdminId(),
            ]);
            return $this->error('获取规则失败，请稍后重试', 500);
        }
    }

    /**
     * 新增反作弊规则
     */
    public function saveRiskRule(Request $request)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限保存规则', 403);
        }

        if (!SchemaInspector::tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        try {
            $payload = $this->buildRiskRulePayload($request->post());
            $id = (int) Db::name('tc_anti_cheat_rule')->insertGetId($payload);
            $saved = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();

            $this->logOperation('create', 'anticheat', [
                'target_id'   => $id,
                'target_type' => 'risk_rule',
                'detail'      => '新增反作弊规则：' . ($payload['name'] ?? ''),
                'after_data'  => $this->transformRiskRuleRow($saved ?: []),
            ]);

            return $this->success(['id' => $id], '保存成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('保存反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->getAdminId(),
            ]);
            return $this->error('保存失败，请稍后重试', 500);
        }
    }

    /**
     * 更新反作弊规则
     */
    public function updateRiskRule(Request $request, $id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限更新规则', 403);
        }

        if (!SchemaInspector::tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        $id = (int) $id;
        try {
            $existing = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();
            if (!$existing) {
                return $this->error('规则不存在', 404);
            }

            $payload = $this->buildRiskRulePayload($request->put(), $existing);
            Db::name('tc_anti_cheat_rule')->where('id', $id)->update($payload);
            $saved = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();

            $this->logOperation('update', 'anticheat', [
                'target_id'   => $id,
                'target_type' => 'risk_rule',
                'detail'      => '更新反作弊规则：' . ($payload['name'] ?? ''),
                'before_data' => $this->transformRiskRuleRow($existing),
                'after_data'  => $this->transformRiskRuleRow($saved ?: []),
            ]);

            return $this->success(['id' => $id], '更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('更新反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->getAdminId(),
                'rule_id'  => $id,
            ]);
            return $this->error('更新失败，请稍后重试', 500);
        }
    }

    /**
     * 删除反作弊规则
     */
    public function deleteRiskRule($id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限删除规则', 403);
        }

        if (!SchemaInspector::tableExists('tc_anti_cheat_rule')) {
            return $this->error('反作弊规则表不存在，请先执行 database/20260317_create_anticheat_tables.sql', 500);
        }

        $id = (int) $id;
        try {
            $existing = Db::name('tc_anti_cheat_rule')->where('id', $id)->find();
            if (!$existing) {
                return $this->error('规则不存在', 404);
            }

            Db::name('tc_anti_cheat_rule')->where('id', $id)->delete();
            $this->logOperation('delete', 'anticheat', [
                'target_id'   => $id,
                'target_type' => 'risk_rule',
                'detail'      => '删除反作弊规则：' . (string) ($existing['name'] ?? ''),
                'before_data' => $this->transformRiskRuleRow($existing),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除反作弊规则失败: ' . $e->getMessage(), [
                'admin_id' => $this->getAdminId(),
                'rule_id'  => $id,
            ]);
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 获取设备指纹列表
     */
    public function deviceFingerprints(Request $request)
    {
        if (!$this->checkPermission('anticheat_view')) {
            return $this->error('无权限查看设备信息', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', 20)
            );
            $keyword = $request->get('keyword', '');

            $query = Db::name('tc_anti_cheat_device')->order('last_active_at', 'desc');
            if ($keyword) {
                $query->whereLike('device_id|block_reason', '%' . $keyword . '%');
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list'  => $list,
                'total' => $total,
            ], '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取设备列表失败', 500);
        }
    }

    /**
     * 拉黑/移出黑名单设备
     */
    public function blockDevice(Request $request, $id)
    {
        if (!$this->checkPermission('anticheat_manage')) {
            return $this->error('无权限操作设备', 403);
        }

        try {
            $data = $request->put();
            $isBlocked = $data['is_blocked'] ?? 1;
            $reason = $data['reason'] ?? '';

            Db::name('tc_anti_cheat_device')->where('id', $id)->update([
                'is_blocked'   => $isBlocked,
                'block_reason' => $reason,
            ]);

            return $this->success(null, '操作成功');
        } catch (\Exception $e) {
            return $this->error('操作失败', 500);
        }
    }

    // ─── 私有辅助方法 ────────────────────────────────────────────────────────

    /**
     * 格式化风险规则行数据
     */
    private function transformRiskRuleRow(array $row): array
    {
        $config = $row['config'] ?? [];
        if (is_string($config) && $config !== '') {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($config)) {
            $config = [];
        }

        $threshold = (int) ($config['threshold'] ?? $config['limit'] ?? 0);

        return array_merge($row, [
            'config'    => $config,
            'threshold' => $threshold,
            'status'    => (int) ($row['status'] ?? 0),
            'action'    => (string) ($row['action'] ?? 'log'),
            'code'      => (string) ($row['code'] ?? ''),
        ]);
    }

    /**
     * 组装反作弊规则写入载荷
     */
    private function buildRiskRulePayload(array $data, array $existing = []): array
    {
        $name = trim((string) ($data['name'] ?? ($existing['name'] ?? '')));
        if ($name === '') {
            throw new \InvalidArgumentException('规则名称不能为空');
        }

        $type = trim((string) ($data['type'] ?? ($existing['type'] ?? '')));
        if ($type === '') {
            throw new \InvalidArgumentException('检测类型不能为空');
        }

        $action = trim((string) ($data['action'] ?? ($existing['action'] ?? 'log')));
        if (!in_array($action, ['log', 'captcha', 'block'], true)) {
            throw new \InvalidArgumentException('拦截动作无效');
        }

        $threshold = (int) ($data['threshold'] ?? 0);
        if ($threshold <= 0) {
            $existingConfig = $existing['config'] ?? [];
            if (is_string($existingConfig) && $existingConfig !== '') {
                $decodedConfig = json_decode($existingConfig, true);
                $existingConfig = is_array($decodedConfig) ? $decodedConfig : [];
            }
            $threshold = (int) ($existingConfig['threshold'] ?? 0);
        }
        if ($threshold <= 0) {
            throw new \InvalidArgumentException('阈值必须大于 0');
        }

        $config = $data['config'] ?? ($existing['config'] ?? []);
        if (is_string($config) && $config !== '') {
            $decodedConfig = json_decode($config, true);
            $config = is_array($decodedConfig) ? $decodedConfig : [];
        } elseif (!is_array($config)) {
            $config = [];
        }
        $config['threshold'] = $threshold;
        $config['window_minutes'] = (int) ($config['window_minutes'] ?? 1);

        $code = trim((string) ($data['code'] ?? ($existing['code'] ?? '')));
        if ($code === '') {
            $code = strtolower($type . '_' . substr(md5($name . microtime(true) . mt_rand()), 0, 8));
        }
        $code = trim((string) preg_replace('/[^a-z0-9_]+/i', '_', strtolower($code)), '_');
        if ($code === '') {
            throw new \InvalidArgumentException('规则编码无效');
        }

        return [
            'name'   => $name,
            'code'   => $code,
            'type'   => $type,
            'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'action' => $action,
            'status' => (int) (($data['status'] ?? ($existing['status'] ?? 1)) == 1),
        ];
    }

}
