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
            SELECT s.id, s.idclasse, c.classe, c.idannee, s.date,
                   TIME_FORMAT(s.heured,'%H:%i') AS heured,
                   s.observation
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :id AND s.deleted_at IS NULL AND c.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['id' => $id]);
        $seance = $st->fetch();
        if (!$seance) {
            http_response_code(404);
            echo "Séance introuvable";
            return;
        }

        // Élèves actifs de la classe + état absence (pour cette séance)
        $st = $pdo->prepare("
            SELECT
              e.id,
              ec.numero,
              e.nom, e.prenom,
              COALESCE(se.absent,0) AS absent,
              COALESCE(se.justify,0) AS justify
            FROM eleves_classes ec
            JOIN eleves e ON e.id = ec.ideleve AND e.deleted_at IS NULL
            LEFT JOIN seances_eleves se ON se.idseance = :idseance AND se.ideleve = e.id
            WHERE ec.idclasse = :idclasse AND ec.depart = 0
            ORDER BY ec.numero ASC, e.nom ASC, e.prenom ASC
        ");
        $st->execute([
            'idseance' => (int)$seance['id'],
            'idclasse' => (int)$seance['idclasse'],
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
            LEFT JOIN seances s2 ON s2.id = sp2.idseance
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
            'seance' => $seance,
            'eleves' => $eleves,
            'parties' => $parties,
        ]);
    }
}