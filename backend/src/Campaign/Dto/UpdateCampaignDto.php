<?php
declare(strict_types=1);

namespace App\Campaign\Dto;

use App\Campaign\CampaignStatus;
use App\Campaign\Exception\CampaignInvalidStatusException;
use App\Campaign\Exception\CampaignNoFieldsToUpdateException;
use App\Validation\Assert;
use App\Validation\Limits;
use DateTimeImmutable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'UpdateCampaignRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'budget_total', type: 'integer', minimum: 0),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'closed']),
    ],
)]
final class UpdateCampaignDto {
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $budgetTotal = null,
        public readonly ?DateTimeImmutable $startsAt = null,
        public readonly ?DateTimeImmutable $endsAt = null,
        public readonly ?CampaignStatus $status = null,
    ) {}

    public static function fromArray(array $body): self {
        $name = array_key_exists('name', $body) ? Assert::nonEmptyString($body['name'], 'name', Limits::CAMPAIGN_NAME) : null;
        $budgetTotal = array_key_exists('budget_total', $body) ? Assert::nonNegativeInt($body['budget_total'], 'budget_total', Limits::BUDGET_TOTAL_MAX) : null;
        $startsAt = array_key_exists('starts_at', $body) ? Assert::dateTime($body['starts_at'], 'starts_at') : null;
        $endsAt = array_key_exists('ends_at', $body) ? Assert::dateTime($body['ends_at'], 'ends_at') : null;

        $status = null;
        if (array_key_exists('status', $body) && $body['status'] !== null) {
            $status = CampaignStatus::tryFrom(is_string($body['status']) ? $body['status'] : '');
            if ($status === null) {
                throw new CampaignInvalidStatusException();
            }
        }

        $filled = array_filter([$name, $budgetTotal, $startsAt, $endsAt, $status], fn($v) => $v !== null);
        if ($filled === [] && !array_key_exists('name', $body) && !array_key_exists('budget_total', $body) && !array_key_exists('starts_at', $body) && !array_key_exists('ends_at', $body) && !array_key_exists('status', $body)) {
            throw new CampaignNoFieldsToUpdateException();
        }

        if ($startsAt !== null && $endsAt !== null) {
            Assert::isBefore($startsAt, $endsAt, 'ends_at');
        }

        return new self($name, $budgetTotal, $startsAt, $endsAt, $status);
    }
}
