#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\OpenAI\OpenAIClient;
use App\Services\OpenAI\AiBatchGenerator;

function parseArgs(array $argv): array {
    $out = [];
    foreach ($argv as $a) {
        if (preg_match('/^--([^=]+)=(.*)$/', $a, $m)) $out[$m[1]] = $m[2];
    }
    return $out;
}

function usage(): void {
    $msg = <<<TXT
Usage:
  php bin/ai_batch_generate.php --classe=ID --annee=ID [--scope=monthly|s1|s2|annual] [--period=...]
                               [--dry=1] [--limit=N] [--out=/path/input.jsonl] [--check=1] [--ping=1]

Options:
  --dry=1      Génère input.jsonl (prompts) et stop (0 coût)
  --limit=N    Limite à N élèves (utile avec --dry ou --check)
  --out=PATH   Chemin de sortie input.jsonl (défaut: /tmp/input.jsonl)
  --check=1    Vérifie DB (dossiers/modules) + affiche un exemple (0 coût)
  --ping=1     Teste connexion OpenAI (/v1/models)

Examples:
  php bin/ai_batch_generate.php --classe=3 --annee=2 --scope=monthly --period=2026-01 --dry=1 --limit=2
  php bin/ai_batch_generate.php --classe=3 --annee=2 --check=1
  php bin/ai_batch_generate.php --classe=3 --annee=2 --ping=1

TXT;
    fwrite(STDERR, $msg);
}

$args = parseArgs($argv);

$idClasse = (int)($args['classe'] ?? 0);
$idAnnee  = (int)($args['annee'] ?? 0);
$scope    = (string)($args['scope'] ?? 'monthly'); // monthly|s1|s2|annual
$period   = (string)($args['period'] ?? date('Y-m'));

$dry   = (int)($args['dry'] ?? 0);
$check = (int)($args['check'] ?? 0);
$ping  = (int)($args['ping'] ?? 0);
$limit = (int)($args['limit'] ?? 0);
$out   = (string)($args['out'] ?? (sys_get_temp_dir() . '/input.jsonl'));

$allowedScopes = ['monthly','s1','s2','annual'];
if (!in_array($scope, $allowedScopes, true)) {
    fwrite(STDERR, "Erreur: scope invalide. Valeurs: " . implode('|', $allowedScopes) . "\n\n");
    usage();
    exit(1);
}

if ($idClasse <= 0 || $idAnnee <= 0) {
    usage();
    exit(1);
}

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

function env(string $k): ?string {
    $v = $_ENV[$k] ?? $_SERVER[$k] ?? null;
    if ($v === null || $v === '') return null;
    return (string)$v;
}

foreach (['DB_HOST','DB_NAME','DB_USER','DB_PASS'] as $k) {
    if (env($k) === null) {
        throw new RuntimeException("Variable .env manquante: {$k}");
    }
}

// OpenAI
$apiKey = env('OPENAI_API_KEY') ?: '';
$base   = env('OPENAI_BASE') ?: 'https://api.openai.com/v1';
$model  = env('OPENAI_MODEL') ?: 'gpt-4.1-mini';

// DB
$dbHost = env('DB_HOST') ?: '127.0.0.1';
$dbName = env('DB_NAME') ?: 'appclassetest';
$dbUser = env('DB_USER') ?: 'root';
$dbPass = env('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Erreur DB: " . $e->getMessage() . "\n");
    exit(1);
}

// OpenAI client (API key requise uniquement si ping=1 ou dry=0/run)
if (($ping === 1 || $dry === 0) && $apiKey === '') {
    fwrite(STDERR, "OPENAI_API_KEY manquant.\n");
    exit(1);
}

$client = new OpenAIClient($apiKey, $base);
$svc = new AiBatchGenerator($pdo, $client, $model);

// 1) DB CHECK (0 coût)
if ($check === 1) {
    echo "CHECK: classe={$idClasse} annee={$idAnnee} scope={$scope} period={$period}\n";
    // -> À implémenter dans AiBatchGenerator
    // doit retourner un tableau: ['records'=>..., 'modules'=>..., 'sample'=>...]
    $report = $svc->check($idClasse, $idAnnee, $limit);

    echo "DB OK\n";
    echo "- dossiers trouvés: " . (int)($report['records_count'] ?? 0) . "\n";
    echo "- modules id>0:     " . (int)($report['modules_count'] ?? 0) . "\n";

    if (!empty($report['sample'])) {
        echo "\n--- SAMPLE (1 élève anonymisé) ---\n";
        echo $report['sample'] . "\n";
    }
    exit(0);
}

// 2) PING OpenAI (/v1/models)
if ($ping === 1) {
    echo "PING OpenAI: {$base}\n";
    try {
        $models = $client->listModels(); // -> À implémenter dans OpenAIClient
        $n = is_array($models) && isset($models['data']) && is_array($models['data']) ? count($models['data']) : 0;
        echo "OK: auth + réseau. Models reçus: {$n}\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "PING FAILED: " . $e->getMessage() . "\n");
        exit(2);
    }
}

// 3) DRY-RUN: générer input.jsonl (0 coût)
if ($dry === 1) {
    echo "DRY-RUN: build input.jsonl (0 coût)\n";
    echo "-> out={$out}\n";
    echo "-> limit=" . ($limit > 0 ? $limit : 'ALL') . "\n";

    // -> À implémenter dans AiBatchGenerator
    $count = $svc->buildInputJsonl($idClasse, $idAnnee, $scope, $period, $out, $limit);

    echo "OK: {$count} ligne(s) écrite(s) dans {$out}\n";
    echo "Astuce: head -n 1 {$out}\n";
    exit(0);
}

// 4) RUN réel (coût)
echo "RUN: classe={$idClasse} annee={$idAnnee} scope={$scope} period={$period}\n";
$svc->run($idClasse, $idAnnee, $scope, $period);
