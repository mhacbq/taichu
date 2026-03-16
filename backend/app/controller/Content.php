<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\middleware\AdminAuth;
use app\model\Page;
use app\model\PageVersion;
use app\model\PageDraft;
use app\model\PageRecycle;
use app\model\OperationLog;
use think\Request;

/**
 * 内容管理控制器
 * 支持可视化编辑的内容管理
 */
class Content extends BaseController
{
    /**
     * 中间件配置
     */
    protected $middleware = [
        AdminAuth::class
    ];

    /**
     * 获取页面内容
     */
    public function getPage(Request $request, string $pageId)
    {
        try {
            $page = Page::where('page_id', $pageId)->find();
            
            if (!$page) {
                // 返回默认页面结构
                return json([
                    'code' => 200,
                    'data' => [
                        'page_id' => $pageId,
                        'title' => $this->getDefaultTitle($pageId),
                        'blocks' => [],
                        'settings' => [
                            'background' => '#f5f7fa',
                            'padding' => '20px',
                            'maxWidth' => '1200px'
                        ]
                    ]
                ]);
            }
            
            return json([
                'code' => 200,
                'data' => [
                    'page_id' => $page->page_id,
                    'title' => $page->title,
                    'blocks' => json_decode($page->content, true) ?: [],
                    'settings' => json_decode($page->settings, true) ?: [],
                    'version' => $page->version,
                    'updated_at' => $page->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 保存页面内容
     */
    public function savePage(Request $request, string $pageId)
    {
        try {
            $data = $request->post();
            
            // 验证数据
            if (empty($data['blocks'])) {
                return json(['code' => 400, 'message' => '内容块不能为空']);
            }
            
            // 保存历史版本
            $oldPage = Page::where('page_id', $pageId)->find();
            if ($oldPage) {
                PageVersion::create([
                    'page_id' => $pageId,
                    'content' => $oldPage->content,
                    'settings' => $oldPage->settings,
                    'version' => $oldPage->version,
                    'author_id' => $request->adminId,
                    'author_name' => $request->adminName,
                    'description' => $data['description'] ?? '自动保存'
                ]);
            }
            
            // 保存或更新页面
            $page = Page::updateOrCreate(
                ['page_id' => $pageId],
                [
                    'title' => $data['title'] ?? $this->getDefaultTitle($pageId),
                    'content' => json_encode($data['blocks']),
                    'settings' => json_encode($data['settings'] ?? []),
                    'version' => $oldPage ? $oldPage->version + 1 : 1,
                    'updated_by' => $request->adminId
                ]
            );
            
            // 记录操作日志
            $this->logOperation('save_page', "保存页面: {$pageId}");
            
            return json([
                'code' => 200,
                'message' => '保存成功',
                'data' => [
                    'version' => $page->version,
                    'updated_at' => $page->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 自动保存页面（草稿）
     */
    public function autoSave(Request $request, string $pageId)
    {
        try {
            $data = $request->post();
            
            // 保存到草稿表
            PageDraft::updateOrCreate(
                ['page_id' => $pageId, 'admin_id' => $request->adminId],
                [
                    'content' => json_encode($data['blocks'] ?? []),
                    'settings' => json_encode($data['settings'] ?? []),
                    'auto_save' => true
                ]
            );
            
            return json([
                'code' => 200,
                'message' => '自动保存成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 获取草稿
     */
    public function getDraft(Request $request, string $pageId)
    {
        try {
            $draft = PageDraft::where('page_id', $pageId)
                ->where('admin_id', $request->adminId)
                ->order('updated_at', 'desc')
                ->find();
            
            if (!$draft) {
                return json(['code' => 404, 'message' => '没有草稿']);
            }
            
            return json([
                'code' => 200,
                'data' => [
                    'blocks' => json_decode($draft->content, true),
                    'settings' => json_decode($draft->settings, true),
                    'updated_at' => $draft->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 获取页面版本历史
     */
    public function getVersions(Request $request, string $pageId)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            
            $versions = PageVersion::where('page_id', $pageId)
                ->order('created_at', 'desc')
                ->page($page, $pageSize)
                ->select();
            
            $total = PageVersion::where('page_id', $pageId)->count();
            
            return json([
                'code' => 200,
                'data' => [
                    'list' => $versions,
                    'total' => $total
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 恢复版本
     */
    public function restoreVersion(Request $request, int $versionId)
    {
        try {
            $version = PageVersion::find($versionId);
            
            if (!$version) {
                return json(['code' => 404, 'message' => '版本不存在']);
            }
            
            // 保存当前版本到历史
            $currentPage = Page::where('page_id', $version->page_id)->find();
            if ($currentPage) {
                PageVersion::create([
                    'page_id' => $currentPage->page_id,
                    'content' => $currentPage->content,
                    'settings' => $currentPage->settings,
                    'version' => $currentPage->version,
                    'author_id' => $request->adminId,
                    'author_name' => $request->adminName,
                    'description' => '恢复版本前的备份'
                ]);
            }
            
            // 恢复版本
            Page::updateOrCreate(
                ['page_id' => $version->page_id],
                [
                    'content' => $version->content,
                    'settings' => $version->settings,
                    'version' => ($currentPage->version ?? 0) + 1,
                    'updated_by' => $request->adminId
                ]
            );
            
            $this->logOperation('restore_version', "恢复页面 {$version->page_id} 到版本 {$version->version}");
            
            return json([
                'code' => 200,
                'message' => '恢复成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 预览版本
     */
    public function previewVersion(Request $request, int $versionId)
    {
        try {
            $version = PageVersion::find($versionId);
            
            if (!$version) {
                return json(['code' => 404, 'message' => '版本不存在']);
            }
            
            return json([
                'code' => 200,
                'data' => [
                    'blocks' => json_decode($version->content, true),
                    'settings' => json_decode($version->settings, true),
                    'version' => $version->version,
                    'author' => $version->author_name,
                    'created_at' => $version->created_at,
                    'description' => $version->description
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 导出页面
     */
    public function exportPage(Request $request, string $pageId)
    {
        try {
            $page = Page::where('page_id', $pageId)->find();
            
            if (!$page) {
                return json(['code' => 404, 'message' => '页面不存在']);
            }
            
            $exportData = [
                'page_id' => $page->page_id,
                'title' => $page->title,
                'content' => json_decode($page->content, true),
                'settings' => json_decode($page->settings, true),
                'version' => $page->version,
                'exported_at' => date('Y-m-d H:i:s')
            ];
            
            return json([
                'code' => 200,
                'data' => $exportData
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 导入页面
     */
    public function importPage(Request $request)
    {
        try {
            $data = $request->post();
            
            // 验证数据
            if (empty($data['page_id']) || empty($data['content'])) {
                return json(['code' => 400, 'message' => '数据格式错误']);
            }
            
            // 检查页面是否存在
            $exists = Page::where('page_id', $data['page_id'])->find();
            
            // 保存页面
            $page = Page::updateOrCreate(
                ['page_id' => $data['page_id']],
                [
                    'title' => $data['title'] ?? $this->getDefaultTitle($data['page_id']),
                    'content' => json_encode($data['content']),
                    'settings' => json_encode($data['settings'] ?? []),
                    'version' => ($exists->version ?? 0) + 1,
                    'updated_by' => $request->adminId
                ]
            );
            
            $this->logOperation('import_page', "导入页面: {$data['page_id']}");
            
            return json([
                'code' => 200,
                'message' => '导入成功',
                'data' => [
                    'page_id' => $page->page_id,
                    'version' => $page->version
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 获取所有页面列表
     */
    public function getPages(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $keyword = $request->get('keyword');
            
            // 验证分页参数
            $page = filter_var($page, FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT) ?: 20;
            $page = max(1, $page);
            $pageSize = max(1, min(100, $pageSize)); // 限制最大100条
            
            $query = Page::order('updated_at', 'desc');
            
            if ($keyword) {
                // 使用ThinkPHP参数绑定，防止SQL注入
                $keyword = preg_replace('/[%_\\\\]/', '', $keyword);
                $query->where('title|page_id', 'like', '%' . $keyword . '%');
            }
            
            $pages = $query->page($page, $pageSize)->select();
            $total = $query->count();
            
            return json([
                'code' => 200,
                'data' => [
                    'list' => $pages,
                    'total' => $total
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 删除页面
     */
    public function deletePage(Request $request, string $pageId)
    {
        try {
            $page = Page::where('page_id', $pageId)->find();
            
            if (!$page) {
                return json(['code' => 404, 'message' => '页面不存在']);
            }
            
            // 保存到回收站
            PageRecycle::create([
                'page_id' => $page->page_id,
                'title' => $page->title,
                'content' => $page->content,
                'settings' => $page->settings,
                'version' => $page->version,
                'deleted_by' => $request->adminId,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
            
            // 删除页面
            $page->delete();
            
            $this->logOperation('delete_page', "删除页面: {$pageId}");
            
            return json([
                'code' => 200,
                'message' => '删除成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 获取块配置
     */
    public function getBlockConfig(Request $request, string $type)
    {
        try {
            $configs = [
                'text' => [
                    'name' => '文本',
                    'props' => [
                        ['name' => 'type', 'label' => '类型', 'type' => 'select', 'options' => [
                            ['value' => 'text', 'label' => '单行文本'],
                            ['value' => 'textarea', 'label' => '多行文本'],
                            ['value' => 'rich', 'label' => '富文本']
                        ]],
                        ['name' => 'placeholder', 'label' => '占位符', 'type' => 'input']
                    ]
                ],
                'image' => [
                    'name' => '图片',
                    'props' => [
                        ['name' => 'width', 'label' => '宽度', 'type' => 'input'],
                        ['name' => 'height', 'label' => '高度', 'type' => 'input'],
                        ['name' => 'fit', 'label' => '适应模式', 'type' => 'select', 'options' => [
                            ['value' => 'fill', 'label' => '填充'],
                            ['value' => 'contain', 'label' => '包含'],
                            ['value' => 'cover', 'label' => '覆盖'],
                            ['value' => 'none', 'label' => '无'],
                            ['value' => 'scale-down', 'label' => '缩放']
                        ]]
                    ]
                ],
                'carousel' => [
                    'name' => '轮播图',
                    'props' => [
                        ['name' => 'height', 'label' => '高度', 'type' => 'input'],
                        ['name' => 'interval', 'label' => '间隔(ms)', 'type' => 'number'],
                        ['name' => 'type', 'label' => '类型', 'type' => 'select', 'options' => [
                            ['value' => '', 'label' => '默认'],
                            ['value' => 'card', 'label' => '卡片']
                        ]]
                    ]
                ],
                'card' => [
                    'name' => '卡片',
                    'props' => [
                        ['name' => 'shadow', 'label' => '阴影', 'type' => 'select', 'options' => [
                            ['value' => 'always', 'label' => '一直显示'],
                            ['value' => 'hover', 'label' => '悬停显示'],
                            ['value' => 'never', 'label' => '不显示']
                        ]],
                        ['name' => 'rows', 'label' => '内容行数', 'type' => 'number']
                    ]
                ],
                'list' => [
                    'name' => '列表',
                    'props' => []
                ],
                'stat' => [
                    'name' => '统计',
                    'props' => []
                ],
                'chart' => [
                    'name' => '图表',
                    'props' => [
                        ['name' => 'chartType', 'label' => '图表类型', 'type' => 'select', 'options' => [
                            ['value' => 'line', 'label' => '折线图'],
                            ['value' => 'bar', 'label' => '柱状图'],
                            ['value' => 'pie', 'label' => '饼图'],
                            ['value' => 'doughnut', 'label' => '环形图']
                        ]]
                    ]
                ]
            ];
            
            return json([
                'code' => 200,
                'data' => $configs[$type] ?? null
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 获取默认标题
     */
    private function getDefaultTitle(string $pageId): string
    {
        $titles = [
            'home' => '首页',
            'dashboard' => '仪表盘',
            'about' => '关于我们',
            'help' => '帮助中心',
            'contact' => '联系我们'
        ];
        
        return $titles[$pageId] ?? '未命名页面';
    }

    /**
     * 记录操作日志
     */
    private function logOperation(string $action, string $description)
    {
        // 记录到操作日志表
        OperationLog::create([
            'action' => $action,
            'description' => $description,
            'admin_id' => request()->adminId ?? 0,
            'ip' => request()->ip(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
