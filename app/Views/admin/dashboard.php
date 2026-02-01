<?php
    $baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
    $baseUrl = rtrim($baseUrl, '/');
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h3 class="m-0">Admin Dashboard</h3>
    <div class="text-muted small">
      <?php if ($annee): ?>
        Année courante : <b><?= $this->e($annee['annee'] ?? '') ?></b>
      <?php else: ?>
        Aucune année scolaire active.
      <?php endif; ?>
    </div>
  </div>

  <a class="btn btn-primary btn-sm" href="<?= $baseUrl ?>/admin/tools">
    <i class="bi bi-gear"></i> Administration
  </a>
</div>

<div class="row g-3">
  <div class="col-12 col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Classes</div>
        <div class="fs-4 fw-semibold"><?= (int)$classesCount ?></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Élèves actifs</div>
        <div class="fs-4 fw-semibold"><?= (int)$elevesCount ?></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Séances اليوم</div>
        <div class="fs-4 fw-semibold"><?= (int)($today['seances'] ?? 0) ?></div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Absences اليوم</div>
        <div class="fs-4 fw-semibold"><?= (int)($today['absences'] ?? 0) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="mt-3">
  <?php if ((int)$ramadan === 1): ?>
    <span class="badge text-bg-success"><i class="bi bi-moon-stars"></i> Ramadan ON</span>
  <?php else: ?>
    <span class="badge text-bg-secondary"><i class="bi bi-moon-stars"></i> Ramadan OFF</span>
  <?php endif; ?>
</div>
