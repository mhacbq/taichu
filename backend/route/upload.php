<?php

use think\facade\Route;

/**
 * 文件上传路由
 */

Route::group('api/upload', function () {
    // 图片上传
    Route::post('image', 'upload/image');
    Route::post('images', 'upload/images');
    
    // 文件上传
    Route::post('file', 'upload/file');
    
    // 图片库
    Route::get('gallery', 'upload/gallery');
    
    // 删除文件
    Route::delete('file/:id', 'upload/delete');
})->middleware([
    \app\middleware\AdminAuth::class
]);