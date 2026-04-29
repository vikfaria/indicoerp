#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-indicoerp.com}"
APP_DIR="${APP_DIR:-/var/www/hrm-saas}"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.3-fpm.sock}"
NGINX_SITE_PATH="/etc/nginx/sites-available/${DOMAIN}.conf"

cat > "$NGINX_SITE_PATH" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${APP_DIR}/current/public;
    index index.php index.html;

    client_max_body_size 64M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sfn "$NGINX_SITE_PATH" "/etc/nginx/sites-enabled/${DOMAIN}.conf"
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# SSL
certbot --nginx -d "$DOMAIN" -d "www.${DOMAIN}" --non-interactive --agree-tos -m "admin@${DOMAIN}" --redirect || true

# queue worker service
cat > /etc/systemd/system/hrm-queue.service <<EOF
[Unit]
Description=HRM Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=${APP_DIR}/current
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=120

[Install]
WantedBy=multi-user.target
EOF

# scheduler service
cat > /etc/systemd/system/hrm-scheduler.service <<EOF
[Unit]
Description=HRM Laravel Scheduler Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=${APP_DIR}/current
ExecStart=/usr/bin/php artisan schedule:work

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable hrm-queue hrm-scheduler
systemctl restart hrm-queue hrm-scheduler

echo "Runtime configurado para ${DOMAIN}."
