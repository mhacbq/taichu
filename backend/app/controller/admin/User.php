<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\BaziRecord;
use app\model\PointsRecord;
use app\model\TarotRecord;
use app\model\User as UserModel;
use think\facade\Db;
use app\service\AdminStatsService;
use app\service\SchemaInspector;
use think\Request;

/**
 * 后台用户管理控制器
 */
class User extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取用户列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('user_view')) {
            return $this->error('无权限查看用户列表', 403);
        }

        try {
            $params = $this->request->get();
            $data = AdminStatsService::getUserList($params);
            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_user_index', $e, '获取用户列表失败，请稍后重试', [
                'params' => $params ?? [],
            ]);
        }
    }

    /**
     * 获取用户详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('user_view')) {
            return $this->error('无权限查看用户详情', 403);
        }

        try {
            $user = UserModel::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $userData = $this->mergeUserProfileData($user->toArray(), $id);
            $displayUsername = $this->resolveDisplayUsername($userData, $id);
            $normalizedUserData = $this->normalizeDetailUserData($userData, $displayUsername, $id);
            $pointsRecords = [];
            if (SchemaInspector::tableExists('tc_points_record')) {
                $pointsRecordRows = PointsRecord::where('user_id', $id)
                    ->order('created_at', 'desc')
                    ->limit(20)
                    ->select()
                    ->toArray();
                $pointsRecords = PointsRecord::normalizeRecordList($pointsRecordRows, [
                    $id => (int) ($normalizedUserData['points'] ?? 0),
                ]);
            }

            $liuyaoTable = $this->resolveFirstExistingTable(['tc_liuyao_record', 'liuyao_records']);
            $vipOrderTable = $this->resolveFirstExistingTable(['tc_vip_order', 'vip_orders']);
            $rechargeOrderTable = $this->resolveFirstExistingTable(['tc_recharge_order']);

            $baziCount = SchemaInspector::tableExists('tc_bazi_record')
                ? BaziRecord::where('user_id', $id)->count()
                : 0;
            $tarotCount = SchemaInspector::tableExists('tc_tarot_record')
                ? TarotRecord::where('user_id', $id)->count()
                : 0;
            $liuyaoCount = $liuyaoTable !== null
                ? Db::table($liuyaoTable)->where('user_id', $id)->count()
                : 0;
            $vipOrders = $vipOrderTable !== null
                ? Db::table($vipOrderTable)->where('user_id', $id)->order('created_at', 'desc')->limit(10)->select()->toArray()
                : [];
            $rechargeOrders = $rechargeOrderTable !== null
                ? Db::table($rechargeOrderTable)->where('user_id', $id)->order('created_at', 'desc')->limit(10)->select()->toArray()
                : [];
            $canAdjustPoints = $this->hasAdminPermission('points_adjust');
            $canEditProfile = $this->hasAdminPermission('user_edit');

            $stats = [
                'bazi_count' => $baziCount,
                'tarot_count' => $tarotCount,
                'liuyao_count' => $liuyaoCount,
                'points_records' => $pointsRecords,
                'vip_orders' => $vipOrders,
                'recharge_orders' => $rechargeOrders,
                'points_summary' => [
                    'current_balance' => (int) ($normalizedUserData['points'] ?? 0),
                    'total_adjust_records' => count($pointsRecords),
                    'can_adjust' => $canAdjustPoints,
                ],
            ];

            $payload = array_merge($normalizedUserData, [
                'username' => $displayUsername,
                'email' => (string) ($normalizedUserData['email'] ?? ''),
                'bazi_count' => $baziCount,
                'tarot_count' => $tarotCount,
                'liuyao_count' => $liuyaoCount,
                'points_records' => $pointsRecords,
                'vip_orders' => $vipOrders,
                'recharge_orders' => $rechargeOrders,
                'can_adjust_points' => $canAdjustPoints,
                'can_edit_profile' => $canEditProfile,
                'user' => $normalizedUserData,
                'stats' => $stats,
                'actions' => [
                    'can_adjust_points' => $canAdjustPoints,
                    'can_edit_profile' => $canEditProfile,
                ],
            ]);

            return $this->success($payload);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_user_detail', $e, '获取用户详情失败，请稍后重试', [
                'user_id' => $id,
            ]);
        }
    }

    /**
     * 编辑用户资料
     */
    public function updateProfile(int $id)
    {
        if (!$this->hasAdminPermission('user_edit')) {
            return $this->error('无权限编辑用户资料', 403);
        }

        $data = $this->request->isPut() ? $this->request->put() : $this->request->post();
        if (!is_array($data)) {
            $data = [];
        }

        try {
            $user = UserModel::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $userColumns = SchemaInspector::tableExists('tc_user')
                ? SchemaInspector::getTableColumns('tc_user')
                : [];
            $profileColumns = SchemaInspector::tableExists('tc_user_profile')
                ? SchemaInspector::getTableColumns('tc_user_profile')
                : [];
            $currentUserData = $user->toArray();
            $currentProfileData = $this->loadProfileData($id);
            $sanitizedData = $this->sanitizeEditableProfileData($data);

            if (empty($sanitizedData)) {
                return $this->error('没有可更新的资料字段', 400);
            }

            if (array_key_exists('email', $sanitizedData)
                && !isset($userColumns['email'])
                && !isset($profileColumns['email'])) {
                return $this->error('当前环境尚未初始化邮箱字段，请先执行数据库脚本', 500);
            }

            $userUpdate = [];
            foreach (['nickname', 'avatar', 'gender', 'phone'] as $field) {
                if (array_key_exists($field, $sanitizedData) && isset($userColumns[$field])) {
                    $userUpdate[$field] = $sanitizedData[$field];
                }
            }
            if (array_key_exists('email', $sanitizedData) && isset($userColumns['email'])) {
                $userUpdate['email'] = $sanitizedData['email'];
            }
            if (!empty($userUpdate) && isset($userColumns['updated_at'])) {
                $userUpdate['updated_at'] = date('Y-m-d H:i:s');
            }

            $profileUpdate = [];
            if (array_key_exists('email', $sanitizedData) && isset($profileColumns['email'])) {
                $profileUpdate['email'] = $sanitizedData['email'];
            }
            if (!empty($profileUpdate) && isset($profileColumns['updated_at'])) {
                $profileUpdate['updated_at'] = date('Y-m-d H:i:s');
            }

            if (empty($userUpdate) && empty($profileUpdate)) {
                return $this->error('当前环境没有可写入的资料字段，请先执行数据库脚本', 500);
            }

            $beforeData = [
                'nickname' => (string) ($currentUserData['nickname'] ?? ''),
                'avatar' => (string) ($currentUserData['avatar'] ?? ''),
                'gender' => (int) ($currentUserData['gender'] ?? 0),
                'phone' => (string) ($currentUserData['phone'] ?? ''),
                'email' => (string) (($currentUserData['email'] ?? '') ?: ($currentProfileData['email'] ?? '')),
            ];

            Db::startTrans();
            if (!empty($userUpdate)) {
                Db::table('tc_user')->where('id', $id)->update($userUpdate);
            }

            if (!empty($profileUpdate) && !empty($profileColumns)) {
                $existingProfile = empty($currentProfileData)
                    ? null
                    : Db::table('tc_user_profile')->where('user_id', $id)->find();

                if ($existingProfile) {
                    Db::table('tc_user_profile')->where('user_id', $id)->update($profileUpdate);
                } else {
                    $insertData = ['user_id' => $id] + $profileUpdate;
                    if (isset($profileColumns['created_at'])) {
                        $insertData['created_at'] = date('Y-m-d H:i:s');
                    }
                    if (isset($profileColumns['updated_at'])) {
                        $insertData['updated_at'] = date('Y-m-d H:i:s');
                    }
                    Db::table('tc_user_profile')->insert($insertData);
                }
            }
            Db::commit();

            $freshUser = UserModel::find($id);
            $afterMerged = $freshUser ? $this->mergeUserProfileData($freshUser->toArray(), $id) : [];
            $afterData = [
                'nickname' => (string) ($afterMerged['nickname'] ?? ''),
                'avatar' => (string) ($afterMerged['avatar'] ?? ''),
                'gender' => (int) ($afterMerged['gender'] ?? 0),
                'phone' => (string) ($afterMerged['phone'] ?? ''),
                'email' => (string) ($afterMerged['email'] ?? ''),
            ];

            $this->logOperation('update_user_profile', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => '更新用户资料',
                'before_data' => $beforeData,
                'after_data' => $afterData,
            ]);

            return $this->success($afterData, '用户资料更新成功');
        } catch (\InvalidArgumentException $e) {
            Db::rollback();
            return $this->respondBusinessException($e, 'admin_update_user_profile_validation', $e->getMessage(), 400, [
                'user_id' => $id,
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_update_user_profile', $e, '更新用户资料失败，请稍后重试', [
                'user_id' => $id,
                'fields' => array_keys($data),
            ]);
        }
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints()
    {
        if (!$this->hasAdminPermission('points_adjust')) {
            return $this->error('无权限调整用户积分', 403);
        }

        $data = $this->request->post();
        $normalizedPoints = null;

        try {
            $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $normalizedPoints = $this->resolvePointsDelta($data);
            if ($userId === false || $normalizedPoints === null) {
                return $this->error('积分调整参数无效，请传入 points 或 type/amount', 400);
            }
            if ($normalizedPoints === 0) {
                return $this->error('积分调整值不能为 0', 400);
            }

            $reason = mb_substr(trim((string) ($data['reason'] ?? '管理员调整')) ?: '管理员调整', 0, 255);
            $result = AdminStatsService::adjustUserPoints(
                (int) $userId,
                $normalizedPoints,
                $reason,
                $this->getAdminId()
            );

            $result['current_points'] = $result['after_points'] ?? null;

            return $this->success($result, '积分调整成功');
        } catch (\Throwable $e) {
            $context = [
                'user_id' => $data['user_id'] ?? null,
                'points' => $normalizedPoints,
                'reason' => $data['reason'] ?? '',
            ];

            if ($e->getMessage() === '用户不存在') {
                return $this->respondBusinessException($e, 'admin_adjust_points_user_not_found', '用户不存在', 404, $context);
            }

            if ($e->getMessage() === '积分不足') {
                return $this->respondBusinessException($e, 'admin_adjust_points_insufficient', '积分不足', 400, $context);
            }

            return $this->respondSystemException('admin_adjust_points', $e, '调整积分失败，请稍后重试', $context);
        }
    }

    /**
     * 禁用/启用用户
     */
    public function toggleStatus(int $id)
    {
        if (!$this->hasAdminPermission('user_edit')) {
            return $this->error('无权限修改用户状态', 403);
        }

        try {
            $user = UserModel::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $requestedStatus = $this->request->put('status', null);
            if ($requestedStatus !== null && $requestedStatus !== '') {
                $newStatus = filter_var($requestedStatus, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                if ($newStatus === false || !in_array($newStatus, [0, 1, 2], true)) {
                    return $this->error('状态值无效', 400);
                }
            } else {
                $newStatus = (int) ($user->status == 1 ? 0 : 1);
            }

            $oldStatus = (int) ($user->status ?? 0);
            $user->status = $newStatus;
            $user->save();

            $this->logOperation('toggle_user_status', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => sprintf('更新用户状态: %s -> %s', $oldStatus, $newStatus),
                'before_data' => ['status' => $oldStatus],
                'after_data' => ['status' => $newStatus],
            ]);

            return $this->success(['status' => $newStatus], '状态更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_toggle_user_status', $e, '更新状态失败，请稍后重试', [
                'user_id' => $id,
                'requested_status' => $this->request->put('status', null),
            ]);
        }
    }

    /**
     * 批量更新用户状态
     */
    public function batchUpdateStatus(Request $request)
    {
        if (!$this->hasAdminPermission('user_edit')) {
            return $this->error('无权限批量编辑用户', 403);
        }

        $ids = $request->put('ids', []);
        $status = $request->put('status');

        try {
            if (!is_array($ids) || empty($ids)) {
                return $this->error('用户ID列表不能为空', 400);
            }
            if (!in_array($status, [0, 1, 2], true)) {
                return $this->error('状态值无效，必须是0(禁用)、1(正常)或2(待验证)', 400);
            }

            $userIds = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $value): bool => $value > 0)));
            if (empty($userIds)) {
                return $this->error('用户ID列表无效', 400);
            }

            $existingCount = UserModel::whereIn('id', $userIds)->count();
            if ($existingCount === 0) {
                return $this->error('未找到可更新的用户', 404);
            }

            UserModel::whereIn('id', $userIds)->update(['status' => $status]);

            $this->logOperation('batch_update_status', 'user', [
                'detail' => '批量更新用户状态为: ' . $status,
                'after_data' => [
                    'user_ids' => $userIds,
                    'status' => $status,
                    'matched_count' => $existingCount,
                ],
            ]);

            return $this->success([
                'updated_count' => $existingCount,
                'status' => $status,
                'user_ids' => $userIds,
            ], '批量操作成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('user_batch_update_status', $e, '批量操作失败，请稍后重试', [
                'ids' => $ids,
                'status' => $status,
            ]);
        }
    }

    /**
     * 兼容 points 与 type/amount 两套积分调整入参
     */
    protected function resolvePointsDelta(array $data): ?int
    {
        $directPoints = filter_var($data['points'] ?? null, FILTER_VALIDATE_INT);
        if ($directPoints !== false && $directPoints !== null) {
            return (int) $directPoints;
        }

        $type = strtolower(trim((string) ($data['type'] ?? '')));
        $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_INT);
        if ($amount === false || $amount === null || (int) $amount === 0) {
            return null;
        }

        $amount = abs((int) $amount);
        if (in_array($type, ['add', 'increase', 'plus'], true)) {
            return $amount;
        }

        if (in_array($type, ['sub', 'subtract', 'reduce', 'minus'], true)) {
            return -$amount;
        }

        return null;
    }

    /**
     * 统一用户名展示口径，避免手机号误写入“用户名”字段
     */
    protected function resolveDisplayUsername(array $userData, int $userId): string
    {
        $nickname = trim((string) ($userData['nickname'] ?? ''));
        $phone = trim((string) ($userData['phone'] ?? ''));
        $username = trim((string) ($userData['username'] ?? ''));

        if ($username !== '' && !$this->isPhoneLikeUsername($username, $phone)) {
            return $username;
        }

        if ($nickname !== '' && !$this->isPhoneLikeUsername($nickname, $phone)) {
            return $nickname;
        }

        return '用户#' . $userId;
    }

    protected function normalizeDetailUserData(array $userData, string $displayUsername, int $userId): array
    {
        $phone = trim((string) ($userData['phone'] ?? ''));
        $nickname = trim((string) ($userData['nickname'] ?? ''));
        if ($nickname === '' || $this->isPhoneLikeUsername($nickname, $phone)) {
            $nickname = $displayUsername !== '' ? $displayUsername : ('用户#' . $userId);
        }

        $userData['username'] = $displayUsername !== '' ? $displayUsername : ('用户#' . $userId);
        $userData['nickname'] = $nickname;
        $userData['email'] = trim((string) ($userData['email'] ?? ''));

        return $userData;
    }

    protected function mergeUserProfileData(array $userData, int $userId): array
    {
        $profileData = $this->loadProfileData($userId);
        if (empty($profileData)) {
            return $userData;
        }

        if (($userData['email'] ?? '') === '' && ($profileData['email'] ?? '') !== '') {
            $userData['email'] = (string) $profileData['email'];
        }

        if (($userData['nickname'] ?? '') === '' && ($profileData['real_name'] ?? '') !== '') {
            $userData['nickname'] = (string) $profileData['real_name'];
        }

        return $userData;
    }

    protected function loadProfileData(int $userId): array
    {
        if (!SchemaInspector::tableExists('tc_user_profile')) {
            return [];
        }

        $profile = Db::table('tc_user_profile')->where('user_id', $userId)->find();
        return is_array($profile) ? $profile : [];
    }

    protected function sanitizeEditableProfileData(array $data): array
    {
        $result = [];

        if (array_key_exists('nickname', $data)) {
            $nickname = $this->sanitizeNickname((string) $data['nickname']);
            if ($nickname === '') {
                throw new \InvalidArgumentException('昵称不能为空');
            }
            $result['nickname'] = $nickname;
        }

        if (array_key_exists('avatar', $data)) {
            $result['avatar'] = $this->sanitizeAvatarUrl((string) $data['avatar']);
        }

        if (array_key_exists('gender', $data)) {
            $gender = (int) $data['gender'];
            if (!in_array($gender, [0, 1, 2], true)) {
                throw new \InvalidArgumentException('性别值无效');
            }
            $result['gender'] = $gender;
        }

        if (array_key_exists('phone', $data)) {
            $phone = trim((string) $data['phone']);
            if ($phone !== '' && preg_match('/^1[3-9]\d{9}$/', $phone) !== 1) {
                throw new \InvalidArgumentException('手机号格式不正确');
            }
            $result['phone'] = $phone;
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) $data['email']);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException('邮箱格式不正确');
            }
            $result['email'] = mb_substr($email, 0, 100);
        }

        return $result;
    }

    protected function sanitizeNickname(string $nickname): string
    {
        $nickname = strip_tags($nickname);
        $nickname = htmlspecialchars(trim($nickname), ENT_QUOTES, 'UTF-8');
        return mb_substr($nickname, 0, 50);
    }

    protected function sanitizeAvatarUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array(strtolower((string) $parsed['scheme']), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('头像地址必须为 http/https 链接');
        }

        return $url;
    }

    protected function isPhoneLikeUsername(string $username, string $phone = ''): bool
    {
        $normalizedUsername = trim($username);
        if ($normalizedUsername === '') {
            return false;
        }

        $normalizedPhone = trim($phone);
        if ($normalizedPhone !== '' && $normalizedUsername === $normalizedPhone) {
            return true;
        }

        return preg_match('/^1[3-9]\d{9}$/', $normalizedUsername) === 1;
    }

    /**
     * 获取用户行为日志
     */
    public function behavior()
    {
        if (!$this->hasAdminPermission('user_view')) {
            return $this->error('无权限查看用户行为日志', 403);
        }

        $id = (int) ($this->request->get('id') ?? 0);
        if ($id <= 0) {
            return $this->error('用户ID无效', 400);
        }

        try {
            $list = [];
            // 从操作日志中查找该用户相关记录
            if (SchemaInspector::tableExists('tc_admin_log')) {
                $list = Db::table('tc_admin_log')
                    ->where('target_id', $id)
                    ->where('target_type', 'user')
                    ->order('id', 'desc')
                    ->limit(50)
                    ->select()
                    ->toArray();
            }

            return $this->success([
                'list' => $list,
                'total' => count($list),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_user_behavior', $e, '获取用户行为日志失败', [
                'user_id' => $id,
            ]);
        }
    }

    /**
     * 调整单个用户积分（通过用户ID路由调用）
     */
    public function adjustPoints(int $id)
    {
        if (!$this->hasAdminPermission('points_adjust')) {
            return $this->error('无权限调整用户积分', 403);
        }

        $data = $this->request->post();
        $normalizedPoints = null;

        try {
            $normalizedPoints = $this->resolvePointsDelta($data);
            if ($normalizedPoints === null) {
                return $this->error('积分调整参数无效，请传入 points 或 type/amount', 400);
            }
            if ($normalizedPoints === 0) {
                return $this->error('积分调整值不能为 0', 400);
            }

            $reason = mb_substr(trim((string) ($data['reason'] ?? '管理员调整')) ?: '管理员调整', 0, 255);

            $user = UserModel::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $oldPoints = (int) ($user->points ?? 0);
            $newPoints = $oldPoints + $normalizedPoints;
            if ($newPoints < 0) {
                return $this->error('积分不足', 400);
            }

            $user->points = $newPoints;
            $user->save();

            PointsRecord::create([
                'user_id' => $id,
                'type' => 'admin_adjust',
                'amount' => $normalizedPoints,
                'balance_after' => $newPoints,
                'remark' => $reason,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logOperation('adjust_points', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => sprintf('调整积分 %+d，原因：%s', $normalizedPoints, $reason),
                'before_data' => ['points' => $oldPoints],
                'after_data' => ['points' => $newPoints],
            ]);

            return $this->success([
                'before_points' => $oldPoints,
                'after_points' => $newPoints,
                'delta' => $normalizedPoints,
                'current_points' => $newPoints,
            ], '积分调整成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_adjust_points_by_id', $e, '调整积分失败，请稍后重试', [
                'user_id' => $id,
                'points' => $normalizedPoints,
            ]);
        }
    }

    /**
     * 返回首个存在的数据表名
     */
    protected function resolveFirstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (SchemaInspector::tableExists((string) $table)) {
                return (string) $table;
            }
        }

        return null;
    }
}
