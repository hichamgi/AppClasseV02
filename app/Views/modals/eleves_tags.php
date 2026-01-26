<?php $ideleve = (int)$eleve['id']; ?>

<div class="modal-header">
  <h5 class="modal-title">
    Tags — <?= $this->e(trim(($eleve['nom'] ?? '').' '.($eleve['prenom'] ?? ''))) ?>
  </h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

  <div class="mb-3">
    <div class="fw-semibold mb-2">Tags associés</div>

    <?php if (empty($allTags)): ?>
      <div class="text-muted">Aucun tag n’existe encore.</div>
    <?php else: ?>
      <div class="d-flex flex-column gap-1" id="tagsList">
        <?php foreach ($allTags as $t): ?>
          <?php $tid = (int)$t['id']; $checked = !empty($selectedMap[$tid]); ?>
          <label class="d-flex align-items-center gap-2">
            <input class="form-check-input js-tag-check" type="checkbox"
                   value="<?= $tid ?>" <?= $checked ? 'checked' : '' ?>>
            <span class="badge text-bg-<?= $this->e($t['color'] ?: 'secondary') ?>">
              <?= $this->e($t['tag']) ?>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <hr>

  <div class="mb-2 fw-semibold">Créer un nouveau tag</div>
  <div class="row g-2 align-items-center">
    <div class="col-7">
      <input class="form-control" id="tagNewLabel" placeholder="Ex: Maladie, Retard, Dispense..." maxlength="250">
    </div>
    <div class="col-3">
      <select class="form-select" id="tagNewColor">
        <option value="secondary">secondary</option>
        <option value="primary">primary</option>
        <option value="success">success</option>
        <option value="warning">warning</option>
        <option value="danger">danger</option>
        <option value="info">info</option>
        <option value="dark">dark</option>
      </select>
    </div>
    <div class="col-2">
      <button class="btn btn-outline-primary w-100" id="btnTagCreate">Créer</button>
    </div>
  </div>

  <div class="alert alert-danger d-none mt-3" id="tagErr"></div>
  <div class="alert alert-success d-none mt-3" id="tagOk"></div>

</div>

<div class="modal-footer">
  <button class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
  <button class="btn btn-primary" id="btnTagSave" data-ideleve="<?= $ideleve ?>">
    Enregistrer
  </button>
</div>
