# Use the official PHP image with Laravel requirements
FROM php:8.2-fpm

# Set working directory
WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    lsb-release \
    software-properties-common

# ✅ Add Tesseract-OCR repository
RUN apt-get update && apt-get install -y tesseract-ocr libtesseract-dev

# Install Python and Pip
RUN apt-get install -y python3 python3-pip

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    && docker-php-ext-install gd pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Python dependencies for OCR script
RUN pip3 install easyocr numpy

# Laravel Optimization Commands
RUN php artisan config:clear
RUN php artisan cache:clear
RUN php artisan route:clear
RUN php artisan view:clear
RUN php artisan optimize:clear

# Expose port
EXPOSE 9000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
