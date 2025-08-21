# cPanel Deployment (PHP 8.1) — AfricaVLP Laravel Apps

This guide shows how to deploy both Laravel apps on typical cPanel shared hosting with PHP 8.1.

## Prerequisites
- cPanel account with SSH or Terminal access enabled
- PHP 8.1 available via MultiPHP Manager
- Composer available server‑wide or installable in your home directory
- Two domains/subdomains ready (recommended):
  - Client App (main): `africavlp.org` (example)
  - Admin App (subdomain): `admin.africavlp.org` (example)

## 1) Set PHP 8.1 in cPanel
- Open cPanel > MultiPHP Manager
- Select your domains and set PHP version to 8.1
- Open cPanel > Select PHP Extensions and enable:
  - bcmath, ctype, fileinfo, json, mbstring, openssl, pdo_mysql, tokenizer, xml, curl, gd, zip

## 2) Upload Code
Option A — cPanel Git Version Control
- cPanel > Git Version Control > Create
- Repository URL: your Git repo
- Clone to: `~/africavlp` (for example)

Option B — Upload ZIP
- Zip locally and upload to home via File Manager
- Extract to: `~/africavlp`

Directory layout after upload:
- `~/africavlp/admin-laravel-app`
- `~/africavlp/client-laravel-app`

## 3) Point Document Roots
- cPanel > Domains
- Set document root for the main domain to: `~/africavlp/client-laravel-app/public`
- Create subdomain `admin.example.com` and set its document root to: `~/africavlp/admin-laravel-app/public`
- Ensure the `.htaccess` exists in each public/ (already added in repo)

## 4) Install Dependencies (Composer + NPM assets)
Open cPanel Terminal or SSH. Use the PHP 8.1 binary path (varies by host). Common paths:
- `/opt/cpanel/ea-php81/root/usr/bin/php`
- `php` (if default shell PHP is 8.1; verify with `php -v`)

Run per app:
```bash
cd ~/africavlp/admin-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php -d memory_limit=512M /usr/bin/composer install --no-dev --prefer-dist --optimize-autoloader

cd ~/africavlp/client-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php -d memory_limit=512M /usr/bin/composer install --no-dev --prefer-dist --optimize-autoloader
```
If `/usr/bin/composer` is missing, install Composer locally in `~/bin/composer` and update the path above.

Front-end assets on shared hosting:
- If your app ships compiled assets, you can skip building on the server.
- Otherwise, run `npm install && npm run build` locally and deploy the compiled assets (recommended for shared hosting).

## 5) Environment Files
For each app:
```bash
cd ~/africavlp/admin-laravel-app
cp .env.example .env

cd ~/africavlp/client-laravel-app
cp .env.example .env
```
Edit `.env` values in File Manager or via terminal editor:
- APP_NAME, APP_ENV=production, APP_URL
- DB_*, MAIL_*
- CLOUDINARY_* (required for uploads per `docs/INSTALLATION.md`)
- Set `APP_DEBUG=false`

Generate application keys:
```bash
cd ~/africavlp/admin-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan key:generate --force

cd ~/africavlp/client-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan key:generate --force
```

## 6) Storage Link and Permissions
```bash
# Create storage symlink
cd ~/africavlp/admin-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan storage:link

cd ~/africavlp/client-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan storage:link

# Permissions (shared hosting typically 755 is fine)
find ~/africavlp/admin-laravel-app/storage -type d -exec chmod 755 {} \;
find ~/africavlp/admin-laravel-app/bootstrap/cache -type d -exec chmod 755 {} \;
find ~/africavlp/client-laravel-app/storage -type d -exec chmod 755 {} \;
find ~/africavlp/client-laravel-app/bootstrap/cache -type d -exec chmod 755 {} \;
```

## 7) Database
- Create database and user in cPanel > MySQL Databases
- Put credentials in each app’s `.env`
- Import schema or run migrations

```bash
# Import complete schema (if you choose this path)
mysql -u DB_USER -p DB_NAME < ~/africavlp/complete_database_schema.sql

# Or run migrations
cd ~/africavlp/admin-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan migrate --force

cd ~/africavlp/client-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan migrate --force

# Optional seed for admin app
cd ~/africavlp/admin-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan db:seed --class=SuperAdminSeeder --force
```

## 8) Caching and Optimization
```bash
# Admin
cd ~/africavlp/admin-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan config:cache
/opt/cpanel/ea-php81/root/usr/bin/php artisan route:cache
/opt/cpanel/ea-php81/root/usr/bin/php artisan view:cache

# Client
cd ~/africavlp/client-laravel-app
/opt/cpanel/ea-php81/root/usr/bin/php artisan config:cache
/opt/cpanel/ea-php81/root/usr/bin/php artisan route:cache
/opt/cpanel/ea-php81/root/usr/bin/php artisan view:cache
```

## 9) Cron Jobs (Scheduler)
- cPanel > Cron Jobs > Add New Cron Job
- Run every minute (recommended):
```
* * * * * /opt/cpanel/ea-php81/root/usr/bin/php /home/USERNAME/africavlp/admin-laravel-app/artisan schedule:run >> /dev/null 2>&1
```
- Repeat for client app if it has scheduled tasks.

Queues on shared hosting:
- Supervisor is usually unavailable. Consider `database` queue driver and run a cron calling `queue:work --once` every minute, or use an external queue service.

## 10) Verify
- Visit the domains. If you see a 500 error, check:
  - `storage/logs/laravel.log`
  - `.env` values (APP_KEY, DB_*, MAIL_*, CLOUDINARY_*)
  - PHP version via `phpinfo()` in a temporary file under public/

## Notes
- This repo already includes Laravel `.htaccess` under:
  - `admin-laravel-app/public/.htaccess`
  - `client-laravel-app/public/.htaccess`
- Keep your apps outside `public_html`. Only the `public/` directories should be document roots.
