<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Auth;
use App\Repositories\Admin\AdminDashboardRepository;
use App\Services\DashboardService;

class AdminDashboardService
{
    public function __construct(
        private AdminDashboardRepository $repo = new AdminDashboardRepository(),
        private DashboardService $dashboard = new DashboardService()
    ) {}

    public function build(): array
    {
        $annee = $this->dashboard->currentAnnee();
        $userId = Auth::id();

        if (!$annee) {
            return [
                'annee' => null,
                'ramadan' => $this->repo->getUserRamadanFlag($userId),
                'classesCount' => 0,
                'elevesCount' => 0,
                'today' => ['seances' => 0, 'absences' => 0],
            ];
        }

        $idannee = (int)$annee['id'];

        return [
            'annee' => $annee,
            'ramadan' => $this->repo->getUserRamadanFlag($userId),
            'classesCount' => $this->repo->countClassesForAnnee($idannee),
            'elevesCount' => $this->repo->countElevesActifsForAnnee($idannee),
            'today' => $this->repo->todayStats($idannee, date('Y-m-d')),
        ];
    }
}