# Runbook de Produção — SCE Moçambique (ERPGo/SysGest)

Este documento define os passos mínimos para publicar com segurança em produção usando os scripts de `deploy/scripts`.

## Servidor actual

Para o servidor actual `srv1512291`, usar preferencialmente:

```bash
DB_PASS='COLOCAR_A_PASSWORD' bash deploy/scripts/05_pull_deploy_indicoerp.sh
```

Este script já está alinhado com:

- `APP_DIR=/var/www/indicoerp/repo`
- `php8.2-fpm`
- `indicoerp-queue`
- `indicoerp-scheduler`
- `mysqldump --no-tablespaces`

## 1) Critérios de Go/No-Go

Só avançar para produção se todos os pontos abaixo estiverem `OK`:

- Migrações e seeders executam sem erro em staging.
- Build frontend (`npm run build`) concluído.
- Testes críticos passam (facturação, IVA, SAF-T, permissões fiscais).
- Backup da base de dados de produção criado e testado para restore.
- `.env` de produção validado (DB, APP_URL, mail, filas, cache).
- Equipa com janela de manutenção e plano de rollback aprovado.

## 2) Pré-requisitos no servidor (1x por ambiente)

```bash
sudo bash deploy/scripts/01_server_bootstrap.sh
```

```bash
sudo DB_ROOT_PASSWORD='trocar-root' \
DB_NAME='hrm_saas' \
DB_USER='hrm_user' \
DB_PASSWORD='trocar-user' \
bash deploy/scripts/02_setup_mysql_container.sh
```

## 3) Estrutura recomendada de produção

Os scripts foram desenhados para:

- `APP_DIR=/var/www/hrm-saas`
- releases em `/var/www/hrm-saas/releases`
- symlink ativo em `/var/www/hrm-saas/current`
- `.env` em `/var/www/hrm-saas/shared/.env`

Se o servidor atual usa `/var/www/indicoerp/repo`, decidir antes do go-live:

1. Migrar para estrutura de releases (recomendado), ou
2. Manter estrutura atual com procedimento manual fora deste script.

## 4) Deploy de release (produção)

### 4.1 Empacotar e enviar (local)

```bash
bash deploy/scripts/local_package_and_upload.sh
```

Se necessário, definir destino:

```bash
SERVER_HOST=SEU_IP SERVER_USER=root REMOTE_TMP=/tmp/hrm-release.tar.gz \
bash deploy/scripts/local_package_and_upload.sh
```

### 4.2 Aplicar release (servidor)

```bash
sudo APP_ENV_FILE=/var/www/hrm-saas/shared/.env \
bash /var/www/hrm-saas/current/deploy/scripts/03_deploy_release.sh /tmp/hrm-release.tar.gz
```

## 5) Pós-deploy (validação imediata)

Para o servidor actual (`/var/www/indicoerp/repo`), usar primeiro o health-check automatizado:

```bash
LOG_SINCE='30 min ago' bash deploy/scripts/06_post_deploy_healthcheck_indicoerp.sh
```

Validação manual complementar:

```bash
sudo systemctl status nginx --no-pager
sudo systemctl status php8.2-fpm --no-pager
sudo systemctl status indicoerp-queue --no-pager
sudo systemctl status indicoerp-scheduler --no-pager
```

```bash
curl -I https://indicoerp.com
```

```bash
docker ps --filter name=hrm_mysql
docker logs --tail=100 hrm_mysql
```

## 6) Logs em tempo real (produção)

```bash
tail -f /var/www/indicoerp/repo/storage/logs/laravel.log
```

```bash
sudo journalctl -u nginx -f
sudo journalctl -u php8.2-fpm -f
sudo journalctl -u indicoerp-queue -f
sudo journalctl -u indicoerp-scheduler -f
```

## 7) Rollback rápido

Listar releases:

```bash
ls -1dt /var/www/hrm-saas/releases/*
```

Apontar `current` para release anterior:

```bash
sudo ln -sfn /var/www/hrm-saas/releases/AAAAmmddHHMMSS /var/www/hrm-saas/current
sudo chown -h www-data:www-data /var/www/hrm-saas/current
sudo systemctl restart php8.2-fpm nginx indicoerp-queue indicoerp-scheduler
```

Se houve migração destrutiva, restaurar backup da base.

## 8) Critério final de aceitação de produção

Produção só é considerada pronta quando:

- Health-check HTTP responde 200/302 estável.
- Login e módulo fiscal carregam sem erro.
- Export SAF-T de teste gera XML válido.
- Não há erro crítico no `laravel.log` durante 15–30 minutos após deploy.

## 9) Auditoria de performance do servidor

Para validar tuning de runtime no próprio VPS:

```bash
bash deploy/scripts/07_server_performance_audit.sh
```

O script recolhe:

- memória, disco e swap;
- RSS médio/máximo dos workers PHP-FPM;
- recomendação inicial para `pm.max_children`;
- processos MySQL/MariaDB e possível duplicação;
- maiores consumidores de memória.

## 10) Smoke test de carga

Para um teste básico com `k6`:

```bash
K6_BASE_URL=https://indicoerp.com \
K6_VUS=10 \
K6_DURATION=2m \
bash deploy/scripts/08_run_k6_indicoerp_smoke.sh
```

Para incluir login e páginas autenticadas:

```bash
K6_BASE_URL=https://indicoerp.com \
K6_LOGIN_EMAIL='seu-utilizador@dominio.com' \
K6_LOGIN_PASSWORD='sua-password' \
K6_AUTH_PATHS='/dashboard,/sales,/accounting' \
K6_VUS=10 \
K6_DURATION=2m \
bash deploy/scripts/08_run_k6_indicoerp_smoke.sh
```

Recomendação operacional:

- correr carga a partir de uma máquina externa ao VPS;
- começar em `10` utilizadores, depois `25`, e só depois `50`;
- comparar `p95`, taxa de erro e tempo de login antes de decidir upgrade do servidor.
