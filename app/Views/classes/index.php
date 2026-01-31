<?php
use App\Helpers\DateHelper;

$classes = $classes ?? [];
$modules = $modules ?? [];
$progress = $progress ?? [];
$seancesByClasse = $seancesByClasse ?? [];

$baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
$baseUrl = rtrim($baseUrl, '/');

// Palette par module (idmodule commence à 0)
$moduleBadgeMap = [
  0 => 'text-bg-secondary', // Divers
  1 => 'text-bg-primary',
  2 => 'text-bg-success',
  3 => 'text-bg-info',
  4 => 'text-bg-warning',
];

$moduleBarMap = [
  0 => 'bg-secondary',
  1 => 'bg-primary',
  2 => 'bg-success',
  3 => 'bg-info',
  4 => 'bg-warning',
];

$modules = array_values(array_filter($modules, static fn($m) => (int)$m['id'] !== 0));

?>

<h1 class="h4 mb-3">Classes — Suivi & Progression</h1>

<div class="card mb-3">
  <div class="card-header fw-semibold">Progression du programme (par module)</div>

  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Classe</th>
          <?php foreach ($modules as $m): ?>
            <th style="min-width:180px;"><?= $this->e((string)($m['abrev'] ?? $m['module'] ?? ('Module '.$m['id']))) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($classes as $c): ?>
          <?php $cid = (int)$c['id']; ?>
          <tr>
            <td class="fw-semibold"><?= $this->e($c['classe']) ?></td>

            <?php foreach ($modules as $m): ?>
              <?php
                $mid = (int)$m['id'];
                $p = $progress[$cid][$mid] ?? ['pct'=>0.0,'done'=>0,'total'=>0,'done_devoir_types'=>0];

                $pct = (float)($p['pct'] ?? 0.0);
                if ($pct < 0) $pct = 0; if ($pct > 1) $pct = 1;
                $pct100 = (int)round($pct * 100);

                $barClass = $moduleBarMap[$mid] ?? 'bg-dark';
              ?>
              <td>
                <div class="d-flex justify-content-between small text-muted mb-1">
                  <span><?= $this->e((string)$pct100) ?>%</span>
                  <span><?= $this->e((string)($p['done'] ?? 0)) ?>/<?= $this->e((string)($p['total'] ?? 0)) ?></span>
                </div>
                <div class="progress" style="height:10px;">
                  <div class="progress-bar <?= $barClass ?>"
                       style="width: <?= $this->e((string)$pct100) ?>%;"></div>
                </div>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($classes)): ?>
          <tr><td colspan="<?= 1 + count($modules) ?>" class="text-center text-muted py-4">Aucune classe.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="accordion" id="accordionClasses">

  <?php foreach ($classes as $idx => $c): ?>
    <?php
      $cid = (int)$c['id'];
      $collapseId = 'collapse' . $cid;
      $headingId  = 'heading' . $cid;

      $nbF = (int)($c['nb_f'] ?? 0);
      $nbM = (int)($c['nb_m'] ?? 0);
      $tot = (int)($c['total'] ?? 0);

      $seances = $seancesByClasse[$cid] ?? [];
    ?>

    <div class="accordion-item">
      <h2 class="accordion-header" id="<?= $this->e($headingId) ?>">
        <button class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?= $this->e($collapseId) ?>"
                aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>"
                aria-controls="<?= $this->e($collapseId) ?>">
          <strong class="me-2"><?= $this->e($c['classe']) ?></strong>
          <span class="text-muted">
            (<?= $this->e((string)$tot) ?> :
            <?= $this->e((string)$nbF) ?> <i class="bi bi-gender-female"></i>
            |
            <?= $this->e((string)$nbM) ?> <i class="bi bi-gender-male"></i>)
          </span>
        </button>
      </h2>

      <div id="<?= $this->e($collapseId) ?>"
           class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
           aria-labelledby="<?= $this->e($headingId) ?>"
           data-bs-parent="#accordionClasses">

        <div class="accordion-body">

          <!-- Progression par module -->
          <div class="card mb-3">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
              <span>Progression du programme (par module)</span>
              <span class="text-muted small">Basé sur séances ↔ parties</span>
            </div>

            <div class="card-body">
              <?php if (empty($modules)): ?>
                <div class="text-muted">Aucun module.</div>
              <?php else: ?>
                <div class="row g-2">
                  <?php foreach ($modules as $m): ?>
                    <?php
                      $mid = (int)$m['id'];
                      $p = $progress[$cid][$mid] ?? ['pct'=>0.0,'done'=>0,'total'=>0,'done_devoir_types'=>0];

                      $pct = (float)($p['pct'] ?? 0.0);
                      if ($pct < 0) $pct = 0;
                      if ($pct > 1) $pct = 1;

                      $pct100 = (int)round($pct * 100);

                      $done = (int)($p['done'] ?? 0);
                      $total = (int)($p['total'] ?? 0);

                      $barClass = $moduleBarMap[$mid] ?? 'bg-dark';
                      $badgeClass = $moduleBadgeMap[$mid] ?? 'text-bg-dark';

                      $title = trim((string)($m['abrev'] ?? $m['module'] ?? ('Module '.$mid)));
                    ?>
                    <div class="col-12 col-md-6">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge <?= $badgeClass ?>"><?= $this->e((string)$mid) ?></span>
                          <span class="fw-semibold"><?= $this->e($title) ?></span>
                        </div>
                        <div class="text-muted small">
                          <?= $this->e((string)$pct100) ?>% (<?= $this->e((string)$done) ?>/<?= $this->e((string)$total) ?>)
                        </div>
                      </div>

                      <div class="progress" style="height: 10px;">
                        <div class="progress-bar <?= $barClass ?>" role="progressbar"
                             style="width: <?= $this->e((string)$pct100) ?>%;"
                             aria-valuenow="<?= $this->e((string)$pct100) ?>"
                             aria-valuemin="0" aria-valuemax="100"></div>
                      </div>

                      <?php if ((int)($p['done_devoir_types'] ?? 0) >= 3): ?>
                        <div class="small text-success mt-1">
                          <i class="bi bi-check-circle"></i> Devoirs (Pratique/Écrit/Activité) réalisés → 100%
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Séances -->
          <div class="card">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
              <span>Historique des séances</span>
              <span class="text-muted small"><?= $this->e((string)count($seances)) ?> séance(s)</span>
            </div>

            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:120px;">Date</th>
                    <th style="width:90px;">Début</th>
                    <th style="width:90px;">Fin</th>
                    <th>Parties</th>
                    <th style="width:90px;" class="text-center">Abs.</th>
                    <th>Observation</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($seances)): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">Aucune séance.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($seances as $s): ?>
                      <?php
                        $sid = (int)($s['seance_id'] ?? 0);
                        $dateYmd = (string)($s['date'] ?? '');
                        $dateFr = $dateYmd !== '' ? DateHelper::toFr($dateYmd, 'dd/MM/yyyy') : '';

                        $heured = (string)($s['heured'] ?? '');
                        $heuref = (string)($s['heuref'] ?? '');
                        $partiesLabel = (string)($s['parties_label'] ?? '');
                        $abs = (int)($s['absences'] ?? 0);
                        $obs = (string)($s['observation'] ?? '');

                        $url = $baseUrl . '/seances/' . $sid;
                      ?>
                      <tr>
                        <td>
                          <?php if ($sid > 0): ?>
                            <a class="text-decoration-none fw-semibold"
                               href="<?= $this->e($url) ?>"
                               title="Ouvrir la séance #<?= $this->e((string)$sid) ?>">
                              <?= $this->e($dateFr) ?>
                            </a>
                          <?php else: ?>
                            <?= $this->e($dateFr) ?>
                          <?php endif; ?>
                        </td>
                        <td><?= $this->e($heured) ?></td>
                        <td><?= $this->e($heuref) ?></td>
                        <td>
                          <?php
                            $listRaw = (string)($s['parties_list'] ?? '');
                            $parts = $listRaw !== '' ? array_filter(explode('||', $listRaw)) : [];
                          ?>
                          <?php if (empty($parts)): ?>
                            <span class="text-muted small">—</span>
                          <?php else: ?>
                            <ul class="mb-0 ps-3 small">
                              <?php foreach ($parts as $li): ?>
                                <li><?= $this->e(trim($li)) ?></li>
                              <?php endforeach; ?>
                            </ul>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php
                            $absCount = (int)($s['absents_count'] ?? 0);
                            $preCount = (int)($s['presents_count'] ?? 0);

                            $absNums = trim((string)($s['absents_nums'] ?? ''));
                            $preNums = trim((string)($s['presents_nums'] ?? ''));

                            // Si pas de données -> —
                            if ($absCount === 0 && $preCount === 0) {
                                echo '<span class="text-muted small">—</span>';
                            } else {
                                if ($preCount < $absCount) {
                                    // Présents moins nombreux → afficher présents
                                    echo '<span class="small fw-semibold">P :</span> ';
                                    echo $preNums !== '' ? '<span class="small">'.$this->e($preNums).'</span>' : '<span class="text-muted small">0</span>';
                                } else {
                                    // Sinon afficher absents
                                    echo '<span class="small fw-semibold">A :</span> ';
                                    echo $absNums !== '' ? '<span class="small">'.$this->e($absNums).'</span>' : '<span class="text-muted small">0</span>';
                                }
                            }
                          ?>
                        </td>
                        <td>
                          <?php if ($obs === ''): ?>
                            <span class="text-muted small">—</span>
                          <?php else: ?>
                            <span class="small"><?= $this->e($obs) ?></span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

  <?php endforeach; ?>

  <?php if (empty($classes)): ?>
    <div class="text-center text-muted py-5">
      Aucune classe (année scolaire en cours).
    </div>
  <?php endif; ?>

</div>
