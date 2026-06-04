#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-indicoerp.com}"
APP_DIR="${APP_DIR:-/var/www/hrm-saas}"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-/run/php/php8.3-fpm.sock}"
QUEUE_SERVICE_NAME="${QUEUE_SERVICE_NAME:-hrm-queue.service}"
SCHEDULER_SERVICE_NAME="${SCHEDULER_SERVICE_NAME:-hrm-scheduler.service}"
RUN_CERTBOT="${RUN_CERTBOT:-1}"
NGINX_SITE_PATH="/etc/nginx/sites-available/${DOMAIN}.conf"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HARDENING_SNIPPET_SOURCE="${SCRIPT_DIR}/../nginx/indicoerp-hardening-snippet.conf"
HARDENING_SNIPPET_TARGET="/etc/nginx/snippets/indicoerp-hardening.conf"

mkdir -p /etc/nginx/snippets
cp "$HARDENING_SNIPPET_SOURCE" "$HARDENING_SNIPPET_TARGET"

cat > "$NGINX_SITE_PATH" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    include ${HARDENING_SNIPPET_TARGET};

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
}
EOF

ln -sfn "$NGINX_SITE_PATH" "/etc/nginx/sites-enabled/${DOMAIN}.conf"
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

if [ "$RUN_CERTBOT" = "1" ]; then
  # SSL
  certbot --nginx -d "$DOMAIN" -d "www.${DOMAIN}" --non-interactive --agree-tos -m "admin@${DOMAIN}" --redirect || true
fi

# queue worker service
cat > "/etc/systemd/system/${QUEUE_SERVICE_NAME}" <<EOF
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
cat > "/etc/systemd/system/${SCHEDULER_SERVICE_NAME}" <<EOF
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
systemctl enable "$QUEUE_SERVICE_NAME" "$SCHEDULER_SERVICE_NAME"
systemctl restart "$QUEUE_SERVICE_NAME" "$SCHEDULER_SERVICE_NAME"

echo "Runtime configurado para ${DOMAIN}."
