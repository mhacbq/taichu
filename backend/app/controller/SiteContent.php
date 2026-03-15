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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'content' => $content,
                'testimonials' => $testimonials,
            ],
        ]);
    }
    
    /**
     * 获取单页内容
     */
    public function getPageContent(Request $request)
    {
        $page = $request->param('page', 'home');
        
        $content = SiteContentModel::getPageContent($page);
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $content,
        ]);
    }
    
    /**
     * 批量更新页面内容（后台）
     */
    public function updatePageContent(Request $request)
    {
        $userId = $request->userId ?? 0;
        $page = $request->param('page', 'home');
        $contents = $request->param('contents', []);
        
        if (empty($contents)) {
            return json(['code' => 400, 'message' => '内容不能为空']);
        }
        
        try {
            SiteContentModel::batchUpdate($contents, $page, $userId);
            
            return json([
                'code' => 0,
                'message' => '更新成功',
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取单条内容（后台）
     */
    public function getContentList(Request $request)
    {
        $page = $request->param('page', 'home');
        $key = $request->param('key');
        
        $query = SiteContentModel::where('page', $page);
        
        if ($key) {
            $query->where('key', 'like', "%{$key}%");
        }
        
        $list = $query->order('sort_order', 'asc')
            ->order('created_at', 'desc')
            ->select();
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ]);
    }
    
    /**
     * 更新单条内容（后台）
     */
    public function updateContent(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only(['key', 'page', 'value', 'description', 'is_enabled', 'sort_order']);
        $data['updated_by'] = $request->userId ?? 0;
        
        try {
            if ($id) {
                $content = SiteContentModel::find($id);
                if (!$content) {
                    return json(['code' => 404, 'message' => '内容不存在']);
                }
                $content->save($data);
            } else {
                $data['created_by'] = $request->userId ?? 0;
                SiteContentModel::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除内容（后台）
     */
    public function deleteContent(Request $request)
    {
        $id = $request->param('id');
        
        $content = SiteContentModel::find($id);
        if (!$content) {
            return json(['code' => 404, 'message' => '内容不存在']);
        }
        
        $content->delete();
        
        return json(['code' => 0, 'message' => '删除成功']);
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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * 获取前台评价列表
     */
    public function getTestimonials()
    {
        $list = Testimonial::getEnabledList(10);
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ]);
    }
    
    /**
     * 保存评价（后台）
     */
    public function saveTestimonial(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'name', 'avatar', 'content', 'service_type',
            'sort_order', 'is_enabled'
        ]);
        
        try {
            if ($id) {
                $item = Testimonial::find($id);
                if (!$item) {
                    return json(['code' => 404, 'message' => '评价不存在']);
                }
                $item->save($data);
            } else {
                Testimonial::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
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
            return json(['code' => 404, 'message' => '评价不存在']);
        }
        
        $item->delete();
        
        return json(['code' => 0, 'message' => '删除成功']);
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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * 获取前台FAQ列表
     */
    public function getFaqs(Request $request)
    {
        $category = $request->param('category');
        $list = Faq::getEnabledList($category);
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ]);
    }
    
    /**
     * 保存FAQ（后台）
     */
    public function saveFaq(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'category', 'question', 'answer',
            'sort_order', 'is_enabled'
        ]);
        
        try {
            if ($id) {
                $item = Faq::find($id);
                if (!$item) {
                    return json(['code' => 404, 'message' => 'FAQ不存在']);
                }
                $item->save($data);
            } else {
                Faq::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
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
            return json(['code' => 404, 'message' => 'FAQ不存在']);
        }
        
        $item->delete();
        
        return json(['code' => 0, 'message' => '删除成功']);
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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * 保存塔罗牌（后台）
     */
    public function saveTarotCard(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'name', 'name_en', 'image_url', 'is_major',
            'upright_meaning', 'reversed_meaning',
            'keywords', 'description', 'is_enabled'
        ]);
        
        try {
            if ($id) {
                $item = TarotCard::find($id);
                if (!$item) {
                    return json(['code' => 404, 'message' => '塔罗牌不存在']);
                }
                $item->save($data);
            } else {
                TarotCard::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * 获取前台牌阵列表
     */
    public function getSpreads()
    {
        $list = TarotSpread::getEnabledList();
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ]);
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
                    return json(['code' => 404, 'message' => '牌阵不存在']);
                }
                $item->save($data);
            } else {
                TarotSpread::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * 获取前台问题模板
     */
    public function getQuestions(Request $request)
    {
        $category = $request->param('category');
        $list = QuestionTemplate::getEnabledList($category);
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $list,
        ]);
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
                    return json(['code' => 404, 'message' => '模板不存在']);
                }
                $item->save($data);
            } else {
                QuestionTemplate::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
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
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
            ],
        ]);
    }
    
    /**
     * 保存运势模板（后台）
     */
    public function saveFortuneTemplate(Request $request)
    {
        $id = $request->param('id');
        $data = $request->only([
            'type', 'level', 'title', 'content', 'is_enabled'
        ]);
        
        try {
            if ($id) {
                $item = DailyFortuneTemplate::find($id);
                if (!$item) {
                    return json(['code' => 404, 'message' => '模板不存在']);
                }
                $item->save($data);
            } else {
                DailyFortuneTemplate::create($data);
            }
            
            return json(['code' => 0, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取所有分类和类型选项
     */
    public function getEnums()
    {
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'faq_categories' => Faq::CATEGORIES,
                'testimonial_service_types' => Testimonial::SERVICE_TYPES,
                'spread_types' => TarotSpread::SPREAD_TYPES,
                'question_categories' => QuestionTemplate::CATEGORIES,
                'fortune_types' => DailyFortuneTemplate::TYPES,
                'fortune_levels' => DailyFortuneTemplate::LEVEL_NAMES,
            ],
        ]);
    }
}