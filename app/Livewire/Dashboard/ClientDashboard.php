<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardMetricsService;
use Illuminate\View\View;
use Livewire\Component;

class ClientDashboard extends Component
{
    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(DashboardMetricsService $metricsService)
    {
        $user = auth()->user();
        $metrics = $metricsService->getClientMetrics($user);
        $recentTickets = $metricsService->getRecentTickets($user);

        return view('livewire.dashboard.client-dashboard', [
            'metrics' => $metrics,
            'recentTickets' => $recentTickets,
        ]);
    }
}
