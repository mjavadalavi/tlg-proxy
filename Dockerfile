FROM php:8.2-apache

# فعال‌سازی mod_rewrite برای پشتیبانی از .htaccess
RUN a2enmod rewrite

# کپی کردن فایل‌های پروژه به دایرکتوری پیش‌فرض آپاچی
COPY . /var/www/html/

# تنظیم دسترسی‌ها
RUN chown -R www-data:www-data /var/www/html
