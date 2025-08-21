# AfricaVLP Laravel Applications - Deployment Guide

## 🚀 Production Deployment Guide

This guide covers deploying both AfricaVLP Laravel applications to production environments.

---

## 📋 Pre-Deployment Checklist

### Environment Preparation
- [ ] Production server with PHP 8.1+, MySQL 8.0+, and web server
- [ ] SSL certificate for HTTPS
- [ ] Domain names configured (e.g., africavlp.org, admin.africavlp.org)
- [ ] Database server accessible from application servers
- [ ] Email service configured (SendGrid, Mailgun, etc.)
- [ ] Backup strategy implemented

### Security Checklist
- [ ] Firewall configured
- [ ] SSH keys configured for secure access
- [ ] Database passwords are strong and unique
- [ ] Application keys generated
- [ ] Debug mode disabled (`APP_DEBUG=false`)
- [ ] Error logging configured

---

## 🏗️ Server Setup

### System Requirements
```bash
# Ubuntu 20.04/22.04 LTS recommended
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1 and extensions
sudo apt install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl \
    php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl \
    php8.1-redis php8.1-imagick

# Install MySQL 8.0
sudo apt install mysql-server-8.0

# Install Nginx
sudo apt install nginx

# Install Redis (for caching and queues)
sudo apt install redis-server

# Install Node.js 18 LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Directory Structure
```bash
# Create application directory
sudo mkdir -p /var/www/africavlp
cd /var/www/africavlp

# Set ownership
sudo chown -R $USER:www-data /var/www/africavlp
```

---

## 📦 Application Deployment

### 1. Deploy Admin Application

#### Clone and Setup
```bash
cd /var/www/africavlp
git clone [repository-url] .
cd admin-laravel-app

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Environment Configuration
```bash
cp .env.example .env
nano .env
```

Production `.env` for Admin App:
```env
APP_NAME="AfricaVLP Admin"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://admin.africavlp.org

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=africavlp_production
DB_USERNAME=africavlp_user
DB_PASSWORD=STRONG_SECURE_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@africavlp.org
MAIL_FROM_NAME="${APP_NAME}"

# Security
SANCTUM_STATEFUL_DOMAINS=admin.africavlp.org
SESSION_DOMAIN=.africavlp.org
```

#### Generate Application Key
```bash
php artisan key:generate
```

### 2. Deploy Client Application

#### Setup
```bash
cd /var/www/africavlp/client-laravel-app

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Environment Configuration
```bash
cp .env.example .env
nano .env
```

Production `.env` for Client App:
```env
APP_NAME="AfricaVLP"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://africavlp.org

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=africavlp_production
DB_USERNAME=africavlp_user
DB_PASSWORD=STRONG_SECURE_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@africavlp.org
MAIL_FROM_NAME="${APP_NAME}"

# Security
SANCTUM_STATEFUL_DOMAINS=africavlp.org
SESSION_DOMAIN=.africavlp.org
```

#### Generate Application Key
```bash
php artisan key:generate
```

---

## 🗄️ Database Setup

### Create Production Database
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE africavlp_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'africavlp_user'@'localhost' IDENTIFIED BY 'STRONG_SECURE_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON africavlp_production.* TO 'africavlp_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### Import Database Schema
```bash
# Import complete schema
mysql -u africavlp_user -p africavlp_production < /var/www/africavlp/complete_database_schema.sql

# Or run migrations
cd /var/www/africavlp/admin-laravel-app
php artisan migrate --force

# Seed admin users
php artisan db:seed --class=SuperAdminSeeder --force
```

---

## 🌐 Web Server Configuration

### Nginx Configuration

#### Admin Application
```nginx
# /etc/nginx/sites-available/admin-africavlp
server {
    listen 80;
    server_name admin.africavlp.org;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name admin.africavlp.org;
    root /var/www/africavlp/admin-laravel-app/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/admin.africavlp.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.africavlp.org/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    index index.php;

    charset utf-8;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Asset caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
```

#### Client Application
```nginx
# /etc/nginx/sites-available/client-africavlp
server {
    listen 80;
    server_name africavlp.org www.africavlp.org;
    return 301 https://africavlp.org$request_uri;
}

server {
    listen 443 ssl http2;
    server_name www.africavlp.org;
    return 301 https://africavlp.org$request_uri;
}

server {
    listen 443 ssl http2;
    server_name africavlp.org;
    root /var/www/africavlp/client-laravel-app/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/africavlp.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/africavlp.org/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    index index.php;

    charset utf-8;

    # Handle Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Asset caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
```

#### Enable Sites
```bash
# Enable sites
sudo ln -s /etc/nginx/sites-available/admin-africavlp /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/client-africavlp /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 🔐 SSL Certificate Setup

### Using Let's Encrypt
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificates
sudo certbot --nginx -d africavlp.org -d www.africavlp.org
sudo certbot --nginx -d admin.africavlp.org

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🔧 Application Optimization

### Laravel Optimizations
```bash
# Admin App
cd /var/www/africavlp/admin-laravel-app

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Client App
cd /var/www/africavlp/client-laravel-app

# Repeat optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload --optimize
```

### PHP-FPM Optimization
```bash
# Edit PHP-FPM pool configuration
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Recommended settings:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

---

## 🔄 Queue Workers Setup

### Supervisor Configuration
```bash
# Install Supervisor
sudo apt install supervisor

# Create worker configuration
sudo nano /etc/supervisor/conf.d/africavlp-worker.conf
```

Supervisor config:
```ini
[program:africavlp-admin-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/africavlp/admin-laravel-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/africavlp/admin-laravel-app/storage/logs/worker.log
stopwaitsecs=3600

[program:africavlp-client-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/africavlp/client-laravel-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/africavlp/client-laravel-app/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start africavlp-admin-worker:*
sudo supervisorctl start africavlp-client-worker:*
```

---

## 📊 Monitoring & Logging

### Log Rotation
```bash
# Create logrotate configuration
sudo nano /etc/logrotate.d/africavlp
```

Logrotate config:
```
/var/www/africavlp/*/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        sudo systemctl reload php8.1-fpm
    endscript
}
```

### Health Check Script
```bash
# Create health check script
nano /var/www/africavlp/health-check.sh
```

```bash
#!/bin/bash

# Health check script for AfricaVLP applications

echo "=== AfricaVLP Health Check $(date) ==="

# Check admin application
echo "Checking Admin Application..."
ADMIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://admin.africavlp.org/health)
if [ "$ADMIN_STATUS" = "200" ]; then
    echo "✅ Admin App: OK"
else
    echo "❌ Admin App: FAILED (Status: $ADMIN_STATUS)"
fi

# Check client application
echo "Checking Client Application..."
CLIENT_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://africavlp.org/health)
if [ "$CLIENT_STATUS" = "200" ]; then
    echo "✅ Client App: OK"
else
    echo "❌ Client App: FAILED (Status: $CLIENT_STATUS)"
fi

# Check database
echo "Checking Database..."
DB_CHECK=$(mysql -u africavlp_user -p$DB_PASSWORD -e "SELECT 1" africavlp_production 2>/dev/null)
if [ $? -eq 0 ]; then
    echo "✅ Database: OK"
else
    echo "❌ Database: FAILED"
fi

# Check Redis
echo "Checking Redis..."
REDIS_CHECK=$(redis-cli ping 2>/dev/null)
if [ "$REDIS_CHECK" = "PONG" ]; then
    echo "✅ Redis: OK"
else
    echo "❌ Redis: FAILED"
fi

echo "=== Health Check Complete ==="
```

```bash
# Make executable
chmod +x /var/www/africavlp/health-check.sh

# Add to crontab for regular checks
crontab -e
# Add: */5 * * * * /var/www/africavlp/health-check.sh >> /var/log/africavlp-health.log 2>&1
```

---

## 🔄 Backup Strategy

### Database Backup Script
```bash
# Create backup script
nano /var/www/africavlp/backup.sh
```

```bash
#!/bin/bash

# AfricaVLP Backup Script

BACKUP_DIR="/var/backups/africavlp"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="africavlp_production"
DB_USER="africavlp_user"
DB_PASS="STRONG_SECURE_PASSWORD"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/database_$DATE.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/africavlp --exclude='*/node_modules' --exclude='*/vendor' --exclude='*/storage/logs'

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Make executable
chmod +x /var/www/africavlp/backup.sh

# Schedule daily backups
crontab -e
# Add: 0 2 * * * /var/www/africavlp/backup.sh >> /var/log/africavlp-backup.log 2>&1
```

---

## 🚀 Deployment Automation

### Deployment Script
```bash
# Create deployment script
nano /var/www/africavlp/deploy.sh
```

```bash
#!/bin/bash

# AfricaVLP Deployment Script

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Admin App Deployment
echo "Deploying Admin Application..."
cd /var/www/africavlp/admin-laravel-app

# Install/update dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Client App Deployment
echo "Deploying Client Application..."
cd /var/www/africavlp/client-laravel-app

# Install/update dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx
sudo supervisorctl restart all

echo "Deployment completed successfully!"
```

```bash
# Make executable
chmod +x /var/www/africavlp/deploy.sh
```

---

## 🔍 Post-Deployment Verification

### Verification Checklist
- [ ] Both applications load correctly
- [ ] SSL certificates are working
- [ ] Database connections are successful
- [ ] Admin login works with seeded credentials
- [ ] User registration works on client app
- [ ] Email notifications are being sent
- [ ] Queue workers are processing jobs
- [ ] File uploads are working
- [ ] All static assets are loading
- [ ] Health check endpoints respond correctly

### Test Commands
```bash
# Test admin application
curl -I https://admin.africavlp.org

# Test client application
curl -I https://africavlp.org

# Test database connection
cd /var/www/africavlp/admin-laravel-app
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue workers
sudo supervisorctl status

# Check logs
tail -f /var/www/africavlp/admin-laravel-app/storage/logs/laravel.log
```

---

## 📞 Support & Troubleshooting

### Common Issues
1. **Permission errors**: Check file ownership and permissions
2. **Database connection**: Verify credentials and network access
3. **SSL issues**: Check certificate installation and renewal
4. **Performance issues**: Monitor server resources and optimize
5. **Queue not processing**: Check supervisor and Redis status

### Log Locations
- Application logs: `/var/www/africavlp/*/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- PHP-FPM logs: `/var/log/php8.1-fpm.log`
- MySQL logs: `/var/log/mysql/`

**Deployment Complete!** 🎉

Your AfricaVLP Laravel applications are now running in production.
