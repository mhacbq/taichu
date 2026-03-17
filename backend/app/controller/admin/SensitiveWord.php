<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\ConfigService;
use think\Request;
use think\facade\Db;

/**
 * 后台敏感词管理控制器
 */
class SensitiveWord extends BaseController
{
    private const CATEGORY_SENSITIVE_WORDS = 'sensitive_words';
    private const ALLOWED_TYPES = ['illegal', 'sensitive'];

    /**
     * 敏感词列表
     */
    public function index(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限查看敏感词', 403);
        }

        $keyword = trim((string) $request->get('keyword', ''));

        try {
            $pagination = $this->getPaginationParams('page', 'pageSize', 20, 100);
            $query = Db::name('system_config')
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->order('created_at', 'desc');

            if ($keyword !== '') {
                $query->whereLike('description', '%' . addcslashes($keyword, '%_\\') . '%');
            }

            $total = (clone $query)->count();
            $rows = $query->page($pagination['page'], $pagination['pageSize'])->select()->toArray();
            $allRows = Db::name('system_config')
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->select()
                ->toArray();

            $stats = ['illegal' => 0, 'sensitive' => 0];
            foreach ($allRows as $row) {
                $item = $this->decodeConfigJson($row['config_value'] ?? null);
                $type = (string) ($item['type'] ?? 'sensitive');
                $stats[$type === 'illegal' ? 'illegal' : 'sensitive']++;
            }

            return $this->success([
                'list' => array_map([$this, 'formatWordRow'], $rows),
                'total' => $total,
                'stats' => [
                    'illegal' => $stats['illegal'],
                    'sensitive' => $stats['sensitive'],
                    'total' => $stats['illegal'] + $stats['sensitive'],
                ],
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('sensitive_word_index', $e, '获取敏感词列表失败，请稍后重试', [
                'keyword' => $keyword,
            ]);
        }
    }

    /**
     * 新增敏感词
     */
    public function create(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限添加敏感词', 403);
        }

        try {
            $payload = $this->buildWordPayload($request->post());
            if ($this->hasDuplicateSensitiveWord($payload['word'])) {
                return $this->error('敏感词已存在', 400);
            }

            $id = (int) Db::name('system_config')->insertGetId([
                'config_key' => 'sensitive_word_' . uniqid('', true),
                'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'config_type' => 'json',
                'description' => $payload['word'],
                'category' => self::CATEGORY_SENSITIVE_WORDS,
                'is_editable' => 1,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            ConfigService::clearCache();

            $this->logOperation('create', 'config', [
                'target_id' => $id,
                'target_type' => 'sensitive_word',
                'detail' => '新增敏感词：' . $payload['word'],
                'after_data' => ['id' => $id] + $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '添加成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'sensitive_word_create', '敏感词参数无效', 400);
        } catch (\Throwable $e) {
            return $this->respondSystemException('sensitive_word_create', $e, '添加敏感词失败，请稍后重试');
        }
    }

    /**
     * 更新敏感词
     */
    public function update(Request $request, int $id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限修改敏感词', 403);
        }

        try {
            $existing = Db::name('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->find();
            if (!$existing) {
                return $this->error('敏感词不存在', 404);
            }

            $payload = $this->buildWordPayload($request->put());
            if ($this->hasDuplicateSensitiveWord($payload['word'], $id)) {
                return $this->error('敏感词已存在', 400);
            }

            Db::name('system_config')
                ->where('id', $id)
                ->update([
                    'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'description' => $payload['word'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            ConfigService::clearCache();

            $this->logOperation('update', 'config', [
                'target_id' => $id,
                'target_type' => 'sensitive_word',
                'detail' => '更新敏感词：' . $payload['word'],
                'before_data' => $this->formatWordRow($existing),
                'after_data' => ['id' => $id] + $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '更新成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'sensitive_word_update', '敏感词参数无效', 400, [
                'id' => $id,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('sensitive_word_update', $e, '更新敏感词失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * 删除敏感词
     */
    public function delete(int $id)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限删除敏感词', 403);
        }

        try {
            $existing = Db::name('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_SENSITIVE_WORDS)
                ->find();
            if (!$existing) {
                return $this->error('敏感词不存在', 404);
            }

            Db::name('system_config')->where('id', $id)->delete();
            ConfigService::clearCache();

            $this->logOperation('delete', 'config', [
                'target_id' => $id,
                'target_type' => 'sensitive_word',
                'detail' => '删除敏感词：' . ((string) ($existing['description'] ?? $id)),
                'before_data' => $this->formatWordRow($existing),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('sensitive_word_delete', $e, '删除敏感词失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * 批量导入敏感词
     */
    public function import(Request $request)
    {
        if (!$this->checkPermission('config_manage')) {
            return $this->error('无权限导入敏感词', 403);
        }

        $rawWords = trim((string) $request->post('words', ''));
        if ($rawWords === '') {
            return $this->error('导入内容不能为空', 400);
        }

        $lines = preg_split('/\r\n|\r|\n/', $rawWords) ?: [];
        $insertData = [];
        $addedWords = [];
        $skipped = 0;

        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                [$word, $type, $replacement] = array_pad(explode('|', $line, 3), 3, '');
                $payload = $this->buildWordPayload([
                    'word' => $word,
                    'type' => $type !== '' ? $type : 'sensitive',
                    'replacement' => $replacement,
                    'remark' => '批量导入',
                ]);

                if ($this->hasDuplicateSensitiveWord($payload['word']) || in_array($payload['word'], $addedWords, true)) {
                    $skipped++;
                    continue;
                }

                $insertData[] = [
                    'config_key' => 'sensitive_word_' . uniqid('', true),
                    'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'config_type' => 'json',
                    'description' => $payload['word'],
                    'category' => self::CATEGORY_SENSITIVE_WORDS,
                    'is_editable' => 1,
                    'sort_order' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $addedWords[] = $payload['word'];
            }

            if (!empty($insertData)) {
                Db::name('system_config')->insertAll($insertData);
                ConfigService::clearCache();
            }

            $this->logOperation('import', 'config', [
                'target_type' => 'sensitive_word',
                'detail' => '批量导入敏感词',
                'after_data' => [
                    'imported' => count($insertData),
                    'skipped' => $skipped,
                ],
            ]);

            return $this->success([
                'imported' => count($insertData),
                'skipped' => $skipped,
            ], '导入成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'sensitive_word_import', '导入内容格式无效', 400);
        } catch (\Throwable $e) {
            return $this->respondSystemException('sensitive_word_import', $e, '导入敏感词失败，请稍后重试');
        }
    }

    /**
     * 校验并构建敏感词负载
     */
    protected function buildWordPayload(array $data): array
    {
        $word = trim((string) ($data['word'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'sensitive'));
        $replacement = trim((string) ($data['replacement'] ?? ''));
        $remark = trim((string) ($data['remark'] ?? ''));

        if ($word === '') {
            throw new \InvalidArgumentException('敏感词不能为空');
        }
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException('敏感词类型无效');
        }

        return [
            'word' => $word,
            'type' => $type,
            'replacement' => $replacement,
            'remark' => $remark,
        ];
    }

    /**
     * 格式化单条敏感词记录
     */
    protected function formatWordRow(array $row): array
    {
        $item = $this->decodeConfigJson($row['config_value'] ?? null);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'word' => (string) ($item['word'] ?? ($row['description'] ?? '')),
            'type' => (string) ($item['type'] ?? 'sensitive'),
            'replacement' => (string) ($item['replacement'] ?? ''),
            'remark' => (string) ($item['remark'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * 解析配置 JSON
     */
    protected function decodeConfigJson(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 判断敏感词是否重复
     */
    protected function hasDuplicateSensitiveWord(string $word, int $excludeId = 0): bool
    {
        $rows = Db::name('system_config')
            ->where('category', self::CATEGORY_SENSITIVE_WORDS)
            ->select()
            ->toArray();

        foreach ($rows as $row) {
            if ($excludeId > 0 && (int) ($row['id'] ?? 0) === $excludeId) {
                continue;
            }

            $item = $this->decodeConfigJson($row['config_value'] ?? null);
            if (($item['word'] ?? '') === $word) {
                return true;
            }
        }

        return false;
    }
}
