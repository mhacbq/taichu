<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\AdminLog;
use think\Request;
use think\facade\Db;

class Logs extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取操作日志列表
     */
    public function getOperationLogs()
    {
        if (!$this->hasAdminPermission('log_view')) {
            return $this->error('无权限查看操作日志', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $adminId = $params['admin_id'] ?? '';
        $action = $params['action'] ?? '';
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';

        try {
            $query = Db::table('tc_admin_log')
                ->alias('l')
                ->leftJoin('tc_admin a', 'l.admin_id = a.id')
                ->field('l.*, a.username as admin_username, a.nickname as admin_nickname');

            if ($adminId) {
                $query->where('l.admin_id', $adminId);
            }

            if ($action) {
                $query->where('l.action', $action);
            }

            if ($startDate) {
                $query->where('l.created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('l.created_at', '<=', $endDate . ' 23:59:59');
            }

            $total = $query->count();
            $list = $query->order('l.id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_logs_operation', $e, '获取操作日志失败');
        }
    }

    /**
     * 获取登录日志列表
     */
    public function getLoginLogs()
    {
        if (!$this->hasAdminPermission('log_view')) {
            return $this->error('无权限查看登录日志', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $adminId = $params['admin_id'] ?? '';
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';

        try {
            // 登录日志表可能不存在，先检查
            $loginLogTable = 'tc_admin_login_log';
            $tableExists = false;
            try {
                Db::table($loginLogTable)->limit(1)->select();
                $tableExists = true;
            } catch (\Throwable $ignored) {}

            if (!$tableExists) {
                return $this->success([
                    'list' => [],
                    'total' => 0,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'notice' => '登录日志表尚未创建，请联系管理员初始化数据库'
                ]);
            }

            $query = Db::table($loginLogTable)
                ->alias('l')
                ->leftJoin('tc_admin a', 'l.admin_id = a.id')
                ->field('l.*, a.username as admin_username, a.nickname as admin_nickname');

            if ($adminId) {
                $query->where('l.admin_id', $adminId);
            }

            if ($startDate) {
                $query->where('l.created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('l.created_at', '<=', $endDate . ' 23:59:59');
            }

            $total = $query->count();
            $list = $query->order('l.id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_logs_login', $e, '获取登录日志失败');
        }
    }

    /**
     * 获取API日志列表
     */
    public function getApiLogs()
    {
        if (!$this->hasAdminPermission('log_view')) {
            return $this->error('无权限查看API日志', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $path = $params['path'] ?? '';
        $method = $params['method'] ?? '';
        $status = $params['status'] ?? '';
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';

        try {
            // API日志表可能不存在，先检查
            $apiLogTable = 'tc_api_log';
            $tableExists = false;
            try {
                Db::table($apiLogTable)->limit(1)->select();
                $tableExists = true;
            } catch (\Throwable $ignored) {}

            if (!$tableExists) {
                return $this->success([
                    'list' => [],
                    'total' => 0,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'notice' => 'API日志表尚未创建，请联系管理员初始化数据库'
                ]);
            }

            $query = Db::table($apiLogTable);

            if ($path) {
                $query->whereLike('path', '%' . $path . '%');
            }

            if ($method) {
                $query->where('method', strtoupper($method));
            }

            if ($status !== '') {
                $query->where('status', $status);
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $total = $query->count();
            $list = $query->order('id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_logs_api', $e, '获取API日志失败');
        }
    }

    /**
     * 清理日志
     */
    public function clearLogs()
    {
        if (!$this->hasAdminPermission('log_manage')) {
            return $this->error('无权限清理日志', 403);
        }

        $data = $this->request->post();
        $type = $data['type'] ?? '';
        $days = (int) ($data['days'] ?? 90);

        if (!$type) {
            return $this->error('日志类型不能为空');
        }

        if ($days < 7) {
            return $this->error('至少保留7天日志');
        }

        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            switch ($type) {
                case 'operation':
                    $count = Db::table('tc_admin_log')
                        ->where('created_at', '<', $cutoffDate)
                        ->delete();
                    break;
                case 'login':
                    $count = Db::table('tc_admin_login_log')
                        ->where('created_at', '<', $cutoffDate)
                        ->delete();
                    break;
                case 'api':
                    $count = Db::table('tc_api_log')
                        ->where('created_at', '<', $cutoffDate)
                        ->delete();
                    break;
                default:
                    return $this->error('无效的日志类型');
            }

            $this->logOperation('clear_logs', 'system', [
                'type' => $type,
                'days' => $days,
                'count' => $count
            ]);

            return $this->success(['count' => $count], "已清理{$count}条日志");
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_logs_clear', $e, '清理日志失败');
        }
    }

    /**
     * 导出日志
     */
    public function exportLogs()
    {
        if (!$this->hasAdminPermission('log_view')) {
            return $this->error('无权限导出日志', 403);
        }

        $data = $this->request->post();
        $type = $data['type'] ?? '';
        $startDate = $data['start_date'] ?? '';
        $endDate = $data['end_date'] ?? '';

        if (!$type) {
            return $this->error('日志类型不能为空');
        }

        try {
            switch ($type) {
                case 'operation':
                    $query = Db::table('tc_admin_log')
                        ->alias('l')
                        ->leftJoin('tc_admin a', 'l.admin_id = a.id')
                        ->field('l.*, a.username as admin_username');
                    break;
                case 'login':
                    $query = Db::table('tc_admin_login_log')
                        ->alias('l')
                        ->leftJoin('tc_admin a', 'l.admin_id = a.id')
                        ->field('l.*, a.username as admin_username');
                    break;
                case 'api':
                    $query = Db::table('tc_api_log');
                    break;
                default:
                    return $this->error('无效的日志类型');
            }

            if ($startDate) {
                $query->where('l.created_at', '>=', $startDate . ' 00:00:00');
            }

            if ($endDate) {
                $query->where('l.created_at', '<=', $endDate . ' 23:59:59');
            }

            $logs = $query->order('l.id', 'desc')->select()->toArray();

            // 简化处理，返回JSON格式
            return $this->success([
                'logs' => $logs,
                'total' => count($logs)
            ], '导出成功（请下载JSON格式）');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_logs_export', $e, '导出日志失败');
        }
    }
}
