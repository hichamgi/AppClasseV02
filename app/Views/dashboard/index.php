<?php

$days = [
  1 => 'Lundi',
  2 => 'Mardi',
  3 => 'Mercredi',
  4 => 'Jeudi',
  5 => 'Vendredi',
  6 => 'Samedi',
];

$dayKeys = $showSaturday ? [1,2,3,4,5,6] : [1,2,3,4,5];

$grid = $timetable['grid'] ?? [];
$hours = $timetable['hours'] ?? [];
$classStyle = $timetable['classStyle'] ?? [];

/*

// collecter toutes les heures présentes pour afficher une grille stable
$allHours = [];
foreach ($timetable as $c) {
    foreach (($c['slots'] ?? []) as $n => $hours) {
        foreach (array_keys($hours) as $h) $allHours[$h] = true;
    }
}
$allHours = array_keys($allHours);
sort($allHours);*/
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

<button class="btn btn-sm btn-primary"
        data-modal="/modals/seances/new?idclasse=<?= 122 ?>"
        data-modal-size="modal-lg">
  Nouvelle séance
</button>

<h2 class="h5 mt-4 mb-2">Emploi du temps (global)</h2>

<?php
$now = new DateTime('now');
$todayN = (int)$now->format('N'); // 1=Lundi ... 7=Dimanche

// État d'un créneau: past | current | future
function slotState(int $dayN, string $h, DateTime $now): string
{
    $start = DateTime::createFromFormat('H:i', $h);
    if (!$start) return 'past';
    $end = (clone $start)->modify('+1 hour');

    $todayN = (int)$now->format('N');

    if ($dayN < $todayN) return 'past';
    if ($dayN > $todayN) return 'past';

    $nowMin   = ((int)$now->format('H')) * 60 + (int)$now->format('i');
    $startMin = ((int)$start->format('H')) * 60 + (int)$start->format('i');
    $endMin   = ((int)$end->format('H')) * 60 + (int)$end->format('i');

    if ($nowMin >= $endMin) return 'past';
    if ($nowMin >= $startMin && $nowMin < $endMin) return 'current';
    return 'past';
}
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
                <th><?= htmlspecialchars($days[$dk], ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hours as $h): ?>
              <?php
                $start = DateTime::createFromFormat('H:i', $h);
                $end = (clone $start)->modify('+1 hour');
                $range = $start->format('H:i') . ' - ' . $end->format('H:i');

                // État de la ligne "heure" selon aujourd'hui (utile pour griser toute la ligne si passé)
                // On se base sur le jour "todayN" pour l'état de la ligne
                $rowState = slotState($todayN, $h, $now);
                $rowStyle = ($todayN <= 6 && $rowState === 'past') ? 'background: rgba(0,0,0,.02); opacity:.75;' : '';
              ?>
              <tr style="<?= $rowStyle ?>">
                <td style="white-space:nowrap;text-align:left;">
                  <?= htmlspecialchars($range, ENT_QUOTES, 'UTF-8') ?>
                </td>

                <?php foreach ($dayKeys as $dk): ?>
                  <?php
                    $state = slotState($dk, $h, $now);

                    // Styles cellule
                    $tdStyle = '';
                    if ($state === 'past') {
                        $tdStyle = 'background: rgba(0,0,0,.03); opacity: .70;';
                    } elseif ($state === 'current') {
                        // surlignage propre
                        $tdStyle = 'background: rgba(13,110,253,.10); box-shadow: inset 0 0 0 2px rgba(13,110,253,.35);';
                    }

                    $cell = $grid[$dk][$h] ?? null;
                  ?>

                  <td style="<?= $tdStyle ?>">
                    <?php if ($cell): ?>
                      <?php
                        $cid = (int)$cell['idclasse'];
                        $label = (string)$cell['classe'];

                        $style = $classStyle[$cid] ?? ['bg' => 'hsl(0 0% 90%)', 'text' => '#111'];
                        $badgeOpacity = ($state === 'past') ? 'opacity:.75;' : '';
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
                          line-height: 1.2;
                          max-width: 200px;
                          white-space: normal;
                          <?= $badgeOpacity ?>
                        "
                      >
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                      </span>
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
              "
            >
              <?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="text-muted small mt-2">
        Créneau en cours : surligné. Créneaux passés : grisés.
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
            <th>Dernière séance</th>
            <th>Module</th>
            <th>Partie</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lastParties as $cid => $lp): ?>
            <tr>
              <td><?= htmlspecialchars($lp['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php if (!empty($lp['date'])): ?>
                  <?= htmlspecialchars($lp['date'], ENT_QUOTES, 'UTF-8') ?>
                  <?= htmlspecialchars($lp['heured'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $mod = $lp['abrev'] ?: $lp['module'];
                  echo $mod ? htmlspecialchars((string)$mod, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>';
                ?>
              </td>
              <td>
                <?php if (!empty($lp['partie'])): ?>
                  <?= htmlspecialchars(($lp['num'] ? $lp['num'] . ' - ' : '') . $lp['partie'], ENT_QUOTES, 'UTF-8') ?>
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