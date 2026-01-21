<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class NotificationService
{
    public function latestForClasse(int $idclasse, int $limit = 10): array
    {
        $sql = "SELECT n.*
                FROM notifications n
                JOIN notifications_classes nc ON nc.idnotification = n.id
                WHERE nc.idclasse = :c AND nc.afficher = 1
                ORDER BY n.created_at DESC
                LIMIT :l";
        $st = Database::pdo()->prepare($sql);
        $st->bindValue(':c', $idclasse, \PDO::PARAM_INT);
        $st->bindValue(':l', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}