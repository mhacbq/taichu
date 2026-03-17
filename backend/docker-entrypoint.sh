#!/bin/bash
set -e

ENV_FILE="/var/www/html/.env"

# 如果 .env 不存在，从 .env.example 复制
if [ ! -f "$ENV_FILE" ]; then
    cp /var/www/html/.env.example "$ENV_FILE"
fi

# 用容器环境变量覆盖 .env 中对应的值
patch_env() {
    local key="$1"
    local val="$2"
    if [ -n "$val" ]; then
        if grep -qE "^${key}\s*=" "$ENV_FILE"; then
            sed -i "s|^${key}\s*=.*|${key} = ${val}|" "$ENV_FILE"
        else
            echo "${key} = ${val}" >> "$ENV_FILE"
        fi
    fi
}

patch_env "DB_HOST"     "$DB_HOST"
patch_env "DB_PORT"     "$DB_PORT"
patch_env "DB_NAME"     "$DB_NAME"
patch_env "DB_USER"     "$DB_USER"
patch_env "DB_PASSWORD" "$DB_PASSWORD"
patch_env "JWT_SECRET"  "$JWT_SECRET"

# 确保 JWT_SECRET 不为空
if ! grep -qE "^JWT_SECRET\s*=\s*.+" "$ENV_FILE"; then
    SECRET=$(php -r 'echo base64_encode(random_bytes(32));')
    sed -i "s|^JWT_SECRET\s*=.*|JWT_SECRET = ${SECRET}|" "$ENV_FILE"
fi

exec apache2-foreground

