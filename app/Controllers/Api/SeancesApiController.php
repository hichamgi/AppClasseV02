<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Csrf;
use App\Core\Database;
use PDO;

final class SeancesApiController
{
    public function create(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $idclasse = (int)($_POST['idclasse'] ?? 0);
        $date     = trim((string)($_POST['date'] ?? ''));
        $heured   = trim((string)($_POST['heured'] ?? '')); // attendu HH:MM
        $obs      = trim((string)($_POST['observation'] ?? ''));

        if ($idclasse <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'idclasse invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Date invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $heured)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Heure début invalide (HH:MM)'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo = Database::pdo();
        $heuredDb = $heured . ':00';

        // ⚠️ règle prof unique (si tu gardes uk_seance_unique(date,heured))
        $stSlot = $pdo->prepare("
            SELECT id, idclasse
            FROM seances
            WHERE date = :d
              AND heured = :h
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stSlot->execute([':d' => $date, ':h' => $heuredDb]);
        if ($row = $stSlot->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(409);
            echo json_encode([
                'ok' => false,
                'error' => 'Créneau déjà pris (prof unique)',
                'reason' => 'slot_taken',
                'id' => (int)$row['id'],
                'classe_id' => (int)$row['idclasse'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // doublon même classe (utile même sans uk_seance_unique)
        $st = $pdo->prepare("
            SELECT id
            FROM seances
            WHERE idclasse = :c
              AND date     = :d
              AND heured   = :h
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute([':c' => $idclasse, ':d' => $date, ':h' => $heuredDb]);

        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(409);
            echo json_encode([
                'ok' => false,
                'error' => 'Une séance existe déjà pour cette classe à cette date/heure',
                'reason' => 'exists',
                'id' => (int)$row['id'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo->beginTransaction();
        try {
            // Création séance
            $st = $pdo->prepare("
                INSERT INTO seances (idclasse, date, heured, observation)
                VALUES (:c, :d, :h, :obs)
            ");
            $st->execute([
                ':c'   => $idclasse,
                ':d'   => $date,
                ':h'   => $heuredDb,
                ':obs' => ($obs === '' ? null : $obs),
            ]);

            $seanceId = (int)$pdo->lastInsertId();

            // Matérialiser "tout le monde présent" (eleves depart=0)
            $st = $pdo->prepare("
                INSERT INTO seances_eleves (idseance, ideleve, absent, justify, created_at, updated_at)
                SELECT :sid, ec.ideleve, 0, 0, NOW(), NOW()
                FROM eleves_classes ec
                JOIN eleves e ON e.id = ec.ideleve AND e.deleted_at IS NULL
                WHERE ec.idclasse = :cid
                  AND ec.depart = 0
                ON DUPLICATE KEY UPDATE seances_eleves.updated_at = NOW()
            ");
            $st->execute([
                ':sid' => $seanceId,
                ':cid' => $idclasse,
            ]);

            $pdo->commit();

            echo json_encode(['ok' => true, 'id' => $seanceId, 'refresh' => true], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function createBulk(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '', true);
        if (!is_array($payload)) $payload = [];

        $date = trim((string)($payload['date'] ?? ''));
        $sessions = $payload['sessions'] ?? [];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Date invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!is_array($sessions) || count($sessions) === 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Sessions manquantes'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo = Database::pdo();

        // Préparer statements une fois
        $stSlot = $pdo->prepare("
            SELECT id, idclasse
            FROM seances
            WHERE date = :d
              AND heured = :h
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stExists = $pdo->prepare("
            SELECT id
            FROM seances
            WHERE idclasse = :c
              AND date     = :d
              AND heured   = :h
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stInsert = $pdo->prepare("
            INSERT INTO seances (idclasse, date, heured, observation)
            VALUES (:c, :d, :h, NULL)
        ");

        $stInsertPresence = $pdo->prepare("
            INSERT INTO seances_eleves (idseance, ideleve, absent, justify, created_at, updated_at)
            SELECT :sid, ec.ideleve, 0, 0, NOW(), NOW()
            FROM eleves_classes ec
            JOIN eleves e ON e.id = ec.ideleve AND e.deleted_at IS NULL
            WHERE ec.idclasse = :cid
              AND ec.depart = 0
            ON DUPLICATE KEY UPDATE seances_eleves.updated_at = NOW()
        ");

        $results = [];

        foreach ($sessions as $s) {
            $idclasse = (int)($s['classe_id'] ?? 0);
            $heured = trim((string)($s['heured'] ?? ''));

            if ($idclasse <= 0 || !preg_match('/^\d{2}:\d{2}$/', $heured)) {
                $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => false, 'reason' => 'invalid'];
                continue;
            }

            $heuredDb = $heured . ':00';

            // ✅ créneau déjà pris (prof unique)
            $stSlot->execute([':d' => $date, ':h' => $heuredDb]);
            if ($row = $stSlot->fetch(PDO::FETCH_ASSOC)) {
                $results[] = [
                    'classe_id' => $idclasse,
                    'heured' => $heured,
                    'created' => false,
                    'reason' => 'slot_taken',
                    'id' => (int)$row['id'],
                    'classe_id_taken' => (int)$row['idclasse'],
                ];
                continue;
            }

            // ✅ doublon même classe
            $stExists->execute([':c' => $idclasse, ':d' => $date, ':h' => $heuredDb]);
            if ($row = $stExists->fetch(PDO::FETCH_ASSOC)) {
                $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => false, 'reason' => 'exists', 'id' => (int)$row['id']];
                continue;
            }

            // ✅ transaction par séance (un échec n'annule pas tout)
            $pdo->beginTransaction();
            try {
                $stInsert->execute([':c' => $idclasse, ':d' => $date, ':h' => $heuredDb]);
                $seanceId = (int)$pdo->lastInsertId();

                $stInsertPresence->execute([':sid' => $seanceId, ':cid' => $idclasse]);

                $pdo->commit();

                $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => true, 'id' => $seanceId];
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => false, 'reason' => 'db_error', 'error' => $e->getMessage()];
            }
        }

        echo json_encode(['ok' => true, 'results' => $results, 'refresh' => true], JSON_UNESCAPED_UNICODE);
    }

    public function updateObservation(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) $data = [];

        $idseance = (int)($data['idseance'] ?? 0);
        $observation = trim((string)($data['observation'] ?? ''));

        if ($idseance <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'idseance invalide'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (mb_strlen($observation) > 2000) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Observation trop longue (max 2000 caractères)'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo = Database::pdo();

        $st = $pdo->prepare("SELECT id FROM seances WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $st->execute([':id' => $idseance]);
        if (!$st->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Séance introuvable'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $st = $pdo->prepare("UPDATE seances SET observation = :obs WHERE id = :id");
        $st->execute([
            ':obs' => ($observation === '' ? null : $observation),
            ':id'  => $idseance,
        ]);

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    public function detachPartie(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Csrf::check((string)$token)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'CSRF token mismatch'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) $data = [];

        $idseance = (int)($data['idseance'] ?? 0);
        $idpartie = (int)($data['idpartie'] ?? 0);

        if ($idseance <= 0 || $idpartie <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Paramètres invalides'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $pdo = Database::pdo();

        $st = $pdo->prepare("SELECT id FROM seances WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $st->execute([':id' => $idseance]);
        if (!$st->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Séance introuvable'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $st = $pdo->prepare("DELETE FROM seances_parties WHERE idseance = :s AND idpartie = :p");
        $st->execute([':s' => $idseance, ':p' => $idpartie]);

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }
}
