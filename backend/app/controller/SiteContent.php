<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\SiteContent as SiteContentModel;
use app\model\Testimonial;
use app\model\Faq;
use app\model\TarotCard;
use app\model\TarotSpread;
use app\model\QuestionTemplate;
use app\model\DailyFortuneTemplate;
use think\Request;
use think\facade\Db;
use think\facade\Log;

/**
 * 网站内容管理控制器
 */
class SiteContent extends BaseController
{
    /**
     * 获取首页内容
     */
    public function getHomeContent()
    {
        $content = SiteContentModel::getPageContent('home');
        
        // 获取启用的用户评价
        $testimonials = Testimonial::getEnabledList(6);
        
        return $this->success([
            'content' => $content,
            'testimonials' => $testimonials,
        ], '获取成功');
    }
    
    /**
     * 获取单页内容
     */
    public function getPageContent(Request $request)
    {
        $page = $request->param('page', 'home');
        
        $content = SiteContentModel::getPageContent($page);
        
        return $this->success($content, '获取成功');
    }
    
    /**
     * 批量更新页面内容（后台）
     */
    public function updatePageContent(Request $request)
    {
        $operatorId = $this->getOperatorId();
        $page = $request->param('page', 'home');
        $contents = $request->param('contents', []);
        
        // 验证page参数格式，防止路径遍历和非法字符
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            return $this->error('页面标识格式无效，只能包含字母、数字、下划线和横线', 400);
        }
        
        if (empty($contents)) {
            return $this->error('内容不能为空', 400);
        }
        
        try {
            SiteContentModel::batchUpdate($contents, $page, $operatorId);
            
            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            Log::error('批量更新站点内容失败', [
                'page' => $page,
                'operator_id' => $operatorId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('更新失败，请稍后重试', 500);
        }
    }
    
    /**
     * 获取单条内容（后台）
     */
    public function getContentList(Request $request)
    {
        $page = $request->param('page', 'home');
        $key = $request->param('key');
        $pagination = $this->getPaginationParams('current', 'pageSize', 20, 100);
        
        // 验证page参数格式，防止路径遍历和非法字符
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            return $this->error('页面标识格式无效，只能包含字母、数字、下划线和横线', 400);
        }
        
        $query = SiteContentModel::where('page', $page);
        
        if ($key) {
            // 使用参数绑定防止SQL注入
            $key = preg_replace('/[%_\\\\]/', '', $key);
            $query->whereLike('key', '%' . $key . '%');
        }

        $total = $query->count();
        $list = $query->order('sort_order', 'asc')
            ->order('created_at', 'desc')
            ->page($pagination['page'], $pagination['pageSize'])
            ->select()
            ->toArray();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $pagination['page'],
            'pageSize' => $pagination['pageSize'],
            'page_key' => $page,
            'keyword' => $key ?: '',
        ], '获取成功');
    }

    
    /**
     * 更新单条内容（后台）
     */
    public function updateContent(Request $request)
    {
        $id = $request->param('id');
        $operatorId = $this->getOperatorId();
        $data = $request->only(['key', 'page', 'value', 'description', 'is_enabled', 'sort_order']);
        $data['updated_by'] = $operatorId;
        
        try {
            if ($id) {
                $content = SiteContentModel::find($id);
                if (!$content) {
                    return $this->error('内容不存在', 404);
                }
                $content->save($data);
            } else {
                $data['created_by'] = $operatorId;
                SiteContentModel::create($data);
            }
            
            return $this->success(null, '保存成功');
        } catch (\Exception $e) {
            Log::error('保存站点内容失败', [
                'content_id' => $id,
                'operator_id' => $operatorId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('保存失败，请稍后重试', 500);
        }
    }

    protected function saveManagedRecord(
        ?int $id,
        array $data,
        callable $finder,
        callable $creator,
        string $notFoundMessage
    ): ?\think\response\Json {
        if ($id) {
            $item = $finder($id);
            if (!$item) {
                return $this->error($notFoundMessage, 404);
            }
            $item->save($data);
            return null;
        }

        $creator($data);

        return null;
    }
    
    /**
     * 删除内容（后台）
     */

    public function deleteContent(Request $request)
    {
        $id = $request->param('id');
        
        $content = SiteContentModel::find($id);
        if (!$content) {
            return $this->error('内容不存在', 404);
        }
        
        $content->delete();
        
        return $this->success(null, '删除成功');
    }
    
    // ==================== 用户评价管理 ====================
    
    /**
     * 获取评价列表（后台）
     */
    public function getTestimonialList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        $isEnabled = $request->param('is_enabled');
        
        $query = Testimonial::order('sort_order', 'asc');
        
        if ($isEnabled !== null && $isEnabled !== '') {
            $query->where('is_enabled', $isEnabled);
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }
    
    /**
     * 获取前台评价列表
     */
    public function getTestimonials()
    {
        $list = Testimonial::getEnabledList(10);
        
        return $this->success($list, '获取成功');
    }
    
    /**
     * 保存评价（后台）
     */
    public function saveTestimonial(Request $request)
    {
        $id = $request->param('id/d', 0) ?: null;
        $data = $request->only([
            'name', 'avatar', 'content', 'service_type',
            'sort_order', 'is_enabled'
        ]);
        
        try {
            $response = $this->saveManagedRecord(
                $id,
                $data,
                static fn (int $recordId) => Testimonial::find($recordId),
                static fn (array $payload) => Testimonial::create($payload),
                '评价不存在'
            );

            return $response ?? $this->success(null, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('site_content.save_testimonial', $e, '保存评价失败，请稍后重试', [
                'testimonial_id' => $id,
                'service_type' => $data['service_type'] ?? '',
                'field_keys' => array_values(array_keys($data)),
            ]);
        }
    }

    
    /**
     * 删除评价（后台）
     */
    public function deleteTestimonial(Request $request)
    {
        $id = $request->param('id');
        
        $item = Testimonial::find($id);
        if (!$item) {
            return $this->error('评价不存在', 404);
        }
        
        $item->delete();
        
        return $this->success(null, '删除成功');
    }
    
    // ==================== FAQ管理 ====================
    
    /**
     * 获取FAQ列表（后台）
     */
    public function getFaqList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        $category = $request->param('category');
        $isEnabled = $request->param('is_enabled');
        
        $query = Faq::order('sort_order', 'asc');
        
        if ($category) {
            $query->where('category', $category);
        }
        if ($isEnabled !== null && $isEnabled !== '') {
            $query->where('is_enabled', $isEnabled);
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }
    
    /**
     * 获取前台FAQ列表
     */
    public function getFaqs(Request $request)
    {
        $category = $request->param('category');
        $list = Faq::getEnabledList($category);
        
        return $this->success($list, '获取成功');
    }
    
    /**
     * 保存FAQ（后台）
     */
    public function saveFaq(Request $request)
    {
        $id = $request->param('id/d', 0) ?: null;
        $data = $request->only([
            'category', 'question', 'answer',
            'sort_order', 'is_enabled'
        ]);
        
        try {
            $response = $this->saveManagedRecord(
                $id,
                $data,
                static fn (int $recordId) => Faq::find($recordId),
                static fn (array $payload) => Faq::create($payload),
                'FAQ不存在'
            );

            return $response ?? $this->success(null, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('site_content.save_faq', $e, '保存FAQ失败，请稍后重试', [
                'faq_id' => $id,
                'category' => $data['category'] ?? '',
                'question_length' => mb_strlen((string) ($data['question'] ?? '')),
            ]);
        }
    }

    
    /**
     * 删除FAQ（后台）
     */
    public function deleteFaq(Request $request)
    {
        $id = $request->param('id');
        
        $item = Faq::find($id);
        if (!$item) {
            return $this->error('FAQ不存在', 404);
        }
        
        $item->delete();
        
        return $this->success(null, '删除成功');
    }
    
    // ==================== 塔罗牌管理 ====================
    
    /**
     * 获取塔罗牌列表（后台）
     */
    public function getTarotCardList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        $isMajor = $request->param('is_major');
        
        $query = TarotCard::order('is_major', 'desc')->order('id', 'asc');
        
        if ($isMajor !== null && $isMajor !== '') {
            $query->where('is_major', $isMajor);
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }
    
    /**
     * 保存塔罗牌（后台）
     */
    public function saveTarotCard(Request $request)
    {
        $id = $request->param('id/d', 0) ?: null;
        $data = $request->only([
            'name', 'name_en', 'image_url', 'is_major',
            'upright_meaning', 'reversed_meaning',
            'keywords', 'description', 'is_enabled'
        ]);
        
        try {
            $response = $this->saveManagedRecord(
                $id,
                $data,
                static fn (int $recordId) => TarotCard::find($recordId),
                static fn (array $payload) => TarotCard::create($payload),
                '塔罗牌不存在'
            );

            return $response ?? $this->success(null, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('site_content.save_tarot_card', $e, '保存塔罗牌失败，请稍后重试', [
                'card_id' => $id,
                'card_name' => $data['name'] ?? '',
                'is_major' => (int) ($data['is_major'] ?? 0),
                'has_image' => !empty($data['image_url']),
            ]);
        }
    }

    
    // ==================== 塔罗牌阵管理 ====================
    
    /**
     * 获取牌阵列表（后台）
     */
    public function getSpreadList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        
        $total = TarotSpread::count();
        $list = TarotSpread::order('sort_order', 'asc')
            ->page($page, $limit)
            ->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }
    
    /**
     * 获取前台牌阵列表
     */
    public function getSpreads()
    {
        $list = TarotSpread::getEnabledList();
        
        return $this->success($list, '获取成功');
    }
    
    /**
     * 保存牌阵（后台）
     */
    public function saveSpread(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'name', 'type', 'description', 'card_count',
            'positions', 'is_free', 'points_required',
            'sort_order', 'is_enabled'
        ]);
        
        try {
            if ($id) {
                $item = TarotSpread::find($id);
                if (!$item) {
                    return $this->error('牌阵不存在', 404);
                }
                $item->save($data);
            } else {
                TarotSpread::create($data);
            }
            
            return $this->success(null, '保存成功');
        } catch (\Exception $e) {
            Log::error('保存牌阵失败', [
                'spread_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('保存失败，请稍后重试', 500);
        }
    }
    
    // ==================== 问题模板管理 ====================
    
    /**
     * 获取问题模板列表（后台）
     */
    public function getQuestionList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        $category = $request->param('category');
        
        $query = QuestionTemplate::order('sort_order', 'asc');
        
        if ($category) {
            $query->where('category', $category);
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }
    
    /**
     * 获取前台问题模板
     */
    public function getQuestions(Request $request)
    {
        $category = $request->param('category');
        $list = QuestionTemplate::getEnabledList($category);
        
        return $this->success($list, '获取成功');
    }
    
    /**
     * 保存问题模板（后台）
     */
    public function saveQuestion(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'category', 'question', 'sort_order', 'is_enabled'
        ]);
        
        try {
            if ($id) {
                $item = QuestionTemplate::find($id);
                if (!$item) {
                    return $this->error('模板不存在', 404);
                }
                $item->save($data);
            } else {
                QuestionTemplate::create($data);
            }
            
            return $this->success(null, '保存成功');
        } catch (\Throwable $e) {
            Log::error('保存问题模板失败', [
                'template_id' => $id ? (int) $id : null,
                'category' => $data['category'] ?? '',
                'operator_id' => $this->getOperatorId(),
                'error' => $e->getMessage(),
            ]);
            return $this->error('保存问题模板失败，请稍后重试', 500);
        }

    }
    
    // ==================== 每日运势模板管理 ====================
    
    /**
     * 获取运势模板列表（后台）
     */
    public function getFortuneTemplateList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        $type = $request->param('type');
        
        $query = DailyFortuneTemplate::order('type', 'asc');
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }
    
    /**
     * 保存运势模板（后台）
     */
    public function saveFortuneTemplate(Request $request)
    {
        $id = $request->param('id/d', 0) ?: null;
        $data = $request->only([
            'type', 'level', 'title', 'content', 'is_enabled'
        ]);
        
        try {
            $response = $this->saveManagedRecord(
                $id,
                $data,
                static fn (int $recordId) => DailyFortuneTemplate::find($recordId),
                static fn (array $payload) => DailyFortuneTemplate::create($payload),
                '模板不存在'
            );

            return $response ?? $this->success(null, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('site_content.save_fortune_template', $e, '保存运势模板失败，请稍后重试', [
                'template_id' => $id,
                'type' => $data['type'] ?? '',
                'level' => $data['level'] ?? '',
                'title_length' => mb_strlen((string) ($data['title'] ?? '')),
            ]);
        }
    }

    
    /**
     * 获取所有分类和类型选项
     */
    public function getEnums()
    {
        return $this->success([
            'faq_categories' => Faq::CATEGORIES,
            'testimonial_service_types' => Testimonial::SERVICE_TYPES,
            'spread_types' => TarotSpread::SPREAD_TYPES,
            'question_categories' => QuestionTemplate::CATEGORIES,
            'fortune_types' => DailyFortuneTemplate::TYPES,
            'fortune_levels' => DailyFortuneTemplate::LEVEL_NAMES,
        ]);
    }
}