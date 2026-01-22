<div class="modal-header">
  <h5 class="modal-title">Nouvelle séance — <?= htmlspecialchars($classe['classe'], ENT_QUOTES, 'UTF-8') ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<form data-ajax="1" method="post" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/api/seances/create">
  <div class="modal-body">
    <input type="hidden" name="idclasse" value="<?= (int)$classe['id'] ?>">

    <div class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Date</label>
        <input
          type="date"
          name="date"
          class="form-control"
          value="<?= htmlspecialchars(($date ?? (new DateTime('now'))->format('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>"
          required
        >
      </div>

      <div class="col-md-6">
        <label class="form-label">Heure début</label>
        <input
          type="time"
          name="heured"
          class="form-control"
          value="<?= htmlspecialchars(($heured ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          required
        >
      </div>
    </div>

    <div class="text-muted small mt-2">
      Astuce : cette modal peut être pré-remplie depuis l’emploi du temps (clic sur une classe).
    </div>
  </div>

  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Créer</button>
  </div>
</form>
