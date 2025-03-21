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
    tesseract-ocr \              # ✅ Install Tesseract OCR
    python3 \                    # ✅ Install Python for running your OCR script
    python3-pip                   # ✅ Install pip for Python package installation

# Install Python dependencies for OCR script
RUN pip3 install --upgrade pip setuptools wheel   # Ensure pip is up-to-date
RUN pip3 install easyocr numpy

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
