<?php
declare(strict_types=1);

namespace App\Repositories\Admin;

use App\Core\Database;

class AdminDashboardRepository
{
    public function getUserRamadanFlag(int $userId): int
    {
        $st = Database::pdo()->prepare("SELECT ramadan FROM users WHERE id=:id LIMIT 1");
        $st->execute(['id' => $userId]);
        $row = $st->fetch();
        return (int)($row['ramadan'] ?? 0);
    }

    public function countClassesForAnnee(int $idannee): int
    {
        $st = Database::pdo()->prepare("SELECT COUNT(*) c FROM classes WHERE idannee=:a AND deleted_at IS NULL");
        $st->execute(['a' => $idannee]);
        return (int)$st->fetchColumn();
    }

    public function countElevesActifsForAnnee(int $idannee): int
    {
        $sql = "SELECT COUNT(DISTINCT ec.ideleve) c
                FROM eleves_classes ec
                JOIN classes c ON c.id = ec.idclasse
                WHERE c.idannee=:a AND c.deleted_at IS NULL AND ec.depart=0";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return (int)$st->fetchColumn();
    }

    public function todayStats(int $idannee, string $dateYmd): array
    {
        $sqlSeances = "SELECT COUNT(*) c
                       FROM seances s
                       JOIN classes c ON c.id = s.idclasse
                       WHERE c.idannee=:a AND c.deleted_at IS NULL
                         AND s.deleted_at IS NULL AND s.date=:d";
        $st = Database::pdo()->prepare($sqlSeances);
        $st->execute(['a' => $idannee, 'd' => $dateYmd]);
        $seances = (int)$st->fetchColumn();

        $sqlAbs = "SELECT COUNT(*) c
                   FROM seances_eleves se
                   JOIN seances s ON s.id = se.idseance
                   JOIN classes c ON c.id = s.idclasse
                   WHERE c.idannee=:a AND c.deleted_at IS NULL
                     AND s.deleted_at IS NULL AND s.date=:d
                     AND se.absent=1";
        $st2 = Database::pdo()->prepare($sqlAbs);
        $st2->execute(['a' => $idannee, 'd' => $dateYmd]);
        $absences = (int)$st2->fetchColumn();

        return ['seances' => $seances, 'absences' => $absences];
    }
}