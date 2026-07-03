<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\MonthlyReportRemark;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonthlyReportService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected TicketQueryService $queryService) {}

    /**
     * Get aggregate status counts for the query.
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

        $result = [];
        foreach (TicketStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * Get aggregate priority counts for the query.
     *
     * @param  Builder  $query
     * @return array<string, int>
     */
    protected function getPriorityCountsForQuery($query): array
    {
        $counts = $query->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $result = [];
        foreach (Priority::cases() as $priority) {
            $result[$priority->value] = (int) ($counts[$priority->value] ?? 0);
        }

        return $result;
    }

    /**
     * Generate the monthly report details.
     *
     * @return array{
     *     client_id: int,
     *     month: int,
     *     year: int,
     *     total_count: int,
     *     status_counts: array<string, int>,
     *     priority_counts: array<string, int>,
     *     tickets: Collection,
     *     remark: ?MonthlyReportRemark
     * }
     */
    public function generateReport(User $user, int $clientId, int $month, int $year): array
    {
        // Enforce role-based query scoping for authorization safety
        $baseQuery = $this->queryService->scopedQuery($user);

        // Client role is hard-locked to their own client company
        if ($user->role === Role::Client) {
            $clientId = $user->client_id;
        }

        $ticketQuery = $baseQuery->where('client_id', $clientId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        $totalCount = (clone $ticketQuery)->count();
        $statusCounts = $this->getStatusCountsForQuery(clone $ticketQuery);
        $priorityCounts = $this->getPriorityCountsForQuery(clone $ticketQuery);

        $tickets = (clone $ticketQuery)
            ->with(['assignedEngineer'])
            ->orderBy('created_at', 'asc')
            ->get();

        $remark = MonthlyReportRemark::where('client_id', $clientId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return [
            'client_id' => $clientId,
            'month' => $month,
            'year' => $year,
            'total_count' => $totalCount,
            'status_counts' => $statusCounts,
            'priority_counts' => $priorityCounts,
            'tickets' => $tickets,
            'remark' => $remark,
        ];
    }

    /**
     * Save or update monthly report remarks.
     */
    public function saveRemark(int $clientId, int $month, int $year, ?string $remarks, int $createdBy): MonthlyReportRemark
    {
        return MonthlyReportRemark::updateOrCreate(
            [
                'client_id' => $clientId,
                'month' => $month,
                'year' => $year,
            ],
            [
                'remarks' => $remarks,
                'created_by' => $createdBy,
            ]
        );
    }
}
