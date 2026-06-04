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

Antes de aplicar isto em produção, validar a release em staging usando [STAGING_VALIDATION_RUNBOOK_PT.md](./STAGING_VALIDATION_RUNBOOK_PT.md).

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

## 7.1) Backup, verificação e restore

Antes de qualquer deploy com migration, criar backup operacional versionado. O fluxo de deploy automatizado (`deploy/scripts/05_pull_deploy_indicoerp.sh`) executa este mesmo passo via `deploy/scripts/17_pre_deploy_backup_indicoerp.sh` e regista o manifesto no `BACKUP_DIR`:

```bash
cd /var/www/indicoerp/repo
DB_PASS='COLOCAR_A_PASSWORD' \
bash deploy/scripts/16_backup_restore_indicoerp.sh backup
```

Se a base estiver dentro do container MySQL, executar via container:

```bash
cd /var/www/indicoerp/repo
MYSQL_CONTAINER_NAME=indicoerp_mysql \
DB_NAME=indicoerp \
DB_USER=indicoerp_user \
DB_PASS='COLOCAR_A_PASSWORD' \
bash deploy/scripts/16_backup_restore_indicoerp.sh backup
```

Validar integridade do dump e testar restore numa base temporária diferente da produção:

```bash
cd /var/www/indicoerp/repo
MYSQL_CONTAINER_NAME=indicoerp_mysql \
DB_NAME=indicoerp \
DB_USER=indicoerp_user \
DB_PASS='COLOCAR_A_PASSWORD' \
MYSQL_ADMIN_USER=root \
MYSQL_ADMIN_PASS='COLOCAR_A_PASSWORD_ROOT' \
RESTORE_FILE=/var/backups/indicoerp/db/db_indicoerp_AAAAMMDD_HHMMSS.sql.gz \
VERIFY_DB_NAME=indicoerp_restore_check \
bash deploy/scripts/16_backup_restore_indicoerp.sh verify
```

Se `MYSQL_ADMIN_USER/MYSQL_ADMIN_PASS` não tiver permissão para `CREATE DATABASE` e `DROP DATABASE`, o script ainda valida a integridade gzip do dump, mas o go-live continua bloqueado até existir teste real de restore.

Restore de produção só deve ser executado com confirmação explícita e janela de manutenção:

```bash
cd /var/www/indicoerp/repo
MYSQL_CONTAINER_NAME=indicoerp_mysql \
DB_NAME=indicoerp \
DB_USER=indicoerp_user \
DB_PASS='COLOCAR_A_PASSWORD' \
MYSQL_ADMIN_USER=root \
MYSQL_ADMIN_PASS='COLOCAR_A_PASSWORD_ROOT' \
RESTORE_FILE=/var/backups/indicoerp/db/db_indicoerp_AAAAMMDD_HHMMSS.sql.gz \
CONFIRM_RESTORE=YES \
bash deploy/scripts/16_backup_restore_indicoerp.sh restore
```

Se houver um backup pré-deploy registado, a verificação de restore deve ser executada com o wrapper operacional:

```bash
cd /var/www/indicoerp/repo
MYSQL_CONTAINER_NAME=indicoerp_mysql \
MYSQL_ADMIN_USER=root \
MYSQL_ADMIN_PASS='COLOCAR_A_PASSWORD_ROOT' \
bash deploy/scripts/18_verify_restore_indicoerp.sh
```

Isto cria uma base temporária de verificação, importa o dump, valida o número de tabelas e regista o manifesto em `restore_verify_latest.env`.

Após o deploy, o healthcheck operacional deve ser executado com o wrapper:

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/19_post_deploy_healthcheck_indicoerp.sh
```

A evidência final esperada é `post_deploy_healthcheck_latest.env`, o link estável `post_deploy_healthcheck_latest.log` e o log histórico `post_deploy_healthcheck_*.log`.

Depois disso, o smoke funcional deve ser executado com login real e módulos críticos:

```bash
cd /var/www/indicoerp/repo
SMOKE_LOGIN_EMAIL='seu-utilizador@dominio.com' \
SMOKE_LOGIN_PASSWORD='sua-password' \
bash deploy/scripts/20_post_deploy_smoke_indicoerp.sh
```

A evidência final esperada é `post_deploy_smoke_latest.env`, o link estável `post_deploy_smoke_latest.log` e o log histórico `post_deploy_smoke_*.log`.

Por fim, o smoke de carga controlado deve correr com `k6` em 25/50 VUs:

```bash
cd /var/www/indicoerp/repo
SMOKE_LOGIN_EMAIL='seu-utilizador@dominio.com' \
SMOKE_LOGIN_PASSWORD='sua-password' \
bash deploy/scripts/21_post_deploy_k6_matrix_indicoerp.sh
```

A evidência final esperada é `post_deploy_k6_matrix_latest.env`, o link estável `post_deploy_k6_matrix_latest.log` e o resumo `k6_matrix_summary_*.md` em `ops/k6/`.

Após `verify`, registar no painel **Go-Live Readiness**:

- estado `Completed`;
- data do teste;
- referência do manifesto `backup_*.manifest`;
- RPO/RTO validado e responsável.

No deploy normal, a referência operacional esperada é o ficheiro `pre_deploy_backup_latest.env` no `BACKUP_DIR`, que aponta para o `backup_*.manifest` e para o dump utilizado antes da activação da release.

## 8) Critério final de aceitação de produção

Produção só é considerada pronta quando:

- Health-check HTTP responde 200/302 estável.
- Login e módulo fiscal carregam sem erro.
- Export SAF-T de teste gera XML válido.
- Não há erro crítico no `laravel.log` durante 15–30 minutos após deploy.
- Backup criado e restore verificado em base temporária com evidência registada no readiness.

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

## 11) Hardening Nginx contra probes comuns

Para aplicar bloqueio a paths sensíveis no servidor atual:

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/09_apply_nginx_hardening_indicoerp.sh
```

O script instala um snippet versionado e injeta o `include` no vhost do domínio.

Depois valide:

```bash
curl -I https://indicoerp.com/.env
curl -I https://indicoerp.com/.git/config
curl -I https://indicoerp.com/storage/logs/laravel.log
```

O esperado é `403` ou `404`.

## 12) Tuning runtime (PHP-FPM + Swap)

Aplicar tuning conservador de PHP-FPM no servidor atual:

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/10_tune_php_fpm_indicoerp.sh
```

## 13) Runtime Redis para cache e sessao

Se o sistema ainda degrada em concorrencia, trocar `cache` e `session` de `file` para `redis`:

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/15_enable_redis_runtime_indicoerp.sh enable
```

Validar estado actual:

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/15_enable_redis_runtime_indicoerp.sh status
```

Se for preciso reverter rapidamente para `file`:

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/15_enable_redis_runtime_indicoerp.sh disable
```

Depois da troca, repetir:

```bash
LOG_SINCE='30 min ago' bash deploy/scripts/06_post_deploy_healthcheck_indicoerp.sh
K6_BASE_URL=https://indicoerp.com K6_DURATION=2m K6_VUS_MATRIX='25,50' \
bash deploy/scripts/14_run_k6_matrix_indicoerp.sh
```

Variáveis opcionais:

- `PM_MAX_CHILDREN` (default `12`)
- `PM_START_SERVERS` (default `4`)
- `PM_MIN_SPARE_SERVERS` (default `2`)
- `PM_MAX_SPARE_SERVERS` (default `6`)
- `PM_MAX_REQUESTS` (default `500`)

Criar swap de segurança (default `2G`):

```bash
cd /var/www/indicoerp/repo
bash deploy/scripts/11_setup_swap_indicoerp.sh
```

Variáveis opcionais:

- `SWAP_SIZE_GB` (default `2`)
- `SWAPPINESS` (default `10`)
- `VFS_CACHE_PRESSURE` (default `50`)

Validar após tuning:

```bash
bash deploy/scripts/07_server_performance_audit.sh
LOG_SINCE='30 min ago' bash deploy/scripts/06_post_deploy_healthcheck_indicoerp.sh
```

## 13) Slow query log (diagnóstico DB)

Ativar e verificar slow query log:

```bash
cd /var/www/indicoerp/repo
export DB_PASS='COLOCAR_PASSWORD_DB'
bash deploy/scripts/12_mysql_slowlog_control_indicoerp.sh enable
```

Nota operacional:

- Se o utilizador da aplicação não tiver `SYSTEM_VARIABLES_ADMIN`, o script tenta fallback para `root` dentro do container `indicoerp_mysql`.
- Se o fallback não for possível, o script falha com mensagem explícita.

Ver status sem alterar:

```bash
bash deploy/scripts/12_mysql_slowlog_control_indicoerp.sh status
```

Relatório rápido das queries lentas:

```bash
bash deploy/scripts/13_mysql_slowlog_report_indicoerp.sh
```

O relatório também inclui top digests via `performance_schema`, mesmo quando o slow log estiver `OFF`.

Desativar após coleta:

```bash
bash deploy/scripts/12_mysql_slowlog_control_indicoerp.sh disable
```

## 14) Teste de carga em matriz (10/25/50 VUs)

Executar cenários sequenciais e gerar resumo markdown:

```bash
cd /var/www/indicoerp/repo
K6_BASE_URL=https://indicoerp.com K6_DURATION=2m bash deploy/scripts/14_run_k6_matrix_indicoerp.sh
```

Personalizar níveis de carga:

```bash
K6_VUS_MATRIX='10,20,30' K6_DURATION=2m bash deploy/scripts/14_run_k6_matrix_indicoerp.sh
```
