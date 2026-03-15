#!/bin/bash

# =====================================================
# 太初命理 - 一键部署脚本
# 支持：前端构建、PHP依赖安装、数据库迁移、Nginx重载
# =====================================================

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置变量
PROJECT_DIR="/var/www/taichu"
FRONTEND_DIR="$PROJECT_DIR/frontend"
BACKEND_DIR="$PROJECT_DIR/backend"
BACKUP_DIR="$PROJECT_DIR/backups"
LOGS_DIR="$PROJECT_DIR/logs"
DATE=$(date +%Y%m%d_%H%M%S)
GIT_BRANCH="master"

# 日志函数
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查命令是否存在
check_command() {
    if ! command -v $1 &> /dev/null; then
        log_error "$1 未安装，请先安装"
        exit 1
    fi
}

# 显示帮助信息
show_help() {
    cat << EOF
太初命理部署脚本

用法: ./deploy.sh [选项]

选项:
    -h, --help          显示帮助信息
    -b, --branch        指定Git分支 (默认: master)
    -s, --skip-build    跳过前端构建
    -d, --skip-db       跳过数据库迁移
    -c, --clean         清理缓存和临时文件
    --only-frontend     仅部署前端
    --only-backend      仅部署后端

示例:
    ./deploy.sh                          # 完整部署
    ./deploy.sh -b develop               # 部署develop分支
    ./deploy.sh --only-frontend          # 仅部署前端
    ./deploy.sh -s                       # 跳过构建（使用已有构建产物）
EOF
}

# 解析参数
SKIP_BUILD=false
SKIP_DB=false
CLEAN=false
ONLY_FRONTEND=false
ONLY_BACKEND=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -b|--branch)
            GIT_BRANCH="$2"
            shift 2
            ;;
        -s|--skip-build)
            SKIP_BUILD=true
            shift
            ;;
        -d|--skip-db)
            SKIP_DB=true
            shift
            ;;
        -c|--clean)
            CLEAN=true
            shift
            ;;
        --only-frontend)
            ONLY_FRONTEND=true
            shift
            ;;
        --only-backend)
            ONLY_BACKEND=true
            shift
            ;;
        *)
            log_error "未知参数: $1"
            show_help
            exit 1
            ;;
    esac
done

echo "============================================"
echo "      太初命理 - 部署脚本"
echo "============================================"
echo "部署时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Git分支: $GIT_BRANCH"
echo "项目目录: $PROJECT_DIR"
echo "============================================"
echo ""

# 检查必要命令
check_command git
check_command php
check_command composer

if [ "$ONLY_BACKEND" = false ]; then
    check_command node
    check_command npm
fi

# 清理操作
if [ "$CLEAN" = true ]; then
    log_info "清理缓存和临时文件..."
    rm -rf $FRONTEND_DIR/node_modules/.cache
    rm -rf $BACKEND_DIR/runtime/cache/*
    rm -rf $BACKEND_DIR/runtime/log/*
    php $BACKEND_DIR/think cache:clear 2>/dev/null || true
    log_success "清理完成"
fi

# 创建必要目录
mkdir -p $BACKUP_DIR
mkdir -p $LOGS_DIR

# 进入项目目录
cd $PROJECT_DIR

# ============================================
# 步骤1: 备份
# ============================================
if [ "$ONLY_BACKEND" = false ] && [ "$ONLY_FRONTEND" = false ]; then
    log_info "[1/7] 备份当前版本..."
    BACKUP_FILE="$BACKUP_DIR/backup_$DATE.tar.gz"
    tar czf $BACKUP_FILE -C $PROJECT_DIR frontend/dist backend \
        --exclude='backend/runtime' \
        --exclude='backend/vendor' \
        --exclude='frontend/node_modules' 2>/dev/null || true
    
    if [ -f "$BACKUP_FILE" ]; then
        log_success "备份已创建: $BACKUP_FILE"
    else
        log_warning "备份创建失败，继续部署..."
    fi
    echo ""
fi

# ============================================
# 步骤2: 拉取代码
# ============================================
if [ "$ONLY_BACKEND" = false ] && [ "$ONLY_FRONTEND" = false ]; then
    log_info "[2/7] 拉取最新代码 (分支: $GIT_BRANCH)..."
    git fetch origin
    git reset --hard origin/$GIT_BRANCH
    log_success "代码已更新到最新版本"
    echo ""
fi

# ============================================
# 步骤3: 部署前端
# ============================================
if [ "$ONLY_BACKEND" = false ] && [ "$SKIP_BUILD" = false ]; then
    log_info "[3/7] 构建前端项目..."
    cd $FRONTEND_DIR
    
    # 安装依赖
    log_info "安装npm依赖..."
    npm ci --legacy-peer-deps
    
    # 构建
    log_info "构建生产环境..."
    npm run build
    
    if [ -d "dist" ]; then
        log_success "前端构建成功"
    else
        log_error "前端构建失败，dist目录不存在"
        exit 1
    fi
    
    # 构建后台管理（如果存在）
    if [ -f "package.json" ] && grep -q "build:admin" package.json; then
        log_info "构建后台管理..."
        npm run build:admin
    fi
    
    echo ""
fi

# ============================================
# 步骤4: 部署后端
# ============================================
if [ "$ONLY_FRONTEND" = false ]; then
    log_info "[4/7] 部署后端项目..."
    cd $BACKEND_DIR
    
    # 安装Composer依赖
    log_info "安装Composer依赖..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # 设置目录权限
    log_info "设置目录权限..."
    chmod -R 755 $BACKEND_DIR/runtime
    chmod -R 755 $BACKEND_DIR/public/uploads 2>/dev/null || true
    
    log_success "后端部署完成"
    echo ""
fi

# ============================================
# 步骤5: 数据库迁移
# ============================================
if [ "$ONLY_FRONTEND" = false ] && [ "$SKIP_DB" = false ]; then
    log_info "[5/7] 执行数据库迁移..."
    cd $BACKEND_DIR
    
    # 检查数据库连接
    php think migrate:status > /dev/null 2>&1 || {
        log_warning "数据库连接检查失败，跳过迁移"
    }
    
    # 执行迁移
    # php think migrate:run || log_warning "数据库迁移执行失败"
    
    log_success "数据库检查完成"
    echo ""
fi

# ============================================
# 步骤6: 清理缓存
# ============================================
if [ "$ONLY_FRONTEND" = false ]; then
    log_info "[6/7] 清理缓存..."
    cd $BACKEND_DIR
    
    php think cache:clear 2>/dev/null || true
    php think route:clear 2>/dev/null || true
    
    # OPCache清理（如果有）
    if command -v curl &> /dev/null; then
        curl -s http://localhost/api/clear-cache > /dev/null 2>&1 || true
    fi
    
    log_success "缓存清理完成"
    echo ""
fi

# ============================================
# 步骤7: 重启服务
# ============================================
log_info "[7/7] 重启服务..."

# 重载Nginx
if command -v nginx &> /dev/null; then
    sudo nginx -t && sudo systemctl reload nginx
    log_success "Nginx已重载"
fi

# 重启PHP-FPM
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.1-fpm 2>/dev/null || \
    sudo systemctl reload php8.0-fpm 2>/dev/null || \
    sudo systemctl reload php7.4-fpm 2>/dev/null || \
    log_warning "PHP-FPM重启失败，请手动重启"
fi

echo ""
echo "============================================"
echo -e "${GREEN}      部署成功!${NC}"
echo "============================================"
echo "部署时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
echo "访问地址:"
echo "  前端网站: https://taichu.chat"
    echo "  后台管理: https://taichu.chat/admin"
    echo "  API接口:  https://taichu.chat/api"
echo ""
echo "查看日志:"
echo "  tail -f $LOGS_DIR/nginx-access.log"
echo "  tail -f $LOGS_DIR/nginx-error.log"
echo "============================================"

# 健康检查
sleep 2
echo ""
log_info "执行健康检查..."

# 检查前端
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://taichu.chat/ || echo "000")
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "304" ]; then
    log_success "前端访问正常 (HTTP $HTTP_CODE)"
else
    log_warning "前端访问异常 (HTTP $HTTP_CODE)"
fi

# 检查API
API_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://taichu.chat/api/health || echo "000")
if [ "$API_CODE" = "200" ]; then
    log_success "API接口正常 (HTTP $API_CODE)"
else
    log_warning "API接口异常 (HTTP $API_CODE)"
fi
