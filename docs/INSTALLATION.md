# AfricaVLP Laravel Applications - Installation Guide

## 📋 Prerequisites

### System Requirements
- **PHP**: 8.1 or higher
- **Composer**: 2.x or higher
- **MySQL**: 8.0 or higher (5.7 minimum)
- **Node.js**: 16.x or higher
- **NPM**: 8.x or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+

### PHP Extensions Required
```bash
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|curl|gd|zip)"
```

Required extensions:
- bcmath
- ctype
- fileinfo
- json
- mbstring
- openssl
- pdo_mysql
- tokenizer
- xml
- curl
- gd
- zip

---

## 🚀 Installation Steps

### 1. Clone Repository
```bash
git clone [repository-url] AfricaVLP-Laravel
cd AfricaVLP-Laravel
```

### 2. Admin Application Setup

#### 2.1 Install Dependencies
```bash
cd admin-laravel-app
composer install --optimize-autoloader
npm install
```

#### 2.2 Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file:
```env
APP_NAME="AfricaVLP Admin"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hruaif93_au_vlp
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Email Configuration (choose one)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@africavlp.org
MAIL_FROM_NAME="African Universities VLP"

# Mailgun Configuration
MAILGUN_DOMAIN=your_mailgun_domain
MAILGUN_SECRET=your_mailgun_secret

# SendGrid Configuration (alternative)
SENDGRID_API_KEY=your_sendgrid_api_key
SENDGRID_FROM_EMAIL=noreply@africavlp.org
SENDGRID_FROM_NAME="African Universities VLP"

# Cloudinary Configuration (Required for file uploads)
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_UPLOAD_PRESET=your_upload_preset

To get these credentials:
1. Sign up for a free Cloudinary account at https://cloudinary.com
2. Go to your Dashboard to find your Cloud Name, API Key, and API Secret
3. Create an upload preset in Settings > Upload presets (optional but recommended)

**Note**: Without Cloudinary configuration, file uploads will fail. This includes profile images, organization logos, event images, forum attachments, and all other file uploads.

FILESYSTEM_DISK=local
```

#### 2.3 Generate Application Key
```bash
php artisan key:generate
```

#### 2.4 Create Storage Link
```bash
php artisan storage:link
```

#### 2.5 Build Assets
```bash
npm run build
```

### 3. Client Application Setup

#### 3.1 Install Dependencies
```bash
cd ../client-laravel-app
composer install --optimize-autoloader
npm install
```

#### 3.2 Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file:
```env
APP_NAME="AfricaVLP Client"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hruaif93_au_vlp
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=local
```

#### 3.3 Generate Application Key
```bash
php artisan key:generate
```

#### 3.4 Create Storage Link
```bash
php artisan storage:link
```

#### 3.5 Build Assets
```bash
npm run build
```

---

## 🗄️ Database Setup

### Option 1: Import Complete Database (Recommended)
```bash
# Import the complete schema with all tables
mysql -u your_username -p hruaif93_au_vlp < ../complete_database_schema.sql
```

### Option 2: Run Migrations
```bash
# Admin App
cd admin-laravel-app
php artisan migrate

# Client App
cd ../client-laravel-app
php artisan migrate
```

### Seed Default Admin Users
```bash
cd admin-laravel-app
php artisan db:seed --class=SuperAdminSeeder
```

**Default Credentials:**
- **Super Admin**: `superadmin@africavlp.org` / `SuperAdmin2024!`
- **Test Admin**: `admin@africavlp.org` / `TestAdmin2024!`

---

## 🔧 Configuration

### File Permissions
```bash
# Set proper permissions
chmod -R 755 admin-laravel-app/storage
chmod -R 755 admin-laravel-app/bootstrap/cache
chmod -R 755 client-laravel-app/storage
chmod -R 755 client-laravel-app/bootstrap/cache

# For production
chown -R www-data:www-data admin-laravel-app/storage
chown -R www-data:www-data client-laravel-app/storage
```

### Queue Configuration (Optional)
For background job processing:

```bash
# Install Redis (recommended)
sudo apt-get install redis-server

# Update .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Cache Configuration (Optional)
```bash
# Update .env for Redis cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

---

## 🚀 Running the Applications

### Development Mode

#### Admin Application
```bash
cd admin-laravel-app
php artisan serve --port=8000
```
Access: `http://localhost:8000`

#### Client Application
```bash
cd client-laravel-app
php artisan serve --port=8001
```
Access: `http://localhost:8001`

### Production Mode

#### Apache Virtual Host Example
```apache
# Admin Application
<VirtualHost *:80>
    ServerName admin.africavlp.org
    DocumentRoot /var/www/AfricaVLP-Laravel/admin-laravel-app/public
    
    <Directory /var/www/AfricaVLP-Laravel/admin-laravel-app/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/admin-africavlp-error.log
    CustomLog ${APACHE_LOG_DIR}/admin-africavlp-access.log combined
</VirtualHost>

# Client Application
<VirtualHost *:80>
    ServerName africavlp.org
    DocumentRoot /var/www/AfricaVLP-Laravel/client-laravel-app/public
    
    <Directory /var/www/AfricaVLP-Laravel/client-laravel-app/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/client-africavlp-error.log
    CustomLog ${APACHE_LOG_DIR}/client-africavlp-access.log combined
</VirtualHost>
```

#### Nginx Configuration Example
```nginx
# Admin Application
server {
    listen 80;
    server_name admin.africavlp.org;
    root /var/www/AfricaVLP-Laravel/admin-laravel-app/public;
    
    index index.php index.html index.htm;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}

# Client Application
server {
    listen 80;
    server_name africavlp.org;
    root /var/www/AfricaVLP-Laravel/client-laravel-app/public;
    
    index index.php index.html index.htm;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

---

## 🔍 Verification

### Test Admin Application
1. Navigate to `http://localhost:8000/admin/login`
2. Login with: `superadmin@africavlp.org` / `SuperAdmin2024!`
3. Verify dashboard loads correctly
4. Test user management functionality

### Test Client Application
1. Navigate to `http://localhost:8001`
2. Test user registration process
3. Verify forum access
4. Test organization features

### Database Connection Test
```bash
# Admin App
cd admin-laravel-app
php artisan tinker
>>> DB::connection()->getPdo();

# Client App
cd ../client-laravel-app
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 🛠️ Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
sudo chown -R $USER:www-data storage
sudo chown -R $USER:www-data bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

#### 2. Database Connection Issues
- Verify MySQL service is running
- Check database credentials in `.env`
- Ensure database exists
- Test connection: `mysql -u username -p database_name`

#### 3. Asset Compilation Issues
```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Rebuild assets
npm run build
```

#### 4. Laravel Cache Issues
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

#### 5. Composer Issues
```bash
composer clear-cache
composer install --no-cache
```

---

## 📊 Performance Optimization

### Production Optimizations
```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize for production
php artisan optimize
```

### Queue Workers (Production)
```bash
# Install supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Supervisor configuration:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/AfricaVLP-Laravel/admin-laravel-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/AfricaVLP-Laravel/admin-laravel-app/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 🔐 Security Considerations

### Production Security
1. **Environment Variables**: Never commit `.env` files
2. **Debug Mode**: Set `APP_DEBUG=false` in production
3. **HTTPS**: Use SSL certificates for production
4. **File Permissions**: Restrict access to sensitive directories
5. **Database**: Use strong passwords and limit access
6. **Updates**: Keep Laravel and dependencies updated

### Firewall Configuration
```bash
# Allow HTTP and HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Allow SSH (if needed)
sudo ufw allow 22

# Enable firewall
sudo ufw enable
```

---

## 📞 Support

For installation issues or questions:
1. Check the troubleshooting section above
2. Review Laravel documentation: https://laravel.com/docs
3. Contact the development team
4. Check project issues on GitHub

**Installation Complete!** 🎉

Your AfricaVLP Laravel applications should now be running successfully.
