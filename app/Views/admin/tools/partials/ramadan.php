<?php $ramadan = (int)($data['ramadan'] ?? 0);?>

<div class="card shadow-sm">
  <div class="card-body d-flex align-items-center justify-content-between">
    <div>
      <div class="fw-semibold">Mode Ramadan</div>
      <div class="text-muted small">Flag unique (0/1) stocké dans users.ramadan</div>
    </div>

    <form method="post" action="<?= $baseUrl ?>/admin/tools/ramadan" class="m-0">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
      <input type="hidden" name="ramadan" value="<?= $ramadan ? 0 : 1 ?>">
      <button class="btn <?= $ramadan ? 'btn-success' : 'btn-outline-secondary' ?>" type="submit">
        <?= $ramadan ? 'ON' : 'OFF' ?>
      </button>
    </form>
  </div>
</div>