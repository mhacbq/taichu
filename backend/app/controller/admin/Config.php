<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\ConfigService;
use think\response\Json;

/**
 * 系统配置管理（后台）
 */
class Config extends BaseController
{
    /**
     * 获取所有配置（按分类）
     */
    public function index(): Json
    {
        $category = $this->request->get('category', '');
        
        if ($category) {
            $configs = ConfigService::getCategory($category);
        } else {
            $configs = ConfigService::getAll();
        }
        
        return $this->success($configs, 'success');
    }
    
    /**
     * 获取功能开关状态
     */
    public function features(): Json
    {
        $features = ConfigService::getFeatureSwitches();
        
        return $this->success($features, 'success');
    }
    
    /**
     * 更新单个配置
     */
    public function update(): Json
    {
        $key = $this->request->post('key', '');
        $value = $this->request->post('value');
        
        if (empty($key)) {
            return $this->error('配置键不能为空', 400);
        }
        
        $result = ConfigService::set($key, $value);
        
        if ($result) {
            return $this->success(null, '配置更新成功');
        }
        
        return $this->error('配置更新失败', 500);
    }
    
    /**
     * 批量更新配置
     */
    public function updateBatch(): Json
    {
        $configs = $this->request->post('configs', []);
        
        if (empty($configs) || !is_array($configs)) {
            return $this->error('配置数据不能为空', 400);
        }
        
        $result = ConfigService::setBatch($configs);
        
        return $this->success(['success' => $result], '批量更新成功');
    }
    
    /**
     * 更新功能开关
     */
    public function updateFeature(): Json
    {
        $feature = $this->request->post('feature', '');
        $enabled = $this->request->post('enabled', false);
        
        if (empty($feature)) {
            return $this->error('功能标识不能为空', 400);
        }
        
        $key = "feature_{$feature}_enabled";
        $result = ConfigService::set($key, $enabled ? '1' : '0');
        
        if ($result) {
            return $this->success([
                'feature' => $feature,
                'enabled' => $enabled,
            ], '功能开关更新成功');
        }
        
        return $this->error('更新失败', 500);
    }
    
    /**
     * 批量更新功能开关
     */
    public function updateFeatures(): Json
    {
        $features = $this->request->post('features', []);
        
        if (empty($features)) {
            return $this->error('功能配置不能为空', 400);
        }
        
        $configs = [];
        foreach ($features as $feature => $enabled) {
            $configs["feature_{$feature}_enabled"] = $enabled ? '1' : '0';
        }
        
        $result = ConfigService::setBatch($configs);
        
        return $this->success(['success' => $result], '功能开关批量更新成功');
    }
    
    /**
     * 获取VIP配置
     */
    public function vip(): Json
    {
        $config = ConfigService::getCategory('vip');
        
        return $this->success($config, 'success');
    }
    
    /**
     * 更新VIP配置
     */
    public function updateVip(): Json
    {
        $data = $this->request->post();
        
        $configs = [];
        $mapping = [
            'month_price' => 'vip_month_price',
            'quarter_price' => 'vip_quarter_price',
            'year_price' => 'vip_year_price',
            'daily_points_multiplier' => 'vip_daily_points_multiplier',
            'paipan_limit' => 'vip_paipan_limit',
            'unlock_basic_report' => 'vip_unlock_basic_report',
            'unlock_hehun' => 'vip_unlock_hehun',
            'unlock_qiming' => 'vip_unlock_qiming',
        ];
        
        foreach ($mapping as $key => $configKey) {
            if (isset($data[$key])) {
                $configs[$configKey] = $data[$key];
            }
        }
        
        if (empty($configs)) {
            return $this->error('没有要更新的配置', 400);
        }
        
        $result = ConfigService::setBatch($configs);
        
        return $this->success(['success' => $result], 'VIP配置更新成功');
    }
    
    /**
     * 获取积分配置
     */
    public function points(): Json
    {
        $tasks = ConfigService::getPointsTasks();
        $costs = ConfigService::getCategory('points_cost');
        
        return $this->success([
            'tasks' => $tasks,
            'costs' => $costs,
        ], 'success');
    }
    
    /**
     * 更新积分配置
     */
    public function updatePoints(): Json
    {
        $tasks = $this->request->post('tasks', []);
        $costs = $this->request->post('costs', []);
        
        $configs = [];
        
        // 任务积分
        $taskMapping = [
            'sign_daily' => 'points_sign_daily',
            'sign_continuous_7' => 'points_sign_continuous_7',
            'sign_continuous_30' => 'points_sign_continuous_30',
            'share_app' => 'points_share_app',
            'invite_friend' => 'points_invite_friend',
            'complete_profile' => 'points_complete_profile',
            'first_paipan' => 'points_first_paipan',
            'bind_wechat' => 'points_bind_wechat',
            'follow_mp' => 'points_follow_mp',
            'browse_article' => 'points_browse_article',
        ];
        
        foreach ($taskMapping as $key => $configKey) {
            if (isset($tasks[$key])) {
                $configs[$configKey] = $tasks[$key];
            }
        }
        
        // 消耗积分
        $costMapping = [
            'save_record' => 'points_cost_save_record',
            'share_poster' => 'points_cost_share_poster',
            'unlock_report' => 'points_cost_unlock_report',
            'yearly_fortune' => 'points_cost_yearly_fortune',
            'dayun_analysis' => 'points_cost_dayun_analysis',
            'dayun_chart' => 'points_cost_dayun_chart',
            'hehun' => 'points_cost_hehun',
            'qiming' => 'points_cost_qiming',
            'jiri' => 'points_cost_jiri',
        ];
        
        foreach ($costMapping as $key => $configKey) {
            if (isset($costs[$key])) {
                $configs[$configKey] = $costs[$key];
            }
        }
        
        if (empty($configs)) {
            return $this->error('没有要更新的配置', 400);
        }
        
        $result = ConfigService::setBatch($configs);
        
        return $this->success(['success' => $result], '积分配置更新成功');
    }
    
    /**
     * 获取营销配置
     */
    public function marketing(): Json
    {
        $limitedOffer = ConfigService::getCategory('limited_offer');
        $packages = ConfigService::get('packages', []);
        $newUser = ConfigService::getCategory('new_user');
        $recharge = ConfigService::getCategory('recharge');
        
        return $this->success([
            'limited_offer' => $limitedOffer,
            'packages' => $packages,
            'new_user' => $newUser,
            'recharge' => $recharge,
        ], 'success');
    }
    
    /**
     * 更新营销配置
     */
    public function updateMarketing(): Json
    {
        $data = $this->request->post();
        $configs = [];
        
        // 限时优惠
        if (isset($data['limited_offer'])) {
            $lo = $data['limited_offer'];
            if (isset($lo['enabled'])) $configs['limited_offer_enabled'] = $lo['enabled'];
            if (isset($lo['discount'])) $configs['limited_offer_discount'] = $lo['discount'];
            if (isset($lo['start_time'])) $configs['limited_offer_start_time'] = $lo['start_time'];
            if (isset($lo['end_time'])) $configs['limited_offer_end_time'] = $lo['end_time'];
        }
        
        // 套餐
        if (isset($data['packages'])) {
            $configs['packages'] = is_array($data['packages']) ? json_encode($data['packages']) : $data['packages'];
        }
        
        // 新用户优惠
        if (isset($data['new_user'])) {
            $nu = $data['new_user'];
            if (isset($nu['enabled'])) $configs['new_user_offer_enabled'] = $nu['enabled'];
            if (isset($nu['discount'])) $configs['new_user_discount'] = $nu['discount'];
            if (isset($nu['valid_hours'])) $configs['new_user_valid_hours'] = $nu['valid_hours'];
        }
        
        // 充值
        if (isset($data['recharge'])) {
            $rc = $data['recharge'];
            if (isset($rc['enabled'])) $configs['recharge_enabled'] = $rc['enabled'];
            if (isset($rc['ratio'])) $configs['recharge_ratio'] = $rc['ratio'];
            if (isset($rc['options'])) {
                $configs['recharge_options'] = is_array($rc['options']) ? json_encode($rc['options']) : $rc['options'];
            }
        }
        
        if (empty($configs)) {
            return $this->error('没有要更新的配置', 400);
        }
        
        $result = ConfigService::setBatch($configs);
        
        return $this->success(['success' => $result], '营销配置更新成功');
    }
    
    /**
     * 刷新配置缓存
     */
    public function refreshCache(): Json
    {
        ConfigService::refreshCache();
        
        return $this->success(null, '配置缓存已刷新');
    }
}