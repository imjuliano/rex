# Commit Plan

- All messages in English and lowercase after the prefix.
- Prefixes: `feat:`, `fix:`, `refactor:`, `style:`.
- Keep related files together; do not mix unrelated domains in the same commit.
- Commit in the order listed below when possible.

## 1. feat: add project docker and environment setup

```
.env.example
.gitignore
Makefile
docker-compose.yml
backend/000-default.conf
backend/Dockerfile
backend/public/.htaccess
backend/public/index.php
frontend/Dockerfile
frontend/index.html
frontend/vite.config.js
frontend/.dockerignore
```

## 2. fix: prevent local node_modules from entering docker build context

```
frontend/.dockerignore
```

## 3. feat: add database schema and seed data

```
db/init.sql
db/logs-init.sql
```

## 4. feat: add backend core infrastructure

```
backend/src/Application.php
backend/src/Router.php
backend/src/Database.php
backend/src/TransactionRunner.php
backend/src/Http/ApiResponse.php
backend/src/Http/Criteria.php
backend/src/Http/PaginatedCollection.php
backend/src/Http/QueryParams.php
backend/src/Http/Exception/InvalidQuerySortException.php
backend/src/Http/Exception/RouteNotFoundException.php
backend/src/Exception/*.php
backend/src/Validation/Assert.php
backend/src/Validation/Limits.php
```

## 5. feat: add backend auth and refresh token domain

```
backend/src/Auth/**/*.php
backend/src/Audit/LogAction.php
```

## 6. feat: add backend product domain

```
backend/src/Product/**/*.php
```

## 7. feat: add backend campaign domain

```
backend/src/Campaign/**/*.php
```

## 8. feat: add backend sale domain

```
backend/src/Sale/**/*.php
```

## 9. feat: add backend user domain

```
backend/src/User/**/*.php
```

## 10. feat: add backend wallet domain

```
backend/src/Wallet/**/*.php
```

## 11. feat: add backend audit logging domain

```
backend/src/Audit/*.php
backend/src/Audit/Controller/AuditController.php
backend/src/Audit/Filter/AuditLogFilter.php
backend/src/Audit/Mapper/AuditLogMapper.php
backend/src/Audit/MySqlAuditLogRepository.php
backend/src/Audit/Exception/*.php
```

## 12. feat: add backend unit tests and test support

```
backend/phpunit.xml
backend/tests/**/*.php
```

## 13. feat: add frontend entry point and global styles

```
frontend/src/main.jsx
frontend/src/styles/global.css
frontend/src/App.jsx
frontend/src/components/Brand.jsx
frontend/src/components/Shell.jsx
```

## 14. feat: add frontend auth context and error helpers

```
frontend/src/context/AuthContext.jsx
frontend/src/lib/errors.js
frontend/src/lib/limits.js
frontend/src/hooks/useToasts.js
frontend/src/hooks/useDebounced.js
```

## 15. feat: add frontend ui components and icon set

```
frontend/src/components/ui.jsx
frontend/src/components/icons.jsx
```

## 16. feat: add frontend pages

```
frontend/src/pages/Login.jsx
frontend/src/pages/Products.jsx
frontend/src/pages/Campaigns.jsx
frontend/src/pages/Sales.jsx
frontend/src/pages/Users.jsx
frontend/src/pages/Wallet.jsx
frontend/src/pages/Auditoria.jsx
```

## 17. feat: add postman collection and environment

```
postman/rex.postman_collection.json
postman/rex.postman_environment.json
```

## 18. refactor: extract api client into domain services

```
frontend/src/lib/api.js
frontend/src/services/api-client.js
frontend/src/services/auth-api.js
frontend/src/services/products-api.js
frontend/src/services/campaigns-api.js
frontend/src/services/sales-api.js
frontend/src/services/users-api.js
frontend/src/services/wallet-api.js
frontend/src/services/audit-api.js
frontend/src/services/index.js
```

## 19. refactor: extract product list logic into useproducts hook

```
frontend/src/hooks/useProducts.js
frontend/src/pages/Products.jsx
```

## 20. feat: add eslint, prettier and lint scripts

```
frontend/.eslintrc.cjs
frontend/.prettierrc
frontend/package.json
frontend/package-lock.json
frontend/jsconfig.json
```

## 21. feat: configure vite path aliases

```
frontend/vite.config.js
frontend/jsconfig.json
```

## 22. style: apply prettier formatting to frontend source

```
frontend/src/App.jsx
frontend/src/main.jsx
frontend/src/lib/api.js
frontend/src/lib/errors.js
frontend/src/components/ui.jsx
frontend/src/components/icons.jsx
frontend/src/context/AuthContext.jsx
frontend/src/pages/*.jsx
frontend/src/styles/global.css
```

## Notes

- `frontend/package-lock.json` should be committed only if you want a reproducible lockfile. If not, add `frontend/package-lock.json` to `.gitignore`.
- `frontend/node_modules` and `frontend/dist` are already ignored and should not be committed.
- The `style:` commit should be the last one so it only contains formatting, making reviews easier.
