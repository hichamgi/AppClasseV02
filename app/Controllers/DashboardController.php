<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\DashboardService;

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

        $this->view('dashboard/index', compact(
            'annee',
            'classesCount',
            'elevesActifsCount',
            'timetable',
            'showSaturday',
            'lastParties',
            'todaySeances'
        ));
    }
}
