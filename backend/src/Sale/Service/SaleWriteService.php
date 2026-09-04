<?php
declare(strict_types=1);

namespace App\Sale\Service;

use App\Audit\AuditEvent;
use App\Audit\AuditLogDispatcher;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Campaign\Exception\CampaignNotActiveException;
use App\Campaign\Exception\CampaignOutOfPeriodException;
use App\Campaign\Service\CampaignWriteService;
use App\Exception\AbstractDomainException;
use App\Exception\ConflictException;
use App\Exception\ErrorCode;
use App\Product\Dao\ProductDao;
use App\Product\Exception\ProductInactiveException;
use App\Sale\Dao\SaleDao;
use App\Sale\Dto\BatchSalesDto;
use App\Sale\Dto\CreateSaleDto;
use App\Sale\Exception\SaleAlreadyCanceledException;
use App\Sale\Exception\SaleAlreadyExistsException;
use App\Sale\Exception\SaleLedgerInconsistentException;
use App\Sale\Exception\SaleNegativeBudgetException;
use App\Sale\Exception\SaleNotFoundException;
use App\Sale\Mapper\SaleMapper;
use App\Sale\SaleStatus;
use App\Sale\ScoringEngine;
use App\TransactionRunner;
use App\User\Dao\UserDao;
use App\User\Exception\SellerNotFoundException;
use App\Wallet\Dao\WalletDao;
use App\Wallet\WalletEntryType;
use DateTimeImmutable;
use PDOException;
use Throwable;

final class SaleWriteService {
    public function __construct(
        private TransactionRunner $transactions,
        private SaleDao $saleDao,
        private CampaignWriteService $campaignService,
        private ProductDao $productDao,
        private UserDao $userDao,
        private WalletDao $walletDao,
        private ScoringEngine $scoring,
        private SaleMapper $mapper,
        private AuditLogDispatcher $audit,
    ) {}

    public function create(CreateSaleDto $dto, array $actor): array {
        $result = $this->transactions->run(fn() => $this->persist($dto));

        $this->audit->dispatch($this->buildSaleCreatedAuditEvent($result, $actor));

        return $result;
    }

    private function buildSaleCreatedAuditEvent(array $result, array $actor): AuditEvent {
        return new AuditEvent(
            action: LogAction::SALE_CREATED,
            entity: LogEntity::SALE,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: $result['external_id'],
            payload: [
                'campaign_id' => $result['campaign']['id'],
                'seller_id' => $result['seller']['id'],
                'product_id' => $result['product']['id'],
                'quantity' => $result['quantity'],
                'points' => $result['points'],
            ],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        );
    }

    public function batch(BatchSalesDto $dto, array $actor): array {
        $counters = ['created' => 0, 'skipped' => 0, 'errors' => 0];
        $results = [];
        $pointsCredited = 0;

        foreach ($dto->sales as $idx => $saleDto) {
            $processed = $this->processBatchItem($saleDto, $idx);
            $counters[$processed['status']]++;
            $pointsCredited += $processed['points'];
            $results[] = $processed['result'];
        }

        $correlationId = bin2hex(random_bytes(8));
        $this->audit->dispatch($this->buildBatchAuditEvent($dto, $counters, $pointsCredited, $correlationId, $actor));

        return [
            'results' => $results,
            'submitted' => count($dto->sales),
            'created' => $counters['created'],
            'skipped' => $counters['skipped'],
            'errors' => $counters['errors'],
            'points_credited' => $pointsCredited,
        ];
    }

    private function processBatchItem(CreateSaleDto $saleDto, int $idx): array {
        try {
            $sale = $this->transactions->run(fn() => $this->persist($saleDto));

            return [
                'status' => 'created',
                'points' => $sale['points'],
                'result' => $this->buildBatchResultItem($idx, $sale),
            ];
        } catch (ConflictException $e) {
            return [
                'status' => 'skipped',
                'points' => 0,
                'result' => $this->buildBatchErrorItem($idx, $e, 'skipped'),
            ];
        } catch (AbstractDomainException $e) {
            return [
                'status' => 'errors',
                'points' => 0,
                'result' => $this->buildBatchErrorItem($idx, $e, 'error'),
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'errors',
                'points' => 0,
                'result' => $this->buildBatchErrorItem($idx, $e, 'error'),
            ];
        }
    }

    private function buildBatchResultItem(int $idx, array $sale): array {
        return [
            'row' => $idx,
            'status' => 'created',
            'external_id' => $sale['external_id'],
            'sale_id' => $sale['id'],
            'points' => $sale['points'],
        ];
    }

    private function buildBatchErrorItem(int $idx, Throwable $e, string $status): array {
        if ($e instanceof PDOException) {
            return [
                'row' => $idx,
                'status' => $status,
                'code' => ErrorCode::DATABASE_ERROR->value,
                'message' => 'Database rejected this row.',
            ];
        }

        return [
            'row' => $idx,
            'status' => $status,
            'code' => $e->errorCode()->value,
            'message' => $e->getMessage(),
            'details' => $e->details(),
        ];
    }

    private function buildBatchAuditEvent(BatchSalesDto $dto, array $counters, int $pointsCredited, string $correlationId, array $actor): AuditEvent {
        return new AuditEvent(
            action: LogAction::SALE_BATCH_CREATED,
            entity: LogEntity::SALE,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: $correlationId,
            payload: [
                'submitted' => count($dto->sales),
                'created' => $counters['created'],
                'skipped' => $counters['skipped'],
                'errors' => $counters['errors'],
                'points_credited' => $pointsCredited,
            ],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: $correlationId,
        );
    }

    private function persist(CreateSaleDto $dto): array {
        $this->assertSaleDoesNotExist($dto->externalId);

        $campaign = $this->validateCampaignEligibility($dto->campaignId);
        $product = $this->validateProductEligibility($dto->productId);
        $seller = $this->validateSeller($dto->sellerId);

        ['points' => $points, 'available' => $available] = $this->calculateSalePoints(
            $dto->quantity,
            (int) $product['points_per_unit'],
            $dto->campaignId,
            (int) $campaign['budget_total'],
            (int) $campaign['budget_used']
        );

        $saleId = $this->persistNewSale($dto);
        $this->creditSaleToWallet($dto->sellerId, $dto->campaignId, $saleId, $points, $dto->externalId);
        $this->campaignService->incrementBudgetUsed($dto->campaignId, $points);

        return $this->mapper->mapCreated(
            saleId: $saleId,
            externalId: $dto->externalId,
            status: SaleStatus::APPROVED->value,
            quantity: $dto->quantity,
            unitValue: $dto->unitValue,
            points: $points,
            campaign: [
                'id' => $dto->campaignId,
                'name' => $campaign['name'],
                'budget_remaining' => $available - $points,
            ],
            sellerId: $dto->sellerId,
            productId: $dto->productId,
            pointsPerUnit: (int) $product['points_per_unit'],
        );
    }

    private function assertSaleDoesNotExist(string $externalId): void {
        $existing = $this->saleDao->findByExternalIdForUpdate($externalId);
        if ($existing !== null) {
            throw new SaleAlreadyExistsException(
                $externalId,
                (int) $existing['id'],
                $existing['status']
            );
        }
    }

    private function validateCampaignEligibility(int $campaignId): array {
        $campaign = $this->campaignService->lockById($campaignId);
        if ($campaign['status'] !== 'active') {
            throw new CampaignNotActiveException($campaignId);
        }

        $now = new DateTimeImmutable();
        if ($now < new DateTimeImmutable($campaign['starts_at']) ||
            $now > new DateTimeImmutable($campaign['ends_at'])) {
            throw new CampaignOutOfPeriodException(
                $campaignId,
                $campaign['starts_at'],
                $campaign['ends_at']
            );
        }

        return $campaign;
    }

    private function validateProductEligibility(int $productId): array {
        $product = $this->productDao->find($productId);
        if (!$product['active']) {
            throw new ProductInactiveException($productId);
        }

        return $product;
    }

    private function validateSeller(int $sellerId): array {
        $seller = $this->userDao->findById($sellerId);
        if ($seller['role'] !== 'seller') {
            throw new SellerNotFoundException($sellerId);
        }

        return $seller;
    }

    private function calculateSalePoints(int $quantity, int $pointsPerUnit, int $campaignId, int $budgetTotal, int $budgetUsed): array {
        $points = $this->scoring->pointsFor($quantity, $pointsPerUnit);
        $available = $this->scoring->budgetAvailable($budgetTotal, $budgetUsed);
        $this->scoring->assertFitsBudget($points, $campaignId, $budgetTotal, $budgetUsed);

        return ['points' => $points, 'available' => $available];
    }

    private function persistNewSale(CreateSaleDto $dto): int {
        return $this->saleDao->create(
            $dto->externalId,
            $dto->campaignId,
            $dto->sellerId,
            $dto->productId,
            $dto->quantity,
            $dto->unitValue,
            SaleStatus::APPROVED->value
        );
    }

    private function creditSaleToWallet(int $sellerId, int $campaignId, int $saleId, int $points, string $externalId): void {
        $this->walletDao->insert(
            $sellerId,
            $campaignId,
            $saleId,
            WalletEntryType::CREDIT,
            $points,
            "Sale {$externalId}"
        );
    }

    public function cancel(string $externalId, array $actor): array {
        $result = $this->transactions->run(function () use ($externalId): array {
            $sale = $this->saleDao->findByExternalIdForUpdate($externalId);
            $this->assertSaleCanBeCanceled($sale, $externalId);

            $saleId = (int) $sale['id'];
            $campaignId = (int) $sale['campaign_id'];

            $points = $this->walletDao->findCreditBySaleId($saleId);
            if ($points === null) {
                throw new SaleLedgerInconsistentException($saleId);
            }

            $campaign = $this->campaignService->lockById($campaignId);
            $budgetUsed = (int) $campaign['budget_used'];
            $this->assertCampaignBudgetAllowsCancel($campaignId, $points, $budgetUsed);

            $this->performCancelLedger($sale, $campaignId, $points, $externalId);

            return $this->buildCanceledResponse($sale, $campaign, $points, $externalId, $budgetUsed);
        });

        $this->audit->dispatch($this->buildSaleCanceledAuditEvent($result, $actor));

        return $result;
    }

    private function assertSaleCanBeCanceled(?array $sale, string $externalId): void {
        if ($sale === null) {
            throw new SaleNotFoundException($externalId);
        }
        if ($sale['status'] === SaleStatus::CANCELED->value) {
            throw new SaleAlreadyCanceledException($externalId, (int) $sale['id']);
        }
    }

    private function assertCampaignBudgetAllowsCancel(int $campaignId, int $points, int $budgetUsed): void {
        if ($budgetUsed - $points < 0) {
            throw new SaleNegativeBudgetException($campaignId, $points, $budgetUsed);
        }
    }

    private function performCancelLedger(array $sale, int $campaignId, int $points, string $externalId): void {
        $saleId = (int) $sale['id'];

        $this->walletDao->insert(
            (int) $sale['seller_id'],
            $campaignId,
            $saleId,
            WalletEntryType::DEBIT,
            $points,
            "Cancellation of sale $externalId",
        );

        $this->saleDao->cancel($saleId);
        $this->campaignService->decrementBudgetUsed($campaignId, $points);
    }

    private function buildCanceledResponse(array $sale, array $campaign, int $points, string $externalId, int $budgetUsed): array {
        $saleId = (int) $sale['id'];
        $campaignId = (int) $sale['campaign_id'];

        return $this->mapper->mapCanceled(
            saleId: $saleId,
            externalId: $externalId,
            status: SaleStatus::CANCELED->value,
            pointsReversed: $points,
            campaignId: $campaignId,
            campaignName: $campaign['name'],
            budgetRemaining: (int) $campaign['budget_total'] - ($budgetUsed - $points),
            sellerId: (int) $sale['seller_id'],
        );
    }

    private function buildSaleCanceledAuditEvent(array $result, array $actor): AuditEvent {
        return new AuditEvent(
            action: LogAction::SALE_CANCELED,
            entity: LogEntity::SALE,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: $result['external_id'],
            payload: [
                'campaign_id' => $result['campaign']['id'],
                'seller_id' => $result['seller']['id'],
                'points_reversed' => $result['points_reversed'],
            ],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        );
    }
}
