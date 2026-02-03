<?php 
    $classes = $classes ?? []; 
    $baseUrl = (require dirname(__DIR__, 4) . '/config/app.php')['base_url'] ?? '';
    $baseUrl = rtrim($baseUrl, '/');
?>

<div class="modal-header">
  <h5 class="modal-title">Ajouter un élève (complet)</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
  <form method="post" action="<?= $baseUrl ?>/admin/tools/upstudents">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
    <input type="hidden" name="mode" value="single">

    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Numero SGS</label>
        <input class="form-control" name="numerosgs">
      </div>

      <div class="col-md-4">
        <label class="form-label">Sexe</label>
        <select class="form-select" name="sexe">
          <option value="M">M</option>
          <option value="F">F</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Date naissance</label>
        <input class="form-control" name="datenaiss" placeholder="YYYY-MM-DD">
      </div>

      <div class="col-md-6">
        <label class="form-label">Nom (FR)</label>
        <input class="form-control" name="nom" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Prénom (FR)</label>
        <input class="form-control" name="prenom">
      </div>

      <div class="col-md-6">
        <label class="form-label">Nom (AR)</label>
        <input class="form-control" name="nomar" dir="rtl">
      </div>

      <div class="col-md-6">
        <label class="form-label">Prénom (AR)</label>
        <input class="form-control" name="prenomar" dir="rtl">
      </div>

      <div class="col-md-12">
        <label class="form-label">Classe</label>
        <select class="form-select" name="classe" required>
          <option value="">--</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= htmlspecialchars((string)($c['classe'] ?? '')) ?>">
              <?= htmlspecialchars((string)($c['classe'] ?? '')) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-12">
        <label class="form-label">Observation</label>
        <textarea class="form-control" name="observation" rows="3"></textarea>
      </div>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary" type="submit">
        <i class="bi bi-check2"></i> Enregistrer
      </button>
    </div>
  </form>
</div>
