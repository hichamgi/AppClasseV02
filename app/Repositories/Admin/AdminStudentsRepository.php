<?php
declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\Database;

class AdminStudentsRepository
{
    public function findClasseIdByCode(int $idannee, string $code): int
    {
        $st = Database::pdo()->prepare(
            "SELECT id
             FROM classes
             WHERE idannee=:a
               AND deleted_at IS NULL
               AND classe=:c
             LIMIT 1"
        );
        $st->execute(['a' => $idannee, 'c' => $code]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function getUsedNumeros(int $idclasse): array
    {
        $st = Database::pdo()->prepare(
            "SELECT numero
             FROM eleves_classes
             WHERE idclasse=:c"
        );
        $st->execute(['c' => $idclasse]);

        $used = [];
        foreach (($st->fetchAll() ?: []) as $r) {
            $n = (int)($r['numero'] ?? 0);
            if ($n > 0) $used[$n] = true;
        }
        return $used;
    }

    public function findEleveIdByNumeroSgs(?string $numerosgs): int
    {
        $numerosgs = trim((string)$numerosgs);
        if ($numerosgs === '') return 0;

        $st = Database::pdo()->prepare("SELECT id FROM eleves WHERE numerosgs=:n LIMIT 1");
        $st->execute(['n' => $numerosgs]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function insertEleveMinimal(string $numerosgs, string $nom, ?string $datenaiss, string $sexe): int
    {
        $sql = "INSERT INTO eleves (numerosgs, nom, datenaiss, sexe)
                VALUES (:numerosgs, :nom, :datenaiss, :sexe)";
        $st = Database::pdo()->prepare($sql);
        $st->execute([
            'numerosgs' => $numerosgs !== '' ? $numerosgs : null,
            'nom' => $nom !== '' ? $nom : null,
            'datenaiss' => $datenaiss !== '' ? $datenaiss : null,
            'sexe' => ($sexe === 'F') ? 'F' : 'M',
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public function insertEleveFull(array $e): int
    {
        $sql = "INSERT INTO eleves (numerosgs, nom, prenom, nomar, prenomar, datenaiss, sexe, observation)
                VALUES (:numerosgs, :nom, :prenom, :nomar, :prenomar, :datenaiss, :sexe, :observation)";
        $st = Database::pdo()->prepare($sql);
        $st->execute([
            'numerosgs' => ($e['numerosgs'] ?? '') !== '' ? (string)$e['numerosgs'] : null,
            'nom' => ($e['nom'] ?? '') !== '' ? (string)$e['nom'] : null,
            'prenom' => ($e['prenom'] ?? '') !== '' ? (string)$e['prenom'] : null,
            'nomar' => ($e['nomar'] ?? '') !== '' ? (string)$e['nomar'] : null,
            'prenomar' => ($e['prenomar'] ?? '') !== '' ? (string)$e['prenomar'] : null,
            'datenaiss' => ($e['datenaiss'] ?? '') !== '' ? (string)$e['datenaiss'] : null,
            'sexe' => ((string)($e['sexe'] ?? 'M') === 'F') ? 'F' : 'M',
            'observation' => ($e['observation'] ?? '') !== '' ? (string)$e['observation'] : null,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public function eleveAlreadyInClasse(int $ideleve, int $idclasse): bool
    {
        $st = Database::pdo()->prepare(
            "SELECT 1 FROM eleves_classes WHERE ideleve=:e AND idclasse=:c LIMIT 1"
        );
        $st->execute(['e' => $ideleve, 'c' => $idclasse]);
        return (bool)$st->fetchColumn();
    }

    public function attachEleveToClasse(int $ideleve, int $idclasse, int $numero): void
    {
        $sql = "INSERT INTO eleves_classes (ideleve, idclasse, numero, classementsgs, depart)
                VALUES (:e, :c, :n, 0, 0)";
        $st = Database::pdo()->prepare($sql);
        $st->execute([
            'e' => $ideleve,
            'c' => $idclasse,
            'n' => $numero,
        ]);
    }
}
