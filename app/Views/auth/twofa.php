<?php $baseUrl = rtrim((require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '', '/'); ?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">Vérification (2FA)</h1>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $baseUrl ?>/twofa">
          <?= $this->csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Code</label>
            <input class="form-control" name="code" inputmode="numeric" maxlength="6" required>
          </div>
          <button class="btn btn-primary w-100">Valider</button>
        </form>
      </div>
    </div>
  </div>
</div>