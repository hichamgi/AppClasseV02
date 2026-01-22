<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;

class SeancesApiController
{
    public function create(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $idclasse = (int)($_POST['idclasse'] ?? 0);
        $date = trim((string)($_POST['date'] ?? ''));
        $heured = trim((string)($_POST['heured'] ?? ''));
        $obs = trim((string)($_POST['observation'] ?? ''));

        if ($idclasse <= 0 || $date === '' || $heured === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Champs requis manquants']);
            return;
        }

        $pdo = Database::pdo();

        // Empêcher doublon séance (unique: idclasse, date, heured)
        $st = $pdo->prepare("SELECT id FROM seances WHERE idclasse=:c AND date=:d AND heured=:h AND deleted_at IS NULL LIMIT 1");
        $st->execute(['c' => $idclasse, 'd' => $date, 'h' => $heured . ':00']);
        if ($st->fetch()) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'Une séance existe déjà pour cette classe à cette date/heure']);
            return;
        }

        $st = $pdo->prepare("
            INSERT INTO seances (idclasse, date, heured, observation)
            VALUES (:c, :d, :hd, :obs)
        ");
        $st->execute([
            'c' => $idclasse,
            'd' => $date,
            'hd' => $heured . ':00',
            'obs' => $obs !== '' ? $obs : null,
        ]);

        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'refresh' => true]);
    }

    public function createBulk(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        $date = trim((string)($payload['date'] ?? ''));
        $sessions = $payload['sessions'] ?? [];

        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Date invalide']);
            return;
        }
        if (!is_array($sessions) || count($sessions) === 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Sessions manquantes']);
            return;
        }

        $pdo = Database::pdo();

        $results = [];

        foreach ($sessions as $s) {
            $idclasse = (int)($s['classe_id'] ?? 0);
            $heured = trim((string)($s['heured'] ?? ''));

            if ($idclasse <= 0 || !preg_match('/^\d{2}:\d{2}$/', $heured)) {
                $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => false, 'reason' => 'invalid'];
                continue;
            }

            $heuredDb = $heured . ':00';

            // Empêcher doublon
            $st = $pdo->prepare("SELECT id FROM seances WHERE idclasse=:c AND date=:d AND heured=:h AND deleted_at IS NULL LIMIT 1");
            $st->execute(['c' => $idclasse, 'd' => $date, 'h' => $heuredDb]);

            if ($st->fetch()) {
                $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => false, 'reason' => 'exists'];
                continue;
            }

            $st = $pdo->prepare("INSERT INTO seances (idclasse, date, heured, observation) VALUES (:c, :d, :h, NULL)");
            $st->execute(['c' => $idclasse, 'd' => $date, 'h' => $heuredDb]);

            $results[] = ['classe_id' => $idclasse, 'heured' => $heured, 'created' => true, 'id' => (int)$pdo->lastInsertId()];
        }

        echo json_encode(['ok' => true, 'results' => $results, 'refresh' => true]);
    }

    public function updateObservation(): void
    {
        // JSON response
        header('Content-Type: application/json; charset=utf-8');

        // CSRF (tu as déjà requireCsrf() dans Controller,
        // ici on est dans un controller API simple -> si tu veux CSRF, fais-le dans Core middleware ou recode ici.
        // Si ton CSRF est déjà vérifié ailleurs, ignore.
        // Sinon, tu peux vérifier via Csrf::check sur HTTP_X_CSRF_TOKEN (comme dans Controller).

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) $data = [];

        $idseance = (int)($data['idseance'] ?? 0);
        $observation = trim((string)($data['observation'] ?? ''));

        if ($idseance <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'idseance invalide']);
            return;
        }

        // Option: limite raisonnable
        if (mb_strlen($observation) > 2000) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Observation trop longue (max 2000 caractères)']);
            return;
        }

        $pdo = \App\Core\Database::pdo();

        // check seance exists and not deleted
        $st = $pdo->prepare("SELECT id FROM seances WHERE id=:id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $idseance]);
        if (!$st->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Séance introuvable']);
            return;
        }

        $st = $pdo->prepare("UPDATE seances SET observation = :obs WHERE id = :id");
        $st->execute([
            'obs' => ($observation === '' ? null : $observation),
            'id'  => $idseance,
        ]);

        echo json_encode(['ok' => true]);
    }

    public function detachPartie(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // CSRF
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!\App\Core\Csrf::check((string)$token)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'error' => 'CSRF token mismatch']);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) $data = [];

        $idseance = (int)($data['idseance'] ?? 0);
        $idpartie = (int)($data['idpartie'] ?? 0);

        if ($idseance <= 0 || $idpartie <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Paramètres invalides']);
            return;
        }

        $pdo = \App\Core\Database::pdo();

        // (Optionnel) vérifier que la séance existe
        $st = $pdo->prepare("SELECT id FROM seances WHERE id=:id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $idseance]);
        if (!$st->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Séance introuvable']);
            return;
        }

        // Supprimer la liaison
        $st = $pdo->prepare("DELETE FROM seances_parties WHERE idseance = :s AND idpartie = :p");
        $st->execute(['s' => $idseance, 'p' => $idpartie]);

        echo json_encode(['ok' => true]);
    }

}
