<?php
declare(strict_types=1);

namespace App\Repositories\Reporting;

use PDO;

final class NotebookRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Flux normalisé des séances liées aux parties (ou null si orpheline)
     * @return array<int, array{seance_id:int, classe_id:int, date:string, partie_id:?int}>
     */
    public function fetchSeancesLinks(array $filters = []): array
    {
        $anneeId  = isset($filters['annee_id']) ? (int)$filters['annee_id'] : null;
        $classeId = isset($filters['classe_id']) ? (int)$filters['classe_id'] : null;
        $dateFrom = isset($filters['date_from']) ? (string)$filters['date_from'] : null; // YYYY-MM-DD
        $dateTo   = isset($filters['date_to']) ? (string)$filters['date_to'] : null;     // YYYY-MM-DD
        $moduleId = isset($filters['module_id']) ? (int)$filters['module_id'] : null;

        $sql = "
            SELECT
                s.id        AS seance_id,
                s.idclasse  AS classe_id,
                s.date      AS date,
                sp.idpartie AS partie_id
            FROM seances s
            INNER JOIN classes c ON c.id = s.idclasse
            LEFT JOIN seances_parties sp ON sp.idseance = s.id
            LEFT JOIN parties p ON p.id = sp.idpartie
            WHERE s.deleted_at IS NULL
              AND c.deleted_at IS NULL
        ";
        $params = [];

        if ($anneeId !== null) {
            $sql .= " AND c.idannee = :annee";
            $params[':annee'] = $anneeId;
        }
        if ($classeId !== null) {
            $sql .= " AND s.idclasse = :classe";
            $params[':classe'] = $classeId;
        }
        if ($dateFrom !== null) {
            $sql .= " AND s.date >= :df";
            $params[':df'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $sql .= " AND s.date <= :dt";
            $params[':dt'] = $dateTo;
        }

        if ($moduleId !== null) {
            // filtre module : on garde orphelines + parties du module
            $sql .= " AND (sp.idpartie IS NULL OR p.idmodule = :mid)";
            $params[':mid'] = $moduleId;
        }

        $sql .= " ORDER BY s.date ASC, s.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
