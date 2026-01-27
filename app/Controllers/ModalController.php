<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ModalController extends Controller
{
    private function baseUrl(): string
    {
        $cfg = require dirname(__DIR__) . '/config/app.php';
        return rtrim($cfg['base_url'] ?? '', '/');
    }

    public function newSeance(): void
    {
        $idclasse = (int)($_GET['idclasse'] ?? 0);
        $date = trim((string)($_GET['date'] ?? ''));
        $heured = trim((string)($_GET['heured'] ?? ''));

        // Validation simple
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = '';
        }
        if ($heured !== '' && !preg_match('/^\d{2}:\d{2}$/', $heured)) {
            $heured = '';
        }

        if ($idclasse <= 0) {
            http_response_code(400);
            echo "Paramètre idclasse manquant";
            return;
        }

        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT id, classe FROM classes WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $idclasse]);
        $classe = $st->fetch();

        if (!$classe) {
            http_response_code(404);
            echo "Classe introuvable";
            return;
        }

        $this->view('modals/seances_new', [
            'baseUrl' => $this->baseUrl(),
            'classe'  => $classe,
            'date'    => $date,
            'heured'  => $heured,
        ], layout: null);
    }

    public function absences(): void
    {
        $idseance = (int)($_GET['idseance'] ?? 0);
        if ($idseance <= 0) {
            http_response_code(400);
            echo "Paramètre idseance manquant";
            return;
        }

        $pdo = Database::pdo();

        // Trouver la classe de la séance
        $st = $pdo->prepare("
            SELECT s.id, s.idclasse, c.classe, s.date, TIME_FORMAT(s.heured,'%H:%i') as heured
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :id AND s.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['id' => $idseance]);
        $seance = $st->fetch();

        if (!$seance) {
            http_response_code(404);
            echo "Séance introuvable";
            return;
        }

        // Liste élèves actifs de la classe + état absence si déjà enregistré
        $st = $pdo->prepare("
            SELECT e.id, e.nom, e.prenom, e.numerosgs,
                   COALESCE(se.absent,0) as absent,
                   COALESCE(se.justify,0) as justify
            FROM eleves_classes ec
            JOIN eleves e ON e.id = ec.ideleve AND e.deleted_at IS NULL
            LEFT JOIN seances_eleves se ON se.ideleve = e.id AND se.idseance = :idseance
            WHERE ec.idclasse = :idclasse AND ec.depart = 0
            ORDER BY e.nom, e.prenom
        ");
        $st->execute([
            'idseance' => (int)$seance['id'],
            'idclasse' => (int)$seance['idclasse'],
        ]);
        $eleves = $st->fetchAll();

        $this->view('modals/seances_absences', [
            'baseUrl' => $this->baseUrl(),
            'seance' => $seance,
            'eleves' => $eleves,
        ], layout: null);
    }

    public function parties(): void
    {
        $idseance = (int)($_GET['idseance'] ?? 0);
        if ($idseance <= 0) {
            http_response_code(400);
            echo "Paramètre idseance manquant";
            return;
        }

        $pdo = Database::pdo();

        $st = $pdo->prepare("
            SELECT s.id, s.idclasse, c.classe, s.date, TIME_FORMAT(s.heured,'%H:%i') as heured
            FROM seances s
            JOIN classes c ON c.id = s.idclasse
            WHERE s.id = :id AND s.deleted_at IS NULL
            LIMIT 1
        ");
        $st->execute(['id' => $idseance]);
        $seance = $st->fetch();
        if (!$seance) {
            http_response_code(404);
            echo "Séance introuvable";
            return;
        }

        $modules = $pdo->query("SELECT id, module, abrev FROM modules ORDER BY module")->fetchAll();

        // Parties du module sélectionné (optionnel: filtrer côté JS)
        $parties = $pdo->query("SELECT p.id, p.partie, p.num, p.idmodule, m.abrev
                                FROM parties p JOIN modules m ON m.id=p.idmodule
                                ORDER BY m.module, p.niv, p.num")->fetchAll();

        // Parties déjà liées à la séance
        $st = $pdo->prepare("
            SELECT sp.idpartie
            FROM seances_parties sp
            WHERE sp.idseance = :id
        ");
        $st->execute(['id' => (int)$seance['id']]);
        $linked = array_column($st->fetchAll(), 'idpartie');

        $this->view('modals/seances_parties', [
            'baseUrl' => $this->baseUrl(),
            'seance' => $seance,
            'modules' => $modules,
            'parties' => $parties,
            'linked' => $linked,
        ], layout: null);
    }

    public function eleveTags(): void
    {
        $ideleve = (int)($_GET['ideleve'] ?? 0);
        if ($ideleve <= 0) {
            http_response_code(400);
            echo "Paramètre ideleve manquant";
            return;
        }

        $pdo = Database::pdo();

        $st = $pdo->prepare("SELECT id, nom, prenom FROM eleves WHERE id=:id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $ideleve]);
        $eleve = $st->fetch(\PDO::FETCH_ASSOC);

        if (!$eleve) {
            http_response_code(404);
            echo "Élève introuvable";
            return;
        }

        // IMPORTANT: fetch assoc
        $st = $pdo->query("SELECT id, tag, color FROM tags ORDER BY tag");
        $tags = $st->fetchAll(\PDO::FETCH_ASSOC);

        $st = $pdo->prepare("SELECT idtag FROM eleves_tags WHERE ideleve = :id");
        $st->execute(['id' => $ideleve]);
        $selected = array_map('intval', array_column($st->fetchAll(\PDO::FETCH_ASSOC), 'idtag'));

        $selectedMap = [];
        foreach ($selected as $idtag) {
            $selectedMap[$idtag] = true;
        }

        $this->view('modals/eleves_tags', [
            'baseUrl'     => $this->baseUrl(),
            'eleve'       => $eleve,

            // compat: si ta vue attend $tags
            'tags'        => $tags,

            // compat: si ta vue attend $allTags
            'allTags'     => $tags,

            // compat: si ta vue attend $selected (liste)
            'selected'    => $selected,

            // compat: si ta vue attend $selectedMap
            'selectedMap' => $selectedMap,

            // utile pour JS fetch + CSRF
            'csrfToken'   => \App\Core\Csrf::token(),
        ], layout: null);
    }


    public function eleveShow(): void
    {
        $ideleve  = (int)($_GET['ideleve'] ?? 0);
        $idclasse = (int)($_GET['idclasse'] ?? 0);
        $idannee  = (int)($_GET['idannee'] ?? 0);

        if ($ideleve <= 0) {
            http_response_code(400);
            echo "Paramètre ideleve invalide";
            return;
        }

        $pdo = Database::pdo();

        // 1) Identité élève
        $st = $pdo->prepare("
          SELECT id, numerosgs, nom, prenom, nomar, prenomar, datenaiss, sexe, observation
          FROM eleves
          WHERE id=:id AND deleted_at IS NULL
          LIMIT 1
        ");
        $st->execute(['id' => $ideleve]);
        $eleve = $st->fetch();
        if (!$eleve) { http_response_code(404); echo "Élève introuvable"; return; }

        // 2) Historique classes/années (chez toi)
        $st = $pdo->prepare("
          SELECT
            a.id AS idannee,
            a.annee,
            c.id AS idclasse,
            c.classe,
            ec.numero,
            ec.depart
          FROM eleves_classes ec
          JOIN classes c ON c.id = ec.idclasse AND c.deleted_at IS NULL
          JOIN annees a ON a.id = c.idannee
          WHERE ec.ideleve = :e
          ORDER BY a.id DESC, c.classe ASC
        ");
        $st->execute(['e' => $ideleve]);
        $history = $st->fetchAll();

        // 3) Dossiers scolaires par année (points/participation/obs)
        $st = $pdo->prepare("
            SELECT
                ds.id AS idacademicrecords,
                ds.idannee, a.annee,
                ds.points, ds.participation, ds.obs1, ds.obs2
            FROM dossiers_scolaires ds
            JOIN annees a ON a.id = ds.idannee
            WHERE ds.ideleve = :e
            ORDER BY ds.idannee DESC
        ");
        $st->execute(['e' => $ideleve]);
        $dossiers = $st->fetchAll();

        // 4) Notes (toutes années) + type + module
        $st = $pdo->prepare("
        SELECT
            ds.id AS idacademicrecords,
            ds.idannee, a.annee,
            m.id AS idmodule,
            m.abrev, m.module,
            te.code, te.libellefr,
            n.note, n.absent, n.triche, n.observation
        FROM notes n
        JOIN dossiers_scolaires ds ON ds.id = n.idacademicrecords
        JOIN annees a ON a.id = ds.idannee
        JOIN types_examens te ON te.id = n.idtypeexamen
        JOIN modules m ON m.id = te.idmodule
        WHERE ds.ideleve = :ideleve
            AND ds.idannee = :idannee

            -- Afficher uniquement les contrôles déjà passés
            AND n.absent IS NOT NULL

        ORDER BY m.id ASC, te.id ASC
        ");
        $st->execute([
        'ideleve' => (int)$ideleve,
        'idannee' => (int)$idannee,
        ]);
        $notes = $st->fetchAll();


        // 5) Notebook scores (toutes années)
        $st = $pdo->prepare("
        SELECT
            ds.id AS idacademicrecords,
            ds.idannee, a.annee,
            m.id AS idmodule,
            m.abrev, m.module,
            ns.score
        FROM notebook_scores ns
        JOIN dossiers_scolaires ds ON ds.id = ns.idacademicrecords
        JOIN annees a ON a.id = ds.idannee
        JOIN modules m ON m.id = ns.idmodule
        WHERE ds.ideleve = :e
        ORDER BY ds.idannee DESC, m.id ASC
        ");
        $st->execute(['e' => $ideleve]);
        $notebook = $st->fetchAll();

        // 6) Tags
        $st = $pdo->prepare("
          SELECT t.id, t.tag, t.color
          FROM eleves_tags et
          JOIN tags t ON t.id = et.idtag
          WHERE et.ideleve = :e
          ORDER BY t.tag ASC
        ");
        $st->execute(['e' => $ideleve]);
        $tags = $st->fetchAll();

        // 7) Stats absences globales (présent/absent sur toutes tes séances)
        // On compte seulement les séances créées dans les classes où il était inscrit (depart=0).
        $st = $pdo->prepare("
          SELECT
            COUNT(DISTINCT s.id) AS total_seances,
            SUM(CASE WHEN se.absent = 1 THEN 1 ELSE 0 END) AS total_absent
          FROM eleves_classes ec
          JOIN seances s ON s.idclasse = ec.idclasse AND s.deleted_at IS NULL
          LEFT JOIN seances_eleves se ON se.idseance = s.id AND se.ideleve = ec.ideleve
          WHERE ec.ideleve = :e
            AND ec.depart = 0
        ");
        $st->execute(['e' => $ideleve]);
        $absenceStats = $st->fetch() ?: ['total_seances' => 0, 'total_absent' => 0];
        $absenceStats['total_present'] = max(0, (int)$absenceStats['total_seances'] - (int)$absenceStats['total_absent']);

        $byYear = []; // [idannee => ['annee'=>..., 'dossier'=>..., 'notes'=>[], 'notebook'=>[]]]

        foreach ($dossiers as $d) {
        $y = (int)$d['idannee'];
        $byYear[$y] = $byYear[$y] ?? [
            'idannee' => $y,
            'annee' => (string)$d['annee'],
            'dossier' => $d,
            'notes' => [],
            'notebook' => [],
        ];
        }

        foreach ($notes as $n) {
        $y = (int)$n['idannee'];
        $byYear[$y] = $byYear[$y] ?? ['idannee'=>$y,'annee'=>(string)$n['annee'],'dossier'=>null,'notes'=>[],'notebook'=>[]];
        $byYear[$y]['notes'][] = $n;
        }

        foreach ($notebook as $ns) {
        $y = (int)$ns['idannee'];
        $byYear[$y] = $byYear[$y] ?? ['idannee'=>$y,'annee'=>(string)$ns['annee'],'dossier'=>null,'notes'=>[],'notebook'=>[]];
        $byYear[$y]['notebook'][] = $ns;
        }

        // tri desc par idannee
        krsort($byYear);

        // --- Notes (année courante) sous forme texte pour le prompt ---
        $notesLines = [];
        $currentYearNotes = $byYear[$idannee]['notes'] ?? [];

        foreach ($currentYearNotes as $n) {
            // Nettoyage d’éventuelles entités HTML
            $module = html_entity_decode((string)($n['module'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $abrev  = html_entity_decode((string)($n['abrev'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $eval   = html_entity_decode((string)($n['libellefr'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $modLabel = trim($abrev . ' : ' . $module, " :");

            $absVal = $n['absent']; // 0|1 (car filtré), mais on sécurise
            if ($absVal === null) continue;

            $absText = ((int)$absVal === 1) ? 'Absent' : 'Présent';

            $note = $n['note'];
            $noteText = ($note !== null) ? (string)$note : '—';

            $notesLines[] = "- {$modLabel} | {$eval} | Note: {$noteText} | {$absText}";
        }

        $notesBlock = empty($notesLines)
            ? "- (Aucune note enregistrée pour l'instant)"
            : implode("\n", $notesLines);


        // Prompt anonyme (sans nom, sans numerosgs)
        $prompt = $this->buildParentReportPrompt($byYear, $absenceStats, $tags, $idannee, $idclasse, $notesBlock);

        // Rendre une vue "modal"
        $this->view('modals/eleves_show', compact(
        'eleve','history','tags','absenceStats','prompt','byYear','idclasse','idannee'
        ), 'modal');

    }

    public function eleveNotebook(): void
    {
        $ideleve = (int)($_GET['ideleve'] ?? 0);
        $idannee = (int)($_GET['idannee'] ?? 0);

        if ($ideleve <= 0 || $idannee <= 0) {
            http_response_code(400);
            echo "Paramètres invalides";
            return;
        }

        $pdo = Database::pdo();

        // Élève
        $st = $pdo->prepare("SELECT id, nom, prenom FROM eleves WHERE id=:id AND deleted_at IS NULL LIMIT 1");
        $st->execute(['id' => $ideleve]);
        $eleve = $st->fetch();
        if (!$eleve) { http_response_code(404); echo "Élève introuvable"; return; }

        // Dossier scolaire pour l'année
        $st = $pdo->prepare("SELECT id FROM dossiers_scolaires WHERE ideleve=:e AND idannee=:a LIMIT 1");
        $st->execute(['e' => $ideleve, 'a' => $idannee]);
        $idacademic = (int)($st->fetchColumn() ?: 0);
        if ($idacademic <= 0) { http_response_code(404); echo "Dossier scolaire introuvable"; return; }

        // Modules + scores (LEFT JOIN)
        $st = $pdo->prepare("
        SELECT
            m.id AS idmodule, m.abrev, m.module,
            ns.score_cours, ns.score_exercices
        FROM modules m
        LEFT JOIN notebook_scores ns
            ON ns.idmodule = m.id AND ns.idacademicrecords = :ar
        WHERE m.id >= 1
        ORDER BY m.id ASC
        ");
        $st->execute(['ar' => $idacademic]);
        $rows = $st->fetchAll();

        $this->view('modals/eleves_notebook', [
            'eleve' => $eleve,
            'idannee' => $idannee,
            'idacademicrecords' => $idacademic,
            'rows' => $rows,
        ], layout: null);
    }


    public function absencesList(): void
    {
        $ideleve = (int)($_GET['ideleve'] ?? 0);
        $idannee = (int)($_GET['idannee'] ?? 0);

        if ($ideleve <= 0 || $idannee <= 0) {
            http_response_code(400);
            echo "Paramètres invalides";
            return;
        }

        $pdo = \App\Core\Database::pdo();

        // Identité (optionnel mais utile dans titre)
        $st = $pdo->prepare("SELECT id, nom, prenom, nomar, prenomar FROM eleves WHERE id=:id LIMIT 1");
        $st->execute(['id' => $ideleve]);
        $eleve = $st->fetch() ?: [];

        // Liste des absences sur l'année
        $st = $pdo->prepare("
        SELECT
            s.id AS idseance,
            c.classe,
            s.date,
            TIME_FORMAT(s.heured, '%H:%i') AS heured
        FROM seances_eleves se
        JOIN seances s ON s.id = se.idseance AND s.deleted_at IS NULL
        JOIN classes c ON c.id = s.idclasse AND c.deleted_at IS NULL
        WHERE se.ideleve = :e
            AND se.absent = 1
            AND c.idannee = :a
        ORDER BY s.date DESC, s.heured DESC
        ");
        $st->execute(['e' => $ideleve, 'a' => $idannee]);
        $rows = $st->fetchAll();

        $cfg = require dirname(__DIR__) . '/config/app.php';
        $baseUrl = rtrim($cfg['base_url'] ?? '', '/');

        $this->view('modals/absences_list', [
            'baseUrl' => $baseUrl,
            'eleve' => $eleve,
            'rows' => $rows,
            'idannee' => $idannee,
        ], 'modal');
    }

    private function buildParentReportPrompt(array $byYear, array $absenceStats, array $tags, int $currentYearId, int $currentClasseId, string $notesBlock): string
    {
        // Infos année courante
        $current = $byYear[$currentYearId]['dossier'] ?? null;
        $points = $current['points'] ?? null;
        $part   = $current['participation'] ?? null;
        $obs1   = isset($current['obs1']) ? trim((string)$current['obs1']) : '';
        $obs2   = isset($current['obs2']) ? trim((string)$current['obs2']) : '';

        $tagList = array_values(array_filter(array_map(fn($t) => (string)($t['tag'] ?? ''), $tags)));

        $txt  = "Tu es un professeur d'informatique au Lycée. Rédige des remarques pour le dossier scolaire et un message destiné aux parents.\n";
        $txt .= "IMPORTANT: l'élève est anonymisé. N'utilise ni nom, ni prénom, ni NumeroSGS, ni identifiant.\n\n";

        $txt .= "Données (année courante):\n";
        
        $points = ($current && isset($current['points'])) ? (int)$current['points'] : null;
        $part   = ($current && isset($current['participation'])) ? (int)$current['participation'] : null;

        if ($points !== null) $txt .= "- Points: {$points}\n";
        $txt .= "- Absences: " . (int)($absenceStats['total_absent'] ?? 0) . " / " . (int)($absenceStats['total_seances'] ?? 0) . "\n";
        if ($obs1 !== '') $txt .= "- Observation 1 (enseignant): {$obs1}\n";
        if ($obs2 !== '') $txt .= "- Observation 2 (enseignant): {$obs2}\n";
        if (!empty($tagList)) $txt .= "- Tags: " . implode(', ', $tagList) . "\n";

        $txt .= "\nNotes (année courante):\n";
        $txt .= $notesBlock . "\n";

        $txt .= "\nTâche:\n";
        $txt .= "1) Écris une appréciation courte (1–2 phrases) pour le bulletin.\n";
        $txt .= "2) Écris un message aux parents (5–7 phrases) orienté amélioration, ton respectueux.\n";
        $txt .= "3) Donne 6 actions concrètes et mesurables sur 2 semaines (participation, travail, comportement, organisation).\n";
        $txt .= "4) Propose 3 objectifs SMART (spécifiques, mesurables) pour le prochain mois.\n";
        $txt .= "5) Si les absences sont élevées, ajoute une phrase de suivi adaptée.\n";

        return $txt;
    }


    private function buildAnonymousPrompt(array $eleve, array $history, array $dossiers, array $absenceStats, array $notes, array $notebook, array $tags, int $idclasse, int $idannee): string
    {
        // Ne pas inclure nom/prénom/numerosgs.
        // On peut inclure: sexe, tranche d'âge, numéro dans la classe courante (si disponible via history)
        $sexe = $eleve['sexe'] ?? 'M';
        $age = null;
        if (!empty($eleve['datenaiss'])) {
            try {
                $dn = new \DateTime((string)$eleve['datenaiss']);
                $age = (int)$dn->diff(new \DateTime('now'))->y;
            } catch (\Throwable $t) {}
        }

        $num = null;
        foreach ($history as $h) {
            if ((int)$h['idannee'] === $idannee && (int)$h['idclasse'] === $idclasse) {
                $num = (int)$h['numero'];
                break;
            }
        }

        $points = null; $part = null;
        foreach ($dossiers as $d) {
            if ((int)$d['idannee'] === $idannee) {
                $points = (int)$d['points'];
                $part   = (int)$d['participation'];
                break;
            }
        }

        $tagList = array_map(fn($t) => (string)$t['tag'], $tags);

        $txt  = "Tu es un professeur. Je veux des remarques pédagogiques et comportementales sur un élève, en restant anonyme.\n";
        $txt .= "Contexte:\n";
        $txt .= "- Élève anonymisé";
        if ($num !== null) $txt .= " (numéro dans la classe: $num)";
        $txt .= "\n- Sexe: " . ($sexe === 'F' ? 'Fille' : 'Garçon') . "\n";
        if ($age !== null) $txt .= "- Âge approximatif: $age ans\n";
        if ($points !== null) $txt .= "- Points: $points\n";
        if ($part !== null)   $txt .= "- Participation: $part\n";
        $txt .= "- Absences: " . (int)$absenceStats['total_absent'] . " absences / " . (int)$absenceStats['total_seances'] . " séances (présence: " . (int)$absenceStats['total_present'] . ")\n";
        if (!empty($tagList)) $txt .= "- Tags: " . implode(', ', $tagList) . "\n";
        $txt .= "\nDonne:\n1) 3 remarques positives\n2) 3 points à améliorer\n3) 5 conseils concrets (classe + maison)\n4) une phrase courte à mettre dans le bulletin.\n";
        $txt .= "Ne demande pas le nom ou un identifiant.\n";
        return $txt;
    }
}
