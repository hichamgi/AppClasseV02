<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ModalController extends Controller
{
    private function baseUrl(): string
    {
        $cfg = require dirname(__DIR__) . '/config/app.php';
        return rtrim($cfg['base_url'] ?? '', '/');
    }

    public function newSeance(): void
    {
        $idclasse = (int)($_GET['idclasse'] ?? 0);
        $date = trim((string)($_GET['date'] ?? ''));
        $heured = trim((string)($_GET['heured'] ?? ''));

        // Validation simple
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = '';
        }
        if ($heured !== '' && !preg_match('/^\d{2}:\d{2}$/', $heured)) {
            $heured = '';
        }

        if ($idclasse <= 0) {
            http_response_code(400);
            echo "Paramètre idclasse manquant";
            return;
        }

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT id, classe FROM classes WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $idclasse]);
        $classe = $st->fetch();

        if (!$classe) {
            http_response_code(404);
            echo "Classe introuvable";
            return;
        }

        $this->view('modals/seances_new', [
            'baseUrl' => $this->baseUrl(),
            'classe'  => $classe,
            'date'    => $date,
            'heured'  => $heured,
        ], layout: null);
    }

    public function absences(): void
    {
        $idseance = (int)($_GET['idseance'] ?? 0);
        if ($idseance <= 0) {
            http_response_code(400);
            echo "Paramètre idseance manquant";
            return;
        }

        $pdo = Database::pdo();

        // Trouver la classe de la séance
        $st = $pdo->prepare("
            SELECT s.id, s.idclasse, c.classe, s.date, TIME_FORMAT(s.heured,'%H:%i') as heured
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :id AND s.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['id' => $idseance]);
        $seance = $st->fetch();

        if (!$seance) {
            http_response_code(404);
            echo "Séance introuvable";
            return;
        }

        // Liste élèves actifs de la classe + état absence si déjà enregistré
        $st = $pdo->prepare("
            SELECT e.id, e.nom, e.prenom, e.numerosgs,
                   COALESCE(se.absent,0) as absent,
                   COALESCE(se.justify,0) as justify
            FROM eleves_classes ec
            JOIN eleves e ON e.id = ec.ideleve AND e.deleted_at IS NULL
            LEFT JOIN seances_eleves se ON se.ideleve = e.id AND se.idseance = :idseance
            WHERE ec.idclasse = :idclasse AND ec.depart = 0
            ORDER BY e.nom, e.prenom
        ");
        $st->execute([
            'idseance' => (int)$seance['id'],
            'idclasse' => (int)$seance['idclasse'],
        ]);
        $eleves = $st->fetchAll();

        $this->view('modals/seances_absences', [
            'baseUrl' => $this->baseUrl(),
            'seance' => $seance,
            'eleves' => $eleves,
        ], layout: null);
    }

    public function parties(): void
    {
        $idseance = (int)($_GET['idseance'] ?? 0);
        if ($idseance <= 0) {
            http_response_code(400);
            echo "Paramètre idseance manquant";
            return;
        }

        $pdo = Database::pdo();

        $st = $pdo->prepare("
            SELECT s.id, s.idclasse, c.classe, s.date, TIME_FORMAT(s.heured,'%H:%i') as heured
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :id AND s.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['id' => $idseance]);
        $seance = $st->fetch();
        if (!$seance) {
            http_response_code(404);
            echo "Séance introuvable";
            return;
        }

        $modules = $pdo->query("SELECT id, module, abrev FROM modules ORDER BY module")->fetchAll();

        // Parties du module sélectionné (optionnel: filtrer côté JS)
        $parties = $pdo->query("SELECT p.id, p.partie, p.num, p.idmodule, m.abrev
                                FROM parties p JOIN modules m ON m.id=p.idmodule
                                ORDER BY m.module, p.niv, p.num")->fetchAll();

        // Parties déjà liées à la séance
        $st = $pdo->prepare("
            SELECT sp.idpartie
            FROM seances_parties sp
            WHERE sp.idseance = :id
        ");
        $st->execute(['id' => (int)$seance['id']]);
        $linked = array_column($st->fetchAll(), 'idpartie');

        $this->view('modals/seances_parties', [
            'baseUrl' => $this->baseUrl(),
            'seance' => $seance,
            'modules' => $modules,
            'parties' => $parties,
            'linked' => $linked,
        ], layout: null);
    }

    public function eleveTags(): void
    {
        $ideleve = (int)($_GET['ideleve'] ?? 0);
        if ($ideleve <= 0) {
            http_response_code(400);
            echo "Paramètre ideleve manquant";
            return;
        }

        $pdo = Database::pdo();

        $st = $pdo->prepare("SELECT id, nom, prenom FROM eleves WHERE id=:id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $ideleve]);
        $eleve = $st->fetch();
        if (!$eleve) {
            http_response_code(404);
            echo "Élève introuvable";
            return;
        }

        $tags = $pdo->query("SELECT id, tag, color FROM tags ORDER BY tag")->fetchAll();

        $st = $pdo->prepare("SELECT idtag FROM eleves_tags WHERE ideleve = :id");
        $st->execute(['id' => $ideleve]);
        $selected = array_map('intval', array_column($st->fetchAll(), 'idtag'));

        $this->view('modals/eleves_tags', [
            'baseUrl' => $this->baseUrl(),
            'eleve' => $eleve,
            'tags' => $tags,
            'selected' => $selected,
        ], layout: null);
    }
}
