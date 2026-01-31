<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ClasseRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<int, array{id:int, classe:string}> */
    public function findByAnnee(int $anneeId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, classe
            FROM classes
            WHERE deleted_at IS NULL
              AND idannee = :annee
            ORDER BY classe ASC
        ");
        $stmt->execute([':annee' => $anneeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{id:int, classe:string, idannee:int}|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, classe, idannee
            FROM classes
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
