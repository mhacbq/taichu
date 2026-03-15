<?php
use think\facade\Route;

// 前台公开接口
Route::group('api/site', function () {
    Route::get('home', 'SiteContent/getHomeContent');
    Route::get('page', 'SiteContent/getPageContent');
    Route::get('testimonials', 'SiteContent/getTestimonials');
    Route::get('faqs', 'SiteContent/getFaqs');
    Route::get('spreads', 'SiteContent/getSpreads');
    Route::get('questions', 'SiteContent/getQuestions');
    Route::get('enums', 'SiteContent/getEnums');
});

// 后台管理接口（需要认证）
Route::group('api/admin/site', function () {
    // 内容管理
    Route::get('content/list', 'SiteContent/getContentList');
    Route::post('content/save', 'SiteContent/updateContent');
    Route::post('content/batch', 'SiteContent/updatePageContent');
    Route::delete('content/:id', 'SiteContent/deleteContent');
    
    // 评价管理
    Route::get('testimonials', 'SiteContent/getTestimonialList');
    Route::post('testimonials', 'SiteContent/saveTestimonial');
    Route::delete('testimonials/:id', 'SiteContent/deleteTestimonial');
    
    // FAQ管理
    Route::get('faqs', 'SiteContent/getFaqList');
    Route::post('faqs', 'SiteContent/saveFaq');
    Route::delete('faqs/:id', 'SiteContent/deleteFaq');
    
    // 塔罗牌管理
    Route::get('tarot-cards', 'SiteContent/getTarotCardList');
    Route::post('tarot-cards', 'SiteContent/saveTarotCard');
    
    // 牌阵管理
    Route::get('spreads', 'SiteContent/getSpreadList');
    Route::post('spreads', 'SiteContent/saveSpread');
    
    // 问题模板管理
    Route::get('questions', 'SiteContent/getQuestionList');
    Route::post('questions', 'SiteContent/saveQuestion');
    
    // 运势模板管理
    Route::get('fortune-templates', 'SiteContent/getFortuneTemplateList');
    Route::post('fortune-templates', 'SiteContent/saveFortuneTemplate');
})->middleware(\app\middleware\Auth::class);