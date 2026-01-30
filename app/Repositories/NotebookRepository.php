<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NotebookRepository
{
    public function __construct(private PDO $db) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchGlobalNotebook(array $filters = []): array
    {
        $anneeId     = isset($filters['annee_id']) ? (int)$filters['annee_id'] : null;
        $classeId    = isset($filters['classe_id']) ? (int)$filters['classe_id'] : null;
        $dateFrom    = isset($filters['date_from']) ? (string)$filters['date_from'] : null; // YYYY-MM-DD
        $dateTo      = isset($filters['date_to']) ? (string)$filters['date_to'] : null;     // YYYY-MM-DD
        $onlyOrphans = !empty($filters['only_orphans']);

        $sql = "
            SELECT
                s.id                          AS seance_id,
                s.date                        AS date_seance,
                s.heured                      AS heure_debut,
                s.idclasse                    AS classe_id,
                c.classe                      AS classe_nom,
                s.observation                 AS observation,

                COUNT(sp.id)                  AS parties_count,

                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        COALESCE(m.abrev, m.module, 'Module'),
                        ' • ',
                        COALESCE(p.num, ''),
                        ' ',
                        COALESCE(p.partie, '')
                    )
                    ORDER BY m.abrev, p.niv, p.num
                    SEPARATOR ' | '
                ) AS parties_label

            FROM seances s
            INNER JOIN classes c ON c.id = s.idclasse

            LEFT JOIN seances_parties sp ON sp.idseance = s.id
            LEFT JOIN parties p          ON p.id = sp.idpartie
            LEFT JOIN modules m          ON m.id = p.idmodule

            WHERE s.deleted_at IS NULL
        ";

        $params = [];

        // Année = portée par la classe (classes.idannee)
        if ($anneeId !== null) {
            $sql .= " AND c.idannee = :annee_id";
            $params[':annee_id'] = $anneeId;
        }

        if ($classeId !== null) {
            $sql .= " AND s.idclasse = :classe_id";
            $params[':classe_id'] = $classeId;
        }

        if ($dateFrom !== null) {
            $sql .= " AND s.date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if ($dateTo !== null) {
            $sql .= " AND s.date <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        $sql .= " GROUP BY s.id";

        // Orphelines = aucune ligne dans seances_parties
        if ($onlyOrphans) {
            $sql .= " HAVING parties_count = 0";
        }

        $sql .= " ORDER BY s.date DESC, c.classe ASC, s.heured DESC, s.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /** @return array<int, array{id:int, classe:string}> */
    public function fetchClasses(?int $anneeId = null): array
    {
        $sql = "SELECT id, classe FROM classes WHERE deleted_at IS NULL";
        $params = [];

        if ($anneeId !== null) {
            $sql .= " AND idannee = :annee";
            $params[':annee'] = $anneeId;
        }

        $sql .= " ORDER BY classe ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Parties du programme (option: filtrer un module)
     * @return array<int, array{partie_id:int, label:string, module_abrev:?string, module_lib:?string, niv:int, num:string, partie:string}>
     */
    public function fetchParties(?int $moduleId = null): array
    {
        $sql = "
            SELECT
                p.id       AS partie_id,
                p.idmodule AS module_id,
                p.niv      AS niv,
                p.partie   AS partie,
                p.num      AS num,
                m.abrev    AS module_abrev,
                m.module   AS module_lib,
                p.devoir   AS devoir
            FROM parties p
            LEFT JOIN modules m ON m.id = p.idmodule
            WHERE 1=1
        ";
        $params = [];

        if ($moduleId !== null) {
            $sql .= " AND p.idmodule = :mid";
            $params[':mid'] = $moduleId;
        }

        // 🔒 RÈGLE MÉTIER FORTE
        $sql .= " ORDER BY p.idmodule ASC, p.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $oldmod = null;

        // Label lisible (ne touche PAS à l’ordre)
        foreach ($rows as &$r) {
            $mod = trim((string)(($r['module_abrev'] ?? '') . ' : ' . ($r['module_lib'] ?? '')));
            $num = trim((string)($r['num'] ?? ''));
            $position = strstr($num, ':');
            $num = ($position !== false) ? ltrim(substr($position, 1)) : '';
            $par = trim((string)($r['partie'] ?? ''));
            $niv = (int)($r['niv'] ?? 0);
            if( $niv === 1 && $mod !== $oldmod) {
                $r['label'] = trim($mod ?? '') . ' | ' . $par;
                $oldmod = $mod;
            }
            else {
                //$r['label'] = trim(($mod !== '' ? $mod.' • ' : '') . ($num !== '' ? $num.' ' : '') . $par);
                $r['label'] = trim(($num !== '' ? $num.'. ' : '') . $par);
            }
        }
        unset($r);

        return $rows;
    }


    /**
     * Flux normalisé des séances liées aux parties (ou null si orpheline)
     * @return array<int, array{classe_id:int, date:string, partie_id:?int}>
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
                s.id AS seance_id,
                s.idclasse AS classe_id,
                s.date     AS date,
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
            // si module filtré : seules les liaisons vers les parties de ce module restent,
            // et les séances sans partie restent (orphans).
            $sql .= " AND (sp.idpartie IS NULL OR p.idmodule = :mid)";
            $params[':mid'] = $moduleId;
        }

        $sql .= " ORDER BY s.date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentAnneeId(): int
    {
        $stmt = $this->db->query("
            SELECT id
            FROM annees
            ORDER BY id DESC
            LIMIT 1
        ");

        $id = $stmt->fetchColumn();

        if (!$id) {
            throw new \RuntimeException("Aucune année scolaire trouvée dans la table annees");
        }

        return (int)$id;
    }
}