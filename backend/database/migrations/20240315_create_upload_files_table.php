<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateUploadFilesTable extends Migrator
{
    /**
     * 创建上传文件表
     */
    public function change()
    {
        $table = $this->table('upload_files', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci']);
        
        $table->addColumn('type', 'string', ['limit' => 20, 'null' => false, 'default' => 'image', 'comment' => '文件类型: image/file'])
            ->addColumn('original_name', 'string', ['limit' => 255, 'null' => false, 'default' => '', 'comment' => '原始文件名'])
            ->addColumn('file_name', 'string', ['limit' => 255, 'null' => false, 'default' => '', 'comment' => '保存的文件名'])
            ->addColumn('file_path', 'string', ['limit' => 500, 'null' => false, 'default' => '', 'comment' => '文件保存路径'])
            ->addColumn('file_url', 'string', ['limit' => 500, 'null' => false, 'default' => '', 'comment' => '文件访问URL'])
            ->addColumn('file_size', 'integer', ['null' => false, 'default' => 0, 'comment' => '文件大小（字节）'])
            ->addColumn('mime_type', 'string', ['limit' => 100, 'null' => false, 'default' => '', 'comment' => 'MIME类型'])
            ->addColumn('extension', 'string', ['limit' => 20, 'null' => false, 'default' => '', 'comment' => '文件扩展名'])
            ->addColumn('uploaded_by', 'integer', ['null' => false, 'default' => 0, 'comment' => '上传者ID'])
            ->addColumn('is_deleted', 'boolean', ['null' => false, 'default' => false, 'comment' => '是否删除'])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'comment' => '删除时间'])
            ->addColumn('created_at', 'datetime', ['null' => true])
            ->addIndex(['type'])
            ->addIndex(['uploaded_by'])
            ->addIndex(['created_at'])
            ->create();
    }
}