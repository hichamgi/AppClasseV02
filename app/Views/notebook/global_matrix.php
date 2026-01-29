<?php
$classes = $classes ?? [];
$parties = $parties ?? [];
$matrix  = $matrix ?? [];
$orphans = $orphans ?? [];
$filters = $filters ?? [];

$dateFrom = $filters['date_from'] ?? '';
$dateTo   = $filters['date_to'] ?? '';
$anneeId  = $filters['annee_id'] ?? '';
$moduleId = $filters['module_id'] ?? '';

$baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
$baseUrl = rtrim($baseUrl, '/');
?>

<h1 class="h4 mb-3">Cahier de texte — Matrice (Parties × Classes)</h1>

<form method="get" class="card mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-sm-3">
        <label class="form-label">Du</label>
        <input type="date" name="date_from" class="form-control" value="<?= $this->e((string)$dateFrom) ?>">
      </div>
      <div class="col-sm-3">
        <label class="form-label">Au</label>
        <input type="date" name="date_to" class="form-control" value="<?= $this->e((string)$dateTo) ?>">
      </div>
      <div class="col-sm-3">
        <label class="form-label">Module ID (optionnel)</label>
        <input type="number" min="1" name="module_id" class="form-control" value="<?= $this->e((string)$moduleId) ?>">
      </div>

      <div class="col-12 mt-2 d-flex gap-2">
        <button class="btn btn-dark" type="submit"><i class="bi bi-funnel"></i> Filtrer</button>
        <a class="btn btn-outline-secondary" href="<?= $baseUrl ?>/notebook/global"><i class="bi bi-x-circle"></i> Réinitialiser</a>
      </div>
    </div>
  </div>
</form>

<div class="card">
  <div class="card-header fw-semibold">Matrice</div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="min-width:320px;">Partie</th>
          <?php foreach ($classes as $c): ?>
            <th class="text-center" style="min-width:180px;"><?= $this->e($c['classe']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>

      <tbody>
        <!-- Ligne Orphelines -->
        <tr class="table-danger">
          <td class="fw-semibold">
            <i class="bi bi-exclamation-triangle"></i> Séances non affectées
          </td>

          <?php foreach ($classes as $c): ?>
            <?php
              $cid = (int)$c['id'];
              $dates = $orphans[$cid] ?? [];
            ?>
            <td>
              <?php if (empty($dates)): ?>
                <span class="text-muted small">—</span>
              <?php else: ?>
                <?php foreach ($dates as $it): ?>
                    <a class="badge text-bg-danger me-1 mb-1 text-decoration-none"
                        href="<?= $baseUrl ?>/seances/<?= (int)$it['id'] ?>">
                        <?= $this->e((string)$it['date']) ?>
                    </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>

        <!-- Lignes Parties -->
        <?php foreach ($parties as $p): ?>
          <?php $pid = (int)$p['partie_id']; ?>
          <tr>
            <td class="fw-semibold"><?= $this->e($p['label']) ?></td>

            <?php foreach ($classes as $c): ?>
              <?php
                $cid = (int)$c['id'];
                $dates = $matrix[$pid][$cid] ?? [];
              ?>
              <td>
                <?php if (empty($dates)): ?>
                  <span class="text-muted small">—</span>
                <?php else: ?>
                    <?php foreach ($dates as $it): ?>
                        <a class="badge text-bg-secondary me-1 mb-1 text-decoration-none"
                            href="<?= $baseUrl ?>/seances/<?= (int)$it['id'] ?>">
                            <?= $this->e((string)$it['date']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($parties)): ?>
          <tr>
            <td colspan="<?= 1 + count($classes) ?>" class="text-center text-muted py-4">
              Aucune partie trouvée (vérifie module_id).
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
