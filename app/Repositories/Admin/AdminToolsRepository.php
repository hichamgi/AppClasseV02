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

}