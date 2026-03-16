<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AiPrompt as AiPromptModel;
use think\Request;

/**
 * AI提示词管理控制器
 */
class AiPrompt extends BaseController
{
    /**
     * 获取提示词列表
     */
    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        $type = $request->param('type');
        $isEnabled = $request->param('is_enabled');
        
        $query = AiPromptModel::order('sort_order', 'asc')->order('created_at', 'desc');
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($isEnabled !== null && $isEnabled !== '') {
            $query->where('is_enabled', $isEnabled);
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'types' => AiPromptModel::TYPES,
        ], '获取成功');
    }
    
    /**
     * 获取提示词详情
     */
    public function getDetail(Request $request)
    {
        $id = $request->param('id');
        
        $prompt = AiPromptModel::find($id);
        if (!$prompt) {
            return $this->error('提示词不存在', 404);
        }
        
        return $this->success($prompt, '获取成功');
    }
    
    /**
     * 保存提示词
     */
    public function save(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'name', 'key', 'type', 'system_prompt', 'user_prompt_template',
            'variables', 'description', 'model_params', 'sort_order', 'is_enabled'
        ]);
        
        // 验证
        if (empty($data['name']) || empty($data['key']) || empty($data['type'])) {
            return $this->error('名称、标识和类型不能为空', 400);
        }
        
        // 检查key是否重复
        $exists = AiPromptModel::where('key', $data['key']);
        if ($id) {
            $exists->where('id', '<>', $id);
        }
        if ($exists->find()) {
            return $this->error('提示词标识已存在', 400);
        }
        
        // 处理变量
        if (!empty($data['variables']) && is_string($data['variables'])) {
            $data['variables'] = json_decode($data['variables'], true);
        }
        
        // 处理模型参数
        if (!empty($data['model_params']) && is_string($data['model_params'])) {
            $data['model_params'] = json_decode($data['model_params'], true);
        }
        
        $data['updated_by'] = $request->userId ?? 0;
        
        try {
            if ($id) {
                $prompt = AiPromptModel::find($id);
                if (!$prompt) {
                    return $this->error('提示词不存在', 404);
                }
                $prompt->save($data);
            } else {
                $data['created_by'] = $request->userId ?? 0;
                $prompt = AiPromptModel::create($data);
            }
            
            return $this->success($prompt, '保存成功');
        } catch (\Exception $e) {
            Log::error('保存AI提示词失败: ' . $e->getMessage());
            return $this->error('保存失败，请稍后重试', 500);
        }
    }
    
    /**
     * 删除提示词
     */
    public function delete(Request $request)
    {
        $id = $request->param('id');
        
        $prompt = AiPromptModel::find($id);
        if (!$prompt) {
            return $this->error('提示词不存在', 404);
        }
        
        // 如果是默认提示词，不允许删除
        if ($prompt->is_default) {
            return $this->error('默认提示词不能删除，请先设置其他提示词为默认', 400);
        }
        
        $prompt->delete();
        
        return $this->success(null, '删除成功');
    }
    
    /**
     * 设置默认提示词
     */
    public function setDefault(Request $request)
    {
        $id = $request->param('id');
        
        $prompt = AiPromptModel::find($id);
        if (!$prompt) {
            return $this->error('提示词不存在', 404);
        }
        
        AiPromptModel::setDefault($id, $prompt->type);
        
        return $this->success(null, '设置成功');
    }
    
    /**
     * 预览提示词效果
     */
    public function preview(Request $request)
    {
        $id = $request->param('id');
        $testVariables = $request->param('variables', []);
        
        $prompt = AiPromptModel::find($id);
        if (!$prompt) {
            return $this->error('提示词不存在', 404);
        }
        
        // 使用测试变量或默认值
        $variables = array_merge($this->getDefaultVariables($prompt->type), $testVariables);
        
        $rendered = $prompt->renderUserPrompt($variables);
        
        return $this->success([
            'system_prompt' => $prompt->system_prompt,
            'user_prompt_template' => $prompt->user_prompt_template,
            'rendered_prompt' => $rendered,
            'variables' => $variables,
        ], '获取成功');
    }
    
    /**
     * 获取类型列表
     */
    public function getTypes()
    {
        return $this->success(AiPromptModel::TYPES, '获取成功');
    }
    
    /**
     * 获取默认变量
     */
    private function getDefaultVariables(string $type): array
    {
        switch ($type) {
            case 'bazi':
                return [
                    'year_gan' => '甲',
                    'year_zhi' => '子',
                    'month_gan' => '乙',
                    'month_zhi' => '丑',
                    'day_gan' => '丙',
                    'day_zhi' => '寅',
                    'hour_gan' => '丁',
                    'hour_zhi' => '卯',
                    'day_master' => '丙火',
                    'year_nayin' => '海中金',
                    'month_nayin' => '海中金',
                    'day_nayin' => '炉中火',
                    'hour_nayin' => '炉中火',
                    'year_shishen' => '偏印',
                    'month_shishen' => '正印',
                    'hour_shishen' => '劫财',
                ];
            case 'tarot':
                return [
                    'card_name' => '愚者',
                    'card_meaning' => '新的开始，冒险精神',
                    'position' => '过去',
                    'question' => '我的事业发展如何？',
                ];
            default:
                return [];
        }
    }
    
    /**
     * 复制提示词
     */
    public function duplicate(Request $request)
    {
        $id = $request->param('id');
        
        $prompt = AiPromptModel::find($id);
        if (!$prompt) {
            return $this->error('提示词不存在', 404);
        }
        
        $data = $prompt->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $data['name'] = $data['name'] . ' - 副本';
        $data['key'] = $data['key'] . '_copy_' . time();
        $data['is_default'] = 0;
        $data['usage_count'] = 0;
        $data['created_by'] = $request->userId ?? 0;
        $data['updated_by'] = $request->userId ?? 0;
        
        try {
            $newPrompt = AiPromptModel::create($data);
            return $this->success($newPrompt, '复制成功');
        } catch (\Exception $e) {
            Log::error('复制AI提示词失败: ' . $e->getMessage());
            return $this->error('复制失败，请稍后重试', 500);
        }
    }
}
