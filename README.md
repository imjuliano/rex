# Vendeu, Ganhou - Desafio Técnico

Plataforma enxuta de incentivo de vendas. O admin cadastra produtos, campanhas com verba limitada e lança vendas/cancelamentos. O motor de pontuação credita pontos na carteira do vendedor e controla o orçamento da campanha.

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows / macOS / Linux)
- `docker` e `docker compose` instalados

Você não precisa do PHP, MySQL, Node.js ou Composer instalados localmente; tudo roda dentro de containers.

---

## Como rodar

1. Clone ou extraia este repositório.
2. Abra o Docker Desktop.
3. No diretório raiz do projeto, execute:

```bash
docker compose up --build
```

Na primeira execução o processo demora alguns minutos porque baixa as imagens e instala as dependências.

Acesse:

| Serviço | URL | Usuário padrão |
|---|---|---|
| Frontend (React + Vite) | http://localhost:5173 | - |
| Backend (API PHP) | http://localhost:8080 | - |
| MySQL | `localhost:3306` / `db:3306` | `rex` / `rex` |

## Variáveis de ambiente

O repositório não inclui o arquivo `.env` real (ele está no `.gitignore` para não vazar secrets). Incluímos **`.env.example`** como template e colocamos **valores default no `docker-compose.yml`** (`${VAR:-default}`).

Por quê? O desafio pede que o sistema suba com o mínimo de fricção. Com isso, `docker compose up --build` funciona na primeira vez sem você criar nada. Os dados sensíveis (JWT, senhas do banco, chave de criptografia da auditoria) usam valores de desenvolvimento seguros o suficiente para testar localmente, mas devem ser trocados em produção.

Se quiser customizar:

```bash
cp .env.example .env
# edite .env com os valores desejados
docker compose up --build
```

Ambiente carrega `.env` automaticamente e sobrescreve os defaults do `docker-compose.yml`.

## Derrubar tudo

```bash
docker compose down
```

Para remover os dados do banco:

```bash
docker compose down -v
```

---

## Credenciais de seed

Usuários pré-criados no banco (`db/init.sql`):

| Email | Senha | Papel |
|---|---|---|
| `admin@rex.test` | `admin123` | `admin` |
| `seller1@rex.test` | `seller123` | `seller` |
| `seller2@rex.test` | `seller123` | `seller` |

Produtos e uma campanha ativa também são inseridos automaticamente.

---

## Schema e seed

O schema e o seed são aplicados automaticamente quando o container MySQL inicia, através do script montado em `/docker-entrypoint-initdb.d/init.sql`.

Entidades:

- `users` - id, name, email (único), password_hash, role (admin|seller)
- `products` - id, name, sku (único), points_per_unit, active
- `campaigns` - id, name, budget_total, budget_used, starts_at, ends_at, status
- `sales` - id, external_id (único), campaign_id, seller_id, product_id, quantity, unit_value, status
- `wallet_entries` - id, seller_id, campaign_id, sale_id, type (credit|debit), points

---

## Regra de verba estourada

**Decisão:** a venda é **rejeitada na íntegra** se o crédito fizer `budget_used + pontos` ultrapassar `budget_total`.

A justificativa é a consistência: é inviável garantir pontuação justa sem permitir fracionamento arbitrário de vendas. Rejeitar a venda protege a integridade da verba e evita interpretações ambíguas no estorno.

---

## Contrato de API

### Envelope de resposta

Todo sucesso tem a forma `{ data, meta? }`. Listagens acrescentam `links` de navegação:

```json
{
  "data": [ ... ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "count": 20,
    "total": 137,
    "total_pages": 7,
    "summary": { "...agregados do recurso..." }
  },
  "links": {
    "self": "/products?page=1",
    "first": "/products?page=1",
    "prev": null,
    "next": "/products?page=2",
    "last": "/products?page=7"
  }
}
```

Detalhes que valem notar:

- `meta.summary` é calculado sobre **todo** o conjunto filtrado, nunca só sobre a página visível. Filtrar a carteira não faz o saldo parecer diferente do que é.
- Escritas devolvem o recurso persistido completo, não apenas `{ id }` — o cliente nunca precisa de um segundo request para saber `created_at` ou o estado derivado.
- Datas saem em **ISO 8601** (`2026-09-04T12:30:00+00:00`).
- `active` é `boolean`; `status` é sempre o nome do enum, nunca `0`/`1`.

### Paginação, ordenação e filtros

Parâmetros comuns a todas as listagens:

| Param | Default | Regra |
|---|---|---|
| `page` | `1` | inteiro >= 1 |
| `per_page` | `20` | 1 a 100 |
| `sort` | `created_at` | apenas colunas da whitelist do recurso |
| `order` | `desc` | `asc` ou `desc` |
| `search` | — | busca parcial nos campos textuais do recurso |

Um `sort` fora da whitelist ou um enum inválido retorna **400** com a lista de valores aceitos, em vez de cair silenciosamente no default — fallback silencioso esconde integração quebrada.

| Rota | Filtros específicos | `sort` aceito |
|---|---|---|
| `GET /products` | `status=active\|inactive`, `min_points`, `max_points` | `id`, `name`, `sku`, `points_per_unit`, `created_at` |
| `GET /campaigns` | `status=active\|closed`, `running=true`, `exhausted=true\|false` | `id`, `name`, `budget_total`, `budget_used`, `starts_at`, `ends_at`, `created_at` |
| `GET /sales` | `status=approved\|canceled`, `campaign_id`, `seller_id`, `product_id`, `from`, `to` | `id`, `external_id`, `quantity`, `unit_value`, `created_at` |
| `GET /me/wallet` | `type=credit\|debit`, `campaign_id`, `sale_id`, `from`, `to` | `id`, `points`, `created_at` |

A ordenação sempre desempata por `id`, então páginas nunca se sobrepõem quando a chave de ordenação repete.

### Autenticação

```
POST /auth/login           { email, password }
POST /auth/refresh         (sem corpo; usa o cookie de refresh)
POST /auth/logout          (sem corpo; usa o cookie de refresh)
```

Os três devolvem o mesmo envelope, exceto `logout`, que responde `204`:

```json
{
  "data": {
    "token": "eyJ...",
    "token_type": "Bearer",
    "expires_at": "2026-09-05T23:48:16+00:00",
    "expires_in": 900,
    "refresh_expires_at": "2026-09-12T23:33:16+00:00",
    "user": { "id": 1, "email": "admin@rex.test", "role": "admin" }
  }
}
```

Enviar `Authorization: Bearer <token>` nas demais chamadas.

#### Access token e refresh token

São dois tokens com papéis diferentes:

| | Access token | Refresh token |
|---|---|---|
| Formato | JWT assinado (HS256) | 256 bits aleatórios (CSPRNG) |
| Validade | 15 minutos | 7 dias |
| Onde fica | corpo da resposta; memória do cliente | cookie `HttpOnly`, `Path=/auth` |
| Estado no servidor | nenhum | linha em `refresh_tokens` |
| Revogável | não, só expira | sim, imediatamente |

O access token é stateless, então não há como cancelá-lo antes do `exp` — por isso ele dura pouco. O refresh token é persistido, e é o que permite encerrar uma sessão de verdade. O resultado é que uma sessão vazada morre no máximo 15 minutos após a revogação.

Só o **hash SHA-256** do refresh token vai para o banco. Um dump não basta para assumir a sessão. Não usamos `password_hash` porque o token já tem 256 bits de entropia de CSPRNG: não é adivinhável, e um KDF lento só adicionaria latência sem ganho.

O refresh token nunca chega ao JavaScript: fica num cookie `HttpOnly`. Um XSS consegue ler o access token da memória (15 min de janela), mas não consegue emitir sessões novas. Por isso o frontend **não** guarda nada em `localStorage` — ao recarregar a página ele chama `/auth/refresh` e reconstrói a sessão a partir do cookie.

#### Rotação e detecção de reuso

Cada `/auth/refresh` gasta o token apresentado e emite um novo, ambos na mesma **família** (`family_id`, herdado do login). Um token gasto que reaparece não tem explicação legítima: é sinal de que a cadeia vazou. Nesse caso **toda a família é revogada**, inclusive o token válido que o atacante (ou a vítima) ainda tem.

Duas ressalvas que valem registrar:

1. A revogação é commitada **antes** de a exceção subir. Se o `throw` acontecesse dentro da transação, o rollback desfaria a revogação e a cadeia vazada continuaria viva.
2. Abas diferentes compartilham o mesmo cookie e podem chamar `/auth/refresh` quase ao mesmo tempo, mandando o mesmo token. A perdedora pareceria um replay e derrubaria a sessão de um usuário que não fez nada de errado. Por isso um replay que chega **até 10s** depois do original é tratado como essa corrida, não como roubo. Um token roubado que aparece depois disso ainda dispara a detecção.

#### O que encerra uma sessão

| Evento | Efeito |
|---|---|
| `POST /auth/logout` | revoga a família e limpa o cookie |
| Reuso de refresh token | revoga a família inteira |
| Troca de senha (`PUT /users/{id}`) | revoga todas as sessões do usuário |
| Exclusão do usuário (`DELETE /users/{id}`) | revoga todas as sessões do usuário |
| Refresh token expirado (7 dias) | sessão morre naturalmente |

Todos os eventos vão para `auth_audit_log`: `AUTH_LOGIN_SUCCESS`, `AUTH_LOGIN_FAILED`, `AUTH_TOKEN_REFRESHED`, `AUTH_LOGOUT` e `AUTH_REFRESH_REUSE_DETECTED`.

#### CORS

Requisições com credenciais não aceitam `Access-Control-Allow-Origin: *`. A origem é conferida contra `FRONTEND_ORIGIN` (lista separada por vírgula) e devolvida explicitamente, junto de `Access-Control-Allow-Credentials: true` e `Vary: Origin`.

Se o frontend e a API estiverem em domínios diferentes (não só portas diferentes), o cookie precisa de `SameSite=None`, que por sua vez exige HTTPS:

```bash
REFRESH_COOKIE_SAMESITE=None
REFRESH_COOKIE_SECURE=true
```

### Admin

```
GET    /products           ?status=&search=&min_points=&max_points=&sort=&order=&page=&per_page=
POST   /products           { name, sku, points_per_unit, active? }
PUT    /products/{id}      { name?, sku?, points_per_unit?, active? }
DELETE /products/{id}      soft delete: active = false

GET    /campaigns          ?status=&search=&running=&exhausted=&sort=&order=&page=&per_page=
POST   /campaigns          { name, budget_total, starts_at, ends_at }
PUT    /campaigns/{id}     { name?, budget_total?, starts_at?, ends_at?, status? }
DELETE /campaigns/{id}     encerra a campanha (status = closed)

GET    /sales              ?status=&campaign_id=&seller_id=&product_id=&search=&from=&to=&sort=&order=&page=&per_page=
POST   /sales              { external_id, campaign_id, seller_id, product_id, quantity, unit_value }
POST   /sales/batch        { sales: [ {...}, {...} ] }
POST   /sales/{external_id}/cancel
```

### Seller

```
GET /me/wallet             ?type=&campaign_id=&sale_id=&search=&from=&to=&sort=&order=&page=&per_page=
```

O `seller_id` vem sempre do token; não existe parâmetro que permita ler a carteira de outro vendedor.

### Campos derivados

Alguns campos não existem no banco e são calculados na borda, para o cliente não ter que reimplementar regra de negócio:

| Campo | Recurso | Significado |
|---|---|---|
| `budget.remaining`, `budget.usage_pct`, `budget.exhausted` | campanha | estado da verba |
| `period.days_remaining` | campanha | dias até o fim |
| `accepting_sales` | campanha | `active` **e** dentro do período **e** com verba — uma campanha pode estar `active` e ainda assim não aceitar venda |
| `total_value` | venda | `quantity * unit_value` |
| `signed_points` | extrato | `points` já com o sinal do tipo, para somar a coluna direto |

### Importação em lote

`POST /sales/batch` processa cada linha em sua própria transação e responde **207 Multi-Status** quando o lote é parcialmente aceito (200 quando tudo entrou):

```json
{
  "data": {
    "results": [
      { "row": 0, "status": "created", "external_id": "CSV-001", "sale_id": 4, "points": 100 },
      { "row": 1, "status": "skipped", "code": "SALE_ALREADY_EXISTS" },
      { "row": 2, "status": "error", "code": "PRODUCT_NOT_FOUND" }
    ]
  },
  "meta": { "submitted": 3, "created": 1, "skipped": 1, "errors": 1, "points_credited": 100 }
}
```

Uma linha ruim nunca derruba o lote inteiro, e uma linha duplicada é `skipped`, não `error`.

---

## Contrato de erros

Toda falha devolve o mesmo envelope. Clientes devem ramificar pelo campo `error` (código estável), nunca pela `message` (texto livre, sujeito a mudança).

```json
{
  "error": "INSUFFICIENT_BUDGET",
  "message": "The sale exceeds the remaining campaign budget and was rejected in full.",
  "status": 422,
  "trace_id": "a526000c5173",
  "details": {
    "campaign_id": 1,
    "requested_points": 499950,
    "available_points": 850,
    "policy": "reject_whole_sale"
  }
}
```

O `trace_id` também vai no header `X-Trace-Id` e é o que correlaciona a resposta com a linha de log do servidor. Com `APP_ENV=dev`, um bloco `debug` com classe, arquivo, linha e causa raiz é anexado; em produção mensagens de infraestrutura são substituídas por texto genérico para não vazar schema, DSN ou caminhos.

### Códigos por status

| Status | Códigos |
|---|---|
| **400** | `INVALID_JSON_BODY`, `MISSING_FIELD`, `INVALID_FIELD`, `NO_FIELDS_TO_UPDATE` |
| **401** | `MISSING_TOKEN`, `INVALID_TOKEN`, `INVALID_CREDENTIALS`, `MISSING_REFRESH_TOKEN`, `INVALID_REFRESH_TOKEN`, `REFRESH_TOKEN_REUSED` |
| **403** | `FORBIDDEN_ROLE` |
| **404** | `ROUTE_NOT_FOUND`, `SALE_NOT_FOUND`, `PRODUCT_NOT_FOUND`, `CAMPAIGN_NOT_FOUND`, `SELLER_NOT_FOUND` |
| **409** | `SALE_ALREADY_EXISTS`, `SALE_ALREADY_CANCELED`, `CAMPAIGN_ALREADY_CLOSED`, `DUPLICATE_SKU`, `DUPLICATE_ENTRY`, `CONCURRENT_UPDATE` |
| **422** | `INSUFFICIENT_BUDGET`, `CAMPAIGN_NOT_ACTIVE`, `CAMPAIGN_OUT_OF_PERIOD`, `PRODUCT_INACTIVE`, `NEGATIVE_BUDGET`, `BUDGET_BELOW_COMMITTED` |
| **500 / 503** | `DATABASE_ERROR`, `DATABASE_UNAVAILABLE`, `LEDGER_INCONSISTENT`, `INTERNAL_ERROR` |

### Idempotência

Reenviar uma venda existente devolve **409 `SALE_ALREADY_EXISTS`** com o `sale_id` original em `details`, sem creditar pontos de novo. Cancelar duas vezes devolve **409 `SALE_ALREADY_CANCELED`**, sem estornar de novo. O status 409 foi escolhido em vez de um 200 silencioso para que a tentativa duplicada seja explícita nos logs e no cliente.

### Ciclo de vida da campanha

`DELETE /campaigns/{id}` **não apaga nada**: apenas move o status para `closed`, o que impede novas vendas. `budget_used` e o ledger ficam intactos, então o histórico continua auditável e o saldo dos vendedores não muda. Reabrir é um `PUT` com `{"status":"active"}`.

Editar a verba tem uma trava de invariante: `budget_total` não pode ficar **abaixo** de `budget_used`, senão a campanha nasceria com verba estourada e o motor de pontuação perderia a garantia de que `budget_used <= budget_total`. A tentativa devolve **422 `BUDGET_BELOW_COMMITTED`** informando o mínimo permitido:

```json
{
  "error": "BUDGET_BELOW_COMMITTED",
  "status": 422,
  "details": { "campaign_id": 2, "requested_budget": 10, "already_committed": 150, "minimum_allowed": 150 }
}
```

O update inteiro roda sob `SELECT ... FOR UPDATE` na campanha: uma venda concorrente não pode se infiltrar entre a checagem do `budget_used` e a escrita do novo `budget_total`.

### Arquitetura

- `src/Exception/ErrorCode.php` — enum com todos os códigos canônicos.
- `src/Exception/AbstractDomainException.php` — base que carrega status, código, `details` e as políticas `shouldLog()` / `isMessagePublic()`.
- Subclasses por categoria: `ValidationException`, `AuthenticationException`, `AuthorizationException`, `NotFoundException`, `ConflictException`, `BusinessException`, `ConcurrencyException`, `InfrastructureException`.
- `src/Exception/PdoExceptionTranslator.php` — traduz SQLSTATE (duplicidade, deadlock, lock timeout, FK) em exceptions de domínio.
- `src/Exception/ExceptionHandler.php` — ponto único de saída: normaliza, loga 5xx com `trace_id` e serializa o envelope.
- `src/Validation/Assert.php` — guard clauses que lançam `ValidationException` nomeando o campo ofensor.

Handlers não tratam erro: eles lançam. `Application::run()` tem o único `try/catch`, e `Application::transactional()` garante rollback antes de repropagar, de modo que uma falha no meio nunca deixa verba debitada sem ponto creditado.

### Camada HTTP e domínio

- `src/Domain/` — enums `ProductStatus`, `CampaignStatus`, `SaleStatus`, `WalletEntryType`, com comportamento junto do valor (`acceptsSales()`, `sign()`, `toFlag()`).
- `src/Http/QueryParams.php` — acesso tipado e validado à query string; whitelist de `sort`, faixa de `per_page`, coerção de enums.
- `src/Http/Criteria.php` — acumula `WHERE`, bindings e paginação. Fragmentos sempre parametrizados; só `LIMIT/OFFSET` é inline, e só depois de provado inteiro.
- `src/Http/PaginatedCollection.php` — a página e seus totais.
- `src/Http/ApiResponse.php` — o único lugar que decide como é uma resposta de sucesso.
- `src/Filter/` — um filtro por recurso, traduzindo query params em `Criteria`.
- `Application::paginate()` — roda a query filtrada duas vezes (página + count) reusando o mesmo `WHERE` e bindings, então `meta` nunca divergem das linhas retornadas.
- `Application::map*()` — os row mappers são o contrato de tipos da API: o banco fala `TINYINT` e `DECIMAL` string, a API fala `boolean`, `int`, `float` e nome de enum.

---

## Exemplos com curl

### Login admin

O refresh token vem num cookie `HttpOnly`, então guarde os cookies num arquivo para poder renovar depois:

```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"email":"admin@rex.test","password":"admin123"}'
```

### Renovar a sessão

```bash
# -b lê o cookie atual, -c grava o novo (cada refresh rotaciona o token)
curl -X POST http://localhost:8080/auth/refresh -b cookies.txt -c cookies.txt
```

### Encerrar a sessão

```bash
curl -X POST http://localhost:8080/auth/logout -b cookies.txt -c cookies.txt
```

Repetir o `refresh` com um cookie já gasto devolve **401 `REFRESH_TOKEN_REUSED`** e revoga a família inteira.

### Criar produto

```bash
curl -X POST http://localhost:8080/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"name":"Mouse Gamer","sku":"MOUSE-001","points_per_unit":15}'
```

### Lançar venda

```bash
curl -X POST http://localhost:8080/sales \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"external_id":"VENDA-1","campaign_id":1,"seller_id":2,"product_id":1,"quantity":2,"unit_value":199.90}'
```

### Cancelar venda

```bash
curl -X POST http://localhost:8080/sales/VENDA-1/cancel \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

### Ver carteira do seller

```bash
curl -X GET http://localhost:8080/me/wallet \
  -H "Authorization: Bearer <TOKEN_SELLER>"
```

### Listagens com filtro e paginação

```bash
# produtos ativos, buscando por SKU, ordenados por nome
curl "http://localhost:8080/products?status=active&search=PHONE&sort=name&order=asc&per_page=10" \
  -H "Authorization: Bearer <TOKEN>"

# campanhas que ainda aceitam venda
curl "http://localhost:8080/campaigns?running=true&exhausted=false" \
  -H "Authorization: Bearer <TOKEN>"

# vendas canceladas de um vendedor num intervalo
curl "http://localhost:8080/sales?status=canceled&seller_id=2&from=2026-01-01&to=2026-12-31" \
  -H "Authorization: Bearer <TOKEN>"

# somente os estornos da carteira
curl "http://localhost:8080/me/wallet?type=debit&per_page=5" \
  -H "Authorization: Bearer <TOKEN_SELLER>"
```

### Importar lote

```bash
curl -X POST http://localhost:8080/sales/batch \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"sales":[
        {"external_id":"CSV-001","campaign_id":1,"seller_id":2,"product_id":1,"quantity":2,"unit_value":1999.90},
        {"external_id":"CSV-002","campaign_id":1,"seller_id":3,"product_id":2,"quantity":5,"unit_value":899.50}
      ]}'
```

Um CSV de exemplo fica servido em `http://localhost:8080/sales-sample.csv` e pode ser carregado direto pela tela de vendas.

---

## Decisões técnicas

- PHP 8.2 puro, sem framework full-stack. Roteador, autenticação e persistência implementados manualmente.
- Banco MySQL 8 acessado via PDO + prepared statements.
- JWT via `firebase/php-jwt`; senhas com `password_hash` / `password_verify`.
- `JWT_SECRET` é obrigatório: sem a variável de ambiente a aplicação não sobe, em vez de cair num segredo default.
- Access token de 15 min + refresh token rotativo de 7 dias em cookie `HttpOnly`, com detecção de reuso. O access token é stateless e não dá para revogar; o refresh token é persistido (só o hash) e dá.
- Transações e `SELECT ... FOR UPDATE` no motor de pontuação para garantir atomicidade e evitar concorrência na verba.
- Hierarquia de exceptions de domínio com um único ponto de saída, `trace_id` correlacionando resposta e log.
- Envelope `data/meta/links` com paginação, filtros e ordenação validados por whitelist.
- React com Vite no frontend; cada serviço em seu container.

---

## O que faria diferente com mais tempo

- Testes automatizados (PHPUnit) para os filtros de listagem; hoje há cobertura do motor de pontuação e do fluxo de autenticação.
- Cursor-based pagination nas listagens que tendem a crescer muito (`sales`, `wallet_entries`); offset degrada em tabelas grandes.
- Índices compostos guiados por `EXPLAIN` para os filtros mais usados (`sales(campaign_id, status, created_at)`).
- Tela de sessões ativas, para o usuário revogar um dispositivo específico em vez de todos de uma vez.
- Job de limpeza dos `refresh_tokens` expirados. Hoje a poda acontece por usuário no login, o que basta, mas deixa lixo de contas inativas.
- Lock entre abas (`BroadcastChannel`) para serializar o refresh, o que permitiria remover a janela de tolerância de 10s.
- Extrair as queries de `Application` para repositórios, que é o próximo passo natural agora que filtro e paginação já estão isolados.
- Makefile e scripts auxiliares para seed/rollback.
