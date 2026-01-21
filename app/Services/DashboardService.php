<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class DashboardService
{
    public function currentAnnee(): ?array
    {
        $sql = "SELECT id, annee FROM annees ORDER BY id DESC LIMIT 1";
        $st = Database::pdo()->query($sql);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function countClassesForAnnee(int $idannee): int
    {
        $sql = "SELECT COUNT(*) AS c FROM classes WHERE idannee = :a AND deleted_at IS NULL";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function countElevesActifsForAnnee(int $idannee): int
    {
        // élèves distincts dans les classes de l'année, avec depart=0
        $sql = "
            SELECT COUNT(DISTINCT ec.ideleve) AS c
            FROM eleves_classes ec
            JOIN classes c ON c.id = ec.idclasse
            JOIN eleves e ON e.id = ec.ideleve
            WHERE c.idannee = :a
              AND c.deleted_at IS NULL
              AND e.deleted_at IS NULL
              AND ec.depart = 0
        ";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function classesForAnnee(int $idannee): array
    {
        $sql = "SELECT id, classe FROM classes WHERE idannee = :a AND deleted_at IS NULL ORDER BY classe";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return $st->fetchAll();
    }

    /**
     * Emplois du temps regroupés par classe, puis par jour (n), puis par heure.
     * Hypothèse: n = 1..6 => Lundi..Samedi
     */
    public function timetableForAnnee(int $idannee): array
    {
        $sql = "
            SELECT edt.idclasse, c.classe, edt.n, edt.heure
            FROM emplois_du_temps edt
            JOIN classes c ON c.id = edt.idclasse
            WHERE c.idannee = :a AND c.deleted_at IS NULL
            ORDER BY c.classe, edt.n, edt.heure
        ";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        $rows = $st->fetchAll();

        // Structure: [idclasse => ['classe'=>..., 'slots'=>[n=>[heure=>true]]]]
        $out = [];
        foreach ($rows as $r) {
            $cid = (int)$r['idclasse'];
            $n = (int)$r['n'];
            $h = (string)$r['heure'];

            if (!isset($out[$cid])) {
                $out[$cid] = [
                    'classe' => $r['classe'],
                    'slots'  => []
                ];
            }
            $out[$cid]['slots'][$n][$h] = true;
        }

        return $out;
    }

    /**
     * Dernière partie réalisée par classe (sur séances ayant des parties).
     * On prend la dernière séance (date + heured) et une partie associée (la plus récente par id).
     */
    public function lastPartieByClasseForAnnee(int $idannee): array
    {
        $sql = "
            SELECT
                c.id AS idclasse,
                c.classe,
                s.date,
                s.heured,
                p.partie,
                p.num,
                m.module,
                m.abrev
            FROM classes c
            LEFT JOIN (
                SELECT s1.*
                FROM seances s1
                JOIN (
                    SELECT idclasse, MAX(CONCAT(date, ' ', heured)) AS max_dt
                    FROM seances
                    WHERE deleted_at IS NULL
                    GROUP BY idclasse
                ) x ON x.idclasse = s1.idclasse AND CONCAT(s1.date, ' ', s1.heured) = x.max_dt
                WHERE s1.deleted_at IS NULL
            ) s ON s.idclasse = c.id
            LEFT JOIN seances_parties sp ON sp.idseance = s.id
            LEFT JOIN parties p ON p.id = sp.idpartie
            LEFT JOIN modules m ON m.id = p.idmodule
            WHERE c.idannee = :a AND c.deleted_at IS NULL
            ORDER BY c.classe, s.date DESC, s.heured DESC, p.id DESC
        ";

        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        $rows = $st->fetchAll();

        // On veut 1 ligne "représentative" par classe : la première rencontrée suffit grâce au ORDER BY.
        $out = [];
        foreach ($rows as $r) {
            $cid = (int)$r['idclasse'];
            if (isset($out[$cid])) continue;

            $out[$cid] = [
                'classe' => $r['classe'],
                'date'   => $r['date'] ?? null,
                'heured' => $r['heured'] ?? null,
                'module' => $r['module'] ?? null,
                'abrev'  => $r['abrev'] ?? null,
                'partie' => $r['partie'] ?? null,
                'num'    => $r['num'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Détermine si le samedi doit être affiché : s'il existe au moins une entrée n=6.
     */
    public function hasSaturdayForAnnee(int $idannee): bool
    {
        $sql = "
            SELECT 1
            FROM emplois_du_temps edt
            JOIN classes c ON c.id = edt.idclasse
            WHERE c.idannee = :a AND edt.n = 6
            LIMIT 1
        ";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return (bool)$st->fetchColumn();
    }

    public function globalTimetableForAnnee(int $idannee): array
    {
        $sql = "
            SELECT
                edt.n,
                TIME_FORMAT(edt.heure, '%H:%i') AS heure,
                c.id AS idclasse,
                c.classe
            FROM emplois_du_temps edt
            JOIN classes c ON c.id = edt.idclasse
            WHERE c.idannee = :a AND c.deleted_at IS NULL
            ORDER BY edt.n, edt.heure
        ";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        $rows = $st->fetchAll();

        $grid = [];
        $hoursSet = [];
        $classes = [];

        foreach ($rows as $r) {
            $n = (int)$r['n'];
            $h = (string)$r['heure']; // déjà HH:MM

            $grid[$n][$h] = [
                'idclasse' => (int)$r['idclasse'],
                'classe'   => (string)$r['classe'],
            ];

            $hoursSet[$h] = true;
            $classes[(int)$r['idclasse']] = (string)$r['classe'];
        }

        $hours = array_keys($hoursSet);
        sort($hours);

        // classes: [idclasse => "classe"]
        // On trie par nom de classe (stable et logique pour toi)
        asort($classes, SORT_NATURAL | SORT_FLAG_CASE);

        $classStyle = [];
        $golden = 137.508; // Golden angle
        $i = 0;

        foreach ($classes as $idclasse => $name) {
            $hue = fmod(($i * $golden), 360.0);

            // Alternance légère pour éviter les teintes "trop voisines" visuellement
            $sat   = ($i % 2 === 0) ? 60 : 52;
            $light = ($i % 3 === 0) ? 82 : 78;   // pastel mais un peu plus contrasté

            $bg = sprintf("hsl(%.3f %d%% %d%%)", $hue, $sat, $light);

            $classStyle[(int)$idclasse] = [
                'bg' => $bg,
                'text' => '#111', // fonds clairs => texte sombre (lisible)
            ];

            $i++;
        }

        return [
            'grid' => $grid,
            'hours' => $hours,
            'classStyle' => $classStyle,
            'classes' => $classes,
        ];
    }

    /**
     * Couleur stable et “distincte” : on dérive la teinte du hash de l'id.
     * Retourne un CSS color: ex "hsl(210 75% 45%)"
     */
    private function colorForClasse(int $idclasse): string
    {
        // Hash simple -> hue 0..359
        $hue = (crc32((string)$idclasse) % 360);

        // Saturation/Luminosité fixes (bon contraste en badge)
        $sat = 75;
        $light = 42;

        return "hsl($hue $sat% $light%)";
    }

    private function bgColorForClasse(int $idclasse): string
    {
        // Hue stable 0..359
        $hue = crc32((string)$idclasse) % 360;

        // Pastel: saturation modérée, luminosité élevée
        $sat = 55;   // 40-60 donne un rendu doux
        $light = 82; // 78-88 = pastel

        return "hsl($hue $sat% $light%)";
    }

    private function textColorForBgHsl(string $hsl): string
    {
        // Parse "hsl(H S% L%)"
        if (!preg_match('/hsl\((\d+)\s+(\d+)%\s+(\d+)%\)/', $hsl, $m)) {
            return '#111';
        }
        $h = (int)$m[1];
        $s = (int)$m[2] / 100;
        $l = (int)$m[3] / 100;

        // Convert HSL -> RGB (approx standard)
        $c = (1 - abs(2*$l - 1)) * $s;
        $x = $c * (1 - abs((($h / 60) % 2) - 1));
        $m0 = $l - $c/2;

        [$r1,$g1,$b1] = match (true) {
            $h < 60  => [$c,$x,0],
            $h < 120 => [$x,$c,0],
            $h < 180 => [0,$c,$x],
            $h < 240 => [0,$x,$c],
            $h < 300 => [$x,0,$c],
            default  => [$c,0,$x],
        };

        $r = ($r1 + $m0);
        $g = ($g1 + $m0);
        $b = ($b1 + $m0);

        // Relative luminance (approx)
        $lum = 0.2126*$r + 0.7152*$g + 0.0722*$b;

        // Si fond très clair => texte sombre, sinon texte blanc
        return ($lum > 0.72) ? '#111' : '#fff';
    }



    public function hasSaturdayGlobal(int $idannee): bool
    {
        $sql = "
            SELECT 1
            FROM emplois_du_temps edt
            JOIN classes c ON c.id = edt.idclasse
            WHERE c.idannee = :a AND edt.n = 6
            LIMIT 1
        ";
        $st = Database::pdo()->prepare($sql);
        $st->execute(['a' => $idannee]);
        return (bool)$st->fetchColumn();
    }
}
