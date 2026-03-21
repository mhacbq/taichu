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
        $expiringVips = Db::name('user_vip')
            ->alias('uv')
            ->leftJoin('user u', 'uv.user_id = u.id')
            ->where('uv.expire_time', '>', time())
            ->where('uv.expire_time', '<=', time() + 7 * 86400)
            ->where('uv.status', 1)
            ->field('uv.id, u.username, uv.expire_time')
            ->select();

        foreach ($expiringVips as $vip) {
            $todos[] = [
                'id' => 'vip_' . $vip['id'],
                'type' => 'vip',
                'title' => 'VIP即将到期',
                'description' => "用户 {$vip['username']} 的VIP会员将在 " . date('Y-m-d', $vip['expire_time']) . " 到期",
                'time' => date('Y-m-d H:i:s', $vip['expire_time']),
                'priority' => 'high'
            ];
        }

        // 长期未活跃用户提醒（30天未登录）
        $inactiveUsers = Db::name('user')
            ->where('last_login_time', '<', time() - 30 * 86400)
            ->where('status', 1)
            ->field('id, username, last_login_time')
            ->limit(20)
            ->select();

        foreach ($inactiveUsers as $user) {
            $todos[] = [
                'id' => 'inactive_' . $user['id'],
                'type' => 'inactive',
                'title' => '长期未活跃用户',
                'description' => "用户 {$user['username']} 已超过30天未登录",
                'time' => date('Y-m-d H:i:s', $user['last_login_time']),
                'priority' => 'medium'
            ];
        }

        return $this->success($todos);
    }

    public function vipExpiring()
    {
        $users = Db::name('user_vip')
            ->alias('uv')
            ->leftJoin('user u', 'uv.user_id = u.id')
            ->where('uv.expire_time', '>', time())
            ->where('uv.expire_time', '<=', time() + 7 * 86400)
            ->where('uv.status', 1)
            ->field('uv.id, u.username, uv.expire_time')
            ->select();

        return $this->success($users);
    }

    public function inactiveUsers()
    {
        $users = Db::name('user')
            ->where('last_login_time', '<', time() - 30 * 86400)
            ->where('status', 1)
            ->field('id, username, last_login_time')
            ->order('last_login_time', 'asc')
            ->limit(50)
            ->select();

        return $this->success($users);
    }

    public function dismiss()
    {
        $id = $this->request->param('id', $this->request->post('id'));
        
        // 将忽略的待办记录到日志
        Db::name('admin_todo_log')->insert([
            'admin_id' => $this->adminId,
            'todo_id' => $id,
            'action' => 'dismiss',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->success();
    }
}
