<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardMetricsService;
use Illuminate\View\View;
use Livewire\Component;

class EngineerDashboard extends Component
{
    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(DashboardMetricsService $metricsService)
    {
        $user = auth()->user();
        $metrics = $metricsService->getEngineerMetrics($user);
        $recentTickets = $metricsService->getRecentTickets($user);

        return view('livewire.dashboard.engineer-dashboard', [
            'metrics' => $metrics,
            'recentTickets' => $recentTickets,
        ]);
    }
}
