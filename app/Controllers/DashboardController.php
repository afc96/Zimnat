<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Dashboard;
use App\Models\Policy;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('dashboard.view');
        $showAdminInsights = Auth::can('audit.view');
        $this->view('dashboard/index', [
            'stats' => Dashboard::stats(),
            'latestPolicies' => Policy::latest(5),
            'officerLoad' => $showAdminInsights ? Dashboard::officerLoad() : [],
            'activity' => $showAdminInsights ? ActivityLog::latest(6) : [],
            'showAdminInsights' => $showAdminInsights,
        ]);
    }
}
