<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Eleve;

class ElevesApiController extends Controller
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $rows = $q !== '' ? Eleve::search($q, 50) : Eleve::all(50);
        $this->json(['ok' => true, 'data' => $rows]);
    }

    public function show(array $args): void
    {
        $id = (int)($args['id'] ?? 0);
        $row = Eleve::find($id);
        if (!$row) $this->json(['ok' => false, 'error' => 'Not found'], 404);
        $this->json(['ok' => true, 'data' => $row]);
    }

    // POST /api/seances/absence
    // JSON: { "idseance": 1, "ideleve": 10, "absent": 1, "justify": 0 }
    public function markAbsence(): void
    {
        $this->requireCsrf();
        $data = $this->inputJson();

        $idseance = (int)($data['idseance'] ?? 0);
        $ideleve  = (int)($data['ideleve'] ?? 0);
        $absent   = (int)($data['absent'] ?? 0);
        $justify  = (int)($data['justify'] ?? 0);

        if ($idseance <= 0 || $ideleve <= 0) {
            $this->json(['ok' => false, 'error' => 'Bad payload'], 422);
        }

        // upsert (idseance, ideleve) unique
        $sql = "INSERT INTO seances_eleves (idseance, ideleve, absent, justify, created_at, updated_at)
                VALUES (:s, :e, :a, :j, NOW(), NOW())
                ON DUPLICATE KEY UPDATE absent=VALUES(absent), justify=VALUES(justify), updated_at=NOW()";

        $st = Database::pdo()->prepare($sql);
        $st->execute(['s' => $idseance, 'e' => $ideleve, 'a' => $absent, 'j' => $justify]);

        $this->json(['ok' => true]);
    }

    // POST /api/seances/partie
    // JSON: { "idseance": 1, "idpartie": 5 }
    public function attachPartie(): void
    {
        $this->requireCsrf();
        $data = $this->inputJson();

        $idseance = (int)($data['idseance'] ?? 0);
        $idpartie = (int)($data['idpartie'] ?? 0);

        if ($idseance <= 0 || $idpartie <= 0) {
            $this->json(['ok' => false, 'error' => 'Bad payload'], 422);
        }

        $sql = "INSERT INTO seances_parties (idseance, idpartie, created_at, updated_at)
                VALUES (:s, :p, NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at=NOW()";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['s' => $idseance, 'p' => $idpartie]);

        $this->json(['ok' => true]);
    }

    public function updatePoints(): void
    {
        $this->requireCsrf();
        $data = $this->inputJson();

        $idseance = (int)($data['idseance'] ?? 0);
        $ideleve  = (int)($data['ideleve'] ?? 0);
        $delta    = (int)($data['delta'] ?? 0); // ex: +1 / -1 / +2...

        if ($idseance <= 0 || $ideleve <= 0 || $delta === 0) {
            $this->json(['ok' => false, 'error' => 'Paramètres invalides'], 400);
        }

        // sécurité simple: limiter l’amplitude
        if ($delta < -5 || $delta > 5) {
            $this->json(['ok' => false, 'error' => 'Delta hors limites'], 400);
        }

        $pdo = Database::pdo();

        // Trouver l'année de la séance via sa classe
        $st = $pdo->prepare("
            SELECT c.idannee
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :sid AND s.deleted_at IS NULL AND c.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['sid' => $idseance]);
        $idannee = (int)($st->fetchColumn() ?: 0);
        if ($idannee <= 0) {
            $this->json(['ok' => false, 'error' => 'Séance/année introuvable'], 404);
        }

        // Update points du dossier scolaire correspondant
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("
                UPDATE dossiers_scolaires
                SET points = points + :delta,
                    updated_at = NOW()
                WHERE ideleve = :e AND idannee = :a
                LIMIT 1
            ");
            $st->execute([
                'delta' => $delta,
                'e' => $ideleve,
                'a' => $idannee,
            ]);

            if ($st->rowCount() === 0) {
                $pdo->rollBack();
                $this->json(['ok' => false, 'error' => 'Dossier scolaire introuvable'], 404);
            }

            // renvoyer la nouvelle valeur
            $st = $pdo->prepare("SELECT points FROM dossiers_scolaires WHERE ideleve=:e AND idannee=:a LIMIT 1");
            $st->execute(['e' => $ideleve, 'a' => $idannee]);
            $points = (int)($st->fetchColumn() ?? 0);

            $pdo->commit();
            $this->json(['ok' => true, 'points' => $points]);
        } catch (\Throwable $ex) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'error' => 'Erreur DB'], 500);
        }
    }

    public function updateTags(): void
    {
        $this->requireCsrf();

        $data = $this->inputJson();
        $ideleve = (int)($data['ideleve'] ?? 0);
        $tagIds = $data['tag_ids'] ?? [];

        if ($ideleve <= 0 || !is_array($tagIds)) {
            $this->json(['ok' => false, 'error' => 'Bad payload'], 400);
        }

        // normaliser ids
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds), fn($x) => $x > 0)));

        $db = App::db();

        // transaction
        $db->beginTransaction();
        try {
            $db->execute("DELETE FROM eleves_tags WHERE ideleve = :ideleve", ['ideleve' => $ideleve]);

            if (!empty($tagIds)) {
                $stmt = $db->prepare("INSERT INTO eleves_tags (idtag, ideleve) VALUES (:idtag, :ideleve)");
                foreach ($tagIds as $idtag) {
                    $stmt->execute(['idtag' => $idtag, 'ideleve' => $ideleve]);
                }
            }

            $db->commit();
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->json(['ok' => false, 'error' => 'DB error'], 500);
        }
    }

    public function createTag(): void
    {
        $this->requireCsrf();

        $data = $this->inputJson();
        $tag = trim((string)($data['tag'] ?? ''));
        $color = trim((string)($data['color'] ?? 'secondary'));

        if ($tag === '') {
            $this->json(['ok' => false, 'error' => 'Tag vide'], 400);
        }

        // whitelist couleurs bootstrap
        $allowed = ['secondary','primary','success','warning','danger','info','dark'];
        if (!in_array($color, $allowed, true)) {
            $color = 'secondary';
        }

        $db = App::db();

        try {
            $db->execute(
                "INSERT INTO tags (tag, color) VALUES (:tag, :color)",
                ['tag' => $tag, 'color' => $color]
            );
            $id = (int)$db->lastInsertId();
            $this->json(['ok' => true, 'id' => $id, 'tag' => $tag, 'color' => $color], 201);
        } catch (\Throwable $e) {
            // doublon (unique tag)
            $this->json(['ok' => false, 'error' => 'Tag existe déjà'], 409);
        }
    }


}