<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AnneeRepository;
use App\Repositories\ProgrammeRepository;
use App\Repositories\Reporting\ClassesRepository;

final class ClassesController extends Controller
{
    public function __construct(
        private AnneeRepository $annees,
        private ProgrammeRepository $programme,
        private ClassesRepository $report
    ) {}

    public function index(): void
    {
        $anneeId = $this->annees->getCurrentId();

        $classes = $this->report->fetchClassesWithCounts($anneeId);
        $modules = $this->programme->listModules();

        $seancesByClasse = $this->report->fetchSeancesByAnneeGrouped($anneeId);
        $progressRaw     = $this->report->fetchProgressByParties($anneeId);

        // Calcul % final (sans Service)
        $progress = [];
        foreach ($classes as $c) {
            $cid = (int)$c['id'];

            foreach ($modules as $m) {
                $mid = (int)$m['id'];
                $row = $progressRaw[$cid][$mid] ?? ['total'=>0,'done'=>0,'total_devoir_types'=>0,'done_devoir_types'=>0];

                $total = (int)$row['total'];
                $done  = (int)$row['done'];

                $pct = ($total > 0) ? ($done / $total) : 0.0;

                // ✅ 100% si Pratique+Ecrit+Activité faits (3 types)
                if ((int)$row['done_devoir_types'] >= 3) {
                    $pct = 1.0;
                }

                $progress[$cid][$mid] = [
                    'pct' => $pct,
                    'done' => $done,
                    'total' => $total,
                    'done_devoir_types' => (int)$row['done_devoir_types'],
                ];
            }
        }

        $this->view('classes/index', [
            'anneeId' => $anneeId,
            'classes' => $classes,
            'modules' => $modules,
            'progress' => $progress,
            'seancesByClasse' => $seancesByClasse,
        ]);
    }
}