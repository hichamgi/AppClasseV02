<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AnneeRepository
{
    public function __construct(private PDO $db) {}

    public function getCurrentId(): int
    {
        $stmt = $this->db->query("
            SELECT id
            FROM annees
            ORDER BY id DESC
            LIMIT 1
        ");

        $id = $stmt->fetchColumn();
        if (!$id) {
            throw new \RuntimeException("Aucune année scolaire trouvée dans la table annees");
        }
        return (int)$id;
    }

    /** @return array<int, array{id:int, annee:string}> */
    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT id, annee
            FROM annees
            ORDER BY id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}