<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\LiuyaoService;
use app\service\DeepSeekService;
use think\Request;
use think\facade\Db;

/**
 * 六爻占卜控制器
 */
class Liuyao extends BaseController
{
    /**
     * 起卦 - 支持多种起卦方式
     */
    public function qiGua()
    {
        try {
            $data = $this->request->post();
            $method = $data['method'] ?? 'time'; // time/number/manual
            
            $result = match($method) {
                'time' => $this->qiGuaByTime(),
                'number' => $this->qiGuaByNumber($data),
                'manual' => $this->qiGuaByManual($data),
                default => throw new \Exception('不支持的起卦方式'),
            };
            
            // 计算变卦、互卦
            $result['bian_gua'] = LiuyaoService::getBianGua($result['yao_code']);
            $result['hu_gua'] = LiuyaoService::getHuGua($result['yao_code']);
            
            // 获取卦辞信息
            $result['gua_info'] = $this->getGuaInfo($result['main_gua'], $result['bian_gua']['bian_name']);
            
            // 计算六亲六神（需要日辰信息）
            $riGan = $data['ri_gan'] ?? '甲';
            
            // 验证日辰天干是否有效
            $validGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
            if (!in_array($riGan, $validGan, true)) {
                return $this->error('日辰天干参数无效，必须是甲-癸之一', 400);
            }
            
            $result['liu_shen'] = LiuyaoService::getLiuShen($riGan);
            
            // 计算卦宫、六亲和世应
            $guaInfo = LiuyaoService::getGuaInfo($result['yao_code']);
            $result['gong'] = $guaInfo['gong'];
            $result['shi_ying'] = LiuyaoService::getShiYing($result['main_gua'], $result['yao_code']);
            
            $gongWuxing = LiuyaoService::BA_GUA_WUXING[$guaInfo['gong']] ?? '金';
            $result['liuqin'] = LiuyaoService::getLiuQin($result['main_gua'], $gongWuxing, $result['yao_code']);
            
            // 判断用神
            $questionType = $data['question_type'] ?? '其他';
            $result['yong_shen'] = LiuyaoService::getYongShen($questionType);
            
            // 保存记录（使用事务）
            if (!empty($data['user_id'])) {
                Db::startTrans();
                try {
                    $this->saveRecord($data, $result);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    throw $e;
                }
            }
            
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('起卦失败：' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 时间起卦
     */
    private function qiGuaByTime(): array
    {
        $now = new \DateTime();
        return LiuyaoService::qiGuaByTime(
            (int)$now->format('Y'),
            (int)$now->format('n'),
            (int)$now->format('j'),
            (int)$now->format('G')
        );
    }
    
    /**
     * 数字起卦
     */
    private function qiGuaByNumber(array $data): array
    {
        if (empty($data['numbers'])) {
            throw new \Exception('请提供数字');
        }
        
        $numbers = $data['numbers'];
        if (is_string($numbers)) {
            $numbers = array_map('intval', explode(',', $numbers));
        }
        
        return LiuyaoService::qiGuaByNumber($numbers[0] ?? 1, $numbers[1] ?? null);
    }
    
    /**
     * 手动摇卦
     */
    private function qiGuaByManual(array $data): array
    {
        if (empty($data['yao_results']) || count($data['yao_results']) != 6) {
            throw new \Exception('请提供6次摇卦结果');
        }
        
        return LiuyaoService::qiGuaByManual($data['yao_results']);
    }
    
    /**
     * 获取卦辞信息
     */
    private function getGuaInfo(string $mainGua, string $bianGua): array
    {
        // 验证卦名格式，只允许中文字符
        $pattern = '/^[\x{4e00}-\x{9fa5}]+$/u';
        if (!preg_match($pattern, $mainGua) || !preg_match($pattern, $bianGua)) {
            return [
                'main' => null,
                'bian' => null,
            ];
        }
        
        $mainInfo = \think\facade\Db::table('gua_data')
            ->where('gua_name', $mainGua)
            ->find();
        
        $bianInfo = \think\facade\Db::table('gua_data')
            ->where('gua_name', $bianGua)
            ->find();
        
        return [
            'main' => $mainInfo,
            'bian' => $bianInfo,
        ];
    }
    
    /**
     * 保存起卦记录
     */
    private function saveRecord(array $data, array $result): void
    {
        $recordData = [
            'user_id' => $data['user_id'],
            'question' => $data['question'] ?? '',
            'question_type' => $this->getQuestionTypeCode($data['question_type'] ?? '其他'),
            'method' => $this->getMethodCode($data['method'] ?? 'time'),
            'input_number' => $data['numbers'] ?? '',
            'yao_result' => $result['yao_results'] ?? '',
            'yao_code' => $result['yao_code'],
            'main_gua_name' => $result['main_gua'],
            'main_gua_code' => str_replace(['0', '1', '2', '3'], ['0', '1', '0', '1'], $result['yao_code']),
            'bian_gua_name' => $result['bian_gua']['bian_name'],
            'bian_gua_code' => $result['bian_gua']['bian_code'],
            'hu_gua_name' => $result['hu_gua']['hu_name'] ?? '',
            'hu_gua_code' => $result['hu_gua']['hu_code'] ?? '',
            'liuqin' => json_encode($result['liuqin'] ?? []),
            'liushen' => json_encode($result['liu_shen']),
            'yongshen' => $result['yong_shen']['liuqin'] ?? '',
            'liunian' => $data['liunian'] ?? '',
            'yuejian' => $data['yuejian'] ?? '',
            'rigan' => $data['ri_gan'] ?? '',
            'interpretation' => $result['gua_info']['main']['general_meaning'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        \think\facade\Db::table('tc_liuyao_record')->insert($recordData);
    }
    
    /**
     * 获取AI解读
     */
    public function aiInterpretation()
    {
        try {
            $data = $this->request->post();
            
            if (empty($data['yao_code']) || empty($data['question'])) {
                return $this->error('参数错误', 400);
            }
            
            // 构建提示词
            $prompt = $this->buildAiPrompt($data);
            
            // 调用DeepSeek API
            $interpretation = DeepSeekService::chat($prompt);
            
            // 更新记录
            if (!empty($data['record_id'])) {
                \think\facade\Db::table('tc_liuyao_record')
                    ->where('id', $data['record_id'])
                    ->update([
                        'ai_interpretation' => $interpretation,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
            
            return $this->success(['interpretation' => $interpretation]);
        } catch (\Exception $e) {
            // 记录详细错误日志，但返回通用错误消息
            \think\facade\Log::error('六爻AI解读失败: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('AI解读失败，请稍后重试', 500);
        }
    }
    
    /**
     * 构建AI提示词
     */
    private function buildAiPrompt(array $data): string
    {
        $mainGua = $data['main_gua'] ?? '';
        $bianGua = $data['bian_gua'] ?? '';
        $yaoCode = $data['yao_code'] ?? '';
        $question = $data['question'] ?? '';
        $yongShen = $data['yong_shen'] ?? '';
        
        return <<<PROMPT
你是一位专业的六爻占卜大师，请根据以下卦象为求测者进行详细解读：

【所问事项】
{$question}

【本卦】
{$mainGua}

【变卦】
{$bianGua}

【动爻】
{$yaoCode}

【用神】
{$yongShen}

请从以下几个方面进行解读：
1. 卦象总体分析
2. 用神状态分析
3. 动变分析（事情的发展变化）
4. 应期推断（事情发生的时间）
5. 具体建议

请用专业但易懂的语言进行解读。
PROMPT;
    }
    
    /**
     * 获取起卦记录列表
     */
    public function records()
    {
        try {
            $userId = $this->request->get('user_id');
            $page = $this->request->get('page', 1);
            $pageSize = $this->request->get('page_size', 20);
            
            $query = \think\facade\Db::table('tc_liuyao_record')
                ->where('status', 1);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $total = $query->count();
            $list = $query->page($page, $pageSize)
                ->order('created_at', 'DESC')
                ->select();
            
            return $this->success([
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'list' => $list,
            ]);
        } catch (\Exception $e) {
            return $this->error('获取记录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取记录详情
     */
    public function recordDetail(int $id)
    {
        try {
            $record = \think\facade\Db::table('tc_liuyao_record')
                ->where('id', $id)
                ->where('status', 1)
                ->find();
            
            if (!$record) {
                return $this->error('记录不存在');
            }
            
            // 解析JSON字段
            $record['liuqin'] = json_decode($record['liuqin'], true);
            $record['liushen'] = json_decode($record['liushen'], true);
            
            return $this->success($record);
        } catch (\Exception $e) {
            return $this->error('获取记录详情失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取六十四卦列表
     */
    public function guaList()
    {
        try {
            $list = \think\facade\Db::table('gua_data')
                ->field('id, gua_name, gua_xuhao, wuxing, general_meaning')
                ->order('gua_xuhao', 'ASC')
                ->select();
            
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->error('获取卦象列表失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取卦象详情
     */
    public function guaDetail(string $name)
    {
        try {
            $gua = \think\facade\Db::table('gua_data')
                ->where('gua_name', $name)
                ->find();
            
            if (!$gua) {
                return $this->error('卦象不存在');
            }
            
            // 获取爻辞
            $yaoCi = \think\facade\Db::table('gua_yao_data')
                ->where('gua_id', $gua['id'])
                ->order('yao_position', 'ASC')
                ->select();
            
            $gua['yao_ci'] = $yaoCi;
            
            return $this->success($gua);
        } catch (\Exception $e) {
            return $this->error('获取卦象详情失败：' . $e->getMessage());
        }
    }
    
    /**
     * 转换问事类型为代码
     */
    private function getQuestionTypeCode(string $type): int
    {
        $map = [
            '求财' => 1,
            '感情' => 2,
            '事业' => 3,
            '健康' => 4,
            '学业' => 5,
            '出行' => 6,
            '其他' => 7,
        ];
        return $map[$type] ?? 7;
    }
    
    /**
     * 转换起卦方式为代码
     */
    private function getMethodCode(string $method): int
    {
        $map = [
            'time' => 1,
            'number' => 2,
            'manual' => 3,
        ];
        return $map[$method] ?? 1;
    }
}
