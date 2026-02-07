<?php

declare(strict_types=1);

namespace App\Repositories\Reporting;

use PDO;

final class NotebookRepository
{
    public function __construct(private PDO $db) {}

    public function fetchSeancesLinks(array $filters = []): array
    {
        $anneeId  = isset($filters['annee_id']) ? (int)$filters['annee_id'] : null;
        $classeId = isset($filters['classe_id']) ? (int)$filters['classe_id'] : null;
        $dateFrom = isset($filters['date_from']) ? (string)$filters['date_from'] : null;
        $dateTo   = isset($filters['date_to']) ? (string)$filters['date_to'] : null;
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
            $sql .= " AND (sp.idpartie IS NULL OR p.idmodule = :mid)";
            $params[':mid'] = $moduleId;
        }

        $sql .= " ORDER BY s.date ASC, s.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Nombre de séances déjà imprimées (print=1) sur une année.
     */
    public function countPrintedSeances(int $anneeId): int
    {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM seances s
            INNER JOIN classes c ON c.id = s.idclasse
            WHERE s.deleted_at IS NULL
              AND c.deleted_at IS NULL
              AND c.idannee = :a
              AND s.print = 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':a' => $anneeId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    /**
     * Séances à imprimer (par défaut print=0) pour l'année en cours.
     * Retourne: id, idclasse, classe, date, heured(HH:MM), observation, print
     */
    public function fetchSeancesForPrint(int $anneeId, array $filters = []): array
    {
        $dateFrom = isset($filters['date_from']) ? (string)$filters['date_from'] : null;
        $dateTo   = isset($filters['date_to']) ? (string)$filters['date_to'] : null;
        $onlyUnprinted = array_key_exists('only_unprinted', $filters) ? (bool)$filters['only_unprinted'] : true;

        $sql = "
            SELECT
                s.id,
                s.idclasse,
                c.classe,
                s.date,
                TIME_FORMAT(s.heured, '%H:%i') AS heured,
                s.observation,
                s.print
            FROM seances s
            INNER JOIN classes c ON c.id = s.idclasse
            WHERE s.deleted_at IS NULL
              AND c.deleted_at IS NULL
              AND c.idannee = :a
        ";
        $params = [':a' => $anneeId];

        if ($onlyUnprinted) {
            $sql .= " AND s.print = 0";
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= " AND s.date >= :df";
            $params[':df'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= " AND s.date <= :dt";
            $params[':dt'] = $dateTo;
        }

        $sql .= " ORDER BY c.classe ASC, s.date ASC, s.heured ASC, s.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Parties d'une liste de séances, triées par idmodule puis idpartie.
     * Chaque ligne: idseance, idmodule, module, abrev, idpartie, partie, num, devoir, niv
     */
    public function fetchPartiesBySeanceIds(array $seanceIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $seanceIds), fn($v) => $v > 0));
        if (empty($ids)) return [];

        $in = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT
                sp.idseance,
                m.id        AS idmodule,
                m.module    AS module,
                m.abrev     AS abrev,
                p.id        AS idpartie,
                p.partie    AS partie,
                p.num       AS num,
                p.devoir    AS devoir,
                p.niv       AS niv
            FROM seances_parties sp
            INNER JOIN parties p ON p.id = sp.idpartie
            INNER JOIN modules m ON m.id = p.idmodule
            WHERE sp.idseance IN ($in)
            ORDER BY m.id ASC, p.id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marquer imprimées
     */
    public function markPrinted(array $seanceIds): int
    {
        $ids = array_values(array_filter(array_map('intval', $seanceIds), fn($v) => $v > 0));
        if (empty($ids)) return 0;

        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE seances SET print = 1 WHERE id IN ($in)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->rowCount();
    }
}
