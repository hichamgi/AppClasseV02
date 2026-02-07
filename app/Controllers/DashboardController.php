<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DashboardService;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): void
    {
        $svc = new DashboardService();

        $annee = $svc->currentAnnee();

        if (!$annee) {
            $this->view('dashboard/index', [
                'annee' => null,
                'classesCount' => 0,
                'elevesActifsCount' => 0,
                'timetable' => [],
                'showSaturday' => false,
                'lastParties' => [],
            ]);
            return;
        }

        $idannee = (int)$annee['id'];
        $todaySeances = $svc->todaySeances($idannee);

        $classesCount = $svc->countClassesForAnnee($idannee);
        $elevesActifsCount = $svc->countElevesActifsForAnnee($idannee);

        $timetable = $svc->globalTimetableForAnnee($idannee);
        $showSaturday = $svc->hasSaturdayGlobal($idannee);

        $lastParties = $svc->lastPartieByClasseForAnnee($idannee);

        $userId = (int)($_SESSION['user_id'] ?? 0);

        $ramadan = 0;
        if ($userId > 0) {
            $ramadan = User::isRamadan($userId);
        }

        $this->view('dashboard/index', compact(
            'annee',
            'classesCount',
            'elevesActifsCount',
            'timetable',
            'showSaturday',
            'lastParties',
            'todaySeances',
            'ramadan'
        ));
    }
}
