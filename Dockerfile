# Use the official PHP image with Laravel requirements
FROM php:8.2-fpm

# Set working directory
WORKDIR /app

# Install dependencies and tools
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    tesseract-ocr \
    libtesseract-dev \
    python3 \
    python3-distutils \
    python3-venv \
    python3-pip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Clear and optimize configuration cache
RUN php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache

# Install Python packages for OCR
RUN pip3 install easyocr numpy

# Expose port
EXPOSE 9000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
