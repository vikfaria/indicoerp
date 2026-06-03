# DB-005 Staging Equivalence Audit

Data: 2026-06-03

## Objectivo

Validar que o runtime e o schema do ambiente de execução estão equivalentes ao perfil de producao usado nos scripts de deploy e healthcheck.

## Evidencias executadas

### Runtime

Com overrides de producao:

```bash
APP_ENV=production CACHE_DRIVER=redis CACHE_STORE=redis SESSION_DRIVER=redis SESSION_STORE=redis \
QUEUE_CONNECTION=database REDIS_CLIENT=phpredis php artisan tinker --execute='...'
```

Resultado observado:

- `env=production`
- `cache.default=redis`
- `session.driver=redis`
- `queue.default=database`
- `db.connection=mysql`
- `db.name=test`
- `redis.client=phpredis`
- `redis.default.host=127.0.0.1`
- `redis.cache.db=1`

`php artisan about --only=environment` tambem confirmou:

- `Environment: production`
- `Debug Mode: OFF`

### Schema

Com o mesmo perfil de producao:

```bash
APP_ENV=production CACHE_DRIVER=redis CACHE_STORE=redis SESSION_DRIVER=redis SESSION_STORE=redis \
QUEUE_CONNECTION=database REDIS_CLIENT=phpredis php artisan migrate --force --no-interaction
```

Resultado:

- `Nothing to migrate.`

Verificacao final:

```bash
APP_ENV=production CACHE_DRIVER=redis CACHE_STORE=redis SESSION_DRIVER=redis SESSION_STORE=redis \
QUEUE_CONNECTION=database REDIS_CLIENT=phpredis php artisan migrate:status | awk '/Pending/{print; found=1} END{if(!found) print "NO_PENDING"}'
```

Resultado:

- `NO_PENDING`

## Conclusao

No perfil de producao, o ambiente local ficou coerente com os requisitos de runtime esperados para staging/producao:

- `mysql` para a base de dados
- `redis` para cache e sessao
- `database` para queue
- `phpredis` como cliente Redis
- sem migrations pendentes

O item `DB-005` fica validado a nivel de configuracao/runtime e schema.
