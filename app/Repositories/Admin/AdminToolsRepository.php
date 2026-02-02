<?php
declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\Database;

class AdminToolsRepository
{
    public function listClassesForAnnee(int $idannee): array
    {
        $st = Database::pdo()->prepare("SELECT * FROM classes WHERE idannee=:a AND deleted_at IS NULL ORDER BY classe");
        $st->execute(['a' => $idannee]);
        return $st->fetchAll() ?: [];
    }

    public function updateClasseName(int $idclasse, string $name): void
    {
        $st = Database::pdo()->prepare("UPDATE classes SET classe=:c WHERE id=:id LIMIT 1");
        $st->execute(['c' => $name, 'id' => $idclasse]);
    }

    public function createClasse(int $idannee, string $name): void
    {
        $st = Database::pdo()->prepare("INSERT INTO classes (classe, idannee) VALUES (:c,:a)");
        $st->execute(['c' => $name, 'a' => $idannee]);
    }

    public function setRamadanFlag(int $userId, int $val): void
    {
        $val = $val === 1 ? 1 : 0;
        $st = Database::pdo()->prepare("UPDATE users SET ramadan=:r WHERE id=:id LIMIT 1");
        $st->execute(['r' => $val, 'id' => $userId]);
    }

    public function getUserPasswordHash(int $userId): string
    {
        $st = Database::pdo()->prepare("SELECT password FROM users WHERE id=:id LIMIT 1");
        $st->execute(['id' => $userId]);
        $row = $st->fetch();
        return (string)($row['password'] ?? '');
    }

    public function updatePasswordHash(int $userId, string $hash): void
    {
        $st = Database::pdo()->prepare("UPDATE users SET password=:p WHERE id=:id LIMIT 1");
        $st->execute(['p' => $hash, 'id' => $userId]);
    }

    public function listElevesByClasse(int $idclasse): array
    {
        $sql = "SELECT e.id, e.nom, e.prenom, e.nomar, e.prenomar,
                    ec.numero, ec.classementsgs, ec.depart
                FROM eleves_classes ec
                JOIN eleves e ON e.id = ec.ideleve
                WHERE ec.idclasse=:c
                ORDER BY ec.numero ASC, e.nom ASC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['c' => $idclasse]);
        return $st->fetchAll() ?: [];
    }

    public function getRamadanFlag(int $userId): int
    {
        $st = \App\Core\Database::pdo()->prepare("SELECT ramadan FROM users WHERE id=:id LIMIT 1");
        $st->execute(['id' => $userId]);
        $row = $st->fetch();
        return (int)($row['ramadan'] ?? 0);
    }

    public function getEdtForClasse(int $idclasse): array
    {
        // ⚠️ Ici on doit utiliser TA table EDT réelle.
        // Exemple générique si tu as une table `emploi_temps` avec json `data`.
        $st = Database::pdo()->prepare("SELECT data FROM emploi_temps WHERE idclasse=:c LIMIT 1");
        $st->execute(['c' => $idclasse]);
        $row = $st->fetch();

        if (!$row || empty($row['data'])) return [];

        $decoded = json_decode((string)$row['data'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public function saveEdtForClasse(int $idclasse, array $edt): void
    {
        $json = json_encode($edt, JSON_UNESCAPED_UNICODE);

        // UPSERT portable (SELECT puis UPDATE/INSERT)
        $st = Database::pdo()->prepare("SELECT id FROM emploi_temps WHERE idclasse=:c LIMIT 1");
        $st->execute(['c' => $idclasse]);
        $id = (int)($st->fetchColumn() ?: 0);

        if ($id > 0) {
            $up = Database::pdo()->prepare("UPDATE emploi_temps SET data=:d WHERE id=:id LIMIT 1");
            $up->execute(['d' => $json, 'id' => $id]);
            return;
        }

        $ins = Database::pdo()->prepare("INSERT INTO emploi_temps (idclasse, data) VALUES (:c,:d)");
        $ins->execute(['c' => $idclasse, 'd' => $json]);
    }

    public function listEdtForAnnee(int $idannee): array
    {
        $sql = "SELECT edt.idclasse, edt.n, edt.heure
                FROM emplois_du_temps edt
                JOIN classes c ON c.id = edt.idclasse
                WHERE c.idannee = :a
                AND c.deleted_at IS NULL
                ORDER BY edt.n ASC, edt.heure ASC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return $st->fetchAll() ?: [];
    }

    /**
     * $grid: [n][heure] => idclasse  (heure format "HH:MM:SS" ou "HH:MM")
     */
    public function saveEdtGrid(int $idannee, array $grid): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        // Supprimer EDT de l'année
        $del = $pdo->prepare(
            "DELETE edt
            FROM emplois_du_temps edt
            JOIN classes c ON c.id = edt.idclasse
            WHERE c.idannee = :a"
        );
        $del->execute(['a' => $idannee]);

        $ins = $pdo->prepare(
            "INSERT INTO emplois_du_temps (idclasse, n, heure)
            VALUES (:idclasse, :n, :heure)"
        );

        foreach ($grid as $n => $hours) {
            $n = (int)$n;
            if ($n <= 0 || !is_array($hours)) continue;

            foreach ($hours as $heure => $idclasse) {
                $idclasse = (int)$idclasse;
                $heure = (string)$heure;
                if ($idclasse <= 0) continue;

                if (preg_match('/^\d{2}:\d{2}$/', $heure)) $heure .= ':00';

                $ins->execute([
                    'idclasse' => $idclasse,
                    'n' => $n,
                    'heure' => $heure,
                ]);
            }
        }

        $pdo->commit();
    }


}