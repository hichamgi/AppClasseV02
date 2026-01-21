<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Eleve extends Model
{
    protected static string $table = 'eleves';

    public static function search(string $q, int $limit = 50): array
    {
        $q = trim($q);
        if ($q === '') return [];

        // recherche simple: nom/prenom/SGS
        return self::where(
            "(deleted_at IS NULL) AND (nom LIKE :q OR prenom LIKE :q OR numerosgs LIKE :q)",
            ['q' => "%$q%"],
            $limit
        );
    }
}
