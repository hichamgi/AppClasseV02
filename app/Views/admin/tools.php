<?php
    $baseUrl = (require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '';
    $baseUrl = rtrim($baseUrl, '/');
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="m-0">Administration</h3>
  <a class="btn btn-outline-secondary btn-sm" href="<?= $baseUrl ?>/admin">Retour dashboard</a>
</div>

<div class="row g-3">
  <?php foreach ($tools as $key => $t): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <a class="text-decoration-none" href="<?= $baseUrl ?>/admin/tools/<?= htmlspecialchars($key) ?>">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-<?= htmlspecialchars($t['icon']) ?>"></i>
              <div class="fw-semibold"><?= htmlspecialchars($t['title']) ?></div>
            </div>
            <div class="text-muted small"><?= $t['body'] /* body contient déjà du HTML encodé */ ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
