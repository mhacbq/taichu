<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\Request;
use think\facade\Log;
use think\facade\Db;

/**
 * 任务调度控制器
 */
class Task extends BaseController
{
    /**
     * 获取系统任务列表
     */
    public function taskList(Request $request)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限管理系统任务', 403);
        }

        try {
            $list = Db::name('tc_system_task')->select();
            return $this->success($list, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取任务列表失败', 500);
        }
    }

    /**
     * 获取任务详情
     */
    public function taskDetail($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限查看详情', 403);
        }

        try {
            $task = Db::name('tc_system_task')->where('id', $id)->find();
            if (!$task) {
                return $this->error('任务不存在', 404);
            }
            return $this->success($task, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取详情失败', 500);
        }
    }

    /**
     * 创建系统任务
     */
    public function createTask(Request $request)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限创建任务', 403);
        }

        try {
            $data = $request->post();
            $id = Db::name('tc_system_task')->insertGetId($data);
            return $this->success(['id' => $id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建失败', 500);
        }
    }

    /**
     * 更新系统任务
     */
    public function updateTask(Request $request, $id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限修改任务', 403);
        }

        try {
            $data = $request->put();
            Db::name('tc_system_task')->where('id', $id)->update($data);
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
        }
    }

    /**
     * 删除系统任务
     */
    public function deleteTask($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限删除任务', 403);
        }

        try {
            Db::name('tc_system_task')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }

    /**
     * 立即执行任务
     */
    public function runTask($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限执行任务', 403);
        }

        try {
            $task = Db::name('tc_system_task')->where('id', $id)->find();
            if (!$task) {
                return $this->error('任务不存在', 404);
            }

            $this->logOperation('run_task', 'task', [
                'target_id' => $id,
                'detail'    => "手动触发系统任务: {$task['name']}",
            ]);

            return $this->success(null, '任务已触发执行');
        } catch (\Exception $e) {
            return $this->error('触发失败', 500);
        }
    }

    /**
     * 切换任务状态
     */
    public function toggleTaskStatus(Request $request, $id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限修改任务状态', 403);
        }

        try {
            $status = $request->put('status');
            Db::name('tc_system_task')->where('id', $id)->update(['status' => $status]);
            return $this->success(null, '状态更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
        }
    }

    /**
     * 获取任务运行日志
     */
    public function taskLogs(Request $request)
    {
        if (!$this->checkPermission('task_view')) {
            return $this->error('无权限查看任务日志', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', 20)
            );
            $taskId = $request->get('task_id');

            $query = Db::name('tc_system_task_log')->order('id', 'desc');
            if ($taskId) {
                $query->where('task_id', $taskId);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list'  => $list,
                'total' => $total,
            ], '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取日志失败', 500);
        }
    }

    /**
     * 获取系统脚本列表
     */
    public function getTaskScripts()
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限管理脚本', 403);
        }

        try {
            $list = Db::name('tc_system_script')->select();
            return $this->success($list, '获取成功');
        } catch (\Exception $e) {
            return $this->error('获取脚本列表失败', 500);
        }
    }

    /**
     * 保存系统脚本
     */
    public function saveTaskScript(Request $request)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限保存脚本', 403);
        }

        try {
            $data = $request->post();
            $id = $data['id'] ?? 0;
            unset($data['id']);

            if ($id > 0) {
                Db::name('tc_system_script')->where('id', $id)->update($data);
            } else {
                $id = Db::name('tc_system_script')->insertGetId($data);
            }

            return $this->success(['id' => $id], '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败', 500);
        }
    }

    /**
     * 删除脚本
     */
    public function deleteTaskScript($id)
    {
        if (!$this->checkPermission('task_manage')) {
            return $this->error('无权限删除脚本', 403);
        }

        try {
            Db::name('tc_system_script')->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }
}
