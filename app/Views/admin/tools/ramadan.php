<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="m-0">Ramadan</h3>
  <a class="btn btn-outline-secondary btn-sm" href="<?= $baseUrl ?>/admin/tools">Retour</a>
</div>

<?php if (!empty($_GET['ok'])): ?>
  <div class="alert alert-success py-2">Enregistré.</div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-semibold">Mode Ramadan</div>
        <div class="text-muted small">
          Active/désactive le mode Ramadan (sans enregistrer de dates).
        </div>
      </div>

      <form method="post" action="<?= $baseUrl ?>/admin/tools/ramadan" class="m-0">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <input type="hidden" name="ramadan" value="<?= $ramadan ? 0 : 1 ?>">
        <button class="btn <?= $ramadan ? 'btn-success' : 'btn-outline-secondary' ?>" type="submit">
          <?= $ramadan ? 'Activé' : 'Désactivé' ?>
        </button>
      </form>
    </div>
  </div>
</div>
