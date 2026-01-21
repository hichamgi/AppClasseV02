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
}
