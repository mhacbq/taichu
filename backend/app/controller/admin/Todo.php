<?php

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\Response;

class Todo extends BaseController
{
    public function list()
    {
        $todos = [];

        // VIP即将到期提醒（7天内）
        // 数据库中似乎没有 tc_user_vip 表，而是使用 tc_vip_order。
        // 我们从已支付且未到期的订单中查找即将到期的。
        $expiringVips = Db::name('tc_vip_order')
            ->alias('vo')
            ->leftJoin('tc_user u', 'vo.user_id = u.id')
            ->where('vo.status', 1) // 已支付
            ->where('vo.end_date', '>', date('Y-m-d'))
            ->where('vo.end_date', '<=', date('Y-m-d', time() + 7 * 86400))
            ->field('vo.id, u.nickname as username, vo.end_date as expire_time')
            ->select();

        foreach ($expiringVips as $vip) {
            $todos[] = [
                'id' => 'vip_' . $vip['id'],
                'type' => 'vip',
                'title' => 'VIP即将到期',
                'description' => "用户 {$vip['username']} 的VIP会员将在 " . $vip['expire_time'] . " 到期",
                'time' => $vip['expire_time'] . ' 23:59:59',
                'priority' => 'high'
            ];
        }

        // 长期未活跃用户提醒（30天未登录）
        $inactiveUsers = Db::name('tc_user')
            ->where('last_login_at', '<', date('Y-m-d H:i:s', time() - 30 * 86400))
            ->where('status', 1)
            ->field('id, nickname as username, last_login_at')
            ->limit(20)
            ->select();

        foreach ($inactiveUsers as $user) {
            $todos[] = [
                'id' => 'inactive_' . $user['id'],
                'type' => 'inactive',
                'title' => '长期未活跃用户',
                'description' => "用户 {$user['username']} 已超过30天未登录",
                'time' => $user['last_login_at'] ?? '从未登录',
                'priority' => 'medium'
            ];
        }

        return $this->success($todos);
    }

    public function vipExpiring()
    {
        $users = Db::name('tc_vip_order')
            ->alias('vo')
            ->leftJoin('tc_user u', 'vo.user_id = u.id')
            ->where('vo.status', 1)
            ->where('vo.end_date', '>', date('Y-m-d'))
            ->where('vo.end_date', '<=', date('Y-m-d', time() + 7 * 86400))
            ->field('vo.id, u.nickname as username, vo.end_date as expire_time')
            ->select();

        return $this->success($users);
    }

    public function inactiveUsers()
    {
        $users = Db::name('tc_user')
            ->where('last_login_at', '<', date('Y-m-d H:i:s', time() - 30 * 86400))
            ->where('status', 1)
            ->field('id, nickname as username, last_login_at')
            ->order('last_login_at', 'asc')
            ->limit(50)
            ->select();

        return $this->success($users);
    }

    public function dismiss()
    {
        $id = $this->request->param('id', $this->request->post('id'));
        $adminId = $this->request->adminId ?? 0;
        
        // 将忽略的待办记录到日志
        Db::name('tc_admin_log')->insert([
            'admin_id' => $adminId,
            'action' => 'dismiss_todo',
            'content' => json_encode(['todo_id' => $id]),
            'ip' => $this->request->ip(),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success();
    }
}
