<?php

$days = [
  1 => 'Lundi',
  2 => 'Mardi',
  3 => 'Mercredi',
  4 => 'Jeudi',
  5 => 'Vendredi',
  6 => 'Samedi',
];

$dayKeys = $showSaturday ? [1, 2, 3, 4, 5, 6] : [1, 2, 3, 4, 5];

$grid = $timetable['grid'] ?? [];
$hours = $timetable['hours'] ?? [];
$classStyle = $timetable['classStyle'] ?? [];

$baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
$baseUrl = rtrim($baseUrl, '/');

/**
 * =========================================================
 * RAMADAN: overlay affichage (DB inchangée)
 * =========================================================
 * - heured (DB) reste la même
 * - heuredAffichage dépend d'un tableau de "shift" (ramadan)
 * - heuref affichée = heuredAffichage +45min (ramadan) sinon +60min
 */
$isRamadan = ((int)($ramadan ?? 0) === 1);

/**
 * Table demandée:
 *  - clé = heured DB
 *  - valeur = heuredAffichage
 */
$RAMADAN_SHIFT = [
  'fri' => [
    '08:30' => '08:30',
    '09:30' => '09:20',
    '10:30' => '10:15',
    '11:30' => '11:05',
    '14:30' => '13:40',
    '15:30' => '14:30',
    '16:30' => '15:25',
    '17:30' => '16:15',
  ],
  'rest' => [
    '08:30' => '08:40',
    '09:30' => '09:30',
    '10:30' => '10:25',
    '11:30' => '11:15',
    '14:30' => '12:30',
    '15:30' => '13:20',
    '16:30' => '14:15',
    '17:30' => '15:05',
  ]
];

function normH(string $h): string
{
  // "08:30:00" => "08:30"
  return substr(trim($h), 0, 5);
}

function timeToMin(string $h): int
{
  $h = normH($h);
  $dt = DateTime::createFromFormat('H:i', $h);
  if (!$dt) return 0;
  return ((int)$dt->format('H')) * 60 + (int)$dt->format('i');
}

function addMinutes(string $h, int $mins): string
{
  $m = timeToMin($h) + $mins;
  $m = max(0, $m);
  $hh = intdiv($m, 60);
  $ii = $m % 60;
  return str_pad((string)$hh, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$ii, 2, '0', STR_PAD_LEFT);
}

/**
 * Retourne l'heure de début AFFICHAGE (heuredAffichage).
 * - heuredDb = heure DB
 * - si ramadan: map(fri/rest)[heuredDb] sinon heuredDb
 */
function heuredAffichage(string $heuredDb, int $weekdayN, bool $isRamadan, array $RAMADAN_SHIFT): string
{
  $heuredDb = normH($heuredDb);
  if (!$isRamadan) return $heuredDb;

  $key = ($weekdayN === 5) ? 'fri' : 'rest';
  // si non trouvé => fallback = DB
  return normH($RAMADAN_SHIFT[$key][$heuredDb] ?? $heuredDb);
}

/**
 * Retourne [debutAff, finAff] selon règles:
 * - debutAff = heuredAffichage(...)
 * - finAff = debutAff + 45 (ramadan) sinon +60
 */
function rangeAffichage(string $heuredDb, int $weekdayN, bool $isRamadan, array $RAMADAN_SHIFT): array
{
  $start = heuredAffichage($heuredDb, $weekdayN, $isRamadan, $RAMADAN_SHIFT);
  $end   = addMinutes($start, $isRamadan ? 45 : 60);
  return [$start, $end];
}

/**
 * État d'un créneau: past | current | future basé sur l'heure AFFICHAGE du jour concerné.
 */
function slotState(int $dayN, string $heuredDb, DateTime $now, bool $isRamadan, array $RAMADAN_SHIFT): string
{
  $todayN = (int)$now->format('N');

  if ($dayN < $todayN) return 'past';
  if ($dayN > $todayN) return 'future';

  [$startDisp, $endDisp] = rangeAffichage($heuredDb, $dayN, $isRamadan, $RAMADAN_SHIFT);

  $nowMin   = ((int)$now->format('H')) * 60 + (int)$now->format('i');
  $startMin = timeToMin($startDisp);
  $endMin   = timeToMin($endDisp);

  if ($nowMin >= $endMin) return 'past';
  if ($nowMin >= $startMin && $nowMin < $endMin) return 'current';
  return 'future';
}

?>

<h1 class="h3 mb-3">Tableau de bord</h1>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold">Année scolaire</div>
        <?php if (!$annee): ?>
          <div class="text-muted">Aucune année</div>
        <?php else: ?>
          <div class="fs-5"><?= htmlspecialchars($annee['annee'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold">Classes</div>
        <div class="fs-5"><?= (int)$classesCount ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <div class="fw-semibold">Élèves (actifs)</div>
        <div class="fs-5"><?= (int)$elevesActifsCount ?></div>
      </div>
    </div>
  </div>
</div>

<h2 class="h5 mt-4 mb-2">Séances du jour</h2>

<?php if (empty($todaySeances)): ?>
  <div class="text-muted mb-3">Aucune séance aujourd’hui.</div>
<?php else: ?>
  <div class="card mb-3">
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead>
          <tr>
            <th style="white-space:nowrap;">Heure</th>
            <th>Classe</th>
            <th>Absents (numéros)</th>
            <th style="white-space:nowrap;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($todaySeances as $s): ?>
            <?php
            // DB: heured normal; UI: heuredAffichage + fin calculée selon ramadan
            $todayN = (int)(new DateTime('now'))->format('N');
            $hDb = normH((string)$s['heured']);

            [$ds, $de] = rangeAffichage($hDb, $todayN, $isRamadan, $RAMADAN_SHIFT);
            $range = $ds . ' - ' . $de;

            $abs = trim((string)($s['absents_numeros'] ?? ''));
            ?>
            <tr>
              <td style="white-space:nowrap;"><?= htmlspecialchars($range, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)$s['classe'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if ($abs !== ''): ?>
                  <span class="ms-2"><?= htmlspecialchars($abs, ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="white-space:nowrap;">
                <a class="btn btn-sm btn-outline-primary"
                  href="<?= $baseUrl ?>/seances/<?= (int)$s['id'] ?>">
                  Ouvrir
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<h2 class="h5 mt-4 mb-2">Emploi du temps (global)</h2>

<?php
$now = new DateTime('now');
$todayN = (int)$now->format('N'); // 1=Lundi ... 7=Dimanche
?>

<?php if (empty($hours)): ?>
  <div class="text-muted">Aucun emploi du temps configuré pour l’année en cours.</div>
<?php else: ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0 text-center">
          <thead>
            <tr>
              <th style="white-space:nowrap;text-align:left;">Heure</th>
              <?php foreach ($dayKeys as $dk): ?>
                <th class="day-header"
                  role="button"
                  tabindex="0"
                  data-weekday="<?= (int)$dk ?>"
                  style="cursor:pointer; user-select:none;">
                  <?= htmlspecialchars($days[$dk], ENT_QUOTES, 'UTF-8') ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($hours as $h): ?>
              <?php
              // heured DB (clé grid) vs heuredAffichage (UI)
              $hDb = normH((string)$h);

              [$ds, $de] = rangeAffichage($hDb, $todayN, $isRamadan, $RAMADAN_SHIFT);
              $range = $ds . ' - ' . $de;

              $rowState = slotState($todayN, $hDb, $now, $isRamadan, $RAMADAN_SHIFT);
              $rowStyle = ($todayN <= 6 && $rowState === 'past') ? 'background: rgba(0,0,0,.02); opacity:.75;' : '';
              ?>
              <!-- IMPORTANT: data-heured = heure DB normale (création séance inchangée) -->
              <tr style="<?= $rowStyle ?>" data-heured="<?= htmlspecialchars($hDb, ENT_QUOTES, 'UTF-8') ?>">
                <td style="white-space:nowrap;text-align:left;">
                  <?= htmlspecialchars($range, ENT_QUOTES, 'UTF-8') ?>
                </td>

                <?php foreach ($dayKeys as $dk): ?>
                  <?php
                  $state = slotState($dk, $hDb, $now, $isRamadan, $RAMADAN_SHIFT);

                  // Styles cellule
                  $tdStyle = '';
                  if ($state === 'past') {
                    $tdStyle = 'background: rgba(0,0,0,.03); opacity: .70;';
                  } elseif ($state === 'current') {
                    $tdStyle = 'background: rgba(13,110,253,.10); box-shadow: inset 0 0 0 2px rgba(13,110,253,.35);';
                  } else {
                    $tdStyle = '';
                  }

                  // Grid indexe par heure DB normale
                  $cell = $grid[$dk][$hDb] ?? null;
                  ?>

                  <td style="<?= $tdStyle ?>" data-weekday="<?= (int)$dk ?>">
                    <?php if ($cell): ?>
                      <?php
                      $cid = (int)$cell['idclasse'];
                      $label = (string)$cell['classe'];

                      $style = $classStyle[$cid] ?? ['bg' => 'hsl(0 0% 90%)', 'text' => '#111'];
                      $badgeOpacity = ($state === 'past') ? 'opacity:.75;' : '';
                      ?>

                      <a href="#"
                        class="class-click"
                        data-classe-id="<?= (int)$cid ?>"
                        style="
                           text-decoration:none;
                           display:inline-block;
                           padding: .25rem .5rem;
                           border-radius: 999px;
                           background: <?= htmlspecialchars($style['bg'], ENT_QUOTES, 'UTF-8') ?>;
                           color: <?= htmlspecialchars($style['text'], ENT_QUOTES, 'UTF-8') ?>;
                           border: 1px solid rgba(0,0,0,.10);
                           font-weight: 600;
                           font-size: .85rem;
                           line-height: 1.2;
                           max-width: 200px;
                           white-space: normal;
                           cursor: pointer;
                           <?= $badgeOpacity ?>
                         ">
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                      </a>

                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>

              </tr>
            <?php endforeach; ?>
          </tbody>

        </table>
      </div>

      <div class="mt-3">
        <div class="fw-semibold mb-2">Légende</div>
        <div class="d-flex flex-wrap gap-2">
          <?php
          $classes = $timetable['classes'] ?? [];
          foreach ($classes as $cid => $cname):
            $style = $classStyle[(int)$cid] ?? ['bg' => 'hsl(0 0% 90%)', 'text' => '#111'];
          ?>
            <span
              style="
                display:inline-block;
                padding: .25rem .5rem;
                border-radius: 999px;
                background: <?= htmlspecialchars($style['bg'], ENT_QUOTES, 'UTF-8') ?>;
                color: <?= htmlspecialchars($style['text'], ENT_QUOTES, 'UTF-8') ?>;
                border: 1px solid rgba(0,0,0,.10);
                font-weight: 600;
                font-size: .85rem;
              ">
              <?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="text-muted small mt-2">
        Clique sur une <strong>classe</strong> pour créer une séance (date=aujourd’hui, heure=ligne).
        Clique sur un <strong>jour</strong> pour créer toutes les séances de ce jour (aujourd’hui ou prochaine occurrence).
        <?php if ($isRamadan): ?>
          <br><span class="badge text-bg-warning">RAMADAN</span>
          Affichage adapté: début affiché via mapping + fin = +45 min. La base conserve les horaires normaux.
        <?php endif; ?>
      </div>

    </div>
  </div>
<?php endif; ?>


<h2 class="h5 mt-4 mb-2">Dernière partie réalisée (par classe)</h2>

<?php if (empty($lastParties)): ?>
  <div class="text-muted">Aucune séance/partie trouvée pour l’année en cours.</div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Classe</th>
            <th>Module</th>
            <th>Partie</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lastParties as $cid => $lp): ?>
            <tr>
              <td><?= htmlspecialchars($lp['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="badge bg-secondary">
                  <?= htmlspecialchars($lp['abrev'] ?: $lp['module']) ?>
                </span>
              </td>
              <td>
                <?php if (!empty($lp['partie'])): ?>
                  <strong><?= htmlspecialchars($lp['partie'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <br>
                  <small class="text-muted">
                    <?= htmlspecialchars($lp['abrev'] ?: $lp['module'], ENT_QUOTES, 'UTF-8') ?>
                    <?= $lp['num'] ? ' – ' . htmlspecialchars($lp['num'], ENT_QUOTES, 'UTF-8') : '' ?>
                  </small>
                  <br><br>
                  <span class="badge bg-light text-dark">
                    <?= htmlspecialchars($lp['num']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>