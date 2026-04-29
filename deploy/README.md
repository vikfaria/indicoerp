# Deploy Manual (KVM 2) - Estrutura igual ao projeto anterior

Este pacote implementa deploy manual sem CI/CD com a mesma estrutura:

- App Laravel em `/var/www/hrm-saas`
- MySQL em Docker container `hrm_mysql` com `--restart unless-stopped`
- Atualização manual por upload (`scp`) + execução de script no servidor
- Domínio: `indicoerp.com`

## 1) Pré-requisitos no servidor

```bash
sudo bash deploy/scripts/01_server_bootstrap.sh
```

## 2) Subir MySQL em Docker (mesma estrutura do histórico)

```bash
sudo DB_ROOT_PASSWORD='trocar-root' \
DB_NAME='hrm_saas' \
DB_USER='hrm_user' \
DB_PASSWORD='trocar-user' \
bash deploy/scripts/02_setup_mysql_container.sh
```

Isto cria:
- Container: `hrm_mysql`
- Porta host: `127.0.0.1:3307 -> 3306` (para Laravel no host)
- Volume: `/opt/hrm/mysql-data`

## 3) Primeiro deploy da aplicação

No teu computador local (na raiz do projeto):

```bash
bash deploy/scripts/local_package_and_upload.sh
```

No servidor:

```bash
sudo APP_ENV_FILE=/var/www/hrm-saas/shared/.env \
bash /var/www/hrm-saas/current/deploy/scripts/03_deploy_release.sh /tmp/hrm-release.tar.gz
```

## 4) Configurar Nginx + SSL + workers

```bash
sudo DOMAIN=indicoerp.com bash deploy/scripts/04_configure_runtime.sh
```

Esse script:
- instala config Nginx para `indicoerp.com`
- emite SSL LetsEncrypt (`certbot`)
- cria serviços systemd:
  - `hrm-queue.service`
  - `hrm-scheduler.service`

## 5) Deploy de atualização (sem CI/CD)

Fluxo padrão:
1. Local: empacotar e enviar
2. Servidor: rodar deploy release

```bash
# Local
bash deploy/scripts/local_package_and_upload.sh

# Servidor
sudo APP_ENV_FILE=/var/www/hrm-saas/shared/.env \
bash /var/www/hrm-saas/current/deploy/scripts/03_deploy_release.sh /tmp/hrm-release.tar.gz
```

## 6) .env produção (exemplo mínimo)

Cria em `/var/www/hrm-saas/shared/.env`:

```dotenv
APP_NAME=IndicoERP
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://indicoerp.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=hrm_saas
DB_USERNAME=hrm_user
DB_PASSWORD=trocar-user

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Depois do primeiro deploy, se `APP_KEY` estiver vazio, o script gera automaticamente.

## 7) Comandos úteis

```bash
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status hrm-queue
sudo systemctl status hrm-scheduler

docker ps --filter name=hrm_mysql
docker logs --tail=100 hrm_mysql
```

