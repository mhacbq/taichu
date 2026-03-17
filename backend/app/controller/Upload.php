<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\middleware\AdminAuth;
use think\Request;
use think\facade\Log;

/**
 * 文件上传控制器
 * 处理图片和文件上传
 */
class Upload extends BaseController
{
    /**
     * 中间件配置
     */
    protected $middleware = [
        AdminAuth::class
    ];

    /**
     * 上传配置
     */
    protected $config = [
        // 图片上传配置
        'image' => [
            'max_size' => 5 * 1024 * 1024,  // 5MB
            'allowed_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'save_path' => '/uploads/images/',
        ],
        // 文件上传配置
        'file' => [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_ext' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
            'save_path' => '/uploads/files/',
        ],
    ];

    /**
     * 上传图片
     */
    public function image(Request $request)
    {
        try {
            $file = $request->file('file');
            
            if (!$file) {
                return $this->error('请选择要上传的图片', 400);
            }
            
            // 验证图片
            $validate = $this->validateImage($file);
            if ($validate !== true) {
                return $this->error($validate, 400);
            }
            
            // 生成保存路径
            $savePath = $this->getSavePath('image');
            // 使用getExtension()而非getOriginalExtension()，防止客户端伪造扩展名
            $fileName = $this->generateFileName($file->getExtension());
            
            // 保存文件
            $info = $file->move(public_path() . $savePath, $fileName);
            
            if (!$info) {
                return $this->error('上传失败：' . $file->getError(), 500);
            }
            
            // 生成URL
            $url = $this->getFileUrl($savePath . $fileName);
            
            // 保存到数据库（可选）
            $this->saveFileRecord([
                'type' => 'image',
                'original_name' => $file->getOriginalName(),
                'file_name' => $fileName,
                'file_path' => $savePath . $fileName,
                'file_url' => $url,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMime(),
                'extension' => $file->getExtension(),
                'uploaded_by' => $this->getOperatorId(),
            ]);

            
            return $this->success([
                'url' => $url,
                'name' => $file->getOriginalName(),
                'size' => $file->getSize(),
                'path' => $savePath . $fileName
            ], '上传成功');
            
        } catch (\Exception $e) {
            Log::error('上传图片失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('上传失败，请稍后重试', 500);
        }

    }

    /**
     * 批量上传图片
     */
    public function images(Request $request)
    {
        try {
            $files = $request->file('files');
            
            if (!$files) {
                return $this->error('请选择要上传的图片', 400);
            }
            
            // 确保是数组
            if (!is_array($files)) {
                $files = [$files];
            }
            
            $results = [];
            $errors = [];
            
            foreach ($files as $index => $file) {
                // 验证图片
                $validate = $this->validateImage($file);
                if ($validate !== true) {
                    $errors[] = "第{$index}张图片：{$validate}";
                    continue;
                }
                
                // 生成保存路径
                $savePath = $this->getSavePath('image');
                // 使用getExtension()而非getOriginalExtension()，防止客户端伪造扩展名
                $fileName = $this->generateFileName($file->getExtension());
                
                // 保存文件
                $info = $file->move(public_path() . $savePath, $fileName);
                
                if ($info) {
                    $url = $this->getFileUrl($savePath . $fileName);
                    
                    $this->saveFileRecord([
                        'type' => 'image',
                        'original_name' => $file->getOriginalName(),
                        'file_name' => $fileName,
                        'file_path' => $savePath . $fileName,
                        'file_url' => $url,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMime(),
                        'extension' => $file->getExtension(),
                        'uploaded_by' => $this->getOperatorId(),

                    ]);
                    
                    $results[] = [
                        'url' => $url,
                        'name' => $file->getOriginalName(),
                        'size' => $file->getSize()
                    ];
                } else {
                    $errors[] = "第{$index}张图片上传失败";
                }
            }
            
            return $this->success([
                'success' => $results,
                'errors' => $errors,
                'total' => count($files),
                'success_count' => count($results),
                'error_count' => count($errors)
            ], '上传完成');
            
        } catch (\Exception $e) {
            Log::error('批量上传图片失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('上传失败，请稍后重试', 500);
        }

    }

    /**
     * 上传文件
     */
    public function file(Request $request)
    {
        try {
            $file = $request->file('file');
            
            if (!$file) {
                return $this->error('请选择要上传的文件', 400);
            }
            
            // 验证文件
            $validate = $this->validateFile($file);
            if ($validate !== true) {
                return $this->error($validate, 400);
            }
            
            // 生成保存路径
            $savePath = $this->getSavePath('file');
            // 使用getExtension()而非getOriginalExtension()，防止客户端伪造扩展名
            $fileName = $this->generateFileName($file->getExtension());
            
            // 保存文件
            $info = $file->move(public_path() . $savePath, $fileName);
            
            if (!$info) {
                return $this->error('上传失败：' . $file->getError(), 500);
            }
            
            $url = $this->getFileUrl($savePath . $fileName);
            
            $this->saveFileRecord([
                'type' => 'file',
                'original_name' => $file->getOriginalName(),
                'file_name' => $fileName,
                'file_path' => $savePath . $fileName,
                'file_url' => $url,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMime(),
                'extension' => $file->getExtension(),
                'uploaded_by' => $this->getOperatorId(),
            ]);

            
            return $this->success([
                'url' => $url,
                'name' => $file->getOriginalName(),
                'size' => $file->getSize()
            ], '上传成功');
            
        } catch (\Exception $e) {
            Log::error('上传文件失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('上传失败，请稍后重试', 500);
        }

    }

    /**
     * 获取图片库列表
     */
    public function gallery(Request $request)
    {
        try {
            $pagination = $this->getPaginationParams('page', 'pageSize', 20, 100);
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $keyword = $request->get('keyword');

            
            $query = \app\model\UploadFile::where('type', 'image')
                ->order('created_at', 'desc');
            
            if ($keyword) {
                // 净化关键词，防止SQL注入
                $keyword = preg_replace('/[%_]/', '', $keyword);
                $query->whereLike('original_name', "%{$keyword}%");
            }
            
            $list = $query->page($page, $pageSize)->select();
            $total = $query->count();
            
            return $this->success([
                'list' => $list,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            Log::error('获取上传列表失败: ' . $e->getMessage());
            return $this->error('获取列表失败，请稍后重试', 500);
        }
    }

    /**
     * 删除上传的文件
     */
    public function delete(Request $request, $id)
    {
        try {
            $file = \app\model\UploadFile::find($id);
            
            if (!$file) {
                return $this->error('文件不存在', 404);
            }
            
            // 删除物理文件 - 验证文件路径防止目录遍历攻击
            $filePath = public_path() . $file->file_path;
            $realPath = realpath($filePath);
            $publicPath = realpath(public_path());
            
            // 确保文件在public目录内，防止目录遍历攻击
            if ($realPath === false || strpos($realPath, $publicPath) !== 0) {
                return $this->error('文件路径无效', 400);
            }
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // 删除数据库记录
            $file->delete();
            
            return $this->success(null, '删除成功');
            
        } catch (\Exception $e) {
            Log::error('删除上传文件失败: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('删除失败，请稍后重试', 500);
        }

    }

    /**
     * 验证图片
     */
    protected function validateImage($file)
    {
        $config = $this->config['image'];
        
        // 检查文件大小
        if ($file->getSize() > $config['max_size']) {
            return '图片大小不能超过' . ($config['max_size'] / 1024 / 1024) . 'MB';
        }
        
        // 检查扩展名 - 使用getExtension()而非getOriginalExtension()，防止客户端伪造
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $config['allowed_ext'])) {
            return '只支持 ' . implode(', ', $config['allowed_ext']) . ' 格式的图片';
        }
        
        // 检查MIME类型
        $mime = $file->getMime();
        if (!in_array($mime, $config['allowed_mime'])) {
            return '图片格式不正确';
        }
        
        // 检查文件头Magic Bytes，验证真实文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg' => ['image/jpeg', 'image/jpg'],
            'image/png' => ['image/png'],
            'image/gif' => ['image/gif'],
            'image/webp' => ['image/webp'],
        ];
        
        if (!isset($allowedMimes[$mime]) || !in_array($realMime, $allowedMimes[$mime])) {
            return '图片文件内容验证失败';
        }
        
        // 对图片进行二次渲染，清除可能存在的恶意代码
        if (!$this->reImage($file->getPathname(), $ext)) {
            return '图片文件验证失败，可能包含恶意内容';
        }
        
        return true;
    }
    
    /**
     * 图片二次渲染
     */
    protected function reImage(string $filepath, string $ext): bool
    {
        try {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    $image = @imagecreatefromjpeg($filepath);
                    if (!$image) return false;
                    imagejpeg($image, $filepath, 90);
                    imagedestroy($image);
                    break;
                    
                case 'png':
                    $image = @imagecreatefrompng($filepath);
                    if (!$image) return false;
                    // 保留透明通道
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    imagepng($image, $filepath, 6);
                    imagedestroy($image);
                    break;
                    
                case 'gif':
                    // GIF文件只验证是否能正确打开
                    $image = @imagecreatefromgif($filepath);
                    if (!$image) return false;
                    imagedestroy($image);
                    break;
                    
                case 'webp':
                    if (!function_exists('imagecreatefromwebp')) {
                        // 如果系统不支持webp，只检查文件头
                        return true;
                    }
                    $image = @imagecreatefromwebp($filepath);
                    if (!$image) return false;
                    imagewebp($image, $filepath, 90);
                    imagedestroy($image);
                    break;
                    
                default:
                    return false;
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 验证文件
     */
    protected function validateFile($file)
    {
        $config = $this->config['file'];
        
        // 检查文件大小
        if ($file->getSize() > $config['max_size']) {
            return '文件大小不能超过' . ($config['max_size'] / 1024 / 1024) . 'MB';
        }
        
        // 检查扩展名 - 使用getExtension()获取真实扩展名，防止客户端伪造
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $config['allowed_ext'])) {
            return '只支持 ' . implode(', ', $config['allowed_ext']) . ' 格式的文件';
        }
        
        return true;
    }

    /**
     * 获取保存路径
     */
    protected function getSavePath($type)
    {
        $config = $this->config[$type];
        $datePath = date('Y/m/d');
        $fullPath = public_path() . $config['save_path'] . $datePath . '/';
        
        // 创建目录
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        return $config['save_path'] . $datePath . '/';
    }

    /**
     * 生成文件名
     */
    protected function generateFileName($extension)
    {
        return date('YmdHis') . '_' . uniqid() . '.' . strtolower($extension);
    }

    /**
     * 获取文件URL
     */
    protected function getFileUrl($path)
    {
        $baseUrl = env('UPLOAD_BASE_URL', '');
        return $baseUrl . $path;
    }

    /**
     * 保存文件记录到数据库
     */
    protected function saveFileRecord($data)
    {
        try {
            \app\model\UploadFile::create($data);
        } catch (\Exception $e) {
            // 记录日志但不影响上传流程
            Log::error('保存文件记录失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

    }
}