<?php
$annee = $data['annee'] ?? null;
// $report vient du tool.php
?>
<?php if (is_array($report) && !empty($report['ok'])): ?>
  <div class="alert alert-success">
    <div class="fw-semibold mb-1">Rapport import</div>
    <div>
      Nouveaux élèves: <b><?= (int)($report['inserted'] ?? 0) ?></b> —
      Affectations classe: <b><?= (int)($report['attached'] ?? 0) ?></b> —
      Ignorés: <b><?= (int)($report['skipped'] ?? 0) ?></b>
    </div>

    <?php if (!empty($report['errors']) && is_array($report['errors'])): ?>
      <hr class="my-2">
      <div class="small fw-semibold">Erreurs :</div>
      <ul class="mb-0 small">
        <?php foreach ($report['errors'] as $e): ?>
          <li><?= htmlspecialchars((string)$e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (is_array($report) && !empty($report['error']) && empty($report['ok'])): ?>
  <div class="alert alert-danger">
    <div class="fw-semibold">Import échoué</div>
    <div class="small"><?= htmlspecialchars((string)($report['error'] ?? 'ERROR')) ?></div>
    <?php if (!empty($report['details'])): ?>
      <div class="small text-muted"><?= htmlspecialchars((string)$report['details']) ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>


<?php if (!$annee): ?>
  <div class="alert alert-warning">Aucune année scolaire active.</div>
<?php else: ?>

<div class="d-flex gap-2 mb-3">
    <a class="btn btn-primary btn-sm"
        href="/admin/tools/upstudents?modal=add"
        data-modal
        data-modal-size="modal-xl">
        <i class="bi bi-plus"></i> Ajouter un élève
    </a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="fw-semibold mb-1">Import liste</div>
    <div class="text-muted small mb-3">
      Format: <code>numerosgs;nomfr;datenaiss;sexe;classe</code><br>
      Classe normalisée: <code>TCT-1</code> → <code>TCT1</code><br>
      Import liste remplit seulement: <code>nom</code>, <code>numerosgs</code>, <code>datenaiss</code>, <code>sexe</code>.
    </div>

    <form method="post" action="<?= $baseUrl ?>/admin/tools/upstudents">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
      <input type="hidden" name="mode" value="list">

      <textarea class="form-control" name="list" rows="10" placeholder="Colle la liste ici..."></textarea>

      <button class="btn btn-success mt-3" type="submit">
        <i class="bi bi-download"></i> Importer
      </button>
    </form>
  </div>
</div>

<?php endif; ?>
