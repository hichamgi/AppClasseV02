<?php
$annee = $data['annee'] ?? null;
$classes = $data['classes'] ?? [];
$selected = (int)($data['selectedClasse'] ?? 0);
$rows = $data['rows'] ?? [];
?>

<?php if (!$annee): ?>
  <div class="alert alert-warning">Aucune année scolaire active.</div>
<?php else: ?>
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2" method="get" action="<?= $baseUrl ?>/admin/tools/students">
        <div class="col-md-6">
          <select class="form-select" name="classe" onchange="this.form.submit()">
            <option value="0">-- Choisir une classe --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $selected) ? 'selected' : '' ?>>
                <?= $this->e($c['classe'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <button class="btn btn-outline-secondary w-100" type="submit">Afficher</button>
        </div>
      </form>
    </div>
  </div>

  <?php if ($selected > 0): ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:70px">N°</th>
                <th>Nom</th>
                <th class="text-muted">AR</th>
                <th style="width:90px">Départ</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= (int)($r['numero'] ?? 0) ?></td>
                  <td><?= $this->e(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? '')) ?></td>
                  <td class="text-muted small"><?= $this->e(($r['nomar'] ?? '') . ' ' . ($r['prenomar'] ?? '')) ?></td>
                  <td><?= ((int)($r['depart'] ?? 0) === 1) ? 'Oui' : 'Non' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
