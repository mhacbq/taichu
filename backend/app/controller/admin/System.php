<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\AdminUser;
use think\Request;

class System extends BaseController
{
    /**
     * 获取管理员列表
     */
    public function adminList()
    {
        if (!$this->checkPermission('user_manage')) {
            return $this->error('无权限查看管理员列表', 403);
        }

        try {
            $admins = AdminUser::where('status', 1)
                ->field('id,username,nickname,role_id')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success($admins, '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('system_admin_list', $e, '获取管理员列表失败');
        }
    }
}
