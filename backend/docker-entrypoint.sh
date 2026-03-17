#!/bin/bash

ENV_FILE="/var/www/html/.env"

# 如果 .env 不存在，从 .env.example 复制
if [ ! -f "$ENV_FILE" ]; then
    cp /var/www/html/.env.example "$ENV_FILE"
fi

# 去除 Windows CRLF，避免 sed/grep 匹配失败
sed -i 's/\r//' "$ENV_FILE"

# 用容器环境变量覆盖 .env 中对应的值
patch_env() {
    local key="$1"
    local val="$2"
    if [ -n "$val" ]; then
        if grep -qE "^${key}" "$ENV_FILE"; then
            sed -i "s|^${key}.*|${key} = ${val}|" "$ENV_FILE"
        else
            echo "${key} = ${val}" >> "$ENV_FILE"
        fi
    fi
}

patch_env "DB_HOST"     "${DB_HOST:-}"
patch_env "DB_PORT"     "${DB_PORT:-}"
patch_env "DB_NAME"     "${DB_NAME:-}"
patch_env "DB_USER"     "${DB_USER:-}"
patch_env "DB_PASSWORD" "${DB_PASSWORD:-}"
patch_env "JWT_SECRET"  "${JWT_SECRET:-}"

# 如果 JWT_SECRET 仍为空，自动生成一个
JWT_VAL=$(grep "^JWT_SECRET" "$ENV_FILE" | sed 's/^JWT_SECRET[^=]*=//' | tr -d ' \r\n')
if [ -z "$JWT_VAL" ]; then
    NEW_SECRET=$(php -r 'echo base64_encode(random_bytes(32));')
    sed -i "s|^JWT_SECRET.*|JWT_SECRET = ${NEW_SECRET}|" "$ENV_FILE"
    echo "[entrypoint] JWT_SECRET auto-generated."
fi

echo "[entrypoint] .env configured. DB_HOST=$(grep '^DB_HOST' $ENV_FILE)"
exec apache2-foreground

