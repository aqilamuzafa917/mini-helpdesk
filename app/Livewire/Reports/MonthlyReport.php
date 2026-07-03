<?php

namespace App\Livewire\Reports;

use App\Enums\Role;
use App\Http\Requests\UpdateMonthlyReportRequest;
use App\Models\Client;
use App\Services\MonthlyReportService;
use Illuminate\View\View;
use Livewire\Component;

class MonthlyReport extends Component
{
    /**
     * Selected parameters.
     */
    public ?int $client_id = null;

    public int $month;

    public int $year;

    public string $remarks = '';

    /**
     * Dropdowns lists.
     */
    public $clients = [];

    public $months = [];

    public $years = [];

    /**
     * Map query string parameters.
     *
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'client_id' => ['except' => null],
        'month' => ['except' => null],
        'year' => ['except' => null],
    ];

    /**
     * Mount the component and initialize options.
     */
    public function mount(): void
    {
        $user = auth()->user();

        // Otorisasi: Hanya Admin dan Client yang boleh mengakses laporan bulanan
        if ($user->role !== Role::Admin && $user->role !== Role::Client) {
            abort(403, 'Unauthorized');
        }

        $this->month = (int) request('month', now()->month);
        $this->year = (int) request('year', now()->year);

        if ($user->role === Role::Admin) {
            $this->clients = Client::orderBy('name')->get();
            $this->client_id = (int) request('client_id', $this->clients->first()?->id);
        } else {
            $this->client_id = $user->client_id;
        }

        // Generate month options
        for ($m = 1; $m <= 12; $m++) {
            $this->months[$m] = date('F', mktime(0, 0, 0, $m, 1));
        }

        // Generate year options
        $currentYear = now()->year;
        for ($y = $currentYear - 5; $y <= $currentYear + 2; $y++) {
            $this->years[$y] = $y;
        }

        $this->loadRemark();
    }

    /**
     * React when inputs change.
     */
    public function updatedClientId(): void
    {
        $this->loadRemark();
    }

    public function updatedMonth(): void
    {
        $this->loadRemark();
    }

    public function updatedYear(): void
    {
        $this->loadRemark();
    }

    /**
     * Fetch existing remarks for this combination.
     */
    protected function loadRemark(): void
    {
        if (! $this->client_id) {
            return;
        }

        $reportService = app(MonthlyReportService::class);
        $report = $reportService->generateReport(auth()->user(), $this->client_id, $this->month, $this->year);
        $this->remarks = $report['remark']?->remarks ?? '';
    }

    /**
     * Save remarks (Admin only).
     */
    public function saveRemarks(MonthlyReportService $reportService): void
    {
        $user = auth()->user();

        $request = new UpdateMonthlyReportRequest;
        $payload = [
            'client_id' => $this->client_id,
            'month' => $this->month,
            'year' => $this->year,
            'remarks' => $this->remarks,
        ];
        $request->merge($payload);

        if (! $request->authorize()) {
            abort(403, 'Unauthorized');
        }

        $this->validate($request->rules());

        $reportService->saveRemark($this->client_id, $this->month, $this->year, $this->remarks, $user->id);

        session()->flash('report_status', 'Remarks saved successfully.');
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(MonthlyReportService $reportService)
    {
        $user = auth()->user();

        if (! $this->client_id) {
            return view('livewire.reports.monthly-report', [
                'report' => null,
            ]);
        }

        $report = $reportService->generateReport($user, $this->client_id, $this->month, $this->year);

        return view('livewire.reports.monthly-report', [
            'report' => $report,
        ]);
    }
}
