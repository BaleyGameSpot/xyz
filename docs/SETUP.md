# Chinar Signals — Setup Guide

Complete step-by-step instructions for deploying the Chinar Signals backend and admin panel.

---

## 1. Server Requirements

| Component | Minimum Version | Recommended    |
|-----------|-----------------|----------------|
| PHP       | 8.1             | 8.2+           |
| MySQL     | 8.0             | 8.0+           |
| Composer  | 2.x             | Latest         |
| Node.js   | 16.x            | 20 LTS         |
| Nginx     | 1.18+           | Latest stable  |
| Redis     | 6.x             | 7.x (optional) |

### Required PHP Extensions

```bash
php -m | grep -E "pdo|mbstring|openssl|tokenizer|xml|json|bcmath|fileinfo|gd|curl"
```

Required: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `json`, `bcmath`, `fileinfo`, `gd`, `curl`

Install missing extensions (Ubuntu/Debian):

```bash
sudo apt install php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-curl php8.2-gd php8.2-zip
```

---

## 2. Backend Setup

### 2.1 Clone the Repository

```bash
git clone https://github.com/your-org/chinar-signals.git
cd chinar-signals/backend
```

### 2.2 Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 2.3 Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your values:

```env
# Application
APP_NAME="Chinar Signals"
APP_ENV=production
APP_KEY=                          # Generated in step 2.4
APP_DEBUG=false
APP_URL=https://api.chinarsignals.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chinar_signals
DB_USERNAME=chinar_user
DB_PASSWORD=your_strong_password

# JWT
JWT_SECRET=                        # Generated in step 2.4
JWT_TTL=1440                       # Token lifetime in minutes (24 hours)

# Firebase (for push notifications)
FIREBASE_PROJECT_ID=chinar-signals-xxxxx
FIREBASE_SERVER_KEY=AAAA...

# Cache & Queue (optional but recommended)
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Mail (for admin notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@chinarsignals.com
MAIL_FROM_NAME="Chinar Signals"
```

### 2.4 Generate Keys

```bash
# Generate Laravel app key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### 2.5 Create Database

```bash
mysql -u root -p -e "
    CREATE DATABASE chinar_signals CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER 'chinar_user'@'localhost' IDENTIFIED BY 'your_strong_password';
    GRANT ALL PRIVILEGES ON chinar_signals.* TO 'chinar_user'@'localhost';
    FLUSH PRIVILEGES;
"
```

### 2.6 Run Migrations

```bash
# Run all migrations
php artisan migrate

# Or import the schema dump directly (faster for fresh installs)
mysql -u chinar_user -p chinar_signals < ../docs/schema.sql
```

### 2.7 Seed Initial Data

```bash
# Seed packages, trading pairs, and default admin
php artisan db:seed

# Or seed specific seeders
php artisan db:seed --class=PackageSeeder
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=TradingPairSeeder
```

### 2.8 Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 2.9 Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 3. Web Server Configuration

### Nginx

```nginx
server {
    listen 80;
    server_name api.chinarsignals.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.chinarsignals.com;

    ssl_certificate     /etc/ssl/chinar/fullchain.pem;
    ssl_certificate_key /etc/ssl/chinar/privkey.pem;

    root /var/www/chinar-signals/backend/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 10M;
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 4. Cron Job Setup

The scheduler handles signal generation, subscription expiry checks, and cleanup tasks.

```bash
crontab -e
```

Add the following line:

```cron
* * * * * cd /var/www/chinar-signals/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

| Task                                | Schedule       | Description                          |
|-------------------------------------|----------------|--------------------------------------|
| `signals:generate`                  | Every 15 min   | Fetch market data and generate signals |
| `subscriptions:check-expiry`        | Daily at 01:00 | Mark expired subscriptions           |
| `signals:cleanup`                   | Daily at 02:00 | Archive old signals                  |
| `payments:check-pending`            | Every 30 min   | Re-verify blockchain transactions    |

To verify the scheduler is working:

```bash
php artisan schedule:list
php artisan schedule:run --verbose
```

### Queue Worker (for push notifications)

```bash
# Start queue worker
php artisan queue:work --queue=notifications,default --tries=3

# Using Supervisor (recommended for production)
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/chinar-worker.conf`:

```ini
[program:chinar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/chinar-signals/backend/artisan queue:work --queue=notifications,default --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/chinar-worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chinar-worker:*
```

---

## 5. Firebase Setup

Firebase is used for push notifications (FCM).

### 5.1 Create Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Click **Add project** → Name it `chinar-signals`
3. Disable Google Analytics (optional) → **Create project**

### 5.2 Get Server Key

1. In Firebase Console → **Project Settings** → **Cloud Messaging** tab
2. Copy the **Server key** (legacy) or create a service account for HTTP v1 API
3. Add to `.env`: `FIREBASE_SERVER_KEY=your-server-key`

### 5.3 Add Android App to Firebase

1. **Project Settings** → **Your apps** → **Add app** → Android icon
2. Android package name: `com.chinarsignals.app`
3. Download `google-services.json`
4. Place it in the Android app at `android/app/google-services.json`

### 5.4 Verify Setup

```bash
php artisan tinker
>>> \App\Services\NotificationService::testSend('your-device-fcm-token');
```

---

## 6. Android App Configuration

### 6.1 Configure API Base URL

Edit `android/app/src/main/java/com/chinarsignals/app/network/ApiConfig.kt`:

```kotlin
object ApiConfig {
    const val BASE_URL = "https://api.chinarsignals.com/api/v1/"
    const val TIMEOUT_SECONDS = 30L
}
```

### 6.2 Place google-services.json

Copy the `google-services.json` file obtained from Firebase into:
```
android/app/google-services.json
```

### 6.3 Build the App

```bash
cd android
./gradlew assembleRelease
```

The signed APK will be at `android/app/build/outputs/apk/release/app-release.apk`.

### 6.4 Environment-Specific Builds

For debug/staging, edit `android/app/src/debug/res/values/strings.xml`:
```xml
<string name="api_base_url">https://staging-api.chinarsignals.com/api/v1/</string>
```

---

## 7. Admin Panel Access

### 7.1 First Login

After seeding, the default admin credentials are:

```
URL:      https://api.chinarsignals.com/admin/login
Email:    admin@chinarsignals.com
Password: Admin@12345
```

**Change the password immediately after first login** via Settings → Security.

### 7.2 Create Additional Admin Users

```bash
php artisan tinker
>>> \App\Models\Admin::create([
...     'name'     => 'Manager',
...     'email'    => 'manager@chinarsignals.com',
...     'password' => bcrypt('YourStrongPassword'),
... ]);
```

### 7.3 Admin Panel URLs

| URL                               | Description                    |
|-----------------------------------|--------------------------------|
| `/admin/login`                    | Admin login page               |
| `/admin/dashboard`                | Main dashboard                 |
| `/admin/users`                    | User management                |
| `/admin/signals`                  | Signal management              |
| `/admin/packages`                 | Subscription packages          |
| `/admin/payments`                 | Payment verification           |
| `/admin/settings`                 | System settings                |

---

## 8. First-Time Configuration

After accessing the admin panel, complete these steps in order:

### Step 1: Set Wallet Addresses
Go to **Settings → Wallet Addresses** and enter your cryptocurrency wallet addresses for each supported network (USDT TRC-20, USDT ERC-20, BTC, ETH, BNB).

### Step 2: Configure Signal Settings
Go to **Settings → Signal Settings**:
- Set the minimum confidence score (recommended: 65%)
- Enter the active trading pairs (one per line)
- Enable signal generation

### Step 3: Activate Packages
Go to **Packages** and review the default packages created by the seeder. Adjust pricing, limits, and timeframes as needed, then toggle them to **Active**.

### Step 4: Configure Firebase
Go to **Settings → Notifications** and enter your Firebase Server Key and Project ID.

### Step 5: Test the System
- Register a test user via the Android app
- Submit a test payment and verify it from the admin panel
- Manually trigger signal generation: `php artisan signals:generate`
- Verify the push notification arrives on the test device

---

## 9. Troubleshooting

### Signals not generating

```bash
# Check cron is running
systemctl status cron

# Run manually with verbose output
php artisan signals:generate --verbose

# Check logs
tail -f storage/logs/laravel.log
```

### Push notifications not working

```bash
# Test FCM connection
php artisan tinker
>>> app(\App\Services\NotificationService::class)->sendTest();

# Check queue worker
supervisorctl status chinar-worker:*
supervisorctl tail chinar-worker:chinar-worker_00
```

### Database connection issues

```bash
php artisan db:show
php artisan migrate:status
```

### Permission denied errors

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
php artisan cache:clear
php artisan config:clear
```

---

## 10. Security Checklist

Before going live, verify:

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] Default admin password changed
- [ ] JWT secret is at least 32 characters
- [ ] Database password is strong (16+ chars, mixed)
- [ ] Firewall blocks all ports except 80, 443, 22
- [ ] SSL certificate installed and auto-renewing
- [ ] `.env` file is not publicly accessible
- [ ] `storage/` and `bootstrap/cache/` are not web-accessible
- [ ] Nginx server tokens disabled
- [ ] PHP version exposed in headers disabled (`expose_php = Off`)
