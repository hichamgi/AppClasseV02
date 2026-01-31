<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AnneeRepository;
use App\Repositories\ClasseRepository;
use App\Repositories\ProgrammeRepository;
use App\Repositories\Reporting\NotebookRepository;
use InvalidArgumentException;

final class NotebookService
{
    public function __construct(
        private AnneeRepository $annees,
        private ClasseRepository $classesRepo,
        private ProgrammeRepository $programme,
        private NotebookRepository $notebookRepo
    ) {}

    public function getGlobalNotebookMatrix(array $filters = []): array
    {
        $anneeId  = $this->annees->getCurrentId();
        $moduleId = isset($filters['module_id']) ? (int)$filters['module_id'] : null;

        $classes = $this->classesRepo->findByAnnee($anneeId);
        $parties = $this->programme->listParties($moduleId);

        $filters['annee_id'] = $anneeId;
        $links = $this->notebookRepo->fetchSeancesLinks($filters);

        $classIds  = array_map(static fn($c) => (int)$c['id'], $classes);
        $partieIds = array_map(static fn($p) => (int)$p['partie_id'], $parties);

        $matrix = [];
        foreach ($partieIds as $pid) {
            foreach ($classIds as $cid) {
                $matrix[$pid][$cid] = [];
            }
        }

        $orphans = [];
        foreach ($classIds as $cid) {
            $orphans[$cid] = [];
        }

        foreach ($links as $r) {
            $sid  = (int)($r['seance_id'] ?? 0);
            $cid  = (int)($r['classe_id'] ?? 0);
            $date = (string)($r['date'] ?? '');
            $pid  = array_key_exists('partie_id', $r) && $r['partie_id'] !== null ? (int)$r['partie_id'] : null;

            if ($sid <= 0 || $cid <= 0 || $date === '' || !isset($orphans[$cid])) continue;

            $item = ['id' => $sid, 'date' => $date];

            if ($pid === null) {
                $orphans[$cid][] = $item;
            } elseif (isset($matrix[$pid][$cid])) {
                $matrix[$pid][$cid][] = $item;
            }
        }

        $dedupSort = static function (array $items): array {
            $map = [];
            foreach ($items as $it) {
                $id = (int)($it['id'] ?? 0);
                if ($id > 0) $map[$id] = $it;
            }
            $items = array_values($map);
            usort($items, static fn($a, $b) => strcmp((string)$a['date'], (string)$b['date']));
            return $items;
        };

        foreach ($orphans as $cid => $items) {
            $orphans[$cid] = $dedupSort($items);
        }
        foreach ($matrix as $pid => $cols) {
            foreach ($cols as $cid => $items) {
                $matrix[$pid][$cid] = $dedupSort($items);
            }
        }

        return [
            'classes' => $classes,
            'parties' => array_map(
                static fn($p) => [
                    'partie_id' => (int)$p['partie_id'],
                    'module_id' => (int)$p['module_id'],
                    'niv'       => (int)$p['niv'],
                    'label'     => (string)$p['label'],
                    'devoir'    => (int)$p['devoir'],
                ],
                $parties
            ),
            'matrix'  => $matrix,
            'orphans' => $orphans,
            'filters' => $filters,
        ];
    }
}