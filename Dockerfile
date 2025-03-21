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
    tesseract-ocr \             # ✅ Install Tesseract OCR
    python3 \                   # ✅ Install Python3
    python3-pip && \            # ✅ Install pip for Python
    docker-php-ext-configure gd && \
    docker-php-ext-install gd pdo pdo_mysql

# Make sure Python3 is properly linked
RUN ln -s /usr/bin/python3 /usr/bin/python

# Install Python dependencies for OCR script
RUN python3 -m pip install --upgrade pip
RUN python3 -m pip install easyocr numpy

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Clear and optimize configuration cache
RUN php artisan config:clear
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Expose port
EXPOSE 9000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
