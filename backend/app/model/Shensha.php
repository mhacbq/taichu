<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class Shensha extends Model
{
    protected $table = 'tc_shensha';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // 字段类型
    protected $schema = [
        'id' => 'int',
        'name' => 'string',
        'type' => 'string',
        'category' => 'string',
        'description' => 'string',
        'effect' => 'string',
        'check_rule' => 'string',
        'check_code' => 'string',
        'gan_rules' => 'json',
        'zhi_rules' => 'json',
        'sort' => 'int',
        'status' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // 默认值
    protected $defaultValues = [
        'sort' => 0,
        'status' => 1,
    ];
}
