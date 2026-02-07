<?php

use App\Helpers\DateHelper;

$baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
$baseUrl = rtrim($baseUrl, '/');

$byClasse = $byClasse ?? [];
$profName = (string)($profName ?? '');
$matiere  = (string)($matiere ?? 'Informatique');
$lycee    = (string)($lycee ?? 'Lycée Ibn Al Haytam – Fès -');

$hasAnyPrinted = (bool)($hasAnyPrinted ?? false);
$filters  = $filters ?? [];
$dateFrom = (string)($filters['date_from'] ?? '');
$dateTo   = (string)($filters['date_to'] ?? '');

$classes = $classes ?? [];
$coverClasses = implode(', ', array_values(array_filter(array_map(
    static fn($c) => trim((string)($c['classe'] ?? '')),
    $classes
), static fn($v) => $v !== '')));

$coverYear = trim((string)($annee_label ?? ''));
if ($coverYear === '') $coverYear = '—';

$h = fn($s) => $this->e((string)$s);

/**
 * Si une partie contient "absen" => message spécial
 */
function startsWithAbsences(array $parts): bool
{
    foreach ($parts as $p) {
        $label = mb_strtolower(trim((string)($p['partie'] ?? '')));
        if ($label !== '' && str_contains($label, 'absen')) return true;
    }
    return false;
}

/**
 * Construit le HTML contenu:
 * - H2 quand (module+numKey) change
 * - Liste des parties dans une seule boucle
 * - Num affiché = partie après ":" (sans "Mx Ly")
 * - Si num vide => afficher la partie sans num
 */
function buildContentHtml(array $parts): string
{
    if (empty($parts)) return '<span class="muted">—</span>';

    if (startsWithAbsences($parts)) {
        return '<strong>Trop d\'absences impossible d\'assurer la séance</strong>';
    }

    $out = '';
    $prevGroupKey = ''; // pour détecter changement (module + key MxLy)

    foreach ($parts as $p) {
        $moduleLabel = trim((string)($p['module'] ?? ''));
        $abrev       = trim((string)($p['abrev'] ?? ''));
        $numRaw      = trim((string)($p['num'] ?? '')); // ex "M1 L1 : 4.1.1"
        $partLabel   = trim((string)($p['partie'] ?? ''));

        // clé de regroupement = partie avant ":" (M1 L1) + module id
        $keyLeft  = '';
        $numRight = '';

        if ($numRaw !== '') {
            $arr = explode(':', $numRaw, 2);
            $keyLeft  = trim($arr[0] ?? '');
            $numRight = trim($arr[1] ?? '');
        }

        $groupKey = ((string)($p['idmodule'] ?? '')) . '|' . $keyLeft;

        // H2 si changement de groupe
        if ($groupKey !== $prevGroupKey) {
            $title = $abrev !== '' ? $abrev : 'Module';
            if ($moduleLabel !== '') $title .= ' : ' . $moduleLabel;
            if ($keyLeft !== '') $title .= ' — ' . $keyLeft; // on affiche Mx Ly ici (regroupement)
            $out .= '<h2 class="sec">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
            $prevGroupKey = $groupKey;
        }

        // Num affiché = ce qui est après ":" (sans Mx Ly)
        $displayNum = '';
        if ($numRaw !== '') {
            if (preg_match('/:\s*([0-9]+(?:\.[0-9]+)*)/u', $numRaw, $m)) {
                $displayNum = $m[1];
            } elseif ($numRight !== '') {
                $displayNum = $numRight;
            }
        }

        if ($partLabel !== '') {
            $prefix = ($displayNum !== '') ? ($displayNum . '. ') : '';
            $out .= '<div class="line">' . htmlspecialchars($prefix . $partLabel, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }

    return $out !== '' ? $out : '<span class="muted">—</span>';
}
?>
<style>
    /* Zone sélection (screen only) */
    .print-toolbar {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .panel {
        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: 12px;
        padding: 12px;
        background: #fff;
    }

    .panel h3 {
        font-size: 14px;
        margin: 0 0 8px;
    }

    .panel .small {
        font-size: 12px;
        color: #666;
    }

    .list {
        max-height: 320px;
        overflow: auto;
        padding-right: 6px;
    }

    .cls-title {
        font-weight: 700;
        margin-top: 10px;
    }

    .chk {
        display: flex;
        gap: 8px;
        align-items: center;
        margin: 4px 0;
        font-size: 13px;
    }

    .muted {
        color: #777;
    }

    /* Pages preview */
    .print-area {
        background: #f6f6f6;
        padding: 12px;
        border-radius: 12px;
    }

    .sheet {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: 12px;
        padding: 12px;
        margin: 0 auto 16px;
    }

    .two-up {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .page-block {
        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: 10px;
        padding: 10px;
        min-height: 360px;
        position: relative;
    }

    .page-block h1 {
        font-size: 18px;
        margin: 0 0 8px;
        text-align: center;
    }

    .page-block table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        table-layout: fixed;
    }

    .page-block th,
    .page-block td {
        border: 1px solid rgba(0, 0, 0, .20);
        padding: 6px;
        vertical-align: top;
        word-wrap: break-word;
    }

    .page-block th {
        background: rgba(0, 0, 0, .04);
    }

    .sec {
        font-size: 13px;
        margin: 8px 0 4px;
    }

    .line {
        margin: 2px 0;
    }

    /* ✅ tfoot collé en bas du bloc/page */
    .page-block table {
        height: 100%;
    }

    .page-block tfoot td {
        vertical-align: middle;
        padding: 8px 6px;
    }

    .sig {
        text-align: center;
        /* signature au milieu de la cellule */
    }

    /* Page de garde */
    .cover {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: calc(100vh - 60px);
    }

    .cover h1 {
        font-size: 34px;
        margin: 0 0 12px;
        text-align: center;
        letter-spacing: .5px;
    }

    .cover .b {
        font-weight: 700;
    }

    .cover .line {
        font-size: 18px;
        margin: 4px 0;
        text-align: center;
    }

    /* PRINT RULES */
    @media print {
        .print-toolbar {
            display: none !important;
        }

        .print-area {
            background: transparent;
            padding: 0;
            border-radius: 0;
        }

        .sheet {
            border: none;
            border-radius: 0;
            padding: 0;
            margin: 0;
        }

        /* A4: 1cm partout */
        @page {
            size: A4;
            margin: 1cm;
        }

        .sheet {
            page-break-after: always;
        }

        .page-block {
            break-inside: avoid;
        }

        .page-block {
            border: none;
            border-radius: 0;
            padding: 0;
            min-height: auto;
        }

        .page-block table {
            font-size: 11px;
        }

        .cover {
            height: auto;
            page-break-after: always;
        }
    }
</style>

<div class="print-toolbar">
    <div class="panel" style="flex:1;">
        <h3>Pages à imprimer</h3>
        <div class="small">
            Sélectionne les séances à imprimer, puis clique <b>Imprimer</b>, ensuite <b>Confirmer</b> pour marquer ces séances comme imprimées.
        </div>

        <form method="get" class="mt-2" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end;">
            <div>
                <label class="small">Du</label>
                <input type="date" name="date_from" value="<?= $h($dateFrom) ?>">
            </div>
            <div>
                <label class="small">Au</label>
                <input type="date" name="date_to" value="<?= $h($dateTo) ?>">
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="submit" style="padding:6px 10px;">Filtrer</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= $baseUrl ?>/notebook/print" style="padding:6px 10px;">Réinitialiser</a>
        </form>

        <div class="mt-2" style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" id="btnAll" class="btn btn-sm btn-outline-dark" style="padding:6px 10px;">Tout cocher</button>
            <button type="button" id="btnNone" class="btn btn-sm btn-outline-dark" style="padding:6px 10px;">Tout décocher</button>
            <button type="button" id="btnPrint" class="btn btn-sm btn-dark" style="padding:6px 10px;">Imprimer</button>
            <button type="button" id="btnConfirm" class="btn btn-sm btn-success" style="padding:6px 10px;">Confirmer l'impression</button>
        </div>

        <div class="list mt-2">
            <?php if (empty($byClasse)): ?>
                <div class="muted">Aucune séance à imprimer.</div>
            <?php else: ?>
                <?php foreach ($byClasse as $cid => $pack): ?>
                    <?php $items = $pack['items'] ?? []; ?>
                    <div class="cls-title"><?= $h($pack['classe'] ?? '') ?></div>
                    <?php if (empty($items)): ?>
                        <div class="muted small">— aucune séance non imprimée —</div>
                    <?php else: ?>
                        <?php foreach ($items as $it): ?>
                            <?php
                            $sid = (int)($it['id'] ?? 0);
                            $label = DateHelper::toFr((string)$it['date']) . ' ' . (string)$it['heured'];
                            ?>
                            <label class="chk">
                                <input class="js-pick" type="checkbox" value="<?= (int)$sid ?>" checked>
                                <span><?= $h($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel" style="width:320px;">
        <h3>Règles</h3>
        <div class="small">
            • Page de garde affichée si <b>aucune séance imprimée</b> dans l’année.<br>
            • Contenu trié <b>idmodule → idpartie</b>.<br>
            • Si une partie contient “absen…”, message spécial.<br>
            • Footer: classe + prof + signature (dans tfoot).
        </div>
    </div>
</div>

<div class="print-area" id="printArea">

    <?php if (!$hasAnyPrinted): ?>
        <!-- PAGE DE GARDE -->
        <div class="sheet">
            <div class="cover">
                <h1>CAHIER DE TEXTE</h1>
                <div class="line b"><?= $h($profName) ?></div>
                <div class="line">Matière : <?= $h($matiere) ?></div>
                <div class="line"><?= $h($lycee) ?></div>
                <div class="line">Année scolaire <?= $h($coverYear) ?></div>
                <div class="line">Classes : <?= $h($coverClasses) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $blocks = [];
    foreach ($byClasse as $cid => $pack) {
        $classeName = (string)($pack['classe'] ?? '');
        $items = $pack['items'] ?? [];

        if (empty($items) && $hasAnyPrinted) continue;

        $blocks[] = ['classe' => $classeName, 'items' => $items];
    }
    $chunks = array_chunk($blocks, 2);
    ?>

    <?php foreach ($chunks as $sheetBlocks): ?>
        <div class="sheet">
            <div class="two-up">

                <?php foreach ($sheetBlocks as $b): ?>
                    <?php
                    $classeName = (string)$b['classe'];
                    $items = $b['items'];
                    ?>

                    <div class="page-block" data-class="<?= $h($classeName) ?>">
                        <?php if (!$hasAnyPrinted): ?>
                            <h1><?= $h($classeName) ?></h1>
                        <?php endif; ?>

                        <table>
                            <thead>
                                <tr>
                                    <th style="width:18%;">Date &amp; Heure</th>
                                    <th>Contenue</th>
                                    <th style="width:18%;">Absences</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="3" class="muted" style="text-align:center;">Aucune séance.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $it): ?>
                                        <?php
                                        $sid = (int)($it['id'] ?? 0);
                                        $d = DateHelper::toFr((string)$it['date'], 'dd/MM/yyyy');
                                        $hStart = (string)($it['heured'] ?? '');
                                        $range = $d . ' ' . $hStart;

                                        $contentHtml = buildContentHtml($it['parts'] ?? []);
                                        $abs = trim((string)($it['absences'] ?? ''));
                                        ?>
                                        <tr class="js-row" data-seance-id="<?= (int)$sid ?>">
                                            <td><?= $h($range) ?></td>
                                            <td><?= $contentHtml ?></td>
                                            <td><?= $abs !== '' ? $h($abs) : '<span class="muted">—</span>' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>

                            <!-- ✅ tfoot = pied du tableau, en fin de page -->
                            <tfoot>
                                <tr>
                                    <td><b>Classe :</b> <?= $h($classeName) ?></td>
                                    <td style="text-align:center;"><b><?= $h($profName) ?></b></td>
                                    <td class="sig"><b>Signature :</b> __________</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endforeach; ?>

                <?php if (count($sheetBlocks) === 1): ?>
                    <div class="page-block" style="opacity:.0;"></div>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>

</div>

<script>
    (() => {
        'use strict';

        const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
        const $ = (sel, root = document) => root.querySelector(sel);

        const picks = () => $$('.js-pick')
            .filter(c => c.checked)
            .map(c => parseInt(c.value, 10))
            .filter(n => Number.isFinite(n) && n > 0);

        const syncRows = () => {
            const selected = new Set(picks());
            $$('.js-row').forEach(tr => {
                const id = parseInt(tr.dataset.seanceId, 10);
                tr.style.display = selected.has(id) ? '' : 'none';
            });
        };

        $('#btnAll')?.addEventListener('click', () => {
            $$('.js-pick').forEach(c => c.checked = true);
            syncRows();
        });

        $('#btnNone')?.addEventListener('click', () => {
            $$('.js-pick').forEach(c => c.checked = false);
            syncRows();
        });

        $$('.js-pick').forEach(c => c.addEventListener('change', syncRows));

        $('#btnPrint')?.addEventListener('click', () => {
            syncRows();
            window.print();
        });

        $('#btnConfirm')?.addEventListener('click', async () => {
            const ids = picks();
            if (!ids.length) {
                alert("Aucune séance sélectionnée.");
                return;
            }
            if (!confirm("Confirmer l'impression et marquer ces séances comme imprimées ?")) return;

            try {
                const res = await fetch('<?= $h($baseUrl) ?>/api/notebook/print/confirm', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ids
                    })
                });
                const json = await res.json();
                if (!json.ok) throw new Error(json.error || 'Erreur');

                alert("OK : " + (json.updated || 0) + " séance(s) marquée(s) imprimée(s).");
                window.location.reload();
            } catch (e) {
                alert("Erreur: " + (e?.message || e));
            }
        });

        syncRows();
    })();
</script>