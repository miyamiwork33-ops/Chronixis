# PHP 8.3-FPM イメージをベースにする
FROM php:8.3-fpm

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip

# Composerのインストール
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# 作業ディレクトリを設定
WORKDIR /var/www/html

# Laravel プロジェクトをコピーして依存関係をインストール
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Laravelの開発サーバーを起動
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
