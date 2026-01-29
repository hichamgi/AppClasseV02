<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotebookRepository;
use InvalidArgumentException;

final class NotebookService
{
    public function __construct(private NotebookRepository $repo) {}

    /**
     * @return array{
     *   stats: array{total:int, orphans:int},
     *   items: array<int, array<string, mixed>>
     * }
     */
    public function getGlobalNotebook(array $filters = []): array
    {
        $this->validateFilters($filters);

        $items = $this->repo->fetchGlobalNotebook($filters);

        $orphans = 0;
        foreach ($items as $row) {
            if ((int)($row['parties_count'] ?? 0) === 0) { $orphans++; }
        }

        return [
            'stats' => [
                'total' => \count($items),
                'orphans' => $orphans,
            ],
            'items' => $items,
        ];
    }

    public function attachPartie(int $seanceId, ?int $partieId): void
    {
        if ($seanceId <= 0) {
            throw new InvalidArgumentException('seance_id invalide');
        }
        if ($partieId !== null && $partieId <= 0) {
            throw new InvalidArgumentException('partie_id invalide');
        }

        $ok = $this->repo->attachPartieToSeance($seanceId, $partieId);
        if (!$ok) {
            // règle UX: jamais de page blanche => on remonte une erreur claire
            throw new InvalidArgumentException("Aucune séance mise à jour (id=$seanceId).");
        }
    }

    private function validateFilters(array $filters): void
    {
        // Validation légère (tu peux renforcer selon tes règles)
        foreach (['annee_id', 'classe_id'] as $k) {
            if (isset($filters[$k]) && (!is_numeric($filters[$k]) || (int)$filters[$k] <= 0)) {
                throw new InvalidArgumentException("$k invalide");
            }
        }
        foreach (['date_from', 'date_to'] as $k) {
            if (isset($filters[$k]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filters[$k])) {
                throw new InvalidArgumentException("$k invalide (format attendu YYYY-MM-DD)");
            }
        }
    }

    /**
     * @return array{
     *   classes: array<int, array{id:int, classe:string}>,
     *   parties: array<int, array{partie_id:int, label:string}>,
     *   matrix:  array<int, array<int, array<int, string>>>,  // [partie_id][classe_id] = [dates...]
     *   orphans: array<int, array<int, string>>,              // [classe_id] = [dates...]
     *   filters: array<string, mixed>
     * }
     */
    public function getGlobalNotebookMatrix(array $filters = []): array
    {
        $anneeId  = $this->repo->getCurrentAnneeId();
        $moduleId = isset($filters['module_id']) ? (int)$filters['module_id'] : null;

        $classes = $this->repo->fetchClasses($anneeId);
        $parties = $this->repo->fetchParties($moduleId);

        // On injecte annee_id dans les filtres internes
        $filters['annee_id'] = $anneeId;

        $links = $this->repo->fetchSeancesLinks($filters);

        // Index rapides
        $classIds  = array_map(static fn($c) => (int)$c['id'], $classes);
        $partieIds = array_map(static fn($p) => (int)$p['partie_id'], $parties);

        // Init matrice
        $matrix = [];
        foreach ($partieIds as $pid) {
            $matrix[$pid] = [];
            foreach ($classIds as $cid) {
                $matrix[$pid][$cid] = []; // liste d'items: ['id'=>int,'date'=>string]
            }
        }

        // Init orphelines
        $orphans = [];
        foreach ($classIds as $cid) {
            $orphans[$cid] = []; // liste d'items: ['id'=>int,'date'=>string]
        }

        // Remplissage
        foreach ($links as $r) {
            $sid  = (int)($r['seance_id'] ?? 0);
            $cid  = (int)($r['classe_id'] ?? 0);
            $date = (string)($r['date'] ?? '');
            $pid  = array_key_exists('partie_id', $r) && $r['partie_id'] !== null ? (int)$r['partie_id'] : null;

            if ($sid <= 0 || $cid <= 0 || $date === '') {
                continue; // sécurité
            }
            if (!isset($orphans[$cid])) {
                continue; // hors scope (classe non affichée)
            }

            $item = ['id' => $sid, 'date' => $date];

            if ($pid === null) {
                $orphans[$cid][] = $item;
                continue;
            }

            if (isset($matrix[$pid][$cid])) {
                $matrix[$pid][$cid][] = $item;
            }
        }

        // Dédupliquer par seance_id + trier par date
        $dedupSort = static function (array $items): array {
            $map = [];
            foreach ($items as $it) {
                $id = (int)($it['id'] ?? 0);
                if ($id > 0) {
                    $map[$id] = $it; // écrase doublons
                }
            }
            $items = array_values($map);

            usort($items, static function ($a, $b): int {
                return strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
            });

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
                static fn($p) => ['partie_id' => (int)$p['partie_id'], 'label' => (string)$p['label']],
                $parties
            ),
            'matrix'  => $matrix,
            'orphans' => $orphans,
            'filters' => $filters,
        ];
    }
}