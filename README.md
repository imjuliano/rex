# Vendeu, Ganhou — Plataforma de Incentivo

Plataforma enxuta de incentivo de vendas. O admin cadastra produtos, campanhas com verba limitada e lança vendas/cancelamentos. O motor de pontuação credita pontos na carteira do vendedor e controla o orçamento da campanha.

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- `docker` e `docker compose`
- `make` (opcional, para os aliases)

Não é necessário instalar PHP, MySQL, Node ou Composer localmente.

## Como rodar

```bash
make up
```

Ou, sem `make`:

```bash
docker compose up --build -d
```

A primeira execução demora alguns minutos para baixar imagens e instalar dependências.

## Serviços

| Serviço | URL | Padrão |
|---|---|---|
| Frontend (React + Vite) | http://localhost:5173 | - |
| Backend API | http://localhost:8080 | - |
| Swagger UI | http://localhost:8080/docs/index.html | - |
| MySQL | `localhost:3306` | `rex` / `rex` |

## Credenciais de seed

| Email | Senha | Papel |
|---|---|---|
| `admin@rex.test` | `admin123` | `admin` |
| `seller1@rex.test` | `seller123` | `seller` |
| `seller2@rex.test` | `seller123` | `seller` |

Produtos e uma campanha ativa são inseridos automaticamente em `db/init.sql`.

## Schema e seed

O schema e o seed são aplicados pelo MySQL no primeiro boot, através de `/docker-entrypoint-initdb.d/init.sql`. Nada precisa ser rodado manualmente.

## Regra de verba estourada

**Decisão:** a venda é **rejeitada na íntegra** se `budget_used + pontos` ultrapassar `budget_total`.

A outra opção seria **creditar só o que cabe** no budget restante. Rejeitei essa abordagem porque ela cria fracionamento arbitrário: o vendedor receberia uma pontuação parcial sem que a venda correspondesse a um valor real, e o cancelamento teria que estornar uma quantia diferente da venda original.

A rejeição completa mantém a integridade do ledger (nunca há crédito sem verba correspondente), simplifica o estorno (sempre reverte a venda toda) e impede que a API devolva valores "arredondados" sem contrapartida de negócio.

O cancelamento reverte tanto os pontos da carteira quanto o `budget_used` de forma atômica, tudo dentro da mesma transação. Assim `budget_used` nunca ultrapassa `budget_total` e o ledger nunca exibe crédito sem verba.

## Decisão técnica: rotas e Swagger de uma única fonte

As rotas não ficam em um arquivo de rotas separado. Cada ação do controller carrega `#[Route]` com o método, o path e os papéis. A `Application.php` usa reflection para escanear esses atributos e montar o `Router` dinamicamente, injetando `QueryParams`, `body`, `actor` e path params de acordo com a assinatura do método.

O mesmo `#[Route]` (e os atributos do `zircote/swagger-php`, como `#[OA\Get]` e `#[OA\Post]`) vivem no controller. Isso evita duplicar definições de rota e documentação, e garante que a API real e o Swagger nunca divergem. Para uma venda com caminho `/sales/{external_id}/cancel`, por exemplo, o `SaleController` declara `string $externalId` e ambos — roteador e OpenAPI — leem o mesmo método.

## Autenticação

- `POST /auth/login` — devolve `{ token, token_type, expires_at, user }` e grava o refresh token em cookie `HttpOnly`.
- Envie `Authorization: Bearer <token>` nas demais chamadas.
- `POST /auth/refresh` e `POST /auth/logout` usam o cookie de refresh (funcionam melhor via curl/Postman do que no Swagger UI).

## Endpoints principais

| Método | Rota | Papel | Descrição |
|---|---|---|---|
| `POST` | `/auth/login` | — | Login |
| `POST` | `/auth/refresh` | — | Renova access token |
| `POST` | `/auth/logout` | — | Encerra sessão |
| `GET` | `/products` | admin/seller | Lista produtos |
| `POST` | `/products` | admin | Cria produto |
| `PUT` | `/products/{id}` | admin | Atualiza produto |
| `DELETE` | `/products/{id}` | admin | Inativa produto |
| `POST` | `/products/{id}/delete` | admin | Exclui produto |
| `GET` | `/campaigns` | admin | Lista campanhas |
| `POST` | `/campaigns` | admin | Cria campanha |
| `PUT` | `/campaigns/{id}` | admin | Atualiza campanha |
| `DELETE` | `/campaigns/{id}` | admin | Encerra campanha |
| `GET` | `/sales` | admin | Lista vendas |
| `POST` | `/sales` | admin | Lança venda |
| `POST` | `/sales/batch` | admin | Importa lote |
| `POST` | `/sales/{external_id}/cancel` | admin | Cancela venda |
| `GET` | `/sales/export` | admin | Exporta CSV |
| `GET` | `/users` | admin | Lista usuários |
| `POST` | `/users` | admin | Cria usuário |
| `PUT` | `/users/{id}` | admin | Atualiza usuário |
| `DELETE` | `/users/{id}` | admin | Exclui usuário |
| `GET` | `/me/wallet` | seller | Carteira do vendedor |
| `GET` | `/audit/{entity}` | admin | Logs de auditoria |

> `entity` em `/audit/{entity}`: `products`, `campaigns`, `sales`, `users`, `auth`.

## Exemplos com curl

### Login admin

```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{"email":"admin@rex.test","password":"admin123"}'
```

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

### Ver carteira

```bash
curl -X GET http://localhost:8080/me/wallet \
  -H "Authorization: Bearer <TOKEN_SELLER>"
```

## Coleção Postman

Também existe uma coleção completa para testar a API:

```
postman/rex.postman_collection.json
postman/rex.postman_environment.json
```

Importe os dois arquivos no Postman ou Insomnia para ter todos os endpoints organizados com variáveis de ambiente.

## Makefile

Comandos úteis:

```bash
make up       # sobe tudo
make down     # derruba
make build    # builda containers
make logs     # logs do backend
make shell    # shell no backend
make test     # roda testes PHP
make openapi  # regenera public/openapi.json
make help     # lista tudo
```

## Documentação interativa (Swagger)

1. Acesse http://localhost:8080/docs/index.html
2. Faça `POST /auth/login` com as credenciais de seed.
3. Copie `data.token`.
4. Clique em **Authorize** e informe `Bearer <token>`.

> `POST /auth/refresh` e `POST /auth/logout` dependem do cookie `HttpOnly` e podem não funcionar pelo Swagger UI. Use curl com `-c cookies.txt -b cookies.txt` ou Postman/Insomnia.

## O que faria diferente com mais tempo

- Testes automatizados (PHPUnit) nos filtros de listagem e motor de pontuação.
- Cursor-based pagination para `sales` e `wallet_entries`.
- Tela de sessões ativas para revogação por dispositivo.
- Job periódico de limpeza de `refresh_tokens` expirados.
- Extrair as queries de `Application` para repositórios dedicados.
