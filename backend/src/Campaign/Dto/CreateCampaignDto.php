<?php
declare(strict_types=1);

namespace App\Campaign\Dto;

use App\Validation\Assert;
use App\Validation\Limits;
use DateTimeImmutable;

final class CreateCampaignDto {
    public function __construct(
        public readonly string $name,
        public readonly int $budgetTotal,
        public readonly DateTimeImmutable $startsAt,
        public readonly DateTimeImmutable $endsAt,
    ) {}

    public static function fromArray(array $body): self {
        Assert::requiredFields($body, ['name', 'budget_total', 'starts_at', 'ends_at']);

        $name = Assert::nonEmptyString($body['name'], 'name', Limits::CAMPAIGN_NAME);
        $budgetTotal = Assert::positiveInt($body['budget_total'], 'budget_total', Limits::BUDGET_TOTAL_MAX);
        $startsAt = Assert::dateTime($body['starts_at'], 'starts_at');
        $endsAt = Assert::dateTime($body['ends_at'], 'ends_at');
        Assert::isBefore($startsAt, $endsAt, 'ends_at');

        return new self($name, $budgetTotal, $startsAt, $endsAt);
    }
}
