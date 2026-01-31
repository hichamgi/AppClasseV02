<?php
declare(strict_types=1);

namespace App\Repositories\Reporting;

use PDO;

final class ClassesRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<int, array{id:int, classe:string, nb_f:int, nb_m:int, total:int}> */
    public function fetchClassesWithCounts(int $anneeId): array
    {
        $sql = "
          SELECT
            c.id,
            c.classe,
            SUM(CASE WHEN e.sexe='F' THEN 1 ELSE 0 END) AS nb_f,
            SUM(CASE WHEN e.sexe='M' THEN 1 ELSE 0 END) AS nb_m,
            COUNT(*) AS total
          FROM classes c
          JOIN eleves_classes ec ON ec.idclasse = c.id AND ec.depart = 0
          JOIN eleves e ON e.id = ec.ideleve AND e.deleted_at IS NULL
          WHERE c.deleted_at IS NULL
            AND c.idannee = :annee
          GROUP BY c.id
          ORDER BY c.classe ASC
        ";

        $st = $this->db->prepare($sql);
        $st->execute([':annee' => $anneeId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['id']    = (int)$r['id'];
            $r['nb_f']  = (int)$r['nb_f'];
            $r['nb_m']  = (int)$r['nb_m'];
            $r['total'] = (int)$r['total'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Séances + parties (multi) + absents (numéros)
     * @return array<int, array<int, array<string,mixed>>>  // [classe_id] => [rows...]
     */
    public function fetchSeancesByAnneeGrouped(int $anneeId): array
    {
        $sql = "
        SELECT
            s.id AS seance_id,
            s.idclasse AS classe_id,
            s.date,
            s.heured,
            ADDTIME(s.heured, '01:00:00') AS heuref,
            s.observation,

            -- Comptes (pour décider si on affiche A: ou P:)
            COUNT(DISTINCT CASE WHEN se.absent = 1 AND ec.numero > 0 THEN ec.numero END) AS absents_count,
            COUNT(DISTINCT CASE WHEN se.absent = 0 AND ec.numero > 0 THEN ec.numero END) AS presents_count,

            -- ✅ Numéros des absents (ex: 1, 7, 13)
            CAST(
            GROUP_CONCAT(
                DISTINCT CASE
                WHEN se.absent = 1 AND ec.numero > 0 THEN ec.numero
                ELSE NULL
                END
                ORDER BY ec.numero ASC
                SEPARATOR ', '
            )
            AS CHAR) AS absents_nums,

            -- ✅ Numéros des présents (ex: 2, 3, 4)
            CAST(
            GROUP_CONCAT(
                DISTINCT CASE
                WHEN se.absent = 0 AND ec.numero > 0 THEN ec.numero
                ELSE NULL
                END
                ORDER BY ec.numero ASC
                SEPARATOR ', '
            )
            AS CHAR) AS presents_nums,

            -- ✅ Parties multi (|| pour explode en <li>)
            GROUP_CONCAT(
            DISTINCT CONCAT(
                COALESCE(p.num,''),
                ' ',
                COALESCE(p.partie,'')
            )
            ORDER BY p.idmodule ASC, p.id ASC
            SEPARATOR '||'
            ) AS parties_list

        FROM seances s
        JOIN classes c ON c.id = s.idclasse AND c.deleted_at IS NULL

        LEFT JOIN seances_eleves se ON se.idseance = s.id
        LEFT JOIN eleves_classes ec
            ON ec.ideleve = se.ideleve
        AND ec.idclasse = s.idclasse

        LEFT JOIN seances_parties sp ON sp.idseance = s.id
        LEFT JOIN parties p ON p.id = sp.idpartie
        LEFT JOIN modules m ON m.id = p.idmodule

        WHERE s.deleted_at IS NULL
            AND c.idannee = :annee

        GROUP BY s.id
        ORDER BY c.classe ASC, s.date ASC, s.heured ASC, s.id DESC
        ";

        $st = $this->db->prepare($sql);
        $st->execute([':annee' => $anneeId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $cid = (int)$r['classe_id'];
            $out[$cid][] = $r;
        }
        return $out;
    }


    /**
     * Progression programme basée UNIQUEMENT sur seances_parties + règle 3 types de devoir
     * @return array<int, array<int, array{total:int, done:int, total_devoir_types:int, done_devoir_types:int}>>
     *         // [classe_id][module_id] => ...
     */
    public function fetchProgressByParties(int $anneeId): array
    {
        $sql = "
          SELECT
            c.id AS classe_id,
            m.id AS module_id,

            (SELECT COUNT(*)
             FROM parties p2
             WHERE p2.idmodule = m.id AND p2.niv <> 1
            ) AS total_parties,

            COUNT(DISTINCT CASE WHEN p.niv <> 1 THEN p.id END) AS done_parties,

            -- ✅ Nb de types existants dans le module (devoir=1)
            (SELECT COUNT(DISTINCT CAST(
                CASE
                  WHEN p3.devoir = 1 AND p3.niv <> 1 AND LOWER(p3.partie) LIKE '%pratique%' THEN 'pratique'
                  WHEN p3.devoir = 1 AND p3.niv <> 1 AND (LOWER(p3.partie) LIKE '%ecrit%' OR LOWER(p3.partie) LIKE '%écrit%') THEN 'ecrit'
                  WHEN p3.devoir = 1 AND p3.niv <> 1 AND LOWER(p3.partie) LIKE '%activit%' THEN 'activite'
                  ELSE NULL
                END
             AS CHAR))
             FROM parties p3
             WHERE p3.idmodule = m.id
            ) AS total_devoir_types,

            -- ✅ Nb de types réalisés par la classe (via seances_parties)
            COUNT(DISTINCT CAST(
                CASE
                  WHEN p.devoir = 1 AND p.niv <> 1 AND LOWER(p.partie) LIKE '%pratique%' THEN 'pratique'
                  WHEN p.devoir = 1 AND p.niv <> 1 AND (LOWER(p.partie) LIKE '%ecrit%' OR LOWER(p.partie) LIKE '%écrit%') THEN 'ecrit'
                  WHEN p.devoir = 1 AND p.niv <> 1 AND LOWER(p.partie) LIKE '%activit%' THEN 'activite'
                  ELSE NULL
                END
            AS CHAR)) AS done_devoir_types

          FROM classes c
          JOIN modules m

          LEFT JOIN seances s
            ON s.idclasse = c.id
           AND s.deleted_at IS NULL

          LEFT JOIN seances_parties sp
            ON sp.idseance = s.id

          LEFT JOIN parties p
            ON p.id = sp.idpartie
           AND p.idmodule = m.id

          WHERE c.deleted_at IS NULL
            AND c.idannee = :annee

          GROUP BY c.id, m.id
          ORDER BY c.classe ASC, m.id ASC
        ";

        $st = $this->db->prepare($sql);
        $st->execute([':annee' => $anneeId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $cid = (int)$r['classe_id'];
            $mid = (int)$r['module_id'];
            $out[$cid][$mid] = [
                'total'              => (int)$r['total_parties'],
                'done'               => (int)$r['done_parties'],
                'total_devoir_types' => (int)$r['total_devoir_types'],
                'done_devoir_types'  => (int)$r['done_devoir_types'],
            ];
        }

        return $out;
    }
}
