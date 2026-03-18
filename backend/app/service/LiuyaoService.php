<?php

namespace app\service;

/**
 * 六爻占卜服务类
 * 提供变卦计算、六亲六神配属、用神判断等专业功能
 */
class LiuyaoService
{
    /**
     * 八卦名称 (按先天八卦序：1乾、2兑、3离、4震、5巽、6坎、7艮、8坤)
     * 键名为三位二进制：初爻、二爻、三爻 (1为阳，0为阴)
     */
    const BA_GUA = [
        '111' => '乾',
        '110' => '兑',
        '101' => '离',
        '100' => '震',
        '011' => '巽',
        '010' => '坎',
        '001' => '艮',
        '000' => '坤',
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
    public static function getLiuQin(string $guaName, string $gongWuxing, string $yaoCode): array
    {
        // 六爻地支（从初爻到上爻）
        $diZhiWuxing = [
            '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
            '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
            '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水',
        ];
        
        $liuQin = [];
        $yaoDiZhi = self::getYaoDiZhi($guaName, $yaoCode);
        
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
     * 八卦纳甲地支
     */
    const NA_JIA = [
        '乾' => ['inner' => ['子', '寅', '辰'], 'outer' => ['午', '申', '戌']],
        '震' => ['inner' => ['子', '寅', '辰'], 'outer' => ['午', '申', '戌']],
        '坎' => ['inner' => ['寅', '辰', '午'], 'outer' => ['申', '戌', '子']],
        '艮' => ['inner' => ['辰', '午', '申'], 'outer' => ['戌', '子', '寅']],
        '坤' => ['inner' => ['未', '巳', '卯'], 'outer' => ['丑', '亥', '酉']],
        '巽' => ['inner' => ['丑', '亥', '酉'], 'outer' => ['未', '巳', '卯']],
        '离' => ['inner' => ['卯', '丑', '亥'], 'outer' => ['酉', '未', '巳']],
        '兑' => ['inner' => ['巳', '卯', '丑'], 'outer' => ['亥', '酉', '未']],
    ];

    /**
     * 六十四卦详细属性 (卦宫、世位、应位)
     * 这里仅展示部分核心逻辑映射，实际可扩展为完整映射
     */
    protected static $guaProperties = [
        // 乾宫
        '乾为天' => ['gong' => '乾', 'shi' => 6, 'ying' => 3],
        '天风姤' => ['gong' => '乾', 'shi' => 1, 'ying' => 4],
        '天山遁' => ['gong' => '乾', 'shi' => 2, 'ying' => 5],
        '天地否' => ['gong' => '乾', 'shi' => 3, 'ying' => 6],
        '风地观' => ['gong' => '乾', 'shi' => 4, 'ying' => 1],
        '山地剥' => ['gong' => '乾', 'shi' => 5, 'ying' => 2],
        '火地晋' => ['gong' => '乾', 'shi' => 4, 'ying' => 1], // 游魂
        '火天大有' => ['gong' => '乾', 'shi' => 3, 'ying' => 6], // 归魂
        // ... 其他56卦以此类推，建议使用算法动态计算
    ];

    /**
     * 获取六爻地支 (纳甲法)
     */
    private static function getYaoDiZhi(string $guaName, string $yaoCode): array
    {
        $yaoArray = str_split($yaoCode);
        // 下卦代码
        $xiaCode = substr($yaoCode, 0, 3);
        $shangCode = substr($yaoCode, 3, 3);
        
        $xiaGua = self::BA_GUA[$xiaCode];
        $shangGua = self::BA_GUA[$shangCode];
        
        $result = [];
        $innerZhi = self::NA_JIA[$xiaGua]['inner'];
        $outerZhi = self::NA_JIA[$shangGua]['outer'];
        
        for ($i = 0; $i < 3; $i++) {
            $result[$i + 1] = $innerZhi[$i];
            $result[$i + 4] = $outerZhi[$i];
        }
        
        return $result;
    }

    /**
     * 动态计算卦宫和世应 (寻宫认世法)
     * 算法逻辑：
     * XOR = 上卦 ^ 下卦
     * 0(000): 八纯, Shi 6, Palace U
     * 1(001): 一世, Shi 1, Palace U
     * 3(011): 二世, Shi 2, Palace U
     * 7(111): 三世, Shi 3, Palace U
     * 6(110): 四世, Shi 4, Palace U ^ 1
     * 4(100): 五世, Shi 5, Palace U ^ 3
     * 5(101): 游魂, Shi 4, Palace U ^ 2
     * 2(010): 归魂, Shi 3, Palace L
     */
    public static function getGuaInfo(string $yaoCode): array
    {
        // 转换 yaoCode 为 0/1 形式进行计算 (忽略老阴老阳)
        $cleanCode = str_replace(['2', '3'], ['0', '1'], $yaoCode);
        $xiaCode = substr($cleanCode, 0, 3);
        $shangCode = substr($cleanCode, 3, 3);
        
        $xia = bindec(strrev($xiaCode)); // 反转因为代码是初爻到三爻，bindec需要高位在前
        $shang = bindec(strrev($shangCode));
        
        // 重新定义位权：初/四位1，二/五位2，三/六位4
        // 所以 bindec(strrev('001')) = 4 是错的。
        // 应该是：初位bit0, 二位bit1, 三位bit2
        // 如果 $xiaCode 是 '100' (震)，表示初爻是阳，二三是阴。
        // 那么 $xia 应该是 1.
        $getXor = function($code) {
            $val = 0;
            if ($code[0] == '1') $val += 1;
            if ($code[1] == '1') $val += 2;
            if ($code[2] == '1') $val += 4;
            return $val;
        };
        
        $xVal = $getXor($xiaCode);
        $sVal = $getXor($shangCode);
        $xor = $xVal ^ $sVal;
        
        $shi = 6;
        $type = '八纯';
        $pVal = $sVal; // 默认宫位是上卦
        
        switch ($xor) {
            case 0: $shi = 6; $type = '八纯'; $pVal = $sVal; break;
            case 1: $shi = 1; $type = '一世'; $pVal = $sVal; break;
            case 3: $shi = 2; $type = '二世'; $pVal = $sVal; break;
            case 7: $shi = 3; $type = '三世'; $pVal = $sVal; break;
            case 6: $shi = 4; $type = '四世'; $pVal = $sVal ^ 1; break;
            case 4: $shi = 5; $type = '五世'; $pVal = $sVal ^ 3; break;
            case 5: $shi = 4; $type = '游魂'; $pVal = $sVal ^ 2; break;
            case 2: $shi = 3; $type = '归魂'; $pVal = $xVal; break;
        }

        $ying = ($shi > 3) ? ($shi - 3) : ($shi + 3);
        
        // 将 pVal 转回 3位二进制字符串
        $pCode = sprintf('%d%d%d', ($pVal & 1), ($pVal & 2) >> 1, ($pVal & 4) >> 2);
        $gong = self::BA_GUA[$pCode] ?? '乾';
        
        return [
            'gong' => $gong,
            'gong_wuxing' => self::BA_GUA_WUXING[$gong] ?? '金',
            'shi' => $shi,
            'ying' => $ying,
            'type' => $type
        ];
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
        $riGan = trim($riGan);
        if (mb_strlen($riGan, 'UTF-8') > 1) {
            $riGan = mb_substr($riGan, 0, 1, 'UTF-8');
        }

        // 日干对应起始六神
        $startLiuShen = [
            '甲' => '青龙', '乙' => '青龙',
            '丙' => '朱雀', '丁' => '朱雀',
            '戊' => '勾陈',
            '己' => '螣蛇',
            '庚' => '白虎', '辛' => '白虎',
            '壬' => '玄武', '癸' => '玄武',
        ];

        $liuShenOrder = self::LIU_SHEN;
        $start = $startLiuShen[$riGan] ?? '青龙';
        $startIndex = array_search($start, $liuShenOrder, true);
        if ($startIndex === false) {
            $startIndex = 0;
        }

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
    public static function getShiYing(string $guaName, string $yaoCode): array
    {
        $info = self::getGuaInfo($yaoCode);
        return ['shi' => $info['shi'], 'ying' => $info['ying']];
    }

    /**
     * 判断用神 (深度分析版)
     * 根据所问事项自动判断用神，并分析其在卦中的状态
     */
    public static function getYongShen(string $questionType, array $liuqin, array $shiYing, string $yaoCode, string $gender = '男', array $timeInfo = []): array
    {
        // 1. 基本映射
        $yongShenMap = [
            '求财' => '妻财',
            '事业' => '官鬼',
            '健康' => '世爻',
            '学业' => '父母',
            '出行' => '世爻',
            '失物' => '妻财',
            '官司' => '官鬼',
            '父母' => '父母',
            '子女' => '子孙',
        ];
        
        $targetLiuqin = $yongShenMap[$questionType] ?? '世爻';
        
        // 特殊处理：感情根据性别区分用神
        if ($questionType === '感情') {
            $targetLiuqin = ($gender === '女') ? '官鬼' : '妻财';
        }
        
        $xunkong = [];
        $selectionReason = '';
        if (!empty($timeInfo) && isset($timeInfo['ri_gan']) && isset($timeInfo['ri_zhi'])) {
            $xunkong = self::calculateXunKong($timeInfo['ri_gan'], $timeInfo['ri_zhi']);
        }

        // 特殊处理：如果用神是“世爻”，直接指向世位
        if ($targetLiuqin === '世爻') {
            $position = (int)($shiYing['shi'] ?? 1);
            $targetLiuqin = $liuqin[$position] ?? '世爻';
            $selectionReason = '此类事项以世爻为主，直取世位定用';
        } else {
            // 在六爻中寻找匹配的六亲位置
            $positions = [];
            foreach ($liuqin as $pos => $lq) {
                if ($lq === $targetLiuqin) {
                    $positions[] = $pos;
                }
            }

            // 如果没找到用神，则需寻找“伏神”
            if (empty($positions)) {
                $info = self::getGuaInfo($yaoCode);
                $fushen = self::getFuShen($info['gong'], $targetLiuqin);
                if ($fushen) {
                    $fushenStatus = ['伏藏'];
                    if (!empty($xunkong) && in_array($fushen['di_zhi'], $xunkong, true)) {
                        $fushenStatus[] = '旬空';
                    }

                    $hostLineLiuQin = $liuqin[$fushen['position']] ?? '未知';

                    return [
                        'liuqin' => $targetLiuqin,
                        'position' => $fushen['position'],
                        'di_zhi' => $fushen['di_zhi'],
                        'is_moving' => false,
                        'is_fushen' => true,
                        'fushen_info' => array_merge($fushen, [
                            'host_line_liuqin' => $hostLineLiuQin,
                            'xunkong' => $xunkong,
                        ]),
                        'xunkong' => $xunkong,
                        'status' => implode('、', $fushenStatus),
                        'description' => "用神【{$targetLiuqin}】不现，按卦宫伏神法取第{$fushen['position']}爻地支【{$fushen['di_zhi']}】，伏于本爻【{$hostLineLiuQin}】之下。状态：" . implode('、', $fushenStatus)
                    ];
                }

                $position = (int)($shiYing['ying'] ?? 1);
                $selectionReason = '本卦未见明现用神，且伏神未取到，暂借应位参看';
            } elseif (count($positions) > 1) {
                $selection = self::selectBestYongShenPosition($positions, $shiYing, $yaoCode, $xunkong);
                $position = $selection['position'];
                $selectionReason = $selection['reason'];
            } else {
                $position = (int)$positions[0];
                $selectionReason = '同类用神唯一现于此爻';
            }
        }

        
        // 2. 状态分析
        $yaoArray = str_split($yaoCode);
        $isMoving = ($yaoArray[$position - 1] == '0' || $yaoArray[$position - 1] == '3');
        
        $status = [];
        if ($position === $shiYing['shi']) $status[] = '持世';
        if ($position === $shiYing['ying']) $status[] = '在应位';
        if ($isMoving) $status[] = '发动';

        $currentZhi = '';
        if (!empty($timeInfo) && isset($timeInfo['ri_gan']) && isset($timeInfo['ri_zhi'])) {
            $yaoDiZhi = self::getYaoDiZhi(self::getGuaName($yaoCode), $yaoCode);
            $currentZhi = $yaoDiZhi[$position] ?? '';
            if ($currentZhi !== '' && in_array($currentZhi, $xunkong, true)) {
                $status[] = '旬空';
            }
        }

        return [
            'liuqin' => $targetLiuqin,
            'position' => $position,
            'di_zhi' => $currentZhi,
            'is_moving' => $isMoving,
            'is_fushen' => false,
            'xunkong' => $xunkong,
            'selection_reason' => $selectionReason,
            'status' => implode('、', $status),
            'description' => "以第{$position}爻【{$targetLiuqin}】为用神。状态： " . (implode('、', $status) ?: '安静') . ($selectionReason !== '' ? "。取用依据：{$selectionReason}" : '')
        ];
    }

    /**
     * 多重同类用神并见时，按动爻、世应、旬空综合取象。
     * 取象上遵循“舍静取动、世应为纲、空亡减力”的常见六爻判断次序。
     */

    protected static function selectBestYongShenPosition(array $positions, array $shiYing, string $yaoCode, array $xunkong = []): array
    {
        if (empty($positions)) {
            return [
                'position' => (int)($shiYing['ying'] ?? 1),
                'reason' => '候选爻位缺失，暂以应位代看',
            ];
        }

        $yaoArray = str_split($yaoCode);
        $yaoDiZhi = self::getYaoDiZhi(self::getGuaName($yaoCode), $yaoCode);
        $shiPos = (int)($shiYing['shi'] ?? 0);
        $yingPos = (int)($shiYing['ying'] ?? 0);
        $candidates = [];

        foreach ($positions as $position) {
            $position = (int)$position;
            $isMoving = isset($yaoArray[$position - 1]) && in_array($yaoArray[$position - 1], ['0', '3'], true);
            $diZhi = $yaoDiZhi[$position] ?? '';
            $isXunkong = $diZhi !== '' && in_array($diZhi, $xunkong, true);
            $score = 0;
            $reasons = [];

            if ($isMoving) {
                $score += 40;
                $reasons[] = '发动';
            }
            if ($position === $shiPos) {
                $score += 22;
                $reasons[] = '临世';
            }
            if ($position === $yingPos) {
                $score += 14;
                $reasons[] = '临应';
            }
            if ($isXunkong) {
                $score -= 8;
                $reasons[] = '旬空减力';
            } else {
                $score += 8;
                $reasons[] = '不空';
            }
            if ($shiPos > 0) {
                $score += max(0, 6 - abs($position - $shiPos));
            }

            $candidates[] = [
                'position' => $position,
                'score' => $score,
                'reasons' => $reasons,
            ];
        }

        usort($candidates, static function (array $left, array $right): int {
            $scoreCompare = $right['score'] <=> $left['score'];
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return $left['position'] <=> $right['position'];
        });

        $best = $candidates[0];
        $reasonText = !empty($best['reasons']) ? implode('、', $best['reasons']) : '先见';

        return [
            'position' => $best['position'],
            'reason' => '同类用神并见，优先取' . $reasonText . '之爻',
        ];
    }

    /**
     * 获取伏神信息

     * 当用神不现于本卦时，从所属卦宫的首卦中寻找
     */
    public static function getFuShen(string $gong, string $missingLiuqin): ?array
    {
        $pureGuaZhi = self::NA_JIA[$gong] ?? null;
        if (!$pureGuaZhi) return null;
        
        $gongWuxing = self::BA_GUA_WUXING[$gong];
        $diZhiWuxing = [
            '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
            '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
            '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水',
        ];

        for ($i = 0; $i < 6; $i++) {
            $zhi = ($i < 3) ? $pureGuaZhi['inner'][$i] : $pureGuaZhi['outer'][$i - 3];
            $lq = self::getLiuQinByWuxing($gongWuxing, $diZhiWuxing[$zhi]);
            if ($lq === $missingLiuqin) {
                return [
                    'position' => $i + 1,
                    'di_zhi' => $zhi,
                    'liuqin' => $lq
                ];
            }
        }
        return null;
    }

    /**
     * 计算旬空
     */
    public static function calculateXunKong(string $riGan, string $riZhi): array
    {
        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        
        $gIdx = array_search($riGan, $tianGan);
        $zIdx = array_search($riZhi, $diZhi);
        if ($gIdx === false || $zIdx === false) return [];
        
        $xunShouZhiIdx = ($zIdx - $gIdx + 12) % 12;
        $kong1Idx = ($xunShouZhiIdx + 10) % 12;
        $kong2Idx = ($xunShouZhiIdx + 11) % 12;
        
        return [$diZhi[$kong1Idx], $diZhi[$kong2Idx]];
    }

    /**
     * 时间起卦法
     * 根据当前时间（农历年月日时干支分值）起卦
     * 
     * @param int $year 年
     * @param int $month 月
     * @param int $day 日
     * @param int $hour 时（0-23）
     * @return array 起卦结果
     */
    public static function qiGuaByTime(int $year, int $month, int $day, int $hour): array
    {
        // 1. 处理晚子时 (23:00-00:00)
        // 在梅花易数中，23点后起卦应按次日计算
        $calcDate = "$year-$month-$day";
        if ($hour >= 23) {
            $dt = new \DateTime($calcDate);
            $dt->modify('+1 day');
            $calcDate = $dt->format('Y-m-d');
        }

        // 2. 转换为农历分值
        $lunar = LunarService::solarToLunar($calcDate);
        $yearNum = $lunar['year_zhi_index'];
        $monthNum = $lunar['lunar_month'];
        $dayNum = $lunar['lunar_day'];
        
        // 时辰支数计算 (子=1, 丑=2, ..., 亥=12)
        $hourNum = (int)(($hour + 1) / 2) % 12 + 1;
        // 23:00-01:00 均为子时(1)
        if ($hour == 23) $hourNum = 1;

        // 3. 计算卦数 (先天八卦数)
        // 上卦 = (年支数 + 农历月 + 农历日) % 8
        $shangGuaNum = ($yearNum + $monthNum + $dayNum) % 8;
        if ($shangGuaNum == 0) $shangGuaNum = 8;
        
        // 下卦 = (年支数 + 农历月 + 农历日 + 时支数) % 8
        $xiaGuaNum = ($yearNum + $monthNum + $dayNum + $hourNum) % 8;
        if ($xiaGuaNum == 0) $xiaGuaNum = 8;
        
        // 动爻 = (年支数 + 农历月 + 农历日 + 时支数) % 6
        $dongYao = ($yearNum + $monthNum + $dayNum + $hourNum) % 6;
        if ($dongYao == 0) $dongYao = 6;
        
        // 4. 生成卦象
        $shangGua = self::getBaGuaByNum($shangGuaNum);
        $xiaGua = self::getBaGuaByNum($xiaGuaNum);
        
        $yaoCode = self::generateYaoCode($shangGua, $xiaGua, $dongYao);
        
        return [
            'method' => 'time',
            'lunar_info' => [
                'year_num' => $yearNum,
                'month_num' => $monthNum,
                'day_num' => $dayNum,
                'hour_num' => $hourNum,
                'is_late_zi' => ($hour >= 23),
            ],
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
        $binaryCode = $xiaCode . $shangCode;

        // 先把静爻编码成：阳=1（少阳），阴=2（少阴）
        // 再将指定动爻转成：老阴=0，老阳=3
        $yaoArray = [];
        foreach (str_split($binaryCode) as $yao) {
            $yaoArray[] = $yao === '1' ? '1' : '2';
        }

        $position = max(0, min(5, $dongYao - 1));
        $yaoArray[$position] = $binaryCode[$position] === '0' ? '0' : '3';

        return implode('', $yaoArray);
    }
}
