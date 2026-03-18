<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Shensha as ShenshaModel;
use app\service\DisplayTextRepairService;

/**
 * 神煞管理控制器
 */
class Shensha extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取神煞列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看神煞', 403);
        }

        try {
            $page = max(1, (int) $this->request->param('page', 1));
            $pageSize = max(1, min(100, (int) $this->request->param('pageSize', 20)));
            $keyword = trim((string) $this->request->param('keyword', ''));
            $type = trim((string) $this->request->param('type', ''));
            $category = trim((string) $this->request->param('category', ''));
            $statusParam = $this->request->param('status', '');

            $query = ShenshaModel::order('sort', 'asc')->order('id', 'asc');

            if ($keyword !== '') {
                $query->whereLike('name|description|effect', "%{$keyword}%");
            }

            if ($type !== '') {
                $query->where('type', $type);
            }

            if ($category !== '') {
                $query->where('category', $category);
            }

            if ($statusParam !== '' && $statusParam !== null) {
                $status = (int) $statusParam;
                if (!in_array($status, [0, 1], true)) {
                    return $this->error('状态值无效', 400);
                }
                $query->where('status', $status);
            }

            $total = $query->count();
            $rows = $query->page($page, $pageSize)->select()->toArray();
            $list = array_map([$this, 'normalizeShenshaRow'], $rows);

            return $this->success([
                'total' => $total,
                'list' => $list,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_index', $e, '获取神煞列表失败，请稍后重试', [
                'page' => $this->request->param('page', 1),
                'page_size' => $this->request->param('pageSize', 20),
                'keyword' => trim((string) $this->request->param('keyword', '')),
                'type' => trim((string) $this->request->param('type', '')),
                'category' => trim((string) $this->request->param('category', '')),
                'status' => $this->request->param('status', ''),
            ]);
        }
    }

    /**
     * 获取神煞筛选项
     */
    public function options()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看神煞筛选项', 403);
        }

        try {
            $types = ShenshaModel::where('type', '<>', '')
                ->distinct(true)
                ->order('type', 'asc')
                ->column('type');
            $categories = ShenshaModel::where('category', '<>', '')
                ->distinct(true)
                ->order('category', 'asc')
                ->column('category');

            return $this->success([
                'types' => $this->normalizeOptionValues($types),
                'categories' => $this->normalizeOptionValues($categories),
                'statuses' => [
                    ['label' => '启用', 'value' => 1],
                    ['label' => '停用', 'value' => 0],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_options', $e, '获取神煞筛选项失败，请稍后重试');
        }
    }

    /**
     * 保存/更新神煞
     */
    public function save()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑神煞', 403);
        }

        $data = $this->request->isPut() ? $this->request->put() : $this->request->post();
        if (!is_array($data)) {
            $data = [];
        }

        try {
            $id = (int) ($data['id'] ?? $this->request->param('id', 0));
            $isUpdate = $id > 0;
            $nameProvided = array_key_exists('name', $data);
            $name = trim((string) ($data['name'] ?? ''));

            if (!$isUpdate && $name === '') {
                return $this->error('神煞名称不能为空', 400);
            }

            if ($nameProvided) {
                if ($name === '') {
                    return $this->error('神煞名称不能为空', 400);
                }
                $data['name'] = $name;
            }

            if (array_key_exists('type', $data)) {
                $data['type'] = trim((string) $data['type']);
            }

            if (array_key_exists('category', $data)) {
                $data['category'] = trim((string) $data['category']);
            }

            if (array_key_exists('description', $data)) {
                $data['description'] = trim((string) $data['description']);
            }

            if (array_key_exists('effect', $data)) {
                $data['effect'] = trim((string) $data['effect']);
            }

            if (array_key_exists('status', $data)) {
                $status = (int) $data['status'];
                if (!in_array($status, [0, 1], true)) {
                    return $this->error('状态值无效', 400);
                }
                $data['status'] = $status;
            }

            $fallbackCheckRule = $this->buildFallbackCheckRule(
                $name,
                (string) ($data['description'] ?? ''),
                (string) ($data['effect'] ?? '')
            );
            if (array_key_exists('check_rule', $data)) {
                $data['check_rule'] = $this->normalizeOptionalTextField($data['check_rule'], $fallbackCheckRule);
            } elseif (!$isUpdate) {
                $data['check_rule'] = $fallbackCheckRule;
            }

            if (array_key_exists('check_code', $data)) {
                $data['check_code'] = $this->normalizeOptionalTextField($data['check_code']);
            }

            $data['id'] = $id;

            if ($isUpdate) {
                $model = ShenshaModel::find($id);
                if (!$model) {
                    return $this->error('记录不存在', 404);
                }
                unset($data['id']);
            } else {
                unset($data['id']);
                $model = new ShenshaModel();
            }

            foreach (['gan_rules', 'zhi_rules'] as $field) {
                if (!array_key_exists($field, $data)) {
                    continue;
                }

                $parsedValue = $this->decodeJsonField($data[$field], $field);
                if ($parsedValue === null) {
                    unset($data[$field]);
                    continue;
                }

                $data[$field] = $parsedValue;
            }

            $model->save($data);

            return $this->success($this->normalizeShenshaRow($model->fresh()->toArray()), '保存成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'shensha_save_validation', '神煞数据格式无效', 400, [
                'id' => (int) ($data['id'] ?? 0),
                'name' => trim((string) ($data['name'] ?? '')),
                'type' => trim((string) ($data['type'] ?? '')),
                'category' => trim((string) ($data['category'] ?? '')),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_save', $e, '保存神煞失败，请稍后重试', [
                'id' => (int) ($data['id'] ?? 0),
                'name' => trim((string) ($data['name'] ?? '')),
                'type' => trim((string) ($data['type'] ?? '')),
                'category' => trim((string) ($data['category'] ?? '')),
            ]);
        }
    }

    /**
     * 删除神煞
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除神煞', 403);
        }

        try {
            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在', 404);
            }

            $model->delete();

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_delete', $e, '删除神煞失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * 更新状态
     */
    public function toggleStatus()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限更新神煞状态', 403);
        }

        try {
            $id = (int) $this->request->post('id');
            $status = (int) $this->request->post('status');

            if (!in_array($status, [0, 1], true)) {
                return $this->error('状态值无效', 400);
            }

            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在', 404);
            }

            $model->status = $status;
            $model->save();

            return $this->success([], '状态更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_toggle_status', $e, '更新神煞状态失败，请稍后重试', [
                'id' => (int) $this->request->post('id'),
                'status' => (int) $this->request->post('status'),
            ]);
        }
    }

    protected function normalizeShenshaRow(array $row): array
    {
        $fallback = $this->resolveShenshaFallback($row);

        return array_merge($row, [
            'id' => (int) ($row['id'] ?? 0),
            'name' => DisplayTextRepairService::normalizeText($row['name'] ?? '', $fallback['name'] ?? ''),
            'type' => trim((string) ($row['type'] ?? '')),
            'category' => trim((string) ($row['category'] ?? '')),
            'description' => DisplayTextRepairService::normalizeText($row['description'] ?? '', $fallback['description'] ?? ''),
            'effect' => DisplayTextRepairService::normalizeText($row['effect'] ?? '', $fallback['effect'] ?? ''),
            'check_rule' => DisplayTextRepairService::normalizeText($row['check_rule'] ?? '', $fallback['check_rule'] ?? ''),
            'check_code' => trim((string) ($row['check_code'] ?? '')),
            'sort' => (int) ($row['sort'] ?? 0),
            'status' => (int) ($row['status'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ]);
    }

    protected function resolveShenshaFallback(array $row): array
    {
        $sort = (int) ($row['sort'] ?? 0);
        $type = trim((string) ($row['type'] ?? ''));
        $category = trim((string) ($row['category'] ?? ''));
        $key = $sort . '|' . $type . '|' . $category;

        $fallbackMap = $this->getDefaultShenshaFallbackMap();
        return $fallbackMap[$key] ?? [];
    }

    protected function getDefaultShenshaFallbackMap(): array
    {
        return [
            '1|daji|guiren' => [
                'name' => '天乙贵人',
                'description' => '最吉之神，命中逢之，遇事有人帮，遇危难有人救',
                'effect' => '遇难成祥，逢凶化吉，人缘极佳，易得他人帮助',
                'check_rule' => '甲戊见牛羊，乙己鼠猴乡，丙丁猪鸡位，壬癸兔蛇藏，庚辛逢虎马，此是贵人方',
            ],
            '2|ji|xueye' => [
                'name' => '文昌贵人',
                'description' => '主聪明好学，利文途考学',
                'effect' => '聪明过人，学业有成，考试顺利，利于文职',
                'check_rule' => '甲乙巳午报君知，丙戊申宫丁己鸡，庚猪辛鼠壬逢虎，癸人见卯入云梯',
            ],
            '3|ji|guiren' => [
                'name' => '太极贵人',
                'description' => '主人聪明好学，喜神秘文化',
                'effect' => '悟性高，对命理、宗教、玄学有兴趣，逢凶化吉',
                'check_rule' => '甲乙生人子午中，丙丁鸡兔定亨通，戊己两干临四季，庚辛寅亥禄丰隆，壬癸巳申偏喜美',
            ],
            '4|daji|guiren' => [
                'name' => '天德贵人',
                'description' => '天地德秀之气，逢凶化吉之神',
                'effect' => '一生安逸，不犯刑律，不遇凶祸，福气好',
                'check_rule' => '正丁二坤宫，三壬四辛同，五乾六甲上，七癸八艮逢，九丙十居乙，子巽丑庚中',
            ],
            '5|daji|guiren' => [
                'name' => '月德贵人',
                'description' => '乃太阴之德，功能与天德略同而稍逊',
                'effect' => '逢凶化吉，灾少福多，一生少病痛',
                'check_rule' => '寅午戌月在丙，申子辰月在壬，亥卯未月在甲，巳酉丑月在庚',
            ],
            '6|ji|guiren' => [
                'name' => '福星贵人',
                'description' => '主人一生福禄无缺，格局配合得当，必然多福多寿',
                'effect' => '一生福禄无缺，享福深厚，平安幸福',
                'check_rule' => '甲丙相邀入虎乡，更游鼠穴最高强，戊猴己未丁宜亥，乙癸逢牛卯禄昌，庚赶马头辛到巳，壬骑龙背喜非常',
            ],
            '7|ping|ganqing' => [
                'name' => '桃花',
                'description' => '主人漂亮多情，风流潇洒',
                'effect' => '人缘好，异性缘佳，感情丰富，但可能感情复杂',
                'check_rule' => '申子辰在酉，巳酉丑在午，亥卯未在子，寅午戌在卯',
            ],
            '8|xiong|jiankang' => [
                'name' => '羊刃',
                'description' => '司刑之星，性情刚强',
                'effect' => '性格刚烈，易有刀伤手术，但也代表能力强',
                'check_rule' => '甲刃在卯，乙刃在寅，丙戊刃在午，丁己刃在巳，庚刃在酉，辛刃在申，壬刃在子，癸刃在亥',
            ],
            '9|xiong|caiyun' => [
                'name' => '劫煞',
                'description' => '主破财、阻碍',
                'effect' => '破财、阻碍、是非、意外',
                'check_rule' => '申子辰见巳，亥卯未见申，寅午戌见亥，巳酉丑见寅',
            ],
            '10|xiong|ganqing' => [
                'name' => '孤辰',
                'description' => '主孤独，不利婚姻',
                'effect' => '孤独，少依靠，婚姻不顺，与亲人缘薄',
                'check_rule' => '亥子丑人，见寅为孤辰，见戌为寡宿',
            ],
            '11|xiong|ganqing' => [
                'name' => '寡宿',
                'description' => '主孤独，不利婚姻',
                'effect' => '孤独，婚姻不顺，女命尤其注意',
                'check_rule' => '亥子丑人，见戌为寡宿，见寅为孤辰',
            ],
            '12|xiong|ganqing' => [
                'name' => '阴差阳错',
                'description' => '主婚姻不顺',
                'effect' => '婚姻不利，夫妻不和，男克妻女克夫',
                'check_rule' => '丙子、丁丑、戊寅、辛卯、壬辰、癸巳、丙午、丁未、戊申、辛酉、壬戌、癸亥',
            ],
            '13|xiong|caiyun' => [
                'name' => '十恶大败',
                'description' => '主不善理财，花钱大手大脚',
                'effect' => '不善理财，花钱如流水，难以积蓄',
                'check_rule' => '甲辰、乙巳、丙申、丁亥、戊戌、己丑、庚辰、辛巳、壬申、癸亥',
            ],
            '14|ji|caiyun' => [
                'name' => '金舆',
                'description' => '主富贵，聪明富贵',
                'effect' => '聪明富贵，性柔貌愿，举止温和',
                'check_rule' => '甲龙乙蛇丙戊羊，丁己猴歌庚犬方，辛猪壬牛癸逢虎',
            ],
            '15|ping|guiren' => [
                'name' => '华盖',
                'description' => '主聪明好学，喜艺术、宗教',
                'effect' => '聪明好学，喜艺术、玄学、宗教，有出世思想',
                'check_rule' => '寅午戌见戌，巳酉丑见丑，申子辰见辰，亥卯未见未',
            ],
        ];
    }

    protected function buildFallbackCheckRule(string $name, string $description = '', string $effect = ''): string
    {
        foreach ([$description, $effect] as $candidate) {
            $normalized = trim($candidate);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return $name !== '' ? sprintf('%s 的查法规则待补充', $name) : '查法规则待补充';
    }

    protected function normalizeOptionalTextField(mixed $value, string $fallback = ''): string
    {
        $normalized = trim((string) $value);
        return $normalized !== '' ? $normalized : $fallback;
    }

    /**
     * 解析 JSON 字段
     */
    protected function decodeJsonField(mixed $value, string $field): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \InvalidArgumentException(sprintf('%s 字段不是合法的 JSON 数组', $field));
        }

        return $decoded;
    }

    /**
     * 归一化下拉选项值
     */
    protected function normalizeOptionValues(array $values): array
    {
        $normalized = array_map(static fn ($value) => trim((string) $value), $values);
        $normalized = array_filter($normalized, static fn (string $value) => $value !== '');

        return array_values(array_unique($normalized));
    }
}
