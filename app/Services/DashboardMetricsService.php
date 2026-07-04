<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected TicketQueryService $queryService)
    {
    }

    /**
     * Get aggregate status counts for the given base query.
     *
     * @param  Builder  $query
     * @return array<string, int>
     */
    protected function getStatusCountsForQuery($query): array
    {
        $counts = $query->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all TicketStatus cases are mapped, defaulting to 0 if no records exist
        $result = [];
        foreach (TicketStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * Get metrics for Admin Dashboard.
     *
     * @return array{status_counts: array<string, int>, unassigned_count: int, open_count: int, total_count: int}
     */
    public function getAdminMetrics(User $user): array
    {
        $baseQuery = $this->queryService->scopedQuery($user);

        $statusCounts = $this->getStatusCountsForQuery(clone $baseQuery);
        $unassignedCount = (clone $baseQuery)->whereNull('assigned_engineer_id')->count();
        $totalCount = (clone $baseQuery)->count();

        return [
            'status_counts' => $statusCounts,
            'unassigned_count' => $unassignedCount,
            'open_count' => $statusCounts[TicketStatus::Open->value] ?? 0,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Get metrics for Engineer Dashboard.
     *
     * @return array{status_counts: array<string, int>, total_count: int}
     */
    public function getEngineerMetrics(User $user): array
    {
        $baseQuery = $this->queryService->scopedQuery($user);

        $statusCounts = $this->getStatusCountsForQuery(clone $baseQuery);
        $totalCount = (clone $baseQuery)->count();

        return [
            'status_counts' => $statusCounts,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Get metrics for Client Dashboard.
     *
     * @return array{status_counts: array<string, int>, total_count: int}
     */
    public function getClientMetrics(User $user): array
    {
        $baseQuery = $this->queryService->scopedQuery($user);

        $statusCounts = $this->getStatusCountsForQuery(clone $baseQuery);
        $totalCount = (clone $baseQuery)->count();

        return [
            'status_counts' => $statusCounts,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Get the most recently updated tickets visible to the user.
     */
    public function getRecentTickets(User $user, int $limit = 5): Collection
    {
        return $this->queryService->scopedQuery($user)
            ->with(['client', 'assignedEngineer'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
