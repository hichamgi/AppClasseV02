<?php $baseUrl = rtrim((require dirname(__DIR__, 2) . '/config/app.php')['base_url'] ?? '', '/'); ?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">Connexion</h1>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $baseUrl ?>/login">
          <?= $this->csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Utilisateur</label>
            <input class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mot de passe</label>
            <input class="form-control" name="password" type="password" required>
          </div>
          <button class="btn btn-primary w-100">Se connecter</button>
        </form>

        <div class="mt-3 text-muted small">
          Astuce: dans la table <code>users</code>, le champ <code>password</code> doit être un hash <code>password_hash()</code>.
        </div>
      </div>
    </div>
  </div>
</div>