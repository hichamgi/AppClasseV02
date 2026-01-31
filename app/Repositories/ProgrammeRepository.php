<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProgrammeRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<int, array{id:int, module:?string, abrev:?string, sem:?int}> */
    public function listModules(): array
    {
        $stmt = $this->db->query("
            SELECT id, module, abrev, sem
            FROM modules
            ORDER BY id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array{
     *   partie_id:int, module_id:int, niv:int, partie:string, num:string, module_abrev:?string, module_lib:?string, devoir:int, label:string
     * }>
     */
    public function listParties(?int $moduleId = null): array
    {
        $sql = "
            SELECT
                p.id       AS partie_id,
                p.idmodule AS module_id,
                p.niv      AS niv,
                p.partie   AS partie,
                p.num      AS num,
                p.devoir   AS devoir,
                m.abrev    AS module_abrev,
                m.module   AS module_lib
            FROM parties p
            LEFT JOIN modules m ON m.id = p.idmodule
            WHERE 1=1
        ";
        $params = [];

        if ($moduleId !== null) {
            $sql .= " AND p.idmodule = :mid";
            $params[':mid'] = $moduleId;
        }

        // 🔒 règle métier forte
        $sql .= " ORDER BY p.idmodule ASC, p.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Label lisible (sans casser l'ordre)
        $oldModKey = null;
        foreach ($rows as &$r) {
            $modKey = trim((string)($r['module_abrev'] ?? '')) . ' : ' . trim((string)($r['module_lib'] ?? ''));
            $modKey = trim($modKey, " :");

            $num = trim((string)($r['num'] ?? ''));
            $pos = strpos($num, ':');
            if ($pos !== false) {
                $num = ltrim(substr($num, $pos + 1));
            }

            $par = trim((string)($r['partie'] ?? ''));
            $niv = (int)($r['niv'] ?? 0);

            if ($niv === 1 && $modKey !== $oldModKey) {
                $r['label'] = trim($modKey) . ' | ' . $par;
                $oldModKey = $modKey;
            } else {
                $r['label'] = trim(($num !== '' ? $num . '. ' : '') . $par);
            }
        }
        unset($r);

        // normaliser types
        foreach ($rows as &$r) {
            $r['partie_id'] = (int)$r['partie_id'];
            $r['module_id'] = (int)$r['module_id'];
            $r['niv']       = (int)$r['niv'];
            $r['devoir']    = (int)$r['devoir'];
        }
        unset($r);

        return $rows;
    }
}
