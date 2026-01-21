<div class="modal-header">
  <h5 class="modal-title">Nouvelle séance — <?= htmlspecialchars($classe['classe'], ENT_QUOTES, 'UTF-8') ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<form data-ajax="1" method="post" action="<?= $baseUrl ?>/api/seances/create">
  <div class="modal-body">
    <input type="hidden" name="idclasse" value="<?= (int)$classe['id'] ?>">

    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control" value="<?= (new DateTime('now'))->format('Y-m-d') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Heure début</label>
        <input type="time" name="heured" class="form-control" required>
      </div>
    </div>
  </div>

  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Créer</button>
  </div>
</form>
