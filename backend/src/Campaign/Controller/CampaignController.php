<?php
declare(strict_types=1);

namespace App\Campaign\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Campaign\Dto\CreateCampaignDto;
use App\Campaign\Dto\UpdateCampaignDto;
use App\Campaign\Filter\CampaignFilter;
use App\Campaign\Service\CampaignReadService;
use App\Campaign\Service\CampaignWriteService;

final class CampaignController {
    public function __construct(
        private CampaignReadService $reads,
        private CampaignWriteService $writes,
    ) {}

    public function list(QueryParams $query): void {
        $criteria = CampaignFilter::from($query);
        $page = $this->reads->list($criteria);
        $totals = $this->reads->totals();
        ApiResponse::collection($page, '/campaigns', $query->all(), $totals);
    }

    public function create(array $body, array $actor): void {
        $campaign = $this->writes->create(CreateCampaignDto::fromArray($body), $actor);
        ApiResponse::item($campaign, 201);
    }

    public function update(int $id, array $body, array $actor): void {
        $campaign = $this->writes->update($id, UpdateCampaignDto::fromArray($body), $actor);
        ApiResponse::item($campaign);
    }

    public function close(int $id, array $actor): void {
        $campaign = $this->writes->close($id, $actor);
        ApiResponse::item($campaign);
    }
}
