FROM php:8.2-apache

# 启用Apache重写模块
RUN a2enmod rewrite

# 安装PHP扩展与 MySQL 客户端（容器启动时需要补跑幂等 SQL 修复脚本）
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    default-mysql-client \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip opcache

# 安装Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置Apache配置（伪静态需要 AllowOverride All）
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . .

# 复制启动脚本并赋权（.env 配置由 entrypoint 在运行时根据环境变量写入）
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# 如果仓库未提供 .htaccess，则在复制代码后兜底生成
RUN if [ ! -f /var/www/html/public/.htaccess ]; then \
      mkdir -p /var/www/html/public; \
      printf '%s\n' \
        'Options +FollowSymLinks -MultiViews' \
        'RewriteEngine On' \
        'RewriteCond %{REQUEST_FILENAME} !-d' \
        'RewriteCond %{REQUEST_FILENAME} !-f' \
        'RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]' \
        > /var/www/html/public/.htaccess; \
    fi

# 安装依赖
RUN composer install --ignore-platform-req=ext-fileinfo --no-security-blocking --no-dev --optimize-autoloader --no-scripts


# 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/runtime

# 暴露端口（Apache 默认监听 80）
EXPOSE 80

# 启动：先由 entrypoint 写入 .env，再启动 Apache
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
