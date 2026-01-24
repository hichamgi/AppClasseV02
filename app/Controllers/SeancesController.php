<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class SeancesController extends Controller
{
    public function show(array $args): void
    {
        $id = (int)($args['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo "ID séance invalide";
            return;
        }

        $pdo = Database::pdo();

        // Séance + classe
        $st = $pdo->prepare("
            SELECT
                s.id,
                s.idclasse,
                c.classe,
                c.idannee,
                s.date,
                TIME_FORMAT(s.heured,'%H:%i') AS heured,
                s.observation
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :id
              AND s.deleted_at IS NULL
              AND c.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['id' => $id]);
        $seance = $st->fetch();

        if (!$seance) {
            http_response_code(404);
            echo "Séance introuvable";
            return;
        }

        // Séance précédente (même classe) : strictement avant (date, heured)
        $st = $pdo->prepare("
            SELECT id
            FROM seances
            WHERE idclasse = :cid
              AND deleted_at IS NULL
              AND (
                    date < :dte1
                    OR (date = :dte2 AND heured < :hdebut)
              )
            ORDER BY date DESC, heured DESC
            LIMIT 1
        ");
        $st->execute([
            'cid'    => (int)$seance['idclasse'],
            'dte1'   => (string)$seance['date'],
            'dte2'   => (string)$seance['date'],
            'hdebut' => (string)$seance['heured'], // 'HH:MM'
        ]);
        $prevSeanceId = (int)($st->fetchColumn() ?: 0);

        // Élèves actifs de la classe + état absence + points/participation
        // + compteur absences sur l'année (toutes classes, même si depart=1)
        // IMPORTANT: placeholders distincts (PDO MySQL n'aime pas la répétition)
        $st = $pdo->prepare("
            SELECT
                e.id,
                ec.numero,
                e.nom, e.prenom,
                e.nomar, e.prenomar,

                COALESCE(ds.points, 0) AS points,
                COALESCE(ds.participation, 0) AS participation,

                COALESCE(cur.absent, 0) AS absent,
                COALESCE(cur.justify, 0) AS justify,
                COALESCE(prev.absent, 0) AS prev_absent,

                COALESCE(ay.abs_year, 0) AS abs_year

            FROM eleves_classes ec
            JOIN eleves e
                ON e.id = ec.ideleve
               AND e.deleted_at IS NULL

            LEFT JOIN dossiers_scolaires ds
                ON ds.ideleve = e.id
               AND ds.idannee = :idannee_ds

            -- absence séance actuelle
            LEFT JOIN seances_eleves cur
                ON cur.idseance = :idseance
               AND cur.ideleve = e.id

            -- absence séance précédente (même classe)
            LEFT JOIN seances_eleves prev
                ON prev.idseance = :prevSeanceId
               AND prev.ideleve = e.id

            -- compteur absences sur l'année: toutes classes de l'année, sans tenir compte depart
            LEFT JOIN (
                SELECT
                    se2.ideleve,
                    COUNT(*) AS abs_year
                FROM seances_eleves se2
                JOIN seances s2
                    ON s2.id = se2.idseance
                   AND s2.deleted_at IS NULL
                JOIN classes c2
                    ON c2.id = s2.idclasse
                   AND c2.deleted_at IS NULL
                WHERE c2.idannee = :idannee_ay
                  AND se2.absent = 1
                GROUP BY se2.ideleve
            ) ay ON ay.ideleve = e.id

            WHERE ec.idclasse = :idclasse
              AND ec.depart = 0
            ORDER BY ec.numero ASC, e.nom ASC, e.prenom ASC
        ");

        $idannee = (int)$seance['idannee'];

        $st->execute([
            'idseance'     => (int)$seance['id'],
            'prevSeanceId' => $prevSeanceId,
            'idclasse'     => (int)$seance['idclasse'],
            'idannee_ds'   => $idannee,
            'idannee_ay'   => $idannee,
        ]);

        $eleves = $st->fetchAll();

        // Parties (toutes) + date dernière réalisation pour cette classe + déjà liée à cette séance
        $st = $pdo->prepare("
            SELECT
                p.id,
                p.partie,
                p.num,
                p.niv,
                p.devoir,
                p.idmodule,
                m.module,
                m.abrev,
                MAX(s2.date) AS last_date,
                MAX(CASE WHEN sp2.idseance = :idseance THEN 1 ELSE 0 END) AS linked_to_current
            FROM parties p
            JOIN modules m ON m.id = p.idmodule
            LEFT JOIN seances_parties sp2 ON sp2.idpartie = p.id
            LEFT JOIN seances s2
                ON s2.id = sp2.idseance
               AND s2.idclasse = :idclasse
               AND s2.deleted_at IS NULL
            GROUP BY
                p.id, p.partie, p.num, p.niv, p.devoir, p.idmodule, m.module, m.abrev
            ORDER BY
                p.idmodule ASC,
                p.id ASC
        ");
        $st->execute([
            'idseance' => (int)$seance['id'],
            'idclasse' => (int)$seance['idclasse'],
        ]);
        $parties = $st->fetchAll();

        $cfg = require dirname(__DIR__) . '/config/app.php';
        $baseUrl = rtrim($cfg['base_url'] ?? '', '/');

        $this->view('seances/show', [
            'baseUrl' => $baseUrl,
            'seance'  => $seance,
            'eleves'  => $eleves,
            'parties' => $parties,
        ]);
    }
}
