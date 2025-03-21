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
    tesseract-ocr \           # ✅ Install Tesseract OCR
    libtesseract-dev \        # ✅ Install Tesseract Dev Library
    python3 \                 # ✅ Install Python for running your OCR script
    python3-pip               # ✅ Install pip for Python package installation

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Python dependencies for your OCR script
RUN pip3 install -r requirements.txt

# Laravel Optimization Commands (RUN these only during build phase)
RUN php artisan config:clear
RUN php artisan cache:clear
RUN php artisan route:clear
RUN php artisan view:clear
RUN php artisan optimize:clear

# Expose port
EXPOSE 9000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
