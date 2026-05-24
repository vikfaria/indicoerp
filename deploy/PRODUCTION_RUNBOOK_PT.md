# Runbook de Produção — SCE Moçambique (ERPGo/SysGest)

Este documento define os passos mínimos para publicar com segurança em produção usando os scripts de `deploy/scripts`.

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

```bash
sudo systemctl status nginx --no-pager
sudo systemctl status php8.3-fpm --no-pager
sudo systemctl status hrm-queue --no-pager
sudo systemctl status hrm-scheduler --no-pager
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
tail -f /var/www/hrm-saas/shared/storage/logs/laravel.log
```

```bash
sudo journalctl -u nginx -f
sudo journalctl -u php8.3-fpm -f
sudo journalctl -u hrm-queue -f
sudo journalctl -u hrm-scheduler -f
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
sudo systemctl restart php8.3-fpm nginx hrm-queue hrm-scheduler
```

Se houve migração destrutiva, restaurar backup da base.

## 8) Critério final de aceitação de produção

Produção só é considerada pronta quando:

- Health-check HTTP responde 200/302 estável.
- Login e módulo fiscal carregam sem erro.
- Export SAF-T de teste gera XML válido.
- Não há erro crítico no `laravel.log` durante 15–30 minutos após deploy.
