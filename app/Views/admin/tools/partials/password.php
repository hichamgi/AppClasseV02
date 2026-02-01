<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="<?= $baseUrl ?>/admin/tools/password" class="row g-3">
      <?= $this->csrfField() ?>

      <div class="col-md-4">
        <label class="form-label">Ancien mot de passe</label>
        <input class="form-control" type="password" name="old" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Nouveau mot de passe</label>
        <input class="form-control" type="password" name="new" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Confirmation</label>
        <input class="form-control" type="password" name="new2" required>
      </div>

      <div class="col-12">
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-check2"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>
