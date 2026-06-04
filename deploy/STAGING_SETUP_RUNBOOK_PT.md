# Runbook de Setup de Staging — ERPGo/SysGest Moçambique

Este runbook prepara um ambiente de `staging` no mesmo servidor, separado da produção, para validar a release antes do go-live.

O staging aqui é intencionalmente local ou interno:

- não depende de DNS público;
- não depende de certificação SSL inicial;
- usa uma base de dados separada;
- usa serviços systemd próprios;
- permite validar migrations, E2E, backup/restore, smoke e `k6` antes da produção.

## 1) Pré-requisitos

Executar como `root` ou com `sudo`:

```bash
cd /var/www/indicoerp/repo
```

Definir as passwords do staging:

```bash
export DB_ROOT_PASSWORD='trocar-root-staging'
export DB_PASSWORD='trocar-user-staging'
```

Se quiseres usar o mesmo Redis do servidor, não precisas de mais nada. Se o servidor não tiver Redis, desactiva o passo opcional:

```bash
export ENABLE_REDIS_RUNTIME=0
```

## 2) Setup inicial de staging

O caminho recomendado é correr o wrapper único:

```bash
sudo DOMAIN='staging.indicoerp.local' \
APP_URL='http://staging.indicoerp.local' \
APP_DIR='/var/www/indicoerp-staging' \
DB_ROOT_PASSWORD='trocar-root-staging' \
DB_PASSWORD='trocar-user-staging' \
bash deploy/scripts/23_setup_staging_environment.sh
```

O que este comando faz:

1. cria a base de dados MySQL do staging em container Docker;
2. cria o `.env` do staging em `/var/www/indicoerp-staging/shared/.env`;
3. empacota a release a partir do repositório actual;
4. aplica a release em `/var/www/indicoerp-staging`;
5. configura Nginx para `staging.indicoerp.local`;
6. cria serviços systemd próprios para queue e scheduler;
7. opcionalmente activa Redis runtime com DBs separadas.

## 3) O que esperar depois do setup

Depois do comando acima, deves conseguir:

- aceder ao staging por `http://staging.indicoerp.local`;
- ver a aplicação a arrancar com o `.env` do staging;
- ter uma base de dados separada da produção;
- ter serviços queue/scheduler separados da produção.

Se o nome `staging.indicoerp.local` não resolver no teu computador, adiciona no teu `hosts`:

```text
IP_DO_SERVIDOR staging.indicoerp.local
```

## 4) Validação posterior ao setup

Depois de o staging estar de pé, valida com:

```bash
sudo systemctl status nginx --no-pager
sudo systemctl status php8.2-fpm --no-pager
sudo systemctl status indicoerp-staging-queue --no-pager
sudo systemctl status indicoerp-staging-scheduler --no-pager
```

```bash
curl -I http://staging.indicoerp.local
```

```bash
docker ps --filter name=indicoerp_staging_mysql
docker logs --tail=100 indicoerp_staging_mysql
```

## 5) Próximo passo

Depois do setup, corre o runbook de validação:

[STAGING_VALIDATION_RUNBOOK_PT.md](./STAGING_VALIDATION_RUNBOOK_PT.md)
