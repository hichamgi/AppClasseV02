<?php
declare(strict_types=1);

namespace App\Services\OpenAI;

use PDO;

final class AiBatchGenerator
{
    public function __construct(
        private PDO $pdo,
        private OpenAIClient $client,
        private string $model = 'gpt-4.1-mini'
    ) {}

    /**
     * RUN réel (coûteux): upload + batch + download + save DB
     */
    public function run(int $idClasse, int $idAnnee, string $scope, string $period): void
    {
        $instructions = $this->instructions();
        $promptHash = hash('sha256', $instructions);
        $schema = $this->jsonSchema();

        $records = $this->fetchAcademicRecords($idClasse, $idAnnee);
        if (!$records) {
            throw new \RuntimeException("Aucun élève trouvé pour idclasse={$idClasse} idannee={$idAnnee}");
        }

        $modules  = $this->fetchModulesIdGt0();
        $typeExam = $this->fetchTypesExamensIdModuleGt0();

        // 1) build input.jsonl
        $dir = sys_get_temp_dir() . '/appclasse_ai';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $inputPath = $dir . '/input.jsonl';
        $count = $this->buildInputJsonl($idClasse, $idAnnee, $scope, $period, $inputPath, 0);

        echo "OK input.jsonl prêt ({$count} élèves) => {$inputPath}\n";

        // 2) upload file
        $file = $this->client->uploadBatchFile($inputPath);
        $fileId = $file['id'] ?? null;
        if (!$fileId) throw new \RuntimeException("Upload file échoué");

        // 3) create batch
        $batch = $this->client->json('POST', '/batches', [
            "input_file_id" => $fileId,
            "endpoint" => "/v1/responses",
            "completion_window" => "24h"
        ]);
        $batchId = $batch['id'] ?? null;
        if (!$batchId) throw new \RuntimeException("Create batch échoué");

        // 4) poll
        $terminal = ['completed','failed','expired','cancelled'];
        $status = '';
        $b = null;

        do {
            sleep(10);
            $b = $this->client->json('GET', "/batches/{$batchId}");
            $status = (string)($b['status'] ?? '');
            echo "status={$status}\n";
        } while (!in_array($status, $terminal, true));

        if ($status !== 'completed') {
            throw new \RuntimeException("Batch terminé status={$status}");
        }

        $outFileId = $b['output_file_id'] ?? null;
        if (!$outFileId) throw new \RuntimeException("output_file_id manquant");

        // 5) download output
        $outPath = $dir . '/output.jsonl';
        $this->client->downloadFileContent((string)$outFileId, $outPath);

        // 6) parse + save DB
        $results = $this->parseBatchOutputJsonl($outPath);

        $this->saveResults(
            results: $results,
            scope: $scope,
            period: $period,
            model: $this->model,
            promptHash: $promptHash,
            batchId: (string)$batchId
        );

        echo "OK sauvegarde terminée.\n";
    }

    /**
     * DRY-RUN (0 coût):
     * - génère le jsonl sans appeler OpenAI
     * - retourne le nombre de lignes écrites
     */
    public function buildInputJsonl(
        int $idClasse,
        int $idAnnee,
        string $scope,
        string $period,
        string $outputPath,
        int $limit = 0
    ): int {
        $instructions = $this->instructions();
        $schema = $this->jsonSchema();

        $records = $this->fetchAcademicRecords($idClasse, $idAnnee, $limit);
        if (!$records) return 0;

        $modules  = $this->fetchModulesIdGt0();
        $typeExam = $this->fetchTypesExamensIdModuleGt0();

        $dir = dirname($outputPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $fh = fopen($outputPath, 'wb');
        if ($fh === false) throw new \RuntimeException("Impossible de créer {$outputPath}");

        $count = 0;

        foreach ($records as $r) {
            $idDossier = (int)$r['idacademicrecords'];
            $idEleve   = (int)$r['ideleve'];

            $abs = $this->fetchAbsences($idEleve, $idClasse);
            $notebooks = $this->fetchNotebookScores($idDossier);
            $notes = $this->fetchNotes($idDossier);

            $moduleLines = $this->buildModuleLines($modules, $typeExam, $notebooks, $notes);

            $input = $this->buildStudentInput(
                points: (int)$r['points'],
                absAbsent: (int)$abs['absent_count'],
                absTotal: (int)$abs['total_sessions'],
                observation: (string)($r['obs1'] ?? 'NA'),
                moduleLines: $moduleLines,
                scope: $scope,
                period: $period
            );

            $line = [
                "custom_id" => "dossier_" . $idDossier,
                "method" => "POST",
                "url" => "/v1/responses",
                "body" => [
                    "model" => $this->model,
                    "instructions" => $instructions,
                    "input" => $input,
                    "max_output_tokens" => 450,
                    "temperature" => 0.4,
                    "text" => [
                        "format" => [
                            "type" => "json_schema",
                            "name" => "appreciation",
                            "schema" => $schema,
                            "strict" => true
                        ]
                    ]
                ]
            ];

            fwrite($fh, json_encode($line, JSON_UNESCAPED_UNICODE) . "\n");
            $count++;
        }

        fclose($fh);
        return $count;
    }

    /**
     * CHECK (0 coût):
     * - compte dossiers + modules id>0
     * - retourne un sample d'input (1 élève anonymisé)
     */
    public function check(int $idClasse, int $idAnnee, int $limit = 1): array
    {
        $records = $this->fetchAcademicRecords($idClasse, $idAnnee, $limit);
        $modules = $this->fetchModulesIdGt0();

        $recordsCount = (int)$this->scalar("
            SELECT COUNT(*)
            FROM eleves_classes ec
            JOIN classes c ON c.id = ec.idclasse
            JOIN dossiers_scolaires ds ON ds.ideleve = ec.ideleve AND ds.idannee = c.idannee
            WHERE ec.idclasse=:idclasse AND c.idannee=:idannee AND ec.depart=0
        ", [':idclasse'=>$idClasse, ':idannee'=>$idAnnee]);

        $modulesCount = (int)$this->scalar("SELECT COUNT(*) FROM modules WHERE id > 0", []);

        $sample = '';
        if (!empty($records)) {
            $r = $records[0];
            $idDossier = (int)$r['idacademicrecords'];
            $idEleve   = (int)$r['ideleve'];

            $abs = $this->fetchAbsences($idEleve, $idClasse);
            $notebooks = $this->fetchNotebookScores($idDossier);
            $notes = $this->fetchNotes($idDossier);

            $typeExam = $this->fetchTypesExamensIdModuleGt0();
            $moduleLines = $this->buildModuleLines($modules, $typeExam, $notebooks, $notes);

            $sample = $this->buildStudentInput(
                points: (int)$r['points'],
                absAbsent: (int)$abs['absent_count'],
                absTotal: (int)$abs['total_sessions'],
                observation: (string)($r['obs1'] ?? 'NA'),
                moduleLines: $moduleLines,
                scope: 'monthly',
                period: date('Y-m')
            );
        }

        return [
            'records_count' => $recordsCount,
            'modules_count' => $modulesCount,
            'sample' => $sample,
        ];
    }

    // ---------- DB ----------
    private function fetchAcademicRecords(int $idClasse, int $idAnnee, int $limit = 0): array
    {
        $sql = "
            SELECT
              ds.id AS idacademicrecords,
              ds.ideleve,
              ds.points,
              ds.participation,
              ds.obs1,
              ds.obs2
            FROM eleves_classes ec
            JOIN classes c ON c.id = ec.idclasse
            JOIN dossiers_scolaires ds ON ds.ideleve = ec.ideleve AND ds.idannee = c.idannee
            WHERE ec.idclasse = :idclasse
              AND c.idannee = :idannee
              AND ec.depart = 0
            ORDER BY ds.id ASC
        ";
        if ($limit > 0) $sql .= " LIMIT " . (int)$limit;

        $st = $this->pdo->prepare($sql);
        $st->execute([':idclasse'=>$idClasse, ':idannee'=>$idAnnee]);
        return $st->fetchAll() ?: [];
    }

    private function fetchAbsences(int $idEleve, int $idClasse): array
    {
        $stT = $this->pdo->prepare("SELECT COUNT(*) AS n FROM seances WHERE idclasse=:c AND deleted_at IS NULL");
        $stT->execute([':c'=>$idClasse]);
        $totalSessions = (int)($stT->fetch()['n'] ?? 0);

        $stA = $this->pdo->prepare("
            SELECT COUNT(*) AS n
            FROM seances_eleves se
            JOIN seances s ON s.id = se.idseance
            WHERE s.idclasse=:c AND s.deleted_at IS NULL
              AND se.ideleve=:e AND se.absent=1
        ");
        $stA->execute([':c'=>$idClasse, ':e'=>$idEleve]);
        $absentCount = (int)($stA->fetch()['n'] ?? 0);

        return ['total_sessions'=>$totalSessions, 'absent_count'=>$absentCount];
    }

    /** modules id>0 uniquement */
    private function fetchModulesIdGt0(): array
    {
        $rows = $this->pdo->query("SELECT id, module, sem FROM modules WHERE id > 0 ORDER BY id ASC")->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id']] = ['module'=>$r['module'], 'sem'=>$r['sem']];
        }
        return $out;
    }

    /** types_examens filtrés sur idmodule>0 pour ne pas injecter "Divers idmodule=0" */
    private function fetchTypesExamensIdModuleGt0(): array
    {
        $rows = $this->pdo->query("
            SELECT id, code, libellefr, idmodule
            FROM types_examens
            WHERE idmodule > 0
        ")->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id']] = [
                'code' => (string)$r['code'],
                'libellefr' => (string)$r['libellefr'],
                'idmodule' => (int)$r['idmodule'],
            ];
        }
        return $out;
    }

    private function fetchNotebookScores(int $idDossier): array
    {
        $st = $this->pdo->prepare("
            SELECT idmodule, score_cours, score_exercices, score
            FROM notebook_scores
            WHERE idacademicrecords=:d
        ");
        $st->execute([':d'=>$idDossier]);
        $rows = $st->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $r) {
            $idm = (int)$r['idmodule'];
            if ($idm < 1) continue; // IMPORTANT
            $out[$idm] = [
                'cours' => (string)$r['score_cours'],
                'ex'    => (string)$r['score_exercices'],
                'total' => (string)$r['score'],
            ];
        }
        return $out;
    }

    private function fetchNotes(int $idDossier): array
    {
        $st = $this->pdo->prepare("SELECT idtypeexamen, note, absent, triche FROM notes WHERE idacademicrecords=:d");
        $st->execute([':d'=>$idDossier]);
        return $st->fetchAll() ?: [];
    }

    private function scalar(string $sql, array $params): mixed
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        if (!is_array($row)) return null;
        return array_values($row)[0] ?? null;
    }

    private function classifyExam(string $code, string $libellefr): string
    {
        $t = mb_strtolower($code . ' ' . $libellefr);
        if (str_contains($t, 'écrit') || str_contains($t, 'ecrit')) return 'ecrit';
        if (str_contains($t, 'pratique') || str_contains($t, 'tp')) return 'pratique';
        if (str_contains($t, 'activ') || str_contains($t, 'act')) return 'activite';
        if (str_contains($t, 's1')) return 's1_activite';
        if (str_contains($t, 's2')) return 's2_activite';
        return 'autre';
    }

    private function buildModuleLines(array $modules, array $typeExam, array $notebooks, array $notes): array
    {
        $agg = [];
        foreach ($modules as $idm => $m) {
            $nb = $notebooks[$idm] ?? null;
            
            $label = (string)($m['module'] ?? ('M'.$idm));
            $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $agg[$idm] = [
                'module' => $label,
                'cours'  => $nb['cours'] ?? '0.00',
                'ex'     => $nb['ex'] ?? '0.00',
                'total'  => $nb['total'] ?? '0.00',
                'ecrit' => 'NA',
                'pratique' => 'NA',
                'activite' => 'NA',
                's1_activite' => 'NA',
                's2_activite' => 'NA',
            ];
        }

        foreach ($notes as $n) {
            $idtype = (int)$n['idtypeexamen'];
            if (!isset($typeExam[$idtype])) continue;

            $idmodule = (int)$typeExam[$idtype]['idmodule'];
            if ($idmodule < 1) continue;
            if (!isset($agg[$idmodule])) continue;

            $kind = $this->classifyExam($typeExam[$idtype]['code'], $typeExam[$idtype]['libellefr']);
            $val = ((int)($n['absent'] ?? 0) === 1) ? 'Absent' : (string)($n['note'] ?? 'NA');

            if (array_key_exists($kind, $agg[$idmodule])) {
                $agg[$idmodule][$kind] = $val;
            }
        }

        $lines = [];
        foreach ($agg as $idmodule => $a) {
            $lines[] =
                "- {$a['module']}"
                . " | cahier_cours={$a['cours']}/10"
                . " | cahier_ex={$a['ex']}/10"
                . " | cahier_total={$a['total']}/20"
                . " | écrit={$a['ecrit']}"
                . " | pratique={$a['pratique']}"
                . " | activité={$a['activite']}";
        }
        return $lines;
    }

    private function buildStudentInput(
        int $points, int $absAbsent, int $absTotal,
        string $observation, array $moduleLines,
        string $scope, string $period
    ): string {
        $lines = [];
        $lines[] = "CONTEXTE: scope={$scope} period={$period}";
        $lines[] = "DONNÉES ÉLÈVE";
        $lines[] = "Points={$points}";
        $lines[] = "Absences={$absAbsent}/{$absTotal}";
        $lines[] = "Observation={$observation}";
        $lines[] = "MODULES";
        foreach ($moduleLines as $l) $lines[] = $l;
        return implode("\n", $lines);
    }

    private function saveResults(array $results, string $scope, string $period, string $model, string $promptHash, string $batchId): void
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare("
            INSERT INTO ai_appreciations (idacademicrecords, scope, period_key, model, prompt_hash, result_json, batch_id)
            VALUES (:idacademicrecords, :scope, :period_key, :model, :prompt_hash, :result_json, :batch_id)
            ON DUPLICATE KEY UPDATE
              model=VALUES(model),
              prompt_hash=VALUES(prompt_hash),
              result_json=VALUES(result_json),
              batch_id=VALUES(batch_id),
              updated_at=CURRENT_TIMESTAMP
        ");

        $saveCount = 0;

        foreach ($results as $customId => $payload) {
            if (!preg_match('/^dossier_(\d+)$/', (string)$customId, $m)) continue;
            $idDossier = (int)$m[1];

            if (isset($payload['error'])) continue;

            $stmt->execute([
                ':idacademicrecords' => $idDossier,
                ':scope' => $scope,
                ':period_key' => $period,
                ':model' => $model,
                ':prompt_hash' => $promptHash,
                ':result_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ':batch_id' => $batchId,
            ]);
            $saveCount++;
        }

        $this->pdo->commit();
        echo "OK sauvegardé en DB: {$saveCount} / " . count($results) . "\n";
    }

    private function parseBatchOutputJsonl(string $outputPath): array
    {
        $fh = fopen($outputPath, 'rb');
        if ($fh === false) throw new \RuntimeException("Impossible d'ouvrir {$outputPath}");

        $results = [];
        while (($line = fgets($fh)) !== false) {
            $row = json_decode(trim($line), true);
            if (!is_array($row)) continue;

            $cid = $row['custom_id'] ?? null;
            if (!$cid) continue;

            if (!empty($row['error'])) {
                $results[$cid] = ["error" => $row['error']];
                continue;
            }

            $body = $row['response']['body'] ?? null;
            if (!is_array($body)) {
                $results[$cid] = ["error" => ["message" => "Missing response body"]];
                continue;
            }

            $parsed = $this->extractStructuredJson($body);
            if ($parsed === null) {
                $results[$cid] = ["error" => ["message" => "Unable to extract structured JSON"]];
                continue;
            }
            $results[$cid] = $parsed;
        }
        fclose($fh);
        return $results;
    }

    private function extractStructuredJson(array $responseBody): ?array
    {
        if (isset($responseBody['output_text']) && is_string($responseBody['output_text'])) {
            $j = json_decode($responseBody['output_text'], true);
            if (is_array($j)) return $j;
        }
        if (isset($responseBody['output']) && is_array($responseBody['output'])) {
            foreach ($responseBody['output'] as $item) {
                if (!is_array($item)) continue;
                if (($item['type'] ?? '') === 'message' && isset($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $c) {
                        if (!is_array($c)) continue;
                        $t = $c['text'] ?? null;
                        if (is_string($t)) {
                            $j = json_decode($t, true);
                            if (is_array($j)) return $j;
                        }
                    }
                }
            }
        }
        return null;
    }

    // ---------- prompt + schema ----------
    private function instructions(): string
    {
        return trim(<<<TXT
RÔLE
Tu es professeur d’informatique au lycée, expérimenté, pédagogue et rigoureux.

OBJECTIF
Rédiger des remarques scolaires claires, utiles et respectueuses pour le dossier scolaire et les parents.
L’élève est anonymisé : ne jamais utiliser de nom, prénom, identifiant ou numéro.

RÈGLE ACADÉMIQUE IMPORTANTE
Un module validé ou terminé ne peut pas être refait.
L’élève ne peut retravailler un module qu’en cas de redoublement, où il recommencera alors depuis le début.
Les remarques doivent donc encourager l’amélioration sur les modules en cours, pas sur les modules déjà terminés.

STYLE
- Ton professionnel, bienveillant, exigeant
- Langage simple, compréhensible par les parents
- Pas de jugement, uniquement des faits + pistes d’amélioration
- Pas de phrases inutiles
- Différencier clairement : écrit / pratique / activités

DÉFINITION DES CONTRAINTES
- Écrit : compréhension théorique, mémorisation
- Pratique : compétences techniques, autonomie
- Activités : participation, effort, régularité
- Cahiers : organisation et sérieux du travail
- Points : comportement et implication
- Absences : suivi scolaire

TÂCHES
1) Appréciation bulletin : 1–2 phrases, max 30 mots
2) Message aux parents : 5–6 phrases, max 90 mots
   - expliquer les différences écrit / pratique / activité
   - valoriser les acquis
   - orienter vers l’amélioration sur les modules en cours uniquement
3) Actions concrètes (2 semaines) : 6 actions courtes
   - 2 pour l’écrit, 2 pour le pratique, 1 pour l’activité, 1 pour le comportement
4) Objectifs SMART (1 mois) : 3 objectifs
   - 1 écrit, 1 pratique, 1 organisation/participation
5) Absences : Ajouter une phrase seulement si absences >10%

SORTIE: JSON strict uniquement.
TXT);
    }

    private function jsonSchema(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "bulletin" => ["type" => "string"],
                "parents"  => ["type" => "string"],
                "actions"  => ["type" => "array", "items" => ["type" => "string"]],
                "objectifs"=> ["type" => "array", "items" => ["type" => "string"]],
                "absences" => ["type" => "string"],
            ],
            "required" => ["bulletin","parents","actions","objectifs","absences"],
            "additionalProperties" => false
        ];
    }
}
