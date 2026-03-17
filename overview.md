# 太初项目统一版 - 2026-03-17 状态概览

## 最近更新

### 命理算法修复（本轮）
- **八字历法内核统一**：修复 `backend/app/controller/Paipan.php` 的旧版节气与日柱分叉逻辑。
  - `getJieQiDate()` 补齐 **20 世纪/21 世纪寿星公式常数** 与特殊年修正。
  - `calculateDayPillar()`、`calculateBazi()` 改为统一委托 `BaziCalculationService`，避免控制器继续使用旧基准日算法。
- **六爻伏神链路补全**：修复 `backend/app/controller/Liuyao.php` 与 `backend/app/service/LiuyaoService.php`。
  - 起卦时不再默认“甲日”，会自动推算当日干支，校验 `ri_gan/ri_zhi`。
  - 用神不现时会按卦宫首卦回退伏神，并补出伏神地支、宿主爻、旬空状态。
- **八字强弱评分深化**：修复 `backend/app/service/BaziCalculationService.php` 与 `backend/app/service/BaziInterpretationService.php`。
  - 在原“月令/藏干/透干”基础上，新增 **六冲、六合、三合局** 对日主力量的加减分。
  - 喜用神文案直接复用核心强弱评分结果，避免解读层与排盘层口径漂移。
- **塔罗元素术语回正**：修复 `backend/app/controller/Tarot.php` 的元素互动文案，回归 `Elemental Dignities / Friendly / Enemy / Neutral` 术语，不再使用“五行化”表达。
- **TODO 清理**：已从 `TODO.md` 删除本轮完成的 5 项 `[占卜]` 条目：
  1. 20世纪节气计算常数缺失
  2. 日柱计算算法不统一
  3. 缺失“伏神”系统
  4. 塔罗元素互动术语中式化
  5. 强弱分析未计入地支冲合

## 本轮涉及文件
- `backend/app/controller/Paipan.php`
- `backend/app/controller/Liuyao.php`
- `backend/app/controller/Tarot.php`
- `backend/app/service/BaziCalculationService.php`
- `backend/app/service/BaziInterpretationService.php`
- `backend/app/service/LiuyaoService.php`
- `TODO.md`

## 验证情况
- 已对上述变更文件执行 **IDE/LSP 诊断检查**：未发现新增语法或静态错误。
- 已人工复核关键差异点：
  - `Paipan` 不再保留旧版日柱计算主链路。
  - `Liuyao` 已把日辰上下文传入用神判断。
  - `BaziInterpretationService` 已改为消费 `BaziCalculationService::analyzeStrength()` 的结果。
- **运行态验证说明**：当前环境未找到可直接调用的 `php` CLI，因此未执行命令行级的 PHP 回归脚本；后续如补齐本机 PHP 路径，建议追加一轮样例盘校验。

## 当前仍待处理的占卜项
- [ ] 命卦计算忽略立春划分
- [ ] 缺失“旬空”提示
- [ ] 塔罗逆位牌义支持不均
