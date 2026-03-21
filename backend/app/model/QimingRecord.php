<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 取名记录模型
 */
class QimingRecord extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tc_qiming_record';

    // 开启自动写入时间戳字段
    protected $createTime = 'created_at';
    protected $updateTime = false;

    /**
     * 关联用户模型
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 搜查器：用户ID
     */
    public function searchUserIdAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('user_id', $value);
        }
    }

    /**
     * 搜查器：姓氏
     */
    public function searchSurnameAttr($query, $value, $data)
    {
        if ($value) {
            $query->whereLike('surname', '%' . $value . '%');
        }
    }

    /**
     * 搜查器：起始日期
     */
    public function searchStartDateAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('created_at', '>=', $value . ' 00:00:00');
        }
    }

    /**
     * 搜查器：结束日期
     */
    public function searchEndDateAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('created_at', '<=', $value . ' 23:59:59');
        }
    }
}

