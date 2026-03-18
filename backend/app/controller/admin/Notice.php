<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\Request;
use think\facade\Db;

/**
 * 后台系统公告控制器
 */
class Notice extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const CATEGORY_SYSTEM_NOTICE = 'system_notice';
    protected const DEFAULT_PAGE_SIZE = 20;
    protected const MAX_PAGE_SIZE = 100;

    /**
     * 获取系统公告列表
     */
    public function getNotices(Request $request)
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限查看公告', 403);
        }

        $statusInput = $request->get('status', '');

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );

            $query = Db::table('system_config')
                ->where('category', self::CATEGORY_SYSTEM_NOTICE)
                ->order('created_at', 'desc');

            if ($statusInput === '' || $statusInput === null) {
                $total = $query->count();
                $rows = $query->page($pagination['page'], $pagination['pageSize'])->select()->toArray();
                $list = array_map([$this, 'formatNoticeRow'], $rows);
            } else {
                $status = filter_var($statusInput, FILTER_VALIDATE_INT);
                if (!in_array($status, [0, 1], true)) {
                    return $this->error('公告状态无效', 400);
                }

                $rows = $query->select()->toArray();
                $filtered = [];
                foreach ($rows as $row) {
                    $item = $this->formatNoticeRow($row);
                    if ((int) $item['status'] !== $status) {
                        continue;
                    }
                    $filtered[] = $item;
                }

                $total = count($filtered);
                $offset = ($pagination['page'] - 1) * $pagination['pageSize'];
                $list = array_slice($filtered, $offset, $pagination['pageSize']);
            }

            return $this->success([
                'list' => array_values($list),
                'total' => $total,
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('notice_list', $e, '获取系统公告失败，请稍后重试', [
                'status' => $statusInput,
                'page' => $request->get('page', 1),
                'pageSize' => $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
            ]);
        }
    }

    /**
     * 保存系统公告
     */
    public function saveNotice(Request $request)
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限保存公告', 403);
        }

        $data = $request->isPut() ? $request->put() : $request->post();
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'normal'));
        $content = trim((string) ($data['content'] ?? ''));
        $status = isset($data['status']) ? (int) $data['status'] : 1;

        if ($title === '' || $content === '') {
            return $this->error('公告标题和内容不能为空', 400);
        }
        if (!in_array($type, ['normal', 'important'], true)) {
            return $this->error('公告类型无效', 400);
        }
        if (!in_array($status, [0, 1], true)) {
            return $this->error('公告状态无效', 400);
        }

        try {
            $existing = null;
            if ($id > 0) {
                $existing = Db::table('system_config')
                    ->where('id', $id)
                    ->where('category', self::CATEGORY_SYSTEM_NOTICE)
                    ->find();
                if (!$existing) {
                    return $this->error('公告不存在', 404);
                }
            }

            $payload = [
                'title' => $title,
                'type' => $type,
                'content' => $content,
                'status' => $status,
            ];

            $saveData = [
                'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'config_type' => 'json',
                'description' => $title,
                'category' => self::CATEGORY_SYSTEM_NOTICE,
                'is_editable' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                Db::table('system_config')->where('id', $id)->update($saveData);
            } else {
                $saveData['config_key'] = 'system_notice_' . uniqid('', true);
                $saveData['sort_order'] = 0;
                $saveData['created_at'] = date('Y-m-d H:i:s');
                $id = (int) Db::table('system_config')->insertGetId($saveData);
            }

            $this->logOperation('save_notice', 'config', [
                'target_id' => $id,
                'target_type' => 'notice',
                'detail' => ($existing ? '更新' : '新增') . '系统公告: ' . $title,
                'before_data' => $existing ? $this->decodeNoticePayload($existing['config_value'] ?? '') : [],
                'after_data' => $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('notice_save', $e, '保存系统公告失败，请稍后重试', [
                'id' => $id,
                'title' => $title,
                'type' => $type,
                'status' => $status,
            ]);
        }
    }

    /**
     * 删除系统公告
     */
    public function deleteNotice(int $id)
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error('无权限删除公告', 403);
        }

        try {
            $existing = Db::table('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_SYSTEM_NOTICE)
                ->find();
            if (!$existing) {
                return $this->error('公告不存在', 404);
            }

            Db::table('system_config')->where('id', $id)->delete();

            $this->logOperation('delete_notice', 'config', [
                'target_id' => $id,
                'target_type' => 'notice',
                'detail' => '删除系统公告: ' . ($existing['description'] ?? ''),
                'before_data' => $this->decodeNoticePayload($existing['config_value'] ?? ''),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('notice_delete', $e, '删除系统公告失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * 格式化公告行
     */
    protected function formatNoticeRow(array $row): array
    {
        $item = $this->decodeNoticePayload($row['config_value'] ?? '');
        $status = (int) ($item['status'] ?? 0);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'title' => (string) (($item['title'] ?? '') ?: ($row['description'] ?? '')),
            'type' => (string) ($item['type'] ?? 'normal'),
            'content' => (string) ($item['content'] ?? ''),
            'status' => $status,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * 解析公告 JSON 配置
     */
    protected function decodeNoticePayload(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
