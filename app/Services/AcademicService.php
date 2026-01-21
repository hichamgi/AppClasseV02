<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class AcademicService
{
    // Exemples de requêtes utiles (à enrichir)
    public function eleveDossiers(int $ideleve): array
    {
        $sql = "SELECT ds.*, a.annee
                FROM dossiers_scolaires ds
                JOIN annees a ON a.id = ds.idannee
                WHERE ds.ideleve = :id
                ORDER BY a.annee DESC";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['id' => $ideleve]);
        return $st->fetchAll();
    }
}