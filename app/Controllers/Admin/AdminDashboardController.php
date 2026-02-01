<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\Admin\AdminDashboardService;

class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $svc = new AdminDashboardService();
        $data = $svc->build();
        $this->view('admin/dashboard', $data);
    }
}
