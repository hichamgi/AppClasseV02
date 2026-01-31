<?php
declare(strict_types=1);

namespace App\Repositories\Reporting;

use App\Repositories\AnneeRepository;
use App\Repositories\ProgrammeRepository;
use App\Repositories\Reporting\ClassesRepository;

final class ClassesDashboardRepository
{
    public function __construct(
        private AnneeRepository $annees,
        private ProgrammeRepository $programme,
        private ClassesRepository $repo
    ) {}

    public function getClassesDashboard(): array
    {
        $anneeId = $this->annees->getCurrentId();

        $classes = $this->repo->fetchClassesWithCounts($anneeId);
        $modules = $this->programme->listModules();

        $seancesByClasse = $this->repo->fetchSeancesByAnneeGrouped($anneeId);
        $progressRaw     = $this->repo->fetchProgressByParties($anneeId);

        // calc pourcentage final
        $progress = [];
        foreach ($classes as $c) {
            $cid = (int)$c['id'];
            foreach ($modules as $m) {
                $mid = (int)$m['id'];
                $row = $progressRaw[$cid][$mid] ?? ['total'=>0,'done'=>0,'total_devoirs'=>0,'done_devoirs'=>0];

                $total = (int)$row['total'];
                $done  = (int)$row['done'];

                $pct = ($total > 0) ? ($done / $total) : 0.0;

                // règle demandée : 100% si 3 devoirs faits (basé sur parties.devoir)
                if ((int)$row['total_devoirs'] >= 3 && (int)$row['done_devoirs'] >= 3) {
                    $pct = 1.0;
                }

                $progress[$cid][$mid] = [
                    'pct' => $pct,
                    'done' => $done,
                    'total' => $total,
                    'done_devoirs' => (int)$row['done_devoirs'],
                    'total_devoirs' => (int)$row['total_devoirs'],
                ];
            }
        }

        return compact('anneeId','classes','modules','seancesByClasse','progress');
    }
}