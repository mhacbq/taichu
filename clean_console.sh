#!/bin/bash
# 前端Console语句清理脚本

set -e

echo "========================================"
echo "  前端Console语句清理脚本"
echo "  项目路径：/data/workspace/taichu"
echo "========================================"
echo ""

# 备份当前目录
BACKUP_DIR="backup_console_$(date +%Y%m%d_%H%M%S)"
echo "📦 备份当前代码到: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
cp -r frontend "$BACKUP_DIR/"
echo "✅ 备份完成"
echo ""

# 定义需要清理的文件和行号
declare -A cleanup_files=(
  ["router/index.js"]="264,364"
  ["src/utils/analytics.js"]="420"
  ["src/utils/cache.js"]="31,65,80,97"
  ["src/utils/tracker.js"]="12"
  ["src/components/TarotShare.vue"]="161"
  ["src/views/Vip.vue"]="131"
  ["src/views/Hehun.vue"]="1005,1019,1029,1885,1946,2018,2126,2297"
  ["src/views/Help.vue"]="191"
  ["src/views/Login.vue"]="161"
  ["src/views/Profile.vue"]="410"
  ["src/views/Qiming.vue"]="160"
  ["src/views/Recharge.vue"]="220"
  ["src/views/Home.vue"]="800,831"
  ["src/views/Liuyao.vue"]="923"
  ["src/views/Bazi.vue"]="2027,2052,2069,2074,2156,2185,2211,2323,2615"
  ["src/views/Daily.vue"]="739"
  ["src/views/Tarot.vue"]="411"
  ["src/App.vue"]="418,460"
)

# 统计信息
total_files=0
total_statements=0

echo "🧹 开始清理Console语句..."
echo ""

# 遍历需要清理的文件
for file in "${!cleanup_files[@]}"; do
  filepath="frontend/$file"

  # 检查文件是否存在
  if [ ! -f "$filepath" ]; then
    echo "⚠️  文件不存在: $filepath"
    continue
  fi

  # 获取行号列表
  lines="${cleanup_files[$file]}"
  IFS=',' read -ra line_array <<< "$lines"

  # 计算需要删除的行数
  file_lines=${#line_array[@]}
  total_statements=$((total_statements + file_lines))
  total_files=$((total_files + 1))

  echo "📝 处理文件: $filepath ($file_lines 条)"

  # 按行号从大到小排序，避免删除后行号偏移
  sorted_lines=($(printf "%s\n" "${line_array[@]}" | sort -rn))

  # 删除指定行
  for line_num in "${sorted_lines[@]}"; do
    sed -i "${line_num}d" "$filepath"
  done

  echo "   ✅ 已删除 $file_lines 条console语句"
done

echo ""
echo "========================================"
echo "  清理统计"
echo "========================================"
echo "📊 处理文件数：$total_files"
echo "📊 清理语句数：$total_statements"
echo "📦 备份位置：/$BACKUP_DIR"
echo ""

# 验证是否还有残留
echo "🔍 验证清理结果..."
remaining=$(find frontend/src -type f \( -name "*.vue" -o -name "*.js" \) \
  ! -path "*/tests/*" \
  ! -path "*/scripts/*" \
  -exec grep -l "console\.\(log\|error\|warn\|debug\|info\)" {} + 2>/dev/null | \
  grep -v "ErrorBoundary.vue" | wc -l)

if [ "$remaining" -eq 0 ]; then
  echo "✅ 所有生产代码中的console语句已清理完毕！"
else
  echo "⚠️  还有 $remaining 个文件包含console语句，请手动检查"
fi

echo ""
echo "========================================"
echo "  后续步骤"
echo "========================================"
echo "1. 检查代码：cd frontend && npm run build"
echo "2. 如果构建失败，恢复备份：cp -r ../$BACKUP_DIR/frontend ."
echo "3. 如果构建成功，可以删除备份：rm -rf ../$BACKUP_DIR"
echo "========================================"
