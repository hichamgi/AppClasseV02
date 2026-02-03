<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Database;
use App\Repositories\Admin\AdminStudentsRepository;

class AdminStudentsService
{
    public function __construct(
        private AdminStudentsRepository $repo = new AdminStudentsRepository()
    ) {}

    public function normalizeClasse(string $s): string
    {
        $s = strtoupper(trim($s));
        $s = str_replace(['-', ' '], '', $s); // TCT-1 -> TCT1
        return $s;
    }

    private function normalizeDate(?string $s): string
    {
        $s = trim((string)$s);
        if ($s === '') return '';

        // Accepter "YYYY-MM-DD" (recommandé)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;

        // Accepter "DD/MM/YYYY" -> "YYYY-MM-DD"
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }

        // Sinon on laisse vide (évite d’insérer une date invalide)
        return '';
    }

    private function nextFreeNumero(array &$used): int
    {
        $i = 1;
        while (isset($used[$i])) $i++;
        $used[$i] = true;
        return $i;
    }

    /**
     * Import multi-lignes.
     * Format ligne: numerosgs;nomfr;datenaiss;sexe;classe
     * séparateurs acceptés: ; ou tab ou ,
     */
    public function importList(int $idannee, string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return ['ok' => false, 'error' => 'EMPTY'];

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $inserted = 0;
        $attached = 0;
        $skipped = 0;
        $errors = [];

        Database::pdo()->beginTransaction();

        try {
            $usedByClasse = [];

            foreach ($lines as $idx => $line) {
                $line = trim($line);
                if ($line === '') continue;

                $parts = preg_split("/[;\t,]/", $line);
                $parts = array_map('trim', $parts ?: []);

                if (count($parts) < 5) {
                    $errors[] = "Ligne ".($idx+1).": format invalide";
                    $skipped++;
                    continue;
                }

                [$numerosgs, $nomfr, $dob, $sexe, $classeRaw] = $parts;

                $classe = $this->normalizeClasse($classeRaw);
                $idclasse = $this->repo->findClasseIdByCode($idannee, $classe);
                if ($idclasse <= 0) {
                    $errors[] = "Ligne ".($idx+1).": classe introuvable ($classeRaw → $classe)";
                    $skipped++;
                    continue;
                }

                if (!isset($usedByClasse[$idclasse])) {
                    $usedByClasse[$idclasse] = $this->repo->getUsedNumeros($idclasse);
                }

                $sexe = strtoupper(trim($sexe)) === 'F' ? 'F' : 'M';
                $dob = $this->normalizeDate($dob);

                // élève existe ?
                $ideleve = $this->repo->findEleveIdByNumeroSgs($numerosgs);

                if ($ideleve <= 0) {
                    // Import liste = minimal : nom/prenom/nomar/prenomar gérés ailleurs
                    $ideleve = $this->repo->insertEleveMinimal($numerosgs, $nomfr, $dob, $sexe);
                    $inserted++;
                }

                // Déjà affecté à cette classe ?
                if ($this->repo->eleveAlreadyInClasse($ideleve, $idclasse)) {
                    $skipped++;
                    continue;
                }

                $numero = $this->nextFreeNumero($usedByClasse[$idclasse]);
                $this->repo->attachEleveToClasse($ideleve, $idclasse, $numero);
                $attached++;
            }

            Database::pdo()->commit();
        } catch (\Throwable $e) {
            Database::pdo()->rollBack();
            return ['ok' => false, 'error' => 'EXCEPTION', 'details' => $e->getMessage()];
        }

        return [
            'ok' => true,
            'inserted' => $inserted,   // nouveaux eleves
            'attached' => $attached,   // affectations classe
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Ajout unitaire (modal) = tous champs eleves + affectation classe
     */
    public function addOneFull(int $idannee, array $post): array
    {
        $classeRaw = trim((string)($post['classe'] ?? ''));
        $classe = $this->normalizeClasse($classeRaw);
        $idclasse = $this->repo->findClasseIdByCode($idannee, $classe);
        if ($idclasse <= 0) return ['ok' => false, 'error' => 'CLASSE_NOT_FOUND'];

        $numerosgs = trim((string)($post['numerosgs'] ?? ''));
        $nom = trim((string)($post['nom'] ?? ''));
        if ($nom === '') return ['ok' => false, 'error' => 'NOM_REQUIRED'];

        $payload = [
            'numerosgs' => $numerosgs,
            'nom' => $nom,
            'prenom' => trim((string)($post['prenom'] ?? '')),
            'nomar' => trim((string)($post['nomar'] ?? '')),
            'prenomar' => trim((string)($post['prenomar'] ?? '')),
            'datenaiss' => $this->normalizeDate((string)($post['datenaiss'] ?? '')),
            'sexe' => strtoupper(trim((string)($post['sexe'] ?? 'M'))) === 'F' ? 'F' : 'M',
            'observation' => trim((string)($post['observation'] ?? '')),
        ];

        Database::pdo()->beginTransaction();

        try {
            // éviter doublon numerosgs
            $ideleve = $this->repo->findEleveIdByNumeroSgs($payload['numerosgs']);
            if ($ideleve <= 0) {
                $ideleve = $this->repo->insertEleveFull($payload);
            }

            if ($this->repo->eleveAlreadyInClasse($ideleve, $idclasse)) {
                Database::pdo()->commit();
                return ['ok' => true];
            }

            $used = $this->repo->getUsedNumeros($idclasse);
            $numero = $this->nextFreeNumero($used);

            $this->repo->attachEleveToClasse($ideleve, $idclasse, $numero);

            Database::pdo()->commit();
            return ['ok' => true];

        } catch (\Throwable $e) {
            Database::pdo()->rollBack();
            return ['ok' => false, 'error' => 'EXCEPTION', 'details' => $e->getMessage()];
        }
    }
}
