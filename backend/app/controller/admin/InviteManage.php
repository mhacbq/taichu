<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\InviteRecord;
use app\model\User;
use think\facade\Db;

class InviteManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 邀请记录列表（管理端）
     */
    public function index()
    {
        $page     = (int) $this->request->get('page', 1);
        $limit    = min((int) $this->request->get('limit', 20), 100);
        $keyword  = trim($this->request->get('keyword', ''));
        $consumed = $this->request->get('consumed', '');  // '' | '1' | '0'

        // 基础查询
        $query = InviteRecord::alias('ir')
            ->join('tc_user inviter', 'inviter.id = ir.inviter_id', 'LEFT')
            ->join('tc_user invitee', 'invitee.id = ir.invitee_id', 'LEFT')
            ->field([
                'ir.id',
                'ir.inviter_id',
                'inviter.nickname as inviter_nickname',
                'inviter.mobile as inviter_mobile',
                'ir.invitee_id',
                'invitee.nickname as invitee_nickname',
                'invitee.mobile as invitee_mobile',
                'ir.points_reward',
                'ir.status',
                'ir.created_at',
            ])
            ->where('ir.status', 1)
            ->order('ir.created_at', 'desc');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('inviter.nickname', "%{$keyword}%")
                  ->whereOr('invitee.nickname', 'like', "%{$keyword}%")
                  ->whereOr('inviter.mobile', 'like', "%{$keyword}%")
                  ->whereOr('invitee.mobile', 'like', "%{$keyword}%");
            });
        }

        $total   = $query->count();
        $records = $query->page($page, $limit)->select()->toArray();

        // 查询哪些被邀请用户有过充值消费
        $inviteeIds = array_unique(array_filter(array_column($records, 'invitee_id')));
        $consumedIds = [];
        if (!empty($inviteeIds)) {
            $consumedIds = Db::name('tc_recharge_order')
                ->whereIn('user_id', $inviteeIds)
                ->where('status', 'paid')
                ->column('user_id');
            $consumedIds = array_unique(array_map('intval', $consumedIds));
        }

        // 注入消费状态
        foreach ($records as &$record) {
            $record['has_consumed'] = in_array((int)$record['invitee_id'], $consumedIds, true);
        }
        unset($record);

        // 按消费状态过滤
        if ($consumed === '1') {
            $records = array_values(array_filter($records, fn($r) => $r['has_consumed']));
        } elseif ($consumed === '0') {
            $records = array_values(array_filter($records, fn($r) => !$r['has_consumed']));
        }

        return $this->success([
            'list'         => $records,
            'total'        => (int) $total,
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ]);
    }

    /**
     * 邀请统计数据
     */
    public function stats()
    {
        $totalInvites = InviteRecord::where('status', 1)->count();

        // 有充值消费的邀请数
        $consumedCount = InviteRecord::alias('ir')
            ->join('tc_recharge_order ro', 'ro.user_id = ir.invitee_id AND ro.status = \'paid\'', 'INNER')
            ->where('ir.status', 1)
            ->count();

        // 本月新增邀请
        $monthStart = date('Y-m-01 00:00:00');
        $monthInvites = InviteRecord::where('status', 1)
            ->where('created_at', '>=', $monthStart)
            ->count();

        // 总发放积分
        $totalPoints = InviteRecord::where('status', 1)->sum('points_reward');

        return $this->success([
            'total_invites'  => (int) $totalInvites,
            'consumed_count' => (int) $consumedCount,
            'month_invites'  => (int) $monthInvites,
            'total_points'   => (int) $totalPoints,
        ]);
    }
}
