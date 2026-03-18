#!/bin/bash

set -e

ENV_FILE="/var/www/html/.env"
BOOTSTRAP_SQL_DIR="${BOOTSTRAP_SQL_DIR:-/var/www/bootstrap-sql}"
BOOTSTRAP_SQL_FILES=(
    "20260317_create_admin_users_table.sql"
    "20260317_create_shensha_table.sql"
    "20260317_create_knowledge_tables.sql"
    "20260318_create_almanac_table.sql"
    "20260318_create_seo_tables.sql"
    "20260318_add_points_record_audit_fields.sql"
    "20260318_fix_knowledge_category_encoding.sql"
)

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

wait_for_db() {
    local host="$1"
    local port="$2"
    local user="$3"
    local password="$4"

    for _ in $(seq 1 30); do
        if MYSQL_PWD="$password" mysqladmin ping -h "$host" -P "$port" -u "$user" --silent >/dev/null 2>&1; then
            return 0
        fi
        sleep 2
    done

    return 1
}

run_bootstrap_sql() {
    local host="${DB_HOST:-127.0.0.1}"
    local port="${DB_PORT:-3306}"
    local database="${DB_NAME:-taichu}"
    local user="${DB_USER:-root}"
    local password="${DB_PASSWORD:-}"

    if [ ! -d "$BOOTSTRAP_SQL_DIR" ]; then
        echo "[entrypoint] bootstrap SQL directory not found, skip: $BOOTSTRAP_SQL_DIR"
        return 0
    fi

    if ! command -v mysql >/dev/null 2>&1; then
        echo "[entrypoint] mysql client not found, skip bootstrap SQL"
        return 0
    fi

    echo "[entrypoint] waiting for database bootstrap target ${host}:${port}/${database} ..."
    if ! wait_for_db "$host" "$port" "$user" "$password"; then
        echo "[entrypoint] database is not ready, bootstrap SQL aborted" >&2
        return 1
    fi

    for sql_file in "${BOOTSTRAP_SQL_FILES[@]}"; do
        local sql_path="$BOOTSTRAP_SQL_DIR/$sql_file"
        if [ ! -f "$sql_path" ]; then
            continue
        fi

        echo "[entrypoint] applying bootstrap SQL: $sql_file"
        MYSQL_PWD="$password" mysql -h "$host" -P "$port" -u "$user" "$database" < "$sql_path"
    done
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

run_bootstrap_sql

echo "[entrypoint] .env configured. DB_HOST=$(grep '^DB_HOST' $ENV_FILE)"
exec apache2-foreground
