<?php

use think\facade\Route;

/**
 * 内容管理路由
 * 支持可视化编辑的API
 */

Route::group('api/content', function () {
    // 页面管理
    Route::get('pages', 'content/getPages');
    Route::get('page/:pageId', 'content/getPage');
    Route::post('page/:pageId', 'content/savePage');
    Route::delete('page/:pageId', 'content/deletePage');
    Route::post('page/import', 'content/importPage');
    Route::get('page/:pageId/export', 'content/exportPage');
    
    // 草稿管理
    Route::post('page/:pageId/autosave', 'content/autoSave');
    Route::get('page/:pageId/draft', 'content/getDraft');
    
    // 版本管理
    Route::get('page/:pageId/versions', 'content/getVersions');
    Route::post('version/:versionId/restore', 'content/restoreVersion');
    Route::get('version/:versionId/preview', 'content/previewVersion');
    
    // 块配置
    Route::get('block-config/:type', 'content/getBlockConfig');
})->middleware([
    \app\middleware\AdminAuth::class
]);