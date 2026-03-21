<?php

namespace app\controller\admin;

use app\model\FeedbackAssign;
use app\model\FeedbackLog;
use app\model\Feedback;
use think\facade\Db;
use think\Request;

class FeedbackAssign
{
    /**
     * 获取分配列表
     */
    public function list(Request $request)
    {
        $params = $request->get();
        
        $query = FeedbackAssign::with(['feedback', 'assignedBy', 'assignedTo'])
            ->order('created_at', 'desc');
        
        // 筛选条件
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        if (!empty($params['assigned_to'])) {
            $query->where('assigned_to', $params['assigned_to']);
        }
        
        $list = $query
            ->paginate([
                'list_rows' => $params['pageSize'] ?? 20,
                'page' => $params['page'] ?? 1,
            ]);
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'list' => $list->items(),
                'total' => $list->total(),
            ]
        ]);
    }
    
    /**
     * 分配反馈
     */
    public function assign(Request $request)
    {
        $params = $request->post();
        
        // 参数验证
        if (empty($params['feedback_id']) || empty($params['assigned_to'])) {
            return json(['code' => 400, 'message' => '参数错误']);
        }
        
        Db::startTrans();
        try {
            // 检查反馈是否已分配
            $exists = FeedbackAssign::where('feedback_id', $params['feedback_id'])
                ->where('status', 1)
                ->find();
            
            if ($exists) {
                Db::rollback();
                return json(['code' => 400, 'message' => '该反馈已被分配']);
            }
            
            // 创建分配记录
            $assign = FeedbackAssign::create([
                'feedback_id' => $params['feedback_id'],
                'assigned_by' => $request->user['id'],
                'assigned_to' => $params['assigned_to'],
                'status' => 1,
                'note' => $params['note'] ?? null,
            ]);
            
            // 记录分配日志
            FeedbackLog::create([
                'feedback_id' => $params['feedback_id'],
                'admin_id' => $request->user['id'],
                'action' => 'assign',
                'content' => '分配反馈给ID: ' . $params['assigned_to'],
            ]);
            
            Db::commit();
            return json(['code' => 200, 'message' => '分配成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '分配失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新分配状态
     */
    public function updateStatus(Request $request)
    {
        $params = $request->post();
        
        if (empty($params['id']) || empty($params['status'])) {
            return json(['code' => 400, 'message' => '参数错误']);
        }
        
        $assign = FeedbackAssign::find($params['id']);
        if (!$assign) {
            return json(['code' => 404, 'message' => '分配记录不存在']);
        }
        
        $oldStatus = $assign->status;
        
        Db::startTrans();
        try {
            $assign->status = $params['status'];
            
            if ($params['status'] == 2) {
                $assign->completed_at = date('Y-m-d H:i:s');
            }
            
            $assign->save();
            
            // 记录状态变更日志
            FeedbackLog::create([
                'feedback_id' => $assign->feedback_id,
                'admin_id' => $request->user['id'],
                'action' => 'status',
                'content' => '更新分配状态',
                'old_value' => $oldStatus,
                'new_value' => $params['status'],
            ]);
            
            Db::commit();
            return json(['code' => 200, 'message' => '更新成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取我的待分配
     */
    public function myAssignments(Request $request)
    {
        $adminId = $request->user['id'];
        
        $list = FeedbackAssign::with(['feedback'])
            ->where('assigned_to', $adminId)
            ->where('status', 1)
            ->order('created_at', 'desc')
            ->limit(10)
            ->select();
        
        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $list
        ]);
    }
}
