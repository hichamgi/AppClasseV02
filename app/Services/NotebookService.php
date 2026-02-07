<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AnneeRepository;
use App\Repositories\ClasseRepository;
use App\Repositories\ProgrammeRepository;
use App\Repositories\Reporting\NotebookRepository;

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

    /**
     * Données d'impression cahier (séances non imprimées + page de garde si aucune imprimée)
     */
    public function getPrintNotebookData(array $filters = []): array
    {
        $anneeId = $this->annees->getCurrentId();
        $annee = $this->annees->getCurrent(); // si ta repo a cette méthode, sinon ignore dans controller
        $classes = $this->classesRepo->findByAnnee($anneeId);

        $printedCount = $this->notebookRepo->countPrintedSeances($anneeId);
        $hasAnyPrinted = $printedCount > 0;

        // séances non imprimées, filtrables par date
        $filtersRepo = [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'only_unprinted' => true,
        ];

        $seances = $this->notebookRepo->fetchSeancesForPrint($anneeId, $filtersRepo);
        $seanceIds = array_map(static fn($s) => (int)$s['id'], $seances);

        $partsRows = $this->notebookRepo->fetchPartiesBySeanceIds($seanceIds);

        // index parties par seance
        $partsBySeance = [];
        foreach ($partsRows as $r) {
            $sid = (int)$r['idseance'];
            $partsBySeance[$sid][] = [
                'idmodule' => (int)$r['idmodule'],
                'module' => (string)($r['module'] ?? ''),
                'abrev' => (string)($r['abrev'] ?? ''),
                'idpartie' => (int)$r['idpartie'],
                'partie' => (string)($r['partie'] ?? ''),
                'num' => (string)($r['num'] ?? ''),
                'devoir' => (int)($r['devoir'] ?? 0),
                'niv' => (int)($r['niv'] ?? 0),
            ];
        }

        // group par classe
        $byClasse = [];
        foreach ($seances as $s) {
            $cid = (int)$s['idclasse'];
            $byClasse[$cid]['classe'] = (string)$s['classe'];
            $byClasse[$cid]['items'][] = [
                'id' => (int)$s['id'],
                'date' => (string)$s['date'],
                'heured' => (string)$s['heured'],
                'observation' => (string)($s['observation'] ?? ''),
                // TODO absences: à brancher quand tu me donnes la source absences
                'absences' => '',
                'parts' => $partsBySeance[(int)$s['id']] ?? [],
            ];
        }

        // remplir classes même si vides
        foreach ($classes as $c) {
            $cid = (int)$c['id'];
            if (!isset($byClasse[$cid])) {
                $byClasse[$cid] = ['classe' => (string)$c['classe'], 'items' => []];
            }
        }

        // tri des classes par nom
        uasort($byClasse, static fn($a, $b) => strcmp((string)$a['classe'], (string)$b['classe']));

        $prefixes = [];

        foreach ($byClasse as $pack) {
            foreach (($pack['items'] ?? []) as $it) {
                foreach (($it['parts'] ?? []) as $p) {
                    $num = trim((string)($p['num'] ?? ''));
                    if ($num === '') continue;

                    // "M1 L1 : 2.3" => prefix = "M1 L1"
                    $tmp = array_map('trim', explode(':', $num, 2));
                    $prefix = $tmp[0] ?? '';
                    if ($prefix !== '') $prefixes[$prefix] = true;
                }
            }
        }

        $prefixes = array_keys($prefixes);

        $rootByPrefix = [];

        if (!empty($prefixes)) {
            $needNums = array_map(fn($p) => $p . ' : 0', $prefixes);

            $in = implode(',', array_fill(0, count($needNums), '?'));

            $sql = "SELECT num, partie FROM parties WHERE num IN ($in)";
            $stmt = \App\Core\Database::pdo()->prepare($sql);
            $stmt->execute($needNums);

            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $num = trim((string)($r['num'] ?? ''));       // ex "M1 L1 : 0"
                $partie = trim((string)($r['partie'] ?? '')); // ex "Introduction"

                // prefix = "M1 L1"
                $tmp = array_map('trim', explode(':', $num, 2));
                $prefix = $tmp[0] ?? '';
                if ($prefix !== '' && $partie !== '') {
                    $rootByPrefix[$prefix] = $partie;
                }
            }
        }

        return [
            'annee_id' => $anneeId,
            'classes' => $classes,
            'byClasse' => $byClasse,
            'hasAnyPrinted' => $hasAnyPrinted,
            'filters' => $filters,
            'rootByPrefix' => $rootByPrefix,
        ];
    }

    public function confirmPrint(array $seanceIds): array
    {
        $count = $this->notebookRepo->markPrinted($seanceIds);
        return ['updated' => $count];
    }
}
