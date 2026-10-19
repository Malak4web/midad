# Deploying Midad

One server, one origin. Laravel serves the API, the admin and the storefront shell;
nginx serves the built assets and uploaded images directly. No CORS, first-party cookies.

```
https://midad.example/            → Laravel (SeoController: shell + per-page meta)
https://midad.example/api/...     → Laravel API
https://midad.example/assets/...  → nginx, built JS/CSS/fonts (hashed, cached forever)
https://midad.example/storage/... → nginx, product images (random names, cached forever)
```

## Requirements

- PHP 8.4+ with `gd` (WebP), `pdo_mysql`, `mbstring`, `intl`, `opcache`
- MySQL 8 (FULLTEXT on InnoDB) or MariaDB 10.6+
- Node 22+ on the build machine only (not needed on the server)
- A process supervisor for the queue worker (systemd or supervisord), and cron

## Build and release

On the build machine (or CI):

```bash
cd frontend
npm ci
npm run build:deploy   # build → size budget → prerender home hero → copy into ../backend
cd ../backend
composer install --no-dev --optimize-autoloader
```

`build:deploy` fails if the JS, CSS or fonts go over budget (`scripts/size-budget.mjs`).
It writes `backend/resources/spa/` (the shell, never served raw) and `backend/public/assets/`.

On the server, inside `backend/`:

```bash
php artisan down --retry=30
php artisan migrate --force
php artisan storage:link          # first release only
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan cache:forget home     # the home payload is cached for 10 minutes
php artisan queue:restart
php artisan up
```

First release only: `php artisan db:seed --force` (governorates, categories, the 30
perfumes, finder questions, settings, and the admin account from `SEED_ADMIN_EMAIL` /
`SEED_ADMIN_PASSWORD`). Change the admin password right after the first login.

## Environment

Start from `.env.example`; the production block at its end lists every value that
differs from local. The ones that break things when wrong:

| Key | Value | Why |
|---|---|---|
| `APP_URL`, `FRONTEND_URL` | `https://midad.example` (same) | canonical URLs, sitemap, email links |
| `SANCTUM_STATEFUL_DOMAINS` | `midad.example,www.midad.example` | without it every login and checkout gets 419 |
| `SESSION_SECURE_COOKIE` | `true` | session, XSRF and cart cookies HTTPS-only |
| `SESSION_SAME_SITE` | `lax` | `strict` would drop the session on the return from Paymob |
| `PAYMENT_DRIVER` | `paymob` | `fake` shows the sandbox page; never ship it live |
| `PAYMOB_HMAC_SECRET` | from the Paymob dashboard | the fake fallback secret only works with the fake driver |
| `MAIL_MAILER` | `smtp` (or a provider) | order emails are queued; `log` sends nothing |
| `APP_DEBUG` | `false` | |

`robots.txt` allows crawling only when `APP_ENV=production`; staging stays hidden.

## nginx

```nginx
server {
    listen 443 ssl http2;
    server_name midad.example;
    root /var/www/midad/backend/public;

    # ssl_certificate ... (certbot)

    gzip on;
    gzip_comp_level 6;
    gzip_types text/html text/css application/javascript application/json application/xml image/svg+xml text/plain;
    gzip_min_length 1024;

    client_max_body_size 8m;    # product images (the API caps uploads at 5 MB)

    # hashed build output and random-named uploads never change
    location ^~ /assets/  { expires max; add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; }
    location ^~ /storage/ { expires max; add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; try_files $uri =404; }

    # everything else, including "/", goes to Laravel (never to a static index.html)
    location / { try_files $uri /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known) { deny all; }
}

server { listen 80; server_name midad.example www.midad.example; return 301 https://midad.example$request_uri; }
```

Security headers (CSP, nosniff, frame denial, HSTS on HTTPS) come from Laravel's
`SecurityHeaders` middleware; do not duplicate them in nginx.

## Background work

Queue worker (order and discovery-credit emails), supervised:

```ini
[program:midad-queue]
command=php /var/www/midad/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
stopwaitsecs=60
```

Scheduler (releases unpaid card orders' stock every minute, clears old guest carts daily):

```cron
* * * * * cd /var/www/midad/backend && php artisan schedule:run >> /dev/null 2>&1
```

If the scheduler stops, stock held by abandoned card payments is never released.

## Backups

Daily, kept 14 days, copied off the server:

```bash
mysqldump --single-transaction --routines midad | gzip > /backups/midad-$(date +%F).sql.gz
tar czf /backups/midad-storage-$(date +%F).tgz -C /var/www/midad/backend/storage/app public
find /backups -name 'midad-*' -mtime +14 -delete
```

Test a restore into a scratch database once before launch.

## Logs and monitoring

- `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`. Payment events log as `payments.*`
  (a `payments.webhook.bad_hmac` line means someone is posting forged callbacks, or the secret is wrong).
- Watch for the first two weeks after launch: 5xx rate, failed jobs (`php artisan queue:failed`),
  orders stuck in `pending_payment`, and the home page LCP on a phone.
- Health check: `GET /up` returns 200 when the app boots.

## Paymob: sandbox to live

1. In the Paymob dashboard create the card and wallet integrations; copy their IDs to
   `PAYMOB_INTEGRATION_CARD` / `PAYMOB_INTEGRATION_WALLET`.
2. Set `PAYMOB_SECRET_KEY`, `PAYMOB_PUBLIC_KEY`, `PAYMOB_HMAC_SECRET`, and `PAYMENT_DRIVER=paymob`.
3. Set the transaction processed callback to `https://midad.example/api/payments/paymob/webhook`.
   The customer's return URL is sent with every payment (`FRONTEND_URL/order/{number}`), nothing to set.
4. `php artisan config:cache`.
5. Place a real order for a small amount and check it turns `paid` from the webhook (not the redirect).
   Refund it in the Paymob dashboard, then record the refund on the order in the admin
   (the admin button records it; it does not move money), and cancel the order before it ships
   (or mark it returned after) so its stock comes back.

## Local production check

`npm run build:deploy`, then open `http://localhost:8391` (served by `php artisan serve`, no Vite).
`localhost:8391` is already in the local `SANCTUM_STATEFUL_DOMAINS`.
