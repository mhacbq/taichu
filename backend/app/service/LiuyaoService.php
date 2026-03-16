<?php

namespace app\service;

/**
 * 六爻占卜服务类
 * 提供变卦计算、六亲六神配属、用神判断等专业功能
 */
class LiuyaoService
{
    /**
     * 八卦名称
     */
    const BA_GUA = [
        '000' => '坤',  // 地
        '001' => '震',  // 雷
        '010' => '坎',  // 水
        '011' => '兑',  // 泽
        '100' => '艮',  // 山
        '101' => '离',  // 火
        '110' => '巽',  // 风
        '111' => '乾',  // 天
    ];

    /**
     * 八卦五行属性
     */
    const BA_GUA_WUXING = [
        '乾' => '金', '兑' => '金',
        '震' => '木', '巽' => '木',
        '坎' => '水',
        '离' => '火',
        '艮' => '土', '坤' => '土',
    ];

    /**
     * 六亲关系
     */
    const LIU_QIN = ['父母', '兄弟', '子孙', '妻财', '官鬼'];

    /**
     * 六神顺序（从初爻到上爻）
     */
    const LIU_SHEN = ['青龙', '朱雀', '勾陈', '螣蛇', '白虎', '玄武'];

    /**
     * 计算变卦
     * 根据动爻位置，将老阴(6)变阳，老阳(9)变阴
     * 
     * @param string $yaoCode 六爻码（6位：0老阴 1少阳 2少阴 3老阳）
     * @return array 变卦信息
     */
    public static function getBianGua(string $yaoCode): array
    {
        $yaoArray = str_split($yaoCode);
        $bianYao = [];
        $dongYao = [];
        
        foreach ($yaoArray as $index => $yao) {
            // 老阴(0)变阳(1)，老阳(3)变阴(2)
            if ($yao == '0') {
                $bianYao[] = '1';  // 老阴变少阳
                $dongYao[] = $index + 1;  // 记录动爻位置（1-6）
            } elseif ($yao == '3') {
                $bianYao[] = '2';  // 老阳变少阴
                $dongYao[] = $index + 1;
            } else {
                $bianYao[] = $yao;
            }
        }
        
        $bianCode = implode('', $bianYao);
        $bianGuaName = self::getGuaName($bianCode);
        
        return [
            'bian_code' => $bianCode,
            'bian_name' => $bianGuaName,
            'dong_yao' => $dongYao,  // 动爻位置数组
            'dong_yao_count' => count($dongYao),
        ];
    }

    /**
     * 计算互卦
     * 取本卦的234爻为下卦，345爻为上卦
     * 
     * @param string $yaoCode 六爻码
     * @return array 互卦信息
     */
    public static function getHuGua(string $yaoCode): array
    {
        $yaoArray = str_split($yaoCode);
        
        // 234爻为下卦（索引1,2,3）
        $xiaGuaCode = $yaoArray[1] . $yaoArray[2] . $yaoArray[3];
        // 345爻为上卦（索引2,3,4）
        $shangGuaCode = $yaoArray[2] . $yaoArray[3] . $yaoArray[4];
        
        // 互卦码（从下往上：下卦+上卦）
        $huCode = $xiaGuaCode . $shangGuaCode;
        
        // 转换为阴阳（0,2为阴，1,3为阳）
        $huYinYang = '';
        foreach (str_split($huCode) as $yao) {
            $huYinYang .= ($yao == '0' || $yao == '2') ? '0' : '1';
        }
        
        return [
            'hu_code' => $huYinYang,
            'hu_name' => self::getGuaName($huYinYang),
            'xia_gua' => self::BA_GUA[$xiaGuaCode] ?? '',
            'shang_gua' => self::BA_GUA[$shangGuaCode] ?? '',
        ];
    }

    /**
     * 根据卦象代码获取卦名
     * 
     * @param string $code 6位卦象代码（0阴1阳，从下到上）
     * @return string 卦名
     */
    public static function getGuaName(string $code): string
    {
        if (strlen($code) != 6) {
            return '';
        }
        
        // 下卦（初二三爻）
        $xiaCode = substr($code, 0, 3);
        // 上卦（四五上爻）
        $shangCode = substr($code, 3, 3);
        
        $xiaGua = self::BA_GUA[$xiaCode] ?? '';
        $shangGua = self::BA_GUA[$shangCode] ?? '';
        
        return $shangGua . $xiaGua;
    }

    /**
     * 计算六亲
     * 根据本卦卦宫五行与各爻地支五行的生克关系确定
     * 
     * @param string $guaName 卦名
     * @param string $gongWuxing 卦宫五行
     * @return array 六亲信息
     */
    public static function getLiuQin(string $guaName, string $gongWuxing): array
    {
        // 六爻地支（从初爻到上爻）- 需要根据日辰推算
        // 简化版本：使用固定对应关系
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        $diZhiWuxing = [
            '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
            '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
            '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水',
        ];
        
        // 生克关系确定六亲
        // 生我者为父母，我生者为子孙，克我者为官鬼，我克者为妻财，比和者为兄弟
        $liuQin = [];
        $yaoDiZhi = self::getYaoDiZhi($guaName);
        
        foreach ($yaoDiZhi as $position => $zhi) {
            $zhiWuxing = $diZhiWuxing[$zhi] ?? '';
            $liuQin[$position] = self::getLiuQinByWuxing($gongWuxing, $zhiWuxing);
        }
        
        return $liuQin;
    }

    /**
     * 根据五行生克关系确定六亲
     */
    private static function getLiuQinByWuxing(string $gongWuxing, string $zhiWuxing): string
    {
        // 五行相生：木生火，火生土，土生金，金生水，水生木
        // 五行相克：木克土，土克水，水克火，火克金，金克木
        $sheng = [
            '木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木',
        ];
        $ke = [
            '木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木',
        ];
        
        if ($gongWuxing == $zhiWuxing) {
            return '兄弟';
        } elseif ($sheng[$gongWuxing] == $zhiWuxing) {
            return '子孙';
        } elseif ($sheng[$zhiWuxing] == $gongWuxing) {
            return '父母';
        } elseif ($ke[$gongWuxing] == $zhiWuxing) {
            return '妻财';
        } elseif ($ke[$zhiWuxing] == $gongWuxing) {
            return '官鬼';
        }
        
        return '';
    }

    /**
     * 获取六爻地支（简化版）
     * 实际应根据日辰和卦宫推算
     */
    private static function getYaoDiZhi(string $guaName): array
    {
        // 简化示例，实际需要复杂的纳甲计算
        $defaultZhi = ['子', '寅', '辰', '午', '申', '戌'];
        $result = [];
        foreach ($defaultZhi as $index => $zhi) {
            $result[$index + 1] = $zhi;
        }
        return $result;
    }

    /**
     * 计算六神
     * 根据日辰天干确定起始六神，然后按顺序配到六爻
     * 
     * @param string $riGan 日干（甲、乙、丙、丁、戊、己、庚、辛、壬、癸）
     * @return array 六神信息
     */
    public static function getLiuShen(string $riGan): array
    {
        // 日干对应起始六神
        $startLiuShen = [
            '甲' => '青龙', '乙' => '青龙',
            '丙' => '朱雀', '丁' => '朱雀',
            '戊' => '勾陈', '己' => '勾陈',
            '庚' => '白虎', '辛' => '白虎',
            '壬' => '玄武', '癸' => '玄武',
        ];
        
        $liuShenOrder = self::LIU_SHEN;
        $start = $startLiuShen[$riGan] ?? '青龙';
        $startIndex = array_search($start, $liuShenOrder);
        
        $result = [];
        for ($i = 0; $i < 6; $i++) {
            $position = $i + 1;  // 爻位（1-6，从下到上）
            $shenIndex = ($startIndex + $i) % 6;
            $result[$position] = $liuShenOrder[$shenIndex];
        }
        
        return $result;
    }

    /**
     * 确定世应
     * 根据卦宫和卦象确定世爻和应爻的位置
     * 
     * @param string $guaName 卦名
     * @return array 世应信息
     */
    public static function getShiYing(string $guaName): array
    {
        // 世应规则：八纯卦世在上六，应在三爻
        // 一世卦世在初爻，应在四爻
        // 二世卦世在二爻，应在五爻
        // 三世卦世在三爻，应在上六
        // 四世卦世在四爻，应在初爻
        // 五世卦世在五爻，应在二爻
        // 游魂卦世在四爻，应在初爻
        // 归魂卦世在三爻，应在上六
        
        $shiYingMap = [
            // 八纯卦
            '乾' => ['shi' => 6, 'ying' => 3],
            '坤' => ['shi' => 6, 'ying' => 3],
            '震' => ['shi' => 6, 'ying' => 3],
            '巽' => ['shi' => 6, 'ying' => 3],
            '坎' => ['shi' => 6, 'ying' => 3],
            '离' => ['shi' => 6, 'ying' => 3],
            '艮' => ['shi' => 6, 'ying' => 3],
            '兑' => ['shi' => 6, 'ying' => 3],
        ];
        
        // 简化处理，实际需要根据卦宫推算
        return $shiYingMap[$guaName] ?? ['shi' => 1, 'ying' => 4];
    }

    /**
     * 判断用神
     * 根据所问事项自动判断用神
     * 
     * @param string $questionType 问事类型
     * @return array 用神信息
     */
    public static function getYongShen(string $questionType): array
    {
        // 用神对应表
        $yongShenMap = [
            '求财' => ['liuqin' => '妻财', 'description' => '妻财爻为用神，代表钱财、利润'],
            '感情' => [
                'male' => ['liuqin' => '妻财', 'description' => '男测感情以妻财为用神'],
                'female' => ['liuqin' => '官鬼', 'description' => '女测感情以官鬼为用神'],
            ],
            '事业' => ['liuqin' => '官鬼', 'description' => '官鬼爻为用神，代表工作、职位'],
            '健康' => ['liuqin' => '世爻', 'description' => '以世爻为用神，代表自己'],
            '学业' => ['liuqin' => '父母', 'description' => '父母爻为用神，代表学业、成绩'],
            '出行' => ['liuqin' => '世爻', 'description' => '以世爻为用神'],
            '失物' => ['liuqin' => '妻财', 'description' => '妻财爻为用神'],
            '官司' => ['liuqin' => '官鬼', 'description' => '官鬼爻为用神'],
            '父母' => ['liuqin' => '父母', 'description' => '父母爻为用神'],
            '子女' => ['liuqin' => '子孙', 'description' => '子孙爻为用神'],
        ];
        
        return $yongShenMap[$questionType] ?? ['liuqin' => '世爻', 'description' => '以世爻为用神'];
    }

    /**
     * 时间起卦法
     * 根据当前时间（年月日时）起卦
     * 
     * @param int $year 年
     * @param int $month 月
     * @param int $day 日
     * @param int $hour 时（0-23）
     * @return array 起卦结果
     */
    public static function qiGuaByTime(int $year, int $month, int $day, int $hour): array
    {
        // 上卦 = (年+月+日) % 8
        $shangGuaNum = ($year + $month + $day) % 8;
        if ($shangGuaNum == 0) $shangGuaNum = 8;
        
        // 下卦 = (年+月+日+时) % 8
        $xiaGuaNum = ($year + $month + $day + $hour) % 8;
        if ($xiaGuaNum == 0) $xiaGuaNum = 8;
        
        // 动爻 = (年+月+日+时) % 6
        $dongYao = ($year + $month + $day + $hour) % 6;
        if ($dongYao == 0) $dongYao = 6;
        
        // 生成卦象
        $shangGua = self::getBaGuaByNum($shangGuaNum);
        $xiaGua = self::getBaGuaByNum($xiaGuaNum);
        
        $yaoCode = self::generateYaoCode($shangGua, $xiaGua, $dongYao);
        
        return [
            'method' => 'time',
            'shang_gua' => $shangGua,
            'xia_gua' => $xiaGua,
            'dong_yao' => $dongYao,
            'yao_code' => $yaoCode,
            'main_gua' => self::getGuaName(str_replace(['0', '1', '2', '3'], ['0', '1', '0', '1'], $yaoCode)),
        ];
    }

    /**
     * 数字起卦法
     * 
     * @param int $num1 第一个数字
     * @param int $num2 第二个数字（可选）
     * @return array 起卦结果
     */
    public static function qiGuaByNumber(int $num1, ?int $num2 = null): array
    {
        if ($num2 === null) {
            // 单数字起卦：拆分为两个数字
            $str = (string) $num1;
            $mid = ceil(strlen($str) / 2);
            $num1 = (int) substr($str, 0, $mid);
            $num2 = (int) substr($str, $mid);
        }
        
        // 上卦 = num1 % 8
        $shangGuaNum = $num1 % 8;
        if ($shangGuaNum == 0) $shangGuaNum = 8;
        
        // 下卦 = num2 % 8
        $xiaGuaNum = $num2 % 8;
        if ($xiaGuaNum == 0) $xiaGuaNum = 8;
        
        // 动爻 = (num1 + num2) % 6
        $dongYao = ($num1 + $num2) % 6;
        if ($dongYao == 0) $dongYao = 6;
        
        $shangGua = self::getBaGuaByNum($shangGuaNum);
        $xiaGua = self::getBaGuaByNum($xiaGuaNum);
        
        $yaoCode = self::generateYaoCode($shangGua, $xiaGua, $dongYao);
        
        return [
            'method' => 'number',
            'num1' => $num1,
            'num2' => $num2,
            'shang_gua' => $shangGua,
            'xia_gua' => $xiaGua,
            'dong_yao' => $dongYao,
            'yao_code' => $yaoCode,
            'main_gua' => self::getGuaName(str_replace(['0', '1', '2', '3'], ['0', '1', '0', '1'], $yaoCode)),
        ];
    }

    /**
     * 手动摇卦法
     * 
     * @param array $yaoResults 六次摇卦结果 [6,7,8,9,6,7]
     * @return array 起卦结果
     */
    public static function qiGuaByManual(array $yaoResults): array
    {
        if (count($yaoResults) != 6) {
            throw new \InvalidArgumentException('摇卦结果必须是6个数字');
        }
        
        $yaoCode = '';
        $dongYao = [];
        
        foreach ($yaoResults as $index => $result) {
            // 转换：6->0老阴, 7->1少阳, 8->2少阴, 9->3老阳
            $code = match($result) {
                6 => '0',
                7 => '1',
                8 => '2',
                9 => '3',
                default => '1',
            };
            $yaoCode .= $code;
            
            // 记录动爻（老阴老阳）
            if ($result == 6 || $result == 9) {
                $dongYao[] = $index + 1;
            }
        }
        
        return [
            'method' => 'manual',
            'yao_results' => $yaoResults,
            'yao_code' => $yaoCode,
            'dong_yao' => $dongYao,
            'main_gua' => self::getGuaName(str_replace(['0', '1', '2', '3'], ['0', '1', '0', '1'], $yaoCode)),
        ];
    }

    /**
     * 根据数字获取八卦
     */
    private static function getBaGuaByNum(int $num): string
    {
        $baGuaList = array_values(self::BA_GUA);
        return $baGuaList[$num - 1] ?? '乾';
    }

    /**
     * 生成六爻码
     */
    private static function generateYaoCode(string $shangGua, string $xiaGua, int $dongYao): string
    {
        // 八卦对应的爻码（从下到上：初爻、二爻、三爻）
        $baGuaCode = array_flip(self::BA_GUA);
        
        $xiaCode = $baGuaCode[$xiaGua] ?? '111';
        $shangCode = $baGuaCode[$shangGua] ?? '111';
        
        // 组合成六爻（下卦+上卦）
        $code = $xiaCode . $shangCode;
        
        // 设置动爻（从下到上，1-6）
        $yaoArray = str_split($code);
        $position = $dongYao - 1;
        
        // 动爻：阴变阳，阳变阴
        if ($yaoArray[$position] == '0') {
            $yaoArray[$position] = '3';  // 老阳
        } else {
            $yaoArray[$position] = '0';  // 老阴
        }
        
        return implode('', $yaoArray);
    }
}
