# Plano de Diagnostico e Otimizacao de Desempenho - IndicoERP

Data: 2026-05-25  
Ambiente analisado: Producao `https://indicoerp.com`  
Servidor atual: Hostinger KVM 2, Ubuntu 24.04 LTS, 2 vCPU, 8 GB RAM, 100 GB disco

## 1. Objetivo

Este plano define uma abordagem pratica e faseada para diagnosticar, medir e melhorar o desempenho do IndicoERP, com foco nos pontos relatados:

- Login lento.
- Mudanca de paginas lenta.
- Selecionar menus lento.
- Experiencia geral pouco fluida.
- Possivel impacto de servidor, base de dados, codigo backend, APIs externas, frontend, caches e configuracao de producao.

O objetivo nao e adivinhar a causa, mas criar uma linha de base, identificar gargalos reais e aplicar melhorias com impacto mensuravel.

## 2. Resumo Executivo

Pelos dados enviados de producao, o servidor nao aparenta estar saturado no momento da amostra:

- CPU com cerca de 85% a 90% livre.
- Load average abaixo de 1 em servidor com 2 vCPU.
- I/O wait praticamente zero.
- Disco com cerca de 35% de uso.
- Memoria disponivel acima de 3 GB.
- Nginx, PHP-FPM, queue e scheduler ativos.

Isto indica que a lentidao provavelmente esta mais relacionada com trabalho excessivo por request, queries nao otimizadas, carregamento de modulos, partilha excessiva de dados no Inertia, chamadas externas no login e bundle frontend pesado, e nao simplesmente com falta de CPU/RAM.

Tambem existem sinais de ruido operacional:

- Muitas tentativas de bots procurando `.env`, `.git`, configs, WordPress e backups.
- Muitos `POST /api/public/meta-webhook` retornando `403`, possivelmente webhook mal configurado, ruido de integracao ou trafego repetitivo.
- Logs de deprecacao PHP em producao.
- Dois processos MySQL aparentes, um como `999` e outro como `mysql`, que devem ser verificados para confirmar se ha instancias duplicadas ou containers relacionados.
- Scheduler Laravel executando `schedule:work` e registando a cada minuto `No scheduled commands are ready to run`, gerando ruido de journal.

Conclusao inicial: antes de aumentar o servidor, devemos medir e otimizar aplicacao, base de dados, cache e frontend. Upgrade de servidor so deve ser considerado se, apos otimizacoes, os indicadores reais ainda mostrarem saturacao ou baixa margem de crescimento.

## 3. Estado Observado em Producao

### 3.1 Infraestrutura

Dados observados:

- SO: Ubuntu 24.04 LTS.
- Plano: KVM 2.
- CPU: 2 cores.
- RAM: 8 GB.
- Disco: 100 GB.
- Localizacao: Alemanha - Frankfurt.
- Uptime: 63 dias.

Metricas reportadas:

- Load average: aproximadamente `0.49, 0.47, 0.43`.
- CPU idle: aproximadamente `86%` a `90%`.
- I/O wait: `0.0%` a `0.12%`.
- Memoria total: `7.8 GiB`.
- Memoria disponivel: cerca de `3.2 GiB`.
- Swap: `0B`.
- Disco `/`: `96G`, usado `34G`, livre `63G`, uso `35%`.

Interpretacao:

- CPU nao esta no limite.
- Disco nao aparenta ser gargalo.
- Memoria esta aceitavel, mas nao existe swap de seguranca.
- A ausencia de swap nao e causa direta de lentidao no momento, mas aumenta risco de OOM se houver pico de memoria.

### 3.2 Servicos

Servicos ativos:

- `php8.2-fpm`: ativo.
- `nginx`: ativo.
- `indicoerp-queue`: ativo.
- `indicoerp-scheduler`: ativo.

PHP-FPM observado:

- Processos: master + 2 workers.
- Status: `Processes active: 0, idle: 2`.
- Requests desde restart: `17`.
- Slow: `0`.
- Trafego: `0.00 req/sec`.

Interpretacao:

- A amostra nao mostra pressao de trafego.
- O pool PHP-FPM pode estar conservador, mas nao ha evidencia de esgotamento no momento.
- Para producao SaaS, precisamos medir concorrencia real antes de aumentar agressivamente `pm.max_children`.

### 3.3 Trafego e Logs

Foram observadas muitas requisicoes suspeitas:

- `GET /.env`
- `GET /.git/config`
- `GET /config.php`
- `GET /docker-compose.yml`
- `GET /storage/logs/laravel.log`
- `GET /wp-admin/install.php`

Tambem foram observadas muitas chamadas:

- `POST /api/public/meta-webhook` com status `403`.

Interpretacao:

- O site esta recebendo varreduras automaticas comuns da internet.
- As respostas 404/403 protegem os ficheiros, mas ainda consomem algum processamento.
- Se os 403 do webhook forem de integracao real, ha uma configuracao incorreta. Se forem ruido, devem ser rate-limited ou bloqueados.

### 3.4 Erros/Warnings

Erro recorrente no Nginx/PHP:

```text
PHP Deprecated: Optional parameter $type declared before required parameter $created_by
packages/workdo/Contract/src/Models/Contract.php on line 101
```

Interpretacao:

- Nao parece ser causa principal de lentidao.
- Mas gera ruido em logs e pode impactar performance se ocorrer muitas vezes.
- Deve ser corrigido para reduzir custo de logging e preparar compatibilidade futura.

## 4. Achados Tecnicos no Codigo

### 4.1 Login com chamada externa sincronica

Ficheiro:

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

Problema identificado:

- O login chama `logLoginHistory()`.
- Esse metodo chama `getLocationData($ip)`.
- `getLocationData()` faz request externo para:

```php
Http::timeout(5)->get("http://ip-api.com/json/{$ip}")
```

Risco:

- Cada login pode ficar preso ate 5 segundos se a API externa estiver lenta.
- Mesmo quando funciona, adiciona latencia desnecessaria ao fluxo mais sensivel do sistema.

Recomendacao:

- Remover chamada externa do request de login.
- Gravar login imediatamente.
- Enviar job para fila para resolver localizacao por IP em background.
- Adicionar cache por IP por 7 a 30 dias.
- Se a API falhar, nao deve afetar login.

Prioridade: P0.

### 4.2 Superadmin executa verificacoes pesadas no login

Ficheiro:

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

Problema identificado:

- Para superadmin, o login executa verificacoes como `Artisan::call('migrate:status')`.
- Tambem verifica pacotes/modulos.

Risco:

- Login do superadmin pode ficar lento.
- Comandos Artisan durante request HTTP devem ser evitados sempre que possivel.

Recomendacao:

- Mover verificacoes para job agendado.
- Guardar resultado em cache/tabela de status.
- No login, apenas ler o ultimo resultado.

Prioridade: P1.

### 4.3 Dados partilhados do Inertia demasiado pesados

Ficheiro:

- `app/Http/Middleware/HandleInertiaRequests.php`

Problema identificado:

Em cada request Inertia sao calculados ou carregados varios dados:

- Permissoes do utilizador.
- Roles do utilizador.
- Modulos ativos.
- Todos os modulos.
- Settings globais.
- Settings da empresa.
- Currencies.
- Linguagens.
- Leitura de ficheiro `language.json`.
- Pacotes/modulos.

Risco:

- Cada clique de menu pode repetir trabalho desnecessario.
- O payload global enviado ao frontend pode ficar grande.
- Permissoes, settings e modulos mudam raramente, mas sao recalculados frequentemente.

Recomendacao:

- Usar cache por utilizador/empresa.
- Usar lazy shared props no Inertia para dados nao necessarios em todas as paginas.
- Separar props globais essenciais de props administrativas.
- Invalidar cache ao alterar roles, permissoes, settings ou modulos.

Prioridade: P0/P1.

### 4.4 Scan de modulos em disco

Ficheiros:

- `app/Classes/Module.php`
- `app/Helpers/Helper.php`
- `app/Http/Middleware/PlanModuleCheck.php`

Problema identificado:

- `Module::allModules()` faz leitura de diretorios em `packages/workdo`.
- `Module::has()` pode chamar `allModules()`.
- `module_is_active()` e `ActivatedModule()` podem ser usados frequentemente.
- O projeto contem muitos pacotes em `packages/workdo`.

Risco:

- Leitura repetida de filesystem em request web.
- Custo multiplicado em cada navegacao.

Recomendacao:

- Cachear lista de modulos instalados.
- Cachear modulos ativos por empresa/utilizador.
- Preaquecer cache no deploy.
- Invalidar cache apenas quando pacote/modulo for instalado, removido, ativado ou desativado.

Prioridade: P0/P1.

### 4.5 Dashboard com agregacoes potencialmente pesadas

Exemplo identificado:

- `packages/workdo/Account/src/Http/Controllers/DashboardController.php`

Problema identificado:

- Dashboards usam varios `count`, `sum`, `whereHas` e loops mensais.
- Consultas de dashboard tendem a crescer com o volume de dados.

Risco:

- Dashboard inicial apos login pode parecer lento.
- Multiempresa/SaaS aumenta necessidade de indices por `company_id`/`created_by`/datas.

Recomendacao:

- Ativar slow query log.
- Medir queries reais por rota.
- Adicionar indices com base em queries reais.
- Cachear estatisticas do dashboard por empresa por 1 a 5 minutos.
- Para dashboards financeiros, usar cache invalidavel quando houver lancamento/documento relevante.

Prioridade: P1.

### 4.6 Frontend com chunks grandes

Observacao de build:

```text
Some chunks are larger than 500 kB after minification
```

Achados:

- `resources/js/app.tsx` usa `import.meta.glob` lazy para paginas.
- Porem existem imports eager em hooks/utilitarios:
  - `resources/js/hooks/useFormFields.ts`
  - `resources/js/hooks/usePageButtons.ts`
  - `resources/js/utils/menu.ts`
  - `resources/js/utils/settings.ts`

Risco:

- O browser pode carregar codigo de muitos modulos antes de ser necessario.
- Navegacao parece lenta, especialmente em rede movel ou computador mais fraco.

Recomendacao:

- Gerar bundle analysis.
- Separar bibliotecas pesadas em chunks:
  - Syncfusion.
  - FullCalendar.
  - TipTap.
  - Recharts.
  - html2pdf.
  - qrcode.
  - jsbarcode.
  - datepickers.
- Trocar imports eager por lazy onde possivel.
- Carregar menus/settings de modulos sob demanda ou cacheados.

Prioridade: P1/P2.

## 5. Hipoteses de Gargalo

| Hipotese | Evidencia | Probabilidade | Impacto |
|---|---:|---:|---:|
| Chamada externa no login bloqueia request | `ip-api.com` com timeout 5s | Alta | Alto |
| Props globais Inertia pesadas em cada pagina | Middleware carrega permissoes, settings, modulos | Alta | Alto |
| Scan de modulos em disco | Muitos pacotes `workdo` e chamadas frequentes | Alta | Medio/Alto |
| Dashboards com queries agregadas sem cache | Varios sums/counts/loops | Media/Alta | Alto |
| Frontend bundle pesado | Warning Vite chunks >500kB | Media/Alta | Medio/Alto |
| Bots e webhooks 403 criam ruido | Access log com varreduras e meta-webhook repetido | Media | Medio |
| Servidor insuficiente | CPU/RAM/IO normais na amostra | Baixa no momento | Alto se crescer |
| PHP-FPM subdimensionado | Apenas 2 idle workers, mas baixo trafego na amostra | Media, precisa medir | Medio |
| MySQL sem indices adequados | Ainda nao medido com slow query log | Media | Alto |

## 6. Metas de Desempenho

As metas abaixo devem ser usadas para validar se as otimizacoes produziram resultado real.

### 6.1 Backend

- Login: p95 abaixo de 800 ms no backend, sem contar tempo de rede/browser.
- Navegacao comum Inertia: p95 abaixo de 400 a 700 ms.
- Dashboard: p95 abaixo de 1.2 s.
- Requests comuns: abaixo de 50 queries SQL por request.
- Slow queries acima de 500 ms: zero ou excecoes justificadas.

### 6.2 Frontend

- Primeiro carregamento autenticado: reduzir JS inicial carregado.
- Chunks principais gzip/brotli: alvo entre 250 KB e 350 KB sempre que viavel.
- Bibliotecas pesadas devem ser carregadas apenas nas paginas que precisam delas.
- Menu e layout devem responder imediatamente apos clique, com estados de loading claros.

### 6.3 Infraestrutura

- CPU idle em horario normal: acima de 40%.
- Load average sustentado: idealmente abaixo de 1.5 em 2 vCPU.
- I/O wait sustentado: abaixo de 5%.
- Memoria disponivel: acima de 1 GB.
- PHP-FPM sem fila de processos.
- MySQL sem queries longas recorrentes.

## 7. Plano de Diagnostico

### 7.1 Comandos de servidor

Executar em producao:

```bash
uptime
free -h
df -h
top -o %CPU
iostat -xz 1 10
ss -s
```

Se `iostat` nao existir:

```bash
apt update
apt install -y sysstat
```

Objetivo:

- Confirmar CPU, RAM, disco, I/O, conexoes e saturacao.

### 7.2 PHP-FPM

```bash
systemctl status php8.2-fpm --no-pager
grep -E '^(pm|pm\.max_children|pm\.start_servers|pm\.min_spare_servers|pm\.max_spare_servers|request_slowlog_timeout|slowlog)' /etc/php/8.2/fpm/pool.d/www.conf
ps -ylC php-fpm8.2 --sort:rss
```

Objetivo:

- Verificar se existem poucos workers.
- Medir memoria media por worker.
- Calcular `pm.max_children` seguro.

Formula pratica:

```text
pm.max_children = memoria reservada para PHP-FPM / memoria media por worker
```

Exemplo conservador:

- RAM total: 8 GB.
- Reservar para SO, MySQL, Redis, Nginx, outros servicos: 4 GB.
- Sobra PHP-FPM: 2 GB a 3 GB.
- Se cada worker usar 150 MB: `2048 / 150 = 13`.
- Valor inicial seguro: 8 a 12 workers.

Nao aplicar sem medir RSS real dos workers sob carga.

### 7.3 Laravel

```bash
cd /var/www/indicoerp/repo
php artisan about
php artisan route:list --except-vendor | wc -l
php artisan config:show app.debug
php artisan config:show cache.default
php artisan config:show session.driver
php artisan config:show queue.default
```

Validar `.env`:

```bash
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|CACHE_STORE|CACHE_DRIVER|SESSION_DRIVER|QUEUE_CONNECTION|LOG_LEVEL|DB_HOST|DB_PORT|DB_DATABASE)' .env
```

Configuracao recomendada para producao:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://indicoerp.com
LOG_LEVEL=warning
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Nota:

- Antes de mudar `CACHE_STORE`, `SESSION_DRIVER` ou `QUEUE_CONNECTION` para Redis, confirmar que Redis esta ativo e configurado corretamente.

### 7.4 MySQL

Os comandos SQL devem ser executados atraves do cliente MySQL. Nao executar `SHOW PROCESSLIST;` diretamente no shell.

Exemplo correto:

```bash
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SHOW PROCESSLIST;"
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SHOW GLOBAL STATUS LIKE 'Slow_queries';"
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SHOW VARIABLES LIKE 'slow_query_log';"
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SHOW VARIABLES LIKE 'long_query_time';"
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SHOW VARIABLES LIKE 'max_connections';"
```

Ativar slow query log temporariamente:

```bash
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SET GLOBAL slow_query_log = 'ON';"
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SET GLOBAL long_query_time = 0.5;"
mysql -h127.0.0.1 -P3307 -uindicoerp_user -p indicoerp -e "SHOW VARIABLES LIKE 'slow_query_log_file';"
```

Depois de 30 a 60 minutos de uso real:

```bash
mysqldumpslow -s t -t 20 /var/log/mysql/mysql-slow.log
```

O caminho do slow log pode ser diferente. Confirmar com `slow_query_log_file`.

Objetivo:

- Identificar queries lentas reais.
- Criar indices com base em evidencia.
- Evitar alterar schema por suposicao.

### 7.5 Redis

```bash
redis-cli ping
redis-cli info memory
redis-cli info stats
redis-cli info clients
```

Objetivo:

- Confirmar se Redis esta disponivel.
- Avaliar se pode suportar cache, sessions e queues.

### 7.6 Nginx

Analisar rotas mais chamadas:

```bash
awk '{print $7}' /var/log/nginx/access.log | sort | uniq -c | sort -nr | head -30
```

Analisar IPs mais ativos:

```bash
awk '{print $1}' /var/log/nginx/access.log | sort | uniq -c | sort -nr | head -30
```

Analisar status HTTP:

```bash
awk '{print $9}' /var/log/nginx/access.log | sort | uniq -c | sort -nr
```

Recomendacao adicional:

- Configurar log format com `request_time` e `upstream_response_time` para separar tempo de Nginx, PHP e rede.

Exemplo de log format:

```nginx
log_format timed '$remote_addr - $remote_user [$time_local] '
                 '"$request" $status $body_bytes_sent '
                 '"$http_referer" "$http_user_agent" '
                 'rt=$request_time urt=$upstream_response_time';
```

### 7.7 Frontend

Gerar build e inspecionar chunks:

```bash
npm run build
ls -lh public/build/assets | sort -k5 -h
```

Adicionar bundle visualizer em ambiente local/staging:

```bash
npm install -D rollup-plugin-visualizer
```

Objetivo:

- Identificar quais bibliotecas estao nos chunks grandes.
- Separar dependencias pesadas por funcionalidade.

## 8. Backlog de Implementacao

### PERF-001 - Remover chamada externa sincronica do login

Prioridade: P0  
Esforco: 0.5 a 1 dia  
Impacto esperado: Alto

Escopo:

- Alterar `AuthenticatedSessionController`.
- Gravar historico de login sem bloquear.
- Criar job `ResolveLoginLocationJob`.
- Cachear resultado por IP.
- Definir timeout baixo na job.
- Em caso de falha, nao gerar erro para o utilizador.

Aceite:

- Login nao depende de `ip-api.com`.
- Login permanece funcional se API externa estiver offline.
- Historico de login continua sendo preenchido, ainda que localizacao seja atualizada depois.

### PERF-002 - Cachear dados globais do Inertia

Prioridade: P0/P1  
Esforco: 1 a 2 dias  
Impacto esperado: Alto

Escopo:

- Rever `HandleInertiaRequests`.
- Cachear permissoes por user.
- Cachear roles por user.
- Cachear settings globais.
- Cachear settings por empresa.
- Cachear currencies.
- Cachear language config.
- Transformar dados nao essenciais em lazy props ou endpoints sob demanda.

Chaves de cache sugeridas:

```text
user:{id}:permissions
user:{id}:roles
company:{id}:settings
global:settings
global:currencies
global:languages
company:{id}:active_modules
```

Invalidacao:

- Ao alterar role/permissao: limpar cache do user/empresa.
- Ao alterar settings: limpar cache de settings.
- Ao ativar/desativar modulo: limpar cache de modulos.

Aceite:

- Props globais menores.
- Menos queries por navegacao.
- Navegacao entre paginas mais rapida.

### PERF-003 - Cachear lista e estado de modulos

Prioridade: P1  
Esforco: 1 dia  
Impacto esperado: Medio/Alto

Escopo:

- Cachear resultado de `Module::allModules()`.
- Cachear `ActivatedModule()` por empresa/utilizador.
- Evitar scan de `packages/workdo` por request.
- Criar comando para limpar/preaquecer cache de modulos no deploy.

Aceite:

- Requests comuns nao fazem scan repetido de diretorios.
- Ativar/desativar modulo invalida cache corretamente.

### PERF-004 - Otimizar dashboard e queries de agregacao

Prioridade: P1  
Esforco: 2 a 4 dias  
Impacto esperado: Alto

Escopo:

- Ativar slow query log.
- Medir dashboard de empresa normal com dados reais.
- Identificar queries acima de 500 ms.
- Adicionar indices baseados em evidencia.
- Cachear cards e graficos por empresa por 1 a 5 minutos.

Indices provaveis, a confirmar:

```text
company_id
created_by
customer_id
vendor_id
account_id
document_date
issue_date
created_at
status
```

Aceite:

- Dashboard nao executa queries lentas recorrentes.
- Dashboard carrega abaixo de 1.2 s p95.
- Query count reduzido.

### PERF-005 - Reduzir bundle frontend e carregamento eager

Prioridade: P1/P2  
Esforco: 2 a 5 dias  
Impacto esperado: Medio/Alto

Escopo:

- Gerar relatorio de bundle.
- Separar libs pesadas em chunks.
- Rever imports eager em:
  - `resources/js/hooks/useFormFields.ts`
  - `resources/js/hooks/usePageButtons.ts`
  - `resources/js/utils/menu.ts`
  - `resources/js/utils/settings.ts`
- Carregar modulos sob demanda.
- Evitar que paginas simples carreguem codigo de modulos complexos.

Aceite:

- Chunks principais menores.
- Tempo de carregamento inicial reduzido.
- Mudanca de paginas mais fluida.

### PERF-006 - Nginx/WAF/rate limit para bots e webhook

Prioridade: P1  
Esforco: 0.5 a 1 dia  
Impacto esperado: Medio

Escopo:

- Bloquear caminhos sensiveis conhecidos no Nginx:
  - `.env`
  - `.git`
  - `composer.json`
  - `storage/logs`
  - backups comuns
- Aplicar rate limit a rotas publicas sensiveis.
- Investigar `POST /api/public/meta-webhook` com 403.
- Se webhook for real, corrigir token/assinatura.
- Se nao for usado, desativar rota ou bloquear origem.

Exemplo conceitual Nginx:

```nginx
location ~ /\.(env|git|svn|hg) {
    deny all;
    return 404;
}

location ~* /(composer\.json|composer\.lock|package\.json|docker-compose\.ya?ml|storage/logs/) {
    deny all;
    return 404;
}
```

Aceite:

- Menos ruido de bots chegando ao Laravel.
- Logs mais limpos.
- Webhook Meta sem 403 repetitivo indevido.

### PERF-007 - Corrigir warnings PHP em producao

Prioridade: P2  
Esforco: 0.5 dia  
Impacto esperado: Baixo/Medio

Escopo:

- Corrigir assinatura do metodo em `packages/workdo/Contract/src/Models/Contract.php`.
- Procurar warnings similares.
- Garantir `APP_DEBUG=false`.
- Ajustar `LOG_LEVEL=warning` ou `error`, conforme necessidade operacional.

Aceite:

- Nginx error log sem deprecations repetidas.
- Menos custo de escrita de logs.

### PERF-008 - Tuning PHP-FPM

Prioridade: P2  
Esforco: 0.5 a 1 dia  
Impacto esperado: Medio, dependente de trafego

Escopo:

- Medir memoria media dos workers.
- Configurar `pm.max_children`, `pm.start_servers`, `pm.min_spare_servers`, `pm.max_spare_servers`.
- Ativar slowlog PHP-FPM temporariamente.

Exemplo inicial conservador, a validar:

```ini
pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
request_slowlog_timeout = 5s
slowlog = /var/log/php8.2-fpm-slow.log
```

Aceite:

- Sem fila de PHP-FPM em uso normal.
- Sem uso excessivo de memoria.
- Requests lentos identificaveis via slowlog.

### PERF-009 - Criar swap de seguranca

Prioridade: P2  
Esforco: 0.25 dia  
Impacto esperado: Baixo como performance, medio como estabilidade

Escopo:

- Criar swapfile de 2 GB.
- Ajustar `vm.swappiness` baixo.

Comandos:

```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
sysctl vm.swappiness=10
echo 'vm.swappiness=10' > /etc/sysctl.d/99-swappiness.conf
```

Aceite:

- Sistema protegido contra picos de memoria.
- Swap nao deve ser usado continuamente.

### PERF-010 - Observabilidade permanente

Prioridade: P2/P3  
Esforco: 1 a 2 dias  
Impacto esperado: Alto para manutencao

Escopo:

- Logs Nginx com tempo de request.
- Slow query log rotativo.
- Monitor de CPU/RAM/disco.
- Alertas para 5xx, fila, uso de disco e memoria.
- Ferramenta APM opcional:
  - Laravel Pulse.
  - Sentry Performance.
  - New Relic.
  - OpenTelemetry.

Aceite:

- Equipa consegue dizer qual rota esta lenta sem depender de percepcao subjetiva.
- Alertas antes de falha.

## 9. Fases de Implementacao

### Fase 0 - Baseline e Medicao

Duracao estimada: 0.5 a 1 dia  
Prioridade: P0

Objetivo:

- Medir antes de alterar.
- Criar baseline de tempo de login, dashboard, navegacao e queries.

Tarefas:

- Executar comandos de servidor.
- Ativar slow query log temporario.
- Medir login com utilizador normal e superadmin.
- Medir dashboard apos login.
- Medir navegacao entre 5 paginas comuns.
- Anotar TTFB e tempo total no browser DevTools.
- Confirmar configuracao `.env`.
- Confirmar Redis.

Entregaveis:

- Tabela de tempos atuais.
- Top 20 queries lentas.
- Top rotas mais chamadas.
- Tamanho dos principais chunks frontend.

### Fase 1 - Quick Wins de Producao

Duracao estimada: 0.5 a 1 dia  
Prioridade: P0/P1

Objetivo:

- Remover ruido e riscos imediatos.

Tarefas:

- Garantir `APP_DEBUG=false`.
- Garantir caches Laravel:
  - `config:cache`
  - `route:cache`
  - `view:cache`
- Validar Redis para cache/sessao/fila.
- Corrigir ou reduzir logs de deprecacao.
- Bloquear caminhos sensiveis no Nginx.
- Investigar `meta-webhook` 403.

Entregaveis:

- Logs mais limpos.
- Menos requests indesejados chegando ao Laravel.
- Ambiente de producao corretamente cacheado.

### Fase 2 - Login Rapido

Duracao estimada: 0.5 a 1 dia  
Prioridade: P0

Objetivo:

- Garantir que login nao dependa de API externa nem comandos pesados.

Tarefas:

- Mover lookup de IP para job.
- Cachear localizacao por IP.
- Remover `Artisan::call('migrate:status')` do fluxo direto de login.
- Cachear status administrativo.

Entregaveis:

- Login mais previsivel.
- Timeout externo nao afeta utilizador.

### Fase 3 - Navegacao e Inertia

Duracao estimada: 1 a 3 dias  
Prioridade: P0/P1

Objetivo:

- Reduzir custo de cada clique e mudanca de pagina.

Tarefas:

- Cachear shared props.
- Reduzir payload global.
- Cachear modulos ativos.
- Cachear lista de modulos.
- Evitar leituras repetidas de ficheiros e diretorios.

Entregaveis:

- Navegacao mais rapida.
- Menos queries por request.
- Menor payload Inertia.

### Fase 4 - Base de Dados e Dashboards

Duracao estimada: 2 a 4 dias  
Prioridade: P1

Objetivo:

- Eliminar queries lentas e agregacoes repetitivas.

Tarefas:

- Analisar slow query log.
- Criar indices.
- Otimizar dashboards.
- Cachear estatisticas.
- Rever queries com `whereHas` em loops.

Entregaveis:

- Dashboard mais rapido.
- Queries lentas documentadas e corrigidas.
- Indices versionados por migration.

### Fase 5 - Frontend e Bundle

Duracao estimada: 2 a 5 dias  
Prioridade: P1/P2

Objetivo:

- Melhorar tempo de carregamento e fluidez visual.

Tarefas:

- Gerar bundle visualizer.
- Separar bibliotecas pesadas.
- Rever imports eager.
- Lazy-load componentes de modulos.
- Melhorar estados de loading em navegacao.

Entregaveis:

- Chunks menores.
- Menos JS inicial.
- Navegacao percebida mais rapida.

### Fase 6 - Tuning de Servidor

Duracao estimada: 0.5 a 1 dia  
Prioridade: P2

Objetivo:

- Ajustar capacidade do PHP-FPM e estabilidade.

Tarefas:

- Medir RSS dos workers.
- Ajustar PHP-FPM.
- Criar swap de seguranca.
- Validar MySQL buffer/config se necessario.
- Confirmar se ha MySQL duplicado/container nao intencional.

Entregaveis:

- PHP-FPM dimensionado.
- Menor risco de OOM.
- Ambiente mais previsivel.

### Fase 7 - Teste de Carga e Validacao Final

Duracao estimada: 1 a 2 dias  
Prioridade: P2

Objetivo:

- Confirmar que as melhorias funcionam com uso simultaneo.

Tarefas:

- Criar cenarios de teste:
  - Login.
  - Dashboard.
  - Menu/navegacao.
  - Listagem de faturas.
  - Relatorios.
  - Contabilidade/SCE.
- Testar com 10, 25 e 50 usuarios virtuais.
- Medir p50, p95, p99.
- Comparar antes/depois.

Ferramentas possiveis:

- `k6`.
- `autocannon`.
- ApacheBench apenas para endpoints simples.

Entregaveis:

- Relatorio antes/depois.
- Decisao fundamentada sobre upgrade ou manutencao do servidor atual.

## 10. Prioridades Recomendadas

| Prioridade | Item | Impacto | Esforco | Justificacao |
|---|---|---:|---:|---|
| P0 | Medir baseline | Alto | Baixo | Sem medicao nao ha prova da causa |
| P0 | Remover API externa do login | Alto | Baixo | Pode causar ate 5s de espera |
| P0 | Cachear props Inertia essenciais | Alto | Medio | Afeta todas as navegacoes |
| P1 | Cachear modulos/lista ativa | Medio/Alto | Baixo/Medio | Evita scans e queries repetidas |
| P1 | Slow query log + indices | Alto | Medio | Corrige gargalos reais de BD |
| P1 | Otimizar dashboards | Alto | Medio | Dashboard costuma ser primeira impressao apos login |
| P1 | Bloquear bots e corrigir webhook 403 | Medio | Baixo | Reduz ruido e consumo desnecessario |
| P2 | Frontend bundle split | Medio/Alto | Medio/Alto | Melhora carregamento e UX |
| P2 | Tuning PHP-FPM | Medio | Baixo | Importante quando houver mais concorrencia |
| P2 | Swap de seguranca | Baixo/Medio | Baixo | Estabilidade |
| P3 | APM/observabilidade avancada | Alto continuo | Medio | Ajuda manutencao futura |

## 11. Plano de Execucao Sugerido

### Semana 1

- Fase 0: baseline completo.
- Fase 1: quick wins de producao.
- Fase 2: login rapido.
- Inicio da Fase 3: cache de Inertia/modulos.

Resultado esperado:

- Login mais rapido.
- Menos trabalho repetido por request.
- Logs mais limpos.
- Primeira melhoria perceptivel para utilizadores.

### Semana 2

- Concluir Fase 3.
- Fase 4: slow queries, indices e dashboards.
- Inicio da Fase 5: bundle analysis.

Resultado esperado:

- Navegacao mais rapida.
- Dashboards mais leves.
- Banco de dados mais eficiente.

### Semana 3

- Concluir Fase 5.
- Fase 6: tuning PHP-FPM/swap.
- Fase 7: teste de carga.

Resultado esperado:

- Sistema validado com concorrencia.
- Decisao clara sobre manter KVM 2 ou migrar para plano superior.

## 12. Decisao Sobre Upgrade do Servidor

Com base nos dados atuais, nao e recomendavel fazer upgrade imediatamente como primeira medida.

Upgrade deve ser considerado se, apos as otimizacoes:

- Load average ficar constantemente acima de 1.5 a 2.0.
- CPU idle cair frequentemente abaixo de 25%.
- I/O wait ficar acima de 5% de forma sustentada.
- MySQL consumir CPU alta com queries ja otimizadas.
- PHP-FPM precisar de mais workers do que a memoria permite.
- Teste de carga mostrar p95 ruim por saturacao de CPU/RAM.

Se for necessario upgrade, a proxima configuracao recomendada seria:

- 4 vCPU.
- 16 GB RAM.
- SSD/NVMe.
- Redis dedicado ou bem isolado.
- MySQL com buffer pool ajustado.

Mas primeiro devemos corrigir os gargalos de aplicacao. Um servidor maior pode apenas mascarar problemas e aumentar custo.

## 13. Riscos e Cuidados

### Cache

Risco:

- Dados de permissoes, roles, settings ou modulos ficarem desatualizados.

Mitigacao:

- Definir invalidacao clara.
- Criar comando para limpar cache.
- Testar alteracao de permissoes, troca de empresa e ativacao/desativacao de modulos.

### Sessao em Redis

Risco:

- Se Redis falhar, utilizadores podem perder sessao.

Mitigacao:

- Confirmar Redis gerido/estavel.
- Monitorar Redis.
- Fazer alteracao em janela controlada.

### Indices

Risco:

- Indices excessivos aumentam custo de escrita.

Mitigacao:

- Criar indices apenas com base em slow query log e `EXPLAIN`.

### Frontend

Risco:

- Lazy-loading mal feito pode quebrar paginas de modulos.

Mitigacao:

- Testar rotas principais.
- Build local e staging antes de producao.

### Nginx/WAF

Risco:

- Bloquear endpoint legitimo por regra agressiva.

Mitigacao:

- Comecar bloqueando apenas caminhos sensiveis obvios.
- Monitorar 403.
- Documentar excecoes.

## 14. Checklist de Validacao Antes/Depois

### Antes

- Medir login normal.
- Medir login superadmin.
- Medir dashboard.
- Medir navegacao entre paginas.
- Capturar queries lentas.
- Capturar tamanho dos assets.
- Capturar top URLs do Nginx.

### Depois

- Repetir os mesmos testes.
- Comparar p50, p95 e p99.
- Confirmar que nao houve regressao funcional.
- Confirmar que logs nao mostram novos erros.
- Confirmar que fila e scheduler continuam ativos.
- Confirmar que cache invalida corretamente.

## 15. Indicadores de Sucesso

O trabalho deve ser considerado bem sucedido quando:

- Login deixa de depender de APIs externas sincronas.
- Navegacao entre menus fica perceptivelmente mais rapida.
- Dashboard principal carrega de forma consistente.
- Queries lentas recorrentes sao eliminadas ou reduzidas.
- Payload global do Inertia fica menor.
- Bundles frontend ficam mais segmentados.
- Bots e webhooks indevidos deixam de poluir logs.
- O servidor atual passa a ter margem clara para crescimento.

## 16. Proximo Passo Recomendado

Executar a Fase 0 e, logo em seguida, implementar PERF-001 e PERF-002.

Ordem recomendada:

1. Criar baseline com tempos reais.
2. Remover chamada externa do login.
3. Cachear shared props do Inertia.
4. Cachear modulos.
5. Ativar slow query log e otimizar dashboards.
6. Reduzir bundle frontend.
7. Ajustar PHP-FPM e hardening Nginx.

Esta ordem maximiza impacto rapido sem alterar infraestrutura antes de existir prova tecnica de necessidade.
