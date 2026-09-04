<?php
declare(strict_types=1);

namespace App;

use App\Application\Exception\JwtSecretMissingException;
use App\Audit\AuditEncryptor;
use App\Audit\AuditLogDispatcher;
use App\Audit\Controller\AuditController;
use App\Audit\Mapper\AuditLogMapper;
use App\Audit\MySqlAuditLogRepository;
use App\Auth\Controller\AuthController;
use App\Auth\Dao\RefreshTokenDao;
use App\Auth\Service\AuthService;
use App\Campaign\Controller\CampaignController;
use App\User\Controller\UserController;
use App\User\Dao\UserDao;
use App\User\Mapper\UserMapper;
use App\User\Service\UserReadService;
use App\User\Service\UserWriteService;
use App\Wallet\Controller\WalletController;
use App\Wallet\Dao\WalletDao;
use App\Wallet\Mapper\WalletMapper;
use App\Wallet\Service\WalletReadService;
use App\Campaign\Dao\CampaignDao;
use App\Campaign\Mapper\CampaignMapper;
use App\Campaign\Service\CampaignReadService;
use App\Campaign\Service\CampaignWriteService;
use App\Sale\ScoringEngine;
use App\Product\Controller\ProductController;
use App\Product\Dao\ProductDao;
use App\Product\Mapper\ProductMapper;
use App\Product\Service\ProductReadService;
use App\Product\Service\ProductWriteService;
use App\Sale\Controller\SaleController;
use App\Sale\Dao\SaleDao;
use App\Sale\Mapper\SaleMapper;
use App\Sale\Service\SaleReadService;
use App\Sale\Service\SaleWriteService;
use App\Exception\ExceptionHandler;
use App\Exception\ValidationException;
use App\Http\QueryParams;
use PDO;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class Application {
    private PDO $pdo;
    private PDO $logsPdo;
    private AuthService $auth;
    private Router $router;
    private ExceptionHandler $errors;
    private QueryParams $query;
    private MySqlAuditLogRepository $auditRepo;
    private AuditLogDispatcher $audit;
    private ScoringEngine $scoring;
    private ProductController $productController;
    private CampaignWriteService $campaignWriteService;
    private CampaignController $campaignController;
    private AuthController $authController;
    private UserController $userController;
    private WalletController $walletController;
    private SaleController $saleController;
    private AuditController $auditController;
    private string $method;
    private string $path;

    public function __construct() {
        $this->errors = ExceptionHandler::fromEnv();
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = QueryParams::fromGlobals();
        $this->scoring = new ScoringEngine();
    }

    public function run(): void {
        $this->sendCorsHeaders();

        if ($this->method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        try {
            $this->boot();
            $this->dispatch();
        } catch (Throwable $e) {
            $this->errors->handle($e, ['method' => $this->method, 'path' => $this->path]);
        }
    }

    private function boot(): void {
        $secret = getenv('JWT_SECRET');
        if ($secret === false || trim($secret) === '') {
            throw new JwtSecretMissingException();
        }

        $this->pdo = Database::connect();
        $this->logsPdo = Database::logsConnect();
        $this->auditRepo = new MySqlAuditLogRepository(
            $this->logsPdo,
            new AuditEncryptor(getenv('AUDIT_LOG_ENCRYPTION_KEY') ?: '')
        );
        $this->audit = new AuditLogDispatcher($this->auditRepo);
        $this->auditController = new AuditController($this->auditRepo, new AuditLogMapper($this->auditRepo));
        $transactions = new TransactionRunner($this->pdo);
        $userDao = new UserDao($this->pdo);
        $refreshTokenDao = new RefreshTokenDao($this->pdo);
        $this->auth = new AuthService(
            $secret,
            $userDao,
            $refreshTokenDao,
            $transactions,
            $this->audit
        );
        $productDao = new ProductDao($this->pdo);
        $productMapper = new ProductMapper();
        $this->productController = new ProductController(
            new ProductReadService($productDao, $productMapper),
            new ProductWriteService($productDao, $productMapper, $this->audit)
        );
        $campaignDao = new CampaignDao($this->pdo);
        $campaignMapper = new CampaignMapper();
        $this->campaignWriteService = new CampaignWriteService($campaignDao, $campaignMapper, $this->audit);
        $this->campaignController = new CampaignController(
            new CampaignReadService($campaignDao, $campaignMapper),
            $this->campaignWriteService
        );
        $this->authController = new AuthController($this->auth);
        $userMapper = new UserMapper();
        $this->userController = new UserController(
            new UserReadService($userDao, $userMapper),
            new UserWriteService($userDao, $userMapper, $this->audit, $refreshTokenDao)
        );
        $this->walletController = new WalletController(
            new WalletReadService(
                new WalletDao($this->pdo),
                new WalletMapper()
            )
        );
        $saleDao = new SaleDao($this->pdo);
        $saleMapper = new SaleMapper();
        $this->saleController = new SaleController(
            new SaleReadService($saleDao, $saleMapper),
            new SaleWriteService(
                $transactions,
                $saleDao,
                $this->campaignWriteService,
                new ProductDao($this->pdo),
                new UserDao($this->pdo),
                new WalletDao($this->pdo),
                $this->scoring,
                $saleMapper,
                $this->audit
            )
        );
        $this->router = new Router();
        $this->registerRoutes();
    }

    private function dispatch(): void {
        $route = $this->router->match($this->method, $this->path);

        if ($route['roles'] !== null) {
            $this->auth->requireRole($route['roles']);
        }

        ($route['handler'])($route['params']);
    }

    /**
     * The refresh cookie makes every browser call a credentialed request, and
     * those are forbidden from using a wildcard origin. So the origin is
     * matched against an allow-list and echoed back instead of using '*'.
     */
    private function sendCorsHeaders(): void {
        header('Content-Type: application/json; charset=utf-8');

        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '' && in_array($origin, $this->allowedOrigins(), true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            // Without this, a shared cache could serve one origin's headers to another.
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Expose-Headers: X-Trace-Id');
    }

    /** @return list<string> */
    private function allowedOrigins(): array {
        $raw = trim((string) getenv('FRONTEND_ORIGIN'));
        if ($raw === '') {
            $raw = 'http://localhost:5173';
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn(string $o): bool => $o !== ''));
    }

    private function registerRoutes(): void {
        $this->registerController($this->authController);
        $this->registerController($this->productController);
        $this->registerController($this->campaignController);
        $this->registerController($this->saleController);
        $this->registerController($this->userController);
        $this->registerController($this->auditController);
        $this->registerController($this->walletController);
    }

    private function registerController(object $controller): void {
        $ref = new ReflectionClass($controller);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Route::class) as $attr) {
                $route = $attr->newInstance();
                $this->router->add(
                    $route->method,
                    $route->path,
                    fn(array $p) => $this->invokeAction([$controller, $method->getName()], $p),
                    $route->roles,
                );
            }
        }
    }

    private function invokeAction(callable $action, array $params): void {
        $ref = new ReflectionMethod($action[0], $action[1]);
        $args = [];
        foreach ($ref->getParameters() as $param) {
            $type = $param->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
            $name = $param->getName();

            if ($typeName === QueryParams::class) {
                $args[] = $this->query;
                continue;
            }

            if ($typeName === 'array' && $name === 'body') {
                $args[] = $this->jsonBody();
                continue;
            }

            if ($typeName === 'array' && $name === 'actor') {
                $args[] = $this->currentActor();
                continue;
            }

            if ($typeName !== null && enum_exists($typeName) && is_a($typeName, \BackedEnum::class, true)) {
                $enumRef = new \ReflectionEnum($typeName);
                $backing = $enumRef->getBackingType()?->getName();
                $raw = $backing === 'int' ? (int) $value : (string) $value;
                $args[] = $typeName::from($raw);
                continue;
            }

            $pathParamAttr = $param->getAttributes(PathParam::class)[0] ?? null;
            $paramName = $pathParamAttr?->newInstance()->name ?? $this->camelToSnake($name);

            $value = $params[$paramName] ?? null;
            if ($typeName === 'int') {
                $args[] = (int) $value;
            } elseif ($typeName === 'string') {
                $args[] = (string) $value;
            } else {
                $args[] = $value;
            }
        }
        ($action)(...$args);
    }

    private function camelToSnake(string $value): string {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    // ------------------------------------------------------------- plumbing

    /** @return array<string, mixed> */
    private function jsonBody(): array {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            throw ValidationException::invalidJsonBody();
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::invalidJsonBody();
        }
        return $data;
    }

    /** @return array<string, mixed> */
    private function currentActor(): array {
        try {
            $user = $this->auth->requireUser();
            return [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
        } catch (\Throwable $e) {
            return ['id' => null, 'email' => null, 'role' => null];
        }
    }
}
