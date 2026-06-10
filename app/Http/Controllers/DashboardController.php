<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $data = $this->reportService->getDashboardData((int) $year);

        return view('dashboard', compact('data', 'year'));
    }
}
